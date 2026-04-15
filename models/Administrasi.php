<?php
/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * Provided as reference for internal learning purposes.
 */

class Administrasi
{
    private $db;

    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }
    
    // ✅ EXISTING METHOD - TIDAK BERUBAH
   /*
 * CATATAN: Tambahkan method ini ke file Administrasi.php
 * Replace method getAll() yang lama dengan yang ini
 */

    public function getAll($team_id = null, $bulan = null, $jenis_kegiatan = null)
    {
        $sql = "
        SELECT 
            a.id,
            a.kegiatan_petugas_id,
            a.jenis as jenis_administrasi,
            a.sppd_uploaded,
            a.pengeluaran_uploaded,
            a.bast_uploaded,
            a.perjanjian_uploaded,
            a.is_complete,
            a.sent_to_verification,
            a.created_at,
            a.updated_at,
            kp.kegiatan_detail_id,
            kp.petugas_id,
            kp.petugas_source,
            k.nama_kegiatan,
            k.jenis_kegiatan,
            t.name AS team_name,
            CASE 
                WHEN kp.petugas_source = 'users' THEN u.name
                WHEN kp.petugas_source = 'mitra' THEN m.name
                ELSE 'Unknown'
            END as petugas_name
        FROM administrasi a
        JOIN kegiatan_petugas kp ON a.kegiatan_petugas_id = kp.id
        JOIN kegiatan_petugas kp ON a.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
        LEFT JOIN teams t ON t.id = k.team_id
        LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
        LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
        WHERE 1=1
        ";

        $params = [];

        // Filter tim
        if (!empty($team_id)) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $team_id;
        }

        // Filter bulan (format: YYYY-MM)
        if (!empty($bulan)) {
            $sql .= " AND DATE_FORMAT(a.created_at, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan;
        }

        // Filter jenis kegiatan
        if (!empty($jenis_kegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenis_kegiatan;
        }

        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Untuk operator - lihat administrasi dari kegiatan yang dia ikuti
     */
    public function getAllForOperator($userId, $userTeamId, $bulan = null, $jenis_kegiatan = null)
    {
        $sql = "
        SELECT DISTINCT
            a.id,
            kp.kegiatan_detail_id,
            k.nama_kegiatan,
            k.jenis_kegiatan,
            NULL as status_ppk,
            NULL as status_bendahara,
            NULL as catatan_ppk,
            NULL as created_by,
            a.created_at,
            NULL as created_by_name,
            CASE 
                WHEN k.team_id = :user_team_id THEN 'Kegiatan Tim Sendiri'
                ELSE CONCAT('Ditugaskan ke kegiatan Tim ', t.name)
            END AS assignment_info,
            t.name as team_name
        FROM administrasi a
        JOIN kegiatan_petugas kp ON a.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
        -- User info removed (column created_by doesnt exist)
        LEFT JOIN teams t ON t.id = k.team_id
        WHERE (
            -- Administrasi dari kegiatan tim sendiri
            k.team_id = :user_team_id
            
            OR
            
            -- Administrasi dari kegiatan yang user ikuti sebagai petugas
            EXISTS (
                SELECT 1 FROM kegiatan_petugas kp 
                WHERE kp.kegiatan_detail_id = k.id 
                AND kp.petugas_id = :user_id 
                AND kp.petugas_source = 'users'
            )
        )
        ";

        $params = [
            'user_id' => $userId,
            'user_team_id' => $userTeamId
        ];

        // Filter bulan
        if (!empty($bulan)) {
            $sql .= " AND DATE_FORMAT(a.created_at, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan;
        }

        // Filter jenis kegiatan
        if (!empty($jenis_kegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenis_kegiatan;
        }

        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Filter administrasi berdasarkan role
     * - Admin/PPK/Bendahara: bisa lihat semua
     * - Operator/Kepala: hanya lihat dari tim sendiri
     */
    public function getAllWithAccessControl($userId, $userTeamId, $role, $team_id = null, $bulan = null, $jenis_kegiatan = null)
    {
        // Admin, PPK, Bendahara bisa lihat semua
        if (in_array($role, ['admin', 'ppk', 'bendahara'])) {
            return $this->getAll($team_id, $bulan, $jenis_kegiatan);
        }
        
        // Operator/Kepala hanya lihat dari tim sendiri
        return $this->getAll($userTeamId, $bulan, $jenis_kegiatan);
    }

/**
 * ========================================
 * UPDATE: models/Administrasi.php
 * DISESUAIKAN DENGAN STRUKTUR DATABASE AKTUAL
 * ========================================
 */

/**
 * REPLACE METHOD: sendToVerificationKepala() (line ~646)
 * GANTI dengan method ini:
 */
public function sendToVerificationOperatorTim($kegiatanPetugasId)
{
    // Get kegiatan_detail_id dari kegiatan_petugas
    $stmt = $this->db->prepare("
        SELECT kegiatan_detail_id 
        FROM kegiatan_petugas 
        WHERE id = ?
    ");
    $stmt->execute([$kegiatanPetugasId]);
    $kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kegiatan) {
        return false;
    }
    
    // Cek apakah sudah ada di verifikasi
    $stmt = $this->db->prepare("
        SELECT id FROM verifikasi_administrasi 
        WHERE kegiatan_petugas_id = ?
    ");
    $stmt->execute([$kegiatanPetugasId]);
    
    if ($stmt->fetch()) {
        return false; // Sudah ada
    }
    
    // Insert dengan status awal: pending_operator_tim
    // CATATAN: Pakai administrasi_submitted_at yang sudah ada di tabel
    $sql = "INSERT INTO verifikasi_administrasi 
            (kegiatan_petugas_id, kegiatan_detail_id, status, administrasi_submitted_at, created_at) 
            VALUES (?, ?, 'pending_operator_tim', NOW(), NOW())";
    
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$kegiatanPetugasId, $kegiatan['kegiatan_detail_id']]);
}

/**
 * TAMBAH METHOD BARU: Get data untuk Operator Tim
 * CATATAN: Pakai administrasi_submitted_at bukan submitted_at
 */
public function getAllForOperatorTim($teamId = null, $bulan = null, $jenis_kegiatan = null)
{
    $sql = "
    SELECT 
        k.id as kegiatan_detail_id,
        k.nama_kegiatan,
        k.jenis_kegiatan,
        k.team_id,
        t.name as team_name,
        COUNT(va.id) as total_petugas,
        SUM(CASE WHEN va.status = 'pending_operator_tim' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN va.status = 'approved_operator_tim' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN va.status = 'rejected_operator_tim' THEN 1 ELSE 0 END) as rejected_count,
        -- Counter untuk status rejected dari level lain (dikembalikan ke operator)
        SUM(CASE WHEN va.status = 'rejected_kepala' THEN 1 ELSE 0 END) as rejected_kepala_count,
        SUM(CASE WHEN va.status = 'rejected_ppk' THEN 1 ELSE 0 END) as rejected_ppk_count,
        SUM(CASE WHEN va.status = 'rejected_bendahara' THEN 1 ELSE 0 END) as rejected_bendahara_count,
        MIN(va.administrasi_submitted_at) as first_submit,
        MAX(va.administrasi_submitted_at) as last_submit,
        CASE 
            -- Prioritas: cek rejected dari level lebih tinggi dulu
            WHEN SUM(CASE WHEN va.status = 'rejected_bendahara' THEN 1 ELSE 0 END) > 0 
            THEN 'rejected_bendahara'
            WHEN SUM(CASE WHEN va.status = 'rejected_ppk' THEN 1 ELSE 0 END) > 0 
            THEN 'rejected_ppk'
            WHEN SUM(CASE WHEN va.status = 'rejected_kepala' THEN 1 ELSE 0 END) > 0 
            THEN 'rejected_kepala'
            WHEN SUM(CASE WHEN va.status = 'pending_operator_tim' THEN 1 ELSE 0 END) > 0 
            THEN 'pending'
            WHEN SUM(CASE WHEN va.status = 'approved_operator_tim' THEN 1 ELSE 0 END) = COUNT(va.id) 
            THEN 'all_approved'
            WHEN SUM(CASE WHEN va.status = 'rejected_operator_tim' THEN 1 ELSE 0 END) > 0 
            THEN 'has_rejected'
            ELSE 'mixed'
        END as kegiatan_status,
        -- Info dokumen kegiatan (KAK, SK KPA, Daftar Nominatif)
        (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'kak') > 0 as has_kak,
        (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'sk_kpa') > 0 as has_sk_kpa,
        (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'daftar_nominatif') > 0 as has_daftar_nominatif,
        (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'form_permintaan') > 0 as has_form_permintaan
    FROM verifikasi_administrasi va
    JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
    JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
    LEFT JOIN teams t ON t.id = k.team_id
    WHERE va.status IN ('pending_operator_tim', 'approved_operator_tim', 'rejected_operator_tim', 'rejected_kepala', 'rejected_ppk', 'rejected_bendahara')
    ";
    
    $params = [];
    
    // Filter tim (null = semua tim, untuk admin)
    if ($teamId !== null) {
        $sql .= " AND k.team_id = :team_id";
        $params['team_id'] = $teamId;
    }
    
    if (!empty($bulan)) {
        $sql .= " AND DATE_FORMAT(va.administrasi_submitted_at, '%Y-%m') = :bulan";
        $params['bulan'] = $bulan;
    }
    
    if (!empty($jenis_kegiatan)) {
        $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
        $params['jenis_kegiatan'] = $jenis_kegiatan;
    }
    
    $sql .= " GROUP BY k.id, k.nama_kegiatan, k.jenis_kegiatan, k.team_id, t.name
              ORDER BY MIN(va.administrasi_submitted_at) DESC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * TAMBAH METHOD BARU: Get detail petugas per kegiatan
 */
public function getDetailPetugasForOperatorTim($kegiatanDetailId)
{
    $sql = "
    SELECT 
        va.id as verifikasi_id,
        va.kegiatan_petugas_id,
        va.status,
        va.administrasi_submitted_at as submitted_at,
        va.catatan_operator_tim,
        va.catatan_kepala,
        va.catatan_ppk,
        va.catatan_bendahara,
        kp.peran,
        kp.realisasi,
        kp.target,
        CASE 
            WHEN kp.petugas_source = 'users' THEN u.name
            WHEN kp.petugas_source = 'mitra' THEN m.nama
        END AS petugas_nama,
        CASE 
            WHEN kp.petugas_source = 'users' THEN 'PNS'
            WHEN kp.petugas_source = 'mitra' THEN 'Mitra'
        END AS petugas_jenis,
        (SELECT COUNT(*) FROM administrasi_files af 
         WHERE af.kegiatan_petugas_id = kp.id 
         AND af.jenis_administrasi = 'perjalanan_dinas'
        ) as file_perjalanan_count,
        (SELECT COUNT(*) FROM administrasi_files af 
         WHERE af.kegiatan_petugas_id = kp.id 
         AND af.jenis_administrasi = 'honorarium'
        ) as file_honorarium_count
    FROM verifikasi_administrasi va
    JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
    LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
    LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
    WHERE kp.kegiatan_detail_id = ?
    AND va.status IN ('pending_operator_tim', 'approved_operator_tim', 'rejected_operator_tim', 'rejected_kepala', 'rejected_ppk', 'rejected_bendahara')
    ORDER BY 
        CASE WHEN kp.peran = 'PML' THEN 1 ELSE 2 END,
        kp.peran,
        petugas_nama
    ";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$kegiatanDetailId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * TAMBAH METHOD BARU: Approve verifikasi operator tim
 * UPDATED: Admin bisa approve status apapun
 */
public function approveVerifikasiOperatorTim($verifikasiId, $userId, $isAdmin = false)
{
    if ($isAdmin) {
        // Admin bisa approve status apapun (pending, rejected operator, atau rejected dari level atas)
        $sql = "UPDATE verifikasi_administrasi 
                SET status = 'approved_operator_tim',
                    verified_operator_tim_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
                AND status IN ('pending_operator_tim', 'rejected_operator_tim', 'rejected_kepala', 'rejected_ppk', 'rejected_bendahara')";
    } else {
        // Operator bisa approve yang pending atau yang dikembalikan dari level atas
        $sql = "UPDATE verifikasi_administrasi 
                SET status = 'approved_operator_tim',
                    verified_operator_tim_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
                AND status IN ('pending_operator_tim', 'rejected_kepala', 'rejected_ppk', 'rejected_bendahara')";
    }
    
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$verifikasiId]);
}

/**
 * TAMBAH METHOD BARU: Reject verifikasi operator tim
 * UPDATED: Admin bisa reject status apapun
 * UPDATED: Operator bisa reject status yang dikembalikan dari level atas
 */
public function rejectVerifikasiOperatorTim($verifikasiId, $catatan, $userId, $isAdmin = false)
{
    if ($isAdmin) {
        // Admin bisa reject status apapun
        $sql = "UPDATE verifikasi_administrasi 
                SET status = 'rejected_operator_tim',
                    catatan_operator_tim = ?,
                    verified_operator_tim_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
                AND status IN ('pending_operator_tim', 'approved_operator_tim', 'rejected_kepala', 'rejected_ppk', 'rejected_bendahara')";
    } else {
        // Operator bisa reject yang pending atau yang dikembalikan dari level atas
        $sql = "UPDATE verifikasi_administrasi 
                SET status = 'rejected_operator_tim',
                    catatan_operator_tim = ?,
                    verified_operator_tim_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
                AND status IN ('pending_operator_tim', 'rejected_kepala', 'rejected_ppk', 'rejected_bendahara')";
    }
    
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$catatan, $verifikasiId]);
}

