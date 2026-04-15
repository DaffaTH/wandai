<?php
/*
 * WANDAI System - KegiatanDetail Model
 * BPS Kabupaten Paniai
 * 
 * UPDATED: 
 * - Tambah kolom program, output, komponen (untuk pembebanan anggaran SPPD)
 */

class KegiatanDetail
{
    public $db;

    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }

    /**
     * Get all kegiatan by team and role
     */
    public function getAllByTeam($team_id, $role_id)
    {
        $stmt = $this->db->query("
            SELECT kd.*, t.name AS team_name 
            FROM kegiatan_detail kd
            LEFT JOIN teams t ON t.id = kd.team_id
            ORDER BY kd.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all kegiatan (untuk dropdown)
     */
    public function getAll()
    {
        $stmt = $this->db->query("
            SELECT kd.id, kd.nama_kegiatan, kd.jenis_kegiatan, kd.team_id, t.name AS team_name
            FROM kegiatan_detail kd
            LEFT JOIN teams t ON t.id = kd.team_id
            ORDER BY kd.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get kegiatan by team
     */
    public function getByTeam($team_id)
    {
        $stmt = $this->db->prepare("
            SELECT id, nama_kegiatan, jenis_kegiatan
            FROM kegiatan_detail 
            WHERE team_id = ? 
            ORDER BY id DESC
        ");
        $stmt->execute([$team_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get kegiatan by ID dengan data lengkap
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT k.*, t.name as team_name
            FROM kegiatan_detail k
            LEFT JOIN teams t ON t.id = k.team_id
            WHERE k.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get daftar jenis kegiatan unik
     */
    public function getJenisKegiatanList()
    {
        $stmt = $this->db->query("
            SELECT DISTINCT jenis_kegiatan 
            FROM kegiatan_detail 
            WHERE jenis_kegiatan IS NOT NULL 
            AND jenis_kegiatan != ''
            ORDER BY jenis_kegiatan ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Insert kegiatan baru dengan kolom program, output, komponen.
     *
     * Catatan: kolom target/realisasi/progress sudah tidak lagi ada di tabel
     * kegiatan_detail — angka-angka itu dihitung on-the-fly dari kegiatan_petugas
     * (lihat dashboard & laporan). Jadi INSERT ini hanya menuliskan kolom yang
     * benar-benar ada di schema saat ini.
     */
    public function insert($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO kegiatan_detail
            (team_id, nama_kegiatan, master_id, jenis_kegiatan,
             rentang_waktu_mulai, rentang_waktu_selesai,
             satuan, komentar, program, output, komponen, honor_satuan,
             edited_by, penanggung_jawab_user_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $data['team_id'],
            $data['nama_kegiatan'],
            $data['master_id'] ?? null,
            $data['jenis_kegiatan'],
            $data['rentang_waktu_mulai'],
            $data['rentang_waktu_selesai'],
            $data['satuan'],
            $data['komentar'] ?? null,
            $data['program'] ?? null,
            $data['output'] ?? null,
            $data['komponen'] ?? null,
            $data['honor_satuan'] ?? null,
            $data['edited_by'],
            !empty($data['penanggung_jawab_user_id']) ? (int)$data['penanggung_jawab_user_id'] : null,
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update kegiatan dengan kolom program, output, komponen, honor_satuan
     */
    public function update($data)
    {
        $stmt = $this->db->prepare("
            UPDATE kegiatan_detail
            SET nama_kegiatan = ?,
                jenis_kegiatan = ?,
                rentang_waktu_mulai = ?,
                rentang_waktu_selesai = ?,
                satuan = ?,
                komentar = ?,
                program = ?,
                output = ?,
                komponen = ?,
                honor_satuan = ?,
                edited_by = ?,
                penanggung_jawab_user_id = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $data['nama_kegiatan'],
            $data['jenis_kegiatan'],
            $data['rentang_waktu_mulai'],
            $data['rentang_waktu_selesai'],
            $data['satuan'],
            $data['komentar'] ?? null,
            $data['program'] ?? null,
            $data['output'] ?? null,
            $data['komponen'] ?? null,
            $data['honor_satuan'] ?? null,
            $data['edited_by'],
            !empty($data['penanggung_jawab_user_id']) ? (int)$data['penanggung_jawab_user_id'] : null,
            $data['id']
        ]);
    }

    /**
     * Update target dan realisasi.
     *
     * Deprecated: kolom target/realisasi/progress sudah dihapus dari
     * kegiatan_detail. Nilai-nilai ini sekarang diturunkan langsung dari
     * kegiatan_petugas setiap kali ditampilkan. Method ini dibiarkan ada
     * (sebagai no-op) karena masih dipanggil via method_exists() di
     * Petugas_kegiatanController — menghapusnya akan memecah call-site
     * itu bila cache opcode belum di-reset.
     */
    public function updateTargetRealisasi($id, $target, $realisasi)
    {
        // Intentionally empty.
        return;
    }

    /**
     * Delete kegiatan
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM kegiatan_detail WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * Get filtered kegiatan.
     *
     * Satu kegiatan sekarang bisa punya beberapa jenis (disimpan di
     * kegiatan_jenis). Supaya listing menampilkan **satu baris per jenis**,
     * query di-LEFT-JOIN ke kegiatan_jenis. LEFT JOIN penting supaya kegiatan
     * lama yang tidak punya row kegiatan_jenis tetap ikut tampil (satu baris
     * seperti sebelumnya).
     *
     * Kolom tanggal/satuan/program/dll diambil dari per-jenis jika ada,
     * dan jatuh-balik ke nilai agregat di kegiatan_detail untuk data legacy.
     */
    public function getFiltered($teamId = null, $roleId = null, $bulan = null, $jenis = null)
    {
        $sql = "
            SELECT
                kd.id,
                kj.id                                                 AS kegiatan_jenis_id,
                kd.team_id,
                t.name                                                AS team_name,
                kd.nama_kegiatan,
                COALESCE(kj.jenis,           kd.jenis_kegiatan)       AS jenis_kegiatan,
                COALESCE(kj.tanggal_mulai,   kd.rentang_waktu_mulai)  AS rentang_waktu_mulai,
                COALESCE(kj.tanggal_selesai, kd.rentang_waktu_selesai) AS rentang_waktu_selesai,
                COALESCE(kj.satuan,          kd.satuan)               AS satuan,
                COALESCE(kj.program,         kd.program)              AS program,
                COALESCE(kj.output,          kd.output)               AS output,
                COALESCE(kj.komponen,        kd.komponen)             AS komponen,
                COALESCE(kj.honor_satuan,    kd.honor_satuan)         AS honor_satuan,
                kd.komentar,
                kd.edited_by,
                kd.penanggung_jawab_user_id,
                kd.created_at,
                kd.updated_at
            FROM kegiatan_detail kd
            LEFT JOIN kegiatan_jenis kj ON kj.kegiatan_id = kd.id
            LEFT JOIN teams t ON t.id = kd.team_id
            WHERE 1=1
        ";
        $params = [];

        if ($teamId && $teamId != '') {
            $sql .= " AND kd.team_id = ?";
            $params[] = $teamId;
        }

        if ($bulan) {
            // Filter bulan pada tanggal mulai per-jenis (fallback ke rentang kegiatan)
            $sql .= " AND DATE_FORMAT(COALESCE(kj.tanggal_mulai, kd.rentang_waktu_mulai), '%Y-%m') = ?";
            $params[] = $bulan;
        }

        if ($jenis !== null && $jenis !== '') {
            $valid = ['Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan'];
            if ($jenis === 'other') {
                // Semua selain 4 jenis baku
                $placeholders = implode(',', array_fill(0, count($valid), '?'));
                $sql .= " AND COALESCE(kj.jenis, kd.jenis_kegiatan) NOT IN ({$placeholders})";
                foreach ($valid as $v) { $params[] = $v; }
            } else {
                $sql .= " AND COALESCE(kj.jenis, kd.jenis_kegiatan) = ?";
                $params[] = $jenis;
            }
        }

        // Urutkan terbaru dulu, lalu urutan alami jenis di setiap kegiatan
        $sql .= " ORDER BY kd.id DESC,
                  FIELD(kj.jenis,'Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all teams
     */
    public function getAllTeams()
    {
        $stmt = $this->db->query("SELECT id, name FROM teams ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get upcoming deadlines
     */
    public function getUpcomingDeadlines($teamId = null, $daysThreshold = 7)
    {
        $sql = "
            SELECT kd.*, t.name AS team_name,
                DATEDIFF(kd.rentang_waktu_selesai, NOW()) as days_remaining,
                CASE 
                    WHEN DATEDIFF(kd.rentang_waktu_selesai, NOW()) <= 0 THEN 'overdue'
                    WHEN DATEDIFF(kd.rentang_waktu_selesai, NOW()) <= 3 THEN 'critical'
                    WHEN DATEDIFF(kd.rentang_waktu_selesai, NOW()) <= 7 THEN 'warning'
                    ELSE 'normal'
                END as urgency_level
            FROM kegiatan_detail kd
            LEFT JOIN teams t ON t.id = kd.team_id
            WHERE DATEDIFF(kd.rentang_waktu_selesai, NOW()) <= ?
            AND DATEDIFF(kd.rentang_waktu_selesai, NOW()) >= -7
        ";
        
        $params = [$daysThreshold];
        
        if ($teamId) {
            $sql .= " AND kd.team_id = ?";
            $params[] = $teamId;
        }
        
        $sql .= " ORDER BY kd.rentang_waktu_selesai ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Sync dari petugas
     */
    public function syncFromPetugas($kegiatan_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(target), 0) as total_target,
                COALESCE(SUM(realisasi), 0) as total_realisasi
            FROM kegiatan_petugas 
            WHERE kegiatan_detail_id = ? AND peran = 'PML'
        ");
        $stmt->execute([$kegiatan_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $target = (int)($result['total_target'] ?? 0);
        $realisasi = (int)($result['total_realisasi'] ?? 0);
        
        $this->updateTargetRealisasi($kegiatan_id, $target, $realisasi);
    }

    /**
     * Get maksud perjalanan dinas (jenis + nama kegiatan)
     */
    public function getMaksudPerjalanan($id)
    {
        $kegiatan = $this->getById($id);
        if (!$kegiatan) return '-';
        
        $jenis = $kegiatan['jenis_kegiatan'] ?? '';
        $nama = $kegiatan['nama_kegiatan'] ?? '';
        
        return trim("$jenis $nama");
    }
}