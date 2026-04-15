<?php
/*
 * WANDAI System - User Model
 * BPS Kabupaten Paniai
 * 
 * UPDATED: 
 * - Kolom: gelar_depan, gelar_belakang, pangkat, golongan, jabatan
 * - Username -> email (unique)
 * - Method getPPK() dan getKepala() dengan data lengkap
 * - Method getAllWithTeamAndRole() untuk halaman Anggota Tim
 * - Method insert(), update(), delete() untuk TimController
 */

class User
{
    private $db;

    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }

    /**
     * Get all users with team and role information
     * Digunakan di halaman Anggota Tim (TimController).
     *
     * team_names   → nama tim (|| separator) dari tabel user_teams.
     * team_ids     → list id tim (CSV) untuk modal edit.
     * team_warnas  → list warna hex (|| separator) searah dengan team_names,
     *                agar badge di kolom Tim bisa memakai warna sama seperti
     *                warna blok di Dashboard Matriks.
     *
     * Urut: Admin → Kepala → Kasubbag → PPK → Bendahara → Operator → lainnya, lalu nama.
     */
    public function getAllWithTeamAndRole()
    {
        $sql = "SELECT
                    u.*,
                    u.email AS username,
                    CONCAT(
                        IFNULL(CONCAT(u.gelar_depan, ' '), ''),
                        u.name,
                        IFNULL(CONCAT(', ', u.gelar_belakang), '')
                    ) AS nama_lengkap,
                    r.name AS role_name,
                    t.name AS team_name,
                    (
                        SELECT GROUP_CONCAT(tm.name ORDER BY tm.name SEPARATOR '||')
                        FROM user_teams ut
                        JOIN teams tm ON tm.id = ut.team_id
                        WHERE ut.user_id = u.id
                    ) AS team_names,
                    (
                        SELECT GROUP_CONCAT(COALESCE(tm.warna,'#6c757d') ORDER BY tm.name SEPARATOR '||')
                        FROM user_teams ut
                        JOIN teams tm ON tm.id = ut.team_id
                        WHERE ut.user_id = u.id
                    ) AS team_warnas,
                    (
                        SELECT GROUP_CONCAT(ut.team_id ORDER BY ut.team_id SEPARATOR ',')
                        FROM user_teams ut
                        WHERE ut.user_id = u.id
                    ) AS team_ids
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN teams t ON t.id = u.team_id
                ORDER BY
                  CASE LOWER(COALESCE(r.name,''))
                    WHEN 'admin'     THEN 1
                    WHEN 'kepala'    THEN 2
                    WHEN 'kasubbag'  THEN 3
                    WHEN 'ppk'       THEN 4
                    WHEN 'bendahara' THEN 5
                    WHEN 'operator'  THEN 6
                    ELSE 7
                  END,
                  u.name";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by ID dengan data lengkap
     */
    public function getById($id)
    {
        $sql = "SELECT 
                    u.*,
                    CONCAT(
                        IFNULL(CONCAT(u.gelar_depan, ' '), ''),
                        u.name,
                        IFNULL(CONCAT(', ', u.gelar_belakang), '')
                    ) AS nama_lengkap,
                    r.name AS role_name,
                    t.name AS team_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN teams t ON t.id = u.team_id
                WHERE u.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by email (untuk login)
     */
    public function getByEmail($email)
    {
        $sql = "SELECT 
                    u.*,
                    u.id AS user_id,
                    r.name AS role_name,
                    t.name AS team_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN teams t ON t.id = u.team_id
                WHERE u.email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get PPK (role_id = 4) dengan data lengkap untuk dokumen
     */
    public function getPPK()
    {
        $sql = "SELECT
                    u.id,
                    u.name,
                    u.gelar_depan,
                    u.gelar_belakang,
                    CONCAT(
                        IFNULL(CONCAT(u.gelar_depan, ' '), ''),
                        u.name,
                        IFNULL(CONCAT(', ', u.gelar_belakang), '')
                    ) AS nama_lengkap,
                    u.nip,
                    u.pangkat,
                    u.golongan,
                    u.jabatan
                FROM users u
                WHERE u.role_id = 4
                ORDER BY u.id ASC
                LIMIT 1";

        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get Kepala (role_id = 2) dengan data lengkap untuk dokumen
     */
    public function getKepala()
    {
        $sql = "SELECT
                    u.id,
                    u.name,
                    u.gelar_depan,
                    u.gelar_belakang,
                    CONCAT(
                        IFNULL(CONCAT(u.gelar_depan, ' '), ''),
                        u.name,
                        IFNULL(CONCAT(', ', u.gelar_belakang), '')
                    ) AS nama_lengkap,
                    u.nip,
                    u.pangkat,
                    u.golongan,
                    u.jabatan
                FROM users u
                WHERE u.role_id = 2
                ORDER BY u.id ASC
                LIMIT 1";

        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all PPK
     */
    public function getAllPPK()
    {
        $sql = "SELECT
                    u.id,
                    u.name,
                    CONCAT(
                        IFNULL(CONCAT(u.gelar_depan, ' '), ''),
                        u.name,
                        IFNULL(CONCAT(', ', u.gelar_belakang), '')
                    ) AS nama_lengkap,
                    u.nip
                FROM users u
                WHERE u.role_id = 4
                ORDER BY u.name";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get pegawai aktif untuk dropdown (dengan email sebagai identifier)
     */
    public function getPegawaiAktif()
    {
        $sql = "SELECT 
                    u.id,
                    u.name,
                    CONCAT(
                        IFNULL(CONCAT(u.gelar_depan, ' '), ''),
                        u.name,
                        IFNULL(CONCAT(', ', u.gelar_belakang), '')
                    ) AS nama_lengkap,
                    u.nip,
                    u.pangkat,
                    u.golongan,
                    u.jabatan,
                    u.email,
                    u.role_id,
                    t.name AS team_name
                FROM users u
                LEFT JOIN teams t ON t.id = u.team_id
                WHERE u.role_id IN (2, 3, 4, 5, 6)
                ORDER BY
                  -- Urutan tampilan di dropdown petugas (mapping baru):
                  -- 1) Kepala  2) Kasubbag  3) PPK  4) Bendahara  5) Operator
                  CASE u.role_id
                    WHEN 2 THEN 1   -- Kepala
                    WHEN 3 THEN 2   -- Kasubbag
                    WHEN 4 THEN 3   -- PPK
                    WHEN 5 THEN 4   -- Bendahara
                    WHEN 6 THEN 5   -- Operator
                    ELSE 99
                  END,
                  u.name";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get pegawai by team
     */
    public function getByTeam($team_id)
    {
        $sql = "SELECT 
                    u.id,
                    u.name,
                    CONCAT(
                        IFNULL(CONCAT(u.gelar_depan, ' '), ''),
                        u.name,
                        IFNULL(CONCAT(', ', u.gelar_belakang), '')
                    ) AS nama_lengkap,
                    u.nip,
                    u.email
                FROM users u
                WHERE u.team_id = ?
                ORDER BY u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$team_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insert new user (untuk TimController)
     * Note: username disimpan sebagai email karena tidak ada kolom username di tabel
     *
     * $team_ids: array id tim (boleh kosong kalau role bukan operator).
     * Saat role bukan operator (role_id != 6), kolom users.team_id di-set NULL.
     */
    public function insert($name, $username, $password, $role_id, $team_ids = [])
    {
        // Normalisasi: hanya operator (role_id 6) yang punya tim.
        if ((int)$role_id !== 6) {
            $team_ids = [];
        } else {
            $team_ids = is_array($team_ids)
                ? array_values(array_unique(array_filter(array_map('intval', $team_ids))))
                : [];
        }

        $primaryTeamId = $team_ids[0] ?? null;

        $sql = "INSERT INTO users
                (name, email, password, role_id, team_id, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $name,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $role_id,
            $primaryTeamId
        ]);

        $newId = $this->db->lastInsertId();
        $this->syncUserTeams($newId, $team_ids);

        return $newId;
    }

    /**
     * Sinkron user_teams: hapus semua entry lama lalu insert ulang sesuai array.
     * Tim pertama dianggap primary.
     */
    public function syncUserTeams($userId, array $teamIds)
    {
        $userId = (int)$userId;
        $this->db->prepare("DELETE FROM user_teams WHERE user_id = ?")->execute([$userId]);

        if (empty($teamIds)) return;

        $stmt = $this->db->prepare(
            "INSERT INTO user_teams (user_id, team_id, is_primary) VALUES (?, ?, ?)"
        );
        $first = true;
        foreach ($teamIds as $tid) {
            $stmt->execute([$userId, (int)$tid, $first ? 1 : 0]);
            $first = false;
        }
    }

    /**
     * Create new user (dengan data lengkap)
     */
    public function create($data)
    {
        $sql = "INSERT INTO users 
                (name, gelar_depan, gelar_belakang, email, password, nip, pangkat, golongan, jabatan, role_id, team_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['gelar_depan'] ?? null,
            $data['gelar_belakang'] ?? null,
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['nip'] ?? null,
            $data['pangkat'] ?? null,
            $data['golongan'] ?? null,
            $data['jabatan'] ?? null,
            $data['role_id'],
            $data['team_id'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * Update user (untuk TimController - tanpa password)
     * Note: username disimpan sebagai email.
     *
     * $team_ids: array id tim (dicontrol oleh checkbox; kalau role bukan operator
     *            array akan diabaikan dan tim di-kosongkan).
     */
    public function update($id, $name, $username, $role_id, $team_ids = [])
    {
        // role_id 6 = Operator (mapping baru)
        if ((int)$role_id !== 6) {
            $team_ids = [];
        } else {
            $team_ids = is_array($team_ids)
                ? array_values(array_unique(array_filter(array_map('intval', $team_ids))))
                : [];
        }

        $primaryTeamId = $team_ids[0] ?? null;

        $sql = "UPDATE users SET
                name = ?,
                email = ?,
                role_id = ?,
                team_id = ?,
                updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $name,
            $username,
            $role_id,
            $primaryTeamId,
            $id
        ]);

        $this->syncUserTeams($id, $team_ids);
        return $ok;
    }

    /**
     * Update user (dengan data lengkap)
     */
    public function updateFull($id, $data)
    {
        $sql = "UPDATE users SET 
                name = ?,
                gelar_depan = ?,
                gelar_belakang = ?,
                email = ?,
                nip = ?,
                pangkat = ?,
                golongan = ?,
                jabatan = ?,
                role_id = ?,
                team_id = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['gelar_depan'] ?? null,
            $data['gelar_belakang'] ?? null,
            $data['email'],
            $data['nip'] ?? null,
            $data['pangkat'] ?? null,
            $data['golongan'] ?? null,
            $data['jabatan'] ?? null,
            $data['role_id'],
            $data['team_id'] ?? null,
            $id
        ]);
    }

    /**
     * Delete user (untuk TimController)
     */
    public function delete($id)
    {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Check email exists
     */
    public function emailExists($email, $excludeId = 0)
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email, $excludeId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get all users
     */
    public function getAll()
    {
        $sql = "SELECT 
                    u.*,
                    CONCAT(
                        IFNULL(CONCAT(u.gelar_depan, ' '), ''),
                        u.name,
                        IFNULL(CONCAT(', ', u.gelar_belakang), '')
                    ) AS nama_lengkap,
                    r.name AS role_name,
                    t.name AS team_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN teams t ON t.id = u.team_id
                ORDER BY u.name";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update password only
     */
    public function updatePassword($id, $newPassword)
    {
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
    }

    /**
     * Ambil daftar tim untuk satu user (dipakai di halaman Profile).
     * Output: array of [id, name, warna, is_primary], urut: primary dulu lalu nama.
     */
    public function getTeamsByUserId($userId)
    {
        $sql = "SELECT t.id, t.name, COALESCE(t.warna,'#6c757d') AS warna, ut.is_primary
                FROM user_teams ut
                JOIN teams t ON t.id = ut.team_id
                WHERE ut.user_id = ?
                ORDER BY ut.is_primary DESC, t.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update username (kolom email) untuk profile.
     * Tidak menyentuh role/team/data lain.
     */
    public function updateEmail($id, $newEmail)
    {
        $sql = "UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newEmail, $id]);
    }

    /**
     * Format NIP dengan spasi: 19840201 200801 1 010
     */
    public static function formatNIP($nip)
    {
        if (empty($nip)) return '-';
        $nip = preg_replace('/\D/', '', $nip);
        if (strlen($nip) == 18) {
            return substr($nip, 0, 8) . ' ' . substr($nip, 8, 6) . ' ' . substr($nip, 14, 1) . ' ' . substr($nip, 15, 3);
        }
        return $nip;
    }
}