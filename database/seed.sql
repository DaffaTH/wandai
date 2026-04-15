-- WANDAI System - Seed Data
-- Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
-- Year: 2025
-- Initial Data for Testing

USE `wandai`;

-- ============================================
-- Seed Data: roles
-- ============================================
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator sistem dengan akses penuh'),
(2, 'kepala', 'Kepala BPS - Monitoring dan approval'),
(3, 'ppk', 'PPK - Verifikasi administrasi keuangan'),
(4, 'operator', 'Operator - Input data kegiatan'),
(5, 'bendahara', 'Bendahara - Verifikasi keuangan');

-- ============================================
-- Seed Data: teams
-- ============================================
INSERT INTO `teams` (`id`, `name`, `description`) VALUES
(1, 'Tim Sosial', 'Tim untuk kegiatan sosial kependudukan'),
(2, 'Tim Produksi', 'Tim untuk kegiatan produksi pertanian'),
(3, 'Tim Distribusi', 'Tim untuk kegiatan distribusi perdagangan'),
(4, 'Tim Nerwilis', 'Tim neraca wilayah dan analisis statistik');

-- ============================================
-- Seed Data: users
-- Password default: "12345" (plaintext untuk testing)
-- PENTING: Ganti dengan password hash di production!
-- ============================================
INSERT INTO `users` (`id`, `username`, `password`, `name`, `nip`, `role_id`, `team_id`, `status`) VALUES
(1, 'admin', '12345', 'Administrator Sistem', '199001012020121001', 1, 1, 'aktif'),
(2, 'kepala', '12345', 'Kepala BPS Boven Digoel', '198505052010121002', 2, NULL, 'aktif'),
(3, 'ppk', '12345', 'PPK BPS Boven Digoel', '198707072015121003', 3, NULL, 'aktif'),
(4, 'operator1', '12345', 'Operator Tim Sosial', '199203032018121004', 4, 1, 'aktif'),
(5, 'operator2', '12345', 'Operator Tim Produksi', '199304042019121005', 4, 2, 'aktif'),
(6, 'bendahara', '12345', 'Bendahara BPS', '198806062012121006', 5, NULL, 'aktif'),
(7, 'bella', '12345', 'Bella Pradiana', '199505052020122007', 4, 1, 'aktif'),
(8, 'ahmad', '12345', 'Ahmad Hidayat', '199606062021122008', 4, 3, 'aktif');

-- ============================================
-- Seed Data: mitra
-- Password default: "9502" (sesuai dari MitraController)
-- ============================================
INSERT INTO `mitra` (`id`, `username`, `password`, `nama`, `nik`, `alamat`, `telepon`, `email`, `status`) VALUES
(1, 'mitra001', '9502', 'Budi Santoso', '9471012001950001', 'Jl. Merdeka No.10, Tanah Merah', '081234567801', 'budi.santoso@email.com', 'aktif'),
(2, 'mitra002', '9502', 'Siti Aminah', '9471015202960001', 'Jl. Kartini No.15, Tanah Merah', '081234567802', 'siti.aminah@email.com', 'aktif'),
(3, 'mitra003', '9502', 'Agus Setiawan', '9471011501970001', 'Jl. Sudirman No.20, Mindiptana', '081234567803', 'agus.setiawan@email.com', 'aktif'),
(4, 'mitra004', '9502', 'Dewi Lestari', '9471014802980001', 'Jl. Diponegoro No.5, Mindiptana', '081234567804', 'dewi.lestari@email.com', 'aktif'),
(5, 'mitra005', '9502', 'Eko Prasetyo', '9471011201990001', 'Jl. Ahmad Yani No.12, Kawagit', '081234567805', 'eko.prasetyo@email.com', 'aktif');

-- ============================================
-- Seed Data: ppk_data
-- ============================================
INSERT INTO `ppk_data` (`id`, `nip`, `nama`, `jabatan`, `pangkat`, `status`) VALUES
(1, '198707072015121003', 'Drs. Muhammad Yusuf, M.Si', 'Pejabat Pembuat Komitmen', 'Penata Tk.I (III/d)', 'aktif');

