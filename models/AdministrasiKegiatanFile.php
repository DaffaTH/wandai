<?php
/*
 * WANDAI System - AdministrasiKegiatanFile Model
 *
 * Dokumen-dokumen yang diupload di tab "Kegiatan" pada halaman Input Administrasi.
 * Relasi ke kegiatan_jenis (satu-ke-banyak: 1 jenis bisa punya banyak dokumen).
 */

class AdministrasiKegiatanFile
{
    public $db;

    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }

    /** Insert dokumen baru. Return insert id. */
    public function insert(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO administrasi_kegiatan_files
                (kegiatan_jenis_id, jenis_dokumen, innas_user_id,
                 nama_file, file_path, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['kegiatan_jenis_id'],
            $data['jenis_dokumen'],
            $data['innas_user_id'] ?? null,
            $data['nama_file'],
            $data['file_path'],
            $data['uploaded_by'] ?? null,
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Replace file: hapus record lama yang match (kegiatan_jenis_id, jenis_dokumen,
     * innas_user_id) lalu insert baru. innas_user_id boleh NULL.
     */
    public function replace(array $data)
    {
        $sql = "DELETE FROM administrasi_kegiatan_files
                WHERE kegiatan_jenis_id = ? AND jenis_dokumen = ? AND ";
        $params = [$data['kegiatan_jenis_id'], $data['jenis_dokumen']];
        if (isset($data['innas_user_id']) && $data['innas_user_id'] !== null) {
            $sql .= "innas_user_id = ?";
            $params[] = $data['innas_user_id'];
        } else {
            $sql .= "innas_user_id IS NULL";
        }
        // Ambil record lama untuk hapus file fisik setelah DB delete
        $stmtSel = $this->db->prepare(str_replace('DELETE', 'SELECT file_path', $sql));
        $stmtSel->execute($params);
        $oldPaths = $stmtSel->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        foreach ($oldPaths as $p) {
            $abs = __DIR__ . '/../' . ltrim($p, '/');
            if ($p && file_exists($abs)) @unlink($abs);
        }

        return $this->insert($data);
    }

    /** Ambil semua file untuk satu kegiatan_jenis_id. */
    public function getByKegiatanJenis($kegiatan_jenis_id)
    {
        $stmt = $this->db->prepare("
            SELECT akf.*, u.name AS innas_name
            FROM administrasi_kegiatan_files akf
            LEFT JOIN users u ON u.id = akf.innas_user_id
            WHERE akf.kegiatan_jenis_id = ?
            ORDER BY akf.jenis_dokumen, akf.innas_user_id
        ");
        $stmt->execute([$kegiatan_jenis_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Map ter-kelompok: [jenis_dokumen => [row, ...] ] atau [jenis_dokumen => row]
     * bila tidak ada innas. Memudahkan view cek ada/tidak.
     */
    public function getMapByKegiatanJenis($kegiatan_jenis_id)
    {
        $rows = $this->getByKegiatanJenis($kegiatan_jenis_id);
        $map = [];
        foreach ($rows as $r) {
            $jd = $r['jenis_dokumen'];
            if ($r['innas_user_id']) {
                if (!isset($map[$jd])) $map[$jd] = [];
                $map[$jd][$r['innas_user_id']] = $r;
            } else {
                $map[$jd] = $r;
            }
        }
        return $map;
    }

    /** Cek apakah jenis dokumen tertentu sudah terisi untuk kegiatan_jenis. */
    public function exists($kegiatan_jenis_id, $jenis_dokumen)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM administrasi_kegiatan_files
            WHERE kegiatan_jenis_id = ? AND jenis_dokumen = ?
        ");
        $stmt->execute([$kegiatan_jenis_id, $jenis_dokumen]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("SELECT file_path FROM administrasi_kegiatan_files WHERE id=?");
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();

        $stmt = $this->db->prepare("DELETE FROM administrasi_kegiatan_files WHERE id=?");
        $stmt->execute([$id]);

        if ($path) {
            $abs = __DIR__ . '/../' . ltrim($path, '/');
            if (file_exists($abs)) @unlink($abs);
        }
    }
}
