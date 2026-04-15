-- =====================================================
-- WANDAI System - Database Migration
-- Update tabel kegiatan_petugas dengan kolom baru
-- =====================================================

-- Tambahkan kolom baru untuk dokumen SPPD dan informasi surat
ALTER TABLE kegiatan_petugas
ADD COLUMN IF NOT EXISTS asal VARCHAR(100) DEFAULT '' AFTER keterangan,
ADD COLUMN IF NOT EXISTS tujuan TEXT DEFAULT NULL AFTER asal,
ADD COLUMN IF NOT EXISTS no_spk VARCHAR(100) DEFAULT '' AFTER tujuan,
ADD COLUMN IF NOT EXISTS no_bast VARCHAR(100) DEFAULT '' AFTER no_spk,
ADD COLUMN IF NOT EXISTS no_surat_tugas VARCHAR(100) DEFAULT '' AFTER no_bast,
ADD COLUMN IF NOT EXISTS no_sppd VARCHAR(100) DEFAULT '' AFTER no_surat_tugas,
ADD COLUMN IF NOT EXISTS tanggal_surat DATE DEFAULT NULL AFTER no_sppd,
ADD COLUMN IF NOT EXISTS periode_mulai DATE DEFAULT NULL AFTER tanggal_surat,
ADD COLUMN IF NOT EXISTS periode_selesai DATE DEFAULT NULL AFTER periode_mulai;

-- Jika kolom no_spd sudah ada, rename menjadi no_sppd
-- ALTER TABLE kegiatan_petugas CHANGE COLUMN no_spd no_sppd VARCHAR(100) DEFAULT '';

-- Update tabel dokumen_kontrak untuk jenis dokumen SPPD
UPDATE dokumen_kontrak SET jenis_dokumen = 'sppd' WHERE jenis_dokumen = 'spd';

-- =====================================================
-- Verifikasi struktur tabel
-- =====================================================
-- DESCRIBE kegiatan_petugas;

-- =====================================================
-- Pastikan role_id sesuai untuk PPK dan Kepala
-- role_id = 2 untuk PPK
-- role_id = 5 untuk Kepala
-- =====================================================
-- SELECT id, name, role_id FROM users WHERE role_id IN (2, 5);
