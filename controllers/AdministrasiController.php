<?php
/*
 * WANDAI System - Administrasi Controller
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * 
 * UPDATED: Context-based data fetching for index() method
 * - context=data → Kegiatan berdasarkan tanggal (sedang berlangsung/selesai)
 * - context=verifikasi → Verifikasi pembayaran (bendahara)
 */

class AdministrasiController
{
    private $administrasiModel;
    private $kegiatanModel;
    private $userModel;
    private $teamModel;

    public function __construct()
    {
        require_once 'models/Administrasi.php';
        require_once 'models/KegiatanDetail.php';
        require_once 'models/User.php';
        require_once 'models/Team.php';
        require_once __DIR__ . '/../helpers/PdfMerger.php';
        
        $this->administrasiModel = new Administrasi();
        $this->kegiatanModel = new KegiatanDetail();
        $this->userModel = new User();
        $this->teamModel = new Team();
        
        // Start session if not started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * ✅ Index - List all administrasi based on role
     * UPDATED: Tampilkan semua kegiatan dengan status posisi dokumen
     */
    public function index()
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login');
            exit();
        }
        
        $user = $_SESSION['user'];
$userId = $user['id'];
$userTeamId = $user['team_id'];

// FIX: Konversi role_id ke role name
$roleId = $user['role_id'] ?? 4;
$roleMap = [1 => 'admin', 2 => 'kepala', 3 => 'kasubbag', 4 => 'ppk', 5 => 'bendahara', 6 => 'operator'];
$role = $roleMap[$roleId] ?? 'operator';
        
        // Get context parameter
        $context = $_GET['context'] ?? '';
        
        // Get filter parameters
        $team_id = $_GET['team_id'] ?? null;
        $tahun = $_GET['tahun'] ?? null;  // NEW: Filter tahun
        $bulan = $_GET['bulan'] ?? null;
        $jenis_kegiatan = $_GET['jenis_kegiatan'] ?? null;
        
        // Determine data based on context
        if ($context === 'verifikasi') {
            // Halaman Verifikasi Pembayaran sudah dihapus.
            // Redirect ke Data Administrasi.
            header('Location: index.php?controller=administrasi&context=data');
            exit();
        } elseif ($context === 'data') {
            // =============================================
            // DATA ADMINISTRASI - untuk semua role
            // UPDATED: Menampilkan SEMUA kegiatan dengan status:
            // - belum_mulai (tanggal_mulai > today)
            // - sedang_berlangsung (tanggal_mulai <= today <= tanggal_selesai)
            // - selesai (tanggal_selesai < today)
            // =============================================
            if (!in_array($role, ['admin', 'kepala', 'ppk', 'operator', 'bendahara'])) {
                $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman Data Administrasi';
                header('Location: index.php?controller=dashboard');
                exit();
            }
            
            // SEMUA role bisa melihat SEMUA kegiatan (bisa filter by tim jika dipilih)
            $teamFilter = !empty($team_id) ? $team_id : null;
            
            // Gunakan method baru yang mengambil SEMUA kegiatan
            $administrasi = $this->administrasiModel->getAllKegiatanForDataAdministrasi($teamFilter, $tahun, $bulan, $jenis_kegiatan);
            
        } else {
            // =============================================
            // DEFAULT (tanpa context) - backward compatibility
            // Menampilkan kegiatan yang sudah PPK approve
            // =============================================
            $teamFilter = $team_id;
            $administrasi = $this->administrasiModel->getDataAdministrasi($teamFilter, $bulan, $jenis_kegiatan);
        }
        
        // Get teams for filter (semua role bisa filter)
        $teams = $this->teamModel->getAll();
        
        // Get jenis kegiatan for filter
        $jenisKegiatanList = $this->kegiatanModel->getJenisKegiatanList();
        
