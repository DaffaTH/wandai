<?php
/*
 * WANDAI System - Kegiatan Controller
 * BPS Kabupaten Paniai
 * 
 * UPDATED:
 * - Tambah kolom program, output, komponen untuk pembebanan anggaran SPPD
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'models/KegiatanDetail.php';
require_once 'models/KegiatanJenis.php';
require_once 'models/KegiatanInnas.php';

class KegiatanController
{
    public function index()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new KegiatanDetail();
        
        $team_id = $_SESSION['user']['team_id'];
        $role_id = $_SESSION['user']['role_id'];
        
        // Filter
        $filterTeam = $_GET['team_id'] ?? null;
        $filterBulan = $_GET['bulan'] ?? null;
        $filterJenis = $_GET['jenis'] ?? null;

        $kegiatanList = $model->getFiltered($filterTeam, $role_id, $filterBulan, $filterJenis);
        $teams = $model->getAllTeams();
        $jenisList = $model->getJenisKegiatanList();

        // Daftar kandidat Penanggung Jawab kegiatan untuk BAST Honor:
        // role Kepala (2), Kasubbag (3), PPK (4).
        $pjUsers = $this->getPJCandidates();

        include 'views/kegiatan/index.php';
    }

    /**
     * Ambil kandidat Penanggung Jawab (Kepala/Kasubbag/PPK) untuk dropdown.
     * Defensif terhadap kolom `status` pada tabel users yang mungkin belum ada.
     */
    private function getPJCandidates()
    {
        require 'config/database.php';
        $statusClause = '';
        try {
            if ($pdo->query("SHOW COLUMNS FROM users LIKE 'status'")->fetch()) {
                $statusClause = "AND (u.status IS NULL OR u.status = 'aktif')";
            }
        } catch (Exception $e) { /* biarkan kosong */ }

        $stmt = $pdo->query("
            SELECT u.id, u.name, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.role_id IN (2, 3, 4)
            {$statusClause}
            ORDER BY FIELD(u.role_id, 2, 3, 4), u.name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new KegiatanDetail();
        $teams = $model->getAllTeams();

        // Daftar kandidat Inda/Innas untuk Pelatihan/Briefing:
        //   - Semua user aktif tanpa melihat tim (filter per-tim tidak dipakai lagi)
        //   - Admin (role_id = 1) dikecualikan karena tidak pernah bertindak sebagai
        //     Inda/Innas di lapangan
        require 'config/database.php';

        // Defensif: kolom `status` di tabel users mungkin belum ada di DB lama
        $statusClause = '';
        try {
            if ($pdo->query("SHOW COLUMNS FROM users LIKE 'status'")->fetch()) {
                $statusClause = "AND (u.status IS NULL OR u.status = 'aktif')";
            }
        } catch (Exception $e) { /* biarkan kosong */ }

        $stmt = $pdo->query("
            SELECT u.id, u.name, u.team_id, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE (u.role_id IS NULL OR u.role_id <> 1)
            {$statusClause}
            ORDER BY u.name
        ");
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Kandidat Penanggung Jawab (Kepala/Kasubbag/PPK) untuk BAST Honor
        $pjUsers = $this->getPJCandidates();

        include 'views/kegiatan/create.php';
    }

    public function store()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model      = new KegiatanDetail();
        $jenisModel = new KegiatanJenis();
        $innasModel = new KegiatanInnas();

        try {
            // Jenis kegiatan sekarang multi-pilih (checkbox)
            $jenisArr = $_POST['jenis'] ?? [];  // array of jenis strings
            if (!is_array($jenisArr) || empty($jenisArr)) {
                throw new Exception('Minimal satu jenis kegiatan harus dipilih.');
            }
            $valid = ['Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan'];
            $jenisArr = array_values(array_intersect($jenisArr, $valid));
            if (empty($jenisArr)) {
                throw new Exception('Jenis kegiatan tidak valid.');
            }

            // Kumpulkan tgl mulai/selesai dari semua jenis untuk kompatibilitas
            // dengan kolom rentang_waktu_mulai/selesai di kegiatan_detail.
            $rowsData = $_POST['row'] ?? [];  // ['Pelatihan/Briefing' => ['tanggal_mulai'=>..., ...], ...]
            $allStart = [];
            $allEnd   = [];
            foreach ($jenisArr as $jen) {
                $r = $rowsData[$jen] ?? [];
                if (!empty($r['tanggal_mulai']))  $allStart[] = $r['tanggal_mulai'];
                if (!empty($r['tanggal_selesai'])) $allEnd[]   = $r['tanggal_selesai'];
            }
            $minStart = !empty($allStart) ? min($allStart) : null;
            $maxEnd   = !empty($allEnd)   ? max($allEnd)   : null;
            if (!$minStart || !$maxEnd) {
                throw new Exception('Tanggal mulai & selesai wajib diisi untuk setiap jenis.');
            }

            // team_id
            $teamId = $_SESSION['user']['team_id'];
            // role_id baru: 1=admin, 6=operator
            if (in_array($_SESSION['user']['role_id'], [1, 6]) && !empty($_POST['team_id'])) {
                $teamId = $_POST['team_id'];
            }

            // Pakai jenis pertama sbg representasi di kolom string (kompatibilitas).
            // Kolom wajib (non-null) di kegiatan_detail tetap diisi dari jenis non-Pelatihan pertama
            // jika ada, agar satuan/program/output/komponen/honor valid. Pelatihan/Briefing
            // tidak punya satuan, jadi kalau hanya Pelatihan, biarkan default.
            $primary = null;
            foreach ($jenisArr as $jen) {
                if ($jen !== 'Pelatihan/Briefing') { $primary = $rowsData[$jen]; break; }
            }
            if ($primary === null) $primary = $rowsData[$jenisArr[0]] ?? [];

            // Helper: input Honor tampil dengan titik ribuan (400.000).
            // Strip semua non-digit sebelum cast supaya "400.000" tidak
            // jadi 400.0 saat di-cast ke float.
            $parseHonor = function ($raw) {
                if ($raw === null || $raw === '') return null;
                $clean = preg_replace('/[^\d]/', '', (string)$raw);
                return $clean === '' ? null : (float)$clean;
            };

            $kegiatanId = $model->insert([
                'team_id'                   => $teamId,
                'nama_kegiatan'             => trim($_POST['nama_kegiatan']),
                'jenis_kegiatan'            => implode(', ', $jenisArr),
                'rentang_waktu_mulai'       => $minStart,
                'rentang_waktu_selesai'     => $maxEnd,
                'satuan'                    => $primary['satuan']   ?? '',
                'komentar'                  => $_POST['komentar']    ?? null,
                'program'                   => trim($primary['program']   ?? ''),
                'output'                    => trim($primary['output']    ?? ''),
                'komponen'                  => trim($primary['komponen']  ?? ''),
                'honor_satuan'              => $parseHonor($primary['honor_satuan'] ?? null),
                'edited_by'                 => $_SESSION['user']['id'],
                'penanggung_jawab_user_id'  => $_POST['penanggung_jawab_user_id'] ?? null,
            ]);

            // Insert per jenis
            foreach ($jenisArr as $jen) {
                $r = $rowsData[$jen] ?? [];
                $kjId = $jenisModel->insert([
                    'kegiatan_id'     => $kegiatanId,
                    'jenis'           => $jen,
                    'satuan'          => ($jen === 'Pelatihan/Briefing') ? null : ($r['satuan'] ?? null),
                    'tanggal_mulai'   => $r['tanggal_mulai']   ?? null,
                    'tanggal_selesai' => $r['tanggal_selesai'] ?? null,
                    'program'         => ($jen === 'Pelatihan/Briefing') ? null : ($r['program']  ?? null),
                    'output'          => ($jen === 'Pelatihan/Briefing') ? null : ($r['output']   ?? null),
                    'komponen'        => ($jen === 'Pelatihan/Briefing') ? null : ($r['komponen'] ?? null),
                    'honor_satuan'    => ($jen === 'Pelatihan/Briefing') ? 0 : ($parseHonor($r['honor_satuan'] ?? null) ?? 0),
                ]);
                // Innas hanya untuk Pelatihan/Briefing
                if ($jen === 'Pelatihan/Briefing') {
                    $innasIds = $r['innas'] ?? [];
                    if (!is_array($innasIds)) $innasIds = [];
                    $innasModel->sync($kjId, array_map('intval', $innasIds));
                }
            }

            $_SESSION['success'] = 'Kegiatan berhasil ditambahkan';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan kegiatan: ' . $e->getMessage();
        }

        header('Location: index.php?controller=kegiatan');
        exit;
    }

    public function update()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new KegiatanDetail();

        try {
            $valid = ['Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan'];
            $jenisKegiatan = trim((string)($_POST['jenis_kegiatan'] ?? ''));
            if (!in_array($jenisKegiatan, $valid, true)) {
                throw new Exception('Jenis kegiatan tidak valid.');
            }

            $isPelatihan = ($jenisKegiatan === 'Pelatihan/Briefing');

            // Field yang relevan hanya untuk non-Pelatihan. Saat Pelatihan,
            // kolom ini dibiarkan kosong (NULL) di DB.
            $parseHonor = function ($raw) {
                if ($raw === null || $raw === '') return null;
                $clean = preg_replace('/[^\d]/', '', (string)$raw);
                return $clean === '' ? null : (float)$clean;
            };

            $model->update([
                'id'                        => $_POST['id'] ?? 0,
                'nama_kegiatan'             => trim((string)($_POST['nama_kegiatan'] ?? '')),
                'jenis_kegiatan'            => $jenisKegiatan,
                'rentang_waktu_mulai'       => $_POST['rentang_waktu_mulai']   ?? null,
                'rentang_waktu_selesai'     => $_POST['rentang_waktu_selesai'] ?? null,
                'satuan'                    => $isPelatihan ? null : ($_POST['satuan'] ?? null),
                'komentar'                  => $_POST['komentar'] ?? null,
                'program'                   => $isPelatihan ? null : trim((string)($_POST['program']  ?? '')),
                'output'                    => $isPelatihan ? null : trim((string)($_POST['output']   ?? '')),
                'komponen'                  => $isPelatihan ? null : trim((string)($_POST['komponen'] ?? '')),
                'honor_satuan'              => $isPelatihan ? null : $parseHonor($_POST['honor_satuan'] ?? null),
                'edited_by'                 => $_SESSION['user']['id'],
                'penanggung_jawab_user_id'  => $_POST['penanggung_jawab_user_id'] ?? null,
            ]);

            $_SESSION['success'] = 'Kegiatan berhasil diupdate';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal mengupdate kegiatan: ' . $e->getMessage();
        }

        header('Location: index.php?controller=kegiatan');
        exit;
    }

    public function delete()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $id = $_POST['id'] ?? 0;
        $model = new KegiatanDetail();

        try {
            $model->delete($id);
            $_SESSION['success'] = 'Kegiatan berhasil dihapus';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus kegiatan: ' . $e->getMessage();
        }

        header('Location: index.php?controller=kegiatan');
        exit;
    }

    /**
     * API: Get kegiatan detail (JSON)
     */
    public function getDetail()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $id = $_GET['id'] ?? 0;
        $model = new KegiatanDetail();
        $data = $model->getById($id);

        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Data tidak ditemukan']);
        }
        exit;
    }
}