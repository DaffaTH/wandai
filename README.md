# WANDAI System

## Source Code Reference

Source code ini merupakan referensi dari sistem **WANDAI** yang dikembangkan oleh **BPS Kabupaten Paniai**.

---

## Informasi Pengembang

**Developer:** M. Daffa Taufiq H.
**Institusi:** BPS Kabupaten Paniai
**Tahun:** 2025  
**Original Author:** Paniai Team

---

## Tentang Sistem Wandai

Sistem WANDAI adalah aplikasi manajemen administrasi dan tugas yang dikembangkan untuk mendukung operasional BPS Kabupaten Paniai. Sistem ini mencakup fitur-fitur manajemen kegiatan, administrasi, tim, mitra, dan dokumentasi.

---

## Lisensi & Attribution

**PENTING:** Source code ini disediakan sebagai referensi untuk keperluan pembelajaran internal.

### Ketentuan Penggunaan:

1. **Attribution Wajib**
   - Segala pengembangan lanjutan **WAJIB** mencantumkan attribution kepada BPS Kabupaten Paniai
   - Header attribution harus tetap ada di setiap file yang dimodifikasi atau dikembangkan lebih lanjut

2. **Penggunaan Internal**
   - Source code ini diperuntukkan untuk keperluan pembelajaran dan referensi internal
   - Penggunaan untuk keperluan komersial atau distribusi publik memerlukan izin tertulis dari BPS Kabupaten Paniai

3. **Modifikasi & Pengembangan**
   - Setiap modifikasi atau pengembangan lanjutan harus mencantumkan:
     - Attribution kepada pengembang asli (BPS Kabupaten Paniai)
     - Informasi pengembang/modifikator baru
     - Tahun modifikasi

---

## Struktur Proyek

```
repmandat/
├── api/              # API endpoints
├── config/           # Konfigurasi database
├── controllers/      # Controller classes
├── models/           # Model classes
├── views/            # View templates
├── lib/              # Library classes
├── helpers/          # Helper functions
├── public/           # Assets publik (CSS, JS, images)
├── templates/        # Template dokumen (BAST, SPK)
└── documents/        # Dokumen yang dihasilkan
```

---

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL/MariaDB
- Web server (Apache/Nginx)
- Composer (untuk dependency management)

---

## Instalasi

1. Clone atau copy source code ke direktori web server
2. Install dependencies menggunakan Composer:
   ```bash
   composer install
   ```
3. Konfigurasi database di `config/database.php`
4. Import database schema dari `db_sql/Wandai (20).sql`
5. Pastikan folder `public/uploads` dan `documents` memiliki permission write

---

## Kontak & Dukungan

Untuk pertanyaan atau informasi lebih lanjut mengenai sistem Wandai, silakan hubungi:

**BPS Kabupaten Paniai**  
Developer: M. Daffa Taufiq H.

---

## Catatan Penting

⚠️ **PERINGATAN:** Source code ini adalah referensi internal. Setiap replikasi atau pengembangan lanjutan harus mengikuti ketentuan attribution yang telah ditetapkan.

---

**© 2025 BPS Kabupaten Paniai. All Rights Reserved.**

*Dikembangkan dengan dedikasi untuk kemajuan administrasi dan manajemen tugas di BPS Kabupaten Paniai.*

