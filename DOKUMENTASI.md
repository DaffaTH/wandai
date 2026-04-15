# WANDAI System - Dokumentasi Update
## BPS Kabupaten Paniai

---

## ⚠️ PENTING: URUTAN IMPLEMENTASI

### LANGKAH 1: Jalankan SQL Migration TERLEBIH DAHULU

Sebelum menggunakan file PHP, **WAJIB** jalankan SQL berikut di phpMyAdmin:

```sql
-- 1. Tambah kolom NIP di tabel users
ALTER TABLE `users` ADD COLUMN `nip` VARCHAR(20) DEFAULT NULL AFTER `name`;

-- 2. Tambah kolom-kolom baru di tabel kegiatan_petugas
ALTER TABLE `kegiatan_petugas` ADD COLUMN `asal` VARCHAR(100) DEFAULT '' AFTER `keterangan`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `tujuan` TEXT DEFAULT NULL AFTER `asal`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `no_spk` VARCHAR(100) DEFAULT '' AFTER `tujuan`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `no_bast` VARCHAR(100) DEFAULT '' AFTER `no_spk`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `no_surat_tugas` VARCHAR(100) DEFAULT '' AFTER `no_bast`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `no_spd` VARCHAR(100) DEFAULT '' AFTER `no_surat_tugas`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `tanggal_surat` DATE DEFAULT NULL AFTER `no_spd`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `periode_mulai` DATE DEFAULT NULL AFTER `tanggal_surat`;
ALTER TABLE `kegiatan_petugas` ADD COLUMN `periode_selesai` DATE DEFAULT NULL AFTER `periode_mulai`;
```

### LANGKAH 2: Update NIP untuk PPK dan Kepala

```sql
-- Ganti ID dan NIP sesuai data Anda
UPDATE users SET nip = '196501011990011001' WHERE id = [ID_PPK];
UPDATE users SET nip = '196801011988011001' WHERE id = [ID_KEPALA];
```

### LANGKAH 3: Copy File PHP ke Server

1. `models/KegiatanPetugas.php` → `htdocs/repmandat/models/`
2. `controllers/Petugas_kegiatanController.php` → `htdocs/repmandat/controllers/`
3. Tambahkan kode dari `TAMBAHAN_DOWNLOAD_DOKUMEN.php` ke view index.php

---

## 📋 PENJELASAN ERROR

### Error: `Unknown column 'kp.no_spd' in 'field list'`

**Penyebab:** Kolom `no_spd`, `asal`, `tujuan`, dll belum ada di tabel `kegiatan_petugas`.

**Solusi:** Jalankan SQL migration di LANGKAH 1.

### Error: `Unknown column 'nip' in 'field list'`

**Penyebab:** Kolom `nip` belum ada di tabel `users`.

**Solusi:** Jalankan SQL: `ALTER TABLE users ADD COLUMN nip VARCHAR(20) DEFAULT NULL AFTER name;`

---

## 🆕 FITUR BARU

### 1. PPK dan Kepala dengan NIP

- Method `getPPK()` dan `getKepala()` sekarang mengembalikan NIP
- NIP akan digunakan di template dokumen

### 2. Download Semua Dokumen per Kegiatan

Fitur baru untuk download semua dokumen petugas dalam 1 kegiatan:

- **Download Semua (ZIP)**: Menghasilkan ZIP dengan 4 file PDF:
  - `SPK_[NamaKegiatan].pdf` - Semua SPK digabung jadi 1 PDF
  - `BAST_[NamaKegiatan].pdf` - Semua BAST digabung jadi 1 PDF
  - `Surat_Tugas_[NamaKegiatan].pdf` - Semua Surat Tugas digabung jadi 1 PDF
  - `SPPD_[NamaKegiatan].pdf` - Semua SPPD digabung jadi 1 PDF

- **Download per Jenis**: Download 1 jenis dokumen saja

### Cara Menggunakan:
1. Pilih kegiatan di filter
2. Card "Download Semua Dokumen" akan muncul
3. Klik tombol yang diinginkan

---

## 📁 STRUKTUR FILE

```
/models/
  └── KegiatanPetugas.php     ← Model dengan NIP dan download dokumen

/controllers/
  └── Petugas_kegiatanController.php  ← Controller dengan action download

/views/petugas_kegiatan/
  └── index.php               ← Tambahkan kode dari TAMBAHAN_DOWNLOAD_DOKUMEN.php
  └── TAMBAHAN_DOWNLOAD_DOKUMEN.php  ← Kode untuk card download

database_migration.sql        ← SQL untuk update database
```

---

## 🔧 CARA MENAMBAHKAN KE VIEW

Buka file `views/petugas_kegiatan/index.php` dan tambahkan kode dari `TAMBAHAN_DOWNLOAD_DOKUMEN.php` **SETELAH Card Filter** dan **SEBELUM Card Data Tabel**.

Cari baris:
```php
</div>  <!-- end card filter -->

<!-- Card Data Tabel -->
```

Tambahkan kode di antara keduanya.

---

## ✅ CHECKLIST IMPLEMENTASI

- [ ] Backup database
- [ ] Jalankan `database_migration.sql`
- [ ] Update NIP untuk PPK dan Kepala
- [ ] Copy `models/KegiatanPetugas.php`
- [ ] Copy `controllers/Petugas_kegiatanController.php`
- [ ] Tambahkan kode download dokumen ke view
- [ ] Test halaman Input Petugas
- [ ] Test fitur download dokumen

---

## 📞 TROUBLESHOOTING

### Jika masih error setelah migration:

1. Cek struktur tabel:
```sql
DESCRIBE kegiatan_petugas;
DESCRIBE users;
```

2. Pastikan kolom sudah ada:
- `kegiatan_petugas`: asal, tujuan, no_spk, no_bast, no_surat_tugas, no_spd, tanggal_surat, periode_mulai, periode_selesai
- `users`: nip

3. Jika ada kolom yang belum ada, jalankan ALTER TABLE secara individual.

### Jika download PDF tidak berfungsi:

1. Pastikan library FPDI terinstall: `composer require setasign/fpdi`
2. Atau pastikan `pdfunite` tersedia di server (Linux)
3. Cek path file PDF di `dokumen_kontrak.file_path_pdf`
