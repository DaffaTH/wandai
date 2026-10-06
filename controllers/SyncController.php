<?php
/*
 * WANDAI System - Sync Controller
 * Sinkronisasi data pegawai dari community.bps.go.id (BPS Paniai)
 * Membutuhkan FortiClient VPN BPS aktif untuk mengakses sumber data.
 */
class SyncController
{
    private PDO $db;
    private string $communityUrl = 'https://community.bps.go.id/portal/index.php?id=2,2,0&kab=9410';

    public function __construct()
    {
        require_once 'config/database.php';
        $this->db = $pdo;
    }

    // GET /?controller=sync → halaman UI sync (admin only)
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->requireAdmin();
        include 'views/sync/index.php';
    }

    // POST /?controller=sync&action=fetchPreview → JSON preview perubahan
    public function fetchPreview(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->requireAdmin();

        echo json_encode($this->runFetch());
        exit;
    }

    // POST /?controller=sync&action=apply → terapkan perubahan (dari UI)
    public function apply(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->requireAdmin();

        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $toAdd  = $input['add']    ?? [];
        $toUpdate = $input['update'] ?? [];

        $result = $this->applyChanges($toAdd, $toUpdate);
        echo json_encode($result);
        exit;
    }

    // -----------------------------------------------------------------------
    // Public API — digunakan CLI auto-sync (sync/auto_sync.php)
    // -----------------------------------------------------------------------

    /**
     * Fetch, parse, compare dan kembalikan array diff.
     * Return: ['success'=>bool, 'message'=>string|null, 'diff'=>array|null, ...]
     */
    public function runFetch(): array
    {
        $html = $this->fetchHtml($this->communityUrl);

        if ($html === false) {
            return [
                'success' => false,
                'message' => 'Gagal mengakses community.bps.go.id. Pastikan FortiClient VPN BPS aktif.',
            ];
        }

        $fromCommunity = $this->parseEmployees($html);

        if (empty($fromCommunity)) {
            return [
                'success'      => false,
                'message'      => 'Halaman berhasil diambil tapi tidak ada data pegawai ditemukan. '
                                . 'Struktur halaman mungkin berubah.',
                'html_snippet' => substr(strip_tags($html), 0, 300),
            ];
        }

        $fromLocal = $this->getLocalUsers();
        $diff      = $this->buildDiff($fromCommunity, $fromLocal);

        return [
            'success'         => true,
            'total_community' => count($fromCommunity),
            'total_local'     => count($fromLocal),
            'diff'            => $diff,
        ];
    }

    /**
     * Terapkan perubahan ke database.
     * - Gelar update: diterapkan otomatis (aman).
     * - Pegawai baru: dibuat dengan role Operator, password default, email generated.
     */
    public function applyChanges(array $toAdd, array $toUpdate): array
    {
        $added   = 0;
        $updated = 0;
        $errors  = [];

        foreach ($toUpdate as $emp) {
            try {
                $this->db->prepare(
                    "UPDATE users SET gelar_depan = ?, gelar_belakang = ?, updated_at = NOW() WHERE id = ?"
                )->execute([
                    $emp['gelar_depan']    ?: null,
                    $emp['gelar_belakang'] ?: null,
                    (int)$emp['id'],
                ]);
                $updated++;
            } catch (Exception $e) {
                $errors[] = "Gagal update {$emp['name']}: " . $e->getMessage();
            }
        }

        foreach ($toAdd as $emp) {
            try {
                $email = $this->uniqueEmail($this->generateEmail($emp['name']));
                $this->db->prepare("
                    INSERT INTO users
                        (name, gelar_depan, gelar_belakang, email, password, jabatan, role_id, tampil_matriks, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 6, 1, NOW())
                ")->execute([
                    $emp['name'],
                    $emp['gelar_depan']    ?: null,
                    $emp['gelar_belakang'] ?: null,
                    $email,
                    password_hash('bps' . date('Y'), PASSWORD_DEFAULT),
                    $emp['jabatan']        ?: 'Pegawai BPS',
                ]);
                $added++;
            } catch (Exception $e) {
                $errors[] = "Gagal tambah {$emp['name']}: " . $e->getMessage();
            }
        }

        $this->logSync($added, $updated, count($errors));

        return [
            'success' => true,
            'added'   => $added,
            'updated' => $updated,
            'errors'  => $errors,
        ];
    }

    // -----------------------------------------------------------------------
    // HTTP Fetch
    // -----------------------------------------------------------------------

    private function fetchHtml(string $url): string|false
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) WANDAI-Sync/1.0',
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $html     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno    = curl_errno($ch);
            curl_close($ch);

            if (!$errno && $httpCode === 200 && $html !== false) {
                return $html;
            }
        }

        // Fallback: file_get_contents
        $ctx = stream_context_create([
            'http' => ['timeout' => 30, 'user_agent' => 'Mozilla/5.0 WANDAI-Sync/1.0'],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        return @file_get_contents($url, false, $ctx);
    }

    // -----------------------------------------------------------------------
    // HTML Parser
    // -----------------------------------------------------------------------

    private function parseEmployees(string $html): array
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath     = new DOMXPath($doc);
        $employees = [];
        $seen      = [];

        // Strategi 1: link yang mengandung parameter "nip=" (profil pegawai community.bps.go.id)
        $nodes = $xpath->query('//a[contains(@href,"nip=")]');
        if ($nodes && $nodes->length > 0) {
            foreach ($nodes as $node) {
                $this->addEmployee($node->textContent, $employees, $seen);
            }
        }

        // Strategi 2: link yang ada di dalam blok setelah heading "Pegawai"
        if (empty($employees)) {
            $headings = $xpath->query('//*[normalize-space(text())="Pegawai"]');
            foreach ($headings as $h) {
                $container = $h->parentNode;
                $links = $xpath->query('.//a', $container);
                foreach ($links as $a) {
                    $this->addEmployee($a->textContent, $employees, $seen);
                }
            }
        }

        // Strategi 3: semua link di area konten yang teks-nya bukan nama kabupaten/kota
        if (empty($employees)) {
            $nodes = $xpath->query(
                '//div[contains(@class,"content") or contains(@class,"card") or contains(@class,"main")]//a'
            );
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (!preg_match('/BPS (Kabupaten|Kota|Propinsi|Provinsi)/i', $text)) {
                    $this->addEmployee($text, $employees, $seen);
                }
            }
        }

        // Strategi 4: fallback — teks yang mengikuti "- " dalam tiap baris
        if (empty($employees)) {
            preg_match_all('/[-–]\s+([A-Z][a-zA-Z .,'\']+(?:[A-Z][a-z.]+)*)/', $html, $matches);
            foreach ($matches[1] as $text) {
                $this->addEmployee($text, $employees, $seen);
            }
        }

        return $employees;
    }

    private function addEmployee(string $raw, array &$list, array &$seen): void
    {
        $raw = trim($raw);
        if (strlen($raw) < 3) return;

        $parsed = $this->parseName($raw);
        if (strlen($parsed['name']) < 3) return;

        $key = $this->normalizeName($parsed['name']);
        if (isset($seen[$key])) return;

        $seen[$key] = true;
        $list[]     = $parsed;
    }

    // -----------------------------------------------------------------------
    // Name Parser
    // -----------------------------------------------------------------------

    /**
     * Pisahkan gelar depan, nama, dan gelar belakang dari string penuh.
     *
     * Pola gelar belakang (common di BPS):
     *   - Dot-separated: S.E., S.Stat., S.Tr.Stat., A.Md.Stat., M.Si., M.M.
     *   - All-caps abbreviation: SST, SE, SH, ST, SKM
     *   - Multi-gelar comma: SST, M.Si  /  S.Stat., M.M.
     */
    private function parseName(string $raw): array
    {
        $raw       = trim($raw);
        $gelarDep  = null;
        $gelarBel  = null;
        $name      = $raw;

        // Gelar depan: Dr., Drs., Ir., Prof., H., Hj.
        if (preg_match('/^((?:Dr|Drs|Ir|Prof|H|Hj)\.)\s+/i', $name, $m)) {
            $gelarDep = $m[1];
            $name     = substr($name, strlen($m[0]));
        }

        // Gelar belakang dari akhir string.
        // Satu unit gelar: huruf kapital + titik + alfanumerik/titik  ATAU  2-4 huruf kapital semua.
        // Dipisahkan koma dengan spasi: "SST, M.Si"
        $unit    = '(?:[A-Z][A-Za-z]*\.[A-Za-z.]+|[A-Z]{2,4})';
        $pattern = '/\s+(' . $unit . '(?:,\s*' . $unit . ')*)\s*$/';

        if (preg_match($pattern, $name, $m)) {
            $candidate = trim($m[1]);
            // Validasi: harus mengandung titik atau seluruhnya huruf kapital
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

    // -----------------------------------------------------------------------
    // Diff builder
    // -----------------------------------------------------------------------

    private function getLocalUsers(): array
    {
        return $this->db->query(
            "SELECT id, name, gelar_depan, gelar_belakang FROM users"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildDiff(array $fromCommunity, array $fromLocal): array
    {
        $localIdx = [];
        foreach ($fromLocal as $u) {
            $localIdx[$this->normalizeName($u['name'])] = $u;
        }

        $new = $gelarUpdate = $unchanged = [];

        foreach ($fromCommunity as $emp) {
            $key = $this->normalizeName($emp['name']);

            if (!isset($localIdx[$key])) {
                $new[] = $emp;
                continue;
            }

            $local = $localIdx[$key];
            $gelarChanged = (($local['gelar_depan'] ?? '') !== ($emp['gelar_depan'] ?? ''))
                         || (($local['gelar_belakang'] ?? '') !== ($emp['gelar_belakang'] ?? ''));

            if ($gelarChanged) {
                $gelarUpdate[] = $emp + [
                    'id'                   => $local['id'],
                    'local_gelar_depan'    => $local['gelar_depan'],
                    'local_gelar_belakang' => $local['gelar_belakang'],
                ];
            } else {
                $unchanged[] = $emp;
            }
        }

        // Di lokal tapi tidak ada di community (tidak dihapus otomatis)
        $communityKeys = array_map(fn($e) => $this->normalizeName($e['name']), $fromCommunity);
        $missing       = [];
        foreach ($fromLocal as $u) {
            if (!in_array($this->normalizeName($u['name']), $communityKeys)) {
                $missing[] = $u;
            }
        }

        return compact('new', 'gelarUpdate', 'unchanged', 'missing');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function normalizeName(string $name): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($name)));
    }

    private function generateEmail(string $name): string
    {
        $parts = array_filter(explode(' ', strtolower(preg_replace('/[^a-z\s]/i', '', $name))));
        $local = implode('.', array_slice(array_values($parts), 0, 2));
        return ($local ?: 'pegawai') . '@bps-paniai.go.id';
    }

    private function uniqueEmail(string $base): string
    {
        $stmt  = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
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

    private function logSync(int $added, int $updated, int $errors): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS sync_log (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                sync_time  DATETIME     DEFAULT NOW(),
                added      INT          DEFAULT 0,
                updated    INT          DEFAULT 0,
                errors     INT          DEFAULT 0,
                source     VARCHAR(255) DEFAULT NULL
            )");
            $this->db->prepare(
                "INSERT INTO sync_log (added, updated, errors, source) VALUES (?, ?, ?, ?)"
            )->execute([$added, $updated, $errors, 'community.bps.go.id']);
        } catch (Exception $e) {
            // log tidak krusial
        }
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role_name'] ?? '') !== 'admin') {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
                exit;
            }
            header('Location: ?controller=auth&action=login');
            exit;
        }
    }
}
