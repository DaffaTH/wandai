<?php
/**
 * WANDAI System — Seeder Users / Roles / Teams / User_Teams
 * BPS Kabupaten Paniai
 *
 * Re-seed total tabel kredensial:
 *   roles  (1=admin, 2=Kepala, 3=Kasubbag, 4=PPK, 5=Bendahara, 6=Operator)
 *   teams  (1=Tim Sosial, 2=Tim Produksi, 3=Tim Distribusi, 4=Tim Nerwilis, 5=Tim IPDS)
 *   users  (21 user sesuai daftar resmi)
 *   user_teams (relasi banyak-ke-banyak operator ↔ tim)
 *
 * SEMUA password awal: Bps9605!  (di-hash bcrypt sebelum disimpan).
 *
 * CARA PAKAI (PILIH SALAH SATU):
 *   1) Browser : http://localhost/repmandat/database/seed_users.php?confirm=YES_DELETE_ALL_USERS
 *   2) CLI     : php database/seed_users.php --confirm
 *
 * Tanpa flag confirm, script hanya menampilkan rencana dan keluar.
 *
 * PERINGATAN: Operasi ini meng-TRUNCATE tabel users / roles / teams / user_teams.
 *             FK ke tabel lain (kegiatan_detail.edited_by, kegiatan_innas.user_id,
 *             dst.) di-bypass dengan SET FOREIGN_KEY_CHECKS=0; row di tabel-tabel
 *             tersebut yang merujuk user lama akan menggantung — jalankan saat
 *             database masih dalam tahap dev / belum punya data produksi.
 */

declare(strict_types=1);

$isCli = (PHP_SAPI === 'cli');
$confirmed = $isCli
    ? in_array('--confirm', $argv ?? [], true)
    : (($_GET['confirm'] ?? '') === 'YES_DELETE_ALL_USERS');

// -------- Koneksi DB --------
chdir(__DIR__ . '/..');
require __DIR__ . '/../config/database.php';
/** @var PDO $pdo */

// -------- Spesifikasi data --------
$roles = [
    1 => 'admin',
    2 => 'Kepala',
    3 => 'Kasubbag',
    4 => 'PPK',
    5 => 'Bendahara',
    6 => 'Operator',
];

$teams = [
    1 => 'Tim Sosial',
    2 => 'Tim Produksi',
    3 => 'Tim Distribusi',
    4 => 'Tim Nerwilis',
    5 => 'Tim IPDS',
];

