<?php
/*
 * WANDAI System - Beban Kerja Controller
 * BPS Kabupaten Paniai
 * 
 * UPDATED:
 * - Akses untuk Operator dan Admin
 * - Filter: Wilayah Kerja, Bulan, Tahun (default tahun berjalan)
 * - Kegiatan Berjalan/Selesai berdasarkan TANGGAL (bukan progress)
 * - Mitra dengan beban tertinggi per wilayah (Paniai & Intan Jaya)
 */

if (session_status() === PHP_SESSION_NONE) session_start();

class BebanKerjaController
{
    public function index()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        // Akses untuk Operator dan Admin
        $role = $_SESSION['user']['role_name'] ?? '';
        $roleId = $_SESSION['user']['role_id'] ?? 0;
        
        // role_id: 1=admin, 2=kepala, 3=kasubbag, 4=ppk, 5=bendahara, 6=operator
        $allowedRoles = ['admin', 'operator', 'Admin', 'Operator'];
        $allowedRoleIds = [1, 6]; // admin, operator

        if (!in_array($role, $allowedRoles) && !in_array($roleId, $allowedRoleIds)) {
            header('Location: index.php?controller=dashboard');
            exit;
        }

        require 'config/database.php';

        // Get filter parameters
        $filterWilayah = $_GET['wilayah'] ?? '';
        $filterBulan = $_GET['bulan'] ?? '';
        $filterTahun = $_GET['tahun'] ?? date('Y'); // Default tahun berjalan

        // Get available years from database
        $sqlYears = "
            SELECT DISTINCT YEAR(kd.rentang_waktu_mulai) AS tahun
            FROM kegiatan_detail kd
            WHERE kd.rentang_waktu_mulai IS NOT NULL
            UNION
            SELECT DISTINCT YEAR(kd.rentang_waktu_selesai) AS tahun
            FROM kegiatan_detail kd
            WHERE kd.rentang_waktu_selesai IS NOT NULL
            ORDER BY tahun DESC
        ";
        $stmtYears = $pdo->query($sqlYears);
        $availableYears = $stmtYears->fetchAll(PDO::FETCH_COLUMN);

        // Get available wilayah from database
        $sqlWilayah = "SELECT DISTINCT wilayah_kerja FROM mitra WHERE wilayah_kerja IS NOT NULL AND wilayah_kerja != '' ORDER BY wilayah_kerja";
        $stmtWilayah = $pdo->query($sqlWilayah);
        $availableWilayah = $stmtWilayah->fetchAll(PDO::FETCH_COLUMN);

        // Build filter conditions for kegiatan
        $kegiatanFilterSql = "";
        $kegiatanParams = [];
        
        if (!empty($filterTahun) && $filterTahun !== 'all') {
            $kegiatanFilterSql .= " AND (YEAR(kd.rentang_waktu_mulai) = ? OR YEAR(kd.rentang_waktu_selesai) = ?)";
            $kegiatanParams[] = $filterTahun;
            $kegiatanParams[] = $filterTahun;
        }
        
        if (!empty($filterBulan)) {
            $kegiatanFilterSql .= " AND (DATE_FORMAT(kd.rentang_waktu_mulai, '%Y-%m') = ? OR DATE_FORMAT(kd.rentang_waktu_selesai, '%Y-%m') = ?)";
            $kegiatanParams[] = $filterBulan;
            $kegiatanParams[] = $filterBulan;
        }

