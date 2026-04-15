-- =====================================================================
-- WANDAI — Migrasi tabel user_teams (multi-tim per user)
-- Tanggal: 2026-04-14
--
-- Tujuan:
--   User (khususnya Operator) bisa tergabung di lebih dari satu tim.
--   Tabel ini adalah junction user ↔ teams. Kolom `is_primary` menandai
--   tim utama (dipakai bila butuh satu tim default, misal fallback
--   `users.team_id`).
--
-- Dipakai oleh:
--   - models/User.php::syncUserTeams() — sinkron saat Tambah/Edit anggota
--   - views/tim/index.php — tampilkan semua tim milik user (badge warna)
--   - controllers/Petugas_kegiatanController.php::getUserTeams() — filter
--     kegiatan berdasarkan daftar tim yang diikuti.
-- =====================================================================

CREATE TABLE IF NOT EXISTS user_teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  team_id INT NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_team (user_id, team_id),
  KEY idx_user (user_id),
  KEY idx_team (team_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- Backfill: untuk user lama yang punya users.team_id namun belum ada di
-- user_teams, isi otomatis sebagai tim primary supaya halaman Anggota Tim
-- langsung menampilkan tim mereka.
INSERT IGNORE INTO user_teams (user_id, team_id, is_primary)
SELECT u.id, u.team_id, 1
FROM users u
WHERE u.team_id IS NOT NULL;

-- =====================================================================
-- Verifikasi (manual):
--   SELECT u.name, GROUP_CONCAT(t.name) AS tims
--   FROM users u
--   LEFT JOIN user_teams ut ON ut.user_id = u.id
--   LEFT JOIN teams t ON t.id = ut.team_id
--   GROUP BY u.id;
-- =====================================================================
