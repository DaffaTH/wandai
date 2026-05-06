-- ============================================================
-- wandai_final.sql  –  WANDAI System  –  BPS Kabupaten Paniai
-- Versi server    : 10.4.32-MariaDB
-- Dibuat          : 2026-05-06
--
-- CATATAN: Tidak mengandung CREATE DATABASE / USE statement.
-- Impor langsung ke database yang sudah dibuat di cPanel.
-- Password default : Bps9605!
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Tabel: administrasi
-- --------------------------------------------------------
CREATE TABLE `administrasi` (
  `id` int(11) NOT NULL,
  `kegiatan_petugas_id` int(11) NOT NULL,
  `jenis` enum('perjalanan_dinas','honorarium') NOT NULL,
  `sppd_uploaded` tinyint(1) DEFAULT 0,
  `pengeluaran_uploaded` tinyint(1) DEFAULT 0,
  `bast_uploaded` tinyint(1) DEFAULT 0,
  `perjanjian_uploaded` tinyint(1) DEFAULT 0,
  `is_complete` tinyint(1) DEFAULT 0,
  `sent_to_verification` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `catatan_verifikasi` text DEFAULT NULL,
  `verified_by` int(11) UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `total_biaya` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: administrasi_files
-- --------------------------------------------------------
CREATE TABLE `administrasi_files` (
  `id` int(11) NOT NULL,
  `kegiatan_petugas_id` int(11) UNSIGNED DEFAULT NULL,
  `jenis_administrasi` varchar(50) DEFAULT NULL,
  `jenis_file` varchar(50) DEFAULT NULL,
  `sub_jenis` varchar(50) NOT NULL,
  `administrasi_id` int(11) DEFAULT NULL,
  `nama_file` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `administrasi_files` (`id`, `kegiatan_petugas_id`, `jenis_administrasi`, `jenis_file`, `sub_jenis`, `administrasi_id`, `nama_file`, `file_path`, `keterangan`, `file_type`, `file_size`, `uploaded_by`, `created_at`) VALUES
(9, 15, 'honorarium', 'dokumen', 'bast', NULL, 'A-DOC001-PROOF_OF_RECEIPT_OF_LETTER_IN_V1_1-fo-xsl_DN2025639026794577298291000.pdf', 'uploads/administrasi/2025/12/bast_1767152131_69549a0387890.pdf', NULL, 'application/pdf', 124193, 1, '2025-12-31 03:35:31'),
(10, 15, 'honorarium', 'dokumen', 'perjanjian', NULL, 'A-DOC001-PROOF_OF_RECEIPT_OF_LETTER_IN_V1_1-fo-xsl_DN2025639026794577298291000.pdf', 'uploads/administrasi/2025/12/perjanjian_1767152131_69549a03888c1.pdf', NULL, 'application/pdf', 124193, 1, '2025-12-31 03:35:31'),
(27, 15, 'perjalanan_dinas', 'dokumen', 'pengeluaran', NULL, 'A-DOC001-PROOF_OF_RECEIPT_OF_LETTER_IN_V1_1-fo-xsl_DN2025639026794577298291000.pdf', 'uploads/administrasi/2025/12/pengeluaran_1767154222_6954a22e26ca2.pdf', NULL, 'application/pdf', 124193, 1, '2025-12-31 04:10:22'),
(28, 15, 'perjalanan_dinas', 'foto', 'foto', NULL, 'STNK 3.jpeg', 'uploads/administrasi/2025/12/foto_1767154222_6954a22e27c8e.jpeg', 'Dokumentasi Kegiatan', 'image/jpeg', 108621, 1, '2025-12-31 04:10:22'),
(29, 15, 'perjalanan_dinas', 'foto', 'foto', NULL, 'STNK 3.jpeg', 'uploads/administrasi/2025/12/foto_1767154222_6954a22e28a25.jpeg', '', 'image/jpeg', 108621, 1, '2025-12-31 04:10:22'),
(30, 15, 'perjalanan_dinas', 'dokumen', 'spd', NULL, 'QUICK_START_GUIDE.md.pdf', 'uploads/administrasi/2025/12/sppd_1767164877_6954cbcd2459c.pdf', NULL, 'application/pdf', 1849957, 1, '2025-12-31 07:07:57'),
(31, 15, 'perjalanan_dinas', 'dokumen', 'pengeluaran', NULL, 'Laporan Inda Sakernas Februari 2026_Muhammad Daffa Taufiq H.pdf', 'uploads/administrasi/2026/01/pengeluaran_1769819292_697d4c9caf811.pdf', NULL, 'application/pdf', 463320, 10, '2026-01-31 00:28:12'),
(32, 15, 'perjalanan_dinas', 'foto', 'foto', NULL, 'WhatsApp Image 2026-01-30 at 07.32.25.jpeg', 'uploads/administrasi/2026/01/foto_1769819292_697d4c9cb72b1.jpeg', 'Dokumentasi Kegiatan', 'image/jpeg', 96798, 10, '2026-01-31 00:28:12'),
(33, 16, 'perjalanan_dinas', 'dokumen', 'pengeluaran', NULL, 'Laporan Inda Sakernas Februari 2026_Muhammad Daffa Taufiq H.pdf', 'uploads/administrasi/2026/01/pengeluaran_1769819422_697d4d1ee9c02.pdf', NULL, 'application/pdf', 463320, 10, '2026-01-31 00:30:22'),
(34, 16, 'perjalanan_dinas', 'foto', 'foto', NULL, 'WhatsApp Image 2026-01-30 at 07.32.25.jpeg', 'uploads/administrasi/2026/01/foto_1769819422_697d4d1eeb3c4.jpeg', 'Dokumentasi Kegiatan', 'image/jpeg', 96798, 10, '2026-01-31 00:30:22'),
(35, 16, 'perjalanan_dinas', 'foto', 'foto', NULL, 'WhatsApp Image 2026-01-30 at 07.32.25.jpeg', 'uploads/administrasi/2026/01/foto_1769819422_697d4d1eebfc3.jpeg', 'Dokumentasi Kegiatan', 'image/jpeg', 96798, 10, '2026-01-31 00:30:22'),
(36, 16, 'perjalanan_dinas', 'dokumen', 'spd', NULL, 'Laporan Inda Sakernas Februari 2026_Muhammad Daffa Taufiq H.pdf', 'uploads/administrasi/2026/01/spd_1769820783_697d526fed814.pdf', NULL, 'application/pdf', 463320, 10, '2026-01-31 00:53:03'),
(37, 16, 'perjalanan_dinas', 'dokumen', 'laporan_perjalanan', NULL, 'Laporan Inda Sakernas Februari 2026_Muhammad Daffa Taufiq H.pdf', 'uploads/administrasi/2026/01/laporan_perjalanan_1769820783_697d526ff03ca.pdf', NULL, 'application/pdf', 463320, 10, '2026-01-31 00:53:03'),
(38, 16, 'perjalanan_dinas', 'dokumen', 'pengeluaran', NULL, 'Laporan Inda Sakernas Februari 2026_Muhammad Daffa Taufiq H.pdf', 'uploads/administrasi/2026/01/pengeluaran_1769820783_697d526ff1645.pdf', NULL, 'application/pdf', 463320, 10, '2026-01-31 00:53:03'),
(39, 16, 'perjalanan_dinas', 'foto', 'foto', NULL, 'WhatsApp Image 2026-01-30 at 07.32.25.jpeg', 'uploads/administrasi/2026/01/foto_1769820783_697d526ff287d.jpeg', 'Dokumentasi Kegiatan', 'image/jpeg', 96798, 10, '2026-01-31 00:53:03'),
(40, 16, 'honorarium', 'dokumen', 'bast', NULL, 'Laporan Inda Sakernas Februari 2026_Muhammad Daffa Taufiq H.pdf', 'uploads/administrasi/2026/01/bast_1769820794_697d527a3d2b7.pdf', NULL, 'application/pdf', 463320, 10, '2026-01-31 00:53:14'),
(41, 16, 'honorarium', 'dokumen', 'perjanjian', NULL, 'Laporan Inda Sakernas Februari 2026_Muhammad Daffa Taufiq H.pdf', 'uploads/administrasi/2026/01/perjanjian_1769820794_697d527a3e670.pdf', NULL, 'application/pdf', 463320, 10, '2026-01-31 00:53:14'),
(42, 17, 'perjalanan_dinas', 'dokumen', 'spd', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/01/spd_1769821013_697d535588486.pdf', NULL, 'application/pdf', 1849957, 10, '2026-01-31 00:56:53'),
(43, 17, 'perjalanan_dinas', 'dokumen', 'laporan_perjalanan', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/01/laporan_perjalanan_1769821013_697d5355899ce.pdf', NULL, 'application/pdf', 1849957, 10, '2026-01-31 00:56:53'),
(44, 17, 'perjalanan_dinas', 'dokumen', 'pengeluaran', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/01/pengeluaran_1769821013_697d53558a28d.pdf', NULL, 'application/pdf', 1849957, 10, '2026-01-31 00:56:53'),
(45, 17, 'perjalanan_dinas', 'foto', 'foto', NULL, 'WhatsApp Image 2026-01-30 at 07.32.25.jpeg', 'uploads/administrasi/2026/01/foto_1769821013_697d53558aa95.jpeg', 'Dokumentasi Kegiatan', 'image/jpeg', 96798, 10, '2026-01-31 00:56:53'),
(46, 17, 'honorarium', 'dokumen', 'bast', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/01/bast_1769821023_697d535f53d42.pdf', NULL, 'application/pdf', 1849957, 10, '2026-01-31 00:57:03'),
(47, 17, 'honorarium', 'dokumen', 'perjanjian', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/01/perjanjian_1769821023_697d535f55136.pdf', NULL, 'application/pdf', 1849957, 10, '2026-01-31 00:57:03'),
(48, 29, 'perjalanan_dinas', 'dokumen', 'spd', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/02/spd_1769981193_697fc509c0cd9.pdf', NULL, 'application/pdf', 1849957, 1, '2026-02-01 21:26:33'),
(49, 29, 'perjalanan_dinas', 'dokumen', 'laporan_perjalanan', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/02/laporan_perjalanan_1769981193_697fc509c33c5.pdf', NULL, 'application/pdf', 1849957, 1, '2026-02-01 21:26:33'),
(50, 29, 'perjalanan_dinas', 'dokumen', 'pengeluaran', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/02/pengeluaran_1769981193_697fc509c4757.pdf', NULL, 'application/pdf', 1849957, 1, '2026-02-01 21:26:33'),
(51, 29, 'perjalanan_dinas', 'foto', 'foto', NULL, 'WhatsApp Image 2026-01-30 at 07.32.25.jpeg', 'uploads/administrasi/2026/02/foto_1769981193_697fc509c5250.jpeg', 'Dokumentasi Kegiatan', 'image/jpeg', 96798, 1, '2026-02-01 21:26:33'),
(52, 29, 'honorarium', 'dokumen', 'bast', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/02/bast_1769981206_697fc51655c27.pdf', NULL, 'application/pdf', 1849957, 1, '2026-02-01 21:26:46'),
(53, 29, 'honorarium', 'dokumen', 'perjanjian', NULL, 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/administrasi/2026/02/perjanjian_1769981206_697fc51656ee8.pdf', NULL, 'application/pdf', 1849957, 1, '2026-02-01 21:26:46');

DELIMITER $$
CREATE TRIGGER `check_perjalanan_complete` AFTER UPDATE ON `administrasi_files` FOR EACH ROW BEGIN
    DECLARE petugas_id INT;
    DECLARE sppd_count INT;
    DECLARE pengeluaran_count INT;

    IF NEW.jenis_administrasi = 'perjalanan_dinas' THEN
        SET petugas_id = NEW.kegiatan_petugas_id;

        SELECT COUNT(*) INTO sppd_count
        FROM administrasi_files
        WHERE kegiatan_petugas_id = petugas_id
          AND jenis_administrasi = 'perjalanan_dinas'
          AND sub_jenis = 'sppd';

        SELECT COUNT(*) INTO pengeluaran_count
        FROM administrasi_files
        WHERE kegiatan_petugas_id = petugas_id
          AND jenis_administrasi = 'perjalanan_dinas'
          AND sub_jenis = 'pengeluaran';

        UPDATE administrasi
        SET sppd_uploaded = IF(sppd_count > 0, 1, 0),
            pengeluaran_uploaded = IF(pengeluaran_count > 0, 1, 0),
            is_complete = IF(sppd_count > 0 AND pengeluaran_count > 0, 1, 0),
            completed_at = IF(sppd_count > 0 AND pengeluaran_count > 0, NOW(), NULL)
        WHERE kegiatan_petugas_id = petugas_id
          AND jenis = 'perjalanan_dinas';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------
-- Tabel: administrasi_kegiatan_files
-- --------------------------------------------------------
CREATE TABLE `administrasi_kegiatan_files` (
  `id` int(11) NOT NULL,
  `kegiatan_jenis_id` int(11) NOT NULL,
  `jenis_dokumen` varchar(60) NOT NULL,
  `innas_user_id` int(11) DEFAULT NULL,
  `nama_file` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabel: dokumen_kegiatan
-- --------------------------------------------------------
CREATE TABLE `dokumen_kegiatan` (
  `id` int(11) NOT NULL,
  `kegiatan_detail_id` int(11) NOT NULL,
  `jenis_dokumen` enum('kak','sk_kpa','daftar_nominatif','form_permintaan') NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dokumen_kegiatan` (`id`, `kegiatan_detail_id`, `jenis_dokumen`, `nama_file`, `file_path`, `file_type`, `file_size`, `uploaded_by`, `created_at`, `updated_at`) VALUES
(1, 3, 'kak', 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/dokumen_kegiatan/2026/01/kak_3_1769824644.pdf', 'application/pdf', 1849957, 10, '2026-01-31 01:57:24', '2026-01-31 01:57:24'),
(2, 3, 'sk_kpa', 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/dokumen_kegiatan/2026/01/sk_kpa_3_1769824644.pdf', 'application/pdf', 1849957, 10, '2026-01-31 01:57:24', '2026-01-31 01:57:24'),
(3, 3, 'daftar_nominatif', 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/dokumen_kegiatan/2026/01/daftar_nominatif_3_1769824644.pdf', 'application/pdf', 1849957, 10, '2026-01-31 01:57:24', '2026-01-31 01:57:24'),
(4, 1, 'kak', 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/dokumen_kegiatan/2026/02/kak_1_1769981244.pdf', 'application/pdf', 1849957, 1, '2026-02-01 21:27:24', '2026-02-01 21:27:24'),
(5, 1, 'sk_kpa', 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/dokumen_kegiatan/2026/02/sk_kpa_1_1769981244.pdf', 'application/pdf', 1849957, 1, '2026-02-01 21:27:24', '2026-02-01 21:27:24'),
(6, 1, 'daftar_nominatif', 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/dokumen_kegiatan/2026/02/daftar_nominatif_1_1769981244.pdf', 'application/pdf', 1849957, 1, '2026-02-01 21:27:24', '2026-02-01 21:27:24'),
(7, 1, 'form_permintaan', 'sppd_1767164877_6954cbcd2459c.pdf', 'uploads/dokumen_kegiatan/2026/02/form_permintaan_1_1769981244.pdf', 'application/pdf', 1849957, 1, '2026-02-01 21:27:24', '2026-02-02 07:04:29'),
(8, 3, 'form_permintaan', 'Surat Pengantar ke BKD Paniai.pdf', 'uploads/dokumen_kegiatan/2026/02/form_permintaan_3_1770015121.pdf', 'application/pdf', 521735, 1, '2026-02-02 06:52:01', '2026-02-02 07:04:29');

-- --------------------------------------------------------
-- Tabel: dokumen_kontrak
-- --------------------------------------------------------
CREATE TABLE `dokumen_kontrak` (
  `id` int(11) NOT NULL,
  `kegiatan_petugas_id` int(11) NOT NULL,
  `jenis_dokumen` enum('spk','bast','surat_tugas','sppd') NOT NULL,
  `nomor_surat` varchar(100) DEFAULT NULL,
  `tanggal_kontrak` date DEFAULT NULL,
  `ppk_id` int(11) DEFAULT NULL,
  `honorarium` decimal(15,2) DEFAULT NULL,
  `periode_mulai` date DEFAULT NULL,
  `periode_selesai` date DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `jumlah_realisasi` int(11) DEFAULT NULL,
  `file_path_docx` varchar(500) DEFAULT NULL,
  `file_path_pdf` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `dokumen_kontrak` (`id`, `kegiatan_petugas_id`, `jenis_dokumen`, `nomor_surat`, `tanggal_kontrak`, `ppk_id`, `honorarium`, `periode_mulai`, `periode_selesai`, `lokasi`, `jumlah_realisasi`, `file_path_docx`, `file_path_pdf`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 29, 'surat_tugas', 'B-002A/96050/KU.350/01/2026', '2026-04-14', 2, NULL, '2026-04-21', '2026-04-27', NULL, NULL, 'C:\\xampp\\htdocs\\repmandat\\documents/surat_tugas/SURTUG_Mitra_Contoh_1_2026-04-21_005806.docx', '', 1, '2026-04-20 05:28:37', '2026-04-20 22:58:06'),
(5, 29, 'spk', 'B-002A/96050/KU.350/01/2026', '2026-04-21', 4, 3000000.00, '2026-04-15', '2026-04-21', NULL, NULL, 'C:\\xampp\\htdocs\\repmandat\\documents/spk/SPK_Mitra_Contoh_1_2026-04-21_011655.docx', '', 1, '2026-04-20 22:58:57', '2026-04-20 23:16:55');

-- --------------------------------------------------------
-- Tabel: kegiatan_administrasi
-- --------------------------------------------------------
CREATE TABLE `kegiatan_administrasi` (
  `id` int(11) NOT NULL,
  `kegiatan_jenis_id` int(11) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `catatan` text DEFAULT NULL,
  `pembayaran_status` enum('belum','sudah') NOT NULL DEFAULT 'belum',
  `pembayaran_at` datetime DEFAULT NULL,
  `pembayaran_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `kegiatan_administrasi` (`id`, `kegiatan_jenis_id`, `data`, `catatan`, `pembayaran_status`, `pembayaran_at`, `pembayaran_by`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 9, '{\"surat_tugas_spd_cakupan\":\"semua\",\"lap_perjalanan_cakupan\":\"semua\",\"stnk_sim_ktp_cakupan\":\"semua\",\"pw_surat_tugas_spd_cakupan\":\"semua\",\"pw_lap_perjalanan_cakupan\":\"semua\",\"pw_stnk_sim_ktp_cakupan\":\"semua\"}', '', 'belum', NULL, NULL, 1, 1, '2026-04-26 07:55:45', '2026-04-26 07:55:45'),
(2, 8, NULL, NULL, 'belum', NULL, NULL, NULL, NULL, '2026-04-26 08:29:15', '2026-04-26 08:33:32');

-- --------------------------------------------------------
-- Tabel: kegiatan_detail
-- --------------------------------------------------------
CREATE TABLE `kegiatan_detail` (
  `id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `nama_kegiatan` varchar(500) NOT NULL,
  `master_id` int(11) DEFAULT NULL,
  `jenis_kegiatan` varchar(255) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `rentang_waktu_mulai` date DEFAULT NULL,
  `rentang_waktu_selesai` date DEFAULT NULL,
  `satuan` varchar(100) DEFAULT NULL,
  `komentar` text DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `output` varchar(255) DEFAULT NULL,
  `komponen` varchar(255) DEFAULT NULL,
  `honor_satuan` decimal(15,2) DEFAULT NULL COMMENT 'Honor per satuan untuk mitra (Rp)',
  `edited_by` int(11) DEFAULT NULL,
  `penanggung_jawab_user_id` int(11) DEFAULT NULL,
  `bast_berbarengan` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Untuk Pendataan: 1=BAST gabungan dgn Updating/Listing, 0=terpisah',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status_pembayaran` enum('belum_lunas','lunas') DEFAULT 'belum_lunas',
  `catatan_pembayaran` text DEFAULT NULL,
  `pembayaran_updated_by` int(11) DEFAULT NULL,
  `pembayaran_updated_at` timestamp NULL DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `catatan_operator_tim` text DEFAULT NULL,
  `catatan_kepala` text DEFAULT NULL,
  `catatan_ppk` text DEFAULT NULL,
  `catatan_bendahara` text DEFAULT NULL,
  `operator_tim_verified_by` int(11) UNSIGNED DEFAULT NULL,
  `operator_tim_verified_at` datetime DEFAULT NULL,
  `kepala_verified_by` int(11) UNSIGNED DEFAULT NULL,
  `kepala_verified_at` datetime DEFAULT NULL,
  `ppk_verified_by` int(11) UNSIGNED DEFAULT NULL,
  `ppk_verified_at` datetime DEFAULT NULL,
  `bendahara_verified_by` int(11) UNSIGNED DEFAULT NULL,
  `bendahara_verified_at` datetime DEFAULT NULL,
  `admin_override` tinyint(1) DEFAULT 0,
  `admin_override_by` int(11) UNSIGNED DEFAULT NULL,
  `admin_override_at` datetime DEFAULT NULL,
  `sent_to_kepala_at` datetime DEFAULT NULL,
  `sent_by` int(11) UNSIGNED DEFAULT NULL,
  `sk_kpa_gabung_po` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'SK KPA Pendataan+Pengolahan digabung (1=ya, 0=tidak)',
  `periode` enum('Tahunan','Semesteran','Triwulanan','Bulanan') NOT NULL DEFAULT 'Tahunan' COMMENT 'Periode kegiatan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kegiatan_detail` (`id`, `team_id`, `nama_kegiatan`, `master_id`, `jenis_kegiatan`, `tanggal_mulai`, `tanggal_selesai`, `rentang_waktu_mulai`, `rentang_waktu_selesai`, `satuan`, `komentar`, `program`, `output`, `komponen`, `honor_satuan`, `edited_by`, `penanggung_jawab_user_id`, `bast_berbarengan`, `created_at`, `updated_at`, `status_pembayaran`, `catatan_pembayaran`, `pembayaran_updated_by`, `pembayaran_updated_at`, `paid_at`, `completed_at`, `catatan_operator_tim`, `catatan_kepala`, `catatan_ppk`, `catatan_bendahara`, `operator_tim_verified_by`, `operator_tim_verified_at`, `kepala_verified_by`, `kepala_verified_at`, `ppk_verified_by`, `ppk_verified_at`, `bendahara_verified_by`, `bendahara_verified_at`, `admin_override`, `admin_override_by`, `admin_override_at`, `sent_to_kepala_at`, `sent_by`, `sk_kpa_gabung_po`, `periode`) VALUES
(1, 3, 'Sensus Ekonomi 2026', NULL, 'Updating', NULL, NULL, '2026-01-01', '2026-01-31', 'Dok', NULL, '', '', '', 10000.00, 1, NULL, 0, '2025-12-27 02:25:58', '2026-04-14 14:27:32', 'belum_lunas', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 'Tahunan'),
(2, 2, 'Survei Sosial Ekonomi Nasional', NULL, 'Pendataan', NULL, NULL, '2025-01-15', '2025-01-24', 'Dok', '', '', '', '', 10000.00, 10, NULL, 0, '2025-12-27 02:25:58', '2026-01-30 01:15:16', 'belum_lunas', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 'Tahunan'),
(3, 3, 'Updating Direktori Perusahaan', NULL, 'Updating', NULL, NULL, '2026-01-08', '2026-01-23', 'Dok', '', '', '', '', 40000.00, 10, NULL, 0, '2025-12-27 02:25:58', '2026-01-29 21:20:34', 'belum_lunas', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 'Tahunan'),
(4, 2, 'Susenas Maret 2025', NULL, 'Pendataan', NULL, NULL, '2026-01-11', '2026-01-28', 'SLS', '', '054.01.GG', '2905.BMN.001', '001', NULL, 10, NULL, 0, '2026-01-11 09:06:39', '2026-01-11 09:06:39', 'belum_lunas', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 'Tahunan'),
(6, 2, 'Sakernas Februari 2026', NULL, 'Pendataan', NULL, NULL, '2026-03-15', '2026-03-28', 'Dokumen (Dok)', NULL, NULL, NULL, NULL, 1000000.00, 1, NULL, 0, '2026-04-14 15:18:14', '2026-04-25 16:27:27', 'belum_lunas', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 'Semesteran');

-- --------------------------------------------------------
-- Tabel: kegiatan_innas
-- --------------------------------------------------------
CREATE TABLE `kegiatan_innas` (
  `id` int(11) NOT NULL,
  `kegiatan_jenis_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `kegiatan_innas` (`id`, `kegiatan_jenis_id`, `user_id`, `created_at`) VALUES
(1, 8, 7, '2026-04-14 15:18:14'),
(2, 8, 6, '2026-04-14 15:18:14');

-- --------------------------------------------------------
-- Tabel: kegiatan_jenis
-- --------------------------------------------------------
CREATE TABLE `kegiatan_jenis` (
  `id` int(11) NOT NULL,
  `kegiatan_id` int(11) NOT NULL,
  `jenis` enum('Pelatihan/Briefing','Pelatihan','Updating/Listing','Pemutakhiran','Listing','Pendataan','Pencacahan','Pengolahan','Perjalanan Dinas') NOT NULL,
  `lokasi` varchar(50) NOT NULL DEFAULT '',
  `tipe_innas` varchar(10) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `output` varchar(255) DEFAULT NULL,
  `komponen` varchar(255) DEFAULT NULL,
  `honor_satuan` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `kegiatan_jenis` (`id`, `kegiatan_id`, `jenis`, `lokasi`, `tipe_innas`, `satuan`, `tanggal_mulai`, `tanggal_selesai`, `program`, `output`, `komponen`, `honor_satuan`, `created_at`) VALUES
(1, 1, 'Pemutakhiran', '', NULL, 'Dok', '2026-01-01', '2026-01-31', '', '', '', 10000.00, '2026-04-14 08:48:01'),
(2, 2, 'Pendataan', '', NULL, 'Dok', '2025-01-15', '2025-01-24', '', '', '', 10000.00, '2026-04-14 08:48:01'),
(3, 3, 'Pemutakhiran', '', NULL, 'Dok', '2026-01-08', '2026-01-23', '', '', '', 40000.00, '2026-04-14 08:48:01'),
(4, 4, 'Pendataan', '', NULL, 'SLS', '2026-01-11', '2026-01-28', '054.01.GG', '2905.BMN.001', '001', 0.00, '2026-04-14 08:48:01'),
(8, 6, '', '', NULL, NULL, '2026-03-01', '2026-03-07', NULL, NULL, NULL, 0.00, '2026-04-14 15:18:14'),
(9, 6, 'Pemutakhiran', '', NULL, 'BS', '2026-03-08', '2026-03-14', '2896.BMA.004', '052', '524113', 150000.00, '2026-04-14 15:18:14'),
(10, 6, 'Pendataan', '', NULL, 'dokumen', '2026-03-15', '2026-03-28', '2896.BMA.004', '052', '', 10000.00, '2026-04-14 15:18:14');

-- --------------------------------------------------------
-- Tabel: kegiatan_petugas
-- --------------------------------------------------------
CREATE TABLE `kegiatan_petugas` (
  `id` int(11) NOT NULL,
  `kegiatan_detail_id` int(11) NOT NULL,
  `petugas_id` int(11) NOT NULL,
  `petugas_source` enum('users','mitra') NOT NULL DEFAULT 'users',
  `peran` enum('PML','PPL','Supervisi') NOT NULL DEFAULT 'PPL',
  `pml_id` int(11) DEFAULT NULL COMMENT 'ID PML yang membawahi PPL ini (jika peran=PPL)',
  `realisasi` decimal(10,2) DEFAULT 0.00 COMMENT 'Realisasi individual petugas',
  `non_respon` int(11) NOT NULL DEFAULT 0,
  `target` decimal(10,2) DEFAULT 0.00 COMMENT 'Target individual petugas',
  `keterangan` text DEFAULT NULL COMMENT 'Catatan/komentar dari kepala',
  `asal` varchar(100) DEFAULT '',
  `tujuan` varchar(100) DEFAULT '',
  `alat_angkutan` varchar(100) DEFAULT 'Kendaraan Umum',
  `no_bast` varchar(50) DEFAULT '',
  `no_spk` varchar(50) DEFAULT '',
  `no_surat_tugas` varchar(50) DEFAULT '',
  `no_sppd` varchar(100) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tanggal_surat` date DEFAULT NULL,
  `periode_mulai` date DEFAULT NULL,
  `periode_selesai` date DEFAULT NULL,
  `periode_ke` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Periode ke-N: NULL=Tahunan, 1-2=Semesteran, 1-4=Triwulanan, 1-12=Bulanan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kegiatan_petugas` (`id`, `kegiatan_detail_id`, `petugas_id`, `petugas_source`, `peran`, `pml_id`, `realisasi`, `non_respon`, `target`, `keterangan`, `asal`, `tujuan`, `alat_angkutan`, `no_bast`, `no_spk`, `no_surat_tugas`, `no_sppd`, `created_at`, `updated_at`, `tanggal_surat`, `periode_mulai`, `periode_selesai`, `periode_ke`) VALUES
(15, 3, 10, 'users', 'PML', NULL, 100.00, 0, 100.00, '', '', '', 'Kendaraan Umum', '', '', '', '', '2025-12-29 01:16:02', '2026-02-01 05:30:32', NULL, NULL, NULL, NULL),
(16, 3, 1, 'mitra', 'PPL', 15, 50.00, 0, 50.00, '', '', '', 'Kendaraan Umum', '', '', '', '', '2025-12-29 01:16:02', '2026-02-01 05:30:32', NULL, NULL, NULL, NULL),
(17, 3, 2, 'mitra', 'PPL', 15, 50.00, 0, 50.00, '', '', '', 'Kendaraan Umum', '', '', '', '', '2025-12-29 01:16:02', '2026-02-01 05:20:04', NULL, NULL, NULL, NULL),
(28, 1, 9, 'users', 'PML', NULL, 0.00, 0, 300.00, '', 'Enarotali', '', 'Kendaraan Umum', '', '', '', '', '2026-01-30 11:40:05', '2026-01-30 11:40:05', NULL, NULL, NULL, NULL),
(29, 1, 1, 'mitra', 'PPL', 28, 0.00, 0, 300.00, '', 'Enarotali', '', 'Kendaraan Umum', '', '', '', '', '2026-01-30 11:40:05', '2026-01-30 11:40:05', NULL, NULL, NULL, NULL),
(31, 6, 2, 'users', 'Supervisi', NULL, 0.00, 0, 0.00, '', 'Enarotali', '', 'Kendaraan Umum', '', '', '', '', '2026-04-15 01:05:40', '2026-04-15 01:05:40', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Tabel: kegiatan_rincian
-- --------------------------------------------------------
CREATE TABLE `kegiatan_rincian` (
  `id` int(11) NOT NULL,
  `kegiatan_jenis_id` int(11) NOT NULL,
  `nama_rincian` varchar(255) NOT NULL,
  `satuan` varchar(100) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `honor_satuan` decimal(15,2) DEFAULT NULL,
  `urutan` tinyint(3) UNSIGNED DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabel: mitra
-- --------------------------------------------------------
CREATE TABLE `mitra` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `wilayah_kerja` enum('Paniai','Intan Jaya','Deiyai') DEFAULT 'Paniai',
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `mitra` (`id`, `nama`, `nik`, `alamat`, `wilayah_kerja`, `email`, `password`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Mitra Contoh 1', '1234567890123456', 'Jl. Contoh No. 1, Paniai', 'Paniai', 'mitra1', '9502', 'aktif', '2025-12-27 02:25:28', '2025-12-27 02:25:28'),
(2, 'Mitra Contoh 2', '9876543210987654', 'Jl. Contoh No. 2, Paniai', 'Paniai', 'mitra2', '9502', 'aktif', '2025-12-27 02:25:28', '2025-12-27 02:25:28');

-- --------------------------------------------------------
-- Tabel: ppk_data
-- --------------------------------------------------------
CREATE TABLE `ppk_data` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `jabatan` varchar(255) DEFAULT 'Pejabat Pembuat Komitmen',
  `unit_kerja` varchar(255) DEFAULT 'BPS Kabupaten Paniai',
  `alamat_unit` varchar(500) DEFAULT 'Madi',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabel: roles
-- --------------------------------------------------------
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'admin', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(2, 'Kepala', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(3, 'Kasubbag', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(4, 'PPK', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(5, 'Bendahara', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(6, 'Operator', '2026-04-15 12:12:33', '2026-04-15 12:12:33');

-- --------------------------------------------------------
-- Tabel: teams
-- --------------------------------------------------------
CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `warna` varchar(7) DEFAULT '#6c757d',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `teams` (`id`, `name`, `description`, `warna`, `created_at`, `updated_at`) VALUES
(1, 'Tim Sosial', NULL, '#0ea5e9', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(2, 'Tim Produksi', NULL, '#22c55e', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(3, 'Tim Distribusi', NULL, '#f97316', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(4, 'Tim Nerwilis', NULL, '#a855f7', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(5, 'Tim IPDS', NULL, '#ef4444', '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(6, 'Tim TU', 'Tim Tata Usaha', '#d3cd1d', '2026-04-21 04:10:56', '2026-04-21 06:50:06');

-- --------------------------------------------------------
-- Tabel: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gelar_belakang` varchar(100) DEFAULT NULL,
  `gelar_depan` varchar(50) DEFAULT NULL,
  `nip` varchar(20) DEFAULT NULL,
  `jabatan` varchar(150) DEFAULT NULL,
  `golongan` varchar(20) DEFAULT NULL,
  `pangkat` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `gelar_belakang`, `gelar_depan`, `nip`, `jabatan`, `golongan`, `pangkat`, `email`, `password`, `role_id`, `team_id`, `created_at`, `updated_at`) VALUES
(1, 'administrator', NULL, NULL, NULL, NULL, NULL, NULL, 'kabupatenpaniai.bps', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 1, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(2, 'Khaerul Umam', 'SST, M.Si', NULL, '198402012008011010', 'Kepala', 'IV/a', 'Pembina', 'khaerul_umam', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 2, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(3, 'John Marselino Alfonso Akwan', 'SST', NULL, '199403292016021001', 'Kepala Subbagian Umum', 'III/c', 'Penata', 'john.alfonso', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 3, NULL, '2026-04-15 12:12:33', '2026-04-21 03:41:53'),
(4, 'Christin Septiana', 'SST', NULL, '199509242018022001', 'Statistisi Ahli Pertama', 'III/b', 'Penata Muda Tk. I', 'christin.septiana', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 4, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(5, 'Calvino Pablo Dinova', 'S.Tr.Stat.', NULL, '200111262023101003', 'Statistisi Ahli Pertama', 'III/a', 'Penata Muda', 'pablo.dinova', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 5, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(6, 'Daniel Butar Butar', 'A.Md.Stat.', NULL, '200105112022011001', 'Statistisi Pelaksana/Terampil', 'II/d', 'Pengatur Tk. I', 'daniel.butarbutar', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 1, '2026-04-15 12:12:33', '2026-04-21 04:11:39'),
(7, 'Azmi Faisal', 'S.Tr.Stat.', NULL, '200007232023021005', 'Statistisi Ahli Pertama', 'III/a', 'Penata Muda', 'azmifaisal', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 1, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(8, 'Tarsisius Bandur', NULL, NULL, '199401142025211028', 'Pelaksana', 'I/c', 'Juru', 'tarsisiusb-pppk', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 2, '2026-04-15 12:12:33', '2026-04-21 04:11:32'),
(9, 'Muhammad Almas Yafi\'', 'S.Tr.Stat.', NULL, '200108182024121001', 'Statistisi Ahli Pertama', 'III/a', 'Penata Muda', 'almas.yafi', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 3, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(10, 'Rezky Maharani', 'A.Md.Stat.', NULL, '200303012026032001', 'Pelaksana', 'II/c', 'Pengatur', 'rezkymaharani', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 6, '2026-04-15 12:12:33', '2026-04-21 04:11:55'),
(11, 'Miftachul Rachman Dinda', 'S.Tr.Stat.', NULL, '199712122021041002', 'Statistisi Ahli Pertama', 'III/b', 'Penata Muda Tk. I', 'rachman.dinda', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 1, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(12, 'Amos Holombau', NULL, NULL, '198410022009111001', 'Pelaksana', 'II/d', 'Pengatur Tk. I', 'amos.holombau', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(13, 'Yanuarius Madai', 'S.E.', NULL, '198312122010031001', 'Pelaksana', 'III/d', 'Penata Tk. I', 'ymadai', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(14, 'Isayas Kudiai', NULL, NULL, '197903022002121002', 'Pelaksana', 'III/b', 'Penata Muda Tk. I', 'isayas', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(15, 'Yance Mbogou Belau', NULL, NULL, '197310132006041012', 'Pelaksana', 'III/a', 'Penata Muda', 'yance.belau', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(16, 'Dian Nastiti Indrayanti', 'S.Stat.', NULL, '199310292019032001', 'Statistisi Ahli Pertama', 'III/b', 'Penata Muda Tk. I', 'dian.nastiti', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 2, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(17, 'Muhammad Daffa Taufiq Hadikara', 'S.Tr.Stat.', NULL, '200001112023101003', 'Pranata Komputer Ahli Pertama', 'III/a', 'Penata Muda', 'daffa.taufiq', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 2, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(18, 'Wardiman Sianturi', 'S.Tr.Stat.', NULL, '199608232022011001', 'Statistisi Ahli Pertama', 'III/a', 'Penata Muda', 'wardiman.sianturi', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 4, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(19, 'Krisbana Togar Sianturi', 'S.Tr.Stat.', NULL, '200007042023021004', 'Pranata Komputer Ahli Pertama', 'III/a', 'Penata Muda', 'sianturi.bana', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, 5, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(20, 'Andi Iriadi Cahya', 'SST', NULL, '199404052017011001', 'Pelaksana', 'III/c', 'Penata', 'andi.cahya', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(21, 'Deviani Dyah Widyastuti Ramandey', 'S.Tr.Stat.', NULL, '199612282019122001', 'Pelaksana', 'III/b', 'Penata Muda Tk. I', 'deviani.dyah', '$2y$10$/R0mLbrqFUMgjnPJ1dge2.47LsKusUOZFkR72jWTrULtwbsr1O.5i', 6, NULL, '2026-04-15 12:12:33', '2026-04-15 12:12:33');

-- --------------------------------------------------------
-- Tabel: user_teams
-- --------------------------------------------------------
CREATE TABLE `user_teams` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0 COMMENT '1 = tim utama, 0 = tim tambahan',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_teams` (`id`, `user_id`, `team_id`, `is_primary`, `created_at`, `updated_at`) VALUES
(2, 7, 1, 1, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(3, 7, 3, 0, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(4, 11, 1, 1, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(5, 11, 3, 0, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(6, 16, 2, 1, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(7, 17, 2, 1, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(8, 17, 5, 0, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(10, 9, 3, 1, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(11, 9, 4, 0, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(12, 9, 5, 0, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(13, 18, 4, 1, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(14, 19, 5, 1, '2026-04-15 12:12:33', '2026-04-15 12:12:33'),
(15, 3, 6, 1, '2026-04-21 04:10:56', '2026-04-21 04:10:56'),
(16, 4, 6, 1, '2026-04-21 04:10:56', '2026-04-21 04:10:56'),
(17, 5, 6, 1, '2026-04-21 04:10:56', '2026-04-21 04:10:56'),
(18, 8, 2, 1, '2026-04-21 04:11:32', '2026-04-21 04:11:32'),
(19, 8, 6, 0, '2026-04-21 04:11:32', '2026-04-21 04:11:32'),
(20, 6, 1, 1, '2026-04-21 04:11:39', '2026-04-21 04:11:39'),
(21, 6, 6, 0, '2026-04-21 04:11:39', '2026-04-21 04:11:39'),
(22, 10, 6, 1, '2026-04-21 04:11:55', '2026-04-21 04:11:55');

-- --------------------------------------------------------
-- Tabel: verifikasi_administrasi
-- --------------------------------------------------------
CREATE TABLE `verifikasi_administrasi` (
  `id` int(11) NOT NULL,
  `kegiatan_petugas_id` int(11) NOT NULL COMMENT 'FK ke kegiatan_petugas',
  `kegiatan_detail_id` int(11) DEFAULT NULL,
  `status` enum('pending','pending_operator_tim','approved_operator_tim','rejected_operator_tim','pending_kepala','approved_kepala','rejected_kepala','verified_ppk','approved_ppk','rejected_ppk','verified_bendahara','approved_bendahara','rejected_bendahara') DEFAULT 'pending',
  `verified_by_ppk` int(11) DEFAULT NULL COMMENT 'FK ke users - PPK yang verifikasi',
  `verified_at_ppk` timestamp NULL DEFAULT NULL COMMENT 'Waktu verifikasi PPK',
  `catatan_ppk` text DEFAULT NULL COMMENT 'Catatan dari PPK',
  `verified_by_bendahara` int(11) DEFAULT NULL COMMENT 'FK ke users - Bendahara yang verifikasi',
  `verified_at_bendahara` timestamp NULL DEFAULT NULL COMMENT 'Waktu verifikasi Bendahara',
  `catatan_bendahara` text DEFAULT NULL COMMENT 'Catatan dari Bendahara',
  `rejected_by` int(11) DEFAULT NULL COMMENT 'FK ke users - siapa yang reject',
  `rejected_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu reject',
  `rejection_reason` text DEFAULT NULL COMMENT 'Alasan reject',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `administrasi_submitted_at` timestamp NULL DEFAULT NULL,
  `administrasi_submitted_by` int(11) DEFAULT NULL,
  `perjalanan_checked` tinyint(1) DEFAULT 0,
  `honorarium_checked` tinyint(1) DEFAULT 0,
  `catatan_operator_tim` text DEFAULT NULL,
  `verified_operator_tim_at` datetime DEFAULT NULL,
  `verified_kepala_at` datetime DEFAULT NULL,
  `verified_ppk_at` datetime DEFAULT NULL,
  `verified_bendahara_at` datetime DEFAULT NULL,
  `sent_to_kepala` tinyint(1) DEFAULT 0,
  `sent_to_kepala_at` timestamp NULL DEFAULT NULL,
  `approved_by_kepala` int(11) DEFAULT NULL,
  `approved_at_kepala` timestamp NULL DEFAULT NULL,
  `catatan_kepala` text DEFAULT NULL,
  `sent_to_ppk` tinyint(1) DEFAULT 0,
  `sent_to_ppk_at` timestamp NULL DEFAULT NULL,
  `approved_by_ppk` int(11) DEFAULT NULL,
  `approved_at_ppk` timestamp NULL DEFAULT NULL,
  `sent_to_bendahara` tinyint(1) DEFAULT 0,
  `sent_to_bendahara_at` timestamp NULL DEFAULT NULL,
  `approved_by_bendahara` int(11) DEFAULT NULL,
  `approved_at_bendahara` timestamp NULL DEFAULT NULL,
  `catatan_verifikasi` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel untuk workflow verifikasi administrasi oleh PPK dan Bendahara';

INSERT INTO `verifikasi_administrasi` (`id`, `kegiatan_petugas_id`, `kegiatan_detail_id`, `status`, `verified_by_ppk`, `verified_at_ppk`, `catatan_ppk`, `verified_by_bendahara`, `verified_at_bendahara`, `catatan_bendahara`, `rejected_by`, `rejected_at`, `rejection_reason`, `created_at`, `updated_at`, `administrasi_submitted_at`, `administrasi_submitted_by`, `perjalanan_checked`, `honorarium_checked`, `catatan_operator_tim`, `verified_operator_tim_at`, `verified_kepala_at`, `verified_ppk_at`, `verified_bendahara_at`, `sent_to_kepala`, `sent_to_kepala_at`, `approved_by_kepala`, `approved_at_kepala`, `catatan_kepala`, `sent_to_ppk`, `sent_to_ppk_at`, `approved_by_ppk`, `approved_at_ppk`, `sent_to_bendahara`, `sent_to_bendahara_at`, `approved_by_bendahara`, `approved_at_bendahara`, `catatan_verifikasi`, `verified_by`, `verified_at`) VALUES
(6, 15, 3, '', NULL, NULL, NULL, NULL, NULL, 'ss', NULL, NULL, NULL, '2026-02-02 06:51:05', '2026-02-02 08:25:52', '2026-02-02 06:51:05', NULL, 0, 0, NULL, '2026-02-02 15:24:58', NULL, NULL, '2026-02-02 15:25:52', 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 16, 3, '', NULL, NULL, NULL, NULL, NULL, 'ss', NULL, NULL, NULL, '2026-02-02 06:51:09', '2026-02-02 08:25:52', '2026-02-02 06:51:09', NULL, 0, 0, NULL, '2026-02-02 15:24:58', NULL, NULL, '2026-02-02 15:25:52', 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 17, 3, '', NULL, NULL, NULL, NULL, NULL, 'ss', NULL, NULL, NULL, '2026-02-02 06:51:12', '2026-02-02 08:25:52', '2026-02-02 06:51:12', NULL, 0, 0, NULL, '2026-02-02 15:24:58', NULL, NULL, '2026-02-02 15:25:52', 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Tabel: verifikasi_kegiatan
-- --------------------------------------------------------
CREATE TABLE `verifikasi_kegiatan` (
  `id` int(11) NOT NULL,
  `kegiatan_detail_id` int(11) NOT NULL,
  `status` enum('pending_operator_tim','approved_operator_tim','rejected_operator_tim','pending_kepala','approved_kepala','rejected_kepala','pending_ppk','approved_ppk','rejected_ppk','pending_bendahara','approved_bendahara','rejected_bendahara','completed') DEFAULT 'pending_operator_tim',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_operator_tim_by` int(11) DEFAULT NULL,
  `verified_operator_tim_at` timestamp NULL DEFAULT NULL,
  `catatan_operator_tim` text DEFAULT NULL,
  `sent_to_kepala_at` timestamp NULL DEFAULT NULL,
  `verified_kepala_by` int(11) DEFAULT NULL,
  `verified_kepala_at` timestamp NULL DEFAULT NULL,
  `catatan_kepala` text DEFAULT NULL,
  `sent_to_ppk_at` timestamp NULL DEFAULT NULL,
  `verified_ppk_by` int(11) DEFAULT NULL,
  `verified_ppk_at` timestamp NULL DEFAULT NULL,
  `catatan_ppk` text DEFAULT NULL,
  `sent_to_bendahara_at` timestamp NULL DEFAULT NULL,
  `verified_bendahara_by` int(11) DEFAULT NULL,
  `verified_bendahara_at` timestamp NULL DEFAULT NULL,
  `catatan_bendahara` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: verifikasi_log
-- --------------------------------------------------------
CREATE TABLE `verifikasi_log` (
  `id` int(11) NOT NULL,
  `kegiatan_detail_id` int(11) NOT NULL,
  `level` varchar(50) NOT NULL COMMENT 'operator_tim, kepala, ppk, bendahara, admin',
  `action` varchar(50) NOT NULL COMMENT 'approve, reject, reset, send',
  `user_id` int(11) NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Stand-in struktur untuk VIEW v_administrasi_full
-- --------------------------------------------------------
CREATE TABLE `v_administrasi_full` (
);

-- --------------------------------------------------------
-- Stand-in struktur untuk VIEW v_kegiatan_summary
-- --------------------------------------------------------
CREATE TABLE `v_kegiatan_summary` (
);

-- --------------------------------------------------------
-- Stand-in struktur untuk VIEW v_users_lengkap
-- --------------------------------------------------------
CREATE TABLE `v_users_lengkap` (
  `id` int(11),
  `name` varchar(255),
  `gelar_depan` varchar(50),
  `gelar_belakang` varchar(100),
  `nama_lengkap` varchar(408),
  `nip` varchar(20),
  `pangkat` varchar(100),
  `golongan` varchar(20),
  `jabatan` varchar(150),
  `email` varchar(150),
  `role_id` int(11),
  `role_name` varchar(100),
  `team_id` int(11),
  `team_name` varchar(255)
);

-- --------------------------------------------------------
-- View: v_administrasi_full
-- (Dirancang ulang untuk schema administrasi yang berlaku)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `v_administrasi_full`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_administrasi_full` AS
SELECT
  `a`.`id`                     AS `id`,
  `kp`.`kegiatan_detail_id`    AS `kegiatan_detail_id`,
  `kd`.`nama_kegiatan`         AS `nama_kegiatan`,
  `kd`.`team_id`               AS `team_id`,
  `t`.`name`                   AS `team_name`,
  `a`.`jenis`                  AS `jenis`,
  `a`.`is_complete`            AS `is_complete`,
  `a`.`sent_to_verification`   AS `sent_to_verification`,
  `a`.`total_biaya`            AS `total_biaya`,
  `a`.`created_at`             AS `created_at`,
  `a`.`updated_at`             AS `updated_at`
FROM `administrasi` `a`
LEFT JOIN `kegiatan_petugas` `kp` ON `kp`.`id` = `a`.`kegiatan_petugas_id`
LEFT JOIN `kegiatan_detail` `kd`  ON `kd`.`id` = `kp`.`kegiatan_detail_id`
LEFT JOIN `teams` `t`             ON `t`.`id`  = `kd`.`team_id`;

-- --------------------------------------------------------
-- View: v_kegiatan_summary
-- (Dirancang ulang untuk schema kegiatan_detail yang berlaku)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `v_kegiatan_summary`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_kegiatan_summary` AS
SELECT
  `kd`.`id`                      AS `id`,
  `kd`.`nama_kegiatan`           AS `nama_kegiatan`,
  `kd`.`jenis_kegiatan`          AS `jenis_kegiatan`,
  `kd`.`rentang_waktu_mulai`     AS `rentang_waktu_mulai`,
  `kd`.`rentang_waktu_selesai`   AS `rentang_waktu_selesai`,
  `kd`.`satuan`                  AS `satuan`,
  `kd`.`komentar`                AS `komentar`,
  `t`.`name`                     AS `team_name`,
  `t`.`id`                       AS `team_id`,
  `kd`.`status_pembayaran`       AS `status_pembayaran`,
  CASE
    WHEN `kd`.`status_pembayaran` = 'lunas' THEN 'Selesai'
    ELSE 'Pending'
  END                            AS `status_kegiatan`,
  TO_DAYS(`kd`.`rentang_waktu_selesai`) - TO_DAYS(CURDATE()) AS `days_remaining`,
  `kd`.`created_at`              AS `created_at`,
  `kd`.`updated_at`              AS `updated_at`
FROM `kegiatan_detail` `kd`
LEFT JOIN `teams` `t` ON `t`.`id` = `kd`.`team_id`;

-- --------------------------------------------------------
-- View: v_users_lengkap
-- --------------------------------------------------------
DROP TABLE IF EXISTS `v_users_lengkap`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_users_lengkap` AS
SELECT
  `u`.`id`             AS `id`,
  `u`.`name`           AS `name`,
  `u`.`gelar_depan`    AS `gelar_depan`,
  `u`.`gelar_belakang` AS `gelar_belakang`,
  CONCAT(
    IFNULL(CONCAT(`u`.`gelar_depan`, ' '), ''),
    `u`.`name`,
    IFNULL(CONCAT(', ', `u`.`gelar_belakang`), '')
  )                    AS `nama_lengkap`,
  `u`.`nip`            AS `nip`,
  `u`.`pangkat`        AS `pangkat`,
  `u`.`golongan`       AS `golongan`,
  `u`.`jabatan`        AS `jabatan`,
  `u`.`email`          AS `email`,
  `u`.`role_id`        AS `role_id`,
  `r`.`name`           AS `role_name`,
  `u`.`team_id`        AS `team_id`,
  `t`.`name`           AS `team_name`
FROM `users` `u`
LEFT JOIN `roles` `r` ON `r`.`id` = `u`.`role_id`
LEFT JOIN `teams` `t` ON `t`.`id` = `u`.`team_id`;

-- ============================================================
-- PRIMARY KEYS, UNIQUE KEYS, INDEXES
-- ============================================================

ALTER TABLE `administrasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_kegiatan_jenis` (`kegiatan_petugas_id`,`jenis`);

ALTER TABLE `administrasi_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `administrasi_id` (`administrasi_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_kegiatan_petugas` (`kegiatan_petugas_id`),
  ADD KEY `idx_jenis_administrasi` (`jenis_administrasi`),
  ADD KEY `idx_jenis_file` (`jenis_file`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`);

ALTER TABLE `administrasi_kegiatan_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kj` (`kegiatan_jenis_id`),
  ADD KEY `idx_jd` (`jenis_dokumen`);

ALTER TABLE `dokumen_kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dokumen` (`kegiatan_detail_id`,`jenis_dokumen`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_dokumen_kegiatan` (`kegiatan_detail_id`);

ALTER TABLE `dokumen_kontrak`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dokumen` (`kegiatan_petugas_id`,`jenis_dokumen`);

ALTER TABLE `kegiatan_administrasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_adm_kj` (`kegiatan_jenis_id`);

ALTER TABLE `kegiatan_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `edited_by` (`edited_by`),
  ADD KEY `idx_dates` (`rentang_waktu_mulai`,`rentang_waktu_selesai`),
  ADD KEY `idx_jenis` (`jenis_kegiatan`),
  ADD KEY `idx_kegiatan_pembayaran` (`status_pembayaran`),
  ADD KEY `fk_kd_pj` (`penanggung_jawab_user_id`);

ALTER TABLE `kegiatan_innas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_innas` (`kegiatan_jenis_id`,`user_id`),
  ADD KEY `idx_kj` (`kegiatan_jenis_id`),
  ADD KEY `idx_user` (`user_id`);

ALTER TABLE `kegiatan_jenis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_keg_jenis` (`kegiatan_id`,`jenis`,`lokasi`),
  ADD KEY `idx_keg` (`kegiatan_id`);

ALTER TABLE `kegiatan_petugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kegiatan_detail_id` (`kegiatan_detail_id`),
  ADD KEY `petugas_idx` (`petugas_id`,`petugas_source`),
  ADD KEY `pml_id` (`pml_id`),
  ADD KEY `idx_peran` (`peran`);

ALTER TABLE `kegiatan_rincian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kegiatan_jenis_id` (`kegiatan_jenis_id`);

ALTER TABLE `mitra`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`),
  ADD KEY `idx_status` (`status`);

ALTER TABLE `ppk_data`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `team_id` (`team_id`);

ALTER TABLE `user_teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_team` (`user_id`,`team_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `idx_primary` (`is_primary`);

ALTER TABLE `verifikasi_administrasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_kegiatan` (`kegiatan_petugas_id`),
  ADD KEY `verified_by_ppk` (`verified_by_ppk`),
  ADD KEY `verified_by_bendahara` (`verified_by_bendahara`),
  ADD KEY `rejected_by` (`rejected_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `administrasi_submitted_by` (`administrasi_submitted_by`),
  ADD KEY `idx_kegiatan_detail` (`kegiatan_detail_id`);

ALTER TABLE `verifikasi_kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kegiatan_detail_id` (`kegiatan_detail_id`),
  ADD KEY `verified_operator_tim_by` (`verified_operator_tim_by`),
  ADD KEY `verified_kepala_by` (`verified_kepala_by`),
  ADD KEY `verified_ppk_by` (`verified_ppk_by`),
  ADD KEY `verified_bendahara_by` (`verified_bendahara_by`),
  ADD KEY `idx_verifikasi_kegiatan_status` (`status`);

ALTER TABLE `verifikasi_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kegiatan_detail_id` (`kegiatan_detail_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_action` (`action`);

-- ============================================================
-- AUTO_INCREMENT
-- ============================================================

ALTER TABLE `administrasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `administrasi_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

ALTER TABLE `administrasi_kegiatan_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `dokumen_kegiatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `dokumen_kontrak`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `kegiatan_administrasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `kegiatan_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `kegiatan_innas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `kegiatan_jenis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

ALTER TABLE `kegiatan_petugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

ALTER TABLE `kegiatan_rincian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `mitra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `ppk_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

ALTER TABLE `user_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

ALTER TABLE `verifikasi_administrasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `verifikasi_kegiatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `verifikasi_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ============================================================
-- FOREIGN KEY CONSTRAINTS
-- ============================================================

ALTER TABLE `administrasi`
  ADD CONSTRAINT `administrasi_ibfk_1` FOREIGN KEY (`kegiatan_petugas_id`) REFERENCES `kegiatan_petugas` (`id`) ON DELETE CASCADE;

ALTER TABLE `administrasi_files`
  ADD CONSTRAINT `fk_adminfiles_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `administrasi_kegiatan_files`
  ADD CONSTRAINT `fk_akf_kj` FOREIGN KEY (`kegiatan_jenis_id`) REFERENCES `kegiatan_jenis` (`id`) ON DELETE CASCADE;

ALTER TABLE `dokumen_kegiatan`
  ADD CONSTRAINT `dokumen_kegiatan_ibfk_1` FOREIGN KEY (`kegiatan_detail_id`) REFERENCES `kegiatan_detail` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dokumen_kegiatan_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

ALTER TABLE `dokumen_kontrak`
  ADD CONSTRAINT `dokumen_kontrak_ibfk_1` FOREIGN KEY (`kegiatan_petugas_id`) REFERENCES `kegiatan_petugas` (`id`) ON DELETE CASCADE;

ALTER TABLE `kegiatan_administrasi`
  ADD CONSTRAINT `fk_adm_kj` FOREIGN KEY (`kegiatan_jenis_id`) REFERENCES `kegiatan_jenis` (`id`) ON DELETE CASCADE;

ALTER TABLE `kegiatan_detail`
  ADD CONSTRAINT `fk_kd_pj` FOREIGN KEY (`penanggung_jawab_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kegiatan_editor` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kegiatan_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

ALTER TABLE `kegiatan_innas`
  ADD CONSTRAINT `fk_ki_kj` FOREIGN KEY (`kegiatan_jenis_id`) REFERENCES `kegiatan_jenis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ki_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `kegiatan_jenis`
  ADD CONSTRAINT `fk_kj_keg` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_detail` (`id`) ON DELETE CASCADE;

ALTER TABLE `kegiatan_petugas`
  ADD CONSTRAINT `fk_kegpetugas_kegiatan` FOREIGN KEY (`kegiatan_detail_id`) REFERENCES `kegiatan_detail` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kegpetugas_pml` FOREIGN KEY (`pml_id`) REFERENCES `kegiatan_petugas` (`id`) ON DELETE CASCADE;

ALTER TABLE `kegiatan_rincian`
  ADD CONSTRAINT `kegiatan_rincian_ibfk_1` FOREIGN KEY (`kegiatan_jenis_id`) REFERENCES `kegiatan_jenis` (`id`) ON DELETE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_users_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

ALTER TABLE `user_teams`
  ADD CONSTRAINT `fk_userteam_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_userteam_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `verifikasi_administrasi`
  ADD CONSTRAINT `verifikasi_administrasi_ibfk_1` FOREIGN KEY (`kegiatan_petugas_id`) REFERENCES `kegiatan_petugas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `verifikasi_administrasi_ibfk_2` FOREIGN KEY (`verified_by_ppk`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `verifikasi_administrasi_ibfk_3` FOREIGN KEY (`verified_by_bendahara`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `verifikasi_administrasi_ibfk_4` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `verifikasi_administrasi_ibfk_5` FOREIGN KEY (`administrasi_submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `verifikasi_kegiatan`
  ADD CONSTRAINT `verifikasi_kegiatan_ibfk_1` FOREIGN KEY (`kegiatan_detail_id`) REFERENCES `kegiatan_detail` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `verifikasi_kegiatan_ibfk_2` FOREIGN KEY (`verified_operator_tim_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `verifikasi_kegiatan_ibfk_3` FOREIGN KEY (`verified_kepala_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `verifikasi_kegiatan_ibfk_4` FOREIGN KEY (`verified_ppk_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `verifikasi_kegiatan_ibfk_5` FOREIGN KEY (`verified_bendahara_by`) REFERENCES `users` (`id`);

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
