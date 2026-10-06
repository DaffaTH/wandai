<?php
/**
 * WANDAI Sync — Push dari PC Lokal ke Server
 *
 * Dijalankan di PC BPS yang sudah terkoneksi FortiClient VPN.
 * Script ini:
 *   1. Scrape data pegawai dari community.bps.go.id
 *   2. Kirim ke endpoint WANDAI di server BPS
 *
 * Cara jalankan:
 *   php push_to_server.php
 *
 * Atau otomatis via Windows Task Scheduler (lihat daftar_task_scheduler.ps1)
 */

// ── Konfigurasi ────────────────────────────────────────────────────────────
define('COMMUNITY_URL',   'https://community.bps.go.id/portal/index.php?id=2,2,0&kab=9410');
define('WANDAI_ENDPOINT', 'https://www.wandaipaniai.web.bps.go.id/api/receive_sync.php');
define('LOG_FILE',        __DIR__ . '/sync.log');

// Secret key dibaca dari config/sync.php (tidak di-commit ke git)
$syncConfigPath = dirname(__DIR__) . '/config/sync.php';
if (!file_exists($syncConfigPath)) {
    $log("GAGAL: config/sync.php tidak ditemukan. Salin dari config/sync.example.php.");
    exit(1);
}
require_once $syncConfigPath;

// ── Hanya via CLI ──────────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya bisa dijalankan via CLI.\n");
}

$ts = fn() => '[' . date('Y-m-d H:i:s') . '] ';
$log = function(string $msg) use ($ts): void {
    $line = $ts() . $msg . "\n";
    echo $line;
    file_put_contents(LOG_FILE, $line, FILE_APPEND);
};

$log("WANDAI Push Sync dimulai.");
$log("Mengambil data dari: " . COMMUNITY_URL);

// ── 1. Fetch community.bps.go.id ──────────────────────────────────────────
$html = fetchHtml(COMMUNITY_URL);

if ($html === false) {
    $log("GAGAL: Tidak dapat mengakses community.bps.go.id.");
    $log("Pastikan FortiClient VPN BPS sudah aktif dan terkoneksi.");
    exit(1);
}

$log("Halaman berhasil diambil (" . strlen($html) . " byte).");

// ── 2. Parse nama pegawai ──────────────────────────────────────────────────
$employees = parseEmployees($html);

if (empty($employees)) {
    $log("PERINGATAN: Tidak ada data pegawai ditemukan. Struktur halaman mungkin berubah.");
    exit(1);
}

$log("Ditemukan " . count($employees) . " pegawai:");
foreach ($employees as $e) {
    $gelar = trim(($e['gelar_depan'] ?? '') . ' ' . ($e['gelar_belakang'] ?? ''));
    $log("  - {$e['name']}" . ($gelar ? " [{$gelar}]" : ''));
}

// ── 3. Kirim ke server WANDAI ──────────────────────────────────────────────
$log("Mengirim ke: " . WANDAI_ENDPOINT);

$payload = json_encode(['employees' => $employees]);
$result  = postToServer(WANDAI_ENDPOINT, $payload, SYNC_SECRET);

if ($result === false) {
    $log("GAGAL: Tidak dapat menghubungi server WANDAI.");
    exit(1);
}

$response = json_decode($result, true);

if (!($response['success'] ?? false)) {
    $log("GAGAL server: " . ($response['message'] ?? 'Unknown error'));
    exit(1);
}

$log("Sync berhasil: {$response['added']} ditambah, {$response['updated']} diperbarui, {$response['skipped']} tidak berubah.");

if (!empty($response['errors'])) {
    foreach ($response['errors'] as $err) {
        $log("  ERROR: {$err}");
    }
}

$log("Selesai.");
exit(0);

// ── Helper: fetch HTML ─────────────────────────────────────────────────────
function fetchHtml(string $url): string|false
{
    if (!function_exists('curl_init')) {
        $ctx = stream_context_create([
            'http' => ['timeout' => 30, 'user_agent' => 'Mozilla/5.0 WANDAI-Sync/1.0'],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        return @file_get_contents($url, false, $ctx);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 WANDAI-Sync/1.0',
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 200 && $html !== false) ? $html : false;
}

// ── Helper: POST ke server ─────────────────────────────────────────────────
function postToServer(string $url, string $json, string $secret): string|false
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Sync-Secret: ' . $secret,
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 200 && $body !== false) ? $body : false;
}

// ── Helper: parse nama pegawai dari HTML ───────────────────────────────────
function parseEmployees(string $html): array
{
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $xpath     = new DOMXPath($doc);
    $employees = [];
    $seen      = [];

    // Strategi 1: link dengan parameter nip= (profil pegawai)
    $nodes = $xpath->query('//a[contains(@href,"nip=")]');
    if ($nodes && $nodes->length > 0) {
        foreach ($nodes as $n) {
            addEmployee(trim($n->textContent), $employees, $seen);
        }
    }

    // Strategi 2: link di dalam blok setelah heading "Pegawai"
    if (empty($employees)) {
        foreach ($xpath->query('//*[normalize-space(text())="Pegawai"]') as $h) {
            foreach ($xpath->query('.//a', $h->parentNode) as $a) {
                addEmployee(trim($a->textContent), $employees, $seen);
            }
        }
    }

    // Strategi 3: semua link konten, filter nama kabupaten
    if (empty($employees)) {
        foreach ($xpath->query('//div[contains(@class,"content") or contains(@class,"main")]//a') as $a) {
            $text = trim($a->textContent);
            if (!preg_match('/BPS (Kabupaten|Kota|Propinsi|Provinsi)/i', $text)) {
                addEmployee($text, $employees, $seen);
            }
        }
    }

    return $employees;
}

function addEmployee(string $raw, array &$list, array &$seen): void
{
    $raw = trim($raw);
    if (strlen($raw) < 3) return;

    $parsed = parseName($raw);
    if (strlen($parsed['name']) < 3) return;

    $key = strtolower(preg_replace('/\s+/', ' ', $parsed['name']));
    if (isset($seen[$key])) return;

    $seen[$key] = true;
    $list[]     = $parsed;
}

function parseName(string $raw): array
{
    $raw      = trim($raw);
    $gelarDep = null;
    $gelarBel = null;
    $name     = $raw;

    if (preg_match('/^((?:Dr|Drs|Ir|Prof|H|Hj)\.)\s+/i', $name, $m)) {
        $gelarDep = $m[1];
        $name     = substr($name, strlen($m[0]));
    }

    $unit    = '(?:[A-Z][A-Za-z]*\.[A-Za-z.]+|[A-Z]{2,4})';
    $pattern = '/\s+(' . $unit . '(?:,\s*' . $unit . ')*)\s*$/';

    if (preg_match($pattern, $name, $m)) {
        $candidate = trim($m[1]);
        if (str_contains($candidate, '.') || preg_match('/^[A-Z]{2,4}$/', $candidate)) {
            $gelarBel = $candidate;
            $name     = trim(substr($name, 0, -strlen($m[0])));
        }
    }

    return [
        'nama_asli'      => $raw,
        'name'           => trim($name),
        'gelar_depan'    => $gelarDep,
        'gelar_belakang' => $gelarBel,
        'jabatan'        => null,
    ];
}
