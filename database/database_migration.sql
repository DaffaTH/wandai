-- =====================================================
-- WANDAI System - Database Migration
-- BPS Kabupaten Paniai
-- JALANKAN SQL INI DI phpMyAdmin ATAU MySQL
-- =====================================================

-- =====================================================
-- 1. TAMBAH KOLOM NIP DI TABEL USERS
-- =====================================================
ALTER TABLE `users` ADD COLUMN `nip` VARCHAR(20) DEFAULT NULL AFTER `name`;

-- =====================================================
-- 2. TAMBAH KOLOM-KOLOM BARU DI TABEL KEGIATAN_PETUGAS
-- =====================================================
ALTER TABLE `kegiatan_petugas` ADD COLUMN `asal` VARCHAR(100) DEFAULT '' AFTER `keterangan`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `tujuan` TEXT DEFAULT NULL AFTER `asal`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `no_spk` VARCHAR(100) DEFAULT '' AFTER `tujuan`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `no_bast` VARCHAR(100) DEFAULT '' AFTER `no_spk`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `no_surat_tugas` VARCHAR(100) DEFAULT '' AFTER `no_bast`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `no_spd` VARCHAR(100) DEFAULT '' AFTER `no_surat_tugas`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `tanggal_surat` DATE DEFAULT NULL AFTER `no_spd`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `periode_mulai` DATE DEFAULT NULL AFTER `tanggal_surat`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `periode_selesai` DATE DEFAULT NULL AFTER `periode_mulai`;

-- =====================================================
-- 3. CONTOH UPDATE NIP UNTUK PPK DAN KEPALA
-- =====================================================
-- Ganti dengan NIP yang sesuai
-- UPDATE users SET nip = '196501011990011001' WHERE role_id = 2;
-- UPDATE users SET nip = '196801011988011001' WHERE role_id = 5;

-- =====================================================
-- CATATAN:
-- - Jika kolom sudah ada, akan muncul error "Duplicate column"
-- - Abaikan error tersebut dan lanjutkan ke baris berikutnya
-- - Jalankan setiap ALTER TABLE satu per satu jika ada masalah
-- =====================================================
