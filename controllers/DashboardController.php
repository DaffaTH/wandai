<?php
/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * 
 * FIXED:
 * - Dashboard menampilkan SEMUA kegiatan tanpa filter role/tim secara default
 * - Filter tim bekerja untuk SELURUH komponen (bukan hanya deadline)
 * - Tambah filter tahun dengan opsi berdasarkan data yang ada di database
 * - PERBAIKAN: Realisasi dan Target diambil dari kegiatan_petugas (bukan kegiatan_detail)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'models/KegiatanDetail.php';

class DashboardController
{
    public function index()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        // Cek apakah user adalah mitra
        $userType = $_SESSION['user']['user_type'] ?? 'user';
        $isMitra = ($userType === 'mitra');
        $role = $_SESSION['user']['role_name'] ?? '';

        require 'config/database.php';

        // Tab aktif: matriks (default) atau monitoring.
        // Mitra tidak punya tab matriks.
        $activeTab = $_GET['tab'] ?? 'matriks';
        if ($isMitra) $activeTab = 'monitoring';
        if (!in_array($activeTab, ['matriks', 'monitoring'])) $activeTab = 'matriks';

        // Data matriks (selalu di-load untuk non-mitra)
        $matriksData = [];
        $bulanAktif = $_GET['bulan'] ?? date('Y-m');
        if (!$isMitra) {
            $matriksData = $this->buildMatriksData($pdo, $bulanAktif);
        }