// 21 user — id konsekutif 1..21, role_id = 1..5 untuk id 1..5, sisanya 6 (Operator)
$users = [
    ['id'=>1,  'name'=>'administrator',                       'gelar_belakang'=>null,         'nip'=>null,                'jabatan'=>null,                                'golongan'=>null,  'pangkat'=>null,                  'email'=>'kabupatenpaniai.bps',  'role_id'=>1],
    ['id'=>2,  'name'=>'Khaerul Umam',                        'gelar_belakang'=>'SST, M.Si',  'nip'=>'198402012008011010','jabatan'=>'Kepala',                            'golongan'=>'IV/a','pangkat'=>'Pembina',             'email'=>'khaerul_umam',         'role_id'=>2],
    ['id'=>3,  'name'=>'John Marselino Alfonso Akwan',         'gelar_belakang'=>'SST',        'nip'=>'199403292016021001','jabatan'=>'Kepala Subbagian Umum',             'golongan'=>'III/c','pangkat'=>'Penata',             'email'=>'john.alfonso',         'role_id'=>3],
    ['id'=>4,  'name'=>'Christin Septiana',                    'gelar_belakang'=>'SST',        'nip'=>'199509242018022001','jabatan'=>'Statistisi Ahli Pertama',           'golongan'=>'III/b','pangkat'=>'Penata Muda Tk. I',  'email'=>'christin.septiana',    'role_id'=>4],
    ['id'=>5,  'name'=>'Calvino Pablo Dinova',                 'gelar_belakang'=>'S.Tr.Stat.', 'nip'=>'200111262023101003','jabatan'=>'Statistisi Ahli Pertama',           'golongan'=>'III/a','pangkat'=>'Penata Muda',        'email'=>'pablo.dinova',         'role_id'=>5],
    ['id'=>6,  'name'=>'Daniel Butar Butar',                   'gelar_belakang'=>'A.Md.Stat.', 'nip'=>'200105112022011001','jabatan'=>'Statistisi Pelaksana/Terampil',     'golongan'=>'II/d','pangkat'=>'Pengatur Tk. I',     'email'=>'daniel.butarbutar',    'role_id'=>6],
    ['id'=>7,  'name'=>'Azmi Faisal',                          'gelar_belakang'=>'S.Tr.Stat.', 'nip'=>'200007232023021005','jabatan'=>'Statistisi Ahli Pertama',           'golongan'=>'III/a','pangkat'=>'Penata Muda',        'email'=>'azmifaisal',           'role_id'=>6],
    ['id'=>8,  'name'=>'Tarsisius Bandur',                     'gelar_belakang'=>null,         'nip'=>'199401142025211028','jabatan'=>'Pelaksana',                         'golongan'=>'I/c', 'pangkat'=>'Juru',                'email'=>'tarsisiusb-pppk',      'role_id'=>6],
    ['id'=>9,  'name'=>"Muhammad Almas Yafi'",                 'gelar_belakang'=>'S.Tr.Stat.', 'nip'=>'200108182024121001','jabatan'=>'Statistisi Ahli Pertama',           'golongan'=>'III/a','pangkat'=>'Penata Muda',        'email'=>'almas.yafi',           'role_id'=>6],
    ['id'=>10, 'name'=>'Rezky Maharani',                       'gelar_belakang'=>'A.Md.Stat.', 'nip'=>'200303012026032001','jabatan'=>'Pelaksana',                         'golongan'=>'II/c','pangkat'=>'Pengatur',            'email'=>'rezkymaharani',        'role_id'=>6],
    ['id'=>11, 'name'=>'Miftachul Rachman Dinda',              'gelar_belakang'=>'S.Tr.Stat.', 'nip'=>'199712122021041002','jabatan'=>'Statistisi Ahli Pertama',           'golongan'=>'III/b','pangkat'=>'Penata Muda Tk. I',  'email'=>'rachman.dinda',        'role_id'=>6],
    ['id'=>12, 'name'=>'Amos Holombau',                        'gelar_belakang'=>null,         'nip'=>'198410022009111001','jabatan'=>'Pelaksana',                         'golongan'=>'II/d','pangkat'=>'Pengatur Tk. I',     'email'=>'amos.holombau',        'role_id'=>6],
    ['id'=>13, 'name'=>'Yanuarius Madai',                      'gelar_belakang'=>'S.E.',       'nip'=>'198312122010031001','jabatan'=>'Pelaksana',                         'golongan'=>'III/d','pangkat'=>'Penata Tk. I',       'email'=>'ymadai',               'role_id'=>6],
    ['id'=>14, 'name'=>'Isayas Kudiai',                        'gelar_belakang'=>null,         'nip'=>'197903022002121002','jabatan'=>'Pelaksana',                         'golongan'=>'III/b','pangkat'=>'Penata Muda Tk. I',  'email'=>'isayas',               'role_id'=>6],
    ['id'=>15, 'name'=>'Yance Mbogou Belau',                   'gelar_belakang'=>null,         'nip'=>'197310132006041012','jabatan'=>'Pelaksana',                         'golongan'=>'III/a','pangkat'=>'Penata Muda',        'email'=>'yance.belau',          'role_id'=>6],
    ['id'=>16, 'name'=>'Dian Nastiti Indrayanti',              'gelar_belakang'=>'S.Stat.',    'nip'=>'199310292019032001','jabatan'=>'Statistisi Ahli Pertama',           'golongan'=>'III/b','pangkat'=>'Penata Muda Tk. I',  'email'=>'dian.nastiti',         'role_id'=>6],
    ['id'=>17, 'name'=>'Muhammad Daffa Taufiq Hadikara',       'gelar_belakang'=>'S.Tr.Stat.', 'nip'=>'200001112023101003','jabatan'=>'Pranata Komputer Ahli Pertama',     'golongan'=>'III/a','pangkat'=>'Penata Muda',        'email'=>'daffa.taufiq',         'role_id'=>6],
    ['id'=>18, 'name'=>'Wardiman Sianturi',                    'gelar_belakang'=>'S.Tr.Stat.', 'nip'=>'199608232022011001','jabatan'=>'Statistisi Ahli Pertama',           'golongan'=>'III/a','pangkat'=>'Penata Muda',        'email'=>'wardiman.sianturi',    'role_id'=>6],
    ['id'=>19, 'name'=>'Krisbana Togar Sianturi',              'gelar_belakang'=>'S.Tr.Stat.', 'nip'=>'200007042023021004','jabatan'=>'Pranata Komputer Ahli Pertama',     'golongan'=>'III/a','pangkat'=>'Penata Muda',        'email'=>'sianturi.bana',        'role_id'=>6],
    ['id'=>20, 'name'=>'Andi Iriadi Cahya',                    'gelar_belakang'=>'SST',        'nip'=>'199404052017011001','jabatan'=>'Pelaksana',                         'golongan'=>'III/c','pangkat'=>'Penata',             'email'=>'andi.cahya',           'role_id'=>6],
    ['id'=>21, 'name'=>'Deviani Dyah Widyastuti Ramandey',     'gelar_belakang'=>'S.Tr.Stat.', 'nip'=>'199612282019122001','jabatan'=>'Pelaksana',                         'golongan'=>'III/b','pangkat'=>'Penata Muda Tk. I',  'email'=>'deviani.dyah',         'role_id'=>6],
];

// Mapping team → user_id list (sesuai spek user)
$teamMembers = [
    1 => [6, 7, 11],     // Tim Sosial
    2 => [16, 17, 8],    // Tim Produksi
    3 => [7, 9, 11],     // Tim Distribusi
    4 => [18, 9],        // Tim Nerwilis
    5 => [19, 17, 9],    // Tim IPDS
];

