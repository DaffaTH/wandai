<?php
/*
 * WANDAI System - Mitra Model
 * BPS Kabupaten Paniai
 * 
 * UPDATED: 
 * - Tambah kolom wilayah_kerja (Paniai/Intan Jaya/Deiyai)
 * - Username -> email (unique)
 */

class Mitra
{
    private $db;
    
    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }

    /**
     * List semua mitra
     */
    public function listAll()
    {
        try {
            $sql = "SELECT id, nama, nik, alamat, wilayah_kerja, email, status, created_at 
                    FROM mitra 
                    ORDER BY id DESC";
            
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Mitra listAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get mitra by ID
     */
    public function getById(int $id)
    {
        try {
            $sql = "SELECT id, nama, nik, alamat, wilayah_kerja, email, status FROM mitra WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Mitra getById error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get mitra by email
     */
    public function getByEmail(string $email)
    {
        try {
            $sql = "SELECT id, nama, nik, alamat, wilayah_kerja, email, password, status FROM mitra WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Mitra getByEmail error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get mitra by nama
     */
    public function getByNama(string $nama)
    {
        try {
            $sql = "SELECT id, nama, nik, alamat, wilayah_kerja, email, status FROM mitra WHERE nama = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nama]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Mitra getByNama error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create mitra baru
     */
    public function create(array $data)
    {
        try {
            $sql = "INSERT INTO mitra (nama, nik, alamat, wilayah_kerja, email, password, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'aktif', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['nama'],
                $data['nik'],
                $data['alamat'],
                $data['wilayah_kerja'] ?? 'Paniai',
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT)
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Mitra create error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update mitra
     */
    public function update(int $id, array $data)
    {
        try {
            if (empty($data['password'])) {
                $sql = "UPDATE mitra SET nama = ?, nik = ?, alamat = ?, wilayah_kerja = ?, email = ?, status = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $data['nama'],
                    $data['nik'],
                    $data['alamat'],
                    $data['wilayah_kerja'] ?? 'Paniai',
                    $data['email'],
                    $data['status'],
                    $id
                ]);
            } else {
                $sql = "UPDATE mitra SET nama = ?, nik = ?, alamat = ?, wilayah_kerja = ?, email = ?, password = ?, status = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $data['nama'],
                    $data['nik'],
                    $data['alamat'],
                    $data['wilayah_kerja'] ?? 'Paniai',
                    $data['email'],
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    $data['status'],
                    $id
                ]);
            }
        } catch (PDOException $e) {
            error_log("Mitra update error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete mitra
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM mitra WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Mitra delete error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check email exists
     */
    public function emailExists(string $email, int $excludeId = 0)
    {
        try {
            $sql = "SELECT COUNT(*) FROM mitra WHERE email = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email, $excludeId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Mitra emailExists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get mitra aktif untuk dropdown (dengan email sebagai identifier)
     */
    public function getAktif()
    {
        try {
            $sql = "SELECT id, nama, nik, email, wilayah_kerja FROM mitra WHERE status = 'aktif' ORDER BY nama";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Mitra getAktif error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get mitra by wilayah kerja
     */
    public function getByWilayah($wilayah)
    {
        try {
            $sql = "SELECT id, nama, nik, email, alamat FROM mitra WHERE wilayah_kerja = ? AND status = 'aktif' ORDER BY nama";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$wilayah]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Mitra getByWilayah error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Bulk insert mitra dari import Excel
     */
    public function bulkInsert(array $dataArray)
    {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO mitra (nama, email, nik, alamat, wilayah_kerja, password, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            
            $inserted = 0;
            foreach ($dataArray as $data) {
                $stmt->execute([
                    $data['nama'],
                    $data['email'],
                    $data['nik'],
                    $data['alamat'],
                    $data['wilayah_kerja'] ?? 'Paniai',
                    password_hash($data['password'] ?? '9502', PASSWORD_DEFAULT),
                    $data['status'] ?? 'aktif'
                ]);
                $inserted++;
            }
            
            $this->db->commit();
            return $inserted;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Mitra bulkInsert error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check bulk email untuk validasi import
     */
    public function checkBulkEmail(array $emails)
    {
        try {
            if (empty($emails)) return [];
            
            $placeholders = str_repeat('?,', count($emails) - 1) . '?';
            $sql = "SELECT email FROM mitra WHERE email IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($emails);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Mitra checkBulkEmail error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validasi data import sebelum insert
     */
    public function validateImportData(array $dataArray)
    {
        $errors = [];
        $warnings = [];
        $validData = [];
        
        $emails = array_column($dataArray, 'email');
        $existingEmails = $this->checkBulkEmail($emails);
        $emailCount = array_count_values($emails);
        
        $validWilayah = ['Paniai', 'Intan Jaya', 'Deiyai'];
        
        foreach ($dataArray as $index => $data) {
            $rowErrors = [];
            $rowWarnings = [];
            
            if (empty(trim($data['nama'] ?? ''))) {
                $rowErrors[] = 'Nama wajib diisi';
            }
            
            if (empty(trim($data['email'] ?? ''))) {
                $rowErrors[] = 'Email wajib diisi';
            } else {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'Format email tidak valid';
                }
                if (in_array($data['email'], $existingEmails)) {
                    $rowErrors[] = 'Email sudah ada di database';
                }
                if ($emailCount[$data['email']] > 1) {
                    $rowErrors[] = 'Email duplikasi dalam file';
                }
            }
            
            $nik = trim($data['nik'] ?? '');
            if (empty($nik)) {
                $rowErrors[] = 'NIK wajib diisi';
            } elseif (!preg_match('/^\d{16}$/', $nik)) {
                $rowErrors[] = 'NIK harus 16 digit angka';
            }
            
            if (empty(trim($data['alamat'] ?? ''))) {
                $rowErrors[] = 'Alamat wajib diisi';
            }
            
            $wilayah = ucwords(strtolower(trim($data['wilayah_kerja'] ?? 'Paniai')));
            if (!in_array($wilayah, $validWilayah)) {
                $data['wilayah_kerja'] = 'Paniai';
                $rowWarnings[] = 'Wilayah kerja tidak valid, diset default: Paniai';
            } else {
                $data['wilayah_kerja'] = $wilayah;
            }
            
            if (empty(trim($data['password'] ?? ''))) {
                $data['password'] = '9502';
                $rowWarnings[] = 'Password kosong, diset default: 9502';
            }
            
            $status = strtolower(trim($data['status'] ?? 'aktif'));
            $data['status'] = in_array($status, ['aktif', 'nonaktif']) ? $status : 'aktif';
            
            if (!empty($rowErrors)) {
                $errors[$index + 2] = $rowErrors;
            } else {
                $validData[] = $data;
            }
            
            if (!empty($rowWarnings)) {
                $warnings[$index + 2] = $rowWarnings;
            }
        }
        
        return [
            'valid_data' => $validData,
            'errors' => $errors,
            'warnings' => $warnings,
            'total_rows' => count($dataArray),
            'valid_rows' => count($validData),
            'error_rows' => count($errors)
        ];
    }

    /**
     * Export semua data mitra
     */
    public function exportAll()
    {
        try {
            $sql = "SELECT nama, email, nik, alamat, wilayah_kerja, status FROM mitra ORDER BY nama";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Mitra exportAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get daftar wilayah kerja
     */
    public static function getWilayahList()
    {
        return ['Paniai', 'Intan Jaya', 'Deiyai'];
    }

    /**
     * Change status mitra
     */
    public function changeStatus(int $id, string $status)
    {
        try {
            $sql = "UPDATE mitra SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            error_log("Mitra changeStatus error: " . $e->getMessage());
            throw $e;
        }
    }
}