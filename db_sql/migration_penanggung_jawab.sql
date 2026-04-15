-- Migrasi: Tambah kolom Penanggung Jawab Kegiatan untuk BAST Honor
-- Dipakai oleh DokumenController::generateBASTKegiatan()
-- Relasi ke users (biasanya Kepala/Kasubbag/PPK)
-- Dijalankan: 2026-04-15

ALTER TABLE kegiatan_detail
    ADD COLUMN penanggung_jawab_user_id INT(11) NULL AFTER edited_by,
    ADD CONSTRAINT fk_kd_pj
        FOREIGN KEY (penanggung_jawab_user_id) REFERENCES users(id) ON DELETE SET NULL;
