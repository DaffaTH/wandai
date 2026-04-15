<?php
/*
 * WANDAI System - Helper Terbilang & Tanggal Indonesia
 * BPS Kabupaten Paniai
 *
 * Dipakai oleh DokumenController untuk generate:
 *  - SPK   (Terbilang honorarium, Tanggal/Bulan/Tahun latin untuk klausul surat)
 *  - BAST  (Hari/Tanggal/Bulan/Tahun latin)
 *  - SPD   (opsional untuk lama perjalanan)
 *
 * Semua fungsi idempoten & bebas state. Boleh di-require_once berkali-kali.
 */

if (!function_exists('terbilang')) {
    /**
     * Konversi angka bulat ke huruf Indonesia (tanpa "Rupiah").
     * terbilang(12)     => "dua belas"
     * terbilang(2610000) => "dua juta enam ratus sepuluh ribu"
     */
    function terbilang(int $n): string
    {
        $n = abs($n);
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima',
                   'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($n < 12)              return $satuan[$n];
        if ($n < 20)              return trim(terbilang($n - 10) . ' belas');
        if ($n < 100)             return trim(terbilang(intdiv($n, 10)) . ' puluh ' . terbilang($n % 10));
        if ($n < 200)             return trim('seratus ' . terbilang($n - 100));
        if ($n < 1000)            return trim(terbilang(intdiv($n, 100)) . ' ratus ' . terbilang($n % 100));
        if ($n < 2000)            return trim('seribu ' . terbilang($n - 1000));
        if ($n < 1000000)         return trim(terbilang(intdiv($n, 1000)) . ' ribu ' . terbilang($n % 1000));
        if ($n < 1000000000)      return trim(terbilang(intdiv($n, 1000000)) . ' juta ' . terbilang($n % 1000000));
        if ($n < 1000000000000)   return trim(terbilang(intdiv($n, 1000000000)) . ' miliar ' . terbilang($n % 1000000000));
        return trim(terbilang(intdiv($n, 1000000000000)) . ' triliun ' . terbilang($n % 1000000000000));
    }
}

if (!function_exists('terbilangRupiah')) {
    /**
     * Contoh: terbilangRupiah(2610000) => "Dua Juta Enam Ratus Sepuluh Ribu Rupiah"
     */
    function terbilangRupiah(int $n): string
    {
        $text = trim(preg_replace('/\s+/', ' ', terbilang($n)));
        return ucwords($text) . ' Rupiah';
    }
}

if (!function_exists('hariIndo')) {
    /**
     * hariIndo('2026-01-12') => "Senin"
     */
    function hariIndo(string $date): string
    {
        $map = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $ts = strtotime($date);
        if (!$ts) return '-';
        return $map[date('l', $ts)] ?? '-';
    }
}

if (!function_exists('bulanIndo')) {
    /**
     * bulanIndo('2026-01-12') => "Januari"
     */
    function bulanIndo(string $date): string
    {
        $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $ts = strtotime($date);
        if (!$ts) return '-';
        return $bulan[(int)date('m', $ts)] ?? '-';
    }
}
