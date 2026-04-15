-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 11, 2025 at 01:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Wandai`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrasi`
--

CREATE TABLE `administrasi` (
  `id` int(11) NOT NULL,
  `kegiatan_id` int(11) DEFAULT NULL,
  `jenis_kegiatan` varchar(100) DEFAULT NULL,
  `status_ppk` enum('pending','approved','rejected') DEFAULT 'pending',
  `status_bendahara` enum('pending','approved','rejected') DEFAULT 'pending',
  `komentar_ppk` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_by_type` enum('user','mitra') DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `administrasi_files`
--

CREATE TABLE `administrasi_files` (
  `id` int(11) NOT NULL,
  `administrasi_id` int(11) DEFAULT NULL,
  `nama_file` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_kontrak`
--

CREATE TABLE `dokumen_kontrak` (
  `id` int(11) NOT NULL,
  `kegiatan_petugas_id` int(11) NOT NULL,
  `jenis_dokumen` enum('spk','bast') NOT NULL,
  `nomor_surat` varchar(50) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `ppk_id` int(11) NOT NULL COMMENT 'PPK yang dipilih saat generate',
  `honorarium` decimal(15,2) DEFAULT NULL COMMENT 'Untuk SPK',
  `periode_mulai` date DEFAULT NULL COMMENT 'Untuk SPK',
  `periode_selesai` date DEFAULT NULL COMMENT 'Untuk SPK',
  `jumlah_realisasi` int(11) DEFAULT NULL COMMENT 'Untuk BAST',
  `file_path_docx` varchar(255) DEFAULT NULL,
  `file_path_pdf` varchar(255) DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan_detail`
--

CREATE TABLE `kegiatan_detail` (
  `id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `nama_kegiatan` varchar(255) DEFAULT NULL,
  `master_id` int(11) DEFAULT NULL,
  `jenis_kegiatan` varchar(100) DEFAULT NULL,
  `rentang_waktu_mulai` date DEFAULT NULL,
  `rentang_waktu_selesai` date DEFAULT NULL,
  `realisasi` int(11) DEFAULT NULL,
  `target` int(11) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT NULL,
  `komentar` text DEFAULT NULL,
  `edited_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kegiatan_detail`
--

INSERT INTO `kegiatan_detail` (`id`, `team_id`, `nama_kegiatan`, `master_id`, `jenis_kegiatan`, `rentang_waktu_mulai`, `rentang_waktu_selesai`, `realisasi`, `target`, `satuan`, `progress`, `komentar`, `edited_by`, `created_at`, `updated_at`) VALUES
(50, 8, 'Pemutakhiran Kerangka Geospasial dan Muatan Wilkerstat', NULL, 'Pendataan', '2025-08-01', '2025-08-31', 457, 935, 'Project', 48.88, '', 63, '2025-08-26 14:09:31', '2025-08-26 15:49:36'),
(51, 6, 'Sakernas Agustus 2025', NULL, 'Pendataan', '2025-08-13', '2025-08-31', 100, 570, 'Rumah Tangga', 17.54, '', 61, '2025-08-26 22:06:39', '2025-08-26 22:19:34');

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan_master`
--

CREATE TABLE `kegiatan_master` (
  `id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan_petugas`
--

CREATE TABLE `kegiatan_petugas` (
  `id` int(11) NOT NULL,
  `kegiatan_detail_id` int(11) NOT NULL,
  `petugas_id` int(11) NOT NULL,
  `petugas_source` enum('users','mitra') NOT NULL DEFAULT 'users',
  `peran` enum('PML','PPL') NOT NULL,
  `realisasi` int(11) DEFAULT 0,
  `target` int(11) DEFAULT 0,
  `keterangan` varchar(255) DEFAULT NULL,
  `pml_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kegiatan_petugas`
--

INSERT INTO `kegiatan_petugas` (`id`, `kegiatan_detail_id`, `petugas_id`, `petugas_source`, `peran`, `realisasi`, `target`, `keterangan`, `pml_id`, `created_at`, `updated_at`) VALUES
(305, 50, 60, 'users', 'PML', 1, 105, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(306, 50, 56, 'users', 'PML', 95, 127, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(307, 50, 63, 'users', 'PML', 90, 98, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(308, 50, 64, 'users', 'PML', 0, 63, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(309, 50, 65, 'users', 'PML', 23, 60, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(310, 50, 57, 'users', 'PML', 63, 63, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(311, 50, 66, 'users', 'PML', 2, 47, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(312, 50, 59, 'users', 'PML', 0, 36, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(313, 50, 61, 'users', 'PML', 59, 59, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(314, 50, 70, 'users', 'PML', 20, 36, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(315, 50, 67, 'users', 'PML', 42, 68, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(316, 50, 69, 'users', 'PML', 39, 89, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(317, 50, 58, 'users', 'PML', 23, 84, '', NULL, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(318, 50, 123, 'mitra', 'PPL', 0, 20, '', 305, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(319, 50, 59, 'mitra', 'PPL', 1, 28, '', 305, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(320, 50, 71, 'mitra', 'PPL', 0, 23, '', 305, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(321, 50, 116, 'mitra', 'PPL', 0, 34, '', 305, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(322, 50, 69, 'mitra', 'PPL', 19, 19, '', 306, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(323, 50, 34, 'mitra', 'PPL', 17, 34, '', 306, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(324, 50, 91, 'mitra', 'PPL', 17, 17, '', 306, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(325, 50, 107, 'mitra', 'PPL', 22, 22, '', 306, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(326, 50, 90, 'mitra', 'PPL', 20, 35, '', 306, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(327, 50, 47, 'mitra', 'PPL', 15, 19, '', 307, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(328, 50, 102, 'mitra', 'PPL', 15, 19, '', 307, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(329, 50, 37, 'mitra', 'PPL', 27, 27, '', 307, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(330, 50, 54, 'mitra', 'PPL', 15, 15, '', 307, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(331, 50, 114, 'mitra', 'PPL', 18, 18, '', 307, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(332, 50, 86, 'mitra', 'PPL', 0, 19, '', 308, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(333, 50, 76, 'mitra', 'PPL', 0, 19, '', 308, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(334, 50, 56, 'mitra', 'PPL', 0, 25, '', 308, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(335, 50, 29, 'mitra', 'PPL', 0, 16, '', 309, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(336, 50, 115, 'mitra', 'PPL', 23, 23, '', 309, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(337, 50, 70, 'mitra', 'PPL', 0, 21, '', 309, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(338, 50, 73, 'mitra', 'PPL', 23, 23, '', 310, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(339, 50, 81, 'mitra', 'PPL', 21, 21, '', 310, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(340, 50, 126, 'mitra', 'PPL', 19, 19, '', 310, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(341, 50, 25, 'mitra', 'PPL', 0, 18, '', 311, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(342, 50, 83, 'mitra', 'PPL', 0, 14, '', 311, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(343, 50, 112, 'mitra', 'PPL', 2, 15, '', 311, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(344, 50, 98, 'mitra', 'PPL', 0, 14, '', 312, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(345, 50, 65, 'mitra', 'PPL', 0, 22, '', 312, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(346, 50, 53, 'mitra', 'PPL', 27, 27, '', 313, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(347, 50, 106, 'mitra', 'PPL', 12, 12, '', 313, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(348, 50, 105, 'mitra', 'PPL', 20, 20, '', 313, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(349, 50, 84, 'mitra', 'PPL', 7, 23, '', 314, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(350, 50, 128, 'mitra', 'PPL', 13, 13, '', 314, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(351, 50, 103, 'mitra', 'PPL', 12, 14, '', 315, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(352, 50, 97, 'mitra', 'PPL', 9, 14, '', 315, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(353, 50, 64, 'mitra', 'PPL', 7, 25, '', 315, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(354, 50, 85, 'mitra', 'PPL', 14, 15, '', 315, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(355, 50, 99, 'mitra', 'PPL', 11, 25, '', 316, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(356, 50, 28, 'mitra', 'PPL', 0, 13, '', 316, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(357, 50, 52, 'mitra', 'PPL', 0, 23, '', 316, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(358, 50, 100, 'mitra', 'PPL', 28, 28, '', 316, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(359, 50, 75, 'mitra', 'PPL', 0, 23, '', 317, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(360, 50, 66, 'mitra', 'PPL', 0, 21, '', 317, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(361, 50, 129, 'mitra', 'PPL', 21, 21, '', 317, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(362, 50, 92, 'mitra', 'PPL', 2, 19, '', 317, '2025-08-26 15:49:36', '2025-08-26 15:49:36'),
(363, 51, 69, 'users', 'PML', 19, 60, '', NULL, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(364, 51, 59, 'users', 'PML', 10, 30, '', NULL, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(365, 51, 70, 'users', 'PML', 9, 60, '', NULL, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(366, 51, 57, 'users', 'PML', 0, 60, '', NULL, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(367, 51, 61, 'users', 'PML', 34, 60, 'mantap', NULL, '2025-08-26 22:19:34', '2025-08-27 00:08:03'),
(368, 51, 104, 'mitra', 'PML', 4, 60, '', NULL, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(369, 51, 66, 'users', 'PML', 0, 60, '', NULL, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(370, 51, 58, 'users', 'PML', 9, 60, '', NULL, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(371, 51, 56, 'users', 'PML', 2, 60, '', NULL, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(372, 51, 64, 'users', 'PML', 13, 60, '', NULL, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(373, 51, 82, 'mitra', 'PPL', 10, 30, '', 363, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(374, 51, 48, 'mitra', 'PPL', 9, 30, '', 363, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(375, 51, 67, 'mitra', 'PPL', 10, 30, '', 364, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(376, 51, 55, 'mitra', 'PPL', 0, 30, '', 365, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(377, 51, 119, 'mitra', 'PPL', 9, 30, '', 365, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(378, 51, 117, 'mitra', 'PPL', 0, 30, '', 366, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(379, 51, 38, 'mitra', 'PPL', 0, 30, '', 366, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(380, 51, 125, 'mitra', 'PPL', 30, 30, '', 367, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(381, 51, 72, 'mitra', 'PPL', 4, 30, '', 367, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(382, 51, 111, 'mitra', 'PPL', 0, 30, '', 368, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(383, 51, 36, 'mitra', 'PPL', 4, 30, '', 368, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(384, 51, 121, 'mitra', 'PPL', 0, 30, '', 369, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(385, 51, 88, 'mitra', 'PPL', 0, 30, '', 369, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(386, 51, 127, 'mitra', 'PPL', 9, 30, '', 370, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(387, 51, 60, 'mitra', 'PPL', 0, 30, '', 370, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(388, 51, 79, 'mitra', 'PPL', 1, 30, '', 371, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(389, 51, 80, 'mitra', 'PPL', 1, 30, '', 371, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(390, 51, 26, 'mitra', 'PPL', 13, 30, '', 372, '2025-08-26 22:19:34', '2025-08-26 22:19:34'),
(391, 51, 122, 'mitra', 'PPL', 0, 30, '', 372, '2025-08-26 22:19:34', '2025-08-26 22:19:34');

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan_petugas_backup`
--

CREATE TABLE `kegiatan_petugas_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `kegiatan_detail_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `peran` enum('Koseka','PML','PPL') NOT NULL,
  `realisasi` int(11) DEFAULT 0,
  `target` int(11) DEFAULT 0,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mitra`
--

CREATE TABLE `mitra` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mitra`
--

INSERT INTO `mitra` (`id`, `nama`, `nik`, `alamat`, `username`, `password`, `status`, `created_at`, `updated_at`) VALUES
(25, 'Florentina Richarda Andun', '9101016502970000', 'Jln Belanda lama', 'florentina', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(26, 'Aliva Olina', '9116055204850004', 'Jln.Trans Papua Kompleks Binamarga 014', 'aliva', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(27, 'Naning Nuryani', '9116055701181001', 'Kampung Asiki Mess Staf', 'naning', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(28, 'Ervando Aurico Fernando Maturbongs', '9116012007010001', 'Jl Arimop Trans Papua', 'ervando', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(29, 'Irene Saila', '7317185207000002', 'Jln. Fankan (Wet)', 'irene', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(30, 'DEVI RISKY DWI SAPUTRI', '9101014904950006', 'JL BOSOWA LAMA', 'devi', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(31, 'Novi Meilanny', '7317164905000001', 'Jalan belakang kantor bupati', 'novi.meilanny', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(32, 'Icha purnamasari', '7324084509950001', 'Jalan david ugo rt 02, kampung persatuan', 'icha', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(33, 'Yerimias India alimua', '9116012207970002', 'Jln arimop', 'yerimias', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(34, 'Esli Ketrine Dewi Koromat', '9116016507070003', 'Jl.trans Papua RT/002 RW/001,Kec,', 'esli', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(35, 'Silwanus Bontong', '7318334706980001', 'Jl.Trans Papua km.3', 'silwanus', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(36, 'Apriliana Widia Astuti', '9101064704910001', 'Jl.trans Papua km 03 kampung sokanggo', 'apriliana', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(37, 'Nurmalia Sopamena', '9116056412890001', 'Barak Karyawan korindo RT.006 Kampung Asiki', 'nurmalia', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(38, 'Silvia Sherina Torar', '9116056609030001', 'Asrama Polres Boven Digoel', 'silvia', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(39, 'Maria Sumiyatu Renyaan', '8102017004940004', 'Jln. Aerop', 'maria.renyaan', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(40, 'LIA', '7317165705970002', 'Jl.trans Papua belakang Hartoyo', 'lia', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(41, 'Yulnita Kaihena', '8101215107930001', 'Kampung persatuan RT022', 'yulnita', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(42, 'Fiolenta. F. Yawan', '9116026203920002', 'Rt 02/ Rw o1', 'fiolenta', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(43, 'Khatrine Theresia Reyaan', '9116057004810001', 'Jl Bosowa RT 008', 'khatrine', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(44, 'Rosmawati', '9116015003870001', 'JL. Van Kan Wet Kampung Sokanggo', 'rosmawati', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(45, 'Bernadeta', '9116014303960001', 'Jl trans papua km 04', 'bernadeta', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(46, 'Alfrida Felle', '9116016409890001', 'Jalan strad desa kampung persatuan', 'alfrida', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(47, 'Emiliana Marselina Nini', '9116056809780002', 'Jl. Trans Papua', 'emiliana', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(48, 'Atiek Cornelia', '9116015701940003', 'Jalan trans Papua km. 04', 'atiek', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(49, 'Oktaviana Dorvin Ino', '5308144610030002', 'Jl. Ampera, RT/RW. 012/000', 'oktaviana', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(50, 'Jesica Rumpun Palili', '7317185004990001', 'Jln. AMPERA II', 'jesica', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(51, 'Yohanes Rimang Eka Pratama', '9101013110980002', 'Km.03 RT.12', 'yohanes', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(52, 'Robertus Rinaldi Liem Gebze', '9101011704020002', 'Jalan Brawijaya RT 015/RW 002', 'robertus', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(53, 'Bergitha Maria Fiyane', '9116015206800002', 'Jln Kapten Piere Tendean RT 001 RW 001', 'bergitha', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(54, 'Rohani', '9101016205840001', 'Kampung Getentiri RT001/000', 'rohani', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(55, 'David Berop', '9116070110940001', 'kampung Kombut, kecamatan Kombut', 'david', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(56, 'Kesi Mikha Pakanda', '7326111105980002', 'Jalan tole\'', 'kesi', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(57, 'DONNY DANIEL MESAK LEKI', '5303042504950002', 'Jl. Pasar Wet', 'donny', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(58, 'Paskalina Aprila Tiy', '9116015404960001', 'Jln. Ayarop, RT. 003', 'paskalina.aprila', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(59, 'Hadi Karepesina', '8101130206840005', 'RT 14', 'hadi', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(60, 'Selmina Mampioper', '9171034211900003', 'RT 001', 'selmina', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(61, 'LORNA LILING PADANG', '7317212609990001', 'Kampung Persatuan, Kompleks Binamarga', 'lorna', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(62, 'Elisabeth Yawon', '9116014301910001', 'Kampung maju Rt 001 Rw 001', 'elisabeth', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(63, 'Imelda Gita Rande', '7318025506950001', 'Kampung ASIKI, Distrik Jair', 'imelda', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(64, 'Fransiscus Tandi Tiku', '7318340811060001', 'Jalan Ampera 2', 'fransiscus', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(65, 'Jhon Chris Karipui', '5305012711950001', 'Jl. Trans Papua RT 3 Kampung Persatuan', 'jhon', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(66, 'Esterlita Erminda Wara', '9116054309010001', 'Jl. Ampera, RT.012. RW.000 (002) Perstuan, (OSO) mandobo, (02) Boven Digoel, (95) Papua selatan.', 'esterlita', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(67, 'Leonardus Libdo', '7318193007940001', 'RT.01', 'leonardus', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(68, 'Nober Porrero\'', '7326133011960003', 'Jln.SMA', 'nober', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(69, 'Bagus hadi prabowo', '9101011604960004', 'Jln karning I kabupatrn boven digoel', 'bagus', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(70, 'Watini', '9116054206810001', 'RT.004 Rw.004 Kampung Asiki', 'watini', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(71, 'Nurdiana', '9116016707810001', 'Jl Arimop Trans Papua', 'nurdiana', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(72, 'Brenda Stephanie Ayomi', '9116016109040002', 'Kampung Sokanggo (007) SOKANGGO, (030) , (13) , (94) PAPUA', 'brenda', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(73, 'Cittiro pusungulena', '7104162601920002', 'Jl. Beteyop', 'cittiro', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(74, 'Rahmat Ely santoso', '9101012401870002', 'Distrik mandobo kampung persatuan', 'rahmat', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(75, 'Ana Maria konarim', '9116035707950001', 'Kampung kanggewot,Distrik Waropko', 'ana', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(76, 'Eka yuliana', '3522235603930002', 'Jln Trans papua', 'eka.yuliana', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(77, 'Amalisna hiowati', '9105056611000001', 'Kodim bvd', 'amalisna', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(78, 'BIN RAPPA RONDONG', '7326020308000004', 'JLN. ARIMOP KAMP. PERSATUAN DIST. MANDOBO', 'bin', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(79, 'Latifah Fahmi', '3403094801940002', 'Jalan Bosowa Lama, Kampung Persatuan, Mandobo', 'latifah', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(80, 'Suratman', '9116011010960002', 'Jln. Trans papua km.03', 'suratman', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(81, 'Marten Konoralma', '8107011202990001', 'Jl. Trans Papua Km.03, Kampung Sokanggo RT003 RW000', 'marten', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(82, 'Hafiz Fikri', '3175072303030012', 'RT008 Kampung Persatuan', 'hafiz', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(83, 'Insar Mazrikce Mofu', '9171025510000002', 'JL. Trans Papua KM-3 arah Asiki', 'insar', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(84, 'Renlly Piefra Adiputra Meteray', '9101011710980002', 'Jl. Ampera 1', 'renlly', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(85, 'Lilis Ariska Rohmatika', '3204335503950005', 'Jalan Trans Papua KM 03', 'lilis', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(86, 'Abdon I.banlowen', '5305060508990001', 'Kampung kanggewot', 'abdon', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(87, 'Elias Leonardo Jamrewaw', '9101010704990003', 'Jl Trans Papua km 2', 'elias', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(88, 'Sierlina Klaru', '9116014503990001', 'Jl.Trans Papua km 2.Belakang Apotek meisya', 'sierlina', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(89, 'anggie sabrina islmi', '9116015908060001', 'jalan trans papua km 02', 'anggie', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(90, 'Nur hijriyah turuy', '9116014803010001', 'Perum pegawai km 3', 'nur', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(91, 'JIMMY YOSEPH OHOITIMUR', '9101011406940005', 'JL. YRANS PAPUA PERUM. PEGAWAI KM. 03', 'jimmy', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(92, 'Yuliana Tammu', '7318136007950001', 'Jl.Trans Papua Km.02 RT014/000, Kampung Persatuan, Mandobo', 'yuliana.tammu', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(93, 'Falentino Tandirura', '7326021406990001', 'Jln. Transpapua km 6', 'falentino', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(94, 'Philomine Claresia L.Yawan', '9116024507990001', 'RT 001/RW 000', 'philomine', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(95, 'Januaria Elisabet Dinggon', '9116011109920001', 'Jalan Bosowa Lama', 'januaria', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(96, 'ANGLE PONGSIBIDANG', '9116012808970001', 'Jl. TMP', 'angle', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(97, 'Eka Nurviana', '9116014610030004', 'Kampung Persatuan Dusun Persatuan', 'eka.nurviana', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(98, 'Anthonius okyap', '9116021404940001', 'Kampung Epsembit RT.001 RW.000', 'anthonius', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(99, 'ADITYA PUTRA N.F . SUBAN', '9117020907000001', 'Jalan Trans Papua KM 03 RT 022 / RW 000', 'aditya', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(100, 'Yanne Helena Samakori', '9116014704000002', 'Jalan David Ugo / RT 02', 'yanne', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(101, 'Chandra', '1276031807940001', 'jalan sokanggo', 'chandra', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(102, 'Miranda Elsa Papuani Mofu', '9106085710070001', 'JLn. Trans Papua Km-3 Arah Asiki', 'miranda', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(103, 'Dian Islamiati', '9116015205990003', 'Jalan pelabuhan lama no. 41', 'dian', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(104, 'Samaria Sarabeka Dinggon', '9116014101950001', 'Jl.Arimop', 'samaria', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(105, 'Yuliana Fibriani Kego', '5308205602870001', 'Jl. Ampera, RT/RW. 012/000', 'yuliana.kego', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(106, 'Rosdiantika', '7317166111950001', 'Kampung sokanggo RT01 Kelurahan Mandobo', 'rosdiantika', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(107, 'MUHAMMAD YUSUF SAFRIANSYAH', '9116012509940002', 'Jl.TRANS PAPUA', 'muhammad', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(108, 'Riskawati', '9116015805990003', 'jalan trans papua', 'riskawati', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(109, 'Sisilia K Kayom', '9116024311960001', 'RT/RW: 002/001 Jln Perkebunan', 'sisilia', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(110, 'Noor Komaria Rasyid', '7306025612920002', 'Jalan Belanda lama RT 18', 'noor', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(111, 'Novi Sulistiowati', '9116015911820002', 'Aspol baru kalimax RT 19/rw 00', 'novi.sulistiowati', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(112, 'Kristina Andap', '9116016905930004', 'Jalan Trans Papua Arah Mindiptana, Persatuan, Mandobo', 'kristina', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(113, 'Deliyanthi M', '9116024401950001', 'Kam andopbit', 'deliyanthi', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(114, 'Suratmin', '5206110904941004', 'Kampung Getentiri RT010 RW003', 'suratmin', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(115, 'Kornelis Arianto', '5310130202010001', 'Jl. Prabu Komplek SMA Negeri Asiki, RT002/000', 'kornelis', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(116, 'Thomas Wanan', '9116012207000001', 'Kampung Sokanggo RT 003', 'thomas', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(117, 'Nova Utami Ramadhani', '9116014111030003', 'jalan Ampera gang bonop 013/000 persatuan', 'nova', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(118, 'Jumedi Kalatiku', '7317110107870043', 'Asiki RT 3', 'jumedi', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(119, 'Reski Adelia Pasoro', '9116026908040001', 'RT.002/RW 000', 'reski', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(120, 'ANDRIANI BATARA', '7318125007970001', 'JL.KAPT.TANDEAN TANAH MERAH', 'andriani', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(121, 'OKTOVINA MAGDALENA KONMOP', '9101015210990005', 'Jln. BELANDA LAMA', 'oktovina', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(122, 'Paskalina Dorothea Treok Aute', '9101015203010004', 'Jl. Trans Papua', 'paskalina.dorothea', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(123, 'Divania Natasha Trivelin Maturbongs', '9116016212030001', 'Jl. Arimop Trans Papua RT/RW 002/000', 'divania', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(124, 'Susanti', '7326025912980003', 'Jl ampera', 'susanti', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(125, 'Siti Mualifah', '9101015407870009', 'Jalan karning 2', 'siti', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(126, 'Selpy Patrifia Sari', '7471095002890001', 'Kampung Sokanggo, Distrik Mandobo, RT/RW 002/000', 'selpy', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(127, 'MARIA VIRGINIA HADA', '9116015606990001', 'Jln. Strad Desa, Kampung Persatuan, RT:003.', 'maria.hada', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(128, 'Saverius Babo Benge', '5308201809890001', 'Jl. Ampera, RT/RW. 012/000', 'saverius', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57'),
(129, 'Sunarti', '7317167011950001', 'Jl. Amupka, kampung Waropko, Distrik Waropko', 'sunarti', '9502', 'aktif', '2025-08-25 08:53:57', '2025-08-25 08:53:57');

-- --------------------------------------------------------

--
-- Table structure for table `ppk_data`
--

CREATE TABLE `ppk_data` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `jabatan` varchar(100) DEFAULT 'Pejabat Pembuat Komitmen',
  `unit_kerja` varchar(100) DEFAULT 'BPS Kabupaten Boven Digoel',
  `alamat_unit` text DEFAULT 'Tanah Merah',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ppk_data`
--

INSERT INTO `ppk_data` (`id`, `nama`, `nip`, `jabatan`, `unit_kerja`, `alamat_unit`, `is_active`, `created_at`) VALUES
(1, 'Galih Pramono', '199603232019011001', 'Pejabat Pembuat Komitmen', 'BPS Kabupaten Boven Digoel', 'Tanah Merah', 1, '2025-08-22 15:53:30');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'admin'),
(2, 'kepala'),
(3, 'ppk'),
(4, 'operator'),
(5, 'bendahara'),
(6, 'mitra');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`) VALUES
(1, 'Kepala BPS Kabupaten Boven Digoel'),
(2, 'Diseminasi dan Layanan Statistik'),
(3, 'Statistik Distribusi'),
(4, 'Statistik Produksi'),
(5, 'Neraca Wilayah dan Analisis Statistik'),
(6, 'Statistik Sosial'),
(7, 'Subbagian Umum'),
(8, 'Pengolahan Statistik');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL COMMENT '1=admin, 2=kepala, 3=ppk, 4=operator, 5=bendahara',
  `team_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role_id`, `team_id`, `created_at`, `updated_at`) VALUES
(55, 'Novita Damayanti', 'novita', 'novita', 2, 1, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(56, 'Anis Khoirun Nisak', 'khoirun.nisak', 'khoirun.nisak', 4, 2, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(57, 'Fariz Hamzah Fanshuri', 'fariz.hamzah', 'fariz.hamzah', 4, 3, '2025-04-30 23:01:41', '2025-08-15 11:19:32'),
(58, 'Wilhelmus Anthon', 'wilhelmus.anthon', 'wilhelmus.anthon', 4, 3, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(59, 'Kostan Karlos Liem Gebze', 'karlos.liem', 'karlos.liem', 4, 4, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(60, 'Amin Chusnul Hidayat', 'chusnul.hidayat', 'chusnul.hidayat', 4, 5, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(61, 'Paulus Satria Prasetyo', 'paulus.satria', 'paulus.satria', 4, 6, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(62, 'Muhammad Afiyf Besari', 'muhammadafiyf', 'muhammadafiyf', 4, 7, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(63, 'Apriliyanti Eka Putri', 'apriliyanti.eka', 'apriliyanti.eka', 4, 8, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(64, 'Bella Pradiana', 'bella.pradiana', 'bella.pradiana', 4, 5, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(65, 'Fahmi Burhanuddin', 'fahmi.burhanuddin', 'fahmi.burhanuddin', 4, 4, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(66, 'Inseri Putri Melanesia Mofu', 'inseri.mofu', 'inseri.mofu', 4, 8, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(67, 'Sri Handayani', 'sri.handayani2', 'sri.handayani2', 4, 2, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(68, 'Galih Pramono', 'galih.pramono', 'galih.pramono', 3, 7, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(69, 'Teuku Rizki Sunandarsyah', 'teuku.rizki', 'teuku.rizki', 4, 6, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(70, 'Roy Boris Simanjuntak', 'roy.boris', 'roy.boris', 4, 2, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(71, 'Muhammad Hafid Septianto', 'hafid.septianto', 'hafid.septianto', 5, 7, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(72, 'admin', 'admin', 'admin', 1, NULL, '2025-04-30 23:01:41', '2025-04-30 23:01:41'),
(76, 'operator', 'operator', '123', 4, 1, '2025-08-15 11:21:12', '2025-08-16 07:51:21'),
(77, 'Bendahara', 'bendahara', '123', 5, 1, '2025-08-15 13:50:12', '2025-08-15 13:50:12'),
(78, 'kepala', 'kepala', '123', 2, 1, '2025-08-16 07:33:18', '2025-08-16 07:33:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrasi`
--
ALTER TABLE `administrasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `administrasi_ibfk_1` (`kegiatan_id`);

--
-- Indexes for table `administrasi_files`
--
ALTER TABLE `administrasi_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `administrasi_id` (`administrasi_id`);

--
-- Indexes for table `dokumen_kontrak`
--
ALTER TABLE `dokumen_kontrak`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dokumen` (`kegiatan_petugas_id`,`jenis_dokumen`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_jenis_dokumen` (`jenis_dokumen`),
  ADD KEY `idx_kegiatan_petugas` (`kegiatan_petugas_id`),
  ADD KEY `idx_ppk` (`ppk_id`);

--
-- Indexes for table `kegiatan_detail`
--
ALTER TABLE `kegiatan_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `master_id` (`master_id`),
  ADD KEY `edited_by` (`edited_by`);

--
-- Indexes for table `kegiatan_master`
--
ALTER TABLE `kegiatan_master`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `kegiatan_petugas`
--
ALTER TABLE `kegiatan_petugas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kegiatan_petugas` (`kegiatan_detail_id`,`petugas_id`,`petugas_source`),
  ADD KEY `idx_kp_kegiatan` (`kegiatan_detail_id`),
  ADD KEY `idx_kp_user` (`petugas_id`),
  ADD KEY `idx_petugas_source` (`petugas_source`),
  ADD KEY `idx_pml_id` (`pml_id`),
  ADD KEY `idx_peran` (`peran`);

--
-- Indexes for table `mitra`
--
ALTER TABLE `mitra`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `ppk_data`
--
ALTER TABLE `ppk_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `team_id` (`team_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `administrasi`
--
ALTER TABLE `administrasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `administrasi_files`
--
ALTER TABLE `administrasi_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `dokumen_kontrak`
--
ALTER TABLE `dokumen_kontrak`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `kegiatan_detail`
--
ALTER TABLE `kegiatan_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `kegiatan_master`
--
ALTER TABLE `kegiatan_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kegiatan_petugas`
--
ALTER TABLE `kegiatan_petugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=392;

--
-- AUTO_INCREMENT for table `mitra`
--
ALTER TABLE `mitra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `ppk_data`
--
ALTER TABLE `ppk_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `administrasi`
--
ALTER TABLE `administrasi`
  ADD CONSTRAINT `administrasi_ibfk_1` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_detail` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `administrasi_files`
--
ALTER TABLE `administrasi_files`
  ADD CONSTRAINT `administrasi_files_ibfk_1` FOREIGN KEY (`administrasi_id`) REFERENCES `administrasi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dokumen_kontrak`
--
ALTER TABLE `dokumen_kontrak`
  ADD CONSTRAINT `dokumen_kontrak_ibfk_1` FOREIGN KEY (`kegiatan_petugas_id`) REFERENCES `kegiatan_petugas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dokumen_kontrak_ibfk_2` FOREIGN KEY (`ppk_id`) REFERENCES `ppk_data` (`id`),
  ADD CONSTRAINT `dokumen_kontrak_ibfk_3` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `kegiatan_detail`
--
ALTER TABLE `kegiatan_detail`
  ADD CONSTRAINT `kegiatan_detail_ibfk_1` FOREIGN KEY (`master_id`) REFERENCES `kegiatan_master` (`id`),
  ADD CONSTRAINT `kegiatan_detail_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `kegiatan_master`
--
ALTER TABLE `kegiatan_master`
  ADD CONSTRAINT `kegiatan_master_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);

--
-- Constraints for table `kegiatan_petugas`
--
ALTER TABLE `kegiatan_petugas`
  ADD CONSTRAINT `fk_kp_kegiatan` FOREIGN KEY (`kegiatan_detail_id`) REFERENCES `kegiatan_detail` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kp_pml` FOREIGN KEY (`pml_id`) REFERENCES `kegiatan_petugas` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
