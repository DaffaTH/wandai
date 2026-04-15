<?php
/*
 * WANDAI System - KegiatanJenis Model
 *
 * 1 kegiatan_detail punya banyak row di kegiatan_jenis (per jenis kegiatan).
 */

class KegiatanJenis
{
    public $db;

    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }

    /** Insert satu baris jenis. Return lastInsertId. */
    public function insert(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO kegiatan_jenis
                (kegiatan_id, jenis, satuan, tanggal_mulai, tanggal_selesai,
                 program, output, komponen, honor_satuan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                satuan = VALUES(satuan),
                tanggal_mulai = VALUES(tanggal_mulai),
                tanggal_selesai = VALUES(tanggal_selesai),
                program = VALUES(program),
                output = VALUES(output),
                komponen = VALUES(komponen),
                honor_satuan = VALUES(honor_satuan)
        ");
        $stmt->execute([
            $data['kegiatan_id'],
            $data['jenis'],
            $data['satuan'] ?? null,
            $data['tanggal_mulai'] ?? null,
            $data['tanggal_selesai'] ?? null,
            $data['program'] ?? null,
            $data['output'] ?? null,
            $data['komponen'] ?? null,
            $data['honor_satuan'] ?? 0,
        ]);
        // lastInsertId kosong kalau hit UPDATE -> ambil row-nya
        $id = $this->db->lastInsertId();
        if (!$id) {
            $stmt = $this->db->prepare("SELECT id FROM kegiatan_jenis WHERE kegiatan_id=? AND jenis=?");
            $stmt->execute([$data['kegiatan_id'], $data['jenis']]);
            $id = $stmt->fetchColumn();
        }
        return (int)$id;
    }

    /** Hapus semua jenis milik kegiatan tertentu. */
    public function deleteByKegiatan($kegiatan_id)
    {
        $stmt = $this->db->prepare("DELETE FROM kegiatan_jenis WHERE kegiatan_id = ?");
        $stmt->execute([$kegiatan_id]);
    }

    /** Ambil semua jenis milik satu kegiatan. */
    public function getByKegiatan($kegiatan_id)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM kegiatan_jenis
            WHERE kegiatan_id = ?
            ORDER BY FIELD(jenis,'Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan')
        ");
        $stmt->execute([$kegiatan_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ambil satu jenis by id. */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT kj.*, kd.nama_kegiatan, kd.team_id, t.name AS team_name
            FROM kegiatan_jenis kj
            JOIN kegiatan_detail kd ON kd.id = kj.kegiatan_id
            LEFT JOIN teams t ON t.id = kd.team_id
            WHERE kj.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Ambil SEMUA row (kegiatan × jenis) — untuk halaman Data Administrasi. */
    public function getAllRows($teamId = null)
    {
        $sql = "
            SELECT kj.*, kd.nama_kegiatan, kd.team_id,
                   t.name AS team_name, t.warna AS team_warna
            FROM kegiatan_jenis kj
            JOIN kegiatan_detail kd ON kd.id = kj.kegiatan_id
            LEFT JOIN teams t ON t.id = kd.team_id
        ";
        $params = [];
        if ($teamId) {
            $sql .= " WHERE kd.team_id = ?";
            $params[] = $teamId;
        }
        $sql .= " ORDER BY kd.id DESC,
                  FIELD(kj.jenis,'Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