        if ($isMitra) {
            // Logic untuk mitra - ambil kegiatan yang sudah di-assign dengan progress individual
            $mitraId = $_SESSION['user']['id'];
            
            // Query untuk mengambil kegiatan yang sudah di-assign ke mitra dengan progress individual
            // Per-jenis row: JOIN ke kegiatan_jenis supaya 1 kegiatan dengan N jenis
            // tampil sebagai N baris terpisah (masing-masing punya rentang & satuan sendiri).
            $sql = "
                SELECT DISTINCT
                    kd.id,
                    kd.nama_kegiatan,
                    kj.jenis AS jenis_kegiatan,
                    COALESCE(kj.tanggal_mulai,   kd.rentang_waktu_mulai)   AS rentang_waktu_mulai,
                    COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai) AS rentang_waktu_selesai,
                    kp.realisasi AS realisasi,  -- Progress individual mitra
                    kp.target AS target,        -- Target individual mitra
                    COALESCE(kj.satuan, kd.satuan) AS satuan,
                    CASE
                        WHEN kp.target > 0 THEN ROUND((kp.realisasi / kp.target) * 100, 2)
                        ELSE 0
                    END AS progress,            -- Progress individual mitra
                    kd.komentar,
                    t.name AS team_name,
                    t.id AS team_id,
                    kp.peran
                FROM kegiatan_petugas kp
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                LEFT JOIN kegiatan_jenis kj ON kj.kegiatan_id = kd.id
                LEFT JOIN teams t ON t.id = kd.team_id
                WHERE kp.petugas_id = ?
                AND kp.petugas_source = 'mitra'
                AND (kj.jenis IS NULL OR kj.jenis <> 'Pelatihan/Briefing')
                ORDER BY COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai) DESC, kd.nama_kegiatan ASC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$mitraId]);
            $kegiatanMitra = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Set $kegiatanTable untuk mitra
            $kegiatanTable = $kegiatanMitra;
            
            $teams = [];
            $availableYears = [];
            $upcomingDeadlines = []; // Mitra tidak perlu reminder untuk sementara
        } else {
            // Logic untuk user biasa (SEMUA ROLE termasuk kepala, admin, operator, ppk, bendahara)
            $model = new KegiatanDetail();
            
            // Ambil data teams untuk dropdown filter
            $teams = $model->getAllTeams();

            // Ambil daftar tahun yang tersedia dari database untuk filter
            $sqlYears = "
                SELECT DISTINCT 
                    YEAR(rentang_waktu_mulai) AS tahun
                FROM kegiatan_detail
                WHERE rentang_waktu_mulai IS NOT NULL
                UNION
                SELECT DISTINCT 
                    YEAR(rentang_waktu_selesai) AS tahun
                FROM kegiatan_detail
                WHERE rentang_waktu_selesai IS NOT NULL
                ORDER BY tahun DESC
            ";
            $stmtYears = $pdo->query($sqlYears);
            $availableYears = $stmtYears->fetchAll(PDO::FETCH_COLUMN);

            // Filter dari GET parameter
            $teamId = null;
            if (isset($_GET['team_id']) && !empty($_GET['team_id'])) {
                $teamId = $_GET['team_id'];
            }

            // Filter tahun - default ke tahun berjalan jika tidak dipilih
            $currentYear = date('Y');
            $filterYear = isset($_GET['tahun']) && !empty($_GET['tahun']) ? $_GET['tahun'] : $currentYear;

            // PERBAIKAN UTAMA: Tampilkan SEMUA kegiatan untuk SEMUA role
            // PERBAIKAN: Realisasi dan Target diambil dari SUM kegiatan_petugas
            // CATATAN: Hanya hitung PML saja karena PML sudah merupakan agregasi dari PPL di bawahnya
            // Per-jenis row: JOIN ke kegiatan_jenis supaya 1 kegiatan dengan N jenis
            // tampil sebagai N baris terpisah (masing-masing punya rentang & satuan sendiri).
            $sql = "
                SELECT
                    kd.id,
                    kd.nama_kegiatan,
                    kj.jenis AS jenis_kegiatan,
                    COALESCE(kj.tanggal_mulai,   kd.rentang_waktu_mulai)   AS rentang_waktu_mulai,
                    COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai) AS rentang_waktu_selesai,
                    COALESCE(petugas_agg.total_realisasi, 0) AS realisasi,
                    COALESCE(petugas_agg.total_target, 0) AS target,
                    COALESCE(kj.satuan, kd.satuan) AS satuan,
                    CASE
                        WHEN COALESCE(petugas_agg.total_target, 0) > 0
                        THEN ROUND((COALESCE(petugas_agg.total_realisasi, 0) / petugas_agg.total_target) * 100, 2)
                        ELSE 0
                    END AS progress,
                    kd.komentar,
                    t.name AS team_name,
                    t.id AS team_id,
                    NULL as peran
                FROM kegiatan_detail kd
                LEFT JOIN kegiatan_jenis kj ON kj.kegiatan_id = kd.id
                LEFT JOIN teams t ON t.id = kd.team_id
                LEFT JOIN (
                    SELECT
                        kegiatan_detail_id,
                        SUM(realisasi) AS total_realisasi,
                        SUM(target) AS total_target
                    FROM kegiatan_petugas
                    WHERE peran = 'PML'
                    GROUP BY kegiatan_detail_id
                ) petugas_agg ON petugas_agg.kegiatan_detail_id = kd.id
                WHERE 1=1
                AND (kj.jenis IS NULL OR kj.jenis <> 'Pelatihan/Briefing')
            ";
            $params = [];

            // Filter berdasarkan tahun (gunakan tanggal per-jenis kalau ada, fallback ke kegiatan_detail)
            if (!empty($filterYear) && $filterYear !== 'all') {
                $sql .= " AND (
                    YEAR(COALESCE(kj.tanggal_mulai,   kd.rentang_waktu_mulai))   = ?
                    OR YEAR(COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai)) = ?
                )";
                $params[] = $filterYear;
                $params[] = $filterYear;
            }

            // Filter berdasarkan tim (opsional)
            if ($teamId) {
                $sql .= " AND kd.team_id = ?";
                $params[] = $teamId;
            }

            $sql .= " ORDER BY COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai) DESC, kd.nama_kegiatan ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $kegiatanTable = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // TAMBAHAN: Ambil data reminder deadline (H-7) - JUGA dengan filter tim dan tahun
            $upcomingDeadlines = $this->getUpcomingDeadlinesFiltered($pdo, $teamId, $filterYear, 7);
            
            // TAMBAHAN: Ambil data mitra dengan beban tertinggi per wilayah
            $mitraBebanTertinggi = $this->getMitraBebanTertinggiData($pdo, $filterYear);
            $mitraBebanTertinggiPaniai = $mitraBebanTertinggi['paniai'] ?? null;
            $mitraBebanTertinggiIntanJaya = $mitraBebanTertinggi['intan_jaya'] ?? null;
        }

        // Include view dashboard
        include 'views/dashboard/index.php';
    }
    
    /**
     * Get mitra dengan beban tertinggi per wilayah
     */
    private function getMitraBebanTertinggiData($pdo, $filterYear = null)
    {
        $filterSql = "";
        $params = [];
        
        if (!empty($filterYear) && $filterYear !== 'all') {
            $filterSql = " AND (YEAR(kd.rentang_waktu_mulai) = ? OR YEAR(kd.rentang_waktu_selesai) = ?)";
            $params = [$filterYear, $filterYear];
        }

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
            HAVING jumlah_kegiatan > 0
            ORDER BY jumlah_kegiatan DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $allMitra = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        return [
            'paniai' => $mitraPaniai,
            'intan_jaya' => $mitraIntanJaya
        ];
    }

    /**
     * Get upcoming deadlines dengan filter tim dan tahun
     * PERBAIKAN: Realisasi dan Target diambil dari kegiatan_petugas (hanya PML)
     */
    private function getUpcomingDeadlinesFiltered($pdo, $teamId = null, $filterYear = null, $days = 7)
    {
        // Per-jenis row + cutoff:
        //  - hanya jenis selain Pelatihan/Briefing
        //  - tampilkan deadline H-{days} sampai max 30 hari terlambat
        //    (lebih dari 30 hari overdue dianggap sudah lewat & dihilangkan
        //     dari peringatan supaya tidak ramai)
        $sql = "
            SELECT
                kd.id,
                kd.nama_kegiatan,
                kj.jenis AS jenis_kegiatan,
                COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai) AS rentang_waktu_selesai,
                COALESCE(petugas_agg.total_realisasi, 0) AS realisasi,
                COALESCE(petugas_agg.total_target, 0) AS target,
                CASE
                    WHEN COALESCE(petugas_agg.total_target, 0) > 0
                    THEN ROUND((COALESCE(petugas_agg.total_realisasi, 0) / petugas_agg.total_target) * 100, 2)
                    ELSE 0
                END AS progress,
                COALESCE(kj.satuan, kd.satuan) AS satuan,
                t.name as team_name,
                DATEDIFF(COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai), CURDATE()) as days_remaining,
                CASE
                    WHEN DATEDIFF(COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai), CURDATE()) < 0 THEN 'overdue'
                    WHEN DATEDIFF(COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai), CURDATE()) <= 3 THEN 'critical'
                    WHEN DATEDIFF(COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai), CURDATE()) <= 7 THEN 'warning'
                    ELSE 'normal'
                END as urgency_level
            FROM kegiatan_detail kd
            LEFT JOIN kegiatan_jenis kj ON kj.kegiatan_id = kd.id
            LEFT JOIN teams t ON t.id = kd.team_id
            LEFT JOIN (
                SELECT
                    kegiatan_detail_id,
                    SUM(realisasi) AS total_realisasi,
                    SUM(target) AS total_target
                FROM kegiatan_petugas
                WHERE peran = 'PML'
                GROUP BY kegiatan_detail_id
            ) petugas_agg ON petugas_agg.kegiatan_detail_id = kd.id
            WHERE COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai) IS NOT NULL
            AND DATEDIFF(COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai), CURDATE()) <= ?
            AND DATEDIFF(COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai), CURDATE()) >= -30
            AND (kj.jenis IS NULL OR kj.jenis <> 'Pelatihan/Briefing')
        ";

        $params = [$days];

        // Filter berdasarkan tahun (per-jenis kalau ada)
        if (!empty($filterYear) && $filterYear !== 'all') {
            $sql .= " AND (
                YEAR(COALESCE(kj.tanggal_mulai,   kd.rentang_waktu_mulai))   = ?
                OR YEAR(COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai)) = ?
            )";
            $params[] = $filterYear;
            $params[] = $filterYear;
        }

        // Filter berdasarkan tim (opsional)
        if ($teamId) {
            $sql .= " AND kd.team_id = ?";
            $params[] = $teamId;
        }

        // Filter: hanya yang progress < 100
        $sql .= " HAVING progress < 100";

        $sql .= " ORDER BY COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai) ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonitoringDetail()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $kegiatanId = $_GET['kegiatan_id'] ?? null;
        if (!$kegiatanId) {
            echo json_encode(['success' => false, 'error' => 'Missing kegiatan_id']);
            exit;
        }

        try {
            // Query untuk mengambil data monitoring PML dan PPL
            require 'config/database.php';
            
            $sql = "
                SELECT 
                    kp.id,
                    kp.peran,
                    kp.realisasi,
                    kp.target,
                    kp.keterangan,
                    kp.pml_id,
                    CASE 
                        WHEN kp.target > 0 THEN ROUND((kp.realisasi / kp.target) * 100, 2)
                        ELSE 0 
                    END as progress,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name 
                        WHEN kp.petugas_source = 'mitra' THEN m.nama 
                    END as petugas_nama,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN 'PNS'
                        WHEN kp.petugas_source = 'mitra' THEN 'Mitra'
                    END as petugas_jenis
                FROM kegiatan_petugas kp
                LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
                WHERE kp.kegiatan_detail_id = ?
                ORDER BY kp.peran DESC, petugas_nama ASC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$kegiatanId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Bangun data matriks bulanan: daftar pegawai/mitra + seluruh
     * penugasan kegiatan yang overlap dengan bulan aktif.
     *
     * Return: [
     *   'bulan'    => 'YYYY-MM',
     *   'tanggal'  => [ ['tgl'=>27,'hari'=>'Sen','iso'=>'2026-04-27'], ... ],
     *   'pegawai'  => [ ['id'=>, 'name'=>, 'team_id'=>, 'team_warna'=>, 'bloks'=>[...]] ],
     *   'mitra_paniai' => [...],
     *   'mitra_intan_jaya' => [...],
     *   'teams'    => [ ['id'=>,'name'=>,'warna'=>], ... ],
     * ]
     *
     * Tiap "blok" penugasan berisi:
     *   id_kp, kegiatan_id, nama_kegiatan, team_name, warna,
     *   tgl_mulai, tgl_selesai, peran.
     */
    private function buildMatriksData($pdo, $bulan)
    {
        // Normalisasi bulan
        $ts = strtotime($bulan . '-01');
        if (!$ts) $ts = strtotime(date('Y-m-01'));
        $firstDay = date('Y-m-01', $ts);
        $lastDay  = date('Y-m-t',  $ts);
        $bulanStr = date('Y-m', $ts);

        // Daftar tanggal di bulan aktif
        $tanggal = [];
        $hariShort = ['Minggu'=>'Min','Senin'=>'Sen','Selasa'=>'Sel','Rabu'=>'Rab','Kamis'=>'Kam','Jumat'=>'Jum','Sabtu'=>'Sab'];
        $loop = strtotime($firstDay);
        $end  = strtotime($lastDay);
        while ($loop <= $end) {
            $hariIndo = [
                'Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
                'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'
            ][date('l', $loop)];
            $tanggal[] = [
                'tgl'  => (int)date('j', $loop),
                'hari' => $hariShort[$hariIndo],
                'iso'  => date('Y-m-d', $loop),
                'is_weekend' => in_array(date('N', $loop), [6,7]),
            ];
            $loop = strtotime('+1 day', $loop);
        }

        // Cek apakah kolom `warna` sudah ada di tabel teams (migrasi jalan?)
        $hasWarna = false;
        try {
            $chk = $pdo->query("SHOW COLUMNS FROM teams LIKE 'warna'")->fetch();
            $hasWarna = (bool)$chk;
        } catch (Exception $e) { $hasWarna = false; }

        $warnaSelect     = $hasWarna ? "COALESCE(warna,'#6c757d')" : "'#6c757d'";
        $warnaJoinSelect = $hasWarna ? "COALESCE(t.warna,'#6c757d')" : "'#6c757d'";

        // Ambil semua teams (untuk legenda + lookup warna)
        $teamsStmt = $pdo->query("SELECT id, name, {$warnaSelect} AS warna FROM teams ORDER BY name");
        $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Daftar pegawai (users) + mitra (mitra)
        // Filter status user hanya jika kolomnya ada (beberapa instalasi tidak punya).
        $userStatusFilter = '';
        try {
            if ($pdo->query("SHOW COLUMNS FROM users LIKE 'status'")->fetch()) {
                $userStatusFilter = "WHERE (u.status IS NULL OR u.status = 'aktif')";
            }
        } catch (Exception $e) { /* biarkan kosong */ }

        // Dashboard matriks: exclude Admin (role_id=1) dan urut berdasarkan jabatan.
        // Urutan: 1) Kepala  2) Kasubbag  3) PPK  4) Bendahara  5) sisanya (Operator).
        // Kalau tabel `roles` belum punya Kasubbag, urutan akan otomatis melompatinya.
        $roleFilter = "WHERE u.role_id <> 1";
        if ($userStatusFilter !== '') {
            // gabungkan: WHERE (status..) AND role_id <> 1
            $roleFilter = $userStatusFilter . " AND u.role_id <> 1";
        }

        $pegawaiStmt = $pdo->query("
            SELECT u.id, u.name, u.team_id, {$warnaJoinSelect} AS team_warna,
                   COALESCE(t.name,'-') AS team_name,
                   COALESCE(r.name,'') AS role_name
            FROM users u
            LEFT JOIN teams t ON t.id = u.team_id
            LEFT JOIN roles r ON r.id = u.role_id
            {$roleFilter}
            ORDER BY
              CASE LOWER(COALESCE(r.name,''))
                WHEN 'kepala'    THEN 1
                WHEN 'kasubbag'  THEN 2
                WHEN 'ppk'       THEN 3
                WHEN 'bendahara' THEN 4
                ELSE 5
              END,
              u.name
        ");
        $pegawai = $pegawaiStmt->fetchAll(PDO::FETCH_ASSOC);

        $mitraStmt = $pdo->query("
            SELECT id, nama AS name, wilayah_kerja
            FROM mitra
            WHERE status = 'aktif'
            ORDER BY nama
        ");
        $allMitra = $mitraStmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil semua penugasan yang overlap dengan bulan
        // periode_mulai/periode_selesai di kegiatan_petugas; fallback ke rentang kegiatan_detail
        $sql = "
            SELECT
                kp.id AS kp_id,
                kp.petugas_id,
                kp.petugas_source,
                kp.peran,
                COALESCE(kp.periode_mulai,   kd.rentang_waktu_mulai)   AS tgl_mulai,
                COALESCE(kp.periode_selesai, kd.rentang_waktu_selesai) AS tgl_selesai,
                kd.id AS kegiatan_id,
                kd.nama_kegiatan,
                kd.jenis_kegiatan,
                t.id AS team_id,
                t.name AS team_name,
                {$warnaJoinSelect} AS warna
            FROM kegiatan_petugas kp
            JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
            LEFT JOIN teams t ON t.id = kd.team_id
            WHERE
                COALESCE(kp.periode_mulai,   kd.rentang_waktu_mulai)   <= ?
                AND COALESCE(kp.periode_selesai, kd.rentang_waktu_selesai) >= ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lastDay, $firstDay]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Kelompokkan assignments per (source, id)
        $byUser  = [];
        $byMitra = [];
        foreach ($assignments as $a) {
            // Clamp ke bulan
            $start = max($a['tgl_mulai'], $firstDay);
            $endA  = min($a['tgl_selesai'], $lastDay);
            if ($start > $endA) continue;
            $blok = [
                'kp_id'         => (int)$a['kp_id'],
                'kegiatan_id'   => (int)$a['kegiatan_id'],
                'nama_kegiatan' => $a['nama_kegiatan'],
                'jenis'         => $a['jenis_kegiatan'],
                'team_name'     => $a['team_name'],
                'warna'         => $a['warna'],
                'peran'         => $a['peran'],
                'tgl_start'     => (int)date('j', strtotime($start)),
                'tgl_end'       => (int)date('j', strtotime($endA)),
            ];
            if ($a['petugas_source'] === 'users') {
                $byUser[$a['petugas_id']][] = $blok;
            } elseif ($a['petugas_source'] === 'mitra') {
                $byMitra[$a['petugas_id']][] = $blok;
            }
        }

        $attachBloks = function ($list, $map) {
            foreach ($list as &$row) {
                $row['bloks'] = $map[$row['id']] ?? [];
            }
            return $list;
        };

        // Split mitra per wilayah (case-insensitive)
        $mitraPaniai = [];
        $mitraIntanJaya = [];
        foreach ($allMitra as $m) {
            $w = strtolower(trim($m['wilayah_kerja'] ?? ''));
            if (strpos($w, 'paniai') !== false) {
                $mitraPaniai[] = $m;
            } elseif (strpos($w, 'intan') !== false || strpos($w, 'jaya') !== false) {
                $mitraIntanJaya[] = $m;
            }
        }

        return [
            'bulan'            => $bulanStr,
            'tanggal'          => $tanggal,
            'pegawai'          => $attachBloks($pegawai, $byUser),
            'mitra_paniai'     => $attachBloks($mitraPaniai, $byMitra),
            'mitra_intan_jaya' => $attachBloks($mitraIntanJaya, $byMitra),
            'teams'            => $teams,
        ];
    }

    /**
     * Endpoint AJAX: detail kegiatan untuk 1 blok di matriks.
     */
    public function matriksInfo()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success'=>false,'error'=>'Unauthorized']);
            exit;
        }
        $kpId = $_GET['kp_id'] ?? 0;
        if (!$kpId) {
            echo json_encode(['success'=>false,'error'=>'Missing kp_id']);
            exit;
        }
        try {
            require 'config/database.php';
            // Cek kolom warna (migrasi bisa belum jalan)
            $hasWarna = false;
            try {
                $chk = $pdo->query("SHOW COLUMNS FROM teams LIKE 'warna'")->fetch();
                $hasWarna = (bool)$chk;
            } catch (Exception $e) { $hasWarna = false; }
            $warnaExpr = $hasWarna ? "COALESCE(t.warna,'#6c757d')" : "'#6c757d'";

            $sql = "
                SELECT
                    kp.id, kp.peran, kp.realisasi, kp.target,
                    COALESCE(kp.periode_mulai,   kd.rentang_waktu_mulai)   AS tgl_mulai,
                    COALESCE(kp.periode_selesai, kd.rentang_waktu_selesai) AS tgl_selesai,
                    kd.nama_kegiatan, kd.jenis_kegiatan,
                    t.name AS team_name, {$warnaExpr} AS warna,
                    CASE WHEN kp.petugas_source='users' THEN u.name ELSE m.nama END AS petugas_nama,
                    CASE WHEN kp.petugas_source='users' THEN 'Pegawai' ELSE 'Mitra' END AS petugas_jenis
                FROM kegiatan_petugas kp
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                LEFT JOIN teams t ON t.id = kd.team_id
                LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source='users'
                LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source='mitra'
                WHERE kp.id = ?
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$kpId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$data) {
                echo json_encode(['success'=>false,'error'=>'Not found']);
                exit;
            }
            echo json_encode(['success'=>true, 'data'=>$data]);
        } catch (Exception $e) {
            echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
        }
        exit;
    }

    public function updateKomentar()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        // Cek role - hanya kepala yang bisa edit komentar
        if ($_SESSION['user']['role_name'] !== 'kepala') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        $kegiatanPetugasId = $_POST['kegiatan_petugas_id'] ?? null;
        $komentar = $_POST['komentar'] ?? '';

        if (!$kegiatanPetugasId) {
            echo json_encode(['success' => false, 'error' => 'Missing kegiatan_petugas_id']);
            exit;
        }

        try {
            require 'config/database.php';
            
            $sql = "UPDATE kegiatan_petugas SET keterangan = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$komentar, $kegiatanPetugasId]);
            
            echo json_encode(['success' => true, 'message' => 'Komentar berhasil diperbarui']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}