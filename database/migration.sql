-- ============================================================
-- WANDAI SYSTEM - DATABASE MIGRATION COMPLETE
-- BPS Kabupaten Paniai
-- ============================================================

-- ============================================================
-- 1. ALTER TABLE: users
-- Tambah: gelar_depan, gelar_belakang, pangkat, golongan, jabatan
-- Ubah: username -> email (unique)
-- ============================================================

ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `gelar_depan` VARCHAR(50) NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `gelar_belakang` VARCHAR(100) NULL AFTER `gelar_depan`,
ADD COLUMN IF NOT EXISTS `pangkat` VARCHAR(100) NULL AFTER `nip`,
ADD COLUMN IF NOT EXISTS `golongan` VARCHAR(20) NULL AFTER `pangkat`,
ADD COLUMN IF NOT EXISTS `jabatan` VARCHAR(150) NULL AFTER `golongan`;

-- Rename username ke email (jika belum)
-- ALTER TABLE `users` CHANGE COLUMN `username` `email` VARCHAR(150) NOT NULL;
-- ALTER TABLE `users` ADD UNIQUE INDEX `email_UNIQUE` (`email` ASC);

-- ============================================================
-- 2. ALTER TABLE: mitra
-- Tambah: wilayah_kerja
-- Ubah: username -> email (unique)
-- ============================================================

ALTER TABLE `mitra` 
ADD COLUMN IF NOT EXISTS `wilayah_kerja` ENUM('Paniai', 'Intan Jaya', 'Deiyai') NULL DEFAULT 'Paniai' AFTER `alamat`;

-- Rename username ke email (jika belum)
-- ALTER TABLE `mitra` CHANGE COLUMN `username` `email` VARCHAR(150) NOT NULL;
-- ALTER TABLE `mitra` ADD UNIQUE INDEX `mitra_email_UNIQUE` (`email` ASC);

-- ============================================================
-- 3. ALTER TABLE: kegiatan_detail
-- Tambah: program, output, komponen (untuk pembebanan anggaran SPPD)
-- ============================================================

ALTER TABLE `kegiatan_detail` 
ADD COLUMN IF NOT EXISTS `program` VARCHAR(255) NULL AFTER `komentar`,
ADD COLUMN IF NOT EXISTS `output` VARCHAR(255) NULL AFTER `program`,
ADD COLUMN IF NOT EXISTS `komponen` VARCHAR(255) NULL AFTER `output`;

-- ============================================================
-- 4. ALTER TABLE: kegiatan_petugas
-- Tambah: alat_angkutan (untuk SPPD)
-- ============================================================

ALTER TABLE `kegiatan_petugas` 
ADD COLUMN IF NOT EXISTS `alat_angkutan` VARCHAR(100) NULL DEFAULT 'Kendaraan Umum' AFTER `tujuan`;

-- ============================================================
-- 5. UPDATE DATA SAMPLE - Kepala (role_id = 5)
-- ============================================================

UPDATE `users` SET 
    `gelar_depan` = NULL,
    `gelar_belakang` = 'SST, M.Si',
    `pangkat` = 'Statistisi Ahli Muda',
    `golongan` = 'III/c',
    `jabatan` = 'Kepala BPS Kabupaten Paniai'
WHERE `role_id` = 5;

-- ============================================================
-- 6. UPDATE DATA SAMPLE - PPK (role_id = 2)
-- ============================================================

UPDATE `users` SET 
    `gelar_depan` = NULL,
    `gelar_belakang` = 'SST',
    `pangkat` = 'Statistisi Ahli Pertama',
    `golongan` = 'III/b',
    `jabatan` = 'Pejabat Pembuat Komitmen'
WHERE `role_id` = 2;

-- ============================================================
-- 7. INSERT ROLES (jika belum ada)
-- ============================================================

INSERT IGNORE INTO `roles` (`id`, `name`) VALUES 
(1, 'admin'),
(2, 'ppk'),
(3, 'bendahara'),
(4, 'operator'),
(5, 'kepala');

-- ============================================================
-- CATATAN STRUKTUR ROLE:
-- role_id = 1 : Admin
-- role_id = 2 : PPK (Pejabat Pembuat Komitmen)
-- role_id = 3 : Bendahara
-- role_id = 4 : Operator / Subject Matter
-- role_id = 5 : Kepala BPS
-- ============================================================

-- ============================================================
-- CATATAN WILAYAH KERJA MITRA:
-- - Paniai
-- - Intan Jaya
-- - Deiyai
-- ============================================================

-- ============================================================
-- CATATAN ALAT ANGKUTAN:
-- - Kendaraan Umum (default)
-- - Kendaraan Dinas
-- - Pesawat
-- - Kapal
-- - Motor
-- - Jalan Kaki
-- ============================================================
