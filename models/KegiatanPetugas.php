<?php
/*
 * WANDAI System - KegiatanPetugas Model
 * BPS Kabupaten Paniai
 * 
 * UPDATED: 
 * - Tambah kolom alat_angkutan untuk SPPD
 * - Menggunakan email sebagai identifier di import/export
 * - Data lengkap untuk dokumen (wilayah_kerja, pangkat, golongan, jabatan)
 * - Tambah pml_nama di listByTeam untuk menampilkan nama PML di kolom Struktur
 */

class KegiatanPetugas
{
    public $db;

    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }

    /**
     * List penugasan petugas dengan data lengkap
     */
    public function listByTeam($team_id = null, $kegiatan_id = null)
    {
        $sql = "SELECT 
                    kp.id,
                    kp.kegiatan_detail_id,
                    kp.petugas_id,
                    kp.petugas_source,
                    kp.peran,
                    kp.realisasi,
                    kp.target,
                    kp.keterangan,
                    kp.pml_id,
                    kp.created_at,
                    kp.updated_at,
                    kp.asal,
                    kp.tujuan,
                    kp.alat_angkutan,
                    kp.no_bast,
                    kp.no_spk,
                    kp.no_surat_tugas,
                    kp.no_sppd,
                    kp.tanggal_surat,
                    kp.periode_mulai,
                    kp.periode_selesai,
                    kd.nama_kegiatan,
                    kd.jenis_kegiatan,
                    kd.satuan,
                    kd.team_id as kegiatan_team_id,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                        ELSE 'Unknown'
                    END AS petugas_nama,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.email
                        WHEN kp.petugas_source = 'mitra' THEN m.email
                    END AS petugas_email,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN 'Pegawai'
                        WHEN kp.petugas_source = 'mitra' THEN 'Mitra'
                        ELSE 'Unknown'
                    END AS petugas_jenis,
                    m.wilayah_kerja,
                    spk.id as spk_id,
                    bast.id as bast_id,
                    surtug.id as surtug_id,
                    sppd.id as sppd_id,
                    -- PML nama: ambil dari kegiatan_petugas PML lalu JOIN ke users/mitra
                    CASE 
                        WHEN pml_kp.petugas_source = 'users' THEN pml_u.name
                        WHEN pml_kp.petugas_source = 'mitra' THEN pml_m.nama
                        ELSE NULL
                    END AS pml_nama,
                    -- Honor per satuan dari kegiatan_detail untuk kalkulasi honorarium
                    COALESCE(kd.honor_satuan, 0) AS honor_satuan
                FROM kegiatan_petugas kp
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
                LEFT JOIN dokumen_kontrak spk ON spk.kegiatan_petugas_id = kp.id AND spk.jenis_dokumen = 'spk'
                LEFT JOIN dokumen_kontrak bast ON bast.kegiatan_petugas_id = kp.id AND bast.jenis_dokumen = 'bast'
                LEFT JOIN dokumen_kontrak surtug ON surtug.kegiatan_petugas_id = kp.id AND surtug.jenis_dokumen = 'surat_tugas'
                LEFT JOIN dokumen_kontrak sppd ON sppd.kegiatan_petugas_id = kp.id AND sppd.jenis_dokumen = 'sppd'
                -- JOIN untuk mendapatkan nama PML
                LEFT JOIN kegiatan_petugas pml_kp ON pml_kp.id = kp.pml_id
                LEFT JOIN users pml_u ON pml_u.id = pml_kp.petugas_id AND pml_kp.petugas_source = 'users'
                LEFT JOIN mitra pml_m ON pml_m.id = pml_kp.petugas_id AND pml_kp.petugas_source = 'mitra'
                WHERE 1=1";
        
        $params = [];

        if ($team_id !== null) {
            $sql .= " AND kd.team_id = ?";
            $params[] = $team_id;
        }

        if (!empty($kegiatan_id)) {
            $sql .= " AND kp.kegiatan_detail_id = ?";
            $params[] = $kegiatan_id;
        }

        /**
     * ORDER BY: Kegiatan, lalu PML diikuti PPL-nya (PPL di bawah PML)
     */

        $sql .= " ORDER BY kd.nama_kegiatan ASC, COALESCE(kp.pml_id, kp.id) ASC, kp.peran DESC, kp.id ASC";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get petugas by ID dengan data lengkap untuk dokumen
     */
    public function getById($id)
    {
        $sql = "SELECT 
                    kp.*,
                    kd.nama_kegiatan,
                    kd.jenis_kegiatan,
                    kd.satuan,
                    kd.program,
                    kd.output,
                    kd.komponen,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                    END AS petugas_nama,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN 
                            CONCAT(IFNULL(CONCAT(u.gelar_depan, ' '), ''), u.name, IFNULL(CONCAT(', ', u.gelar_belakang), ''))
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                    END AS petugas_nama_lengkap,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.nip
                        ELSE NULL
                    END AS petugas_nip,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.pangkat
                        ELSE NULL
                    END AS petugas_pangkat,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.golongan
                        ELSE NULL
                    END AS petugas_golongan,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.jabatan
                        ELSE NULL
                    END AS petugas_jabatan,
                    m.nik as mitra_nik,
                    m.alamat as mitra_alamat,
                    m.wilayah_kerja as mitra_wilayah
                FROM kegiatan_petugas kp
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
                WHERE kp.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Add petugas baru dengan kolom alat_angkutan
     */
    public function add(array $data)
    {
        $sql = "INSERT INTO kegiatan_petugas
                (kegiatan_detail_id, petugas_id, petugas_source, peran, realisasi, target, keterangan, pml_id,
                 asal, tujuan, alat_angkutan, no_bast, no_spk, no_surat_tugas, no_sppd, tanggal_surat, periode_mulai, periode_selesai)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $data['kegiatan_detail_id'],
            $data['petugas_id'],
            $data['petugas_source'],
            $data['peran'],
            $data['realisasi'] ?? 0,
            $data['target'] ?? 0,
            $data['keterangan'] ?? null,
            $data['pml_id'] ?? null,
            $data['asal'] ?? 'Enarotali',
            $data['tujuan'] ?? '',
            $data['alat_angkutan'] ?? 'Kendaraan Umum',
            $data['no_bast'] ?? '',
            $data['no_spk'] ?? '',
            $data['no_surat_tugas'] ?? '',
            $data['no_sppd'] ?? '',
            $data['tanggal_surat'] ?? null,
            $data['periode_mulai'] ?? null,
            $data['periode_selesai'] ?? null
        ]);
        
        if ($success) {
            return (int)$this->db->lastInsertId();
        }
        return 0;
    }

    /**
     * Update petugas
     */
    public function update(array $data)
    {
        $sql = "UPDATE kegiatan_petugas SET 
                peran = ?, realisasi = ?, target = ?, keterangan = ?,
                asal = ?, tujuan = ?, alat_angkutan = ?,
                no_bast = ?, no_spk = ?, no_surat_tugas = ?, no_sppd = ?,
                tanggal_surat = ?, periode_mulai = ?, periode_selesai = ?,
                updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['peran'],
            $data['realisasi'],
            $data['target'],
            $data['keterangan'] ?? '',
            $data['asal'] ?? 'Enarotali',
            $data['tujuan'] ?? '',
            $data['alat_angkutan'] ?? 'Kendaraan Umum',
            $data['no_bast'] ?? '',
            $data['no_spk'] ?? '',
            $data['no_surat_tugas'] ?? '',
            $data['no_sppd'] ?? '',
            $data['tanggal_surat'] ?? null,
            $data['periode_mulai'] ?? null,
            $data['periode_selesai'] ?? null,
            $data['id']
        ]);
    }

    /**
     * Delete petugas
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM kegiatan_petugas WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * Get petugas by email (untuk import Excel)
     */
    public function getPetugasByEmail($email)
    {
        // Cek di users dulu
        $stmt = $this->db->prepare("SELECT id, 'users' as source, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) return $result;
        
        // Cek di mitra
        $stmt = $this->db->prepare("SELECT id, 'mitra' as source, nama as name FROM mitra WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get mitra untuk bulk generate
     */
    public function getMitraForBulkGenerate($kegiatan_id, $jenis_dokumen)
    {
        $sql = "SELECT 
                    kp.id,
                    kp.petugas_id,
                    kp.petugas_source,
                    kp.peran,
                    kp.realisasi,
                    kp.target,
                    kp.asal,
                    kp.tujuan,
                    kp.alat_angkutan,
                    kp.no_spk,
                    kp.no_bast,
                    kp.no_surat_tugas,
                    kp.no_sppd,
                    kp.tanggal_surat,
                    kp.periode_mulai,
                    kp.periode_selesai,
                    kd.nama_kegiatan,
                    kd.jenis_kegiatan,
                    kd.satuan,
                    kd.program,
                    kd.output,
                    kd.komponen,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                    END AS petugas_nama,
                    m.nik as mitra_nik,
                    m.alamat as mitra_alamat,
                    m.wilayah_kerja as mitra_wilayah
                FROM kegiatan_petugas kp
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
                LEFT JOIN dokumen_kontrak dk ON dk.kegiatan_petugas_id = kp.id AND dk.jenis_dokumen = ?
                WHERE kp.kegiatan_detail_id = ?
                AND dk.id IS NULL
                ORDER BY kp.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$jenis_dokumen, $kegiatan_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validasi data untuk generate dokumen
     */
    public function validateForGenerate($kegiatan_id, $jenis_dokumen)
    {
        $mitraList = $this->getMitraForBulkGenerate($kegiatan_id, $jenis_dokumen);
        
        $valid = [];
        $errors = [];
        
        foreach ($mitraList as $mitra) {
            $rowErrors = [];
            
            switch ($jenis_dokumen) {
                case 'spk':
                    if (empty($mitra['no_spk'])) $rowErrors[] = 'Nomor SPK kosong';
                    if (empty($mitra['tanggal_surat'])) $rowErrors[] = 'Tanggal surat kosong';
                    if (empty($mitra['periode_mulai'])) $rowErrors[] = 'Periode mulai kosong';
                    if (empty($mitra['periode_selesai'])) $rowErrors[] = 'Periode selesai kosong';
                    break;
                    
                case 'bast':
                    if (empty($mitra['no_bast'])) $rowErrors[] = 'Nomor BAST kosong';
                    if (empty($mitra['tanggal_surat'])) $rowErrors[] = 'Tanggal surat kosong';
                    if ($mitra['realisasi'] <= 0) $rowErrors[] = 'Realisasi harus > 0';
                    break;
                    
                case 'surat_tugas':
                    if (empty($mitra['no_surat_tugas'])) $rowErrors[] = 'Nomor Surat Tugas kosong';
                    if (empty($mitra['tanggal_surat'])) $rowErrors[] = 'Tanggal surat kosong';
                    if (empty($mitra['periode_mulai'])) $rowErrors[] = 'Periode mulai kosong';
                    if (empty($mitra['periode_selesai'])) $rowErrors[] = 'Periode selesai kosong';
                    break;
                    
                case 'sppd':
                    if (empty($mitra['no_sppd'])) $rowErrors[] = 'Nomor SPPD kosong';
                    if (empty($mitra['tanggal_surat'])) $rowErrors[] = 'Tanggal surat kosong';
                    if (empty($mitra['periode_mulai'])) $rowErrors[] = 'Periode mulai kosong';
                    if (empty($mitra['periode_selesai'])) $rowErrors[] = 'Periode selesai kosong';
                    if (empty($mitra['asal'])) $rowErrors[] = 'Asal kosong';
                    if (empty($mitra['tujuan'])) $rowErrors[] = 'Tujuan kosong';
                    break;
            }
            
            if (empty($rowErrors)) {
                $valid[] = $mitra;
            } else {
                $errors[$mitra['id']] = [
                    'nama' => $mitra['petugas_nama'],
                    'errors' => $rowErrors
                ];
            }
        }
        
        return [
            'valid' => $valid,
            'errors' => $errors,
            'total' => count($mitraList),
            'valid_count' => count($valid),
            'error_count' => count($errors)
        ];
    }

    /**
     * Get PPK dari users (role_id = 4 — mapping baru)
     */
    public function getPPK()
    {
        $stmt = $this->db->prepare("
            SELECT id, name as nama, nip,
                   CONCAT(IFNULL(CONCAT(gelar_depan, ' '), ''), name, IFNULL(CONCAT(', ', gelar_belakang), '')) as nama_lengkap
            FROM users WHERE role_id = 4 ORDER BY name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Kepala dari users (role_id = 2 — mapping baru)
     */
    public function getKepala()
    {
        $stmt = $this->db->prepare("
            SELECT id, name as nama, nip,
                   CONCAT(IFNULL(CONCAT(gelar_depan, ' '), ''), name, IFNULL(CONCAT(', ', gelar_belakang), '')) as nama_lengkap
            FROM users WHERE role_id = 2 ORDER BY name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get default PPK dengan data lengkap (role_id = 4)
     */
    public function getDefaultPPK()
    {
        $stmt = $this->db->prepare("
            SELECT id, name as nama, nip, pangkat, golongan, jabatan,
                   CONCAT(IFNULL(CONCAT(gelar_depan, ' '), ''), name, IFNULL(CONCAT(', ', gelar_belakang), '')) as nama_lengkap
            FROM users WHERE role_id = 4 ORDER BY id ASC LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get default Kepala dengan data lengkap (role_id = 2)
     */
    public function getDefaultKepala()
    {
        $stmt = $this->db->prepare("
            SELECT id, name as nama, nip, pangkat, golongan, jabatan,
                   CONCAT(IFNULL(CONCAT(gelar_depan, ' '), ''), name, IFNULL(CONCAT(', ', gelar_belakang), '')) as nama_lengkap
            FROM users WHERE role_id = 2 ORDER BY id ASC LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Hitung lama perjalanan (hari)
     */
    public static function hitungLamaPerjalanan($tanggalMulai, $tanggalSelesai)
    {
        if (empty($tanggalMulai) || empty($tanggalSelesai)) return 0;
        
        $start = new DateTime($tanggalMulai);
        $end = new DateTime($tanggalSelesai);
        $diff = $start->diff($end);
        
        return $diff->days + 1;
    }

    /**
     * Get daftar alat angkutan
     */
    public static function getAlatAngkutanList()
    {
        return [
            'Kendaraan Umum',
            'Kendaraan Dinas',
            'Pesawat',
            'Kapal',
            'Motor',
            'Jalan Kaki'
        ];
    }

    /**
     * Import dari Excel (menggunakan email sebagai identifier)
     */
    public function importFromExcel(array $validatedData, $team_id)
    {
        $success_count = 0;
        $errors = [];
        
        foreach ($validatedData as $row) {
            try {
                // Cari petugas berdasarkan email
                $petugas = $this->getPetugasByEmail($row['email']);
                if (!$petugas) {
                    $errors[$row['row_number']] = "Email {$row['email']} tidak ditemukan";
                    continue;
                }
                
                $this->add([
                    'kegiatan_detail_id' => $row['kegiatan_detail_id'],
                    'petugas_id' => $petugas['id'],
                    'petugas_source' => $petugas['source'],
                    'peran' => $row['peran'] ?: 'PPL',
                    'target' => $row['target'] ?? 0,
                    'realisasi' => $row['realisasi'] ?? 0,
                    'keterangan' => $row['keterangan'] ?? '',
                    'pml_id' => null,
                    'asal' => $row['asal'] ?? 'Enarotali',
                    'tujuan' => $row['tujuan'] ?? '',
                    'alat_angkutan' => $row['alat_angkutan'] ?? 'Kendaraan Umum',
                    'no_spk' => $row['no_spk'] ?? '',
                    'no_bast' => $row['no_bast'] ?? '',
                    'no_surat_tugas' => $row['no_surat_tugas'] ?? '',
                    'no_sppd' => $row['no_sppd'] ?? '',
                    'tanggal_surat' => !empty($row['tanggal_surat']) ? $row['tanggal_surat'] : null,
                    'periode_mulai' => !empty($row['periode_mulai']) ? $row['periode_mulai'] : null,
                    'periode_selesai' => !empty($row['periode_selesai']) ? $row['periode_selesai'] : null
                ]);
                
                $success_count++;
            } catch (Exception $e) {
                $errors[$row['row_number']] = $e->getMessage();
            }
        }
        
        return ['success_count' => $success_count, 'errors' => $errors];
    }

    /**
     * Get nama kegiatan
     */
    public function getNamaKegiatan($kegiatan_id)
    {
        $stmt = $this->db->prepare("SELECT nama_kegiatan FROM kegiatan_detail WHERE id = ?");
        $stmt->execute([$kegiatan_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nama_kegiatan'] : 'Kegiatan';
    }

    /**
     * Export data untuk template Excel (menggunakan email)
     */
    public function exportForTemplate($kegiatan_id)
    {
        $sql = "SELECT 
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.email
                        WHEN kp.petugas_source = 'mitra' THEN m.email
                    END AS email,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                    END AS nama_petugas,
                    kp.peran,
                    kp.target,
                    kp.realisasi,
                    kp.asal,
                    kp.tujuan,
                    kp.alat_angkutan,
                    kp.no_spk,
                    kp.no_bast,
                    kp.no_surat_tugas,
                    kp.no_sppd,
                    kp.tanggal_surat,
                    kp.periode_mulai,
                    kp.periode_selesai,
                    kp.keterangan
                FROM kegiatan_petugas kp
                LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
                WHERE kp.kegiatan_detail_id = ?
                ORDER BY kp.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatan_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alias untuk validateForGenerate (untuk kompatibilitas dengan controller)
     */
    public function validateForBulkGenerate($kegiatan_id, $jenis_dokumen)
    {
        return $this->validateForGenerate($kegiatan_id, $jenis_dokumen);
    }

    /**
     * Update target dan realisasi PML (dihitung dari total PPL)
     */
    public function updateTargetRealisasi($id, $target, $realisasi)
    {
        $sql = "UPDATE kegiatan_petugas SET target = ?, realisasi = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$target, $realisasi, $id]);
    }

    /**
     * Update realisasi saja
     */
    public function updateRealisasi($id, $realisasi)
    {
        $sql = "UPDATE kegiatan_petugas SET realisasi = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$realisasi, $id]);
    }

    /**
     * Get PML list by kegiatan (untuk dropdown PPL)
     */
    public function getPMLByKegiatan($kegiatan_id)
    {
        $sql = "SELECT 
                    kp.id,
                    kp.petugas_id,
                    kp.petugas_source,
                    CASE 
                        WHEN kp.petugas_source = 'users' THEN u.name
                        WHEN kp.petugas_source = 'mitra' THEN m.nama
                    END AS nama_pml
                FROM kegiatan_petugas kp
                LEFT JOIN users u ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
                WHERE kp.kegiatan_detail_id = ? AND kp.peran = 'PML'
                ORDER BY nama_pml";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatan_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Honor Mitra per Bulan
     * Menghitung total honor dari semua kegiatan yang diikuti mitra pada bulan tertentu
     * Honor = target * honor_satuan dari kegiatan_detail
     * UPDATED: Menampilkan SEMUA mitra (termasuk yang honor 0)
     */
    public function getHonorMitra($bulan, $tahun)
    {
        // Format periode: YYYY-MM
        $periodeStart = sprintf('%04d-%02d-01', $tahun, $bulan);
        $periodeEnd = date('Y-m-t', strtotime($periodeStart)); // Last day of month
        
        // Query untuk menampilkan SEMUA mitra (termasuk yang honor 0)
        // Honor = honor_satuan * target per mitra pada kegiatan
        // Tambahkan has_ob untuk status OB
        $sql = "SELECT 
                    m.id AS mitra_id,
                    m.nama,
                    m.email,
                    COALESCE(honor_data.total_honor, 0) AS total_honor,
                    COALESCE(honor_data.jumlah_kegiatan, 0) AS jumlah_kegiatan,
                    COALESCE(honor_data.has_ob, 0) AS has_ob
                FROM mitra m
                LEFT JOIN (
                    SELECT 
                        kp.petugas_id,
                        SUM(kp.target * COALESCE(kd.honor_satuan, 0)) AS total_honor,
                        COUNT(DISTINCT kp.kegiatan_detail_id) AS jumlah_kegiatan,
                        MAX(CASE WHEN UPPER(kd.satuan) = 'OB' THEN 1 ELSE 0 END) AS has_ob
                    FROM kegiatan_petugas kp
                    JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                    WHERE kp.petugas_source = 'mitra'
                    AND (
                        (kd.rentang_waktu_mulai <= ? AND kd.rentang_waktu_selesai >= ?)
                        OR (kd.rentang_waktu_mulai BETWEEN ? AND ?)
                        OR (kd.rentang_waktu_selesai BETWEEN ? AND ?)
                    )
                    GROUP BY kp.petugas_id
                ) AS honor_data ON honor_data.petugas_id = m.id
                WHERE m.status = 'aktif'
                ORDER BY total_honor DESC, m.nama ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $periodeEnd, $periodeStart,  // Kegiatan yang berlangsung selama periode
            $periodeStart, $periodeEnd,  // Kegiatan yang mulai di periode
            $periodeStart, $periodeEnd   // Kegiatan yang selesai di periode
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check apakah mitra sudah mencapai batas honor
     * Return: info lengkap tentang status honor mitra
     * 
     * Validasi:
     * - Aman: < 3 juta
     * - Perhatian: 3 - 4 juta
     * - Melebihi: > 4 juta ATAU mitra ikut kegiatan OB
     * 
     * Parameter $honorBaru: honor dari kegiatan baru yang akan ditambahkan
     */
    public function checkMitraHonorLimit($mitra_id, $bulan = null, $tahun = null, $honorBaru = 0)
    {
        // Default ke bulan dan tahun sekarang
        if (!$bulan) $bulan = date('m');
        if (!$tahun) $tahun = date('Y');
        
        $periodeStart = sprintf('%04d-%02d-01', $tahun, $bulan);
        $periodeEnd = date('Y-m-t', strtotime($periodeStart));
        
        // Query untuk total honor YANG SUDAH ADA
        $sql = "SELECT 
                    COALESCE(SUM(kp.target * COALESCE(kd.honor_satuan, 0)), 0) AS total_honor
                FROM kegiatan_petugas kp
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                WHERE kp.petugas_id = ? 
                AND kp.petugas_source = 'mitra'
                AND (
                    (kd.rentang_waktu_mulai <= ? AND kd.rentang_waktu_selesai >= ?)
                    OR (kd.rentang_waktu_mulai BETWEEN ? AND ?)
                    OR (kd.rentang_waktu_selesai BETWEEN ? AND ?)
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $mitra_id,
            $periodeEnd, $periodeStart,
            $periodeStart, $periodeEnd,
            $periodeStart, $periodeEnd
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentHonor = (float) ($result['total_honor'] ?? 0);
        
        // Hitung PROYEKSI honor = honor saat ini + honor kegiatan baru
        $honorBaru = (float) $honorBaru;
        $projectedHonor = $currentHonor + $honorBaru;
        
        // Cek apakah mitra ikut kegiatan dengan satuan OB pada bulan ini
        $hasOBKegiatan = $this->mitraHasOBKegiatan($mitra_id, $bulan, $tahun);
        
        // Batas honor:
        $safeLimit = 3000000;     // Batas aman
        $warningLimit = 4000000;  // Batas melebihi
        
        // Status
        $status = 'aman';
        $is_blocked = false;
        $needs_confirmation = false;
        
        // Jika SUDAH melebihi atau ikut OB → BLOKIR (tidak bisa ditambah sama sekali)
        if ($hasOBKegiatan || $currentHonor > $warningLimit) {
            $status = 'melebihi';
            $is_blocked = true;
        }
        // Jika PROYEKSI akan melebihi → perlu konfirmasi (tapi tidak diblokir)
        elseif ($projectedHonor > $warningLimit) {
            $status = 'proyeksi_melebihi';
            $is_blocked = false;
            $needs_confirmation = true;
        }
        // Jika honor saat ini di zona perhatian (3-4 juta)
        elseif ($currentHonor >= $safeLimit) {
            $status = 'perhatian';
            $is_blocked = false;
        }
        
        return [
            'mitra_id' => $mitra_id,
            'current_honor' => $currentHonor,
            'honor_baru' => $honorBaru,
            'projected_honor' => $projectedHonor,
            'safe_limit' => $safeLimit,
            'warning_limit' => $warningLimit,
            'has_ob_kegiatan' => $hasOBKegiatan,
            'status' => $status,
            'is_blocked' => $is_blocked,
            'needs_confirmation' => $needs_confirmation,
            'is_warning' => ($status === 'perhatian'),
            'is_over_limit' => ($status === 'melebihi'),
            'will_exceed_limit' => ($projectedHonor > $warningLimit)
        ];
    }
    
    /**
     * Cek apakah mitra ikut kegiatan dengan satuan OB pada bulan tertentu
     */
    public function mitraHasOBKegiatan($mitra_id, $bulan = null, $tahun = null)
    {
        if (!$bulan) $bulan = date('m');
        if (!$tahun) $tahun = date('Y');
        
        $periodeStart = sprintf('%04d-%02d-01', $tahun, $bulan);
        $periodeEnd = date('Y-m-t', strtotime($periodeStart));
        
        $sql = "SELECT COUNT(*) as count
                FROM kegiatan_petugas kp
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                WHERE kp.petugas_id = ? 
                AND kp.petugas_source = 'mitra'
                AND UPPER(kd.satuan) = 'OB'
                AND (
                    (kd.rentang_waktu_mulai <= ? AND kd.rentang_waktu_selesai >= ?)
                    OR (kd.rentang_waktu_mulai BETWEEN ? AND ?)
                    OR (kd.rentang_waktu_selesai BETWEEN ? AND ?)
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $mitra_id,
            $periodeEnd, $periodeStart,
            $periodeStart, $periodeEnd,
            $periodeStart, $periodeEnd
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0) > 0;
    }

    /**
     * Get Mitra yang mendekati/melebihi batas honor (untuk dashboard warning)
     * Batas baru: Perhatian >= 3 juta, Melebihi > 4 juta
     */
    public function getMitraWarningHonor($bulan = null, $tahun = null)
    {
        if (!$bulan) $bulan = date('m');
        if (!$tahun) $tahun = date('Y');
        
        $periodeStart = sprintf('%04d-%02d-01', $tahun, $bulan);
        $periodeEnd = date('Y-m-t', strtotime($periodeStart));
        
        $sql = "SELECT 
                    m.id AS mitra_id,
                    m.nama,
                    m.email,
                    COALESCE(SUM(kp.target * COALESCE(kd.honor_satuan, 0)), 0) AS total_honor,
                    MAX(CASE WHEN UPPER(kd.satuan) = 'OB' THEN 1 ELSE 0 END) AS has_ob
                FROM mitra m
                JOIN kegiatan_petugas kp ON kp.petugas_id = m.id 
                    AND kp.petugas_source = 'mitra'
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                WHERE m.status = 'aktif'
                AND (
                    (kd.rentang_waktu_mulai <= ? AND kd.rentang_waktu_selesai >= ?)
                    OR (kd.rentang_waktu_mulai BETWEEN ? AND ?)
                    OR (kd.rentang_waktu_selesai BETWEEN ? AND ?)
                )
                GROUP BY m.id, m.nama, m.email
                HAVING total_honor >= 3000000 OR has_ob = 1
                ORDER BY total_honor DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $periodeEnd, $periodeStart,
            $periodeStart, $periodeEnd,
            $periodeStart, $periodeEnd
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update realisasi PML berdasarkan total realisasi PPL di bawahnya
     */
    public function updatePMLRealisasi($pml_kegiatan_petugas_id)
    {
        // Hitung total realisasi PPL di bawah PML ini
        $sql = "SELECT COALESCE(SUM(realisasi), 0) as total_realisasi 
                FROM kegiatan_petugas 
                WHERE pml_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pml_kegiatan_petugas_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalRealisasi = (int) ($result['total_realisasi'] ?? 0);
        
        // Update realisasi PML
        $sql = "UPDATE kegiatan_petugas SET realisasi = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$totalRealisasi, $pml_kegiatan_petugas_id]);
        
        return $totalRealisasi;
    }
    
    /**
     * Get PML ID dari PPL
     */
    public function getPMLIdFromPPL($ppl_kegiatan_petugas_id)
    {
        $sql = "SELECT pml_id FROM kegiatan_petugas WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ppl_kegiatan_petugas_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['pml_id'] ?? null;
    }
    
    /**
     * Cek apakah petugas sudah terdaftar di kegiatan tertentu
     * Return: true jika sudah ada (duplikat), false jika belum ada
     */
    public function isPetugasInKegiatan($kegiatan_detail_id, $petugas_id, $petugas_source)
    {
        $sql = "SELECT COUNT(*) as count FROM kegiatan_petugas 
                WHERE kegiatan_detail_id = ? 
                AND petugas_id = ? 
                AND petugas_source = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kegiatan_detail_id, $petugas_id, $petugas_source]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['count'] > 0);
    }
}