/**
 * TAMBAH METHOD BARU: Kirim kegiatan ke Kepala
 */
public function sendKegiatanToKepala($kegiatanDetailId)
{
    // Cek apakah semua file kegiatan sudah approved operator tim
    $stmt = $this->db->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'approved_operator_tim' THEN 1 ELSE 0 END) as approved
        FROM verifikasi_administrasi va
        JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
        WHERE kp.kegiatan_detail_id = ?
        AND va.status IN ('pending_operator_tim', 'approved_operator_tim', 'rejected_operator_tim')
    ");
    $stmt->execute([$kegiatanDetailId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] != $result['approved'] || $result['total'] == 0) {
        return false; // Belum semua approved
    }
    
    // Update semua file kegiatan ke status pending_kepala
    $sql = "UPDATE verifikasi_administrasi va
            JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
            SET va.status = 'pending_kepala',
                va.updated_at = NOW()
            WHERE kp.kegiatan_detail_id = ?
            AND va.status = 'approved_operator_tim'";
    
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$kegiatanDetailId]);
}

/**
 * UPDATE METHOD: getAllForKepala() 
 * CATATAN: Pakai administrasi_submitted_at
 */
public function getAllForKepala($team_id, $userTeamId, $bulan = null, $jenis_kegiatan = null)
{
    $sql = "
    SELECT 
        k.id as kegiatan_detail_id,
        k.nama_kegiatan,
        k.jenis_kegiatan,
        k.team_id,
        t.name as team_name,
        COUNT(va.id) as total_petugas,
        MIN(va.verified_operator_tim_at) as verified_operator_at,
        MIN(va.administrasi_submitted_at) as first_submit,
        SUM(CASE WHEN va.status = 'pending_kepala' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN va.status = 'approved_kepala' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN va.status = 'rejected_kepala' THEN 1 ELSE 0 END) as rejected_count,
        CASE 
            WHEN SUM(CASE WHEN va.status = 'pending_kepala' THEN 1 ELSE 0 END) = COUNT(va.id) 
            THEN 'pending'
            WHEN SUM(CASE WHEN va.status = 'approved_kepala' THEN 1 ELSE 0 END) = COUNT(va.id) 
            THEN 'approved'
            WHEN SUM(CASE WHEN va.status = 'rejected_kepala' THEN 1 ELSE 0 END) > 0 
            THEN 'rejected'
            ELSE 'mixed'
        END as kegiatan_status
    FROM verifikasi_administrasi va
    JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
    JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
    LEFT JOIN teams t ON t.id = k.team_id
    WHERE va.status IN ('pending_kepala', 'approved_kepala', 'rejected_kepala')
    ";
    
    $params = [];
    
    // Filter tim
    if ($team_id) {
        $sql .= " AND k.team_id = :team_id";
        $params['team_id'] = $team_id;
    }
    
    // Filter bulan
    if (!empty($bulan)) {
        $sql .= " AND DATE_FORMAT(va.administrasi_submitted_at, '%Y-%m') = :bulan";
        $params['bulan'] = $bulan;
    }
    
    // Filter jenis kegiatan
    if (!empty($jenis_kegiatan)) {
        $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
        $params['jenis_kegiatan'] = $jenis_kegiatan;
    }
    
    $sql .= " GROUP BY k.id, k.nama_kegiatan, k.jenis_kegiatan, k.team_id, t.name
              ORDER BY MIN(va.verified_operator_tim_at) DESC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * ✅ Method untuk mitra
     */
    public function getByMitra($mitraId, $bulan = null, $jenis_kegiatan = null, $team_id = null)
    {
        $sql = "
        SELECT DISTINCT
            a.id,
            kp.kegiatan_detail_id,
            k.nama_kegiatan,
            k.jenis_kegiatan,
            NULL as status_ppk,
            NULL as status_bendahara,
            NULL as catatan_ppk,
            a.created_at,
            t.name AS team_name
        FROM administrasi a
        JOIN kegiatan_petugas kp ON a.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
        JOIN kegiatan_petugas kp ON kp.kegiatan_detail_id = k.id
        LEFT JOIN teams t ON t.id = k.team_id
        WHERE kp.petugas_id = :mitra_id
        AND kp.petugas_source = 'mitra'
        ";
        
        $params = ['mitra_id' => $mitraId];
        
        // Filter bulan
        if (!empty($bulan)) {
            $sql .= " AND DATE_FORMAT(a.created_at, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan;
        }
        
        // Filter jenis kegiatan
        if (!empty($jenis_kegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenis_kegiatan;
        }
        
        // Filter tim
        if (!empty($team_id)) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $team_id;
        }
        
        $sql .= " ORDER BY a.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ Get files by administrasi_id
     */
    public function getFilesByAdministrasiId($admId)
    {
        $stmt = $this->db->prepare("SELECT * FROM administrasi_files WHERE administrasi_id = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$admId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Get files by kegiatan_petugas_id
     * FIXED: Menambahkan prefix 'public/' ke file_path agar bisa diakses dari browser
     */
    public function getFilesByKegiatanPetugasId($kegiatanPetugasId, $jenisAdministrasi = null)
    {
        $sql = "SELECT id, kegiatan_petugas_id, jenis_administrasi, jenis_file, sub_jenis, 
                       nama_file, CONCAT('public/', file_path) as file_path, keterangan, 
                       file_type, file_size, uploaded_by, created_at 
                FROM administrasi_files WHERE kegiatan_petugas_id = ?";
        $params = [$kegiatanPetugasId];
        
        if ($jenisAdministrasi) {
            $sql .= " AND jenis_administrasi = ?";
            $params[] = $jenisAdministrasi;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Get file by ID
     */
    public function getFileById($fileId)
    {
        $stmt = $this->db->prepare("
            SELECT af.*, kp.petugas_id, kp.petugas_source, kp.peran,
                   kd.nama_kegiatan, kd.team_id,
                   u.name as uploaded_by_name
            FROM administrasi_files af
            LEFT JOIN kegiatan_petugas kp ON kp.id = af.kegiatan_petugas_id
            LEFT JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
            LEFT JOIN users u ON u.id = af.uploaded_by
            WHERE af.id = ?
        ");
        $stmt->execute([$fileId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Get grouped files by kegiatan_petugas_id
     * FIXED: Menambahkan support untuk 'spd' dan 'laporan_perjalanan'
     */
    public function getGroupedFilesByKegiatanPetugas($kegiatanPetugasId)
    {
        $files = $this->getFilesByKegiatanPetugasId($kegiatanPetugasId);
        
        $grouped = [
            'perjalanan_dinas' => [
                'sppd' => null,              // Legacy support
                'spd' => null,               // New field (Surat Tugas & SPD)
                'laporan_perjalanan' => null, // New field (Laporan Perjalanan)
                'pengeluaran' => null,
                'fotos' => []
            ],
            'honorarium' => [
                'bast' => null,
                'perjanjian' => null
            ],
            'other' => []
        ];
        
        foreach ($files as $file) {
            if ($file['jenis_administrasi'] == 'perjalanan_dinas') {
                if ($file['sub_jenis'] == 'sppd') {
                    $grouped['perjalanan_dinas']['sppd'] = $file;
                } elseif ($file['sub_jenis'] == 'spd') {
                    $grouped['perjalanan_dinas']['spd'] = $file;
                } elseif ($file['sub_jenis'] == 'laporan_perjalanan') {
                    $grouped['perjalanan_dinas']['laporan_perjalanan'] = $file;
                } elseif ($file['sub_jenis'] == 'pengeluaran') {
                    $grouped['perjalanan_dinas']['pengeluaran'] = $file;
                } elseif ($file['sub_jenis'] == 'foto') {
                    $grouped['perjalanan_dinas']['fotos'][] = $file;
                }
            } elseif ($file['jenis_administrasi'] == 'honorarium') {
                if ($file['sub_jenis'] == 'bast') {
                    $grouped['honorarium']['bast'] = $file;
                } elseif ($file['sub_jenis'] == 'perjanjian') {
                    $grouped['honorarium']['perjanjian'] = $file;
                }
            } else {
                $grouped['other'][] = $file;
            }
        }
        
        return $grouped;
    }

    /**
     * ✅ Get administrasi by team
     */
    public function getByTeam($teamId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id,
                kp.kegiatan_detail_id,
                k.nama_kegiatan,
                k.jenis_kegiatan,
                NULL as status_ppk,
                NULL as status_bendahara,
                NULL as catatan_ppk,
                NULL as created_by,
                a.created_at,
                NULL as created_by_name
            FROM administrasi a
            JOIN kegiatan_petugas kp ON a.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
            -- User info removed (column created_by doesnt exist)
            WHERE k.team_id = :team_id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute(['team_id' => $teamId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ Get administrasi by id
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.*,
                k.nama_kegiatan,
                k.jenis_kegiatan,
                k.team_id,
                NULL as created_by_name,
                t.name AS team_name
            FROM administrasi a
            JOIN kegiatan_petugas kp ON a.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
            -- User info removed (column created_by doesnt exist)
            LEFT JOIN teams t ON t.id = k.team_id
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Get kegiatan_petugas by id
     */
    public function getKegiatanPetugasById($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                kp.*,
                kd.nama_kegiatan,
                kd.team_id,
                CASE 
                    WHEN kp.petugas_source = 'users' THEN u.name
                    WHEN kp.petugas_source = 'mitra' THEN m.nama
                END AS petugas_nama
            FROM kegiatan_petugas kp
            JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
            LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
            LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
            WHERE kp.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ Insert administrasi
     */
    public function insert($data)
    {
        $sql = "
        INSERT INTO administrasi 
        (kegiatan_detail_id, status_ppk, status_bendahara, catatan_ppk, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['kegiatan_detail_id'],
            $data['status_ppk'] ?? 'pending',
            $data['status_bendahara'] ?? 'pending',
            $data['catatan_ppk'] ?? null,
            $data['created_by'] ?? $_SESSION['user']['id']
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * ✅ Insert file (simple version)
     */
    public function insertFile($data)
    {
        $sql = "
        INSERT INTO administrasi_files 
        (administrasi_id, nama_file, file_path, uploaded_at)
        VALUES (?, ?, ?, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['administrasi_id'],
            $data['nama_file'],
            $data['file_path']
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * ✅ NEW METHOD: Insert file complete (with all columns)
     */
    public function insertFileComplete($data)
    {
        $sql = "
        INSERT INTO administrasi_files 
        (kegiatan_petugas_id, jenis_administrasi, jenis_file, sub_jenis, 
         nama_file, file_path, keterangan,
         file_type, file_size, uploaded_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['kegiatan_petugas_id'] ?? null,
            $data['jenis_administrasi'] ?? null,
            $data['jenis_file'] ?? null,
            $data['sub_jenis'] ?? null,
            $data['nama_file'],
            $data['file_path'],
            $data['keterangan'] ?? null,
            $data['file_type'] ?? null,
            $data['file_size'] ?? null,
            $data['uploaded_by'] ?? $_SESSION['user']['id']
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * ✅ NEW METHOD: Delete files by kegiatan_petugas_id and jenis
     */
    public function deleteFilesByKegiatanPetugas($kegiatanPetugasId, $jenisAdministrasi = null, $subJenis = null)
    {
        $sql = "SELECT file_path FROM administrasi_files WHERE kegiatan_petugas_id = ?";
        $params = [$kegiatanPetugasId];
        
        if ($jenisAdministrasi) {
            $sql .= " AND jenis_administrasi = ?";
            $params[] = $jenisAdministrasi;
        }
        
        if ($subJenis) {
            $sql .= " AND sub_jenis = ?";
            $params[] = $subJenis;
        }
        
        // Get files first
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete physical files
        foreach ($files as $file) {
            if (!empty($file['file_path'])) {
                $fullPath = __DIR__ . '/../public/' . $file['file_path'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        }
        
        // Delete database records
        $sql = "DELETE FROM administrasi_files WHERE kegiatan_petugas_id = ?";
        $params = [$kegiatanPetugasId];
        
        if ($jenisAdministrasi) {
            $sql .= " AND jenis_administrasi = ?";
            $params[] = $jenisAdministrasi;
        }
        
        if ($subJenis) {
            $sql .= " AND sub_jenis = ?";
            $params[] = $subJenis;
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * ✅ Update status PPK
     */
    public function updateStatusPPK($id, $status, $catatan)
    {
        $sql = "UPDATE administrasi 
                SET status_ppk = :status, 
                    catatan_ppk = :catatan,
                    approved_ppk_at = CASE WHEN :status = 'approved' THEN NOW() ELSE approved_ppk_at END
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'catatan' => $catatan,
            'id' => $id
        ]);
    }

    /**
     * ✅ Update status Bendahara
     */
    public function updateStatusBendahara($id, $status, $catatan)
    {
        $sql = "UPDATE administrasi 
                SET status_bendahara = :status, 
                    catatan_bendahara = :catatan,
                    approved_bendahara_at = CASE WHEN :status = 'approved' THEN NOW() ELSE approved_bendahara_at END
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'catatan' => $catatan,
            'id' => $id
        ]);
    }

    /**
     * ✅ NEW METHOD: Send to verification kepala
     */
    public function sendToVerificationKepala($kegiatanPetugasId)
    {
        // Cek apakah sudah ada di verifikasi
        $stmt = $this->db->prepare("SELECT id FROM verifikasi_administrasi WHERE kegiatan_petugas_id = ?");
        $stmt->execute([$kegiatanPetugasId]);
        
        if ($stmt->fetch()) {
            return false; // Sudah ada
        }
        
        // Insert ke verifikasi dengan status awal
        $sql = "INSERT INTO verifikasi_administrasi 
                (kegiatan_petugas_id, status, created_at) 
                VALUES (?, 'pending_kepala', NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$kegiatanPetugasId]);
    }

    /**
     * ✅ NEW METHOD: Get verification status
     */
    public function getVerificationStatus($kegiatanPetugasId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM verifikasi_administrasi 
            WHERE kegiatan_petugas_id = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$kegiatanPetugasId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Update verification status by Kepala
     */
    public function updateVerificationKepala($kegiatanPetugasId, $status, $catatan)
    {
        $sql = "UPDATE verifikasi_administrasi 
                SET status = :status, 
                    catatan_kepala = :catatan,
                    verified_kepala_at = NOW(),
                    updated_at = NOW()
                WHERE kegiatan_petugas_id = :kegiatan_petugas_id
                AND status = 'pending_kepala'";
        
        $newStatus = $status == 'approved' ? 'approved_kepala' : 'rejected_kepala';
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $newStatus,
            'catatan' => $catatan,
            'kegiatan_petugas_id' => $kegiatanPetugasId
        ]);
    }

    /**
     * ✅ NEW METHOD: Update verification status by PPK
     */
    public function updateVerificationPPK($kegiatanPetugasId, $status, $catatan)
    {
        $sql = "UPDATE verifikasi_administrasi 
                SET status = :status, 
                    catatan_ppk = :catatan,
                    verified_ppk_at = NOW(),
                    updated_at = NOW()
                WHERE kegiatan_petugas_id = :kegiatan_petugas_id
                AND status = 'approved_kepala'";
        
        $newStatus = $status == 'approved' ? 'approved_ppk' : 'rejected_ppk';
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $newStatus,
            'catatan' => $catatan,
            'kegiatan_petugas_id' => $kegiatanPetugasId
        ]);
    }

    /**
     * ✅ NEW METHOD: Update verification status by Bendahara
     */
    public function updateVerificationBendahara($kegiatanPetugasId, $status, $catatan)
    {
        $sql = "UPDATE verifikasi_administrasi 
                SET status = :status, 
                    catatan_bendahara = :catatan,
                    verified_bendahara_at = NOW(),
                    updated_at = NOW()
                WHERE kegiatan_petugas_id = :kegiatan_petugas_id
                AND status = 'approved_ppk'";
        
        $newStatus = $status == 'approved' ? 'completed' : 'rejected_bendahara';
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $newStatus,
            'catatan' => $catatan,
            'kegiatan_petugas_id' => $kegiatanPetugasId
        ]);
    }

    /**
     * ✅ Delete administrasi
     */
    public function delete($id)
    {
        // Hapus files terkait dulu
        $stmt = $this->db->prepare("SELECT file_path FROM administrasi_files WHERE administrasi_id = ?");
        $stmt->execute([$id]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hapus file fisik
        foreach ($files as $file) {
            $fullPath = __DIR__ . '/../public/' . $file['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // Hapus file dari database
        $stmt = $this->db->prepare("DELETE FROM administrasi_files WHERE administrasi_id = ?");
        $stmt->execute([$id]);
        
        // Hapus administrasi
        $stmt = $this->db->prepare("DELETE FROM administrasi WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * ✅ Delete file by id
     */
    public function deleteFile($fileId)
    {
        // Get file info
        $stmt = $this->db->prepare("SELECT file_path FROM administrasi_files WHERE id = ?");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete physical file
        if ($file && isset($file['file_path'])) {
            $fullPath = __DIR__ . '/../public/' . $file['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // Delete database record
        $stmt = $this->db->prepare("DELETE FROM administrasi_files WHERE id = ?");
        return $stmt->execute([$fileId]);
    }

    /**
     * ✅ Get assignments with administrasi
     * FIXED: Menambahkan verification_status dan catatan_operator_tim untuk sinkronisasi status
     */
    public function getAssignmentsWithAdministrasi($user, $kegiatan_id = null)
    {
        $team_id = $user['team_id'] ?? null;
        
        $sql = "SELECT 
                    kp.id,
                    kp.kegiatan_detail_id,
                    kp.petugas_id,
                    kp.petugas_source,
                    kp.peran,
                    kp.realisasi,
                    kp.target,
                    kp.pml_id,
                    kd.nama_kegiatan,
                    kd.team_id,
                    t.name as team_name,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                    END AS petugas_nama,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN 'PNS'
                        WHEN kp.petugas_source = 'mitra' THEN 'Mitra'
                    END AS petugas_jenis,
                    CASE 
                        WHEN kp.peran = 'PPL' AND kp.pml_id IS NOT NULL THEN
                            CASE 
                                WHEN pml_user.id IS NOT NULL THEN pml_user.name
                                WHEN pml_mitra.id IS NOT NULL THEN pml_mitra.nama
                            END
                        ELSE NULL
                    END AS pml_name,
                    (SELECT COUNT(*) FROM administrasi_files af 
                     WHERE af.kegiatan_petugas_id = kp.id 
                     AND af.jenis_administrasi = 'perjalanan_dinas'
                     AND af.sub_jenis IN ('sppd', 'spd', 'laporan_perjalanan', 'pengeluaran')
                    ) >= 3 AS perjalanan_complete,
                    (SELECT COUNT(*) FROM administrasi_files af 
                     WHERE af.kegiatan_petugas_id = kp.id 
                     AND af.jenis_administrasi = 'honorarium'
                     AND af.sub_jenis IN ('bast', 'perjanjian')
                    ) >= 2 AS honorarium_complete,
                    (SELECT COUNT(*) FROM verifikasi_administrasi va2 
                     WHERE va2.kegiatan_petugas_id = kp.id
                    ) > 0 AS sent_to_verification,
                    va.status AS verification_status,
                    va.catatan_operator_tim
                FROM kegiatan_petugas kp
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                LEFT JOIN teams t ON t.id = kd.team_id
                LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
                LEFT JOIN kegiatan_petugas pml_kp ON pml_kp.id = kp.pml_id
                LEFT JOIN users pml_user ON pml_user.id = pml_kp.petugas_id AND pml_kp.petugas_source = 'users'
                LEFT JOIN mitra pml_mitra ON pml_mitra.id = pml_kp.petugas_id AND pml_kp.petugas_source = 'mitra'
                LEFT JOIN verifikasi_administrasi va ON va.kegiatan_petugas_id = kp.id
                WHERE 1=1";
        
        
        $params = [];
        
        // Filter berdasarkan team_id jika ada
        if ($team_id) {
            $sql .= " AND kd.team_id = :team_id";
            $params['team_id'] = $team_id;
        }
        // Jika tidak ada team_id, tampilkan semua (untuk admin atau debugging)
        
        if ($kegiatan_id) {
            $sql .= " AND kp.kegiatan_detail_id = :kegiatan_id";
            $params['kegiatan_id'] = $kegiatan_id;
        }
        
        $sql .= " ORDER BY kd.nama_kegiatan, 
                  CASE WHEN kp.peran = 'PML' THEN 1 ELSE 2 END,
                  kp.peran DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ Check if perjalanan dinas complete
     * FIXED: Memeriksa 3 file (spd/sppd, laporan_perjalanan, pengeluaran)
     */
    public function checkPerjalananComplete($kegiatan_petugas_id)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM administrasi_files 
                WHERE kegiatan_petugas_id = ? 
                AND jenis_administrasi = 'perjalanan_dinas'
                AND sub_jenis IN ('sppd', 'spd', 'laporan_perjalanan', 'pengeluaran')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatan_petugas_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Minimal 3 file (spd/sppd, laporan_perjalanan, pengeluaran)
        return $result['count'] >= 3;
    }

    /**
     * ✅ Check if honorarium complete
     */
    public function checkHonorariumComplete($kegiatan_petugas_id)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM administrasi_files 
                WHERE kegiatan_petugas_id = ? 
                AND jenis_administrasi = 'honorarium'
                AND sub_jenis IN ('bast', 'perjanjian')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatan_petugas_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] >= 2;
    }

    /**
     * ✅ Get kegiatan for user
     */
    public function getKegiatanForUser($user)
    {
        $role_id = $user['role_id'] ?? null;
        $team_id = $user['team_id'] ?? null;

        // Admin (1) lihat semua kegiatan. Role lain di-scope per tim.
        // Tidak perlu JOIN kegiatan_petugas — kegiatan tetap muncul walau belum ada petugasnya,
        // supaya admin/operator bisa upload dokumen administrasi level kegiatan lebih dulu.
        if ((int)$role_id === 1) {
            $stmt = $this->db->query(
                "SELECT id, nama_kegiatan FROM kegiatan_detail ORDER BY nama_kegiatan"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->db->prepare(
            "SELECT id, nama_kegiatan FROM kegiatan_detail
             WHERE team_id = ? ORDER BY nama_kegiatan"
        );
        $stmt->execute([$team_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ Get statistics for dashboard
     */
    public function getStatistics($userTeamId, $role)
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status_ppk = 'pending' THEN 1 ELSE 0 END) as pending_ppk,
                    SUM(CASE WHEN status_ppk = 'approved' THEN 1 ELSE 0 END) as approved_ppk,
                    SUM(CASE WHEN status_ppk = 'rejected' THEN 1 ELSE 0 END) as rejected_ppk,
                    SUM(CASE WHEN status_bendahara = 'pending' THEN 1 ELSE 0 END) as pending_bendahara,
                    SUM(CASE WHEN status_bendahara = 'approved' THEN 1 ELSE 0 END) as approved_bendahara,
                    SUM(CASE WHEN status_bendahara = 'rejected' THEN 1 ELSE 0 END) as rejected_bendahara
                FROM administrasi a
                JOIN kegiatan_petugas kp ON a.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
                WHERE 1=1";
        
        $params = [];
        
        // Filter berdasarkan role
        if (!in_array($role, ['admin', 'ppk', 'bendahara'])) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $userTeamId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ Get administrasi by bulan and jenis
     */
    public function getByBulanJenis($bulan, $jenis_kegiatan = null, $team_id = null)
    {
        $sql = "SELECT 
                    a.*,
                    k.nama_kegiatan,
                    k.jenis_kegiatan,
                    NULL as created_by_name,
                    t.name AS team_name
                FROM administrasi a
                JOIN kegiatan_petugas kp ON a.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
                -- User info removed (column created_by doesnt exist)
                LEFT JOIN teams t ON t.id = k.team_id
                WHERE DATE_FORMAT(a.created_at, '%Y-%m') = :bulan";
        
        $params = ['bulan' => $bulan];
        
        if (!empty($jenis_kegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenis_kegiatan;
        }
        
        if (!empty($team_id)) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $team_id;
        }
        
        $sql .= " ORDER BY a.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Get verification list for Kepala
     */
    public function getVerificationListKepala($team_id = null)
    {
        $sql = "
            SELECT 
                va.*,
                kp.peran,
                kp.petugas_source,
                kd.nama_kegiatan,
                kd.team_id,
                t.name as team_name,
                CASE 
                    WHEN kp.petugas_source = 'users' THEN u.name
                    WHEN kp.petugas_source = 'mitra' THEN m.nama
                END AS petugas_nama
            FROM verifikasi_administrasi va
            JOIN kegiatan_petugas kp ON kp.id = va.kegiatan_petugas_id
            JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
            LEFT JOIN teams t ON t.id = kd.team_id
            LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
            LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
            WHERE va.status = 'pending_kepala'
        ";
        
        $params = [];
        
        if ($team_id) {
            $sql .= " AND kd.team_id = :team_id";
            $params['team_id'] = $team_id;
        }
        
        $sql .= " ORDER BY va.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Get verification list for PPK
     */
    public function getVerificationListPPK($team_id = null)
    {
        $sql = "
            SELECT 
                va.*,
                kp.peran,
                kp.petugas_source,
                kd.nama_kegiatan,
                kd.team_id,
                t.name as team_name,
                CASE 
                    WHEN kp.petugas_source = 'users' THEN u.name
                    WHEN kp.petugas_source = 'mitra' THEN m.nama
                END AS petugas_nama
            FROM verifikasi_administrasi va
            JOIN kegiatan_petugas kp ON kp.id = va.kegiatan_petugas_id
            JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
            LEFT JOIN teams t ON t.id = kd.team_id
            LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
            LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
            WHERE va.status = 'approved_kepala'
        ";
        
        $params = [];
        
        if ($team_id) {
            $sql .= " AND kd.team_id = :team_id";
            $params['team_id'] = $team_id;
        }
        
        $sql .= " ORDER BY va.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Get verification list for Bendahara
     */
    public function getVerificationListBendahara($team_id = null)
    {
        $sql = "
            SELECT 
                va.*,
                kp.peran,
                kp.petugas_source,
                kd.nama_kegiatan,
                kd.team_id,
                t.name as team_name,
                CASE 
                    WHEN kp.petugas_source = 'users' THEN u.name
                    WHEN kp.petugas_source = 'mitra' THEN m.nama
                END AS petugas_nama
            FROM verifikasi_administrasi va
            JOIN kegiatan_petugas kp ON kp.id = va.kegiatan_petugas_id
            JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
            LEFT JOIN teams t ON t.id = kd.team_id
            LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
            LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
            WHERE va.status = 'approved_ppk'
        ";
        
        $params = [];
        
        if ($team_id) {
            $sql .= " AND kd.team_id = :team_id";
            $params['team_id'] = $team_id;
        }
        
        $sql .= " ORDER BY va.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW METHOD: Get verification history for petugas
     */
    public function getVerificationHistory($kegiatanPetugasId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                va.*,
                CASE 
                    WHEN va.status = 'pending_kepala' THEN 'Menunggu Verifikasi Kepala'
                    WHEN va.status = 'approved_kepala' THEN 'Disetujui Kepala'
                    WHEN va.status = 'rejected_kepala' THEN 'Ditolak Kepala'
                    WHEN va.status = 'approved_ppk' THEN 'Disetujui PPK'
                    WHEN va.status = 'rejected_ppk' THEN 'Ditolak PPK'
                    WHEN va.status = 'completed' THEN 'Selesai (Disetujui Bendahara)'
                    WHEN va.status = 'rejected_bendahara' THEN 'Ditolak Bendahara'
                    ELSE va.status
                END as status_text
            FROM verifikasi_administrasi va
            WHERE va.kegiatan_petugas_id = ?
            ORDER BY va.created_at DESC
        ");
        $stmt->execute([$kegiatanPetugasId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
 * TAMBAHAN: Get files by petugas untuk modal
 * FIXED: Menambahkan prefix 'public/' ke file_path agar bisa diakses dari browser
 */
public function getFilesByPetugas($petugasId)
{
    $stmt = $this->db->prepare("
        SELECT 
            id,
            kegiatan_petugas_id,
            jenis_administrasi,
            jenis_file as kategori_file,           -- FIX: Pakai jenis_file (yang ada di tabel)
            sub_jenis,
            nama_file,
            CONCAT('public/', file_path) as file_path,
            created_at as uploaded_at              -- FIX: Pakai created_at (yang ada di tabel)
        FROM administrasi_files
        WHERE kegiatan_petugas_id = ?
        ORDER BY 
            CASE 
                WHEN jenis_administrasi = 'perjalanan_dinas' THEN 1
                WHEN jenis_administrasi = 'honorarium' THEN 2
                ELSE 3
            END,
            CASE 
                WHEN sub_jenis = 'sppd' THEN 1
                WHEN sub_jenis = 'spd' THEN 1
                WHEN sub_jenis = 'laporan_perjalanan' THEN 2
                WHEN sub_jenis = 'pengeluaran' THEN 3
                WHEN sub_jenis = 'foto' THEN 4
                WHEN sub_jenis = 'bast' THEN 5
                WHEN sub_jenis = 'perjanjian' THEN 6
                ELSE 7
            END
    ");
    $stmt->execute([$petugasId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 /**
     * TAMBAH METHOD BARU: Reset status verifikasi ke pending (Admin Only)
     */
    public function resetVerifikasiStatus($verifikasiId, $userId)
    {
        $sql = "UPDATE verifikasi_administrasi 
                SET status = 'pending_operator_tim',
                    catatan_operator_tim = NULL,
                    verified_operator_tim_at = NULL,
                    updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$verifikasiId]);
    }

    /**
     * Kirim Ulang Verifikasi (setelah ditolak)
     */
    public function kirimUlangVerifikasi($kegiatanPetugasId)
    {
        $sql = "UPDATE verifikasi_administrasi 
                SET status = 'pending_operator_tim',
                    catatan_operator_tim = NULL,
                    verified_operator_tim_at = NULL,
                    updated_at = NOW()
                WHERE kegiatan_petugas_id = ?
                AND status = 'rejected_operator_tim'";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$kegiatanPetugasId]);
    }

    /**
 * Get ALL petugas for a kegiatan (including those who haven't submitted)
 */
public function getAllPetugasForKegiatan($kegiatanDetailId)
{
    $sql = "SELECT 
                kp.id as kegiatan_petugas_id,
                kp.peran,
                CASE 
                    WHEN kp.petugas_source = 'mitra' THEN m.nama
                    WHEN kp.petugas_source = 'users' THEN u.name
                END as petugas_nama,
                CASE 
                    WHEN kp.petugas_source = 'mitra' THEN 'Mitra'
                    ELSE 'PNS'
                END as petugas_jenis,
                va.id as verifikasi_id,
                va.status,
                va.catatan_operator_tim,
                va.created_at as submitted_at,
                (SELECT COUNT(*) FROM administrasi_files af 
                 WHERE af.kegiatan_petugas_id = kp.id 
                 AND af.jenis_administrasi = 'perjalanan_dinas') as file_perjalanan_count,
                (SELECT COUNT(*) FROM administrasi_files af 
                 WHERE af.kegiatan_petugas_id = kp.id 
                 AND af.jenis_administrasi = 'honorarium') as file_honorarium_count
            FROM kegiatan_petugas kp
            LEFT JOIN mitra m ON kp.petugas_id = m.id AND kp.petugas_source = 'mitra'
            LEFT JOIN users u ON kp.petugas_id = u.id AND kp.petugas_source = 'users'
            LEFT JOIN verifikasi_administrasi va ON va.kegiatan_petugas_id = kp.id
            WHERE kp.kegiatan_detail_id = ?
            ORDER BY kp.peran DESC, petugas_nama ASC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$kegiatanDetailId]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

    /**
     * Insert dokumen kegiatan (KAK, SK KPA, Daftar Nominatif)
     */
    public function insertDokumenKegiatan($data)
    {
        $sql = "INSERT INTO dokumen_kegiatan 
                (kegiatan_detail_id, jenis_dokumen, nama_file, file_path, file_type, file_size, uploaded_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['kegiatan_detail_id'],
            $data['jenis_dokumen'],
            $data['nama_file'],
            $data['file_path'],
            $data['file_type'] ?? null,
            $data['file_size'] ?? null,
            $data['uploaded_by']
        ]);
    }

    /**
     * Delete dokumen kegiatan by jenis
     */
    public function deleteDokumenKegiatan($kegiatanDetailId, $jenisDokumen)
    {
        // Get file path first
        $stmt = $this->db->prepare("SELECT file_path FROM dokumen_kegiatan WHERE kegiatan_detail_id = ? AND jenis_dokumen = ?");
        $stmt->execute([$kegiatanDetailId, $jenisDokumen]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete physical file
        if ($file && !empty($file['file_path'])) {
            $fullPath = __DIR__ . '/../public/' . $file['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // Delete database record
        $sql = "DELETE FROM dokumen_kegiatan WHERE kegiatan_detail_id = ? AND jenis_dokumen = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$kegiatanDetailId, $jenisDokumen]);
    }

    /**
     * Get file dengan informasi kegiatan dan petugas
     */
    public function getFileWithInfo($fileId)
    {
        $sql = "SELECT 
                    af.*,
                    kd.nama_kegiatan,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                        ELSE 'Unknown'
                    END AS petugas_nama
                FROM administrasi_files af
                JOIN kegiatan_petugas kp ON af.kegiatan_petugas_id = kp.id
                JOIN kegiatan_detail kd ON kp.kegiatan_detail_id = kd.id
                LEFT JOIN users u ON kp.petugas_id = u.id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON kp.petugas_id = m.id AND kp.petugas_source = 'mitra'
                WHERE af.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fileId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all files for a kegiatan (untuk download ZIP)
     */
    public function getAllFilesForKegiatan($kegiatanDetailId, $subJenis = null)
    {
        $sql = "SELECT 
                    af.id,
                    af.kegiatan_petugas_id,
                    af.jenis_administrasi,
                    af.sub_jenis,
                    af.nama_file,
                    af.file_path,
                    af.file_type,
                    kd.nama_kegiatan,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                        ELSE 'Unknown'
                    END AS petugas_nama
                FROM administrasi_files af
                JOIN kegiatan_petugas kp ON af.kegiatan_petugas_id = kp.id
                JOIN kegiatan_detail kd ON kp.kegiatan_detail_id = kd.id
                LEFT JOIN users u ON kp.petugas_id = u.id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON kp.petugas_id = m.id AND kp.petugas_source = 'mitra'
                WHERE kp.kegiatan_detail_id = ?";
        
        $params = [$kegiatanDetailId];
        
        if ($subJenis) {
            $sql .= " AND af.sub_jenis = ?";
            $params[] = $subJenis;
        }
        
        $sql .= " ORDER BY petugas_nama, af.sub_jenis";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get dokumen kegiatan (KAK, SK KPA, Daftar Nominatif)
     */
    public function getDokumenKegiatan($kegiatanDetailId)
    {
        $sql = "SELECT * FROM dokumen_kegiatan WHERE kegiatan_detail_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get dokumen kegiatan grouped by jenis_dokumen
     */
    public function getDokumenKegiatanGrouped($kegiatanDetailId)
    {
        $docs = $this->getDokumenKegiatan($kegiatanDetailId);
        $grouped = [];
        foreach ($docs as $doc) {
            $grouped[$doc['jenis_dokumen']] = $doc;
        }
        return $grouped;
    }

    /**
     * NEW: Get semua kegiatan dengan status verifikasi untuk halaman Data Administrasi
     * Menampilkan posisi dokumen (operator_tim/kepala/ppk/bendahara) dan tanggal lunas
     */
    public function getAllKegiatanWithStatus($teamId = null, $bulan = null, $jenisKegiatan = null)
    {
        $sql = "
        SELECT 
            k.id as kegiatan_detail_id,
            k.nama_kegiatan,
            k.jenis_kegiatan,
            k.team_id,
            t.name as team_name,
            COUNT(va.id) as total_petugas,
            
            -- Hitung per status
            SUM(CASE WHEN va.status = 'pending_operator_tim' THEN 1 ELSE 0 END) as cnt_pending_operator,
            SUM(CASE WHEN va.status = 'approved_operator_tim' THEN 1 ELSE 0 END) as cnt_approved_operator,
            SUM(CASE WHEN va.status = 'rejected_operator_tim' THEN 1 ELSE 0 END) as cnt_rejected_operator,
            SUM(CASE WHEN va.status = 'pending_kepala' THEN 1 ELSE 0 END) as cnt_pending_kepala,
            SUM(CASE WHEN va.status = 'approved_kepala' THEN 1 ELSE 0 END) as cnt_approved_kepala,
            SUM(CASE WHEN va.status = 'rejected_kepala' THEN 1 ELSE 0 END) as cnt_rejected_kepala,
            SUM(CASE WHEN va.status = 'approved_ppk' THEN 1 ELSE 0 END) as cnt_approved_ppk,
            SUM(CASE WHEN va.status = 'rejected_ppk' THEN 1 ELSE 0 END) as cnt_rejected_ppk,
            SUM(CASE WHEN va.status = 'completed' THEN 1 ELSE 0 END) as cnt_completed,
            SUM(CASE WHEN va.status = 'rejected_bendahara' THEN 1 ELSE 0 END) as cnt_rejected_bendahara,
            
            -- Tanggal penting
            MIN(va.administrasi_submitted_at) as first_submit,
            MAX(va.verified_operator_tim_at) as last_verified_operator,
            MAX(CASE WHEN va.status IN ('approved_kepala','approved_ppk','rejected_ppk','completed','rejected_bendahara') THEN va.updated_at END) as last_verified_kepala,
            MAX(CASE WHEN va.status IN ('approved_ppk','completed','rejected_bendahara') THEN va.updated_at END) as last_verified_ppk,
            MAX(CASE WHEN va.status = 'completed' THEN va.updated_at END) as last_verified_bendahara,
            
            -- Dokumen kegiatan
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'kak') > 0 as has_kak,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'sk_kpa') > 0 as has_sk_kpa,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'daftar_nominatif') > 0 as has_daftar_nominatif,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'form_permintaan') > 0 as has_form_permintaan
            
        FROM verifikasi_administrasi va
        JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
        LEFT JOIN teams t ON t.id = k.team_id
        WHERE 1=1
        ";
        
        $params = [];
        
        if ($teamId !== null) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $teamId;
        }
        
        if (!empty($bulan)) {
            $sql .= " AND DATE_FORMAT(va.administrasi_submitted_at, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan;
        }
        
        if (!empty($jenisKegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenisKegiatan;
        }
        
        $sql .= " GROUP BY k.id, k.nama_kegiatan, k.jenis_kegiatan, k.team_id, t.name
                  ORDER BY 
                    CASE 
                        WHEN SUM(CASE WHEN va.status = 'completed' THEN 1 ELSE 0 END) = COUNT(va.id) THEN 1
                        ELSE 0
                    END ASC,
                    MAX(va.updated_at) DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get kegiatan grouped for KEPALA verification (status = pending_kepala)
     * Returns same format as getAllForOperatorTim for unified view
     */
    public function getAllForKepalaGrouped($teamId = null, $bulan = null, $jenis_kegiatan = null)
    {
        $sql = "
        SELECT 
            k.id as kegiatan_detail_id,
            k.nama_kegiatan,
            k.jenis_kegiatan,
            k.team_id,
            t.name as team_name,
            COUNT(va.id) as total_petugas,
            COUNT(va.id) as pending_count,
            0 as approved_count,
            0 as rejected_count,
            MIN(va.administrasi_submitted_at) as first_submit,
            'all_approved' as kegiatan_status,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'kak') > 0 as has_kak,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'sk_kpa') > 0 as has_sk_kpa,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'daftar_nominatif') > 0 as has_daftar_nominatif,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'form_permintaan') > 0 as has_form_permintaan
        FROM verifikasi_administrasi va
        JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
        LEFT JOIN teams t ON t.id = k.team_id
        WHERE va.status = 'pending_kepala'
        ";
        
        $params = [];
        if ($teamId !== null) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $teamId;
        }
        if (!empty($bulan)) {
            $sql .= " AND DATE_FORMAT(va.administrasi_submitted_at, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan;
        }
        if (!empty($jenis_kegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenis_kegiatan;
        }
        
        $sql .= " GROUP BY k.id, k.nama_kegiatan, k.jenis_kegiatan, k.team_id, t.name
                  ORDER BY MAX(va.updated_at) DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get kegiatan grouped for PPK verification (status = approved_kepala)
     */
    public function getAllForPPKGrouped($teamId = null, $bulan = null, $jenis_kegiatan = null)
    {
        $sql = "
        SELECT 
            k.id as kegiatan_detail_id,
            k.nama_kegiatan,
            k.jenis_kegiatan,
            k.team_id,
            t.name as team_name,
            COUNT(va.id) as total_petugas,
            COUNT(va.id) as pending_count,
            0 as approved_count,
            0 as rejected_count,
            MIN(va.administrasi_submitted_at) as first_submit,
            'all_approved' as kegiatan_status,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'kak') > 0 as has_kak,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'sk_kpa') > 0 as has_sk_kpa,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'daftar_nominatif') > 0 as has_daftar_nominatif,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'form_permintaan') > 0 as has_form_permintaan
        FROM verifikasi_administrasi va
        JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
        LEFT JOIN teams t ON t.id = k.team_id
        WHERE va.status = 'approved_kepala'
        ";
        
        $params = [];
        if ($teamId !== null) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $teamId;
        }
        if (!empty($bulan)) {
            $sql .= " AND DATE_FORMAT(va.administrasi_submitted_at, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan;
        }
        if (!empty($jenis_kegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenis_kegiatan;
        }
        
        $sql .= " GROUP BY k.id, k.nama_kegiatan, k.jenis_kegiatan, k.team_id, t.name
                  ORDER BY MAX(va.updated_at) DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Send kegiatan from Kepala to PPK (pending_kepala -> approved_kepala)
     */
    public function sendKegiatanToPPK($kegiatanDetailId)
    {
        $sql = "UPDATE verifikasi_administrasi va
                JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
                SET va.status = 'approved_kepala',
                    va.updated_at = NOW()
                WHERE kp.kegiatan_detail_id = ?
                AND va.status = 'pending_kepala'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Send kegiatan from PPK to completed/approved_ppk
     */
    public function sendKegiatanToApprovedPPK($kegiatanDetailId)
    {
        $sql = "UPDATE verifikasi_administrasi va
                JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
                SET va.status = 'approved_ppk',
                    va.updated_at = NOW()
                WHERE kp.kegiatan_detail_id = ?
                AND va.status = 'approved_kepala'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Count verifikasi by status for debugging
     */
    public function countVerifikasiByStatus($kegiatanDetailId, $status)
    {
        $sql = "SELECT COUNT(*) FROM verifikasi_administrasi va
                JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
                WHERE kp.kegiatan_detail_id = ? AND va.status = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId, $status]);
        return $stmt->fetchColumn();
    }

    /**
     * Reject kegiatan by Kepala (pending_kepala -> rejected_kepala)
     */
    public function rejectKegiatanByKepala($kegiatanDetailId, $catatan)
    {
        $sql = "UPDATE verifikasi_administrasi va
                JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
                SET va.status = 'rejected_kepala',
                    va.updated_at = NOW()
                WHERE kp.kegiatan_detail_id = ?
                AND va.status = 'pending_kepala'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Reject kegiatan by PPK (approved_kepala -> rejected_ppk)
     */
    public function rejectKegiatanByPPK($kegiatanDetailId, $catatan)
    {
        $sql = "UPDATE verifikasi_administrasi va
                JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
                SET va.status = 'rejected_ppk',
                    va.updated_at = NOW()
                WHERE kp.kegiatan_detail_id = ?
                AND va.status = 'approved_kepala'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get Data Administrasi - kegiatan yang sudah PPK approve (untuk halaman pembayaran)
     * Returns kegiatan-level data with payment status
     */
    public function getDataAdministrasi($teamId = null, $bulan = null, $jenisKegiatan = null)
    {
        $sql = "
        SELECT 
            k.id as kegiatan_detail_id,
            k.nama_kegiatan,
            k.jenis_kegiatan,
            k.team_id,
            t.name as team_name,
            COUNT(va.id) as total_petugas,
            SUM(CASE WHEN va.status = 'approved_ppk' THEN 1 ELSE 0 END) as cnt_approved_ppk,
            SUM(CASE WHEN va.status = 'completed' THEN 1 ELSE 0 END) as cnt_completed,
            CASE 
                WHEN SUM(CASE WHEN va.status = 'completed' THEN 1 ELSE 0 END) = COUNT(va.id) THEN 'completed'
                WHEN SUM(CASE WHEN va.status = 'approved_ppk' THEN 1 ELSE 0 END) > 0 THEN 'approved_ppk'
                ELSE 'pending'
            END as verifikasi_status,
            CASE 
                WHEN SUM(CASE WHEN va.status = 'completed' THEN 1 ELSE 0 END) = COUNT(va.id) THEN 'lunas'
                WHEN SUM(CASE WHEN va.status = 'approved_ppk' THEN 1 ELSE 0 END) > 0 THEN 'belum'
                ELSE 'belum_bisa'
            END as status_pembayaran,
            MIN(va.administrasi_submitted_at) as first_submit,
            MIN(CASE WHEN va.status IN ('pending_kepala', 'approved_kepala', 'rejected_kepala', 'pending_ppk', 'approved_ppk', 'rejected_ppk', 'completed') 
                THEN va.verified_operator_tim_at END) as first_sent_to_kepala,
            MAX(CASE WHEN va.status = 'completed' THEN COALESCE(va.verified_bendahara_at, va.updated_at) END) as tanggal_lunas,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'kak') > 0 as has_kak,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'sk_kpa') > 0 as has_sk_kpa,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'daftar_nominatif') > 0 as has_daftar_nominatif,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'form_permintaan') > 0 as has_form_permintaan
        FROM verifikasi_administrasi va
        JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
        JOIN kegiatan_detail k ON kp.kegiatan_detail_id = k.id
        LEFT JOIN teams t ON t.id = k.team_id
        WHERE va.status IN ('approved_ppk', 'completed')
        ";
        
        $params = [];
        if ($teamId !== null) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $teamId;
        }
        if (!empty($bulan)) {
            $sql .= " AND DATE_FORMAT(va.administrasi_submitted_at, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan;
        }
        if (!empty($jenisKegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenisKegiatan;
        }
        
        $sql .= " GROUP BY k.id, k.nama_kegiatan, k.jenis_kegiatan, k.team_id, t.name
                  ORDER BY 
                    CASE WHEN SUM(CASE WHEN va.status = 'completed' THEN 1 ELSE 0 END) = COUNT(va.id) THEN 1 ELSE 0 END ASC,
                    MAX(va.updated_at) DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark kegiatan as paid (approved_ppk -> completed)
     * IMPORTANT: Hanya update record yang sudah approved_ppk, tidak boleh override status lain
     * FIX: Tambahkan update verified_bendahara_at untuk tanggal lunas
     */
    public function markKegiatanLunas($kegiatanDetailId)
    {
        // HANYA update record yang status-nya approved_ppk
        // Tidak boleh mengubah status lain (pending, rejected, dll)
        $sql = "UPDATE verifikasi_administrasi va
                JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
                SET va.status = 'completed',
                    va.verified_bendahara_at = NOW(),
                    va.updated_at = NOW()
                WHERE kp.kegiatan_detail_id = ?
                AND va.status = 'approved_ppk'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Mark kegiatan as unpaid (completed -> approved_ppk)
     * FIX: Reset verified_bendahara_at ke NULL saat dikembalikan
     */
    public function markKegiatanBelumLunas($kegiatanDetailId)
    {
        $sql = "UPDATE verifikasi_administrasi va
                JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
                SET va.status = 'approved_ppk',
                    va.verified_bendahara_at = NULL,
                    va.updated_at = NOW()
                WHERE kp.kegiatan_detail_id = ?
                AND va.status = 'completed'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$kegiatanDetailId]);
    }

    /**
     * Get detail petugas with files for a kegiatan (for detail popup in Data Administrasi)
     */
    public function getPetugasWithFilesForKegiatan($kegiatanDetailId)
    {
        $sql = "
        SELECT 
            kp.id as kegiatan_petugas_id,
            kp.peran,
            CASE 
                WHEN kp.petugas_source = 'users' THEN u.name
                WHEN kp.petugas_source = 'mitra' THEN m.nama
            END AS petugas_nama,
            CASE 
                WHEN kp.petugas_source = 'users' THEN 'PNS'
                WHEN kp.petugas_source = 'mitra' THEN 'Mitra'
            END AS petugas_jenis,
            va.status as verifikasi_status
        FROM kegiatan_petugas kp
        LEFT JOIN verifikasi_administrasi va ON va.kegiatan_petugas_id = kp.id
        LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
        LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
        WHERE kp.kegiatan_detail_id = ?
        AND va.status IN ('approved_ppk', 'completed')
        ORDER BY kp.peran, petugas_nama
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ NEW: Get petugas with files by jenis dokumen untuk Data Administrasi modal
     */
    public function getPetugasWithFilesByJenis($kegiatanDetailId, $fileTypes = [])
    {
        // Build file type condition
        $fileTypeCondition = '';
        $params = [$kegiatanDetailId];
        
        if (!empty($fileTypes)) {
            $placeholders = implode(',', array_fill(0, count($fileTypes), '?'));
            $fileTypeCondition = "AND af.jenis_file IN ($placeholders)";
            $params = array_merge($params, $fileTypes);
        }
        
        $sql = "
        SELECT 
            kp.id as kegiatan_petugas_id,
            kp.peran,
            CASE 
                WHEN kp.petugas_source = 'users' THEN u.name
                WHEN kp.petugas_source = 'mitra' THEN m.nama
            END AS petugas_nama,
            CASE 
                WHEN kp.petugas_source = 'users' THEN 'PNS'
                WHEN kp.petugas_source = 'mitra' THEN 'Mitra'
            END AS petugas_jenis,
            af.file_path,
            af.nama_file,
            af.jenis_file
        FROM kegiatan_petugas kp
        LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
        LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
        LEFT JOIN administrasi_files af ON af.kegiatan_petugas_id = kp.id $fileTypeCondition
        WHERE kp.kegiatan_detail_id = ?
        ORDER BY kp.peran, petugas_nama
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get kegiatan untuk Data Administrasi
     * Hanya tampilkan kegiatan yang sudah mulai (tanggal_mulai <= hari ini)
     */
    /**
     * ========================================
     * NEW METHOD: Get ALL Kegiatan untuk Data Administrasi
     * SEMUA kegiatan langsung muncul dengan status:
     * - belum_mulai (tanggal_mulai > today)
     * - sedang_berlangsung (tanggal_mulai <= today <= tanggal_selesai)
     * - selesai (tanggal_selesai < today)
     * 
     * Filter: team, tahun, bulan, jenis_kegiatan
     * ========================================
     */
    public function getAllKegiatanForDataAdministrasi($teamId = null, $tahun = null, $bulan = null, $jenisKegiatan = null)
    {
        $sql = "
        SELECT 
            k.id as kegiatan_detail_id,
            k.nama_kegiatan,
            k.jenis_kegiatan,
            k.team_id,
            k.rentang_waktu_mulai as tanggal_mulai,
            k.rentang_waktu_selesai as tanggal_selesai,
            k.created_at,
            t.name as team_name,
            
            -- Total petugas
            (SELECT COUNT(*) FROM kegiatan_petugas kp WHERE kp.kegiatan_detail_id = k.id) as total_petugas,
            
            -- Total yang sudah submit administrasi
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id) as total_submitted,
            
            -- Count per status verifikasi
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_operator_tim') as cnt_pending_operator,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_operator_tim') as cnt_approved_operator,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'rejected_operator_tim') as cnt_rejected_operator,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_kepala') as cnt_pending_kepala,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_kepala') as cnt_approved_kepala,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'rejected_kepala') as cnt_rejected_kepala,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_ppk') as cnt_pending_ppk,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_ppk') as cnt_approved_ppk,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'rejected_ppk') as cnt_rejected_ppk,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') as cnt_completed,
            
            -- Status kegiatan berdasarkan TANGGAL (menggunakan rentang_waktu)
            CASE 
                WHEN k.rentang_waktu_selesai < CURDATE() THEN 'selesai'
                WHEN k.rentang_waktu_mulai <= CURDATE() AND k.rentang_waktu_selesai >= CURDATE() THEN 'sedang_berlangsung'
                ELSE 'belum_mulai'
            END as status_kegiatan,
            
            -- Posisi verifikasi saat ini (untuk badge)
            -- FIX: Tambahkan pengecekan rejected_kepala, rejected_ppk, rejected_bendahara
            CASE 
                -- Kondisi 1: Semua record di verifikasi_administrasi sudah completed
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') > 0
                     AND (SELECT COUNT(*) FROM verifikasi_administrasi va 
                          JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                          WHERE kp.kegiatan_detail_id = k.id 
                          AND va.status NOT IN ('completed', 'rejected_operator_tim', 'rejected_kepala', 'rejected_ppk', 'rejected_bendahara')) = 0
                THEN 'completed'
                -- Rejected Bendahara - dikembalikan ke Operator Tim
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'rejected_bendahara') > 0
                THEN 'rejected_bendahara'
                -- Rejected PPK - dikembalikan ke Operator Tim
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'rejected_ppk') > 0
                THEN 'rejected_ppk'
                -- Rejected Kepala - dikembalikan ke Operator Tim
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'rejected_kepala') > 0
                THEN 'rejected_kepala'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_ppk') > 0
                THEN 'approved_ppk'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_ppk') > 0
                THEN 'pending_ppk'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_kepala') > 0
                THEN 'approved_kepala'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_kepala') > 0
                THEN 'pending_kepala'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_operator_tim') > 0
                THEN 'approved_operator_tim'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_operator_tim') > 0
                THEN 'pending_operator_tim'
                -- Rejected Operator Tim
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'rejected_operator_tim') > 0
                THEN 'rejected_operator_tim'
                ELSE 'belum_submit'
            END as verifikasi_status,
            
            -- Status PPK (untuk kompatibilitas)
            CASE 
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status IN ('approved_ppk', 'completed')) > 0
                THEN 'approved'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'rejected_ppk') > 0
                THEN 'rejected'
                ELSE 'pending'
            END as status_ppk,
            
            -- Status Bendahara (untuk kompatibilitas)
            -- FIX: Perbaiki kondisi agar tidak memerlukan SEMUA petugas completed
            CASE 
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') > 0
                     AND (SELECT COUNT(*) FROM verifikasi_administrasi va 
                          JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                          WHERE kp.kegiatan_detail_id = k.id 
                          AND va.status NOT IN ('completed', 'rejected_operator_tim', 'rejected_kepala', 'rejected_ppk', 'rejected_bendahara')) = 0
                THEN 'approved'
                ELSE 'pending'
            END as status_bendahara,
            
            -- Status pembayaran
            -- FIXED: 
            -- 'lunas' = SEMUA petugas sudah 'completed' (sudah diklik tombol Lunas)
            -- 'siap_bayar' = Ada yang 'approved_ppk' (menunggu diklik Lunas)
            -- 'pending' = Belum sampai PPK
            CASE 
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') > 0
                     AND (SELECT COUNT(*) FROM verifikasi_administrasi va 
                          JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                          WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_ppk') = 0
                THEN 'lunas'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_ppk') > 0
                THEN 'siap_bayar'
                ELSE 'pending'
            END as status_pembayaran,
            
            -- Tanggal submit pertama ke kepala (pertama kali dikirim)
            -- Include semua status yang menunjukkan sudah pernah dikirim ke kepala
            (SELECT MIN(COALESCE(va.updated_at, va.administrasi_submitted_at, va.created_at)) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id 
             AND va.status IN ('pending_kepala', 'approved_kepala', 'rejected_kepala', 'approved_ppk', 'rejected_ppk', 'completed', 'rejected_bendahara')) as first_sent_to_kepala,
             
            -- Tanggal lunas (prioritaskan verified_bendahara_at)
            (SELECT MAX(COALESCE(va.verified_bendahara_at, va.updated_at)) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') as tanggal_lunas,
            
            -- Dokumen kegiatan
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'kak') > 0 as has_kak,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'sk_kpa') > 0 as has_sk_kpa,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'daftar_nominatif') > 0 as has_daftar_nominatif,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'form_permintaan') > 0 as has_form_permintaan,
            
            -- Backward compatibility fields
            k.id as id,
            NULL as uploaded_by,
            NULL as uploaded_by_type,
            NULL as uploaded_by_name,
            NULL as petugas_peran,
            NULL as komentar_ppk
            
        FROM kegiatan_detail k
        LEFT JOIN teams t ON t.id = k.team_id
        WHERE 1=1
        ";
        
        $params = [];
        
        // Filter by team
        if (!empty($teamId)) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $teamId;
        }
        
        // Filter by tahun (gunakan rentang_waktu_mulai)
        if (!empty($tahun)) {
            $sql .= " AND YEAR(k.rentang_waktu_mulai) = :tahun";
            $params['tahun'] = $tahun;
        }
        
        // Filter by bulan (format: YYYY-MM atau hanya MM)
        if (!empty($bulan)) {
            if (strlen($bulan) == 7) {
                // Format YYYY-MM
                $sql .= " AND (DATE_FORMAT(k.rentang_waktu_mulai, '%Y-%m') = :bulan OR DATE_FORMAT(k.rentang_waktu_selesai, '%Y-%m') = :bulan2)";
                $params['bulan'] = $bulan;
                $params['bulan2'] = $bulan;
            } else {
                // Format MM only
                $sql .= " AND (MONTH(k.rentang_waktu_mulai) = :bulan OR MONTH(k.rentang_waktu_selesai) = :bulan2)";
                $params['bulan'] = intval($bulan);
                $params['bulan2'] = intval($bulan);
            }
        }
        
        // Filter by jenis kegiatan
        if (!empty($jenisKegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenisKegiatan;
        }
        
        // Order: sedang berlangsung dulu, lalu belum mulai, lalu selesai
        $sql .= " ORDER BY 
            CASE 
                WHEN k.tanggal_mulai <= CURDATE() AND k.tanggal_selesai >= CURDATE() THEN 0
                WHEN k.tanggal_mulai > CURDATE() THEN 1
                ELSE 2
            END ASC,
            k.tanggal_mulai DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ORIGINAL METHOD (kept for backward compatibility)
     */
    public function getKegiatanByDateStatus($teamId = null, $bulan = null, $jenisKegiatan = null)
    {
        $sql = "
        SELECT 
            k.id as kegiatan_detail_id,
            k.nama_kegiatan,
            k.jenis_kegiatan,
            k.team_id,
            k.tanggal_mulai,
            k.tanggal_selesai,
            k.created_at,
            t.name as team_name,
            
            -- Total petugas
            (SELECT COUNT(*) FROM kegiatan_petugas kp WHERE kp.kegiatan_detail_id = k.id) as total_petugas,
            
            -- Total yang sudah submit
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id) as total_submitted,
            
            -- Count per status
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_operator_tim') as cnt_pending_operator,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_operator_tim') as cnt_approved_operator,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_kepala') as cnt_pending_kepala,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_kepala') as cnt_approved_kepala,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_ppk') as cnt_pending_ppk,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_ppk') as cnt_approved_ppk,
            (SELECT COUNT(*) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') as cnt_completed,
            
            -- Status kegiatan berdasarkan TANGGAL
            CASE 
                WHEN k.tanggal_selesai < CURDATE() THEN 'selesai'
                WHEN k.tanggal_mulai <= CURDATE() AND k.tanggal_selesai >= CURDATE() THEN 'sedang_berlangsung'
                ELSE 'belum_mulai'
            END as status_kegiatan,
            
            -- Posisi verifikasi saat ini
            CASE 
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') = 
                     (SELECT COUNT(*) FROM kegiatan_petugas kp WHERE kp.kegiatan_detail_id = k.id)
                     AND (SELECT COUNT(*) FROM kegiatan_petugas kp WHERE kp.kegiatan_detail_id = k.id) > 0
                THEN 'completed'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_ppk') > 0
                THEN 'approved_ppk'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_ppk') > 0
                THEN 'pending_ppk'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_kepala') > 0
                THEN 'approved_kepala'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_kepala') > 0
                THEN 'pending_kepala'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_operator_tim') > 0
                THEN 'approved_operator_tim'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_operator_tim') > 0
                THEN 'pending_operator_tim'
                ELSE NULL
            END as verifikasi_status,
            
            -- Status per level (untuk kompatibilitas)
            CASE 
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status IN ('approved_ppk', 'completed')) > 0
                THEN 'approved'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'pending_ppk') > 0
                THEN 'pending'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'rejected_ppk') > 0
                THEN 'rejected'
                ELSE 'pending'
            END as status_ppk,
            
            CASE 
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') = 
                     (SELECT COUNT(*) FROM kegiatan_petugas kp WHERE kp.kegiatan_detail_id = k.id)
                     AND (SELECT COUNT(*) FROM kegiatan_petugas kp WHERE kp.kegiatan_detail_id = k.id) > 0
                THEN 'approved'
                ELSE 'pending'
            END as status_bendahara,
            
            -- Status pembayaran
            CASE 
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') = 
                     (SELECT COUNT(*) FROM kegiatan_petugas kp WHERE kp.kegiatan_detail_id = k.id)
                     AND (SELECT COUNT(*) FROM kegiatan_petugas kp WHERE kp.kegiatan_detail_id = k.id) > 0
                THEN 'lunas'
                WHEN (SELECT COUNT(*) FROM verifikasi_administrasi va 
                      JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
                      WHERE kp.kegiatan_detail_id = k.id AND va.status = 'approved_ppk') > 0
                THEN 'siap_bayar'
                ELSE 'belum_siap'
            END as status_pembayaran,
            
            -- Tanggal
            (SELECT MIN(va.administrasi_submitted_at) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id) as first_submit,
            (SELECT MAX(va.updated_at) FROM verifikasi_administrasi va 
             JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id 
             WHERE kp.kegiatan_detail_id = k.id AND va.status = 'completed') as tanggal_lunas,
            
            -- Dokumen kegiatan
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'kak') > 0 as has_kak,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'sk_kpa') > 0 as has_sk_kpa,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'daftar_nominatif') > 0 as has_daftar_nominatif,
            (SELECT COUNT(*) FROM dokumen_kegiatan dk WHERE dk.kegiatan_detail_id = k.id AND dk.jenis_dokumen = 'form_permintaan') > 0 as has_form_permintaan,
            
            -- Backward compatibility
            k.id as id,
            NULL as uploaded_by,
            NULL as uploaded_by_type,
            NULL as uploaded_by_name,
            NULL as petugas_peran,
            NULL as komentar_ppk
            
        FROM kegiatan_detail k
        LEFT JOIN teams t ON t.id = k.team_id
        WHERE k.tanggal_mulai <= CURDATE()
        ";
        
        $params = [];
        
        // Filter by team
        if ($teamId !== null) {
            $sql .= " AND k.team_id = :team_id";
            $params['team_id'] = $teamId;
        }
        
        // Filter by month
        if (!empty($bulan)) {
            $sql .= " AND (DATE_FORMAT(k.tanggal_mulai, '%Y-%m') = :bulan OR DATE_FORMAT(k.tanggal_selesai, '%Y-%m') = :bulan2)";
            $params['bulan'] = $bulan;
            $params['bulan2'] = $bulan;
        }
        
        // Filter by jenis kegiatan
        if (!empty($jenisKegiatan)) {
            $sql .= " AND k.jenis_kegiatan = :jenis_kegiatan";
            $params['jenis_kegiatan'] = $jenisKegiatan;
        }
        
        // Order: sedang berlangsung dulu, kemudian selesai
        $sql .= " ORDER BY 
            CASE 
                WHEN k.tanggal_mulai <= CURDATE() AND k.tanggal_selesai >= CURDATE() THEN 0
                ELSE 1
            END ASC,
            k.tanggal_mulai DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reject kegiatan by Bendahara - kembalikan ke Operator
     * Status: approved_ppk -> rejected_bendahara -> pending_operator_tim
     */
    public function rejectKegiatanByBendahara($kegiatanDetailId, $catatan)
    {
        // Update semua verifikasi dari approved_ppk ke rejected_bendahara
        // Kemudian otomatis dikembalikan ke operator (pending_operator_tim)
        // FIXED: Gunakan kolom yang ada di tabel (catatan_bendahara, updated_at)
        $sql = "UPDATE verifikasi_administrasi va
                JOIN kegiatan_petugas kp ON va.kegiatan_petugas_id = kp.id
                SET va.status = 'rejected_bendahara',
                    va.catatan_bendahara = ?,
                    va.updated_at = NOW()
                WHERE kp.kegiatan_detail_id = ?
                AND va.status = 'approved_ppk'";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$catatan, $kegiatanDetailId]);
        return $result && $stmt->rowCount() > 0;
    }

    /**
     * Get dokumen kegiatan by jenis
     */
    public function getDokumenKegiatanByJenis($kegiatanDetailId, $jenisDokumen)
    {
        $sql = "SELECT * FROM dokumen_kegiatan 
                WHERE kegiatan_detail_id = ? 
                AND jenis_dokumen = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId, $jenisDokumen]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all petugas files for a kegiatan (for merged PDF)
     */
    public function getAllPetugasFilesForKegiatan($kegiatanDetailId)
    {
        $sql = "SELECT af.* 
                FROM administrasi_files af
                JOIN kegiatan_petugas kp ON af.kegiatan_petugas_id = kp.id
                WHERE kp.kegiatan_detail_id = ?
                ORDER BY kp.peran, af.jenis_file, af.created_at";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatanDetailId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get petugas with files by jenis dokumen
     * @param int $kegiatanDetailId
     * @param array $subJenis - Array of sub_jenis values
    
    public function getPetugasWithFilesByJenis($kegiatanDetailId, $subJenis)
    {
        $placeholders = str_repeat('?,', count($subJenis) - 1) . '?';
        
        $sql = "SELECT 
                    kp.id as kegiatan_petugas_id,
                    kp.peran,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                    END AS petugas_nama,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN 'PNS'
                        WHEN kp.petugas_source = 'mitra' THEN 'Mitra'
                    END AS petugas_jenis,
                    af.file_path,
                    af.nama_file,
                    af.sub_jenis
                FROM kegiatan_petugas kp
                LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
                LEFT JOIN administrasi_files af ON af.kegiatan_petugas_id = kp.id 
                    AND af.sub_jenis IN ({$placeholders})
                WHERE kp.kegiatan_detail_id = ?
                ORDER BY 
                    CASE WHEN kp.peran = 'PML' THEN 1 ELSE 2 END,
                    kp.peran,
                    petugas_nama";
        
        $params = array_merge($subJenis, [$kegiatanDetailId]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
     */

    /**
     * Get kegiatan by ID
     */
    public function getKegiatanById($id)
    {
        $sql = "SELECT * FROM kegiatan_detail WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}