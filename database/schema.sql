-- Wandai System - Database Schema
-- Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
-- Year: 2025
-- MySQL Database Schema

-- Create Database
CREATE DATABASE IF NOT EXISTS `wandai` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wandai`;

-- ============================================
-- Table: roles
-- ============================================
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: teams
-- ============================================
CREATE TABLE IF NOT EXISTS `teams` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: users (PNS/Pegawai)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `nip` VARCHAR(20) DEFAULT NULL,
  `role_id` INT(11) UNSIGNED NOT NULL,
  `team_id` INT(11) UNSIGNED DEFAULT NULL,
  `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `users_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `users_team_fk` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: mitra (Partner/Petugas Lapangan)
-- ============================================
CREATE TABLE IF NOT EXISTS `mitra` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `nik` VARCHAR(16) NOT NULL,
  `alamat` TEXT DEFAULT NULL,
  `telepon` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `nik` (`nik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: ppk_data (Data PPK)
-- ============================================
CREATE TABLE IF NOT EXISTS `ppk_data` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nip` VARCHAR(20) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `jabatan` VARCHAR(100) DEFAULT NULL,
  `pangkat` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nip` (`nip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: kegiatan_detail (Master Kegiatan)
-- ============================================
CREATE TABLE IF NOT EXISTS `kegiatan_detail` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_kegiatan` VARCHAR(255) NOT NULL,
  `jenis_kegiatan` VARCHAR(100) DEFAULT NULL,
  `tim_id` INT(11) UNSIGNED DEFAULT NULL,
  `tanggal_mulai` DATE DEFAULT NULL,
  `tanggal_selesai` DATE DEFAULT NULL,
  `deadline` DATE DEFAULT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `lokasi` VARCHAR(255) DEFAULT NULL,
  `target` INT(11) DEFAULT 0,
  `realisasi` INT(11) DEFAULT 0,
  `status` ENUM('pending', 'berjalan', 'selesai', 'dibatalkan') DEFAULT 'pending',
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tim_id` (`tim_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `kegiatan_tim_fk` FOREIGN KEY (`tim_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kegiatan_creator_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: kegiatan_petugas (Assignment Petugas ke Kegiatan)
-- ============================================
CREATE TABLE IF NOT EXISTS `kegiatan_petugas` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kegiatan_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `mitra_id` INT(11) UNSIGNED DEFAULT NULL,
  `role_tugas` VARCHAR(100) DEFAULT NULL,
  `status_tugas` ENUM('ditugaskan', 'berjalan', 'selesai', 'dibatalkan') DEFAULT 'ditugaskan',
  `progress` INT(3) DEFAULT 0,
  `catatan` TEXT DEFAULT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kegiatan_id` (`kegiatan_id`),
  KEY `user_id` (`user_id`),
  KEY `mitra_id` (`mitra_id`),
  CONSTRAINT `petugas_kegiatan_fk` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_detail` (`id`) ON DELETE CASCADE,
  CONSTRAINT `petugas_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `petugas_mitra_fk` FOREIGN KEY (`mitra_id`) REFERENCES `mitra` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: dokumen_kontrak (SPK/BAST)
-- ============================================
CREATE TABLE IF NOT EXISTS `dokumen_kontrak` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kegiatan_id` INT(11) UNSIGNED NOT NULL,
  `mitra_id` INT(11) UNSIGNED NOT NULL,
  `jenis_dokumen` ENUM('SPK', 'BAST', 'Lainnya') NOT NULL,
  `nomor_dokumen` VARCHAR(100) DEFAULT NULL,
  `tanggal_dokumen` DATE DEFAULT NULL,
  `filepath` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('draft', 'final', 'ditandatangani') DEFAULT 'draft',
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kegiatan_id` (`kegiatan_id`),
  KEY `mitra_id` (`mitra_id`),
  CONSTRAINT `dokumen_kegiatan_fk` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_detail` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dokumen_mitra_fk` FOREIGN KEY (`mitra_id`) REFERENCES `mitra` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: administrasi (Tracking Administrasi Kegiatan)
-- ============================================
CREATE TABLE IF NOT EXISTS `administrasi` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kegiatan_id` INT(11) UNSIGNED NOT NULL,
  `mitra_id` INT(11) UNSIGNED DEFAULT NULL,
  `jenis_administrasi` VARCHAR(100) NOT NULL,
  `nomor_administrasi` VARCHAR(100) DEFAULT NULL,
  `tanggal_pengajuan` DATE DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `status_ppk` ENUM('pending', 'disetujui', 'ditolak') DEFAULT 'pending',
  `catatan_ppk` TEXT DEFAULT NULL,
  `verified_ppk_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_ppk_by` INT(11) UNSIGNED DEFAULT NULL,
  `status_bendahara` ENUM('pending', 'disetujui', 'ditolak') DEFAULT 'pending',
  `catatan_bendahara` TEXT DEFAULT NULL,
  `verified_bendahara_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_bendahara_by` INT(11) UNSIGNED DEFAULT NULL,
  `status_kepala` ENUM('pending', 'disetujui', 'ditolak') DEFAULT 'pending',
  `catatan_kepala` TEXT DEFAULT NULL,
  `verified_kepala_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_kepala_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kegiatan_id` (`kegiatan_id`),
  KEY `mitra_id` (`mitra_id`),
  CONSTRAINT `adm_kegiatan_fk` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_detail` (`id`) ON DELETE CASCADE,
  CONSTRAINT `adm_mitra_fk` FOREIGN KEY (`mitra_id`) REFERENCES `mitra` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: activity_logs (Audit Trail)
-- ============================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(50) DEFAULT NULL,
  `record_id` INT(11) UNSIGNED DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Indexes for Performance
-- ============================================
ALTER TABLE `kegiatan_detail` ADD INDEX `status_idx` (`status`);
ALTER TABLE `kegiatan_detail` ADD INDEX `deadline_idx` (`deadline`);
ALTER TABLE `administrasi` ADD INDEX `status_ppk_idx` (`status_ppk`);
ALTER TABLE `administrasi` ADD INDEX `status_bendahara_idx` (`status_bendahara`);
ALTER TABLE `administrasi` ADD INDEX `status_kepala_idx` (`status_kepala`);

-- ============================================
-- Views for Easy Querying
-- ============================================

-- View: user_with_details
CREATE OR REPLACE VIEW `v_users` AS
SELECT 
    u.id,
    u.username,
    u.name,
    u.nip,
    u.status,
    r.id AS role_id,
    r.name AS role_name,
    t.id AS team_id,
    t.name AS team_name,
    u.created_at,
    u.updated_at
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN teams t ON u.team_id = t.id;

-- View: kegiatan_with_team
CREATE OR REPLACE VIEW `v_kegiatan` AS
SELECT 
    k.id,
    k.nama_kegiatan,
    k.jenis_kegiatan,
    k.tanggal_mulai,
    k.tanggal_selesai,
    k.deadline,
    k.target,
    k.realisasi,
    k.status,
    t.id AS tim_id,
    t.name AS tim_name,
    u.name AS created_by_name,
    k.created_at,
    k.updated_at,
    DATEDIFF(k.deadline, CURDATE()) AS days_remaining
FROM kegiatan_detail k
LEFT JOIN teams t ON k.tim_id = t.id
LEFT JOIN users u ON k.created_by = u.id;

-- ============================================
-- Triggers for Auto-Update
-- ============================================

-- Trigger: Update realisasi kegiatan otomatis
DELIMITER $$
CREATE TRIGGER `update_kegiatan_realisasi` 
AFTER UPDATE ON `kegiatan_petugas`
FOR EACH ROW
BEGIN
    UPDATE kegiatan_detail
    SET realisasi = (
        SELECT COALESCE(AVG(progress), 0)
        FROM kegiatan_petugas
        WHERE kegiatan_id = NEW.kegiatan_id
    )
    WHERE id = NEW.kegiatan_id;
END$$
DELIMITER ;

-- ============================================
-- End of Schema
-- ============================================
