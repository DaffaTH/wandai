<?php
/*
 * WANDAI System - KegiatanInnas Model
 *
 * Menyimpan Inda/Innas (instruktur) untuk kegiatan Pelatihan/Briefing.
 * 1 row = 1 innas per kegiatan_jenis.
 */

class KegiatanInnas
{
    public $db;

    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }

    /** Tambah satu innas. */
    public function insert($kegiatan_jenis_id, $user_id)
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO kegiatan_innas (kegiatan_jenis_id, user_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$kegiatan_jenis_id, $user_id]);
        return $this->db->lastInsertId();
    }

    /** Sinkron: hapus semua innas kegiatan_jenis ini, lalu insert ulang dari array. */
    public function sync($kegiatan_jenis_id, array $user_ids)
    {
        $this->deleteByKegiatanJenis($kegiatan_jenis_id);
        foreach (array_unique($user_ids) as $uid) {
            if ($uid) $this->insert($kegiatan_jenis_id, (int)$uid);
        }
    }

    public function deleteByKegiatanJenis($kegiatan_jenis_id)
    {
        $stmt = $this->db->prepare("DELETE FROM kegiatan_innas WHERE kegiatan_jenis_id = ?");
        $stmt->execute([$kegiatan_jenis_id]);
    }

    /** Ambil daftar innas (dengan nama user) untuk satu kegiatan_jenis. */
    public function getByKegiatanJenis($kegiatan_jenis_id)
    {
        $stmt = $this->db->prepare("
            SELECT ki.*, u.name AS user_name, u.nip
            FROM kegiatan_innas ki
            JOIN users u ON u.id = ki.user_id
            WHERE ki.kegiatan_jenis_id = ?
            ORDER BY u.name
        ");
        $stmt->execute([$kegiatan_jenis_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ambil anggota tim (users WHERE team_id = :tim_id) untuk dropdown innas. */
    public function getTeamMembers($team_id)
    {
        // Defensif: kolom `status` mungkin belum ada di DB lama
        $hasStatus = false;
        try {
            $hasStatus = (bool)$this->db->query("SHOW COLUMNS FROM users LIKE 'status'")->fetch();
        } catch (Exception $e) { /* ignore */ }

        $statusFilter = $hasStatus ? "AND (status IS NULL OR status = 'aktif')" : '';

        $stmt = $this->db->prepare("
            SELECT id, name, nip
            FROM users
            WHERE team_id = ? {$statusFilter}
            ORDER BY name
        ");
        $stmt->execute([$team_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
