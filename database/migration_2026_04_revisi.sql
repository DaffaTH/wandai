-- =====================================================================
-- WANDAI Revisi 2026-04 — Matriks Dashboard, Multi-Jenis Kegiatan,
-- Input Administrasi baru, Dokumen Kegiatan, dll.
--
-- Jalankan satu kali di phpMyAdmin database `wandai`.
-- Aman untuk dijalankan ulang: pakai IF NOT EXISTS / INSERT IGNORE.
-- =====================================================================

-- 1) Warna tim (untuk matriks dashboard)
-- MySQL 8.0+ mendukung IF NOT EXISTS pada ADD COLUMN. Kalau versi lebih
-- lama, jalankan perintah `ALTER TABLE teams ADD COLUMN warna ...` manual.
ALTER TABLE teams
  ADD COLUMN IF NOT EXISTS warna VARCHAR(7) DEFAULT '#6c757d' AFTER description;

-- Seed warna default berdasarkan nama tim yang umum (aman kalau tidak match).
UPDATE teams SET warna = '#3b82f6' WHERE LOWER(name) LIKE '%sosial%'   AND (warna IS NULL OR warna = '' OR warna = '#6c757d');
UPDATE teams SET warna = '#22c55e' WHERE LOWER(name) LIKE '%produksi%' AND (warna IS NULL OR warna = '' OR warna = '#6c757d');
UPDATE teams SET warna = '#f59e0b' WHERE LOWER(name) LIKE '%distribusi%' AND (warna IS NULL OR warna = '' OR warna = '#6c757d');
UPDATE teams SET warna = '#a855f7' WHERE (LOWER(name) LIKE '%nerwilis%' OR LOWER(name) LIKE '%neraca%') AND (warna IS NULL OR warna = '' OR warna = '#6c757d');
UPDATE teams SET warna = '#ef4444' WHERE LOWER(name) LIKE '%ipds%'     AND (warna IS NULL OR warna = '' OR warna = '#6c757d');
UPDATE teams SET warna = '#14b8a6' WHERE LOWER(name) LIKE '%umum%'     AND (warna IS NULL OR warna = '' OR warna = '#6c757d');

-- 2) Kegiatan bisa banyak jenis (1 kegiatan_detail -> N baris jenis)
CREATE TABLE IF NOT EXISTS kegiatan_jenis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kegiatan_id INT NOT NULL,
  jenis ENUM('Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan') NOT NULL,
  satuan VARCHAR(50) NULL,
  tanggal_mulai DATE NULL,
  tanggal_selesai DATE NULL,
  program VARCHAR(255) NULL,
  output VARCHAR(255) NULL,
  komponen VARCHAR(255) NULL,
  honor_satuan DECIMAL(15,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_keg_jenis (kegiatan_id, jenis),
  KEY idx_keg (kegiatan_id),
  CONSTRAINT fk_kj_keg FOREIGN KEY (kegiatan_id)
    REFERENCES kegiatan_detail(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Inda/Innas untuk Pelatihan/Briefing (anggota tim, multi-pilih)
CREATE TABLE IF NOT EXISTS kegiatan_innas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kegiatan_jenis_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_innas (kegiatan_jenis_id, user_id),
  KEY idx_kj (kegiatan_jenis_id),
  KEY idx_user (user_id),
  CONSTRAINT fk_ki_kj FOREIGN KEY (kegiatan_jenis_id)
    REFERENCES kegiatan_jenis(id) ON DELETE CASCADE,
  CONSTRAINT fk_ki_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Backfill kegiatan_jenis dari kegiatan_detail lama.
-- INSERT IGNORE memakai UNIQUE(kegiatan_id,jenis) -> re-run aman.
INSERT IGNORE INTO kegiatan_jenis
  (kegiatan_id, jenis, satuan, tanggal_mulai, tanggal_selesai,
   program, output, komponen, honor_satuan)
SELECT
  id,
  CASE
    WHEN jenis_kegiatan IN ('Updating','Updating/Listing','Listing')      THEN 'Updating/Listing'
    WHEN jenis_kegiatan IN ('Pengolahan')                                  THEN 'Pengolahan'
    WHEN jenis_kegiatan IN ('Pelatihan','Briefing','Pelatihan/Briefing') THEN 'Pelatihan/Briefing'
    ELSE 'Pendataan'
  END AS jenis,
  satuan,
  rentang_waktu_mulai,
  rentang_waktu_selesai,
  program,
  output,
  komponen,
  COALESCE(honor_satuan, 0)
FROM kegiatan_detail;

-- 5) Dokumen administrasi per kegiatan (tab "Kegiatan" di Input Administrasi)
CREATE TABLE IF NOT EXISTS administrasi_kegiatan_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kegiatan_jenis_id INT NOT NULL,
  jenis_dokumen VARCHAR(60) NOT NULL,
  -- kak, form_permintaan, daftar_nominatif,
  -- surat_tugas_innas, surat_undangan, absensi,
  -- laporan_kegiatan, laporan_innas, dokumentasi,
  -- surat_tugas_petugas, spd, visum_responden,
  -- laporan_perjalanan, bukti_perjalanan, dokumentasi_lapangan,
  -- dokumentasi_pengolahan
  innas_user_id INT NULL,
  nama_file VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  uploaded_by INT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_kj (kegiatan_jenis_id),
  KEY idx_jd (jenis_dokumen),
  CONSTRAINT fk_akf_kj FOREIGN KEY (kegiatan_jenis_id)
    REFERENCES kegiatan_jenis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Tidak ada kolom tambahan untuk status pembayaran:
--    tetap pakai administrasi.status_bendahara + verified_bendahara_at.
--    Logika "lunas" = status_bendahara = 'disetujui'; tanggal_lunas = verified_bendahara_at.