        // Include view
        require 'views/administrasi/index.php';
    }

    /**
     * ✅ Form Input Administrasi - Halaman utama (NEW)
     */
    public function form()
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login');
            exit();
        }
        
        $user = $_SESSION['user'];
        $roleId = $user['role_id'] ?? 4;
        $roleMapLocal = [1 => 'admin', 2 => 'kepala', 3 => 'kasubbag', 4 => 'ppk', 5 => 'bendahara', 6 => 'operator'];
        $role = $roleMapLocal[$roleId] ?? ($user['role'] ?? 'operator');

        // Only operator, kepala, admin can access
        if (!in_array($role, ['operator', 'kepala', 'admin'])) {
            $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini';
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/administrasi');
            exit();
        }

        $activeTab = $_GET['tab'] ?? ($role === 'operator' ? 'kegiatan' : 'petugas');
        if (!in_array($activeTab, ['kegiatan','petugas'])) $activeTab = 'petugas';

        // Get kegiatan list for filter
        $kegiatanList = $this->administrasiModel->getKegiatanForUser($user);

        // Get filter parameter
        $kegiatan_id = $_GET['kegiatan_id'] ?? null;

        // Get assignments with administrasi status (for petugas tab)
        $assignments = $this->administrasiModel->getAssignmentsWithAdministrasi($user, $kegiatan_id);

        // Data for KEGIATAN tab: daftar kegiatan tim user + jenis-nya + file map
        $kegiatanJenisList = [];
        $selectedKegiatan = null;
        $jenisActive = $_GET['jenis'] ?? null;
        $selectedJenisRow = null;
        $innasList = [];
        $fileMap = [];

        if ($activeTab === 'kegiatan') {
            require_once 'models/KegiatanJenis.php';
            require_once 'models/KegiatanInnas.php';
            require_once 'models/AdministrasiKegiatanFile.php';
            require 'config/database.php';

            $kjModel = new KegiatanJenis();
            $innasModel = new KegiatanInnas();
            $fileModel = new AdministrasiKegiatanFile();

            // Auto-pilih kegiatan pertama jika belum dipilih
            if (!$kegiatan_id && !empty($kegiatanList)) {
                $kegiatan_id = $kegiatanList[0]['id'];
            }

            if ($kegiatan_id) {
                $stmt = $pdo->prepare("SELECT * FROM kegiatan_detail WHERE id = ? LIMIT 1");
                $stmt->execute([$kegiatan_id]);
                $selectedKegiatan = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

                $kegiatanJenisList = $kjModel->getByKegiatan($kegiatan_id);

                // Tentukan jenis aktif (sub-tab)
                if (!$jenisActive && !empty($kegiatanJenisList)) {
                    $jenisActive = $kegiatanJenisList[0]['jenis'];
                }
                foreach ($kegiatanJenisList as $kj) {
                    if ($kj['jenis'] === $jenisActive) {
                        $selectedJenisRow = $kj;
                        break;
                    }
                }

                if ($selectedJenisRow) {
                    $fileMap = $fileModel->getMapByKegiatanJenis($selectedJenisRow['id']);
                    if ($selectedJenisRow['jenis'] === 'Pelatihan/Briefing') {
                        $innasList = $innasModel->getByKegiatanJenis($selectedJenisRow['id']);
                    }
                }
            }
        }

        // Include view
        require 'views/administrasi/form.php';
    }

    /**
     * ✅ Save file upload untuk Administrasi Kegiatan (per kegiatan_jenis)
     */
    public function saveAdministrasiKegiatan()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        require_once 'models/AdministrasiKegiatanFile.php';
        $fileModel = new AdministrasiKegiatanFile();

        try {
            $kjId = (int)($_POST['kegiatan_jenis_id'] ?? 0);
            $jenisDok = $_POST['jenis_dokumen'] ?? '';
            $innasUserId = !empty($_POST['innas_user_id']) ? (int)$_POST['innas_user_id'] : null;
            $kegiatanId = (int)($_POST['kegiatan_id'] ?? 0);
            $jenisTab = $_POST['jenis_tab'] ?? '';

            if ($kjId <= 0 || empty($jenisDok)) {
                throw new Exception('Parameter tidak lengkap.');
            }
            if (empty($_FILES['file']['name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File tidak valid atau gagal diupload.');
            }

            $uploadDir = 'uploads/administrasi_kegiatan/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($_FILES['file']['name'], PATHINFO_FILENAME));
            $filename = $jenisDok . '_' . $kjId . '_' . ($innasUserId ?: '0') . '_' . time() . '_' . $safeName . '.' . $ext;
            $dest = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                throw new Exception('Gagal menyimpan file ke server.');
            }

            $fileModel->replace([
                'kegiatan_jenis_id' => $kjId,
                'jenis_dokumen'     => $jenisDok,
                'innas_user_id'     => $innasUserId,
                'nama_file'         => $_FILES['file']['name'],
                'file_path'         => $dest,
                'uploaded_by'       => $_SESSION['user']['id'],
            ]);

            $_SESSION['success'] = 'Dokumen berhasil diupload.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal upload: ' . $e->getMessage();
        }

        $redir = 'index.php?controller=administrasi&action=form&tab=kegiatan';
        if (!empty($_POST['kegiatan_id'])) $redir .= '&kegiatan_id=' . (int)$_POST['kegiatan_id'];
        if (!empty($_POST['jenis_tab']))   $redir .= '&jenis=' . urlencode($_POST['jenis_tab']);
        header('Location: ' . $redir);
        exit();
    }

    /**
     * ✅ Hapus file administrasi kegiatan
     */
    public function deleteAdministrasiKegiatan()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        require_once 'models/AdministrasiKegiatanFile.php';
        $fileModel = new AdministrasiKegiatanFile();

        try {
            $fileId = (int)($_POST['file_id'] ?? 0);
            if ($fileId > 0) {
                $fileModel->delete($fileId);
                $_SESSION['success'] = 'Dokumen dihapus.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal hapus: ' . $e->getMessage();
        }

        $redir = 'index.php?controller=administrasi&action=form&tab=kegiatan';
        if (!empty($_POST['kegiatan_id'])) $redir .= '&kegiatan_id=' . (int)$_POST['kegiatan_id'];
        if (!empty($_POST['jenis_tab']))   $redir .= '&jenis=' . urlencode($_POST['jenis_tab']);
        header('Location: ' . $redir);
        exit();
    }

    /**
     * ✅ Update Status Pembayaran (admin/bendahara only)
     */
    public function updateStatusPembayaran()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit();
        }

        $roleId = $_SESSION['user']['role_id'] ?? 4;
        if (!in_array($roleId, [1, 3])) {
            echo json_encode(['success' => false, 'error' => 'Hanya admin/bendahara yang dapat mengubah status.']);
            exit();
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id <= 0 || !in_array($status, ['lunas', 'belum_lunas'])) {
            echo json_encode(['success' => false, 'error' => 'Parameter tidak valid.']);
            exit();
        }

        try {
            require 'config/database.php';
            if ($status === 'lunas') {
                $stmt = $pdo->prepare("
                    UPDATE administrasi
                    SET status_bendahara = 'approved',
                        verified_bendahara_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE administrasi
                    SET status_bendahara = 'pending',
                        verified_bendahara_at = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$id]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * ✅ Download gabungan PDF dokumen per petugas untuk 1 kegiatan × jenis
     */
    public function downloadGabunganPetugas()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        $kegiatanId = (int)($_GET['kegiatan_id'] ?? 0);
        $jenisDok   = $_GET['jenis'] ?? 'spd'; // spd|bast|perjanjian|bukti
        if ($kegiatanId <= 0) {
            http_response_code(400);
            echo 'kegiatan_id required';
            exit();
        }

        try {
            require 'config/database.php';

            $fieldMap = [
                'spd' => 'adm.file_spd',
                'bast' => 'adm.file_bast',
                'perjanjian' => 'adm.file_perjanjian',
                'bukti' => 'adm.file_pengeluaran',
                'laporan' => 'adm.file_laporan_perjalanan',
            ];
            $field = $fieldMap[$jenisDok] ?? 'adm.file_spd';

            $sql = "SELECT $field AS path, kp.peran,
                           COALESCE(u.name, m.name) AS nama
                    FROM kegiatan_petugas kp
                    LEFT JOIN administrasi adm ON adm.kegiatan_petugas_id = kp.id
                    LEFT JOIN users u ON kp.petugas_source='users' AND kp.petugas_id = u.id
                    LEFT JOIN mitra m ON kp.petugas_source='mitra' AND kp.petugas_id = m.id
                    WHERE kp.kegiatan_detail_id = ?
                    ORDER BY kp.peran, nama";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$kegiatanId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $paths = [];
            foreach ($rows as $r) {
                if (!empty($r['path']) && file_exists($r['path'])) {
                    $paths[] = $r['path'];
                }
            }
            if (empty($paths)) {
                http_response_code(404);
                echo 'Tidak ada dokumen untuk digabungkan.';
                exit();
            }

            $outDir = 'documents/merged/';
            if (!is_dir($outDir)) @mkdir($outDir, 0777, true);
            $outPath = $outDir . 'keg_' . $kegiatanId . '_' . $jenisDok . '.pdf';

            $merger = new PdfMerger();
            if (method_exists($merger, 'addFiles')) {
                $merger->addFiles($paths);
            } else {
                foreach ($paths as $p) $merger->addFile($p);
            }
            $merger->save($outPath);

            $basename = 'Dokumen_' . $jenisDok . '_kegiatan_' . $kegiatanId . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $basename . '"');
            header('Content-Length: ' . filesize($outPath));
            readfile($outPath);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo 'Error: ' . htmlspecialchars($e->getMessage());
            exit();
        }
    }

    /**
     * ✅ Save Perjalanan Dinas - FIXED VERSION
     * PERBAIKAN: 
     * - file_sppd diganti menjadi file_spd
     * - Menambahkan handler untuk file_laporan_perjalanan
     */
    public function savePerjalanan()
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login');
            exit();
        }
        
        try {
            $user = $_SESSION['user'];
            $kegiatan_petugas_id = $_POST['kegiatan_petugas_id'] ?? null;
            $mode = $_POST['mode'] ?? 'input';
            
            if (!$kegiatan_petugas_id) {
                throw new Exception('ID petugas tidak valid');
            }
            
            // Get kegiatan petugas data
            $kegiatanPetugas = $this->administrasiModel->getKegiatanPetugasById($kegiatan_petugas_id);
            if (!$kegiatanPetugas) {
                throw new Exception('Data petugas tidak ditemukan');
            }
            
            // PERBAIKAN: Hanya hapus file yang akan di-replace
            if ($mode == 'edit') {
                // Hapus SPD hanya jika ada upload SPD baru
                if (!empty($_FILES['file_spd']['name'])) {
                    $this->administrasiModel->deleteFilesByKegiatanPetugas(
                        $kegiatan_petugas_id, 
                        'perjalanan_dinas',
                        'spd'
                    );
                    // Hapus juga sppd lama jika ada (backward compatibility)
                    $this->administrasiModel->deleteFilesByKegiatanPetugas(
                        $kegiatan_petugas_id, 
                        'perjalanan_dinas',
                        'sppd'
                    );
                }
                
                // Hapus Laporan Perjalanan hanya jika ada upload baru
                if (!empty($_FILES['file_laporan_perjalanan']['name'])) {
                    $this->administrasiModel->deleteFilesByKegiatanPetugas(
                        $kegiatan_petugas_id, 
                        'perjalanan_dinas',
                        'laporan_perjalanan'
                    );
                }
                
                // Hapus Pengeluaran hanya jika ada upload Pengeluaran baru
                if (!empty($_FILES['file_pengeluaran']['name'])) {
                    $this->administrasiModel->deleteFilesByKegiatanPetugas(
                        $kegiatan_petugas_id, 
                        'perjalanan_dinas',
                        'pengeluaran'
                    );
                }
                
                // Hapus Foto hanya jika ada upload Foto baru
                if (!empty($_FILES['file_foto']['name'][0])) {
                    $this->administrasiModel->deleteFilesByKegiatanPetugas(
                        $kegiatan_petugas_id, 
                        'perjalanan_dinas',
                        'foto'
                    );
                }
            }
            
            $uploadedFiles = [];
            
            // Handle SPD upload (Surat Tugas & SPD)
            if (!empty($_FILES['file_spd']['name'])) {
                $spdFile = $this->handleFileUpload($_FILES['file_spd'], 'spd');
                
                $spdData = [
                    'kegiatan_petugas_id' => $kegiatan_petugas_id,
                    'jenis_administrasi' => 'perjalanan_dinas',
                    'jenis_file' => 'dokumen',
                    'sub_jenis' => 'spd',
                    'nama_file' => $_FILES['file_spd']['name'],
                    'file_path' => $spdFile['path'],
                    'file_type' => $_FILES['file_spd']['type'],
                    'file_size' => $_FILES['file_spd']['size'],
                    'uploaded_by' => $user['id']
                ];
                
                $this->administrasiModel->insertFileComplete($spdData);
                $uploadedFiles[] = 'Surat Tugas & SPD';
            }
            
            // Handle Laporan Perjalanan upload (NEW)
            if (!empty($_FILES['file_laporan_perjalanan']['name'])) {
                $laporanFile = $this->handleFileUpload($_FILES['file_laporan_perjalanan'], 'laporan_perjalanan');
                
                $laporanData = [
                    'kegiatan_petugas_id' => $kegiatan_petugas_id,
                    'jenis_administrasi' => 'perjalanan_dinas',
                    'jenis_file' => 'dokumen',
                    'sub_jenis' => 'laporan_perjalanan',
                    'nama_file' => $_FILES['file_laporan_perjalanan']['name'],
                    'file_path' => $laporanFile['path'],
                    'file_type' => $_FILES['file_laporan_perjalanan']['type'],
                    'file_size' => $_FILES['file_laporan_perjalanan']['size'],
                    'uploaded_by' => $user['id']
                ];
                
                $this->administrasiModel->insertFileComplete($laporanData);
                $uploadedFiles[] = 'Laporan Perjalanan';
            }
            
            // Handle Pengeluaran upload
            if (!empty($_FILES['file_pengeluaran']['name'])) {
                $pengeluaranFile = $this->handleFileUpload($_FILES['file_pengeluaran'], 'pengeluaran');
                
                $pengeluaranData = [
                    'kegiatan_petugas_id' => $kegiatan_petugas_id,
                    'jenis_administrasi' => 'perjalanan_dinas',
                    'jenis_file' => 'dokumen',
                    'sub_jenis' => 'pengeluaran',
                    'nama_file' => $_FILES['file_pengeluaran']['name'],
                    'file_path' => $pengeluaranFile['path'],
                    'file_type' => $_FILES['file_pengeluaran']['type'],
                    'file_size' => $_FILES['file_pengeluaran']['size'],
                    'uploaded_by' => $user['id']
                ];
                
                $this->administrasiModel->insertFileComplete($pengeluaranData);
                $uploadedFiles[] = 'Pengeluaran';
            }
            
            // Handle Foto uploads
            if (!empty($_FILES['file_foto']['name'][0])) {
                $fotoCount = 0;
                
                for ($i = 0; $i < count($_FILES['file_foto']['name']); $i++) {
                    if (!empty($_FILES['file_foto']['name'][$i]) && 
                        $_FILES['file_foto']['error'][$i] === UPLOAD_ERR_OK) {
                        
                        $fileData = [
                            'name' => $_FILES['file_foto']['name'][$i],
                            'tmp_name' => $_FILES['file_foto']['tmp_name'][$i],
                            'type' => $_FILES['file_foto']['type'][$i],
                            'size' => $_FILES['file_foto']['size'][$i],
                            'error' => $_FILES['file_foto']['error'][$i]
                        ];
                        
                        $fotoFile = $this->handleFileUpload($fileData, 'foto');
                        
                        $fotoData = [
                            'kegiatan_petugas_id' => $kegiatan_petugas_id,
                            'jenis_administrasi' => 'perjalanan_dinas',
                            'jenis_file' => 'foto',
                            'sub_jenis' => 'foto',
                            'nama_file' => $_FILES['file_foto']['name'][$i],
                            'file_path' => $fotoFile['path'],
                            'keterangan' => $_POST['keterangan_foto'][$i] ?? 'Dokumentasi Kegiatan',
                            'file_type' => $_FILES['file_foto']['type'][$i],
                            'file_size' => $_FILES['file_foto']['size'][$i],
                            'uploaded_by' => $user['id']
                        ];
                        
                        $this->administrasiModel->insertFileComplete($fotoData);
                        $fotoCount++;
                    }
                }
                
                if ($fotoCount > 0) {
                    $uploadedFiles[] = "{$fotoCount} Foto";
                }
            }
            
            // Set success message
            if (count($uploadedFiles) > 0) {
                $filesText = implode(', ', $uploadedFiles);
                $_SESSION['success'] = 'Data perjalanan dinas berhasil disimpan (' . $filesText . ')';
            } else {
                $_SESSION['success'] = 'Data perjalanan dinas berhasil disimpan';
            }
            
            // Redirect back to form
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '?controller=administrasi&action=form');
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '?controller=administrasi&action=form');
            exit();
        }
    }

    /**
     * ✅ Save Honorarium - FIXED VERSION
     * Hanya hapus file yang benar-benar di-replace, bukan semua file
     */
    public function saveHonorarium()
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login');
            exit();
        }
        
        try {
            $user = $_SESSION['user'];
            $kegiatan_petugas_id = $_POST['kegiatan_petugas_id'] ?? null;
            $mode = $_POST['mode'] ?? 'input';
            
            if (!$kegiatan_petugas_id) {
                throw new Exception('ID petugas tidak valid');
            }
            
            // Get kegiatan petugas data
            $kegiatanPetugas = $this->administrasiModel->getKegiatanPetugasById($kegiatan_petugas_id);
            if (!$kegiatanPetugas) {
                throw new Exception('Data petugas tidak ditemukan');
            }
            
            // PERBAIKAN: Hanya hapus file yang akan di-replace
            if ($mode == 'edit') {
                // Hapus BAST hanya jika ada upload BAST baru
                if (!empty($_FILES['file_bast']['name'])) {
                    $this->administrasiModel->deleteFilesByKegiatanPetugas(
                        $kegiatan_petugas_id, 
                        'honorarium',
                        'bast'  // Tambahkan parameter sub_jenis
                    );
                }
                
                // Hapus Perjanjian hanya jika ada upload Perjanjian baru
                if (!empty($_FILES['file_perjanjian']['name'])) {
                    $this->administrasiModel->deleteFilesByKegiatanPetugas(
                        $kegiatan_petugas_id, 
                        'honorarium',
                        'perjanjian'  // Tambahkan parameter sub_jenis
                    );
                }
            }
            
            $uploadedFiles = [];
            
            // Handle BAST upload
            if (!empty($_FILES['file_bast']['name'])) {
                $bastFile = $this->handleFileUpload($_FILES['file_bast'], 'bast');
                
                $bastData = [
                    'kegiatan_petugas_id' => $kegiatan_petugas_id,
                    'jenis_administrasi' => 'honorarium',
                    'jenis_file' => 'dokumen',
                    'sub_jenis' => 'bast',
                    'nama_file' => $_FILES['file_bast']['name'],
                    'file_path' => $bastFile['path'],
                    'file_type' => $_FILES['file_bast']['type'],
                    'file_size' => $_FILES['file_bast']['size'],
                    'uploaded_by' => $user['id']
                ];
                
                $this->administrasiModel->insertFileComplete($bastData);
                $uploadedFiles[] = 'BAST';
            }
            
            // Handle Perjanjian upload
            if (!empty($_FILES['file_perjanjian']['name'])) {
                $perjanjianFile = $this->handleFileUpload($_FILES['file_perjanjian'], 'perjanjian');
                
                $perjanjianData = [
                    'kegiatan_petugas_id' => $kegiatan_petugas_id,
                    'jenis_administrasi' => 'honorarium',
                    'jenis_file' => 'dokumen',
                    'sub_jenis' => 'perjanjian',
                    'nama_file' => $_FILES['file_perjanjian']['name'],
                    'file_path' => $perjanjianFile['path'],
                    'file_type' => $_FILES['file_perjanjian']['type'],
                    'file_size' => $_FILES['file_perjanjian']['size'],
                    'uploaded_by' => $user['id']
                ];
                
                $this->administrasiModel->insertFileComplete($perjanjianData);
                $uploadedFiles[] = 'Perjanjian';
            }
            
            // Set success message
            if (count($uploadedFiles) > 0) {
                $filesText = implode(', ', $uploadedFiles);
                $_SESSION['success'] = 'Data honorarium berhasil disimpan (' . $filesText . ')';
            } else {
                $_SESSION['success'] = 'Data honorarium berhasil disimpan';
            }
            
            // Redirect back to form
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '?controller=administrasi&action=form');
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '?controller=administrasi&action=form');
            exit();
        }
    }

    /**
     * ✅ Get Files API (NEW - AJAX)
     */
    public function getFiles()
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $kegiatan_petugas_id = $_GET['id'] ?? null;
            $jenis = $_GET['jenis'] ?? null;
            
            if (!$kegiatan_petugas_id) {
                throw new Exception('ID tidak valid');
            }
            
            $files = $this->administrasiModel->getGroupedFilesByKegiatanPetugas($kegiatan_petugas_id);
            
            echo json_encode([
                'success' => true,
                'files' => $files
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

/**
 * ========================================
 * UPDATE: controllers/AdministrasiController.php
 * Method yang perlu ditambah/diupdate
 * ========================================
 */

/**
 * UPDATE METHOD: kirimVerifikasi() - Line ~421
 * Ganti nama method yang dipanggil
 */
public function kirimVerifikasi()
{
    // Check authentication
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    try {
        $user = $_SESSION['user'];
        $kegiatan_petugas_id = $_POST['kegiatan_petugas_id'] ?? null;
        
        if (!$kegiatan_petugas_id) {
            throw new Exception('ID petugas tidak valid');
        }
        
        // Get kegiatan petugas info untuk cek jenis (PNS/Mitra)
        $kegiatanPetugas = $this->administrasiModel->getKegiatanPetugasById($kegiatan_petugas_id);
        if (!$kegiatanPetugas) {
            throw new Exception('Data petugas tidak ditemukan');
        }
        
        // FIX: Cek jenis petugas berdasarkan petugas_source
        // petugas_source = 'users' berarti PNS, 'mitra' berarti Mitra
        $isPNS = ($kegiatanPetugas['petugas_source'] ?? '') === 'users';
        
        // Logika validasi berdasarkan jenis petugas
        if ($isPNS) {
            // PNS: Perjalanan dinas opsional, Honorarium tidak perlu
            // Bisa langsung kirim tanpa validasi kelengkapan
        } else {
            // Mitra: Wajib lengkap perjalanan DAN honorarium
            $perjalananComplete = $this->administrasiModel->checkPerjalananComplete($kegiatan_petugas_id);
            if (!$perjalananComplete) {
                throw new Exception('Dokumen perjalanan dinas belum lengkap');
            }
            
            $honorariumComplete = $this->administrasiModel->checkHonorariumComplete($kegiatan_petugas_id);
            if (!$honorariumComplete) {
                throw new Exception('Dokumen honorarium belum lengkap');
            }
        }
        
        // Cek apakah sudah dikirim sebelumnya
        $verifikasiStatus = $this->administrasiModel->getVerificationStatus($kegiatan_petugas_id);
        if ($verifikasiStatus) {
            throw new Exception('Data sudah dikirim ke verifikasi sebelumnya');
        }
        
        // UBAH: Kirim ke verifikasi OPERATOR TIM (bukan langsung ke Kepala)
        $result = $this->administrasiModel->sendToVerificationOperatorTim($kegiatan_petugas_id);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Data berhasil dikirim ke Operator Tim untuk diverifikasi'
            ]);
        } else {
            throw new Exception('Gagal mengirim data ke verifikasi');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * TAMBAH METHOD BARU: Halaman Verifikasi untuk Operator Tim
 */
/**
 * Approve Verifikasi Operator Tim (AJAX)
 * Admin juga bisa approve.
 */
public function approveOperatorTim()
{
    // Check authentication
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    try {
        $user = $_SESSION['user'];
        $role = $user['role'] ?? 'operator';
        $isAdmin = ($role === 'admin');
        
        // Operator dan Admin yang bisa approve
        if (!in_array($role, ['operator', 'admin'])) {
            throw new Exception('Hanya Operator Tim atau Admin yang dapat melakukan verifikasi');
        }
        
        $verifikasiId = $_POST['verifikasi_id'] ?? null;
        $adminOverride = $_POST['admin_override'] ?? false;
        
        if (!$verifikasiId) {
            throw new Exception('ID verifikasi tidak valid');
        }
        
        // Approve verifikasi (admin bisa override status apapun)
        $result = $this->administrasiModel->approveVerifikasiOperatorTim($verifikasiId, $user['id'], $isAdmin);
        
        if ($result) {
            $msg = $isAdmin ? 'Verifikasi berhasil disetujui (Admin Override)' : 'Verifikasi berhasil disetujui';
            echo json_encode([
                'success' => true,
                'message' => $msg
            ]);
        } else {
            throw new Exception('Gagal menyetujui verifikasi');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * TAMBAH METHOD BARU: Reject Verifikasi Operator Tim (AJAX)
 * UPDATED: Admin juga bisa reject
 */
public function rejectOperatorTim()
{
    // Check authentication
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    try {
        $user = $_SESSION['user'];
        $role = $user['role'] ?? 'operator';
        $isAdmin = ($role === 'admin');
        
        // Operator dan Admin yang bisa reject
        if (!in_array($role, ['operator', 'admin'])) {
            throw new Exception('Hanya Operator Tim atau Admin yang dapat melakukan verifikasi');
        }
        
        $verifikasiId = $_POST['verifikasi_id'] ?? null;
        $catatan = $_POST['catatan'] ?? '';
        $adminOverride = $_POST['admin_override'] ?? false;
        
        if (!$verifikasiId) {
            throw new Exception('ID verifikasi tidak valid');
        }
        
        if (empty($catatan)) {
            throw new Exception('Catatan penolakan harus diisi');
        }
        
        // Reject verifikasi
        $result = $this->administrasiModel->rejectVerifikasiOperatorTim($verifikasiId, $catatan, $user['id'], $isAdmin);
        
        if ($result) {
            $msg = $isAdmin ? 'Verifikasi ditolak (Admin Override). User akan menerima catatan penolakan.' : 'Verifikasi ditolak. User akan menerima catatan penolakan.';
            echo json_encode([
                'success' => true,
                'message' => $msg
            ]);
        } else {
            throw new Exception('Gagal menolak verifikasi');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * TAMBAH METHOD BARU: Kirim Kegiatan ke Kepala (AJAX)
 * Setelah semua petugas approved
 * UPDATED: Admin juga bisa kirim ke Kepala
 */
public function kirimKeKepala()
{
    // Check authentication
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    try {
        $user = $_SESSION['user'];
        $role = $user['role'] ?? 'operator';
        
        // Operator dan Admin yang bisa kirim ke kepala
        if (!in_array($role, ['operator', 'admin'])) {
            throw new Exception('Hanya Operator Tim atau Admin yang dapat mengirim ke Kepala');
        }
        
        $kegiatanDetailId = $_POST['kegiatan_id'] ?? null;
        
        if (!$kegiatanDetailId) {
            throw new Exception('ID kegiatan tidak valid');
        }
        
        // Kirim semua file kegiatan ke Kepala
        $result = $this->administrasiModel->sendKegiatanToKepala($kegiatanDetailId);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Kegiatan berhasil dikirim ke Kepala untuk diverifikasi'
            ]);
        } else {
            throw new Exception('Gagal mengirim ke Kepala. Pastikan semua petugas sudah disetujui.');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * ✅ UNIFIED Approve Kegiatan - handles all roles
 * Operator: approved_operator_tim -> pending_kepala (via sendKegiatanToKepala)
 * Kepala: pending_kepala -> approved_kepala (via sendKegiatanToPPK)
 * PPK: approved_kepala -> approved_ppk (via sendKegiatanToApprovedPPK)
 * 
 * FIX: Menggunakan role_id mapping untuk menentukan role dengan benar
 */
public function approveKegiatanUnified()
{
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    try {
        $user = $_SESSION['user'];
        
        // FIX: Gunakan role_id mapping seperti di method index()
        $roleId = $user['role_id'] ?? 4;
        $roleMap = [1 => 'admin', 2 => 'kepala', 3 => 'kasubbag', 4 => 'ppk', 5 => 'bendahara', 6 => 'operator'];
        $role = $roleMap[$roleId] ?? 'operator';
        
        $kegiatanDetailId = $_POST['kegiatan_id'] ?? null;
        
        if (!$kegiatanDetailId) {
            throw new Exception('ID kegiatan tidak valid');
        }
        
        $result = false;
        $message = '';
        
        if ($role === 'operator') {
            // Operator: kirim ke Kepala (sama seperti kirimKeKepala)
            $result = $this->administrasiModel->sendKegiatanToKepala($kegiatanDetailId);
            $message = 'Kegiatan berhasil dikirim ke Kepala untuk diverifikasi';
            if (!$result) throw new Exception('Gagal mengirim. Pastikan semua petugas sudah disetujui.');
            
        } elseif ($role === 'kepala') {
            // Kepala: langsung approve semua petugas ke PPK
            // Tidak perlu cek per-petugas, langsung update semua yang pending_kepala
            $result = $this->administrasiModel->sendKegiatanToPPK($kegiatanDetailId);
            $message = 'Kegiatan berhasil di-approve dan diteruskan ke PPK';
            if (!$result) {
                // Coba cek apakah ada data yang bisa di-approve
                $countPendingKepala = $this->administrasiModel->countVerifikasiByStatus($kegiatanDetailId, 'pending_kepala');
                if ($countPendingKepala == 0) {
                    throw new Exception('Tidak ada data dengan status pending_kepala untuk kegiatan ini. Kemungkinan sudah di-approve atau belum dikirim oleh operator.');
                }
                throw new Exception('Gagal approve ke PPK.');
            }
            
        } elseif ($role === 'ppk') {
            // PPK: langsung approve semua petugas ke approved_ppk
            // Tidak perlu cek per-petugas, langsung update semua yang approved_kepala
            $result = $this->administrasiModel->sendKegiatanToApprovedPPK($kegiatanDetailId);
            $message = 'Kegiatan berhasil di-approve PPK. Dokumen masuk ke Data Administrasi.';
            if (!$result) {
                $countApprovedKepala = $this->administrasiModel->countVerifikasiByStatus($kegiatanDetailId, 'approved_kepala');
                if ($countApprovedKepala == 0) {
                    throw new Exception('Tidak ada data dengan status approved_kepala untuk kegiatan ini. Pastikan Kepala sudah approve.');
                }
                throw new Exception('Gagal approve PPK.');
            }
            
        } elseif ($role === 'admin') {
            // Admin: coba semua level secara berurutan
            $result = $this->administrasiModel->sendKegiatanToKepala($kegiatanDetailId);
            if ($result) { $message = 'Kegiatan berhasil dikirim ke Kepala (Admin)'; }
            else {
                $result = $this->administrasiModel->sendKegiatanToPPK($kegiatanDetailId);
                if ($result) { $message = 'Kegiatan berhasil di-approve Kepala ke PPK (Admin)'; }
                else {
                    $result = $this->administrasiModel->sendKegiatanToApprovedPPK($kegiatanDetailId);
                    if ($result) { $message = 'Kegiatan berhasil di-approve PPK (Admin)'; }
                    else { throw new Exception('Tidak ada status yang bisa di-approve untuk kegiatan ini'); }
                }
            }
        } else {
            throw new Exception('Role Anda tidak memiliki akses untuk approve');
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

/**
 * ✅ UNIFIED Reject Kegiatan - handles Kepala, PPK, Admin
 * FIX: Menggunakan role_id mapping untuk menentukan role dengan benar
 */
public function rejectKegiatanUnified()
{
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    try {
        $user = $_SESSION['user'];
        
        // FIX: Gunakan role_id mapping seperti di method index()
        $roleId = $user['role_id'] ?? 4;
        $roleMap = [1 => 'admin', 2 => 'kepala', 3 => 'kasubbag', 4 => 'ppk', 5 => 'bendahara', 6 => 'operator'];
        $role = $roleMap[$roleId] ?? 'operator';
        
        $kegiatanDetailId = $_POST['kegiatan_id'] ?? null;
        $catatan = $_POST['catatan'] ?? '';
        
        if (!$kegiatanDetailId) throw new Exception('ID kegiatan tidak valid');
        if (empty($catatan)) throw new Exception('Alasan penolakan harus diisi');
        
        $result = false;
        $message = '';
        
        if ($role === 'kepala' || $role === 'admin') {
            $result = $this->administrasiModel->rejectKegiatanByKepala($kegiatanDetailId, $catatan);
            $message = 'Kegiatan berhasil di-reject oleh Kepala';
        }
        
        if (($role === 'ppk' || $role === 'admin') && !$result) {
            $result = $this->administrasiModel->rejectKegiatanByPPK($kegiatanDetailId, $catatan);
            $message = 'Kegiatan berhasil di-reject oleh PPK';
        }
        
        if (!$result) throw new Exception('Gagal me-reject kegiatan');
        
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

/**
 * ✅ Mark kegiatan as Lunas (for Bendahara/Admin)
 */
public function markLunas()
{
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    try {
        // FIX: Gunakan role_id mapping yang konsisten
        $roleId = $_SESSION['user']['role_id'] ?? 4;
        $roleMap = [1 => 'admin', 2 => 'kepala', 3 => 'kasubbag', 4 => 'ppk', 5 => 'bendahara', 6 => 'operator'];
        $role = $roleMap[$roleId] ?? 'operator';
        
        // Juga cek role_name sebagai fallback
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if ($roleName === 'admin' || $roleName === 'bendahara') {
            $role = $roleName;
        }
        
        if (!in_array($role, ['bendahara', 'admin'])) {
            throw new Exception('Hanya Bendahara/Admin yang dapat mengubah status pembayaran');
        }
        
        // Support both GET and POST
        $kegiatanDetailId = $_REQUEST['kegiatan_id'] ?? null;
        if (!$kegiatanDetailId) throw new Exception('ID kegiatan tidak valid');
        
        $result = $this->administrasiModel->markKegiatanLunas($kegiatanDetailId);
        if (!$result) {
            throw new Exception('Tidak ada dokumen yang sudah di-approve PPK untuk kegiatan ini. Pastikan PPK sudah menyetujui dokumen terlebih dahulu.');
        }
        
        echo json_encode(['success' => true, 'message' => 'Status pembayaran diubah menjadi LUNAS']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

/**
 * ✅ Mark kegiatan as Belum Lunas (for Bendahara/Admin)
 */
public function markBelumLunas()
{
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    try {
        // FIX: Gunakan role_id mapping yang konsisten
        $roleId = $_SESSION['user']['role_id'] ?? 4;
        $roleMap = [1 => 'admin', 2 => 'kepala', 3 => 'kasubbag', 4 => 'ppk', 5 => 'bendahara', 6 => 'operator'];
        $role = $roleMap[$roleId] ?? 'operator';
        
        // Juga cek role_name sebagai fallback
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if ($roleName === 'admin' || $roleName === 'bendahara') {
            $role = $roleName;
        }
        
        if (!in_array($role, ['bendahara', 'admin'])) {
            throw new Exception('Hanya Bendahara/Admin yang dapat mengubah status pembayaran');
        }
        
        // Support both GET and POST
        $kegiatanDetailId = $_REQUEST['kegiatan_id'] ?? null;
        if (!$kegiatanDetailId) throw new Exception('ID kegiatan tidak valid');
        
        $result = $this->administrasiModel->markKegiatanBelumLunas($kegiatanDetailId);
        if (!$result) throw new Exception('Gagal mengubah status pembayaran');
        
        echo json_encode(['success' => true, 'message' => 'Status pembayaran diubah menjadi BELUM LUNAS']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

    /**
     * Approve/Reject by Kepala for Verification (NEW)
     */
    public function approveVerifikasiKepala()
    {
        // Check authentication and role
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['kepala', 'admin'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $user = $_SESSION['user'];
            $kegiatan_petugas_id = $_POST['kegiatan_petugas_id'] ?? null;
            $status = $_POST['status'] ?? '';
            $catatan = $_POST['catatan'] ?? '';
            
            if (!$kegiatan_petugas_id) {
                throw new Exception('ID petugas tidak valid');
            }
            
            if (!in_array($status, ['approved', 'rejected'])) {
                throw new Exception('Status tidak valid');
            }
            
            // Update verification status
            $result = $this->administrasiModel->updateVerificationKepala($kegiatan_petugas_id, $status, $catatan);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Verifikasi Kepala berhasil disimpan'
                ]);
            } else {
                throw new Exception('Gagal menyimpan verifikasi. Pastikan status masih pending_kepala.');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * ✅ Approve/Reject by PPK for Verification (NEW)
     */
    public function approveVerifikasiPPK()
    {
        // Check authentication and role
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'ppk') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $kegiatan_petugas_id = $_POST['kegiatan_petugas_id'] ?? null;
            $status = $_POST['status'] ?? '';
            $catatan = $_POST['catatan'] ?? '';
            
            if (!$kegiatan_petugas_id) {
                throw new Exception('ID petugas tidak valid');
            }
            
            if (!in_array($status, ['approved', 'rejected'])) {
                throw new Exception('Status tidak valid');
            }
            
            // Update verification status
            $result = $this->administrasiModel->updateVerificationPPK($kegiatan_petugas_id, $status, $catatan);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Verifikasi PPK berhasil disimpan'
                ]);
            } else {
                throw new Exception('Gagal menyimpan verifikasi. Pastikan status masih approved_kepala.');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * ✅ Approve/Reject by Bendahara for Verification (NEW)
     */
    public function approveVerifikasiBendahara()
    {
        // Check authentication and role
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'bendahara') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $kegiatan_petugas_id = $_POST['kegiatan_petugas_id'] ?? null;
            $status = $_POST['status'] ?? '';
            $catatan = $_POST['catatan'] ?? '';
            
            if (!$kegiatan_petugas_id) {
                throw new Exception('ID petugas tidak valid');
            }
            
            if (!in_array($status, ['approved', 'rejected'])) {
                throw new Exception('Status tidak valid');
            }
            
            // Update verification status
            $result = $this->administrasiModel->updateVerificationBendahara($kegiatan_petugas_id, $status, $catatan);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Verifikasi Bendahara berhasil disimpan'
                ]);
            } else {
                throw new Exception('Gagal menyimpan verifikasi. Pastikan status masih approved_ppk.');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Delete administrasi
     */
    public function delete($id)
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login');
            exit();
        }
        
        $user = $_SESSION['user'];
        $userId = $user['id'];
        $role = $user['role'];
        
        // Get administrasi data
        $administrasi = $this->administrasiModel->getById($id);
        
        if (!$administrasi) {
            $_SESSION['error'] = 'Data administrasi tidak ditemukan';
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/administrasi');
            exit();
        }
        
        // Check if user can delete
        // Only admin, creator (if pending), or kepala tim (if pending) can delete
        $canDelete = false;
        if ($role == 'admin') {
            $canDelete = true;
        } elseif ($administrasi['created_by'] == $userId && 
                 $administrasi['status_ppk'] == 'pending' && 
                 $administrasi['status_bendahara'] == 'pending') {
            $canDelete = true;
        } elseif ($role == 'kepala' && 
                 $administrasi['team_id'] == $user['team_id'] &&
                 $administrasi['status_ppk'] == 'pending' && 
                 $administrasi['status_bendahara'] == 'pending') {
            $canDelete = true;
        }
        
        if (!$canDelete) {
            $_SESSION['error'] = 'Anda tidak dapat menghapus administrasi ini';
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/administrasi/view/' . $id);
            exit();
        }
        
        // Delete administrasi
        $result = $this->administrasiModel->delete($id);
        
        if ($result) {
            $_SESSION['success'] = 'Administrasi berhasil dihapus';
        } else {
            $_SESSION['error'] = 'Gagal menghapus administrasi';
        }
        
        header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/administrasi');
        exit();
    }

    /**
     * Delete file
     */
    public function deleteFile($fileId)
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login');
            exit();
        }
        
        $user = $_SESSION['user'];
        
        // Get file info
        $file = $this->administrasiModel->getFileById($fileId);
        
        if (!$file) {
            $_SESSION['error'] = 'File tidak ditemukan';
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/administrasi');
            exit();
        }
        
        // Check access
        $canDelete = false;
        if ($user['role'] == 'admin') {
            $canDelete = true;
        } elseif ($file['uploaded_by'] == $user['id']) {
            $canDelete = true;
        } elseif ($user['role'] == 'kepala' && $file['team_id'] == $user['team_id']) {
            $canDelete = true;
        }
        
        if (!$canDelete) {
            $_SESSION['error'] = 'Anda tidak dapat menghapus file ini';
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/administrasi/view/' . $file['administrasi_id']);
            exit();
        }
        
        // Delete file
        $result = $this->administrasiModel->deleteFile($fileId);
        
        if ($result) {
            $_SESSION['success'] = 'File berhasil dihapus';
        } else {
            $_SESSION['error'] = 'Gagal menghapus file';
        }
        
        header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/administrasi/view/' . $file['administrasi_id']);
        exit();
    }

    /**
     * ✅ Download file
     */
    public function downloadFile($fileId)
    {
        // Get file info
        $file = $this->administrasiModel->getFileById($fileId);
        
        if (!$file) {
            $_SESSION['error'] = 'File tidak ditemukan';
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/administrasi');
            exit();
        }
        
        $filePath = __DIR__ . '/../public/' . $file['file_path'];
        
        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file['nama_file']) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit();
        } else {
            $_SESSION['error'] = 'File tidak ditemukan di server';
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/administrasi/view/' . $file['administrasi_id']);
            exit();
        }
    }

    /**
     * Export to PDF/Excel
     */
    public function export()
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login');
            exit();
        }
        
        $user = $_SESSION['user'];
        $userId = $user['id'];
        $userTeamId = $user['team_id'];
        $role = $user['role'];
        
        // Get filter parameters
        $format = $_GET['format'] ?? 'pdf';
        $team_id = $_GET['team_id'] ?? null;
        $bulan = $_GET['bulan'] ?? null;
        $jenis_kegiatan = $_GET['jenis_kegiatan'] ?? null;
        
        // Get data based on role
        if ($role == 'operator') {
            $administrasi = $this->administrasiModel->getAllForOperator($userId, $userTeamId, $bulan, $jenis_kegiatan);
        } elseif ($role == 'kepala') {
            $administrasi = $this->administrasiModel->getAllForKepala($team_id, $userTeamId, $bulan, $jenis_kegiatan);
        } else {
            $administrasi = $this->administrasiModel->getAllWithAccessControl($userId, $userTeamId, $role, $team_id, $bulan, $jenis_kegiatan);
        }
        
        // Export based on format
        if ($format == 'excel') {
            $this->exportToExcel($administrasi);
        } else {
            $this->exportToPDF($administrasi);
        }
    }

    /**
     * Check access to administrasi
     */
    private function checkAccess($administrasi, $user)
    {
        $role = $user['role'];
        $userId = $user['id'];
        $userTeamId = $user['team_id'];
        
        // Admin, PPK, Bendahara can access all
        if (in_array($role, ['admin', 'ppk', 'bendahara'])) {
            return true;
        }
        
        // Kepala can access from their team
        if ($role == 'kepala' && $administrasi['team_id'] == $userTeamId) {
            return true;
        }
        
        // Operator can access if created by them or from their team
        if ($role == 'operator') {
            if ($administrasi['created_by'] == $userId) {
                return true;
            }
            
            // Check if operator is assigned to this kegiatan
            require_once 'config/database.php';
            $stmt = $pdo->prepare("
                SELECT 1 FROM kegiatan_petugas kp
                WHERE kp.kegiatan_detail_id = :kegiatan_id
                AND kp.petugas_id = :user_id
                AND kp.petugas_source = 'users'
            ");
            $stmt->execute([
                'kegiatan_id' => $administrasi['kegiatan_detail_id'],
                'user_id' => $userId
            ]);
            
            return $stmt->fetch() !== false;
        }
        
        // Mitra can access if assigned to kegiatan
        if ($role == 'mitra') {
            $mitraId = $user['mitra_id'] ?? $user['id'];
            
            require_once 'config/database.php';
            $stmt = $pdo->prepare("
                SELECT 1 FROM kegiatan_petugas kp
                WHERE kp.kegiatan_detail_id = :kegiatan_id
                AND kp.petugas_id = :mitra_id
                AND kp.petugas_source = 'mitra'
            ");
            $stmt->execute([
                'kegiatan_id' => $administrasi['kegiatan_detail_id'],
                'mitra_id' => $mitraId
            ]);
            
            return $stmt->fetch() !== false;
        }
        
        return false;
    }

    /**
     * Handle multiple file uploads
     */
    private function handleMultipleFileUpload($administrasiId, $files)
    {
        $uploadDir = 'uploads/administrasi/';
        
        // Create directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileCount = count($files['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                // Validate file type
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                if (!in_array($files['type'][$i], $allowedTypes)) {
                    continue; // Skip invalid files
                }
                
                // Generate unique filename
                $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = 'admin_' . $administrasiId . '_' . time() . '_' . $i . '_' . uniqid() . '.' . $extension;
                $filePath = $uploadDir . $filename;
                $fullPath = __DIR__ . '/../public/' . $filePath;
                
                // Move uploaded file
                if (move_uploaded_file($files['tmp_name'][$i], $fullPath)) {
                    // Insert file record
                    $fileData = [
                        'administrasi_id' => $administrasiId,
                        'nama_file' => $files['name'][$i],
                        'file_path' => $filePath
                    ];
                    
                    $this->administrasiModel->insertFile($fileData);
                }
            }
        }
    }

    /**
     * Handle single file upload (NEW) - FIXED VERSION
     */
    private function handleFileUpload($file, $type)
    {
        // Validasi
        $allowedTypes = [
            'application/pdf',
            'image/jpeg', 
            'image/jpg',
            'image/png',
            'image/gif'
        ];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Format file tidak didukung. Format yang diizinkan: PDF, JPG, PNG, GIF');
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Ukuran file maksimal 5MB');
        }
        
        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . time() . '_' . uniqid() . '.' . $ext;
        
        // Path relatif untuk disimpan di database
        $relativeDir = 'uploads/administrasi/' . date('Y/m/');
        
        // Path absolut untuk filesystem (dimana file benar-benar disimpan)
        $absoluteDir = __DIR__ . '/../public/' . $relativeDir;
        
        // Create directory jika belum ada
        if (!is_dir($absoluteDir)) {
            if (!mkdir($absoluteDir, 0777, true)) {
                throw new Exception('Gagal membuat direktori: ' . $absoluteDir);
            }
        }
        
        // Full paths
        $relativePath = $relativeDir . $filename;  // untuk database
        $absolutePath = $absoluteDir . $filename;  // untuk filesystem
        
        // Upload file
        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            $error = error_get_last();
            throw new Exception('Gagal menyimpan file. Error: ' . ($error['message'] ?? 'Unknown'));
        }
        
        return ['path' => $relativePath, 'full_path' => $absolutePath];
    }
    
    /**
     * Export to Excel
     */
    private function exportToExcel($data)
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="administrasi_' . date('Ymd') . '.xls"');
        
        echo '<table border="1">';
        echo '<tr>
                <th>No</th>
                <th>Kegiatan</th>
                <th>Jenis Kegiatan</th>
                <th>Status PPK</th>
                <th>Status Bendahara</th>
                <th>Dibuat Oleh</th>
                <th>Tanggal</th>
                <th>Tim</th>
              </tr>';
        
        $no = 1;
        foreach ($data as $row) {
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_kegiatan']) . '</td>';
            echo '<td>' . htmlspecialchars($row['jenis_kegiatan']) . '</td>';
            echo '<td>' . $row['status_ppk'] . '</td>';
            echo '<td>' . $row['status_bendahara'] . '</td>';
            echo '<td>' . htmlspecialchars($row['created_by_name'] ?? '-') . '</td>';
            echo '<td>' . $row['created_at'] . '</td>';
            echo '<td>' . htmlspecialchars($row['team_name'] ?? '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit();
    }

    /**
     * Export to PDF
     */
    private function exportToPDF($data)
    {
        // Note: You'll need to install a PDF library like TCPDF or DomPDF
        // This is a simplified example
        
        require_once 'libs/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('WANDAI System');
        $pdf->SetAuthor('BPS Kabupaten Paniai');
        $pdf->SetTitle('Laporan Administrasi');
        $pdf->SetSubject('Laporan Administrasi Kegiatan');
        
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'LAPORAN ADMINISTRASI KEGIATAN', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Tanggal: ' . date('d/m/Y'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(10, 7, 'No', 1, 0, 'C');
        $pdf->Cell(60, 7, 'Kegiatan', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Jenis', 1, 0, 'C');
        $pdf->Cell(25, 7, 'Status PPK', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Status Bendahara', 1, 0, 'C');
        $pdf->Cell(40, 7, 'Dibuat Oleh', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Tanggal', 1, 0, 'C');
        $pdf->Cell(40, 7, 'Tim', 1, 1, 'C');
        
        // Table content
        $pdf->SetFont('helvetica', '', 8);
        $no = 1;
        foreach ($data as $row) {
            $pdf->Cell(10, 6, $no++, 1, 0, 'C');
            $pdf->Cell(60, 6, substr($row['nama_kegiatan'], 0, 40), 1, 0);
            $pdf->Cell(30, 6, $row['jenis_kegiatan'], 1, 0);
            $pdf->Cell(25, 6, $row['status_ppk'], 1, 0, 'C');
            $pdf->Cell(30, 6, $row['status_bendahara'], 1, 0, 'C');
            $pdf->Cell(40, 6, $row['created_by_name'] ?? '-', 1, 0);
            $pdf->Cell(30, 6, date('d/m/Y', strtotime($row['created_at'])), 1, 0);
            $pdf->Cell(40, 6, $row['team_name'] ?? '-', 1, 1);
        }
        
        $pdf->Output('administrasi_' . date('Ymd') . '.pdf', 'D');
        exit();
    }

    /**
 * TAMBAHAN: Get Files Petugas untuk Modal (AJAX)
 * FIXED: Mengelompokkan hasil berdasarkan jenis_administrasi
 */
public function getFilesPetugas()
{
    header('Content-Type: application/json');
    
    try {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            throw new Exception('Unauthorized');
        }
        
        $petugasId = $_GET['petugas_id'] ?? null;
        
        if (!$petugasId) {
            throw new Exception('ID petugas tidak valid');
        }
        
        // Get files menggunakan method model (flat array)
        $rawFiles = $this->administrasiModel->getFilesByPetugas($petugasId);
        
        // Kelompokkan file berdasarkan jenis_administrasi
        $groupedFiles = [
            'perjalanan_dinas' => [],
            'honorarium' => []
        ];
        
        foreach ($rawFiles as $file) {
            $jenis = $file['jenis_administrasi'] ?? 'other';
            if ($jenis === 'perjalanan_dinas') {
                $groupedFiles['perjalanan_dinas'][] = $file;
            } elseif ($jenis === 'honorarium') {
                $groupedFiles['honorarium'][] = $file;
            }
        }
        
        echo json_encode([
            'success' => true,
            'files' => $groupedFiles
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * ✅ NEW: Get Detail Petugas Dokumen untuk modal di Data Administrasi
 * Menampilkan file per petugas berdasarkan jenis dokumen
 */
public function getDetailPetugasDokumen()
{
    header('Content-Type: application/json');
    
    try {
        if (!isset($_SESSION['user'])) {
            throw new Exception('Unauthorized');
        }
        
        $kegiatanId = $_GET['kegiatan_id'] ?? null;
        $jenis = $_GET['jenis'] ?? null;
        
        if (!$kegiatanId) {
            throw new Exception('ID kegiatan tidak valid');
        }
        
        // Map jenis to file type
        $fileTypeMap = [
            'spd' => ['surat_tugas', 'spd', 'sppd'],
            'laporan_perjalanan' => ['laporan_perjalanan', 'laporan'],
            'pengeluaran' => ['bukti_pengeluaran', 'pengeluaran', 'kwitansi'],
            'bast' => ['bast'],
            'perjanjian' => ['spk', 'kontrak', 'perjanjian']
        ];
        
        $fileTypes = $fileTypeMap[$jenis] ?? [$jenis];
        
        // Get all petugas for this kegiatan with their files
        $result = $this->administrasiModel->getPetugasWithFilesByJenis($kegiatanId, $fileTypes);
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * Upload Dokumen Kegiatan (KAK, SK KPA, Daftar Nominatif)
 */
public function uploadDokumenKegiatan()
{
    // Check authentication
    if (!isset($_SESSION['user'])) {
        $_SESSION['error'] = 'Anda harus login terlebih dahulu';
        header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login');
        exit();
    }
    
    try {
        $kegiatanDetailId = $_POST['kegiatan_detail_id'] ?? null;
        
        if (!$kegiatanDetailId) {
            throw new Exception('ID kegiatan tidak valid');
        }
        
        $user = $_SESSION['user'];
        $uploadedFiles = [];
        
        // Upload directory
        $uploadDir = __DIR__ . '/../public/uploads/dokumen_kegiatan/' . date('Y') . '/' . date('m') . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Handle KAK upload
        if (!empty($_FILES['file_kak']['name']) && $_FILES['file_kak']['error'] === UPLOAD_ERR_OK) {
            $result = $this->uploadDokumenKegiatanFile($_FILES['file_kak'], $uploadDir, $kegiatanDetailId, 'kak', $user['id']);
            if ($result) $uploadedFiles[] = 'KAK';
        }
        
        // Handle SK KPA upload
        if (!empty($_FILES['file_sk_kpa']['name']) && $_FILES['file_sk_kpa']['error'] === UPLOAD_ERR_OK) {
            $result = $this->uploadDokumenKegiatanFile($_FILES['file_sk_kpa'], $uploadDir, $kegiatanDetailId, 'sk_kpa', $user['id']);
            if ($result) $uploadedFiles[] = 'SK KPA';
        }
        
        // Handle Daftar Nominatif upload
        if (!empty($_FILES['file_daftar_nominatif']['name']) && $_FILES['file_daftar_nominatif']['error'] === UPLOAD_ERR_OK) {
            $result = $this->uploadDokumenKegiatanFile($_FILES['file_daftar_nominatif'], $uploadDir, $kegiatanDetailId, 'daftar_nominatif', $user['id']);
            if ($result) $uploadedFiles[] = 'Daftar Nominatif';
        }
        
        // Handle Form Permintaan upload (NEW)
        if (!empty($_FILES['file_form_permintaan']['name']) && $_FILES['file_form_permintaan']['error'] === UPLOAD_ERR_OK) {
            $result = $this->uploadDokumenKegiatanFile($_FILES['file_form_permintaan'], $uploadDir, $kegiatanDetailId, 'form_permintaan', $user['id']);
            if ($result) $uploadedFiles[] = 'Form Permintaan';
        }
        
        if (count($uploadedFiles) > 0) {
            $_SESSION['success'] = 'Berhasil upload: ' . implode(', ', $uploadedFiles);
        } else {
            $_SESSION['error'] = 'Tidak ada file yang diupload';
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
    
    header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/index.php?controller=administrasi&context=data');
    exit();
}

/**
 * Helper untuk upload file dokumen kegiatan
 */
private function uploadDokumenKegiatanFile($file, $uploadDir, $kegiatanDetailId, $jenisDokumen, $uploadedBy)
{
    // Validasi file
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipe file tidak diizinkan: ' . $file['type']);
    }
    
    // Max 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Ukuran file terlalu besar (maks 10MB)');
    }
    
    // Generate filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = $jenisDokumen . '_' . $kegiatanDetailId . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $newFilename;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Gagal menyimpan file');
    }
    
    // Relative path for database
    $relativePath = 'uploads/dokumen_kegiatan/' . date('Y') . '/' . date('m') . '/' . $newFilename;
    
    // Delete old file if exists
    $this->administrasiModel->deleteDokumenKegiatan($kegiatanDetailId, $jenisDokumen);
    
    // Save to database
    return $this->administrasiModel->insertDokumenKegiatan([
        'kegiatan_detail_id' => $kegiatanDetailId,
        'jenis_dokumen' => $jenisDokumen,
        'nama_file' => $file['name'],
        'file_path' => $relativePath,
        'file_type' => $file['type'],
        'file_size' => $file['size'],
        'uploaded_by' => $uploadedBy
    ]);
}

/**
 * AJAX: Get Dokumen Kegiatan (KAK, SK KPA, Daftar Nominatif, Form Permintaan)
 * Dipanggil oleh modal upload di verifikasi_tim.php
 */
public function getDokumenKegiatan()
{
    header('Content-Type: application/json');
    
    try {
        if (!isset($_SESSION['user'])) {
            throw new Exception('Unauthorized');
        }
        
        $kegiatanId = $_GET['kegiatan_id'] ?? null;
        $jenis = $_GET['jenis'] ?? null;
        
        if (!$kegiatanId) {
            throw new Exception('ID kegiatan tidak valid');
        }
        
        // Jika jenis dokumen spesifik diminta
        if ($jenis) {
            $doc = $this->administrasiModel->getDokumenKegiatanByJenis($kegiatanId, $jenis);
            
            if ($doc && !empty($doc['file_path'])) {
                echo json_encode([
                    'success' => true,
                    'file_path' => 'public/' . $doc['file_path'],
                    'filename' => $doc['filename'] ?? basename($doc['file_path'])
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Dokumen tidak ditemukan'
                ]);
            }
        } else {
            // Ambil semua dokumen (grouped)
            $docs = $this->administrasiModel->getDokumenKegiatanGrouped($kegiatanId);
            
            // Add file_path prefix for browser access
            foreach ($docs as $key => &$doc) {
                if (!empty($doc['file_path'])) {
                    $doc['file_path'] = 'public/' . $doc['file_path'];
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $docs
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * Download file dengan nama format: namaKegiatan_jenisFile_namaPetugas
 */
public function downloadFileFormatted()
{
    try {
        $fileId = $_GET['file_id'] ?? null;
        
        if (!$fileId) {
            throw new Exception('File ID tidak valid');
        }
        
        // Get file info with kegiatan and petugas info
        $file = $this->administrasiModel->getFileWithInfo($fileId);
        
        if (!$file) {
            throw new Exception('File tidak ditemukan');
        }
        
        // Build filename: namaKegiatan_jenisFile_namaPetugas.ext
        $namaKegiatan = preg_replace('/[^a-zA-Z0-9]/', '_', $file['nama_kegiatan'] ?? 'kegiatan');
        $jenisFile = $file['sub_jenis'] ?? 'file';
        $namaPetugas = preg_replace('/[^a-zA-Z0-9]/', '_', $file['petugas_nama'] ?? 'petugas');
        $ext = pathinfo($file['nama_file'], PATHINFO_EXTENSION);
        
        $downloadName = $namaKegiatan . '_' . $jenisFile . '_' . $namaPetugas . '.' . $ext;
        
        // Full file path
        $filePath = __DIR__ . '/../public/' . $file['file_path'];
        
        if (!file_exists($filePath)) {
            throw new Exception('File tidak ditemukan di server');
        }
        
        // Output file
        header('Content-Type: ' . ($file['file_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php?controller=administrasi&action=form');
        exit();
    }
}

/**
 * Download semua file per kegiatan dalam ZIP
 */
public function downloadAllFilesZip()
{
    try {
        $kegiatanId = $_GET['kegiatan_id'] ?? null;
        $jenis = $_GET['jenis'] ?? null; // spd, laporan_perjalanan, pengeluaran, foto, bast, perjanjian
        
        if (!$kegiatanId) {
            throw new Exception('Kegiatan ID tidak valid');
        }
        
        // Get kegiatan info
        $kegiatan = $this->kegiatanModel->getById($kegiatanId);
        if (!$kegiatan) {
            throw new Exception('Kegiatan tidak ditemukan');
        }
        
        // Get all files for this kegiatan
        $files = $this->administrasiModel->getAllFilesForKegiatan($kegiatanId, $jenis);
        
        if (empty($files)) {
            throw new Exception('Tidak ada file untuk didownload');
        }
        
        // Create ZIP
        $zipFileName = preg_replace('/[^a-zA-Z0-9]/', '_', $kegiatan['nama_kegiatan']);
        if ($jenis) {
            $zipFileName .= '_' . $jenis;
        }
        $zipFileName .= '_' . date('Ymd_His') . '.zip';
        
        $zipPath = __DIR__ . '/../public/uploads/temp/' . $zipFileName;
        
        // Ensure temp directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0777, true);
        }
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Gagal membuat file ZIP');
        }
        
        foreach ($files as $file) {
            $filePath = __DIR__ . '/../public/' . $file['file_path'];
            
            if (file_exists($filePath)) {
                // Format filename: namaKegiatan_jenisFile_namaPetugas.ext
                $namaKegiatan = preg_replace('/[^a-zA-Z0-9]/', '_', $kegiatan['nama_kegiatan']);
                $jenisFile = $file['sub_jenis'] ?? 'file';
                $namaPetugas = preg_replace('/[^a-zA-Z0-9]/', '_', $file['petugas_nama'] ?? 'petugas');
                $ext = pathinfo($file['nama_file'], PATHINFO_EXTENSION);
                
                $zipEntryName = $namaKegiatan . '_' . $jenisFile . '_' . $namaPetugas . '.' . $ext;
                
                // Add unique suffix if file already exists in zip
                $counter = 1;
                $originalEntry = $zipEntryName;
                while ($zip->locateName($zipEntryName) !== false) {
                    $zipEntryName = pathinfo($originalEntry, PATHINFO_FILENAME) . '_' . $counter . '.' . $ext;
                    $counter++;
                }
                
                $zip->addFile($filePath, $zipEntryName);
            }
        }
        
        $zip->close();
        
        // Output ZIP
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        
        // Delete temp file
        unlink($zipPath);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=administrasi&action=form'));
        exit();
    }
}

/**
 * Download All Files dari SEMUA petugas di 1 kegiatan
 */
public function downloadAllFilesByKegiatan()
{
    try {
        $kegiatanId = $_GET['kegiatan_id'] ?? null;
        $jenis = $_GET['jenis'] ?? null;
        
        if (!$kegiatanId) {
            throw new Exception('Parameter kegiatan_id tidak ada');
        }
        
        // Get all files dari SEMUA petugas di kegiatan ini
        $sql = "
            SELECT 
                af.id,
                af.kegiatan_petugas_id,
                af.jenis_administrasi,
                af.jenis_file as kategori_file,
                af.sub_jenis,
                af.nama_file,
                af.file_path,
                af.created_at as uploaded_at,
                u.nama as petugas_nama
            FROM administrasi_files af
            JOIN kegiatan_petugas kp ON af.kegiatan_petugas_id = kp.id
            JOIN users u ON kp.user_id = u.id
            WHERE kp.kegiatan_detail_id = ?
        ";
        
        if ($jenis) {
            $sql .= " AND af.jenis_administrasi = ?";
            $params = [$kegiatanId, $jenis];
        } else {
            $params = [$kegiatanId];
        }
        
        $sql .= "
            ORDER BY 
                u.nama,
                CASE 
                    WHEN af.jenis_administrasi = 'perjalanan_dinas' THEN 1
                    WHEN af.jenis_administrasi = 'honorarium' THEN 2
                    ELSE 3
                END,
                CASE 
                    WHEN af.sub_jenis = 'sppd' THEN 1
                    WHEN af.sub_jenis = 'pengeluaran' THEN 2
                    WHEN af.sub_jenis = 'foto' THEN 3
                    WHEN af.sub_jenis = 'bast' THEN 4
                    WHEN af.sub_jenis = 'perjanjian' THEN 5
                    ELSE 6
                END
        ";
        
        $stmt = $this->administrasiModel->db->prepare($sql);
        $stmt->execute($params);
        $allFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($allFiles)) {
            throw new Exception('Tidak ada file untuk diunduh');
        }
        
        // Group files by petugas then by category
        $groupedByPetugas = [];
        foreach ($allFiles as $file) {
            $petugasNama = $file['petugas_nama'];
            if (!isset($groupedByPetugas[$petugasNama])) {
                $groupedByPetugas[$petugasNama] = [];
            }
            $groupedByPetugas[$petugasNama][] = $file;
        }
        
        // Create temp directory
        $tempDir = sys_get_temp_dir() . '/merged_pdfs_kegiatan_' . time();
        if (!mkdir($tempDir, 0777, true)) {
            throw new Exception('Gagal membuat folder temporary');
        }
        
        $mergedFiles = [];
        
        // Merge per petugas
        foreach ($groupedByPetugas as $petugasNama => $files) {
            $groupedByCategory = $this->groupFilesByCategory($files);
            
            foreach ($groupedByCategory as $category => $categoryFiles) {
                $mergedPdf = $this->mergePDFsByCategory(
                    $categoryFiles, 
                    $category, 
                    $tempDir,
                    $petugasNama
                );
                if ($mergedPdf) {
                    $mergedFiles[] = $mergedPdf;
                }
            }
        }
        
        if (empty($mergedFiles)) {
            throw new Exception('Tidak ada PDF yang berhasil di-merge');
        }
        
        // Download as ZIP
        $kegiatanNama = $this->getKegiatanNama($kegiatanId);
        $this->downloadMergedFilesAsZip($mergedFiles, $jenis, $kegiatanNama);
        
        // Cleanup
        foreach ($mergedFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        error_log("Download All Files Error: " . $e->getMessage());
        exit;
    }
}

/**
 * Get kegiatan name
 */
private function getKegiatanNama($kegiatanId)
{
    $stmt = $this->administrasiModel->db->prepare("
        SELECT nama_kegiatan 
        FROM kegiatan_detail 
        WHERE id = ?
    ");
    $stmt->execute([$kegiatanId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nama_kegiatan'] : 'Kegiatan';
}

    /**
     * Group files by category berdasarkan sub_jenis
     */
    private function groupFilesByCategory($files)
    {
        $groups = [
            'surat_tugas_sppd' => [],
            'bukti_pengeluaran' => [],
            'bast' => [],
            'sppk' => []
        ];
        
        foreach ($files as $file) {
            $subJenis = strtolower($file['sub_jenis'] ?? '');
            
            // Mapping sub_jenis ke kategori
            if (in_array($subJenis, ['sppd', 'surat_tugas'])) {
                $groups['surat_tugas_sppd'][] = $file;
            } elseif (in_array($subJenis, ['pengeluaran', 'foto'])) {
                $groups['bukti_pengeluaran'][] = $file;
            } elseif ($subJenis === 'bast') {
                $groups['bast'][] = $file;
            } elseif (in_array($subJenis, ['perjanjian', 'kontrak', 'sppk'])) {
                $groups['sppk'][] = $file;
            } else {
                // Default: masukkan ke bukti pengeluaran
                $groups['bukti_pengeluaran'][] = $file;
            }
        }
        
        // Remove empty groups
        return array_filter($groups, function($files) {
            return !empty($files);
        });
    }

    /**
 * Merge PDFs by category (dengan nama petugas di filename)
 */
private function mergePDFsByCategory($files, $category, $tempDir, $petugasNama = null)
{
    try {
        $merger = new PdfMerger();
        
        // Nama file output berdasarkan kategori
        $outputNames = [
            'surat_tugas_sppd' => '1_Surat_Tugas_dan_SPPD',
            'bukti_pengeluaran' => '2_Bukti_Pengeluaran',
            'bast' => '3_BAST',
            'sppk' => '4_SPPK'
        ];
        
        $baseName = $outputNames[$category] ?? ucfirst($category);
        
        // Tambahkan nama petugas jika ada
        if ($petugasNama) {
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $petugasNama);
            $outputFilename = $baseName . '_' . $safeName . '.pdf';
        } else {
            $outputFilename = $baseName . '.pdf';
        }
        
        $outputPath = $tempDir . '/' . $outputFilename;
        
        // Sort files by sub_jenis
        usort($files, function($a, $b) {
            $order = [
                'surat_tugas' => 1, 
                'sppd' => 2, 
                'pengeluaran' => 3, 
                'foto' => 4, 
                'bast' => 5, 
                'perjanjian' => 6, 
                'kontrak' => 7, 
                'sppk' => 8
            ];
            $orderA = $order[$a['sub_jenis']] ?? 99;
            $orderB = $order[$b['sub_jenis']] ?? 99;
            return $orderA - $orderB;
        });
        
        // Add PDF files
        $addedCount = 0;
        foreach ($files as $file) {
            $filePath = $file['file_path'];
            
            if (file_exists($filePath) && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf') {
                if ($merger->addFile($filePath)) {
                    $addedCount++;
                } else {
                    error_log("Failed to add file: " . $filePath);
                }
            } else {
                error_log("File not found or not PDF: " . $filePath);
            }
        }
        
        if ($addedCount === 0) {
            error_log("No PDF files added for category: " . $category);
            return null;
        }
        
        // Save merged PDF
        if ($merger->save($outputPath)) {
            error_log("Successfully merged {$addedCount} files to: " . $outputPath);
            return $outputPath;
        } else {
            error_log("Failed to save merged PDF: " . $outputPath);
            return null;
        }
        
    } catch (Exception $e) {
        error_log("Error merging PDF for category {$category}: " . $e->getMessage());
        return null;
    }
}

    /**
 * Download merged files as ZIP
 */
private function downloadMergedFilesAsZip($files, $jenis = null, $kegiatanNama = 'Kegiatan')
{
    $zip = new ZipArchive();
    $zipFilename = tempnam(sys_get_temp_dir(), 'merged_pdfs_') . '.zip';
    
    if ($zip->open($zipFilename, ZipArchive::CREATE) !== TRUE) {
        throw new Exception('Gagal membuat file ZIP');
    }
    
    foreach ($files as $filePath) {
        if (file_exists($filePath)) {
            $zip->addFile($filePath, basename($filePath));
        }
    }
    
    $zip->close();
    
    // Nama ZIP file dengan nama kegiatan
    $safeKegiatanNama = preg_replace('/[^a-zA-Z0-9_-]/', '_', $kegiatanNama);
    $jenisLabel = $jenis ? ucfirst($jenis) . '_' : '';
    $downloadName = $safeKegiatanNama . '_' . $jenisLabel . 'Files_' . date('Ymd') . '.zip';
    
    // Send to browser
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($zipFilename));
    readfile($zipFilename);
    
    // Cleanup
    unlink($zipFilename);
    exit;
}

    /**
     * View All Files (untuk preview - redirect ke file pertama)
     */
    public function viewAllFilesPDF()
    {
        try {
            $petugasId = $_GET['petugas_id'] ?? null;
            $jenis = $_GET['jenis'] ?? null;
            
            if (!$petugasId || !$jenis) {
                throw new Exception('Parameter tidak lengkap');
            }
            
            // Get files
            $files = $this->administrasiModel->getFilesByPetugas($petugasId);
            $filteredFiles = array_filter($files, function($f) use ($jenis) {
                return $f['jenis_administrasi'] === $jenis;
            });
            
            if (empty($filteredFiles)) {
                echo "Tidak ada file untuk ditampilkan";
                exit;
            }
            
            // Redirect ke file pertama
            $firstFile = reset($filteredFiles);
            header('Location: ' . $firstFile['file_path']);
            exit;
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            exit;
        }
    }

    /**
     * TAMBAH METHOD BARU: Reset Status Verifikasi (Admin Only)
     */
    public function resetVerifikasiStatus()
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $user = $_SESSION['user'];
            $role = $user['role'] ?? 'operator';
            
            // Hanya admin yang bisa reset status
            if ($role !== 'admin') {
                throw new Exception('Hanya Admin yang dapat mereset status verifikasi');
            }
            
            $verifikasiId = $_POST['verifikasi_id'] ?? null;
            
            if (!$verifikasiId) {
                throw new Exception('ID verifikasi tidak valid');
            }
            
            // Reset status ke pending_operator_tim
            $result = $this->administrasiModel->resetVerifikasiStatus($verifikasiId, $user['id']);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status verifikasi berhasil direset ke Pending'
                ]);
            } else {
                throw new Exception('Gagal mereset status verifikasi');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Kirim Ulang Verifikasi (setelah ditolak)
     */
    public function kirimUlangVerifikasi()
    {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $user = $_SESSION['user'];
            $kegiatan_petugas_id = $_POST['kegiatan_petugas_id'] ?? null;
            
            if (!$kegiatan_petugas_id) {
                throw new Exception('ID petugas tidak valid');
            }
            
            // Update status dari rejected_operator_tim ke pending_operator_tim
            $result = $this->administrasiModel->kirimUlangVerifikasi($kegiatan_petugas_id);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Data berhasil dikirim ulang ke verifikasi'
                ]);
            } else {
                throw new Exception('Gagal mengirim ulang data');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Approve All Pending (untuk Operator Tim)
     */
    public function approveAllOperatorTim()
    {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $user = $_SESSION['user'];
            $role = $user['role'] ?? 'operator';
            $isAdmin = ($role === 'admin');
            
            if (!in_array($role, ['operator', 'admin'])) {
                throw new Exception('Hanya Operator Tim atau Admin yang dapat melakukan verifikasi');
            }
            
            $verifikasiIds = json_decode($_POST['verifikasi_ids'] ?? '[]', true);
            
            if (empty($verifikasiIds)) {
                throw new Exception('Tidak ada data untuk disetujui');
            }
            
            $successCount = 0;
            foreach ($verifikasiIds as $verifikasiId) {
                if ($this->administrasiModel->approveVerifikasiOperatorTim($verifikasiId, $user['id'], $isAdmin)) {
                    $successCount++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "{$successCount} petugas berhasil disetujui"
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Reject All Pending (untuk Operator Tim)
     */
    public function rejectAllOperatorTim()
    {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $user = $_SESSION['user'];
            $role = $user['role'] ?? 'operator';
            $isAdmin = ($role === 'admin');
            
            if (!in_array($role, ['operator', 'admin'])) {
                throw new Exception('Hanya Operator Tim atau Admin yang dapat melakukan verifikasi');
            }
            
            $verifikasiIds = json_decode($_POST['verifikasi_ids'] ?? '[]', true);
            $catatan = $_POST['catatan'] ?? '';
            
            if (empty($verifikasiIds)) {
                throw new Exception('Tidak ada data untuk ditolak');
            }
            
            if (empty($catatan)) {
                throw new Exception('Catatan penolakan harus diisi');
            }
            
            $successCount = 0;
            foreach ($verifikasiIds as $verifikasiId) {
                if ($this->administrasiModel->rejectVerifikasiOperatorTim($verifikasiId, $catatan, $user['id'], $isAdmin)) {
                    $successCount++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "{$successCount} petugas berhasil ditolak"
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Reject pembayaran by Bendahara dan kembalikan ke Operator
     */
    public function rejectByBendahara()
    {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $user = $_SESSION['user'];
            $roleId = $user['role_id'] ?? 4;
            $roleMap = [1 => 'admin', 2 => 'kepala', 3 => 'kasubbag', 4 => 'ppk', 5 => 'bendahara', 6 => 'operator'];
            $role = $roleMap[$roleId] ?? 'operator';
            
            if (!in_array($role, ['bendahara', 'admin'])) {
                throw new Exception('Hanya Bendahara atau Admin yang dapat menolak pembayaran');
            }
            
            $kegiatanId = $_POST['kegiatan_id'] ?? null;
            $catatan = $_POST['catatan'] ?? '';
            
            if (!$kegiatanId) {
                throw new Exception('ID kegiatan tidak valid');
            }
            
            if (empty(trim($catatan))) {
                throw new Exception('Alasan penolakan harus diisi');
            }
            
            // Reject dan kembalikan ke operator
            $result = $this->administrasiModel->rejectKegiatanByBendahara($kegiatanId, $catatan);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Pembayaran ditolak dan dikembalikan ke Operator'
                ]);
            } else {
                throw new Exception('Gagal menolak pembayaran');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Get merged PDF of all petugas documents
     */
    public function getMergedPdfPetugas()
    {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
        
        header('Content-Type: application/json');
        
        try {
            $kegiatanId = $_GET['kegiatan_id'] ?? null;
            
            if (!$kegiatanId) {
                throw new Exception('ID kegiatan tidak valid');
            }
            
            // Get all petugas files for this kegiatan
            $files = $this->administrasiModel->getAllPetugasFilesForKegiatan($kegiatanId);
            
            if (empty($files)) {
                throw new Exception('Tidak ada file dokumen petugas');
            }
            
            // Check if merged PDF already exists
            $mergedFileName = 'merged_petugas_' . $kegiatanId . '.pdf';
            $mergedDir = __DIR__ . '/../public/uploads/merged/';
            $mergedPath = $mergedDir . $mergedFileName;
            
            // Create directory if not exists
            if (!is_dir($mergedDir)) {
                mkdir($mergedDir, 0777, true);
            }
            
            // Generate merged PDF using PdfMerger
            $filePaths = [];
            foreach ($files as $file) {
                $fullPath = __DIR__ . '/../public/' . $file['file_path'];
                if (file_exists($fullPath)) {
                    $filePaths[] = $fullPath;
                }
            }
            
            if (empty($filePaths)) {
                throw new Exception('File dokumen tidak ditemukan');
            }
            
            // Use PdfMerger to merge files
            $merger = new PdfMerger();
            $result = $merger->merge($filePaths, $mergedPath);
            
            if ($result && file_exists($mergedPath)) {
                echo json_encode([
                    'success' => true,
                    'file_path' => 'public/uploads/merged/' . $mergedFileName
                ]);
            } else {
                // If merge fails, return first file as fallback
                echo json_encode([
                    'success' => true,
                    'file_path' => 'public/' . $files[0]['file_path'],
                    'note' => 'Showing first file (merge unavailable)'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Get dokumen petugas by jenis (ST & SPD, BAST, Bukti, SPK)
     */
    public function getDokumenPetugasByJenis()
    {
        header('Content-Type: application/json');
        
        try {
            if (!isset($_SESSION['user'])) {
                throw new Exception('Unauthorized');
            }
            
            $kegiatanId = $_GET['kegiatan_id'] ?? null;
            $jenis = $_GET['jenis'] ?? 'spd';
            
            if (!$kegiatanId) {
                throw new Exception('ID kegiatan tidak valid');
            }
            
            // Map jenis to sub_jenis in administrasi_files
            $jenisMap = [
                'spd' => ['spd', 'sppd', 'st'],           // Surat Tugas & SPD
                'bast' => ['bast'],                       // BAST
                'bukti' => ['pengeluaran', 'bukti'],      // Bukti Pengeluaran
                'spk' => ['perjanjian', 'spk', 'kontrak'] // SPK
            ];
            
            $subJenis = $jenisMap[$jenis] ?? [$jenis];
            
            // Get petugas with their files
            $data = $this->administrasiModel->getPetugasWithFilesByJenis($kegiatanId, $subJenis);
            
            // Add file_path prefix
            foreach ($data as &$row) {
                if (!empty($row['file_path'])) {
                    $row['file_path'] = 'public/' . $row['file_path'];
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Download dokumen petugas sebagai ZIP (dikelompokkan per PML dan PPL)
     */
    public function downloadDokumenPetugasZip()
    {
        try {
            if (!isset($_SESSION['user'])) {
                throw new Exception('Unauthorized');
            }
            
            $kegiatanId = $_GET['kegiatan_id'] ?? null;
            $jenis = $_GET['jenis'] ?? 'spd';
            
            if (!$kegiatanId) {
                throw new Exception('ID kegiatan tidak valid');
            }
            
            // Get kegiatan info
            $kegiatan = $this->administrasiModel->getKegiatanById($kegiatanId);
            $namaKegiatan = $kegiatan['nama_kegiatan'] ?? 'Kegiatan';
            $namaKegiatan = preg_replace('/[^a-zA-Z0-9_-]/', '_', $namaKegiatan);
            
            // Map jenis to sub_jenis
            $jenisMap = [
                'spd' => ['spd', 'sppd', 'st'],
                'bast' => ['bast'],
                'bukti' => ['pengeluaran', 'bukti'],
                'spk' => ['perjanjian', 'spk', 'kontrak']
            ];
            $jenisLabels = [
                'spd' => 'ST_SPD',
                'bast' => 'BAST',
                'bukti' => 'Bukti_Pengeluaran',
                'spk' => 'SPK'
            ];
            
            $subJenis = $jenisMap[$jenis] ?? [$jenis];
            $jenisLabel = $jenisLabels[$jenis] ?? $jenis;
            
            // Get petugas with files
            $data = $this->administrasiModel->getPetugasWithFilesByJenis($kegiatanId, $subJenis);
            
            if (empty($data)) {
                throw new Exception('Tidak ada dokumen untuk di-download');
            }
            
            // Create ZIP
            $zipFilename = $namaKegiatan . '_' . $jenisLabel . '_' . date('Ymd_His') . '.zip';
            $zipPath = __DIR__ . '/../public/uploads/temp/' . $zipFilename;
            
            // Ensure temp directory exists
            $tempDir = dirname($zipPath);
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('Gagal membuat file ZIP');
            }
            
            // Group files by peran
            $pmlFiles = [];
            $pplFiles = [];
            
            foreach ($data as $row) {
                if (empty($row['file_path'])) continue;
                
                $fullPath = __DIR__ . '/../public/' . $row['file_path'];
                if (!file_exists($fullPath)) continue;
                
                $petugasNama = preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['petugas_nama'] ?? 'Unknown');
                $ext = pathinfo($row['file_path'], PATHINFO_EXTENSION);
                $filename = $petugasNama . '_' . $jenisLabel . '.' . $ext;
                
                if ($row['peran'] === 'PML') {
                    $pmlFiles[$filename] = $fullPath;
                } else {
                    $pplFiles[$filename] = $fullPath;
                }
            }
            
            // Add PML folder
            if (!empty($pmlFiles)) {
                foreach ($pmlFiles as $name => $path) {
                    $zip->addFile($path, 'PML/' . $name);
                }
            }
            
            // Add PPL folder
            if (!empty($pplFiles)) {
                foreach ($pplFiles as $name => $path) {
                    $zip->addFile($path, 'PPL/' . $name);
                }
            }
            
            $zip->close();
            
            // Send file to browser
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
            header('Content-Length: ' . filesize($zipPath));
            header('Cache-Control: no-cache, must-revalidate');
            
            readfile($zipPath);
            
            // Clean up temp file
            unlink($zipPath);
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ?controller=administrasi&context=data');
        }
        exit();
    }

}