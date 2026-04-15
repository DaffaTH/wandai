<?php
/*
 * WANDAI System - DokumenController
 * BPS Kabupaten Paniai
 * 
 * Generate Surat Tugas dan SPPD sesuai template:
 * - Template_Surat_Tugas.docx
 * - Template_SPPD.docx (halaman depan)
 * - Lembar Belakang SPPD: Generate dinamis berdasarkan jumlah tujuan
 * 
 * UPDATED:
 * - Romawi dinamis (II, III, IV, dst) sesuai jumlah tujuan
 * - Lembar dinamis dihitung otomatis
 * - NIK Petugas: Pegawai = "Nama / NIP", Mitra = "Nama" saja
 * - Wilayah Kerja dari mitra.wilayah_kerja
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/KegiatanPetugas.php';
require_once __DIR__ . '/../models/KegiatanDetail.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Mitra.php';
require_once __DIR__ . '/../helpers/Terbilang.php';

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Cell;

class DokumenController
{
    private $db;
    private $templatePath;
    private $outputPath;

    public function __construct()
    {
        require __DIR__ . '/../config/database.php';
        $this->db = $pdo;
        $this->templatePath = __DIR__ . '/../templates/';
        $this->outputPath = __DIR__ . '/../documents/';
    }

    // ==================== HELPER DOMAIN ====================

    /**
     * Map peran (PML/PPL/Supervisi) → label surat-resmi.
     *
     *   PPL            → "Pencacah Lapangan"
     *   PML, Supervisi → "Pengawas Lapangan"
     *
     * Dipakai semua template dokumen (Surat Tugas, SPD, SPK, BAST)
     * pada placeholder ${PERAN}.
     */
    private function getPeranLabel($peran)
    {
        $p = strtoupper(trim((string)$peran));
        if ($p === 'PPL') return 'Pencacah Lapangan';
        // PML dan Supervisi sama-sama menjadi "Pengawas Lapangan"
        // di surat resmi, supaya user surat tidak bingung.
        return 'Pengawas Lapangan';
    }

    /**
     * Derivasi Wilayah Tugas (Paniai / Intan Jaya) untuk satu baris petugas.
     *
     * Prioritas:
     *   1. Mitra → mitra.wilayah_kerja
     *   2. Pegawai → cari mitra lain di kegiatan yang sama
     *      (kegiatan_petugas JOIN mitra) ambil wilayah_kerja pertama
     *   3. Fallback → 'Paniai'
     */
    private function getWilayahKerja($data)
    {
        if (!empty($data['mitra_wilayah'])) {
            return $data['mitra_wilayah'];
        }
        if (!empty($data['kegiatan_detail_id'])) {
            $stmt = $this->db->prepare("
                SELECT m.wilayah_kerja
                FROM kegiatan_petugas kp
                JOIN mitra m ON m.id = kp.petugas_id
                WHERE kp.kegiatan_detail_id = ?
                  AND kp.petugas_source = 'mitra'
                  AND m.wilayah_kerja IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$data['kegiatan_detail_id']]);
            $w = $stmt->fetchColumn();
            if ($w) return $w;
        }
        return 'Paniai';
    }

    // ==================== GET PEJABAT ====================

    private function getPPK()
    {
        // role_id 4 = PPK (mapping baru)
        $sql = "SELECT
                    id, name, nip, pangkat, golongan, jabatan,
                    gelar_depan, gelar_belakang,
                    CONCAT(IFNULL(CONCAT(gelar_depan, ' '), ''), name, IFNULL(CONCAT(', ', gelar_belakang), '')) as nama_lengkap
                FROM users WHERE role_id = 4 ORDER BY id ASC LIMIT 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getKepala()
    {
        // role_id 2 = Kepala (mapping baru)
        $sql = "SELECT
                    id, name, nip, pangkat, golongan, jabatan,
                    gelar_depan, gelar_belakang,
                    CONCAT(IFNULL(CONCAT(gelar_depan, ' '), ''), name, IFNULL(CONCAT(', ', gelar_belakang), '')) as nama_lengkap
                FROM users WHERE role_id = 2 ORDER BY id ASC LIMIT 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getDataPetugas($kegiatan_petugas_id)
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
        $stmt->execute([$kegiatan_petugas_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==================== GENERATE SURAT TUGAS ====================

    public function generateSuratTugas()
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 6])) {
            echo "Akses ditolak."; exit;
        }

        $kegiatan_petugas_id = (int)($_POST['kegiatan_petugas_id'] ?? 0);
        $nomor_surat = trim($_POST['nomor_surat'] ?? '');
        $tanggal_surat = $_POST['tanggal_surat'] ?? '';
        $periode_mulai = $_POST['periode_mulai'] ?? '';
        $periode_selesai = $_POST['periode_selesai'] ?? '';

        $errors = [];
        if ($kegiatan_petugas_id <= 0) $errors[] = 'ID Petugas tidak valid';
        if (empty($nomor_surat)) $errors[] = 'Nomor surat kosong';
        if (empty($tanggal_surat)) $errors[] = 'Tanggal surat kosong';

        if (!empty($errors)) {
            $_SESSION['error'] = 'Data tidak lengkap: ' . implode(', ', $errors);
            header('Location: index.php?controller=petugas_kegiatan'); exit;
        }

        try {
            $kepala = $this->getKepala();
            if (!$kepala) throw new Exception('Kepala tidak ditemukan (role_id = 2)');

            $data = $this->getDataPetugas($kegiatan_petugas_id);
            if (!$data) throw new Exception('Data petugas tidak ditemukan');

            // Waktu pelaksanaan
            $waktuPelaksanaan = $this->formatTanggalRange($periode_mulai, $periode_selesai);

            // Uraian tugas
            $uraianTugas = ($data['peran'] === 'PPL' ? 'Pendataan' : 'Pengawasan') . ' ' . 
                           $data['nama_kegiatan'] . ' sebanyak ' . $data['target'] . ' ' . ($data['satuan'] ?? 'sampel');

            // Generate dari template
            $templateFile = $this->templatePath . 'Template_Surat_Tugas.docx';
            if (!file_exists($templateFile)) {
                throw new Exception('Template Surat Tugas tidak ditemukan: ' . $templateFile);
            }

            $template = new TemplateProcessor($templateFile);

            // ========== Placeholder ${...} di Template_Surat_Tugas.docx ==========
            //   NOMOR_SURAT, NAMA_KEGIATAN, PERAN, NAMA_PETUGAS, WILAYAH_KERJA,
            //   TANGGAL_SURAT, PERIODE_MULAI, PERIODE_SELESAI,
            //   (legacy) URAIAN_TUGAS, WAKTU_PELAKSANAAN, TEMPAT_TANGGAL_SURAT,
            //   NAMA_KEPALA, NIP_KEPALA
            $template->setValue('NOMOR_SURAT',      $nomor_surat);
            $template->setValue('TANGGAL_SURAT',    $this->formatTanggal($tanggal_surat));
            $template->setValue('NAMA_KEGIATAN',    $data['nama_kegiatan']);
            $template->setValue('PERAN',            $this->getPeranLabel($data['peran']));
            $template->setValue('NAMA_PETUGAS',     $data['petugas_nama']);
            $template->setValue('WILAYAH_KERJA',    $this->getWilayahKerja($data));
            $template->setValue('PERIODE_MULAI',    $this->formatTanggal($periode_mulai));
            $template->setValue('PERIODE_SELESAI',  $this->formatTanggal($periode_selesai));
            // Legacy placeholders (supaya template lama tetap jalan)
            $template->setValue('URAIAN_TUGAS',        $uraianTugas);
            $template->setValue('WAKTU_PELAKSANAAN',   $waktuPelaksanaan);
            $template->setValue('TEMPAT_TANGGAL_SURAT','Enarotali, ' . $this->formatTanggal($tanggal_surat));
            $template->setValue('NAMA_KEPALA',         $kepala['nama_lengkap']);
            $template->setValue('NIP_KEPALA',          $this->formatNIP($kepala['nip']));

            // Save
            $docsDir = $this->outputPath . 'surat_tugas/';
            if (!file_exists($docsDir)) mkdir($docsDir, 0755, true);
            
            $filename = 'SURTUG_' . preg_replace('/[^a-zA-Z0-9]/', '_', $data['petugas_nama']) . '_' . date('Y-m-d_His') . '.docx';
            $outputFile = $docsDir . $filename;
            $template->saveAs($outputFile);

            // Simpan ke database
            $this->saveDokumenRecord($kegiatan_petugas_id, 'surat_tugas', [
                'nomor_surat' => $nomor_surat,
                'tanggal_surat' => $tanggal_surat,
                'ppk_id' => $kepala['id'],
                'periode_mulai' => $periode_mulai,
                'periode_selesai' => $periode_selesai,
                'file_path_docx' => $outputFile,
                'file_path_pdf' => ''
            ]);

            $_SESSION['success'] = 'Surat Tugas berhasil digenerate untuk ' . $data['petugas_nama'];
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal generate Surat Tugas: ' . $e->getMessage();
            error_log("Generate Surat Tugas error: " . $e->getMessage());
        }

        header('Location: index.php?controller=petugas_kegiatan'); exit;
    }

    // ==================== GENERATE SPPD ====================

    public function generateSPD()
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 6])) {
            echo "Akses ditolak."; exit;
        }

        $kegiatan_petugas_id = (int)($_POST['kegiatan_petugas_id'] ?? 0);
        $nomor_surat = trim($_POST['nomor_surat'] ?? '');
        $tanggal_surat = $_POST['tanggal_surat'] ?? '';
        $periode_mulai = $_POST['periode_mulai'] ?? '';
        $periode_selesai = $_POST['periode_selesai'] ?? '';
        $asal = trim($_POST['asal'] ?? 'Enarotali');
        $tujuan = $_POST['tujuan'] ?? '';
        $alat_angkutan = trim($_POST['alat_angkutan'] ?? 'Kendaraan Umum');

        // Handle tujuan array (multiple destinations)
        if (is_array($tujuan)) {
            $tujuanArray = array_filter(array_map('trim', $tujuan));
        } else {
            $tujuanArray = array_filter(array_map('trim', explode(',', $tujuan)));
        }
        
        if (empty($tujuanArray)) {
            $tujuanArray = ['-'];
        }
        
        $tujuanString = implode(', ', $tujuanArray);

        // Validasi
        $errors = [];
        if ($kegiatan_petugas_id <= 0) $errors[] = 'ID Petugas tidak valid';
        if (empty($nomor_surat)) $errors[] = 'Nomor surat kosong';
        if (empty($tanggal_surat)) $errors[] = 'Tanggal surat kosong';

        if (!empty($errors)) {
            $_SESSION['error'] = 'Data tidak lengkap: ' . implode(', ', $errors);
            header('Location: index.php?controller=petugas_kegiatan'); exit;
        }

        try {
            $ppk = $this->getPPK();
            if (!$ppk) throw new Exception('PPK tidak ditemukan (role_id = 4)');

            $kepala = $this->getKepala();
            if (!$kepala) throw new Exception('Kepala tidak ditemukan (role_id = 2)');

            $data = $this->getDataPetugas($kegiatan_petugas_id);
            if (!$data) throw new Exception('Data petugas tidak ditemukan');

            // Hitung lama perjalanan
            $lamaPerjalanan = $this->hitungLamaPerjalanan($periode_mulai, $periode_selesai);

            // ================================================================
            // HITUNG JUMLAH LEMBAR DINAMIS
            // Lembar = 1 (halaman depan) + jumlah_tujuan (untuk baris dinamis)
            // Minimal 3 lembar (standar SPPD)
            // ================================================================
            $jumlahTujuan = count($tujuanArray);
            $jumlahLembar = max(3, 1 + ceil($jumlahTujuan / 2) + 1);

            // Format Nama/NIP Petugas
            // Pegawai: "Nama / NIP. xxx", Mitra: "Nama" saja
            if ($data['petugas_source'] === 'users' && !empty($data['petugas_nip'])) {
                $namaNipPetugas = $data['petugas_nama_lengkap'] . ' / NIP. ' . $this->formatNIP($data['petugas_nip']);
            } else {
                $namaNipPetugas = $data['petugas_nama'];
            }

            // ================================================================
            // GENERATE HALAMAN DEPAN SPPD (dari template)
            // ================================================================
            $templateFile = $this->templatePath . 'Template_SPPD.docx';
            if (!file_exists($templateFile)) {
                throw new Exception('Template SPPD tidak ditemukan: ' . $templateFile);
            }

            $template = new TemplateProcessor($templateFile);

            // ========== Placeholder ${...} di Template_SPPD.docx ==========
            //   NOMOR_SPD, NAMA_KEGIATAN, PERAN, NAMA_PETUGAS, WILAYAH_KERJA,
            //   TANGGAL_SURAT, PERIODE_MULAI, PERIODE_SELESAI, ASAL, TUJUAN,
            //   (legacy) NAMA_NIP_PETUGAS, MAKSUD_PERJALANAN, ALAT_ANGKUTAN,
            //   TEMPAT_BERANGKAT, TEMPAT_TUJUAN, LAMA_PERJALANAN,
            //   TANGGAL_BERANGKAT, TEMPAT_DIKELUARKAN, TEMPAT_BERANGKAT_AWAL,
            //   NAMA_KEPALA, NIP_KEPALA, NAMA_PPK, NIP_PPK, LEMBAR
            $template->setValue('NOMOR_SPD',        $nomor_surat);
            $template->setValue('NOMOR_SURAT',      $nomor_surat); // alias
            $template->setValue('TANGGAL_SURAT',    $this->formatTanggal($tanggal_surat));
            $template->setValue('NAMA_KEGIATAN',    $data['nama_kegiatan']);
            $template->setValue('PERAN',            $this->getPeranLabel($data['peran']));
            $template->setValue('NAMA_PETUGAS',     $data['petugas_nama']);
            $template->setValue('WILAYAH_KERJA',    $this->getWilayahKerja($data));
            $template->setValue('PERIODE_MULAI',    $this->formatTanggal($periode_mulai));
            $template->setValue('PERIODE_SELESAI',  $this->formatTanggal($periode_selesai));
            $template->setValue('ASAL',             $asal);
            $template->setValue('TUJUAN',           $tujuanString); // "Nabire, Waghete"
            $template->setValue('LEMBAR',           $jumlahLembar);
            // Legacy / SPD field detail
            $template->setValue('NAMA_PPK',               $ppk['nama_lengkap']);
            $template->setValue('NIP_PPK',                $this->formatNIP($ppk['nip']));
            $template->setValue('NAMA_NIP_PETUGAS',       $namaNipPetugas);
            $template->setValue('MAKSUD_PERJALANAN',      $data['nama_kegiatan']);
            $template->setValue('ALAT_ANGKUTAN',          $alat_angkutan);
            $template->setValue('TEMPAT_BERANGKAT',       $asal);
            $template->setValue('TEMPAT_TUJUAN',          $tujuanString);
            $template->setValue('LAMA_PERJALANAN',        $lamaPerjalanan . ' hari');
            $template->setValue('TANGGAL_BERANGKAT',      $this->formatTanggal($periode_mulai));
            $template->setValue('TEMPAT_DIKELUARKAN',     'Enarotali, Paniai');
            $template->setValue('TEMPAT_BERANGKAT_AWAL',  $asal);
            $template->setValue('NAMA_KEPALA',            $kepala['nama_lengkap']);
            $template->setValue('NIP_KEPALA',             $this->formatNIP($kepala['nip']));

            // Placeholder tambahan jika ada di template
            $template->setValue('PANGKAT_GOLONGAN', ($data['petugas_source'] === 'users') 
                ? (($data['petugas_pangkat'] ?? '-') . ' / ' . ($data['petugas_golongan'] ?? '-')) 
                : '-');
            $template->setValue('JABATAN', ($data['petugas_source'] === 'users') 
                ? ($data['petugas_jabatan'] ?? '-') 
                : '-');
            $template->setValue('PROGRAM', $data['program'] ?? '-');
            $template->setValue('OUTPUT', $data['output'] ?? '-');
            $template->setValue('KOMPONEN', $data['komponen'] ?? '-');
            $template->setValue('TANGGAL_SURAT', $this->formatTanggal($tanggal_surat));

            // Save halaman depan ke temporary file
            $docsDir = $this->outputPath . 'sppd/';
            if (!file_exists($docsDir)) mkdir($docsDir, 0755, true);
            
            $tempHalamanDepan = $docsDir . 'temp_depan_' . time() . '.docx';
            $template->saveAs($tempHalamanDepan);

            // ================================================================
            // GENERATE LEMBAR BELAKANG SPPD (dinamis)
            // ================================================================
            $lembarBelakangFile = $this->generateLembarBelakangSPPD([
                'asal' => $asal,
                'tujuan_array' => $tujuanArray,
                'periode_mulai' => $periode_mulai,
                'periode_selesai' => $periode_selesai,
                'kepala' => $kepala,
                'ppk' => $ppk,
            ]);

            // ================================================================
            // MERGE HALAMAN DEPAN + LEMBAR BELAKANG
            // ================================================================
            $filename = 'SPPD_' . preg_replace('/[^a-zA-Z0-9]/', '_', $data['petugas_nama']) . '_' . date('Y-m-d_His') . '.docx';
            $outputFile = $docsDir . $filename;
            
            $this->mergeDokumen($tempHalamanDepan, $lembarBelakangFile, $outputFile);

            // Hapus file temporary
            @unlink($tempHalamanDepan);
            @unlink($lembarBelakangFile);

            // Simpan ke database
            $this->saveDokumenRecord($kegiatan_petugas_id, 'sppd', [
                'nomor_surat' => $nomor_surat,
                'tanggal_surat' => $tanggal_surat,
                'ppk_id' => $ppk['id'],
                'periode_mulai' => $periode_mulai,
                'periode_selesai' => $periode_selesai,
                'lokasi' => $tujuanString,
                'file_path_docx' => $outputFile,
                'file_path_pdf' => ''
            ]);

            $_SESSION['success'] = 'SPPD berhasil digenerate untuk ' . $data['petugas_nama'] . ' (' . $jumlahTujuan . ' tujuan, ' . $jumlahLembar . ' lembar)';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal generate SPPD: ' . $e->getMessage();
            error_log("Generate SPPD error: " . $e->getMessage());
        }

        header('Location: index.php?controller=petugas_kegiatan'); exit;
    }

    // Alias
    public function generateSPPD() { return $this->generateSPD(); }

    // ==================== GENERATE LEMBAR BELAKANG SPPD DINAMIS ====================

    /**
     * Generate Lembar Belakang SPPD secara dinamis berdasarkan jumlah tujuan
     * 
     * Struktur:
     * - Baris I (tetap): Kolom kanan saja - Berangkat dari [Asal] + TTD Kepala
     * - Baris II, III, dst (dinamis): Sesuai jumlah tujuan
     * - Baris terakhir (tetap): Tiba kembali + TTD PPK
     * - Baris V (tetap): Catatan Lain-lain
     * - Baris VI (tetap): PERHATIAN
     */
    private function generateLembarBelakangSPPD($params)
    {
        $asal = $params['asal'];
        $tujuanArray = $params['tujuan_array'];
        $periodeMulai = $params['periode_mulai'];
        $periodeSelesai = $params['periode_selesai'];
        $kepala = $params['kepala'];
        $ppk = $params['ppk'];

        $phpWord = new PhpWord();
        
        // Set default font
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        // Add section
        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop' => 567,    // 1 cm
            'marginBottom' => 567,
            'marginLeft' => 567,
            'marginRight' => 567,
        ]);

        // Style untuk tabel
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ];
        $phpWord->addTableStyle('LembarBelakang', $tableStyle);

        $cellWidth = 4800; // Lebar kolom
        $fontBold = ['bold' => true];
        $fontUnderline = ['underline' => 'single'];
        $paragraphCenter = ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0];
        $paragraphLeft = ['alignment' => Jc::LEFT, 'spaceAfter' => 0, 'spaceBefore' => 0];

        // Buat tabel utama
        $table = $section->addTable('LembarBelakang');

        // ================================================================
        // BARIS I: Berangkat dari [Asal] - Hanya kolom KANAN terisi
        // ================================================================
        $table->addRow();
        
        // Kolom kiri (KOSONG)
        $cellLeft = $table->addCell($cellWidth);
        $cellLeft->addText('', [], $paragraphCenter);
        
        // Kolom kanan
        $cellRight = $table->addCell($cellWidth);
        $cellRight->addText('Berangkat dari', [], $paragraphCenter);
        $cellRight->addText(': ' . $asal, [], $paragraphCenter);
        $cellRight->addText('Pada tanggal', [], $paragraphCenter);
        $cellRight->addText(': ' . $this->formatTanggal($periodeMulai), [], $paragraphCenter);
        $cellRight->addText('Ke', [], $paragraphCenter);
        $cellRight->addText(': ' . $tujuanArray[0], [], $paragraphCenter);
        $cellRight->addText('');
        $cellRight->addText('Kepala Badan Pusat Statistik', [], $paragraphCenter);
        $cellRight->addText('Kabupaten Paniai', [], $paragraphCenter);
        $cellRight->addText('');
        $cellRight->addText('');
        $cellRight->addText('');
        $cellRight->addText($kepala['nama_lengkap'], $fontUnderline, $paragraphCenter);
        $cellRight->addText('NIP. ' . $this->formatNIP($kepala['nip']), [], $paragraphCenter);

        // ================================================================
        // BARIS DINAMIS: II, III, dst sesuai jumlah tujuan
        // Romawi: II, III, IV, V, dst (otomatis)
        // ================================================================
        $nomorRomawi = 2; // Mulai dari II

        for ($i = 0; $i < count($tujuanArray); $i++) {
            $tujuanSekarang = $tujuanArray[$i];
            $tujuanBerikutnya = isset($tujuanArray[$i + 1]) ? $tujuanArray[$i + 1] : $asal;
            
            $table->addRow();
            
            // Kolom kiri: Tiba di [Tujuan]
            $cellLeft = $table->addCell($cellWidth);
            $cellLeft->addText($this->toRoman($nomorRomawi) . '.', $fontBold, $paragraphCenter);
            $cellLeft->addText('Tiba di', [], $paragraphCenter);
            $cellLeft->addText(': ' . $tujuanSekarang, [], $paragraphCenter);
            $cellLeft->addText('Pada tanggal', [], $paragraphCenter);
            $cellLeft->addText(': ....................', [], $paragraphCenter);
            $cellLeft->addText('');
            $cellLeft->addText('Kepala Desa/Lurah,', [], $paragraphCenter);
            $cellLeft->addText('');
            $cellLeft->addText('');
            $cellLeft->addText('');
            $cellLeft->addText('(..............................)', [], $paragraphCenter);
            
            // Kolom kanan: Berangkat dari [Tujuan] ke [Tujuan berikutnya/Asal]
            $cellRight = $table->addCell($cellWidth);
            $cellRight->addText($this->toRoman($nomorRomawi + 1) . '.', $fontBold, $paragraphCenter);
            $cellRight->addText('Berangkat dari', [], $paragraphCenter);
            $cellRight->addText(': ' . $tujuanSekarang, [], $paragraphCenter);
            $cellRight->addText('Pada tanggal', [], $paragraphCenter);
            $cellRight->addText(': ....................', [], $paragraphCenter);
            $cellRight->addText('Ke', [], $paragraphCenter);
            $cellRight->addText(': ' . $tujuanBerikutnya, [], $paragraphCenter);
            $cellRight->addText('');
            $cellRight->addText('Kepala Desa/Lurah,', [], $paragraphCenter);
            $cellRight->addText('');
            $cellRight->addText('');
            $cellRight->addText('(..............................)', [], $paragraphCenter);
            
            $nomorRomawi += 2;
        }

        // ================================================================
        // BARIS TERAKHIR: Tiba kembali di [Asal] + Telah diperiksa
        // ================================================================
        $table->addRow();
        
        // Kolom kiri: Tiba kembali
        $cellLeft = $table->addCell($cellWidth);
        $cellLeft->addText($this->toRoman($nomorRomawi) . '.', $fontBold, $paragraphCenter);
        $cellLeft->addText('Tiba kembali di', [], $paragraphCenter);
        $cellLeft->addText(': ' . $asal, [], $paragraphCenter);
        $cellLeft->addText('Pada tanggal', [], $paragraphCenter);
        $cellLeft->addText(': ' . $this->formatTanggal($periodeSelesai), [], $paragraphCenter);
        $cellLeft->addText('');
        $cellLeft->addText('Pejabat Pembuat Komitmen', [], $paragraphCenter);
        $cellLeft->addText('Badan Pusat Statistik', [], $paragraphCenter);
        $cellLeft->addText('Kabupaten Paniai', [], $paragraphCenter);
        $cellLeft->addText('');
        $cellLeft->addText('');
        $cellLeft->addText('');
        $cellLeft->addText($ppk['nama_lengkap'], $fontUnderline, $paragraphCenter);
        $cellLeft->addText('NIP. ' . $this->formatNIP($ppk['nip']), [], $paragraphCenter);
        
        // Kolom kanan: Telah diperiksa
        $cellRight = $table->addCell($cellWidth);
        $cellRight->addText('Telah diperiksa dengan keterangan bahwa', [], $paragraphCenter);
        $cellRight->addText('perjalanan tersebut atas perintahnya dan', [], $paragraphCenter);
        $cellRight->addText('semata-mata untuk kepentingan jabatan', [], $paragraphCenter);
        $cellRight->addText('dalam waktu yang sesingkat-singkatnya', [], $paragraphCenter);
        $cellRight->addText('');
        $cellRight->addText('Pejabat Pembuat Komitmen', [], $paragraphCenter);
        $cellRight->addText('Badan Pusat Statistik', [], $paragraphCenter);
        $cellRight->addText('Kabupaten Paniai', [], $paragraphCenter);
        $cellRight->addText('');
        $cellRight->addText('');
        $cellRight->addText('');
        $cellRight->addText($ppk['nama_lengkap'], $fontUnderline, $paragraphCenter);
        $cellRight->addText('NIP. ' . $this->formatNIP($ppk['nip']), [], $paragraphCenter);

        // ================================================================
        // BARIS V: Catatan Lain-lain
        // ================================================================
        $table->addRow();
        $cell = $table->addCell($cellWidth * 2, ['gridSpan' => 2]);
        $cell->addText('V.    Catatan Lain-lain :', $fontBold, $paragraphLeft);
        $cell->addText('');

        // ================================================================
        // BARIS VI: PERHATIAN
        // ================================================================
        $table->addRow();
        $cell = $table->addCell($cellWidth * 2, ['gridSpan' => 2]);
        $cell->addText('VI.   PERHATIAN:', array_merge($fontBold, $fontUnderline), $paragraphLeft);
        $cell->addText('Pejabat yang berwenang memberikan SPD, Pegawai yang melakukan perjalanan dinas, para pejabat yang mengesahkan tanggal berangkat/tiba serta Bendaharawan bertanggung jawab berdasarkan peraturan-peraturan keuangan Negara apabila Negara menderita rugi akibat kesalahan, kelalaian dan kealpannya.', [], $paragraphLeft);

        // Save
        $filename = 'temp_lembar_belakang_' . time() . '.docx';
        $outputFile = $this->outputPath . 'sppd/' . $filename;
        
        if (!file_exists($this->outputPath . 'sppd/')) {
            mkdir($this->outputPath . 'sppd/', 0755, true);
        }
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputFile);

        return $outputFile;
    }

    // ==================== MERGE DOKUMEN ====================

    private function mergeDokumen($file1, $file2, $outputFile)
    {
        // Baca file pertama
        $zip1 = new ZipArchive();
        if ($zip1->open($file1) !== true) {
            throw new Exception('Tidak dapat membuka file halaman depan');
        }

        // Baca file kedua
        $zip2 = new ZipArchive();
        if ($zip2->open($file2) !== true) {
            throw new Exception('Tidak dapat membuka file lembar belakang');
        }

        // Load kedua dokumen dengan PHPWord
        $phpWord1 = IOFactory::load($file1);
        $phpWord2 = IOFactory::load($file2);

        // Ambil semua section dari dokumen kedua dan tambahkan ke dokumen pertama
        $sections2 = $phpWord2->getSections();
        
        foreach ($sections2 as $section) {
            // Tambah page break sebelum section baru
            $newSection = $phpWord1->addSection([
                'breakType' => 'nextPage'
            ]);
            
            // Copy semua element
            foreach ($section->getElements() as $element) {
                // Clone element ke section baru
                $newSection->addElement(clone $element);
            }
        }

        // Save merged document
        $writer = IOFactory::createWriter($phpWord1, 'Word2007');
        $writer->save($outputFile);

        $zip1->close();
        $zip2->close();
    }

    // ==================== HELPER METHODS ====================

    private function toRoman($num)
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];
        $result = '';
        foreach ($map as $roman => $value) {
            while ($num >= $value) {
                $result .= $roman;
                $num -= $value;
            }
        }
        return $result;
    }

    private function formatTanggal($date)
    {
        if (empty($date)) return '-';
        $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $ts = strtotime($date);
        return date('j', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
    }

    private function formatTanggalRange($start, $end)
    {
        if (empty($start) || empty($end)) return '-';
        
        $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        
        $ts1 = strtotime($start);
        $ts2 = strtotime($end);
        
        if (date('m Y', $ts1) === date('m Y', $ts2)) {
            // Bulan & tahun sama
            return date('j', $ts1) . ' – ' . date('j', $ts2) . ' ' . 
                   $bulan[(int)date('m', $ts1)] . ' ' . date('Y', $ts1);
        } else {
            return $this->formatTanggal($start) . ' s.d. ' . $this->formatTanggal($end);
        }
    }

    private function formatNIP($nip)
    {
        if (empty($nip)) return '-';
        $nip = preg_replace('/\D/', '', $nip);
        if (strlen($nip) == 18) {
            return substr($nip, 0, 8) . ' ' . substr($nip, 8, 6) . ' ' . substr($nip, 14, 1) . ' ' . substr($nip, 15, 3);
        }
        return $nip;
    }

    private function hitungLamaPerjalanan($tanggalMulai, $tanggalSelesai)
    {
        if (empty($tanggalMulai) || empty($tanggalSelesai)) return 0;
        $start = new DateTime($tanggalMulai);
        $end = new DateTime($tanggalSelesai);
        return $start->diff($end)->days + 1;
    }

    private function saveDokumenRecord($kegiatan_petugas_id, $jenis, $data)
    {
        $sql = "INSERT INTO dokumen_kontrak 
                (kegiatan_petugas_id, jenis_dokumen, nomor_surat, tanggal_kontrak, ppk_id, 
                 honorarium, periode_mulai, periode_selesai, lokasi, jumlah_realisasi, 
                 file_path_docx, file_path_pdf, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                nomor_surat = VALUES(nomor_surat),
                tanggal_kontrak = VALUES(tanggal_kontrak),
                file_path_docx = VALUES(file_path_docx),
                file_path_pdf = VALUES(file_path_pdf),
                updated_at = NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $kegiatan_petugas_id,
            $jenis,
            $data['nomor_surat'],
            $data['tanggal_surat'],
            $data['ppk_id'],
            $data['honorarium'] ?? null,
            $data['periode_mulai'] ?? null,
            $data['periode_selesai'] ?? null,
            $data['lokasi'] ?? null,
            $data['jumlah_realisasi'] ?? null,
            $data['file_path_docx'],
            $data['file_path_pdf'],
            $_SESSION['user']['id']
        ]);
    }

    // ==================== SPK & BAST (existing methods) ====================

    public function generateSPK()
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 6])) {
            echo "Akses ditolak."; exit;
        }

        $kegiatan_petugas_id = (int)($_POST['kegiatan_petugas_id'] ?? 0);
        $nomor_surat = trim($_POST['nomor_surat'] ?? '');
        $tanggal_surat = $_POST['tanggal_surat'] ?? '';
        $periode_mulai = $_POST['periode_mulai'] ?? '';
        $periode_selesai = $_POST['periode_selesai'] ?? '';
        $honorarium = (float)($_POST['honorarium'] ?? 0);

        $errors = [];
        if ($kegiatan_petugas_id <= 0) $errors[] = 'ID Petugas tidak valid';
        if (empty($nomor_surat)) $errors[] = 'Nomor surat kosong';
        if (empty($tanggal_surat)) $errors[] = 'Tanggal surat kosong';

        if (!empty($errors)) {
            $_SESSION['error'] = 'Data tidak lengkap: ' . implode(', ', $errors);
            header('Location: index.php?controller=petugas_kegiatan'); exit;
        }

        try {
            $ppk = $this->getPPK();
            if (!$ppk) throw new Exception('PPK tidak ditemukan');

            $data = $this->getDataPetugas($kegiatan_petugas_id);
            if (!$data) throw new Exception('Data petugas tidak ditemukan');

            // Generate dari template jika ada
            $templateFile = $this->templatePath . 'Template_SPK.docx';
            $docsDir = $this->outputPath . 'spk/';
            if (!file_exists($docsDir)) mkdir($docsDir, 0755, true);
            
            $filename = 'SPK_' . preg_replace('/[^a-zA-Z0-9]/', '_', $data['petugas_nama']) . '_' . date('Y-m-d_His') . '.docx';
            $outputFile = $docsDir . $filename;

            if (file_exists($templateFile)) {
                $template = new TemplateProcessor($templateFile);
                // ========== Placeholder ${...} di Template_SPK.docx ==========
                //   NOMOR_SURAT, TANGGAL_SURAT, NAMA_KEGIATAN, PERAN, NAMA_PETUGAS,
                //   WILAYAH_KERJA, PERIODE_MULAI, PERIODE_SELESAI, HONORARIUM,
                //   (legacy) KEGIATAN, SATUAN, TARGET, TERBILANG, NAMA_PPK, NIP_PPK,
                //   NIK_PETUGAS, ALAMAT_PETUGAS
                $template->setValue('NOMOR_SURAT',     $nomor_surat);
                $template->setValue('TANGGAL_SURAT',   $this->formatTanggal($tanggal_surat));
                $template->setValue('NAMA_KEGIATAN',   $data['nama_kegiatan']);
                $template->setValue('PERAN',           $this->getPeranLabel($data['peran']));
                $template->setValue('NAMA_PETUGAS',    strtoupper($data['petugas_nama']));
                $template->setValue('WILAYAH_KERJA',   $this->getWilayahKerja($data));
                $template->setValue('PERIODE_MULAI',   $this->formatTanggal($periode_mulai));
                $template->setValue('PERIODE_SELESAI', $this->formatTanggal($periode_selesai));
                $template->setValue('HONORARIUM',      number_format($honorarium, 0, ',', '.'));
                // Legacy placeholders
                $satuanSpk = $data['satuan'] ?? 'Dokumen';
                $template->setValue('KEGIATAN',        $data['nama_kegiatan']);
                $template->setValue('SATUAN',          $satuanSpk);
                // TARGET sudah termasuk satuan: "200 Dokumen"
                $template->setValue('TARGET',          number_format($data['target'], 0, ',', '.') . ' ' . $satuanSpk);
                $template->setValue('TARGET_ANGKA',    number_format($data['target'], 0, ',', '.'));
                $template->setValue('TERBILANG',       $this->terbilang($honorarium));
                $template->setValue('NAMA_PPK',        $ppk['nama_lengkap']);
                $template->setValue('NIP_PPK',         $this->formatNIP($ppk['nip']));
                $template->setValue('NIK_PETUGAS',     $data['mitra_nik']    ?? '-');
                $template->setValue('ALAMAT_PETUGAS',  $data['mitra_alamat'] ?? '-');
                $template->saveAs($outputFile);
            } else {
                // Fallback: create simple file
                file_put_contents($outputFile, "SPK untuk " . $data['petugas_nama']);
            }

            $this->saveDokumenRecord($kegiatan_petugas_id, 'spk', [
                'nomor_surat' => $nomor_surat,
                'tanggal_surat' => $tanggal_surat,
                'ppk_id' => $ppk['id'],
                'honorarium' => $honorarium,
                'periode_mulai' => $periode_mulai,
                'periode_selesai' => $periode_selesai,
                'file_path_docx' => $outputFile,
                'file_path_pdf' => ''
            ]);

            $_SESSION['success'] = 'SPK berhasil digenerate untuk ' . $data['petugas_nama'];
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal generate SPK: ' . $e->getMessage();
        }

        header('Location: index.php?controller=petugas_kegiatan'); exit;
    }

    public function generateBAST()
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 6])) {
            echo "Akses ditolak."; exit;
        }

        $kegiatan_petugas_id = (int)($_POST['kegiatan_petugas_id'] ?? 0);
        $nomor_surat = trim($_POST['nomor_surat'] ?? '');
        $tanggal_surat = $_POST['tanggal_surat'] ?? '';
        $jumlah_realisasi = (int)($_POST['jumlah_realisasi'] ?? 0);

        $errors = [];
        if ($kegiatan_petugas_id <= 0) $errors[] = 'ID Petugas tidak valid';
        if (empty($nomor_surat)) $errors[] = 'Nomor surat kosong';
        if (empty($tanggal_surat)) $errors[] = 'Tanggal surat kosong';

        if (!empty($errors)) {
            $_SESSION['error'] = 'Data tidak lengkap: ' . implode(', ', $errors);
            header('Location: index.php?controller=petugas_kegiatan'); exit;
        }

        try {
            $ppk = $this->getPPK();
            if (!$ppk) throw new Exception('PPK tidak ditemukan');

            $data = $this->getDataPetugas($kegiatan_petugas_id);
            if (!$data) throw new Exception('Data petugas tidak ditemukan');

            $templateFile = $this->templatePath . 'Template_BAST.docx';
            $docsDir = $this->outputPath . 'bast/';
            if (!file_exists($docsDir)) mkdir($docsDir, 0755, true);
            
            $filename = 'BAST_' . preg_replace('/[^a-zA-Z0-9]/', '_', $data['petugas_nama']) . '_' . date('Y-m-d_His') . '.docx';
            $outputFile = $docsDir . $filename;

            if (file_exists($templateFile)) {
                $template = new TemplateProcessor($templateFile);
                // ========== Placeholder ${...} di Template_BAST.docx ==========
                //   NOMOR_SURAT, TANGGAL_SURAT, NAMA_KEGIATAN, PERAN, NAMA_PETUGAS,
                //   WILAYAH_KERJA, JUMLAH_REALISASI,
                //   (legacy) KEGIATAN, SATUAN, TARGET, NAMA_PPK, NIP_PPK, NIK_PETUGAS
                $template->setValue('NOMOR_SURAT',      $nomor_surat);
                $template->setValue('TANGGAL_SURAT',    $this->formatTanggal($tanggal_surat));
                $template->setValue('NAMA_KEGIATAN',    $data['nama_kegiatan']);
                $template->setValue('PERAN',            $this->getPeranLabel($data['peran']));
                $template->setValue('NAMA_PETUGAS',     strtoupper($data['petugas_nama']));
                $template->setValue('WILAYAH_KERJA',    $this->getWilayahKerja($data));
                $satuanBast = $data['satuan'] ?? 'Dokumen';
                // JUMLAH_REALISASI sudah termasuk satuan: "150 Dokumen"
                $template->setValue('JUMLAH_REALISASI', number_format($jumlah_realisasi, 0, ',', '.') . ' ' . $satuanBast);
                // Legacy placeholders (jika template masih pakai angka & satuan terpisah)
                $template->setValue('REALISASI_ANGKA',  number_format($jumlah_realisasi, 0, ',', '.'));
                $template->setValue('KEGIATAN',         $data['nama_kegiatan']);
                $template->setValue('SATUAN',           $satuanBast);
                $template->setValue('TARGET',           number_format($data['target'], 0, ',', '.') . ' ' . $satuanBast);
                $template->setValue('TARGET_ANGKA',     number_format($data['target'], 0, ',', '.'));
                $template->setValue('NAMA_PPK',         $ppk['nama_lengkap']);
                $template->setValue('NIP_PPK',          $this->formatNIP($ppk['nip']));
                $template->setValue('NIK_PETUGAS',      $data['mitra_nik'] ?? '-');
                $template->saveAs($outputFile);
            } else {
                file_put_contents($outputFile, "BAST untuk " . $data['petugas_nama']);
            }

            $this->saveDokumenRecord($kegiatan_petugas_id, 'bast', [
                'nomor_surat' => $nomor_surat,
                'tanggal_surat' => $tanggal_surat,
                'ppk_id' => $ppk['id'],
                'jumlah_realisasi' => $jumlah_realisasi,
                'file_path_docx' => $outputFile,
                'file_path_pdf' => ''
            ]);

            $_SESSION['success'] = 'BAST berhasil digenerate.';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal generate BAST: ' . $e->getMessage();
        }

        header('Location: index.php?controller=petugas_kegiatan'); exit;
    }

    private function terbilang($angka)
    {
        $angka = abs($angka);
        $huruf = ['','Satu','Dua','Tiga','Empat','Lima','Enam','Tujuh','Delapan','Sembilan','Sepuluh','Sebelas'];
        if ($angka < 12) return ' ' . $huruf[$angka];
        if ($angka < 20) return $this->terbilang($angka - 10) . ' Belas';
        if ($angka < 100) return $this->terbilang(floor($angka / 10)) . ' Puluh' . $this->terbilang($angka % 10);
        if ($angka < 200) return ' Seratus' . $this->terbilang($angka - 100);
        if ($angka < 1000) return $this->terbilang(floor($angka / 100)) . ' Ratus' . $this->terbilang($angka % 100);
        if ($angka < 2000) return ' Seribu' . $this->terbilang($angka - 1000);
        if ($angka < 1000000) return $this->terbilang(floor($angka / 1000)) . ' Ribu' . $this->terbilang($angka % 1000);
        if ($angka < 1000000000) return $this->terbilang(floor($angka / 1000000)) . ' Juta' . $this->terbilang($angka % 1000000);
        return trim($this->terbilang(floor($angka / 1000000000)) . ' Miliar' . $this->terbilang($angka % 1000000000));
    }

    public function download()
    {
        if (!isset($_SESSION['user'])) { echo "Akses ditolak."; exit; }
        $file = $_GET['file'] ?? '';
        if (empty($file) || !preg_match('/^documents\//', $file) || strpos($file, '..') !== false || !file_exists($file)) {
            echo "File tidak ditemukan."; exit;
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        if (ob_get_level()) ob_end_clean();
        readfile($file);
        exit;
    }

    /**
     * Bulk Generate dengan Tabel - Semua petugas sekaligus
     */
    public function bulkGenerateWithTable()
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $kegiatan_id = $_POST['kegiatan_id'] ?? null;
        $jenis = $_POST['jenis_dokumen'] ?? 'spk';
        $petugas_ids = $_POST['petugas_ids'] ?? [];
        
        // Data per petugas (arrays)
        $nomor_surat = $_POST['nomor_surat'] ?? [];
        $periode_mulai_arr = $_POST['periode_mulai'] ?? [];
        $periode_selesai_arr = $_POST['periode_selesai'] ?? [];
        $honorarium_arr = $_POST['honorarium'] ?? [];
        $tujuan_arr = $_POST['tujuan'] ?? [];
        
        // Data bersama (hanya tanggal surat)
        $tanggal_surat = $_POST['tanggal_surat'] ?? date('Y-m-d');
        $asal = $_POST['asal'] ?? 'Enarotali';
        $alat_angkutan = $_POST['alat_angkutan'] ?? 'Kendaraan Umum';

        if (empty($kegiatan_id) || empty($petugas_ids)) {
            $_SESSION['error'] = 'Data tidak lengkap. Pilih minimal 1 petugas.';
            header('Location: index.php?controller=petugas_kegiatan&kegiatan_id=' . $kegiatan_id);
            exit;
        }

        $petugasModel = new KegiatanPetugas();
        $success = 0;
        $errors = [];

        foreach ($petugas_ids as $petugas_id) {
            // Get data per petugas dari array
            $nomorSuratPetugas = $nomor_surat[$petugas_id] ?? '';
            $periodeMulaiPetugas = $periode_mulai_arr[$petugas_id] ?? null;
            $periodeSelesaiPetugas = $periode_selesai_arr[$petugas_id] ?? null;
            $honorariumPetugas = $honorarium_arr[$petugas_id] ?? 400000;
            $tujuanPetugas = $tujuan_arr[$petugas_id] ?? '';

            if (empty($nomorSuratPetugas)) {
                $petugas = $petugasModel->getById($petugas_id);
                $nama = $petugas['petugas_nama'] ?? "ID $petugas_id";
                $errors[] = "$nama: Nomor surat kosong";
                continue;
            }

            // Get data petugas
            $petugas = $petugasModel->getById($petugas_id);
            if (!$petugas) {
                $errors[] = "Petugas ID $petugas_id tidak ditemukan";
                continue;
            }

            try {
                // Generate dokumen berdasarkan jenis
                switch ($jenis) {
                    case 'spk':
                        $this->generateSPKInternal($petugas_id, $nomorSuratPetugas, $tanggal_surat, $periodeMulaiPetugas, $periodeSelesaiPetugas, $honorariumPetugas);
                        break;
                    case 'bast':
                        $this->generateBASTInternal($petugas_id, $nomorSuratPetugas, $tanggal_surat, $petugas['realisasi'] ?? 0);
                        break;
                    case 'surat_tugas':
                        $this->generateSuratTugasInternal($petugas_id, $nomorSuratPetugas, $tanggal_surat, $periodeMulaiPetugas, $periodeSelesaiPetugas);
                        break;
                    case 'sppd':
                        $this->generateSPPDInternal($petugas_id, $nomorSuratPetugas, $tanggal_surat, $periodeMulaiPetugas, $periodeSelesaiPetugas, $asal, $tujuanPetugas, $alat_angkutan);
                        break;
                }
                $success++;
            } catch (Exception $e) {
                $nama = $petugas['petugas_nama'] ?? "ID $petugas_id";
                $errors[] = "$nama: " . $e->getMessage();
            }
        }

        // Set session messages
        $jenisLabel = ['spk' => 'SPK', 'bast' => 'BAST', 'surat_tugas' => 'Surat Tugas', 'sppd' => 'SPPD'];
        
        if ($success > 0) {
            $_SESSION['success'] = "✅ Berhasil generate $success dokumen " . ($jenisLabel[$jenis] ?? strtoupper($jenis));
        }
        if (!empty($errors)) {
            $errorMsg = "❌ Gagal generate beberapa dokumen:\n" . implode("\n", array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $errorMsg .= "\n... dan " . (count($errors) - 5) . " error lainnya";
            }
            $_SESSION['error'] = $errorMsg;
        }

        header('Location: index.php?controller=petugas_kegiatan&kegiatan_id=' . $kegiatan_id);
        exit;
    }

    // Internal methods untuk bulk generate (tambahkan jika belum ada)
    private function generateSPKInternal($petugas_id, $nomor, $tanggal, $mulai, $selesai, $honor) {
        // Simpan ke database dokumen_kontrak
        $sql = "INSERT INTO dokumen_kontrak (kegiatan_petugas_id, jenis_dokumen, nomor_surat, tanggal_surat, periode_mulai, periode_selesai, honorarium, created_at)
                VALUES (?, 'spk', ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE nomor_surat=?, tanggal_surat=?, periode_mulai=?, periode_selesai=?, honorarium=?, updated_at=NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$petugas_id, $nomor, $tanggal, $mulai, $selesai, $honor, $nomor, $tanggal, $mulai, $selesai, $honor]);
    }

    private function generateBASTInternal($petugas_id, $nomor, $tanggal, $realisasi) {
        $sql = "INSERT INTO dokumen_kontrak (kegiatan_petugas_id, jenis_dokumen, nomor_surat, tanggal_surat, jumlah_realisasi, created_at)
                VALUES (?, 'bast', ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE nomor_surat=?, tanggal_surat=?, jumlah_realisasi=?, updated_at=NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$petugas_id, $nomor, $tanggal, $realisasi, $nomor, $tanggal, $realisasi]);
    }

    private function generateSuratTugasInternal($petugas_id, $nomor, $tanggal, $mulai, $selesai) {
        $sql = "INSERT INTO dokumen_kontrak (kegiatan_petugas_id, jenis_dokumen, nomor_surat, tanggal_surat, periode_mulai, periode_selesai, created_at)
                VALUES (?, 'surat_tugas', ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE nomor_surat=?, tanggal_surat=?, periode_mulai=?, periode_selesai=?, updated_at=NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$petugas_id, $nomor, $tanggal, $mulai, $selesai, $nomor, $tanggal, $mulai, $selesai]);
    }

    private function generateSPPDInternal($petugas_id, $nomor, $tanggal, $mulai, $selesai, $asal, $tujuan, $alat) {
        $sql = "INSERT INTO dokumen_kontrak (kegiatan_petugas_id, jenis_dokumen, nomor_surat, tanggal_surat, periode_mulai, periode_selesai, asal, tujuan, alat_angkutan, created_at)
                VALUES (?, 'sppd', ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE nomor_surat=?, tanggal_surat=?, periode_mulai=?, periode_selesai=?, asal=?, tujuan=?, alat_angkutan=?, updated_at=NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$petugas_id, $nomor, $tanggal, $mulai, $selesai, $asal, $tujuan, $alat, $nomor, $tanggal, $mulai, $selesai, $asal, $tujuan, $alat]);
    }

    // =========================================================================
    // ================ NEW: Surat Tugas (Sept 2022) + SPD merged ==============
    // =========================================================================

    /**
     * Generate Surat Tugas (format Sept 2022) yang DIGABUNG dengan SPD
     * jadi satu file docx.
     *
     * Template yang dipakai:
     *   - templates/Template_Surat_Tugas_Sept2022.docx  (halaman Surat Tugas)
     *   - templates/Template_SPPD.docx                   (halaman depan SPD)
     *   - Lembar belakang SPD dibangkitkan dinamis (existing)
     *
     * Placeholder ${...} yang dipakai pada Template_Surat_Tugas_Sept2022.docx:
     *   NOMOR_SURAT, TANGGAL_SURAT, TEMPAT_TANGGAL_SURAT,
     *   NAMA_PETUGAS, NIP_PETUGAS, PANGKAT_GOLONGAN, JABATAN,
     *   URAIAN_TUGAS, WAKTU_PELAKSANAAN, NAMA_KEGIATAN, PERAN,
     *   WILAYAH_KERJA, ASAL, TUJUAN, LAMA_PERJALANAN,
     *   NAMA_KEPALA, NIP_KEPALA
     *
     * Untuk mitra: NIP_PETUGAS, PANGKAT_GOLONGAN, JABATAN = '-' (tidak ada).
     */
    public function generateSuratTugasSPD()
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 6])) {
            echo "Akses ditolak."; exit;
        }

        $kegiatan_petugas_id = (int)($_POST['kegiatan_petugas_id'] ?? 0);
        $nomor_surat     = trim($_POST['nomor_surat'] ?? '');
        $tanggal_surat   = $_POST['tanggal_surat'] ?? '';
        $periode_mulai   = $_POST['periode_mulai'] ?? '';
        $periode_selesai = $_POST['periode_selesai'] ?? '';
        $asal            = trim($_POST['asal'] ?? 'Enarotali');
        $tujuan          = $_POST['tujuan'] ?? '';
        $alat_angkutan   = trim($_POST['alat_angkutan'] ?? 'Kendaraan Umum');

        // Normalisasi tujuan (bisa array atau CSV)
        if (is_array($tujuan)) {
            $tujuanArray = array_filter(array_map('trim', $tujuan));
        } else {
            $tujuanArray = array_filter(array_map('trim', explode(',', $tujuan)));
        }
        if (empty($tujuanArray)) $tujuanArray = ['-'];
        $tujuanString = implode(', ', $tujuanArray);

        $errors = [];
        if ($kegiatan_petugas_id <= 0) $errors[] = 'ID Petugas tidak valid';
        if (empty($nomor_surat))       $errors[] = 'Nomor surat kosong';
        if (empty($tanggal_surat))     $errors[] = 'Tanggal surat kosong';
        if (!empty($errors)) {
            $_SESSION['error'] = 'Data tidak lengkap: ' . implode(', ', $errors);
            header('Location: index.php?controller=petugas_kegiatan'); exit;
        }

        try {
            $kepala = $this->getKepala();
            if (!$kepala) throw new Exception('Kepala tidak ditemukan (role_id = 2)');
            $ppk = $this->getPPK();
            if (!$ppk) throw new Exception('PPK tidak ditemukan (role_id = 4)');

            $data = $this->getDataPetugas($kegiatan_petugas_id);
            if (!$data) throw new Exception('Data petugas tidak ditemukan');

            $isPegawai = ($data['petugas_source'] === 'users');

            // Uraian tugas: gabungan peran + nama kegiatan + target/satuan
            $peranLabel = $this->getPeranLabel($data['peran']);
            $satuan     = $data['satuan'] ?? 'sampel';
            $uraianTugas = $peranLabel . ' ' . $data['nama_kegiatan']
                . ' sebanyak ' . number_format((int)$data['target'], 0, ',', '.') . ' ' . $satuan;

            // Waktu pelaksanaan: single vs range otomatis
            $waktuPelaksanaan = ($periode_mulai && $periode_selesai && $periode_mulai === $periode_selesai)
                ? $this->formatTanggal($periode_mulai)
                : $this->formatTanggalRange($periode_mulai, $periode_selesai);

            $lamaPerjalanan = $this->hitungLamaPerjalanan($periode_mulai, $periode_selesai);

            $docsDir = $this->outputPath . 'surat_tugas_spd/';
            if (!file_exists($docsDir)) mkdir($docsDir, 0755, true);

            // ==== 1. SURAT TUGAS (Sept 2022) ====
            $stTplFile = $this->templatePath . 'Template_Surat_Tugas_Sept2022.docx';
            if (!file_exists($stTplFile)) {
                // Fallback ke template lama agar fitur tidak mati saat file belum di-upload.
                $stTplFile = $this->templatePath . 'Template_Surat_Tugas.docx';
            }
            if (!file_exists($stTplFile)) {
                throw new Exception('Template Surat Tugas tidak ditemukan.');
            }

            $stTpl = new TemplateProcessor($stTplFile);
            $stTpl->setValue('NOMOR_SURAT',         $nomor_surat);
            $stTpl->setValue('TANGGAL_SURAT',       $this->formatTanggal($tanggal_surat));
            $stTpl->setValue('TEMPAT_TANGGAL_SURAT','Enarotali, ' . $this->formatTanggal($tanggal_surat));
            $stTpl->setValue('NAMA_PETUGAS',        $data['petugas_nama_lengkap'] ?? $data['petugas_nama']);
            $stTpl->setValue('NIP_PETUGAS',         $isPegawai ? $this->formatNIP($data['petugas_nip']) : '-');
            $stTpl->setValue('PANGKAT_GOLONGAN',    $isPegawai
                ? (($data['petugas_pangkat'] ?? '-') . ' / ' . ($data['petugas_golongan'] ?? '-'))
                : '-');
            $stTpl->setValue('JABATAN',             $isPegawai ? ($data['petugas_jabatan'] ?? '-') : '-');
            $stTpl->setValue('NAMA_KEGIATAN',       $data['nama_kegiatan']);
            $stTpl->setValue('PERAN',               $peranLabel);
            $stTpl->setValue('WILAYAH_KERJA',       $this->getWilayahKerja($data));
            $stTpl->setValue('URAIAN_TUGAS',        $uraianTugas);
            $stTpl->setValue('WAKTU_PELAKSANAAN',   $waktuPelaksanaan);
            $stTpl->setValue('ASAL',                $asal);
            $stTpl->setValue('TUJUAN',              $tujuanString);
            $stTpl->setValue('LAMA_PERJALANAN',     $lamaPerjalanan . ' hari');
            $stTpl->setValue('NAMA_KEPALA',         $kepala['nama_lengkap']);
            $stTpl->setValue('NIP_KEPALA',          $this->formatNIP($kepala['nip']));
            // Alias placeholder bila template masih pakai nama lama
            $stTpl->setValue('PERIODE_MULAI',       $this->formatTanggal($periode_mulai));
            $stTpl->setValue('PERIODE_SELESAI',     $this->formatTanggal($periode_selesai));

            $tmpSuratTugas = $docsDir . 'tmp_surat_tugas_' . uniqid() . '.docx';
            $stTpl->saveAs($tmpSuratTugas);

            // ==== 2. SPD halaman depan ====
            $spdTplFile = $this->templatePath . 'Template_SPPD.docx';
            if (!file_exists($spdTplFile)) {
                @unlink($tmpSuratTugas);
                throw new Exception('Template SPPD tidak ditemukan.');
            }

            $jumlahTujuan = count($tujuanArray);
            $jumlahLembar = max(3, 1 + (int)ceil($jumlahTujuan / 2) + 1);
            $namaNipPetugas = $isPegawai && !empty($data['petugas_nip'])
                ? $data['petugas_nama_lengkap'] . ' / NIP. ' . $this->formatNIP($data['petugas_nip'])
                : $data['petugas_nama'];

            $spdTpl = new TemplateProcessor($spdTplFile);
            $spdTpl->setValue('NOMOR_SPD',           $nomor_surat);
            $spdTpl->setValue('NOMOR_SURAT',         $nomor_surat);
            $spdTpl->setValue('TANGGAL_SURAT',       $this->formatTanggal($tanggal_surat));
            $spdTpl->setValue('NAMA_KEGIATAN',       $data['nama_kegiatan']);
            $spdTpl->setValue('PERAN',               $peranLabel);
            $spdTpl->setValue('NAMA_PETUGAS',        $data['petugas_nama']);
            $spdTpl->setValue('WILAYAH_KERJA',       $this->getWilayahKerja($data));
            $spdTpl->setValue('PERIODE_MULAI',       $this->formatTanggal($periode_mulai));
            $spdTpl->setValue('PERIODE_SELESAI',     $this->formatTanggal($periode_selesai));
            $spdTpl->setValue('ASAL',                $asal);
            $spdTpl->setValue('TUJUAN',              $tujuanString);
            $spdTpl->setValue('LEMBAR',              $jumlahLembar);
            $spdTpl->setValue('NAMA_PPK',            $ppk['nama_lengkap']);
            $spdTpl->setValue('NIP_PPK',             $this->formatNIP($ppk['nip']));
            $spdTpl->setValue('NAMA_NIP_PETUGAS',    $namaNipPetugas);
            $spdTpl->setValue('MAKSUD_PERJALANAN',   $data['nama_kegiatan']);
            $spdTpl->setValue('ALAT_ANGKUTAN',       $alat_angkutan);
            $spdTpl->setValue('TEMPAT_BERANGKAT',    $asal);
            $spdTpl->setValue('TEMPAT_TUJUAN',       $tujuanString);
            $spdTpl->setValue('LAMA_PERJALANAN',     $lamaPerjalanan . ' hari');
            $spdTpl->setValue('TANGGAL_BERANGKAT',   $this->formatTanggal($periode_mulai));
            $spdTpl->setValue('TEMPAT_DIKELUARKAN',  'Enarotali, Paniai');
            $spdTpl->setValue('TEMPAT_BERANGKAT_AWAL', $asal);
            $spdTpl->setValue('NAMA_KEPALA',         $kepala['nama_lengkap']);
            $spdTpl->setValue('NIP_KEPALA',          $this->formatNIP($kepala['nip']));
            $spdTpl->setValue('PANGKAT_GOLONGAN',    $isPegawai
                ? (($data['petugas_pangkat'] ?? '-') . ' / ' . ($data['petugas_golongan'] ?? '-'))
                : '-');
            $spdTpl->setValue('JABATAN',             $isPegawai ? ($data['petugas_jabatan'] ?? '-') : '-');
            $spdTpl->setValue('PROGRAM',             $data['program']  ?? '-');
            $spdTpl->setValue('OUTPUT',              $data['output']   ?? '-');
            $spdTpl->setValue('KOMPONEN',            $data['komponen'] ?? '-');

            $tmpSpdDepan = $docsDir . 'tmp_spd_depan_' . uniqid() . '.docx';
            $spdTpl->saveAs($tmpSpdDepan);

            // ==== 3. Lembar belakang SPD (dinamis) ====
            $lembarBelakangFile = $this->generateLembarBelakangSPPD([
                'asal' => $asal,
                'tujuan_array' => $tujuanArray,
                'periode_mulai' => $periode_mulai,
                'periode_selesai' => $periode_selesai,
                'kepala' => $kepala,
                'ppk' => $ppk,
            ]);

            // ==== 4. Merge: Surat Tugas + SPD depan + Lembar belakang ====
            $filename = 'SURTUG_SPD_' . preg_replace('/[^a-zA-Z0-9]/', '_', $data['petugas_nama']) . '_' . date('Y-m-d_His') . '.docx';
            $outputFile = $docsDir . $filename;

            // mergeDokumen() hanya menerima 2 file. Gabung bertahap via file sementara.
            $tmpMerged = $docsDir . 'tmp_merged_' . uniqid() . '.docx';
            $this->mergeDokumen($tmpSuratTugas, $tmpSpdDepan, $tmpMerged);
            $this->mergeDokumen($tmpMerged,    $lembarBelakangFile, $outputFile);

            // Bersih-bersih file sementara
            @unlink($tmpSuratTugas);
            @unlink($tmpSpdDepan);
            @unlink($tmpMerged);
            @unlink($lembarBelakangFile);

            // Simpan record utama sebagai 'surat_tugas' (file mencakup keduanya)
            $this->saveDokumenRecord($kegiatan_petugas_id, 'surat_tugas', [
                'nomor_surat'     => $nomor_surat,
                'tanggal_surat'   => $tanggal_surat,
                'ppk_id'          => $kepala['id'],
                'periode_mulai'   => $periode_mulai,
                'periode_selesai' => $periode_selesai,
                'lokasi'          => $tujuanString,
                'file_path_docx'  => $outputFile,
                'file_path_pdf'   => '',
            ]);

            $_SESSION['success'] = 'Surat Tugas + SPD berhasil digenerate untuk ' . $data['petugas_nama']
                . ' (' . $jumlahTujuan . ' tujuan, ' . $jumlahLembar . ' lembar)';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal generate Surat Tugas + SPD: ' . $e->getMessage();
            error_log('Generate Surat Tugas + SPD error: ' . $e->getMessage());
        }

        header('Location: index.php?controller=petugas_kegiatan'); exit;
    }

    // =========================================================================
    // ================ NEW: SPK SNLIK (cloneRow per-jenis) ====================
    // =========================================================================

    /**
     * Generate SPK Petugas Pendataan Lapangan (format SNLIK 2026).
     *
     * Perbedaan vs generateSPK() lama:
     *   - Menggunakan Template_SPK_SNLIK.docx (fallback ke Template_SPK.docx).
     *   - Menulis tanggal/bulan/tahun dalam huruf latin via helpers/Terbilang.php.
     *   - Honorarium dalam terbilang lengkap ("...Rupiah").
     *   - Tabel per-jenis-kegiatan di-clone via cloneRowAndSetValues().
     *     Marker baris di template: ${jenis}, ${satuan}, ${target_jumlah},
     *     ${honor_satuan}, ${sub_total}.
     *   - NIK untuk mitra; untuk pegawai NIP (atau '-' bila tidak ada).
     *
     * Placeholder lain yang dipakai:
     *   NOMOR_SURAT, HARI_LATIN, TANGGAL_LATIN, BULAN_LATIN, TAHUN_LATIN,
     *   NAMA_PETUGAS, NIK_PETUGAS, ALAMAT_PETUGAS, WILAYAH_KERJA,
     *   NAMA_KEGIATAN, PERAN, PERIODE_MULAI, PERIODE_SELESAI,
     *   HONORARIUM, TERBILANG, NAMA_PPK, NIP_PPK.
     */
    public function generateSPKSnlik()
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 6])) {
            echo "Akses ditolak."; exit;
        }

        $kegiatan_petugas_id = (int)($_POST['kegiatan_petugas_id'] ?? 0);
        $nomor_surat     = trim($_POST['nomor_surat'] ?? '');
        $tanggal_surat   = $_POST['tanggal_surat'] ?? '';
        $periode_mulai   = $_POST['periode_mulai'] ?? '';
        $periode_selesai = $_POST['periode_selesai'] ?? '';
        // Honor bisa dikirim langsung, kalau kosong ambil dari kegiatan_jenis
        $honorariumInput = (float)($_POST['honorarium'] ?? 0);

        $errors = [];
        if ($kegiatan_petugas_id <= 0) $errors[] = 'ID Petugas tidak valid';
        if (empty($nomor_surat))       $errors[] = 'Nomor surat kosong';
        if (empty($tanggal_surat))     $errors[] = 'Tanggal surat kosong';
        if (!empty($errors)) {
            $_SESSION['error'] = 'Data tidak lengkap: ' . implode(', ', $errors);
            header('Location: index.php?controller=petugas_kegiatan'); exit;
        }

        try {
            $ppk = $this->getPPK();
            if (!$ppk) throw new Exception('PPK tidak ditemukan');

            $data = $this->getDataPetugas($kegiatan_petugas_id);
            if (!$data) throw new Exception('Data petugas tidak ditemukan');

            // Ambil daftar jenis (untuk tabel rinci honor)
            $stmt = $this->db->prepare("
                SELECT jenis, satuan, honor_satuan
                FROM kegiatan_jenis
                WHERE kegiatan_id = ?
                ORDER BY FIELD(jenis,'Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan')
            ");
            $stmt->execute([$data['kegiatan_detail_id']]);
            $jenisRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $templateFile = $this->templatePath . 'Template_SPK_SNLIK.docx';
            if (!file_exists($templateFile)) {
                $templateFile = $this->templatePath . 'Template_SPK.docx';
            }
            if (!file_exists($templateFile)) {
                throw new Exception('Template SPK tidak ditemukan');
            }

            $template = new TemplateProcessor($templateFile);

            // Header surat: tanggal latin
            $template->setValue('NOMOR_SURAT',     $nomor_surat);
            $template->setValue('TANGGAL_SURAT',   $this->formatTanggal($tanggal_surat));
            $template->setValue('HARI_LATIN',      hariIndo($tanggal_surat));
            $template->setValue('TANGGAL_LATIN',   ucwords(terbilang((int)date('j', strtotime($tanggal_surat)))));
            $template->setValue('BULAN_LATIN',     bulanIndo($tanggal_surat));
            $template->setValue('TAHUN_LATIN',     ucwords(terbilang((int)date('Y', strtotime($tanggal_surat)))));

            // Petugas
            $isPegawai = ($data['petugas_source'] === 'users');
            $template->setValue('NAMA_PETUGAS',    strtoupper($data['petugas_nama']));
            $template->setValue('NIK_PETUGAS',     $isPegawai
                ? ($data['petugas_nip'] ? $this->formatNIP($data['petugas_nip']) : '-')
                : ($data['mitra_nik'] ?? '-'));
            $template->setValue('ALAMAT_PETUGAS',  $data['mitra_alamat'] ?? '-');
            $template->setValue('WILAYAH_KERJA',   $this->getWilayahKerja($data));
            $template->setValue('NAMA_KEGIATAN',   $data['nama_kegiatan']);
            $template->setValue('PERAN',           $this->getPeranLabel($data['peran']));
            $template->setValue('PERIODE_MULAI',   $this->formatTanggal($periode_mulai));
            $template->setValue('PERIODE_SELESAI', $this->formatTanggal($periode_selesai));

            // Tabel rinci per-jenis (cloneRow). Marker baris di template: ${jenis}
            // Honor per jenis = honor_satuan * target (target sama untuk semua jenis
            // pada kegiatan_petugas saat ini; detail per-jenis-target belum dimodelkan).
            $target = (int)$data['target'];
            $totalHonor = 0;
            $rows = [];
            foreach ($jenisRows as $jr) {
                $hs = (float)($jr['honor_satuan'] ?? 0);
                $sub = $hs * $target;
                $totalHonor += $sub;
                $rows[] = [
                    'jenis'         => $jr['jenis'],
                    'satuan'        => $jr['satuan'] ?? '-',
                    'target_jumlah' => number_format($target, 0, ',', '.'),
                    'honor_satuan'  => number_format($hs, 0, ',', '.'),
                    'sub_total'     => number_format($sub, 0, ',', '.'),
                ];
            }
            // Kalau template punya clone row dengan marker ${jenis}, pakai itu.
            try {
                if (!empty($rows)) {
                    $template->cloneRowAndSetValues('jenis', $rows);
                }
            } catch (Exception $ignore) {
                // Template lama tanpa baris clone: abaikan, tetap jalan.
            }

            // Honorarium total. Pakai input user bila ada (> 0), fallback ke total baris.
            $honorarium = $honorariumInput > 0 ? $honorariumInput : $totalHonor;
            $template->setValue('HONORARIUM',  number_format($honorarium, 0, ',', '.'));
            $template->setValue('TERBILANG',   terbilangRupiah((int)$honorarium));
            // Alias legacy
            $satuanSpk = $data['satuan'] ?? 'Dokumen';
            $template->setValue('KEGIATAN',    $data['nama_kegiatan']);
            $template->setValue('SATUAN',      $satuanSpk);
            $template->setValue('TARGET',      number_format($target, 0, ',', '.') . ' ' . $satuanSpk);
            $template->setValue('TARGET_ANGKA',number_format($target, 0, ',', '.'));

            // Pejabat
            $template->setValue('NAMA_PPK',    $ppk['nama_lengkap']);
            $template->setValue('NIP_PPK',     $this->formatNIP($ppk['nip']));

            $docsDir = $this->outputPath . 'spk/';
            if (!file_exists($docsDir)) mkdir($docsDir, 0755, true);
            $filename = 'SPK_' . preg_replace('/[^a-zA-Z0-9]/', '_', $data['petugas_nama']) . '_' . date('Y-m-d_His') . '.docx';
            $outputFile = $docsDir . $filename;
            $template->saveAs($outputFile);

            $this->saveDokumenRecord($kegiatan_petugas_id, 'spk', [
                'nomor_surat'     => $nomor_surat,
                'tanggal_surat'   => $tanggal_surat,
                'ppk_id'          => $ppk['id'],
                'honorarium'      => $honorarium,
                'periode_mulai'   => $periode_mulai,
                'periode_selesai' => $periode_selesai,
                'file_path_docx'  => $outputFile,
                'file_path_pdf'   => '',
            ]);

            $_SESSION['success'] = 'SPK berhasil digenerate untuk ' . $data['petugas_nama'];
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal generate SPK: ' . $e->getMessage();
            error_log('Generate SPK SNLIK error: ' . $e->getMessage());
        }

        header('Location: index.php?controller=petugas_kegiatan'); exit;
    }

    // =========================================================================
    // ================ NEW: BAST Honor per Kegiatan (cloneRow petugas) ========
    // =========================================================================

    /**
     * Generate BAST Honor untuk SATU kegiatan, mencakup SEMUA petugas
     * di kegiatan tersebut (satu dokumen, satu tabel).
     *
     * Placeholder di Template_BAST_Honor.docx:
     *   NOMOR_SURAT, TANGGAL_SURAT, HARI_LATIN, TANGGAL_LATIN, BULAN_LATIN, TAHUN_LATIN,
     *   NAMA_KEGIATAN, JENIS_KEGIATAN, BULAN_KEGIATAN, TAHUN_KEGIATAN,
     *   NAMA_PJ, NIP_PJ, JABATAN_PJ, NAMA_PPK, NIP_PPK.
     *
     * Baris clone (${no}): no, nama_petugas, peran, target_realisasi,
     *                      honor_satuan, honorarium, keterangan.
     */
    public function generateBASTKegiatan()
    {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 6])) {
            echo "Akses ditolak."; exit;
        }

        $kegiatan_detail_id = (int)($_POST['kegiatan_detail_id'] ?? $_GET['kegiatan_detail_id'] ?? 0);
        $nomor_surat   = trim($_POST['nomor_surat'] ?? '');
        $tanggal_surat = $_POST['tanggal_surat'] ?? date('Y-m-d');

        $errors = [];
        if ($kegiatan_detail_id <= 0) $errors[] = 'ID Kegiatan tidak valid';
        if (empty($nomor_surat))      $errors[] = 'Nomor surat kosong';
        if (!empty($errors)) {
            $_SESSION['error'] = 'Data tidak lengkap: ' . implode(', ', $errors);
            header('Location: index.php?controller=kegiatan'); exit;
        }

        try {
            // Kegiatan + Penanggung Jawab
            $stmt = $this->db->prepare("
                SELECT kd.*,
                       CONCAT(IFNULL(CONCAT(pj.gelar_depan, ' '), ''), pj.name,
                              IFNULL(CONCAT(', ', pj.gelar_belakang), '')) AS pj_nama_lengkap,
                       pj.nip       AS pj_nip,
                       pj.jabatan   AS pj_jabatan
                FROM kegiatan_detail kd
                LEFT JOIN users pj ON pj.id = kd.penanggung_jawab_user_id
                WHERE kd.id = ?
            ");
            $stmt->execute([$kegiatan_detail_id]);
            $kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$kegiatan) throw new Exception('Kegiatan tidak ditemukan');

            $ppk = $this->getPPK();
            if (!$ppk) throw new Exception('PPK tidak ditemukan');

            // Ambil semua petugas di kegiatan ini
            $stmt = $this->db->prepare("
                SELECT kp.id, kp.peran, kp.target, kp.realisasi, kp.keterangan,
                       CASE WHEN kp.petugas_source = 'users' THEN u.name
                            WHEN kp.petugas_source = 'mitra' THEN m.nama END AS petugas_nama,
                       COALESCE(kd.honor_satuan, 0) AS honor_satuan,
                       kd.satuan
                FROM kegiatan_petugas kp
                JOIN kegiatan_detail kd ON kd.id = kp.kegiatan_detail_id
                LEFT JOIN users u  ON u.id = kp.petugas_id AND kp.petugas_source = 'users'
                LEFT JOIN mitra m  ON m.id = kp.petugas_id AND kp.petugas_source = 'mitra'
                WHERE kp.kegiatan_detail_id = ?
                ORDER BY COALESCE(kp.pml_id, kp.id) ASC, kp.peran DESC, kp.id ASC
            ");
            $stmt->execute([$kegiatan_detail_id]);
            $petugasList = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($petugasList)) {
                throw new Exception('Belum ada petugas di kegiatan ini');
            }

            $templateFile = $this->templatePath . 'Template_BAST_Honor.docx';
            if (!file_exists($templateFile)) {
                // Fallback ke template lama supaya tidak blank saat file belum di-upload
                $templateFile = $this->templatePath . 'Template_BAST.docx';
            }
            if (!file_exists($templateFile)) {
                throw new Exception('Template BAST tidak ditemukan');
            }

            $template = new TemplateProcessor($templateFile);

            // Header
            $template->setValue('NOMOR_SURAT',   $nomor_surat);
            $template->setValue('TANGGAL_SURAT', $this->formatTanggal($tanggal_surat));
            $template->setValue('HARI_LATIN',    hariIndo($tanggal_surat));
            $template->setValue('TANGGAL_LATIN', ucwords(terbilang((int)date('j', strtotime($tanggal_surat)))));
            $template->setValue('BULAN_LATIN',   bulanIndo($tanggal_surat));
            $template->setValue('TAHUN_LATIN',   ucwords(terbilang((int)date('Y', strtotime($tanggal_surat)))));

            $template->setValue('NAMA_KEGIATAN',  $kegiatan['nama_kegiatan']);
            $template->setValue('JENIS_KEGIATAN', $kegiatan['jenis_kegiatan'] ?? '-');
            $refDate = $kegiatan['rentang_waktu_mulai'] ?: $tanggal_surat;
            $template->setValue('BULAN_KEGIATAN', bulanIndo($refDate));
            $template->setValue('TAHUN_KEGIATAN', date('Y', strtotime($refDate)));

            // Penanggung Jawab (dari kolom penanggung_jawab_user_id; fallback ke Kepala)
            if (!empty($kegiatan['pj_nama_lengkap'])) {
                $template->setValue('NAMA_PJ',    $kegiatan['pj_nama_lengkap']);
                $template->setValue('NIP_PJ',     $this->formatNIP($kegiatan['pj_nip']));
                $template->setValue('JABATAN_PJ', $kegiatan['pj_jabatan'] ?? '-');
            } else {
                $kepala = $this->getKepala();
                $template->setValue('NAMA_PJ',    $kepala['nama_lengkap'] ?? '-');
                $template->setValue('NIP_PJ',     $this->formatNIP($kepala['nip'] ?? ''));
                $template->setValue('JABATAN_PJ', $kepala['jabatan'] ?? 'Kepala');
            }

            // PPK
            $template->setValue('NAMA_PPK', $ppk['nama_lengkap']);
            $template->setValue('NIP_PPK',  $this->formatNIP($ppk['nip']));

            // Tabel petugas
            $rows = [];
            $no = 1;
            $satuan = $kegiatan['satuan'] ?? '';
            foreach ($petugasList as $p) {
                $realisasi = (int)($p['realisasi'] ?? 0);
                $honorSat  = (float)($p['honor_satuan'] ?? 0);
                $honor     = $realisasi * $honorSat;
                $rows[] = [
                    'no'               => $no++,
                    'nama_petugas'     => $p['petugas_nama'] ?? '-',
                    'peran'            => $this->getPeranLabel($p['peran']),
                    'target_realisasi' => number_format($realisasi, 0, ',', '.') . ' ' . $satuan,
                    'honor_satuan'     => number_format($honorSat, 0, ',', '.'),
                    'honorarium'       => number_format($honor, 0, ',', '.'),
                    'keterangan'       => $p['keterangan'] ?? '',
                ];
            }
            try {
                $template->cloneRowAndSetValues('no', $rows);
            } catch (Exception $ignore) {
                // Template lama tanpa cloneRow: biarkan, field tunggal saja yang terisi.
            }

            $docsDir = $this->outputPath . 'bast/';
            if (!file_exists($docsDir)) mkdir($docsDir, 0755, true);
            $filename = 'BAST_HONOR_' . preg_replace('/[^a-zA-Z0-9]/', '_', $kegiatan['nama_kegiatan']) . '_' . date('Y-m-d_His') . '.docx';
            $outputFile = $docsDir . $filename;
            $template->saveAs($outputFile);

            // Simpan record BAST Honor (pakai petugas pertama sebagai kegiatan_petugas_id
            // supaya foreign key dokumen_kontrak.kegiatan_petugas_id tetap valid).
            $anchorPetugasId = (int)($petugasList[0]['id'] ?? 0);
            if ($anchorPetugasId > 0) {
                $this->saveDokumenRecord($anchorPetugasId, 'bast', [
                    'nomor_surat'    => $nomor_surat,
                    'tanggal_surat'  => $tanggal_surat,
                    'ppk_id'         => $ppk['id'],
                    'file_path_docx' => $outputFile,
                    'file_path_pdf'  => '',
                ]);
            }

            $_SESSION['success'] = 'BAST Honor berhasil digenerate untuk kegiatan "' . $kegiatan['nama_kegiatan'] . '" (' . count($petugasList) . ' petugas).';
            // Download langsung file docx-nya
            $relPath = 'documents/bast/' . basename($outputFile);
            header('Location: index.php?controller=dokumen&action=download&file=' . urlencode($relPath));
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal generate BAST Honor: ' . $e->getMessage();
            error_log('Generate BAST Kegiatan error: ' . $e->getMessage());
        }

        header('Location: index.php?controller=kegiatan'); exit;
    }
}