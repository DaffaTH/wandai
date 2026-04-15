<?php
/*
 * WANDAI System - Petugas Kegiatan Controller
 * BPS Kabupaten Paniai
 * 
 * FIXES:
 * - validateForBulkGenerate -> validateForGenerate (method name fix)
 * - Filter tim tersedia untuk SEMUA roles (Admin + Operator)
 * - Dropdown kegiatan filter dinamis berdasarkan tim
 * - Modal form: SEMUA role dapat memilih SEMUA kegiatan (UPDATED!)
 * - Tambah action simpan untuk alur PML-PPL
 * - FIXED: Operator dapat melihat SEMUA kegiatan dari SEMUA tim
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'models/KegiatanPetugas.php';
require_once 'models/KegiatanDetail.php';
require_once 'models/Mitra.php';
require_once 'models/User.php';

class Petugas_kegiatanController
{
    private $db;

    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }

    /**
     * Get all teams yang dimiliki user (dari users.team_id + user_teams)
     */
    private function getUserTeams($user_id)
    {
        $sql = "SELECT DISTINCT t.id, t.name 
                FROM teams t
                WHERE t.id IN (
                    SELECT team_id FROM users WHERE id = ? AND team_id IS NOT NULL
                    UNION
                    SELECT team_id FROM user_teams WHERE user_id = ?
                )
                ORDER BY t.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get team IDs array untuk user
     */
    private function getUserTeamIds($user_id)
    {
        $teams = $this->getUserTeams($user_id);
        return array_column($teams, 'id');
    }

    public function index()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $petugasModel = new KegiatanPetugas();
        $kegiatanModel = new KegiatanDetail();
        $mitraModel = new Mitra();
        $userModel = new User();

        $user = $_SESSION['user'];
        $user_id = $user['id'];
        $isAdmin = ($user['role_id'] == 1);

        // Filter dari GET
        $filter_team_id = $_GET['team_id'] ?? null;
        $filter_kegiatan_id = $_GET['kegiatan_id'] ?? null;

        // === GET USER'S TEAMS (untuk non-admin) ===
        $userTeams = $this->getUserTeams($user_id);
        $userTeamIds = array_column($userTeams, 'id');

        // === GET ALL TEAMS (untuk filter dropdown) ===
        // SEMUA ROLE bisa melihat semua tim di filter
        $stmt = $this->db->query("SELECT id, name FROM teams ORDER BY name");
        $allTeams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // === GET KEGIATAN (untuk filter - berdasarkan tim yang dipilih) ===
        // Filter: menampilkan SEMUA kegiatan (akan difilter by tim jika tim dipilih)
        if (!empty($filter_team_id)) {
            $allKegiatans = $kegiatanModel->getByTeam($filter_team_id);
        } else {
            // Semua kegiatan dari semua tim untuk filter
            $allKegiatans = $kegiatanModel->getAll();
        }

        // === KEGIATAN UNTUK FORM MODAL (berbeda dengan filter) ===
        // Admin: semua kegiatan | Operator: hanya kegiatan dari tim yang diikuti
        if ($isAdmin) {
            $kegiatansForForm = $kegiatanModel->getAll();
        } else {
            // Operator: hanya kegiatan dari tim yang diikuti
            $kegiatansForForm = $this->getKegiatanByMultipleTeams($userTeamIds);
        }

        // === GET ASSIGNMENTS (data petugas) ===
        if (!empty($filter_kegiatan_id)) {
            // Filter by kegiatan
            $assignments = $petugasModel->listByTeam(null, $filter_kegiatan_id);
        } elseif (!empty($filter_team_id)) {
            // Filter by team
            $assignments = $petugasModel->listByTeam($filter_team_id, null);
        } elseif ($isAdmin) {
            // Admin tanpa filter: semua
            $assignments = $petugasModel->listByTeam(null, null);
        } else {
            // Non-admin tanpa filter: gabungkan dari semua tim user
            $assignments = $this->getAssignmentsByMultipleTeams($userTeamIds, $petugasModel);
        }

        // === GET MITRA & PEGAWAI (untuk dropdown modal) ===
        $mitraList = $mitraModel->getAktif();
        $pegawaiList = $userModel->getPegawaiAktif();
        
        // === PETUGAS OPTIONS (gabungan untuk dropdown PML/PPL) ===
        $petugasOptions = [];
        foreach ($pegawaiList as $u) {
            $petugasOptions[] = [
                'id' => $u['id'],
                'nama' => $u['nama_lengkap'] ?? $u['name'],
                'identifier' => $u['email'] ?? $u['nip'] ?? '-',
                'source' => 'users',
                // role_id dipakai di modal untuk mendeteksi Kepala/Kasubbag
                // → auto-switch ke alur Supervisi (tanpa PPL, tanpa target/realisasi).
                'role_id' => (int)($u['role_id'] ?? 0),
            ];
        }
        foreach ($mitraList as $m) {
            $petugasOptions[] = [
                'id' => $m['id'],
                'nama' => $m['nama'],
                'identifier' => $m['nik'] ?? $m['email'] ?? '-',
                'source' => 'mitra',
                'role_id' => 0,
            ];
        }

        // === PPK & Kepala (untuk dokumen) ===
        $ppkList = $petugasModel->getPPK();
        $kepalaList = $petugasModel->getKepala();

        // === Alat Angkutan ===
        $alatAngkutanList = KegiatanPetugas::getAlatAngkutanList();

        include 'views/petugas_kegiatan/index.php';
    }

    /**
     * Get kegiatan dari multiple teams
     */
    private function getKegiatanByMultipleTeams($teamIds)
    {
        if (empty($teamIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $sql = "SELECT kd.id, kd.nama_kegiatan, kd.jenis_kegiatan, kd.team_id, t.name AS team_name
                FROM kegiatan_detail kd
                LEFT JOIN teams t ON t.id = kd.team_id
                WHERE kd.team_id IN ($placeholders)
                ORDER BY kd.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($teamIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get assignments dari multiple teams
     */
    private function getAssignmentsByMultipleTeams($teamIds, $petugasModel)
    {
        if (empty($teamIds)) return [];
        
        $allAssignments = [];
        foreach ($teamIds as $teamId) {
            $assignments = $petugasModel->listByTeam($teamId, null);
            $allAssignments = array_merge($allAssignments, $assignments);
        }
        
        // Remove duplicates by id
        $unique = [];
        foreach ($allAssignments as $a) {
            $unique[$a['id']] = $a;
        }
        
        return array_values($unique);
    }

    /**
     * Store petugas (single)
     */
    public function store()
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new KegiatanPetugas();

        try {
            // Tentukan petugas_id berdasarkan source
            $petugas_source = $_POST['petugas_source'] ?? 'mitra';
            $petugas_id = $_POST['petugas_id'] ?? null;
            
            // Jika pegawai, ambil dari field yang berbeda
            if ($petugas_source === 'users' && empty($petugas_id)) {
                $petugas_id = $_POST['petugas_id_pegawai'] ?? null;
            }

            // === VALIDASI HONOR MITRA ===
            // Cek apakah mitra sudah ikut kegiatan OB atau melebihi batas honor
            if ($petugas_source === 'mitra' && $petugas_id) {
                $honorCheck = $model->checkMitraHonorLimit($petugas_id);
                
                if ($honorCheck['is_blocked']) {
                    $mitraModel = new Mitra();
                    $mitra = $mitraModel->getById($petugas_id);
                    $nama = $mitra['nama'] ?? 'Mitra';
                    $currentHonor = number_format($honorCheck['current_honor'], 0, ',', '.');
                    
                    // Pesan error berbeda untuk OB dan honor melebihi batas
                    if ($honorCheck['has_ob_kegiatan']) {
                        $_SESSION['error'] = "Peringatan: Mitra {$nama} sudah mengikuti kegiatan dengan satuan OB pada bulan ini. Mitra tidak dapat ditambahkan ke kegiatan lain pada bulan yang sama.";
                    } else {
                        $_SESSION['error'] = "Peringatan: Honor mitra {$nama} sudah mencapai Rp {$currentHonor} pada bulan ini. Mitra tidak dapat ditambahkan ke kegiatan baru karena sudah melebihi batas honor (Rp 4.000.000).";
                    }
                    header('Location: index.php?controller=petugas_kegiatan');
                    exit;
                }
            }

            $model->add([
                'kegiatan_detail_id' => $_POST['kegiatan_detail_id'],
                'petugas_id' => $petugas_id,
                'petugas_source' => $petugas_source,
                'peran' => $_POST['peran'] ?? 'PPL',
                'target' => $_POST['target'] ?? 0,
                'realisasi' => $_POST['realisasi'] ?? 0,
                'keterangan' => $_POST['keterangan'] ?? '',
                'pml_id' => $_POST['pml_id'] ?? null,
                'asal' => $_POST['asal'] ?? 'Enarotali',
                'tujuan' => $_POST['tujuan'] ?? '',
                'alat_angkutan' => $_POST['alat_angkutan'] ?? 'Kendaraan Umum',
                'no_spk' => $_POST['no_spk'] ?? '',
                'no_bast' => $_POST['no_bast'] ?? '',
                'no_surat_tugas' => $_POST['no_surat_tugas'] ?? '',
                'no_sppd' => $_POST['no_sppd'] ?? '',
                'tanggal_surat' => !empty($_POST['tanggal_surat']) ? $_POST['tanggal_surat'] : null,
                'periode_mulai' => !empty($_POST['periode_mulai']) ? $_POST['periode_mulai'] : null,
                'periode_selesai' => !empty($_POST['periode_selesai']) ? $_POST['periode_selesai'] : null
            ]);

            $_SESSION['success'] = 'Petugas berhasil ditambahkan';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan petugas: ' . $e->getMessage();
        }

        header('Location: index.php?controller=petugas_kegiatan');
        exit;
    }

    /**
     * Simpan PML + PPL sekaligus (dari modal Step 1/2/3)
     */
    public function simpan()
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new KegiatanPetugas();
        $mitraModel = new Mitra();

        try {
            $kegiatan_id = $_POST['kegiatan_detail_id'];
            $keterangan = $_POST['keterangan'] ?? '';
            
            // Flag: user sudah konfirmasi honor melebihi batas
            $confirmHonorExceed = isset($_POST['confirm_honor_exceed']) && $_POST['confirm_honor_exceed'] == '1';
            
            // ========== AMBIL INFO KEGIATAN ==========
            $kegiatanDetailModel = new KegiatanDetail();
            $kegiatan = $kegiatanDetailModel->getById($kegiatan_id);
            
            $bulanKegiatan = null;
            $tahunKegiatan = null;
            $honorSatuan = 0;
            $namaKegiatan = $kegiatan['nama_kegiatan'] ?? 'Kegiatan';
            $satuanKegiatan = strtoupper($kegiatan['satuan'] ?? '');
            
            if ($kegiatan) {
                if (!empty($kegiatan['rentang_waktu_mulai'])) {
                    $tanggalMulai = new DateTime($kegiatan['rentang_waktu_mulai']);
                    $bulanKegiatan = $tanggalMulai->format('m');
                    $tahunKegiatan = $tanggalMulai->format('Y');
                }
                $honorSatuan = (float) ($kegiatan['honor_satuan'] ?? 0);
            }
            
            // ========== DATA PML & PPL ==========
            $pml_id = $_POST['pml_id'];
            $pml_source = $_POST['pml_source'];
            $pml_target = (int) ($_POST['pml_target'] ?? 0);
            $ppl_ids = $_POST['ppl_id'] ?? [];
            $ppl_sources = $_POST['ppl_source'] ?? [];
            $ppl_targets = $_POST['ppl_target'] ?? [];

            // ========== ALUR SUPERVISI (Kepala / Kasubbag) ==========
            // Flag `is_supervisi` dikirim modal bila user yang dipilih di Step 1
            // ber-role Kepala (role_id=2) atau Kasubbag (role_id=3). Alur ini
            // bypass total Step 2/3 (tanpa PPL, target/realisasi = 0).
            // Verifikasi ulang di server supaya tidak bisa di-bypass dengan
            // memalsukan field hidden.
            $isSupervisiRequested = isset($_POST['is_supervisi']) && $_POST['is_supervisi'] === '1';
            if ($isSupervisiRequested && $pml_source === 'users' && $pml_id) {
                $chkStmt = $this->db->prepare("SELECT role_id FROM users WHERE id = ?");
                $chkStmt->execute([$pml_id]);
                $roleId = (int)($chkStmt->fetchColumn() ?: 0);

                if (in_array($roleId, [2, 3], true)) {
                    // Cek duplikasi
                    if ($model->isPetugasInKegiatan($kegiatan_id, $pml_id, 'users')) {
                        $userModel = new User();
                        $u = $userModel->getById($pml_id);
                        $nm = $u['name'] ?? 'Pegawai';
                        $_SESSION['error'] = "Petugas {$nm} sudah terdaftar pada kegiatan {$namaKegiatan}.";
                        header('Location: index.php?controller=petugas_kegiatan');
                        exit;
                    }

                    $model->add([
                        'kegiatan_detail_id' => $kegiatan_id,
                        'petugas_id'         => $pml_id,
                        'petugas_source'     => 'users',
                        'peran'              => 'Supervisi',
                        'target'             => 0,
                        'realisasi'          => 0,
                        'keterangan'         => $keterangan,
                        'pml_id'             => null,
                        'asal'               => 'Enarotali',
                        'tujuan'             => '',
                        'alat_angkutan'      => 'Kendaraan Umum',
                    ]);

                    $_SESSION['success'] = 'Berhasil menambahkan 1 petugas Supervisi';
                    header('Location: index.php?controller=petugas_kegiatan');
                    exit;
                }
                // role_id tidak cocok → fallback ke alur PML/PPL biasa
            }
            
            // ========== 1. VALIDASI DUPLIKASI ==========
            $duplicatePetugas = [];
            
            // Cek duplikasi PML
            if ($model->isPetugasInKegiatan($kegiatan_id, $pml_id, $pml_source)) {
                if ($pml_source === 'mitra') {
                    $mitra = $mitraModel->getById($pml_id);
                    $duplicatePetugas[] = $mitra['nama'] ?? 'Mitra';
                } else {
                    $userModel = new User();
                    $user = $userModel->getById($pml_id);
                    $duplicatePetugas[] = $user['name'] ?? 'Pegawai';
                }
            }
            
            // Cek duplikasi PPL
            foreach ($ppl_ids as $i => $ppl_id) {
                $ppl_source = $ppl_sources[$i] ?? '';
                if (!empty($ppl_id) && !empty($ppl_source)) {
                    if ($model->isPetugasInKegiatan($kegiatan_id, $ppl_id, $ppl_source)) {
                        if ($ppl_source === 'mitra') {
                            $mitra = $mitraModel->getById($ppl_id);
                            $duplicatePetugas[] = $mitra['nama'] ?? 'Mitra';
                        } else {
                            $userModel = new User();
                            $user = $userModel->getById($ppl_id);
                            $duplicatePetugas[] = $user['name'] ?? 'Pegawai';
                        }
                    }
                }
            }
            
            // Error jika ada duplikasi
            if (!empty($duplicatePetugas)) {
                $uniqueNames = array_unique($duplicatePetugas);
                $_SESSION['error'] = "Petugas berikut sudah terdaftar pada kegiatan {$namaKegiatan}: " . implode(', ', $uniqueNames) . ". Tidak dapat menambahkan petugas yang sama.";
                header('Location: index.php?controller=petugas_kegiatan');
                exit;
            }
            
            // ========== 2. VALIDASI OB (BLOKIR TOTAL) ==========
            $obMitra = [];
            
            // Cek OB untuk PML
            if ($pml_source === 'mitra' && $pml_id) {
                $honorCheck = $model->checkMitraHonorLimit($pml_id, $bulanKegiatan, $tahunKegiatan, 0);
                if ($honorCheck['has_ob_kegiatan']) {
                    $mitra = $mitraModel->getById($pml_id);
                    $obMitra[] = $mitra['nama'] ?? 'Mitra';
                }
            }
            
            // Cek OB untuk PPL
            foreach ($ppl_ids as $i => $ppl_id) {
                $ppl_source = $ppl_sources[$i] ?? '';
                if ($ppl_source === 'mitra' && $ppl_id) {
                    $honorCheck = $model->checkMitraHonorLimit($ppl_id, $bulanKegiatan, $tahunKegiatan, 0);
                    if ($honorCheck['has_ob_kegiatan']) {
                        $mitra = $mitraModel->getById($ppl_id);
                        $obMitra[] = $mitra['nama'] ?? 'Mitra';
                    }
                }
            }
            
            // Error jika ada mitra yang diblokir karena OB
            if (!empty($obMitra)) {
                $uniqueOB = array_unique($obMitra);
                $_SESSION['error'] = "Mitra berikut sudah mengikuti kegiatan dengan satuan OB pada bulan ini: " . implode(', ', $uniqueOB) . ". Mitra tidak dapat ditambahkan ke kegiatan lain pada bulan yang sama.";
                header('Location: index.php?controller=petugas_kegiatan');
                exit;
            }
            
            // ========== 3. VALIDASI PROYEKSI HONOR > 4 JUTA (PERLU KONFIRMASI) ==========
            if (!$confirmHonorExceed) {
                $needsConfirmMitra = [];
                $warningLimit = 4000000;
                
                // Cek proyeksi honor PML
                if ($pml_source === 'mitra' && $pml_id) {
                    $honorBaruPML = $pml_target * $honorSatuan;
                    $honorCheck = $model->checkMitraHonorLimit($pml_id, $bulanKegiatan, $tahunKegiatan, $honorBaruPML);
                    
                    if ($honorCheck['projected_honor'] > $warningLimit) {
                        $mitra = $mitraModel->getById($pml_id);
                        $needsConfirmMitra[] = [
                            'nama' => $mitra['nama'] ?? 'Mitra',
                            'peran' => 'PML',
                            'current' => $honorCheck['current_honor'],
                            'baru' => $honorBaruPML,
                            'proyeksi' => $honorCheck['projected_honor']
                        ];
                    }
                }
                
                // Cek proyeksi honor PPL
                foreach ($ppl_ids as $i => $ppl_id) {
                    $ppl_source = $ppl_sources[$i] ?? '';
                    $ppl_target = (int) ($ppl_targets[$i] ?? 0);
                    
                    if ($ppl_source === 'mitra' && $ppl_id) {
                        $honorBaruPPL = $ppl_target * $honorSatuan;
                        $honorCheck = $model->checkMitraHonorLimit($ppl_id, $bulanKegiatan, $tahunKegiatan, $honorBaruPPL);
                        
                        if ($honorCheck['projected_honor'] > $warningLimit) {
                            $mitra = $mitraModel->getById($ppl_id);
                            $needsConfirmMitra[] = [
                                'nama' => $mitra['nama'] ?? 'Mitra',
                                'peran' => 'PPL',
                                'current' => $honorCheck['current_honor'],
                                'baru' => $honorBaruPPL,
                                'proyeksi' => $honorCheck['projected_honor']
                            ];
                        }
                    }
                }
                
                // Jika ada yang perlu konfirmasi, simpan ke session dan redirect ke index dengan flag
                if (!empty($needsConfirmMitra)) {
                    $_SESSION['honor_confirm_pending'] = [
                        'form_data' => $_POST,
                        'mitra_list' => $needsConfirmMitra,
                        'kegiatan_nama' => $namaKegiatan
                    ];
                    header('Location: index.php?controller=petugas_kegiatan&show_honor_confirm=1');
                    exit;
                }
            }
            
            // ========== SIMPAN PML ==========
            $pml_realisasi = $_POST['pml_realisasi'] ?? 0;
            
            // Simpan PML
            $pml_kegiatan_id = $model->add([
                'kegiatan_detail_id' => $kegiatan_id,
                'petugas_id' => $pml_id,
                'petugas_source' => $pml_source,
                'peran' => 'PML',
                'target' => $pml_target,
                'realisasi' => $pml_realisasi,
                'keterangan' => $keterangan,
                'pml_id' => null,
                'asal' => 'Enarotali',
                'tujuan' => '',
                'alat_angkutan' => 'Kendaraan Umum'
            ]);
            
            // ========== SIMPAN PPL (jika ada) ==========
            $jumlah_ppl = (int)($_POST['jumlah_ppl'] ?? 0);
            $ppl_targets = $_POST['ppl_target'] ?? [];
            $ppl_realisasis = $_POST['ppl_realisasi'] ?? [];
            
            $total_ppl_target = 0;
            $total_ppl_realisasi = 0;
            
            for ($i = 0; $i < $jumlah_ppl; $i++) {
                if (!empty($ppl_ids[$i]) && !empty($ppl_sources[$i])) {
                    $ppl_target = (int)($ppl_targets[$i] ?? 0);
                    $ppl_realisasi = (int)($ppl_realisasis[$i] ?? 0);
                    
                    $model->add([
                        'kegiatan_detail_id' => $kegiatan_id,
                        'petugas_id' => $ppl_ids[$i],
                        'petugas_source' => $ppl_sources[$i],
                        'peran' => 'PPL',
                        'target' => $ppl_target,
                        'realisasi' => $ppl_realisasi,
                        'keterangan' => '',
                        'pml_id' => $pml_kegiatan_id,
                        'asal' => 'Enarotali',
                        'tujuan' => '',
                        'alat_angkutan' => 'Kendaraan Umum'
                    ]);
                    
                    $total_ppl_target += $ppl_target;
                    $total_ppl_realisasi += $ppl_realisasi;
                }
            }
            
            // Update target PML = total target PPL
            if ($jumlah_ppl > 0 && method_exists($model, 'updateTargetRealisasi')) {
                $model->updateTargetRealisasi($pml_kegiatan_id, $total_ppl_target, $total_ppl_realisasi);
            }

            $_SESSION['success'] = "Berhasil menambahkan 1 PML" . ($jumlah_ppl > 0 ? " dan $jumlah_ppl PPL" : "");
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan petugas: ' . $e->getMessage();
        }

        header('Location: index.php?controller=petugas_kegiatan');
        exit;
    }

    public function update()
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new KegiatanPetugas();

        try {
            $model->update([
                'id' => $_POST['id'],
                'peran' => $_POST['peran'],
                'target' => $_POST['target'] ?? 0,
                'realisasi' => $_POST['realisasi'] ?? 0,
                'keterangan' => $_POST['keterangan'] ?? '',
                'asal' => $_POST['asal'] ?? 'Enarotali',
                'tujuan' => $_POST['tujuan'] ?? '',
                'alat_angkutan' => $_POST['alat_angkutan'] ?? 'Kendaraan Umum',
                'no_spk' => $_POST['no_spk'] ?? '',
                'no_bast' => $_POST['no_bast'] ?? '',
                'no_surat_tugas' => $_POST['no_surat_tugas'] ?? '',
                'no_sppd' => $_POST['no_sppd'] ?? '',
                'tanggal_surat' => !empty($_POST['tanggal_surat']) ? $_POST['tanggal_surat'] : null,
                'periode_mulai' => !empty($_POST['periode_mulai']) ? $_POST['periode_mulai'] : null,
                'periode_selesai' => !empty($_POST['periode_selesai']) ? $_POST['periode_selesai'] : null
            ]);

            $_SESSION['success'] = 'Data petugas berhasil diupdate';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal mengupdate data: ' . $e->getMessage();
        }

        header('Location: index.php?controller=petugas_kegiatan');
        exit;
    }

    public function update_realisasi()
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new KegiatanPetugas();

        try {
            $id = $_POST['id'];
            $target = isset($_POST['target']) ? (int)$_POST['target'] : null;
            $realisasi = (int)($_POST['realisasi'] ?? 0);
            
            // Update target dan realisasi petugas
            if ($target !== null) {
                $stmt = $this->db->prepare("UPDATE kegiatan_petugas SET target = ?, realisasi = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$target, $realisasi, $id]);
            } else {
                $stmt = $this->db->prepare("UPDATE kegiatan_petugas SET realisasi = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$realisasi, $id]);
            }
            
            // Jika ini adalah PPL, update juga realisasi PML-nya
            $pml_id = $model->getPMLIdFromPPL($id);
            if ($pml_id) {
                $model->updatePMLRealisasi($pml_id);
            }

            $_SESSION['success'] = 'Data berhasil diupdate';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal mengupdate: ' . $e->getMessage();
        }

        header('Location: index.php?controller=petugas_kegiatan');
        exit;
    }

    public function delete()
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new KegiatanPetugas();
        $id = $_POST['id'] ?? $_GET['id'] ?? 0;

        try {
            $model->delete($id);
            $_SESSION['success'] = 'Data petugas berhasil dihapus';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus data: ' . $e->getMessage();
        }

        header('Location: index.php?controller=petugas_kegiatan');
        exit;
    }

    public function hapusMultiple()
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new KegiatanPetugas();
        
        // FIX: Terima ids[] dari form (array) - perbaikan untuk masalah "tidak ada data yang dipilih"
        $ids = $_POST['ids'] ?? [];
        
        // Fallback ke format selected_ids (string comma-separated) jika ids[] kosong
        if (empty($ids) && !empty($_POST['selected_ids'])) {
            $ids = array_filter(explode(',', $_POST['selected_ids']));
        }

        if (empty($ids)) {
            $_SESSION['error'] = 'Tidak ada data yang dipilih';
            header('Location: index.php?controller=petugas_kegiatan');
            exit;
        }

        try {
            $deleted = 0;
            foreach ($ids as $id) {
                $model->delete($id);
                $deleted++;
            }
            $_SESSION['success'] = "$deleted data petugas berhasil dihapus";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus data: ' . $e->getMessage();
        }

        header('Location: index.php?controller=petugas_kegiatan');
        exit;
    }

    public function downloadTemplate()
    {
        require_once 'vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Petugas');

        // Header
        $headers = ['A1'=>'Email Petugas','B1'=>'Peran (PML/PPL)','C1'=>'Target','D1'=>'Realisasi',
                    'E1'=>'Asal','F1'=>'Tujuan','G1'=>'Alat Angkutan','H1'=>'No SPK','I1'=>'No BAST',
                    'J1'=>'No Surat Tugas','K1'=>'No SPPD','L1'=>'Tanggal Surat','M1'=>'Periode Mulai',
                    'N1'=>'Periode Selesai','O1'=>'Keterangan'];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4472C4');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        // Example
        $sheet->setCellValue('A2', 'mitra@example.com');
        $sheet->setCellValue('B2', 'PPL');
        $sheet->setCellValue('C2', '100');
        $sheet->setCellValue('D2', '0');
        $sheet->setCellValue('E2', 'Enarotali');
        $sheet->setCellValue('F2', 'Distrik Paniai Timur');
        $sheet->setCellValue('G2', 'Kendaraan Umum');

        foreach (range('A', 'O') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        // Sheet 2: Daftar Email
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Daftar Email');
        $sheet2->setCellValue('A1', 'Email');
        $sheet2->setCellValue('B1', 'Nama');
        $sheet2->setCellValue('C1', 'Tipe');
        $sheet2->getStyle('A1:C1')->getFont()->setBold(true);

        $mitraModel = new Mitra();
        $userModel = new User();
        $mitraList = $mitraModel->getAktif();
        $pegawaiList = $userModel->getPegawaiAktif();

        $row = 2;
        foreach ($mitraList as $m) {
            $sheet2->setCellValue('A' . $row, $m['email']);
            $sheet2->setCellValue('B' . $row, $m['nama']);
            $sheet2->setCellValue('C' . $row, 'Mitra');
            $row++;
        }
        foreach ($pegawaiList as $p) {
            $sheet2->setCellValue('A' . $row, $p['email']);
            $sheet2->setCellValue('B' . $row, $p['nama_lengkap'] ?? $p['name']);
            $sheet2->setCellValue('C' . $row, 'Pegawai');
            $row++;
        }
        foreach (range('A', 'C') as $col) $sheet2->getColumnDimension($col)->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Import_Petugas.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function import()
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $kegiatan_id = $_POST['kegiatan_id'] ?? null;
        $confirmHonorExceed = isset($_POST['confirm_honor_exceed']) && $_POST['confirm_honor_exceed'] == '1';
        
        if (!$kegiatan_id) {
            $_SESSION['error'] = 'Kegiatan harus dipilih terlebih dahulu';
            header('Location: index.php?controller=petugas_kegiatan');
            exit;
        }

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'File tidak valid atau gagal diupload';
            header('Location: index.php?controller=petugas_kegiatan&kegiatan_id=' . $kegiatan_id);
            exit;
        }

        require_once 'vendor/autoload.php';

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel_file']['tmp_name']);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            array_shift($rows);

            $model = new KegiatanPetugas();
            $kegiatanModel = new KegiatanDetail();
            $mitraModel = new Mitra();
            
            // Get kegiatan info
            $kegiatan = $kegiatanModel->getById($kegiatan_id);
            if (!$kegiatan) {
                $_SESSION['error'] = 'Kegiatan tidak ditemukan';
                header('Location: index.php?controller=petugas_kegiatan');
                exit;
            }
            
            $namaKegiatan = $kegiatan['nama_kegiatan'];
            $bulanKegiatan = null;
            $tahunKegiatan = null;
            $honorSatuan = (float) ($kegiatan['honor_satuan'] ?? 0);
            $satuanKegiatan = strtoupper($kegiatan['satuan'] ?? '');
            
            if (!empty($kegiatan['rentang_waktu_mulai'])) {
                $tanggalMulai = new DateTime($kegiatan['rentang_waktu_mulai']);
                $bulanKegiatan = $tanggalMulai->format('m');
                $tahunKegiatan = $tanggalMulai->format('Y');
            }
            
            $success = 0;
            $errors = [];
            $warnings = [];
            $blockedMitra = [];
            $needsConfirmMitra = [];

            // First pass: validate all rows
            $validatedRows = [];
            
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                if (empty(trim($row[0] ?? ''))) continue;

                $email = trim($row[0]);
                $petugas = $model->getPetugasByEmail($email);
                
                // Validasi: Email tidak ditemukan
                if (!$petugas) {
                    $errors[] = "Baris $rowNum: Email '$email' tidak ditemukan";
                    continue;
                }
                
                $target = (int)($row[2] ?? 0);
                $peran = strtoupper(trim($row[1] ?? 'PPL'));
                
                // Cek duplikasi
                if ($model->isPetugasInKegiatan($kegiatan_id, $petugas['id'], $petugas['source'])) {
                    $warnings[] = "Baris $rowNum: Petugas '{$petugas['nama']}' sudah terdaftar di kegiatan ini - dilewati";
                    continue;
                }
                
                // Cek apakah ini mitra untuk validasi honor
                if ($petugas['source'] === 'mitra') {
                    $honorBaru = $target * $honorSatuan;
                    $honorCheck = $model->checkMitraHonorLimit($petugas['id'], $bulanKegiatan, $tahunKegiatan, $honorBaru);
                    
                    // BLOKIR: Mitra sudah ikut kegiatan OB
                    if ($honorCheck['has_ob_kegiatan']) {
                        $blockedMitra[] = "Baris $rowNum: Mitra '{$petugas['nama']}' sudah mengikuti kegiatan OB pada bulan ini - DIBLOKIR";
                        continue;
                    }
                    
                    // BLOKIR: Honor sudah melebihi 4 juta
                    if ($honorCheck['current_honor'] > 4000000) {
                        $blockedMitra[] = "Baris $rowNum: Honor mitra '{$petugas['nama']}' sudah melebihi Rp 4.000.000 - DIBLOKIR";
                        continue;
                    }
                    
                    // WARNING: Proyeksi akan melebihi 4 juta (perlu konfirmasi)
                    if ($honorCheck['projected_honor'] > 4000000 && !$confirmHonorExceed) {
                        $needsConfirmMitra[] = [
                            'row' => $rowNum,
                            'nama' => $petugas['nama'],
                            'peran' => $peran,
                            'current' => $honorCheck['current_honor'],
                            'baru' => $honorBaru,
                            'proyeksi' => $honorCheck['projected_honor']
                        ];
                    }
                }
                
                // Simpan row yang valid untuk diproses nanti
                $validatedRows[] = [
                    'row_num' => $rowNum,
                    'row_data' => $row,
                    'petugas' => $petugas
                ];
            }
            
            // Jika ada mitra yang perlu konfirmasi dan belum dikonfirmasi
            if (!empty($needsConfirmMitra) && !$confirmHonorExceed) {
                // Simpan file excel sementara untuk digunakan saat konfirmasi
                $tempFile = sys_get_temp_dir() . '/import_' . session_id() . '.xlsx';
                move_uploaded_file($_FILES['excel_file']['tmp_name'], $tempFile);
                
                $_SESSION['import_honor_confirm'] = [
                    'kegiatan_id' => $kegiatan_id,
                    'kegiatan_nama' => $namaKegiatan,
                    'temp_file' => $tempFile,
                    'mitra_list' => $needsConfirmMitra,
                    'blocked_list' => $blockedMitra,
                    'error_list' => $errors,
                    'warning_list' => $warnings
                ];
                header('Location: index.php?controller=petugas_kegiatan&show_import_confirm=1');
                exit;
            }
            
            // Second pass: actually insert data
            foreach ($validatedRows as $validated) {
                $row = $validated['row_data'];
                $petugas = $validated['petugas'];
                $rowNum = $validated['row_num'];
                
                // Skip jika mitra ini sudah di-flag untuk konfirmasi tapi belum dikonfirmasi
                $skipRow = false;
                foreach ($needsConfirmMitra as $nc) {
                    if ($nc['row'] == $rowNum && !$confirmHonorExceed) {
                        $skipRow = true;
                        break;
                    }
                }
                if ($skipRow) continue;
                
                try {
                    $model->add([
                        'kegiatan_detail_id' => $kegiatan_id,
                        'petugas_id' => $petugas['id'],
                        'petugas_source' => $petugas['source'],
                        'peran' => strtoupper(trim($row[1] ?? 'PPL')),
                        'target' => (int)($row[2] ?? 0),
                        'realisasi' => (int)($row[3] ?? 0),
                        'asal' => trim($row[4] ?? 'Enarotali'),
                        'tujuan' => trim($row[5] ?? ''),
                        'alat_angkutan' => trim($row[6] ?? 'Kendaraan Umum'),
                        'no_spk' => trim($row[7] ?? ''),
                        'no_bast' => trim($row[8] ?? ''),
                        'no_surat_tugas' => trim($row[9] ?? ''),
                        'no_sppd' => trim($row[10] ?? ''),
                        'tanggal_surat' => !empty($row[11]) ? $row[11] : null,
                        'periode_mulai' => !empty($row[12]) ? $row[12] : null,
                        'periode_selesai' => !empty($row[13]) ? $row[13] : null,
                        'keterangan' => trim($row[14] ?? ''),
                        'pml_id' => null
                    ]);
                    $success++;
                } catch (Exception $e) {
                    $errors[] = "Baris $rowNum: " . $e->getMessage();
                }
            }

            // Build result message
            $messages = [];
            if ($success > 0) $messages[] = "$success data berhasil diimport";
            
            if (!empty($errors) || !empty($warnings) || !empty($blockedMitra)) {
                $_SESSION['import_details'] = [
                    'errors' => array_slice($errors, 0, 10),
                    'warnings' => array_slice($warnings, 0, 10),
                    'blocked' => array_slice($blockedMitra, 0, 10)
                ];
            }
            
            if ($success > 0) {
                $_SESSION['success'] = implode('. ', $messages);
            } elseif (!empty($errors) || !empty($blockedMitra)) {
                $_SESSION['error'] = 'Tidak ada data yang berhasil diimport. Periksa detail error.';
            }

        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal membaca file Excel: ' . $e->getMessage();
        }

        header('Location: index.php?controller=petugas_kegiatan&kegiatan_id=' . $kegiatan_id);
        exit;
    }
    
    /**
     * Proses konfirmasi import dengan honor melebihi batas
     */
    public function processImportConfirm()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'confirm' && isset($_SESSION['import_honor_confirm'])) {
            $data = $_SESSION['import_honor_confirm'];
            $tempFile = $data['temp_file'];
            $kegiatan_id = $data['kegiatan_id'];
            
            // Simulasi upload file dari temp
            $_FILES['excel_file'] = [
                'tmp_name' => $tempFile,
                'error' => UPLOAD_ERR_OK
            ];
            $_POST['kegiatan_id'] = $kegiatan_id;
            $_POST['confirm_honor_exceed'] = '1';
            
            unset($_SESSION['import_honor_confirm']);
            
            // Panggil import lagi dengan konfirmasi
            $this->import();
        } else {
            // User membatalkan
            if (isset($_SESSION['import_honor_confirm']['temp_file'])) {
                @unlink($_SESSION['import_honor_confirm']['temp_file']);
            }
            unset($_SESSION['import_honor_confirm']);
            $_SESSION['info'] = 'Import dibatalkan.';
            header('Location: index.php?controller=petugas_kegiatan');
            exit;
        }
    }

    public function getDaftarPetugas()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $mitraModel = new Mitra();
        $userModel = new User();
        $data = [];

        foreach ($mitraModel->getAktif() as $m) {
            $data[] = ['nama' => $m['nama'], 'jenis' => 'Mitra', 'username' => $m['email'] ?? '-'];
        }
        foreach ($userModel->getPegawaiAktif() as $p) {
            $data[] = ['nama' => $p['nama_lengkap'] ?? $p['name'], 'jenis' => 'Pegawai', 'username' => $p['email'] ?? '-'];
        }

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    public function getDetail()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $model = new KegiatanPetugas();
        $data = $model->getById($_GET['id'] ?? 0);
        echo json_encode($data ? ['success' => true, 'data' => $data] : ['success' => false, 'error' => 'Not found']);
        exit;
    }

    /**
     * FIXED: Memanggil method yang benar: validateForGenerate (bukan validateForBulkGenerate)
     */
    public function validateForBulkGenerate()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        try {
            $model = new KegiatanPetugas();
            // FIXED: Panggil method yang benar
            $result = $model->validateForGenerate($_GET['kegiatan_id'] ?? 0, $_GET['jenis'] ?? 'spk');
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Get kegiatan berdasarkan filter tim (untuk AJAX)
     * FIXED: Jika tidak ada tim dipilih, tampilkan SEMUA kegiatan untuk SEMUA role
     */
    public function getKegiatanByTeam()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $team_id = $_GET['team_id'] ?? null;

        $kegiatanModel = new KegiatanDetail();

        if (!empty($team_id)) {
            // Filter by specific team
            $kegiatan = $kegiatanModel->getByTeam($team_id);
        } else {
            // FIXED: Jika tidak ada tim dipilih, tampilkan SEMUA kegiatan
            $kegiatan = $kegiatanModel->getAll();
        }

        echo json_encode(['success' => true, 'data' => $kegiatan]);
        exit;
    }

    /**
     * Get petugas untuk bulk generate (AJAX)
     */
    public function getPetugasForBulk()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $kegiatan_id = $_GET['kegiatan_id'] ?? null;
        $jenis = $_GET['jenis'] ?? 'spk';

        if (empty($kegiatan_id)) {
            echo json_encode(['success' => false, 'error' => 'Kegiatan ID required']);
            exit;
        }

        $model = new KegiatanPetugas();
        
        // Get assignments untuk kegiatan ini
        $data = $model->listByTeam(null, $kegiatan_id);
        
        // Filter berdasarkan jenis dokumen
        // SPK & BAST: hanya Mitra
        // Surat Tugas & SPPD: Mitra & PML
        $filtered = [];
        foreach ($data as $row) {
            $isMitra = ($row['petugas_jenis'] === 'Mitra');
            $isPML = ($row['peran'] === 'PML');
            
            if ($jenis === 'spk' || $jenis === 'bast') {
                // Hanya mitra
                if ($isMitra) {
                    $filtered[] = $row;
                }
            } else {
                // surat_tugas atau sppd: mitra dan PML
                if ($isMitra || $isPML) {
                    $filtered[] = $row;
                }
            }
        }

        echo json_encode(['success' => true, 'data' => $filtered]);
        exit;
    }

    /**
     * API: Get Honor Mitra per Bulan
     */
    public function getHonorMitra()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $bulan = $_GET['bulan'] ?? date('m');
        $tahun = $_GET['tahun'] ?? date('Y');

        try {
            $model = new KegiatanPetugas();
            $data = $model->getHonorMitra($bulan, $tahun);
            
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Check Mitra Honor Limit sebelum input
     */
    public function checkMitraHonor()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $mitra_id = $_GET['mitra_id'] ?? null;
        $bulan = $_GET['bulan'] ?? date('m');
        $tahun = $_GET['tahun'] ?? date('Y');
        $honorBaru = (float) ($_GET['honor_baru'] ?? 0);

        if (!$mitra_id) {
            echo json_encode(['success' => false, 'error' => 'Mitra ID required']);
            exit;
        }

        try {
            $model = new KegiatanPetugas();
            $result = $model->checkMitraHonorLimit($mitra_id, $bulan, $tahun, $honorBaru);
            
            // Get mitra name
            $mitraModel = new Mitra();
            $mitra = $mitraModel->getById($mitra_id);
            $result['nama'] = $mitra['nama'] ?? 'Unknown';
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Get Mitra Warning untuk Dashboard
     */
    public function getMitraWarning()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        try {
            $model = new KegiatanPetugas();
            $data = $model->getMitraWarningHonor();
            
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * API: Mendapatkan data konfirmasi honor yang pending (untuk ditampilkan inline)
     */
    public function getHonorConfirmData()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }
        
        if (!isset($_SESSION['honor_confirm_pending'])) {
            echo json_encode(['success' => false, 'has_pending' => false]);
            exit;
        }
        
        $data = $_SESSION['honor_confirm_pending'];
        echo json_encode([
            'success' => true, 
            'has_pending' => true,
            'mitra_list' => $data['mitra_list'],
            'kegiatan_nama' => $data['kegiatan_nama'] ?? 'Kegiatan'
        ]);
        exit;
    }
    
    /**
     * Proses konfirmasi honor - user memilih Ya atau Tidak (via AJAX atau form)
     */
    public function processConfirmHonor()
    {
        if (!isset($_SESSION['user'])) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Not authenticated']);
                exit;
            }
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'confirm' && isset($_SESSION['honor_confirm_pending'])) {
            // User mengkonfirmasi, lanjutkan simpan
            $_POST = $_SESSION['honor_confirm_pending']['form_data'];
            $_POST['confirm_honor_exceed'] = '1';
            unset($_SESSION['honor_confirm_pending']);
            
            // Panggil method simpan
            $this->simpan();
        } else {
            // User membatalkan
            unset($_SESSION['honor_confirm_pending']);
            $_SESSION['info'] = 'Penambahan petugas dibatalkan.';
            
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Dibatalkan']);
                exit;
            }
            
            header('Location: index.php?controller=petugas_kegiatan');
            exit;
        }
    }
    
    /**
     * Helper: Check if request is AJAX
     */
    private function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * API: Validate Import Data (untuk preview sebelum import)
     */
    public function validateImportData()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }
        
        $kegiatan_id = $_POST['kegiatan_id'] ?? null;
        
        if (!$kegiatan_id) {
            echo json_encode(['success' => false, 'error' => 'Kegiatan harus dipilih']);
            exit;
        }
        
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'File tidak valid']);
            exit;
        }
        
        require_once 'vendor/autoload.php';
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel_file']['tmp_name']);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            array_shift($rows); // Remove header
            
            $model = new KegiatanPetugas();
            $kegiatanModel = new KegiatanDetail();
            $mitraModel = new Mitra();
            
            // Get kegiatan info
            $kegiatan = $kegiatanModel->getById($kegiatan_id);
            if (!$kegiatan) {
                echo json_encode(['success' => false, 'error' => 'Kegiatan tidak ditemukan']);
                exit;
            }
            
            $bulanKegiatan = null;
            $tahunKegiatan = null;
            $honorSatuan = (float) ($kegiatan['honor_satuan'] ?? 0);
            $satuanKegiatan = strtoupper($kegiatan['satuan'] ?? '');
            
            if (!empty($kegiatan['rentang_waktu_mulai'])) {
                $tanggalMulai = new DateTime($kegiatan['rentang_waktu_mulai']);
                $bulanKegiatan = $tanggalMulai->format('m');
                $tahunKegiatan = $tanggalMulai->format('Y');
            }
            
            $validRows = [];
            $errors = [];
            $warnings = [];
            $blocked = [];
            $needsConfirm = [];
            
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                $email = trim($row[0] ?? '');
                
                if (empty($email)) continue;
                
                $petugas = $model->getPetugasByEmail($email);
                
                // Validasi: Email tidak ditemukan
                if (!$petugas) {
                    $errors[] = [
                        'row' => $rowNum,
                        'email' => $email,
                        'message' => "Email '$email' tidak ditemukan di database"
                    ];
                    continue;
                }
                
                $target = (int)($row[2] ?? 0);
                $peran = strtoupper(trim($row[1] ?? 'PPL'));
                
                // Cek apakah ini mitra
                if ($petugas['source'] === 'mitra') {
                    // Cek honor limit
                    $honorBaru = $target * $honorSatuan;
                    $honorCheck = $model->checkMitraHonorLimit($petugas['id'], $bulanKegiatan, $tahunKegiatan, $honorBaru);
                    
                    // Cek apakah mitra sudah ikut kegiatan OB (BLOKIR)
                    if ($honorCheck['has_ob_kegiatan']) {
                        $blocked[] = [
                            'row' => $rowNum,
                            'email' => $email,
                            'nama' => $petugas['nama'],
                            'reason' => 'ob',
                            'message' => "Mitra '{$petugas['nama']}' sudah mengikuti kegiatan dengan satuan OB pada bulan ini"
                        ];
                        continue;
                    }
                    
                    // Cek apakah honor sudah melebihi batas (BLOKIR)
                    if ($honorCheck['current_honor'] > 4000000) {
                        $blocked[] = [
                            'row' => $rowNum,
                            'email' => $email,
                            'nama' => $petugas['nama'],
                            'reason' => 'over_limit',
                            'current_honor' => $honorCheck['current_honor'],
                            'message' => "Honor mitra '{$petugas['nama']}' sudah melebihi Rp 4.000.000 (saat ini: Rp " . number_format($honorCheck['current_honor'], 0, ',', '.') . ")"
                        ];
                        continue;
                    }
                    
                    // Cek apakah proyeksi honor akan melebihi batas (PERLU KONFIRMASI)
                    if ($honorCheck['projected_honor'] > 4000000) {
                        $needsConfirm[] = [
                            'row' => $rowNum,
                            'email' => $email,
                            'nama' => $petugas['nama'],
                            'peran' => $peran,
                            'current_honor' => $honorCheck['current_honor'],
                            'honor_baru' => $honorBaru,
                            'projected_honor' => $honorCheck['projected_honor']
                        ];
                    }
                }
                
                // Cek duplikasi
                if ($model->isPetugasInKegiatan($kegiatan_id, $petugas['id'], $petugas['source'])) {
                    $warnings[] = [
                        'row' => $rowNum,
                        'email' => $email,
                        'nama' => $petugas['nama'],
                        'message' => "Petugas '{$petugas['nama']}' sudah terdaftar di kegiatan ini"
                    ];
                    continue;
                }
                
                // Valid row
                $validRows[] = [
                    'row' => $rowNum,
                    'email' => $email,
                    'nama' => $petugas['nama'],
                    'source' => $petugas['source'],
                    'peran' => $peran,
                    'target' => $target
                ];
            }
            
            echo json_encode([
                'success' => true,
                'kegiatan' => [
                    'id' => $kegiatan_id,
                    'nama' => $kegiatan['nama_kegiatan'],
                    'satuan' => $satuanKegiatan,
                    'honor_satuan' => $honorSatuan
                ],
                'valid_count' => count($validRows),
                'valid_rows' => $validRows,
                'errors' => $errors,
                'warnings' => $warnings,
                'blocked' => $blocked,
                'needs_confirm' => $needsConfirm
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Gagal membaca file: ' . $e->getMessage()]);
        }
        exit;
    }
}