-- ============================================
-- Seed Data: kegiatan_detail
-- ============================================
INSERT INTO `kegiatan_detail` (`id`, `nama_kegiatan`, `jenis_kegiatan`, `tim_id`, `tanggal_mulai`, `tanggal_selesai`, `deadline`, `deskripsi`, `lokasi`, `target`, `realisasi`, `status`, `created_by`) VALUES
(1, 'Sensus Ekonomi 2026 - Pendataan Usaha', 'Survei Lapangan', 1, '2025-01-15', '2025-03-15', '2025-03-20', 'Pendataan usaha ekonomi untuk Sensus Ekonomi 2026', 'Distrik Tanah Merah', 500, 150, 'berjalan', 1),
(2, 'Survei Pertanian 2025', 'Survei Lapangan', 2, '2025-02-01', '2025-04-30', '2025-05-05', 'Survei pertanian tanaman pangan dan hortikultura', 'Distrik Mindiptana', 300, 80, 'berjalan', 1),
(3, 'Pemutakhiran Data Statistik Daerah', 'Updating Data', 4, '2025-01-10', '2025-02-28', '2025-03-05', 'Pemutakhiran berbagai data statistik daerah', 'Kabupaten Boven Digoel', 200, 120, 'berjalan', 4),
(4, 'Penyusunan PDRB Triwulan IV 2024', 'Analisis Data', 4, '2024-12-01', '2025-01-31', '2025-02-05', 'Penyusunan data Produk Domestik Regional Bruto', 'BPS Boven Digoel', 1, 1, 'selesai', 1),
(5, 'Survei Harga Konsumen Januari 2025', 'Survei Rutin', 3, '2025-01-05', '2025-01-25', '2025-01-28', 'Pengumpulan data harga konsumen bulanan', 'Pasar Tanah Merah', 150, 150, 'selesai', 5);

-- ============================================
-- Seed Data: kegiatan_petugas
-- ============================================
INSERT INTO `kegiatan_petugas` (`kegiatan_id`, `user_id`, `mitra_id`, `role_tugas`, `status_tugas`, `progress`) VALUES
-- Kegiatan 1: Sensus Ekonomi
(1, 7, NULL, 'Koordinator Lapangan', 'berjalan', 60),
(1, NULL, 1, 'Pencacah', 'berjalan', 50),
(1, NULL, 2, 'Pencacah', 'berjalan', 40),

-- Kegiatan 2: Survei Pertanian
(2, 5, NULL, 'Koordinator', 'berjalan', 70),
(2, NULL, 3, 'Pencacah', 'berjalan', 30),
(2, NULL, 4, 'Pencacah', 'berjalan', 25),

-- Kegiatan 3: Pemutakhiran Data
(3, 8, NULL, 'Petugas Input Data', 'berjalan', 80),
(3, NULL, 5, 'Pencacah', 'berjalan', 70),

-- Kegiatan 4: PDRB (Selesai)
(4, 4, NULL, 'Analis Statistik', 'selesai', 100),

-- Kegiatan 5: Survei Harga (Selesai)
(5, 5, NULL, 'Koordinator', 'selesai', 100),
(5, NULL, 1, 'Pencacah', 'selesai', 100);

-- ============================================
-- Seed Data: dokumen_kontrak
-- ============================================
INSERT INTO `dokumen_kontrak` (`kegiatan_id`, `mitra_id`, `jenis_dokumen`, `nomor_dokumen`, `tanggal_dokumen`, `filepath`, `status`, `created_by`) VALUES
(1, 1, 'SPK', 'SPK/001/BPS-9471/2025', '2025-01-10', 'documents/spk/SPK-001-2025.pdf', 'ditandatangani', 1),
(1, 2, 'SPK', 'SPK/002/BPS-9471/2025', '2025-01-10', 'documents/spk/SPK-002-2025.pdf', 'ditandatangani', 1),
(2, 3, 'SPK', 'SPK/003/BPS-9471/2025', '2025-01-28', 'documents/spk/SPK-003-2025.pdf', 'final', 1),
(2, 4, 'SPK', 'SPK/004/BPS-9471/2025', '2025-01-28', 'documents/spk/SPK-004-2025.pdf', 'final', 1),
(5, 1, 'BAST', 'BAST/001/BPS-9471/2025', '2025-01-25', 'documents/bast/BAST-001-2025.pdf', 'ditandatangani', 1);