// Bangun user→[teams] (urutan = urutan kemunculan; pertama = primary)
$userTeams = [];
foreach ($teamMembers as $teamId => $members) {
    foreach ($members as $uid) {
        $userTeams[$uid][] = $teamId;
    }
}

// -------- Output helpers --------
$isHtml = !$isCli;
$nl = $isHtml ? "<br>\n" : "\n";

function out(string $msg, string $nl): void { echo $msg . $nl; }

if ($isHtml) echo "<pre style='font-family:Consolas,monospace;font-size:13px;line-height:1.4;background:#0f172a;color:#e2e8f0;padding:18px;border-radius:8px;'>";

out('=== WANDAI Seeder: Users / Roles / Teams ===', $nl);
out('Mode      : ' . ($isCli ? 'CLI' : 'Browser'), $nl);
out('Confirmed : ' . ($confirmed ? 'YES' : 'NO (dry-run)'), $nl);
out('', $nl);
out('Akan meng-INSERT:', $nl);
out('  - ' . count($roles) . ' role',  $nl);
out('  - ' . count($teams) . ' tim',   $nl);
out('  - ' . count($users) . ' user',  $nl);
out('  - ' . array_sum(array_map('count', $teamMembers)) . ' baris user_teams', $nl);
out('', $nl);

if (!$confirmed) {
    out('>> Dry-run. Tambahkan flag berikut untuk benar-benar menjalankan:', $nl);
    out('   - Browser : tambah ?confirm=YES_DELETE_ALL_USERS pada URL', $nl);
    out('   - CLI     : php database/seed_users.php --confirm', $nl);
    if ($isHtml) echo "</pre>";
    exit;
}

// -------- Eksekusi --------
try {
    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    out('-- Truncating user_teams, users, roles, teams', $nl);
    $pdo->exec('TRUNCATE TABLE user_teams');
    $pdo->exec('TRUNCATE TABLE users');
    $pdo->exec('TRUNCATE TABLE roles');
    $pdo->exec('TRUNCATE TABLE teams');

    // Roles
    out('-- Insert roles', $nl);
    $stmt = $pdo->prepare("INSERT INTO roles (id, name, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
    foreach ($roles as $id => $name) {
        $stmt->execute([$id, $name]);
        out("   role #{$id} = {$name}", $nl);
    }

    // Teams (warna default — bisa diubah lewat menu Warna Tim)
    out('-- Insert teams', $nl);
    $teamPalette = [
        1 => '#0ea5e9', // Sosial — biru
        2 => '#22c55e', // Produksi — hijau
        3 => '#f97316', // Distribusi — oranye
        4 => '#a855f7', // Nerwilis — ungu
        5 => '#ef4444', // IPDS — merah
    ];
    $stmt = $pdo->prepare("INSERT INTO teams (id, name, description, warna, created_at, updated_at) VALUES (?, ?, NULL, ?, NOW(), NOW())");
    foreach ($teams as $id => $name) {
        $stmt->execute([$id, $name, $teamPalette[$id] ?? '#6c757d']);
        out("   team #{$id} = {$name}", $nl);
    }

    // Users
    out('-- Insert users (password = Bps9605! di-hash bcrypt)', $nl);
    $stmt = $pdo->prepare("
        INSERT INTO users
            (id, name, gelar_depan, gelar_belakang, email, password, nip,
             pangkat, golongan, jabatan, role_id, team_id, created_at, updated_at)
        VALUES
            (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $defaultHash = password_hash('Bps9605!', PASSWORD_DEFAULT);
    foreach ($users as $u) {
        $primaryTeam = $userTeams[$u['id']][0] ?? null;
        $stmt->execute([
            $u['id'],
            $u['name'],
            $u['gelar_belakang'],
            $u['email'],
            $defaultHash,
            $u['nip'],
            $u['pangkat'],
            $u['golongan'],
            $u['jabatan'],
            $u['role_id'],
            $primaryTeam,
        ]);
        $tag = $primaryTeam ? "team#{$primaryTeam}" : 'no-team';
        out(sprintf("   user #%-2d role#%d %-10s %s", $u['id'], $u['role_id'], $tag, $u['name']), $nl);
    }

    // user_teams (junction)
    out('-- Insert user_teams (junction)', $nl);
    $stmt = $pdo->prepare("INSERT INTO user_teams (user_id, team_id, is_primary, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    foreach ($userTeams as $userId => $teamList) {
        $first = true;
        foreach ($teamList as $teamId) {
            $stmt->execute([$userId, $teamId, $first ? 1 : 0]);
            $first = false;
        }
    }
    out('   ' . array_sum(array_map('count', $teamMembers)) . ' baris user_teams', $nl);

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    $pdo->commit();

    out('', $nl);
    out('=== SELESAI ===', $nl);
    out('Semua password awal: Bps9605!  (silakan ganti via halaman "Akun Saya").', $nl);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    @ $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    out('!! GAGAL: ' . $e->getMessage(), $nl);
    if ($isHtml) echo "</pre>";
    exit(1);
}

if ($isHtml) echo "</pre>";
