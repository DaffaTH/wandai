-- ============================================================
-- WANDAI SYSTEM - COMPLETE DATABASE MIGRATION
-- BPS Kabupaten Paniai
-- Versi: 2.0
-- ============================================================

-- ============================================================
-- BACKUP DULU SEBELUM JALANKAN!
-- mysqldump -u root -p wandai > wandai_backup.sql
-- ============================================================

-- ============================================================
-- 1. ALTER TABLE: users
-- - Tambah kolom gelar_depan, gelar_belakang, pangkat, golongan, jabatan
-- - Rename username ke email
-- ============================================================

-- Cek dan tambah kolom baru di users
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `gelar_depan` VARCHAR(50) NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `gelar_belakang` VARCHAR(100) NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `pangkat` VARCHAR(100) NULL AFTER `nip`,
ADD COLUMN IF NOT EXISTS `golongan` VARCHAR(20) NULL AFTER `nip`,
ADD COLUMN IF NOT EXISTS `jabatan` VARCHAR(150) NULL AFTER `nip`;

-- Rename username ke email (jika masih username)
-- Cek dulu apakah kolom username ada
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'username'
);

SET @sql = IF(@col_exists > 0, 
    'ALTER TABLE `users` CHANGE COLUMN `username` `email` VARCHAR(150) NOT NULL',
    'SELECT "Column already renamed"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 2. ALTER TABLE: mitra
-- - Tambah kolom wilayah_kerja
-- - Rename username ke email
-- ============================================================

-- Tambah kolom wilayah_kerja
ALTER TABLE `mitra` 
ADD COLUMN IF NOT EXISTS `wilayah_kerja` ENUM('Paniai', 'Intan Jaya', 'Deiyai') DEFAULT 'Paniai' AFTER `alamat`;

-- Rename username ke email (jika masih username)
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'mitra' 
    AND COLUMN_NAME = 'username'
);

SET @sql = IF(@col_exists > 0, 
    'ALTER TABLE `mitra` CHANGE COLUMN `username` `email` VARCHAR(150) NOT NULL',
    'SELECT "Column already renamed"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. ALTER TABLE: kegiatan_detail
-- - Tambah kolom program, output, komponen
-- ============================================================

ALTER TABLE `kegiatan_detail` 
ADD COLUMN IF NOT EXISTS `program` VARCHAR(255) NULL AFTER `komentar`,
ADD COLUMN IF NOT EXISTS `output` VARCHAR(255) NULL AFTER `program`,
ADD COLUMN IF NOT EXISTS `komponen` VARCHAR(255) NULL AFTER `output`;

-- ============================================================
-- 4. ALTER TABLE: kegiatan_petugas
-- - Tambah kolom alat_angkutan
-- - Pastikan no_sppd ada (bukan no_spd)
-- ============================================================

ALTER TABLE `kegiatan_petugas` 
ADD COLUMN IF NOT EXISTS `alat_angkutan` VARCHAR(100) DEFAULT 'Kendaraan Umum' AFTER `tujuan`;

-- Rename no_spd ke no_sppd jika ada
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'kegiatan_petugas' 
    AND COLUMN_NAME = 'no_spd'
);