-- ============================================
-- Seed Data: administrasi
-- ============================================
INSERT INTO `administrasi` (`kegiatan_id`, `mitra_id`, `jenis_administrasi`, `nomor_administrasi`, `tanggal_pengajuan`, `file_path`, `status_ppk`, `status_bendahara`, `status_kepala`) VALUES
-- Administrasi yang sudah disetujui semua
(5, 1, 'Klaim Honor Pencacahan', 'ADM/001/I/2025', '2025-01-26', 'uploads/administrasi/adm-001.pdf', 'disetujui', 'disetujui', 'disetujui'),

-- Administrasi yang masih proses verifikasi
(1, 1, 'Klaim Honor Bulan Januari', 'ADM/002/I/2025', '2025-02-01', 'uploads/administrasi/adm-002.pdf', 'disetujui', 'pending', 'pending'),
(1, 2, 'Klaim Honor Bulan Januari', 'ADM/003/I/2025', '2025-02-01', 'uploads/administrasi/adm-003.pdf', 'pending', 'pending', 'pending'),
(2, 3, 'Klaim Transport Lapangan', 'ADM/004/II/2025', '2025-02-05', 'uploads/administrasi/adm-004.pdf', 'pending', 'pending', 'pending');

-- ============================================
-- Seed Data: activity_logs (Sample)
-- ============================================
INSERT INTO `activity_logs` (`user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`) VALUES
(1, 'LOGIN', NULL, NULL, 'Admin login to system', '127.0.0.1'),
(1, 'CREATE', 'kegiatan_detail', 1, 'Created new activity: Sensus Ekonomi 2026', '127.0.0.1'),
(1, 'CREATE', 'kegiatan_detail', 2, 'Created new activity: Survei Pertanian 2025', '127.0.0.1'),
(3, 'APPROVE', 'administrasi', 1, 'PPK approved administration ADM/001/I/2025', '127.0.0.1'),
(6, 'APPROVE', 'administrasi', 1, 'Bendahara approved administration ADM/001/I/2025', '127.0.0.1'),
(2, 'APPROVE', 'administrasi', 1, 'Kepala approved administration ADM/001/I/2025', '127.0.0.1');

-- ============================================
-- Verification: Check Data Counts
-- ============================================
SELECT 'Roles' AS TableName, COUNT(*) AS RecordCount FROM roles
UNION ALL
SELECT 'Teams', COUNT(*) FROM teams
UNION ALL
SELECT 'Users', COUNT(*) FROM users
UNION ALL
SELECT 'Mitra', COUNT(*) FROM mitra
UNION ALL
SELECT 'PPK Data', COUNT(*) FROM ppk_data
UNION ALL
SELECT 'Kegiatan', COUNT(*) FROM kegiatan_detail
UNION ALL
SELECT 'Kegiatan Petugas', COUNT(*) FROM kegiatan_petugas
UNION ALL
SELECT 'Dokumen Kontrak', COUNT(*) FROM dokumen_kontrak
UNION ALL
SELECT 'Administrasi', COUNT(*) FROM administrasi
UNION ALL
SELECT 'Activity Logs', COUNT(*) FROM activity_logs;

-- ============================================
-- Display Sample Login Credentials
-- ============================================
SELECT 
    '=== AKUN LOGIN UNTUK TESTING ===' AS Info
UNION ALL
SELECT CONCAT('Username: admin | Password: 12345 | Role: Administrator')
UNION ALL
SELECT CONCAT('Username: kepala | Password: 12345 | Role: Kepala BPS')
UNION ALL
SELECT CONCAT('Username: ppk | Password: 12345 | Role: PPK')
UNION ALL
SELECT CONCAT('Username: operator1 | Password: 12345 | Role: Operator Tim Sosial')
UNION ALL
SELECT CONCAT('Username: bendahara | Password: 12345 | Role: Bendahara')
UNION ALL
SELECT CONCAT('Username: mitra001 | Password: 9502 | Role: Mitra Pencacah')
UNION ALL
SELECT '=================================';

-- ============================================
-- End of Seed Data
-- ============================================
