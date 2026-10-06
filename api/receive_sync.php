<?php
/**
 * WANDAI API — Receive Sync Pegawai
 *
 * Endpoint ini menerima data pegawai dari script push di PC lokal.
 * Hanya menerima POST dengan secret key yang valid.
 *
 * URL: https://www.wandaipaniai.web.bps.go.id/api/receive_sync.php
 */

header('Content-Type: application/json; charset=utf-8');

// ── Load konfigurasi (secret key ada di config/sync.php, tidak di-commit) ──
require_once dirname(__DIR__) . '/config/sync.php';

// ── Validasi method ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// ── Validasi secret key (dari header atau POST body) ──────────────────────
$secret = $_SERVER['HTTP_X_SYNC_SECRET'] ?? '';
if ($secret !== SYNC_SECRET) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// ── Parse input JSON ───────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['employees']) || !is_array($input['employees'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Format data tidak valid. Field "employees" diperlukan.']));
}

$employees = $input['employees'];

// ── Koneksi DB ─────────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/config/database.php';

// ── Proses sync ────────────────────────────────────────────────────────────
$added   = 0;
$updated = 0;
$skipped = 0;
$errors  = [];

foreach ($employees as $emp) {
    $name        = trim($emp['name']        ?? '');
    $gelarDepan  = trim($emp['gelar_depan'] ?? '') ?: null;
    $gelarBelakang = trim($emp['gelar_belakang'] ?? '') ?: null;
    $jabatan     = trim($emp['jabatan']     ?? '') ?: null;

    if (strlen($name) < 2) continue;

    $nameNorm = strtolower(preg_replace('/\s+/', ' ', $name));

    try {
        // Cek apakah pegawai sudah ada (cocokkan nama tanpa case/spasi)
        $stmt = $pdo->prepare(
            "SELECT id, gelar_depan, gelar_belakang FROM users
             WHERE LOWER(TRIM(name)) = ?"
        );
        $stmt->execute([$nameNorm]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update gelar jika berubah
            $gelarBerubah = (($existing['gelar_depan'] ?? '') !== ($gelarDepan ?? ''))
                         || (($existing['gelar_belakang'] ?? '') !== ($gelarBelakang ?? ''));

            if ($gelarBerubah) {
                $pdo->prepare(
                    "UPDATE users SET gelar_depan = ?, gelar_belakang = ?, updated_at = NOW() WHERE id = ?"
                )->execute([$gelarDepan, $gelarBelakang, $existing['id']]);
                $updated++;
            } else {
                $skipped++;
            }
        } else {
            // Pegawai baru — generate email unik
            $emailBase = generateEmail($name);
            $email     = uniqueEmail($pdo, $emailBase);

            $pdo->prepare("
                INSERT INTO users
                    (name, gelar_depan, gelar_belakang, email, password, jabatan, role_id, tampil_matriks, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 6, 1, NOW())
            ")->execute([
                $name,
                $gelarDepan,
                $gelarBelakang,
                $email,
                password_hash('bps' . date('Y'), PASSWORD_DEFAULT),
                $jabatan ?: 'Pegawai BPS',
            ]);
            $added++;
        }
    } catch (Exception $e) {
        $errors[] = "Error [{$name}]: " . $e->getMessage();
    }
}

// ── Log sync ───────────────────────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sync_log (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        sync_time DATETIME     DEFAULT NOW(),
        added     INT          DEFAULT 0,
        updated   INT          DEFAULT 0,
        errors    INT          DEFAULT 0,
        source    VARCHAR(255) DEFAULT NULL
    )");
    $pdo->prepare(
        "INSERT INTO sync_log (added, updated, errors, source) VALUES (?, ?, ?, ?)"
    )->execute([$added, $updated, count($errors), 'push:community.bps.go.id']);
} catch (Exception $e) {}

// ── Respons ────────────────────────────────────────────────────────────────
echo json_encode([
    'success'   => true,
    'received'  => count($employees),
    'added'     => $added,
    'updated'   => $updated,
    'skipped'   => $skipped,
    'errors'    => $errors,
    'timestamp' => date('Y-m-d H:i:s'),
]);

// ── Helper functions ───────────────────────────────────────────────────────
function generateEmail(string $name): string
{
    $parts = array_filter(explode(' ', strtolower(preg_replace('/[^a-z\s]/i', '', $name))));
    $local = implode('.', array_slice(array_values($parts), 0, 2));
    return ($local ?: 'pegawai') . '@bps-paniai.go.id';
}

function uniqueEmail(PDO $pdo, string $base): string
{
    $stmt  = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $email = $base;
    $i     = 0;
    while (true) {
        $stmt->execute([$email]);
        if (!$stmt->fetchColumn()) break;
        $i++;
        $email = str_replace('@', $i . '@', $base);
    }
    return $email;
}