SET @sql = IF(@col_exists > 0, 
    'ALTER TABLE `kegiatan_petugas` CHANGE COLUMN `no_spd` `no_sppd` VARCHAR(50) NULL',
    'SELECT "Column already correct"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tambah no_sppd jika belum ada
ALTER TABLE `kegiatan_petugas` 
ADD COLUMN IF NOT EXISTS `no_sppd` VARCHAR(50) NULL;

-- ============================================================
-- 5. PASTIKAN ROLES ADA
-- ============================================================

INSERT IGNORE INTO `roles` (`id`, `name`) VALUES 
(1, 'admin'),
(2, 'ppk'),
(3, 'bendahara'),
(4, 'operator'),
(5, 'kepala');

-- ============================================================
-- 6. UPDATE DATA KEPALA (role_id = 5)
-- Sesuaikan dengan data Kepala BPS Kabupaten Paniai
-- ============================================================

UPDATE `users` SET 
    `gelar_depan` = NULL,
    `gelar_belakang` = 'SST, M.Si',
    `pangkat` = 'Statistisi Ahli Muda',
    `golongan` = 'III/c',
    `jabatan` = 'Kepala BPS Kabupaten Paniai'
WHERE `role_id` = 5
AND (`gelar_belakang` IS NULL OR `gelar_belakang` = '');

-- ============================================================
-- 7. UPDATE DATA PPK (role_id = 2)
-- Sesuaikan dengan data PPK BPS Kabupaten Paniai
-- ============================================================

UPDATE `users` SET 
    `gelar_depan` = NULL,
    `gelar_belakang` = 'SST',
    `pangkat` = 'Statistisi Ahli Pertama',
    `golongan` = 'III/b',
    `jabatan` = 'Pejabat Pembuat Komitmen'
WHERE `role_id` = 2
AND (`gelar_belakang` IS NULL OR `gelar_belakang` = '');

-- ============================================================
-- 8. INSERT SAMPLE DATA JIKA BELUM ADA KEPALA & PPK
-- ============================================================

-- Cek apakah ada user dengan role kepala
INSERT INTO `users` (`name`, `gelar_depan`, `gelar_belakang`, `email`, `password`, `nip`, `pangkat`, `golongan`, `jabatan`, `role_id`, `team_id`, `status`, `created_at`)
SELECT 'Khaerul Umam', NULL, 'SST, M.Si', 'kepala@bps.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '198402012008011010', 'Statistisi Ahli Muda', 'III/c', 'Kepala BPS Kabupaten Paniai', 5, 1, 'aktif', NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `role_id` = 5);

-- Cek apakah ada user dengan role PPK
INSERT INTO `users` (`name`, `gelar_depan`, `gelar_belakang`, `email`, `password`, `nip`, `pangkat`, `golongan`, `jabatan`, `role_id`, `team_id`, `status`, `created_at`)
SELECT 'Christin Septiana', NULL, 'SST', 'ppk@bps.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '199509242018022001', 'Statistisi Ahli Pertama', 'III/b', 'Pejabat Pembuat Komitmen', 2, 1, 'aktif', NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `role_id` = 2);

-- ============================================================
-- 9. CREATE VIEW untuk nama lengkap dengan gelar
-- ============================================================

DROP VIEW IF EXISTS `v_users_lengkap`;
CREATE VIEW `v_users_lengkap` AS
SELECT 
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
    u.jabatan,
    u.email,
    u.role_id,
    r.name AS role_name,
    u.team_id,
    t.name AS team_name
FROM users u
LEFT JOIN roles r ON r.id = u.role_id
LEFT JOIN teams t ON t.id = u.team_id;

-- ============================================================
-- 10. CREATE TABLE dokumen_kontrak jika belum ada
-- ============================================================

CREATE TABLE IF NOT EXISTS `dokumen_kontrak` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kegiatan_petugas_id` INT NOT NULL,
    `jenis_dokumen` ENUM('spk', 'bast', 'surat_tugas', 'sppd') NOT NULL,
    `nomor_surat` VARCHAR(100) NULL,
    `tanggal_kontrak` DATE NULL,
    `ppk_id` INT NULL,
    `honorarium` DECIMAL(15,2) NULL,
    `periode_mulai` DATE NULL,
    `periode_selesai` DATE NULL,
    `lokasi` VARCHAR(255) NULL,
    `jumlah_realisasi` INT NULL,
    `file_path_docx` VARCHAR(500) NULL,
    `file_path_pdf` VARCHAR(500) NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_dokumen` (`kegiatan_petugas_id`, `jenis_dokumen`),
    FOREIGN KEY (`kegiatan_petugas_id`) REFERENCES `kegiatan_petugas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 11. VERIFIKASI STRUKTUR TABEL
-- ============================================================

-- Cek struktur users
SELECT 'STRUKTUR TABEL USERS:' AS info;
DESCRIBE users;

-- Cek struktur mitra  
SELECT 'STRUKTUR TABEL MITRA:' AS info;
DESCRIBE mitra;

-- Cek struktur kegiatan_detail
SELECT 'STRUKTUR TABEL KEGIATAN_DETAIL:' AS info;
DESCRIBE kegiatan_detail;

-- Cek struktur kegiatan_petugas
SELECT 'STRUKTUR TABEL KEGIATAN_PETUGAS:' AS info;
DESCRIBE kegiatan_petugas;

-- ============================================================
-- 12. VERIFIKASI DATA KEPALA & PPK
-- ============================================================

SELECT 'DATA KEPALA & PPK:' AS info;
SELECT id, name, gelar_belakang, email, nip, pangkat, golongan, jabatan, role_id
FROM users 
WHERE role_id IN (2, 5);

-- ============================================================
-- SELESAI!
-- ============================================================

SELECT '=== MIGRATION COMPLETE ===' AS status;
SELECT 'Pastikan data Kepala dan PPK sudah benar!' AS reminder;

-- ============================================================
-- CATATAN PENTING:
-- ============================================================
-- 
-- Role ID:
-- 1 = admin
-- 2 = ppk (Pejabat Pembuat Komitmen) - Penandatangan SPPD
-- 3 = bendahara
-- 4 = operator / subject matter
-- 5 = kepala - Penandatangan Surat Tugas
--
-- Wilayah Kerja Mitra:
-- - Paniai
-- - Intan Jaya  
-- - Deiyai
--
-- Alat Angkutan:
-- - Kendaraan Umum (default)
-- - Kendaraan Dinas
-- - Pesawat
-- - Kapal
-- - Motor
-- - Jalan Kaki
--
-- ============================================================