        // Query untuk menghitung jumlah kegiatan per mitra
        // Kegiatan Berjalan/Selesai berdasarkan TANGGAL (seperti dashboard)
        $sql = "
            SELECT 
                m.id,
                m.nama,
                m.nik,
                m.email,
                m.alamat,
                m.wilayah_kerja,
                m.status,
                COUNT(DISTINCT kp.kegiatan_detail_id) AS jumlah_kegiatan,
                COUNT(DISTINCT CASE 
                    WHEN kd.rentang_waktu_mulai <= CURDATE() AND kd.rentang_waktu_selesai >= CURDATE()
                    THEN kp.kegiatan_detail_id 
                END) AS kegiatan_berjalan,
                COUNT(DISTINCT CASE 
                    WHEN kd.rentang_waktu_selesai < CURDATE()
                    THEN kp.kegiatan_detail_id 
                END) AS kegiatan_selesai,
                CASE 
                    WHEN SUM(kp.target) > 0 THEN ROUND((SUM(kp.realisasi) / SUM(kp.target)) * 100, 2)
                    ELSE 0 
                END AS progress_keseluruhan
            FROM mitra m
            LEFT JOIN kegiatan_petugas kp ON kp.petugas_id = m.id AND kp.petugas_source = 'mitra'
            LEFT JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
            WHERE m.status = 'aktif'
            " . ($filterWilayah ? " AND m.wilayah_kerja = ?" : "") . "
            " . str_replace("?", "?", $kegiatanFilterSql) . "
            GROUP BY m.id, m.nama, m.nik, m.email, m.alamat, m.wilayah_kerja, m.status
            ORDER BY jumlah_kegiatan DESC, m.nama ASC
        ";

        $params = [];
        if ($filterWilayah) {
            $params[] = $filterWilayah;
        }
        $params = array_merge($params, $kegiatanParams);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $dataMitra = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query untuk menghitung total kegiatan (dengan filter)
        $sqlTotalKegiatan = "
            SELECT COUNT(DISTINCT kd.id) AS total
            FROM kegiatan_detail kd
            WHERE 1=1
            " . $kegiatanFilterSql . "
        ";
        $stmtTotal = $pdo->prepare($sqlTotalKegiatan);
        $stmtTotal->execute($kegiatanParams);
        $totalKegiatanResult = $stmtTotal->fetch(PDO::FETCH_ASSOC);
        $totalKegiatan = (int)($totalKegiatanResult['total'] ?? 0);

        // Hitung statistik keseluruhan
        $totalMitra = count($dataMitra);

        // Mitra dengan beban tertinggi per wilayah
        $mitraBebanTertinggiPaniai = null;
        $mitraBebanTertinggiIntanJaya = null;
        
        foreach ($dataMitra as $mitra) {
            $wilayah = strtolower(trim($mitra['wilayah_kerja'] ?? ''));
            
            if (strpos($wilayah, 'paniai') !== false) {
                if ($mitraBebanTertinggiPaniai === null || $mitra['jumlah_kegiatan'] > $mitraBebanTertinggiPaniai['jumlah_kegiatan']) {
                    $mitraBebanTertinggiPaniai = $mitra;
                }
            } elseif (strpos($wilayah, 'intan') !== false || strpos($wilayah, 'jaya') !== false) {
                if ($mitraBebanTertinggiIntanJaya === null || $mitra['jumlah_kegiatan'] > $mitraBebanTertinggiIntanJaya['jumlah_kegiatan']) {
                    $mitraBebanTertinggiIntanJaya = $mitra;
                }
            }
        }

        // Beban tertinggi keseluruhan (untuk backward compatibility)
        $bebanTertinggi = !empty($dataMitra) ? max(array_column($dataMitra, 'jumlah_kegiatan')) : 0;

        // Pass filter values to view
        $currentFilters = [
            'wilayah' => $filterWilayah,
            'bulan' => $filterBulan,
            'tahun' => $filterTahun
        ];

