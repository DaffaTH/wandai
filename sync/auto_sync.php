<?php
/**
 * WANDAI Auto-Sync Pegawai — CLI Runner
 *
 * Dijalankan otomatis via Windows Task Scheduler.
 * Membutuhkan FortiClient VPN BPS aktif saat dijalankan.
 *
 * Cara jalankan manual:
 *   php C:\wandai\sync\auto_sync.php
 *
 * Perilaku:
 *   - Update gelar (gelar_depan, gelar_belakang) untuk pegawai yang sudah ada: OTOMATIS
 *   - Tambah pegawai baru: OTOMATIS (role Operator, password default bpsYYYY)
 *   - Hapus pegawai: TIDAK PERNAH (harus manual dari UI)
 */

// Pastikan hanya dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya bisa dijalankan via CLI.\n");
}

define('WANDAI_ROOT', dirname(__DIR__));

chdir(WANDAI_ROOT);
require_once WANDAI_ROOT . '/controllers/SyncController.php';

// ── Jalankan sync ──────────────────────────────────────────────────────────
$sync   = new SyncController();
$ts     = date('Y-m-d H:i:s');

echo "[{$ts}] WANDAI Auto-Sync dimulai...\n";
echo "[{$ts}] Mengambil data dari community.bps.go.id...\n";

$fetch = $sync->runFetch();

if (!$fetch['success']) {
    echo "[{$ts}] GAGAL: {$fetch['message']}\n";
    exit(1);
}

$diff = $fetch['diff'];
echo "[{$ts}] Community: {$fetch['total_community']} pegawai | "
   . "Baru: " . count($diff['new']) . " | "
   . "Gelar update: " . count($diff['gelarUpdate']) . " | "
   . "Tidak di community: " . count($diff['missing']) . "\n";

if (empty($diff['new']) && empty($diff['gelarUpdate'])) {
    echo "[{$ts}] Tidak ada perubahan. Sync selesai.\n";
    exit(0);
}

$result = $sync->applyChanges($diff['new'], $diff['gelarUpdate']);

echo "[{$ts}] Ditambah : {$result['added']} pegawai\n";
echo "[{$ts}] Diperbarui: {$result['updated']} gelar\n";

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $err) {
        echo "[{$ts}] ERROR: {$err}\n";
    }
}

echo "[{$ts}] Sync selesai.\n";
exit(0);
