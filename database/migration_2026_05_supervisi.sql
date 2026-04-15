-- =====================================================================
-- WANDAI — Migrasi Supervisi (Kepala & Kasubbag)
-- Tanggal: 2026-04-14
--
-- Tujuan:
--   1. Tambah role 'Kasubbag' (id=6) ke tabel roles.
--   2. Tambah nilai 'Supervisi' ke ENUM `kegiatan_petugas.peran`
--      supaya Kepala/Kasubbag bisa dicatat sebagai petugas supervisi
--      tanpa masuk alur PML→PPL.
--
-- Catatan domain:
--   - Supervisi TIDAK punya bawahan (no PML→PPL mapping).
--   - Supervisi TIDAK punya target/realisasi (biarkan 0 / abaikan).
--   - Supervisi TETAP dapat dokumen Surat Tugas + SPD.
-- =====================================================================

-- 1) Tambah role Kasubbag
INSERT INTO roles (id, name)
SELECT 6, 'Kasubbag'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE id = 6);

-- 2) Perluas ENUM peran agar mendukung 'Supervisi'.
--    Gunakan default 'PPL' seperti sebelumnya supaya tidak merusak
--    baris existing.
ALTER TABLE kegiatan_petugas
  MODIFY COLUMN peran ENUM('PML','PPL','Supervisi') NOT NULL DEFAULT 'PPL';

-- =====================================================================
-- Verifikasi (manual):
--   SELECT * FROM roles;
--   SHOW COLUMNS FROM kegiatan_petugas LIKE 'peran';
-- =====================================================================