        include 'views/beban_kerja/index.php';
    }

    public function getDetailKegiatan()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        // Akses untuk Operator dan Admin
        $role = $_SESSION['user']['role_name'] ?? '';
        $roleId = $_SESSION['user']['role_id'] ?? 0;
        $allowedRoles = ['admin', 'operator', 'Admin', 'Operator'];
        $allowedRoleIds = [1, 6];

        if (!in_array($role, $allowedRoles) && !in_array($roleId, $allowedRoleIds)) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        $mitraId = $_GET['mitra_id'] ?? null;
        if (!$mitraId) {
            echo json_encode(['success' => false, 'error' => 'Missing mitra_id']);
            exit;
        }

        // Get filter parameters
        $filterBulan = $_GET['bulan'] ?? '';
        $filterTahun = $_GET['tahun'] ?? date('Y');

        require 'config/database.php';

        // Build filter conditions
        $filterSql = "";
        $params = [$mitraId];
        
        if (!empty($filterTahun) && $filterTahun !== 'all') {
            $filterSql .= " AND (YEAR(kd.rentang_waktu_mulai) = ? OR YEAR(kd.rentang_waktu_selesai) = ?)";
            $params[] = $filterTahun;
            $params[] = $filterTahun;
        }
        
        if (!empty($filterBulan)) {
            $filterSql .= " AND (DATE_FORMAT(kd.rentang_waktu_mulai, '%Y-%m') = ? OR DATE_FORMAT(kd.rentang_waktu_selesai, '%Y-%m') = ?)";
            $params[] = $filterBulan;
            $params[] = $filterBulan;
        }

        // Query untuk mengambil detail kegiatan per mitra
        // Status kegiatan berdasarkan TANGGAL
        $sql = "
            SELECT DISTINCT
                kp.id AS kegiatan_petugas_id,
                kd.id,
                kd.nama_kegiatan,
                kd.jenis_kegiatan,
                kd.rentang_waktu_mulai,
                kd.rentang_waktu_selesai,
                kp.target,
                kp.realisasi,
                kp.peran,
                CASE 
                    WHEN kp.target > 0 THEN ROUND((kp.realisasi / kp.target) * 100, 2)
                    ELSE 0 
                END AS progress,
                CASE 
                    WHEN kd.rentang_waktu_selesai < CURDATE() THEN 'Selesai'
                    WHEN kd.rentang_waktu_mulai <= CURDATE() AND kd.rentang_waktu_selesai >= CURDATE() THEN 'Berjalan'
                END AS status_kegiatan,
                t.name AS team_name
            FROM kegiatan_petugas kp
            JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
            LEFT JOIN teams t ON t.id = kd.team_id
            WHERE kp.petugas_id = ? 
            AND kp.petugas_source = 'mitra'
            " . $filterSql . "
            ORDER BY 
                CASE 
                    WHEN kd.rentang_waktu_mulai <= CURDATE() AND kd.rentang_waktu_selesai >= CURDATE() THEN 1
                    WHEN kd.rentang_waktu_mulai > CURDATE() THEN 2
                    ELSE 3
                END,
                kd.rentang_waktu_selesai DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    /**
     * API untuk mendapatkan data mitra dengan beban tertinggi (untuk Dashboard)
     */
    public function getMitraBebanTertinggi()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        require 'config/database.php';

        $filterTahun = $_GET['tahun'] ?? date('Y');

        // Build filter
        $filterSql = "";
        $params = [];
        
        if (!empty($filterTahun) && $filterTahun !== 'all') {
            $filterSql = " AND (YEAR(kd.rentang_waktu_mulai) = ? OR YEAR(kd.rentang_waktu_selesai) = ?)";
            $params = [$filterTahun, $filterTahun];
        }

        // Query untuk mitra dengan beban tertinggi per wilayah
        $sql = "
            SELECT 
                m.id,
                m.nama,
                m.wilayah_kerja,
                COUNT(DISTINCT kp.kegiatan_detail_id) AS jumlah_kegiatan
            FROM mitra m
            LEFT JOIN kegiatan_petugas kp ON kp.petugas_id = m.id AND kp.petugas_source = 'mitra'
            LEFT JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
            WHERE m.status = 'aktif'
            " . $filterSql . "
            GROUP BY m.id, m.nama, m.wilayah_kerja
            ORDER BY jumlah_kegiatan DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $allMitra = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pisahkan per wilayah
        $mitraPaniai = null;
        $mitraIntanJaya = null;
        
        foreach ($allMitra as $mitra) {
            $wilayah = strtolower(trim($mitra['wilayah_kerja'] ?? ''));
            
            if (strpos($wilayah, 'paniai') !== false && $mitraPaniai === null) {
                $mitraPaniai = $mitra;
            } elseif ((strpos($wilayah, 'intan') !== false || strpos($wilayah, 'jaya') !== false) && $mitraIntanJaya === null) {
                $mitraIntanJaya = $mitra;
            }
            
            if ($mitraPaniai !== null && $mitraIntanJaya !== null) {
                break;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'paniai' => $mitraPaniai,
                'intan_jaya' => $mitraIntanJaya
            ]
        ]);
        exit;
    }
}