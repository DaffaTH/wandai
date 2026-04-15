<?php
/**
 * ========================================
 * WANDAI System - Input Administrasi
 * UPDATED VERSION
 * ========================================
 * 
 * FILE: views/administrasi/form.php
 * 
 * PERBAIKAN:
 * - PNS tidak wajib input honorarium (tidak perlu)
 * - PNS tidak wajib input perjalanan dinas (opsional)
 * - Status: Belum Kirim, Terkirim, Ditolak
 * - Jika Ditolak: tombol Kirim Ulang dan Lihat Catatan
 * - SPPD diubah menjadi SPD
 */

if (session_status() === PHP_SESSION_NONE) session_start();

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Get role
$roleId = $_SESSION['user']['role_id'] ?? 4;
$roleMap = [1 => 'admin', 2 => 'ppk', 3 => 'bendahara', 4 => 'operator', 5 => 'kepala', 6 => 'kasubbag'];
$role = $roleMap[$roleId] ?? 'operator';

// Tab aktif: operator/pegawai default 'kegiatan', lainnya 'petugas'
$activeTab = $_GET['tab'] ?? ($role === 'operator' ? 'kegiatan' : 'petugas');
if (!in_array($activeTab, ['kegiatan','petugas'])) $activeTab = 'petugas';
$canSeeKegiatanTab = in_array($role, ['operator','admin','kepala']);
?>
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex">
  <!-- Sidebar -->
  <div class="sidebar">
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
  </div>

  <!-- Main Content -->
  <div class="flex-grow-1">
    <div class="content-wrapper">

      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
          <i class="bi bi-folder-check"></i> Input Administrasi <?= $activeTab === 'kegiatan' ? 'Kegiatan' : 'Petugas' ?>
        </h4>
      </div>

      <!-- Tab Navigation -->
      <ul class="nav nav-tabs mb-3">
        <?php if ($canSeeKegiatanTab): ?>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'kegiatan' ? 'active' : '' ?>"
             href="index.php?controller=administrasi&action=form&tab=kegiatan<?= !empty($_GET['kegiatan_id']) ? '&kegiatan_id=' . (int)$_GET['kegiatan_id'] : '' ?>">
            <i class="bi bi-folder2-open"></i> Kegiatan
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'petugas' ? 'active' : '' ?>"
             href="index.php?controller=administrasi&action=form&tab=petugas<?= !empty($_GET['kegiatan_id']) ? '&kegiatan_id=' . (int)$_GET['kegiatan_id'] : '' ?>">
            <i class="bi bi-people"></i> Petugas
          </a>
        </li>
      </ul>

      <?php if ($activeTab === 'kegiatan'):
          include __DIR__ . '/form_kegiatan.php';
      else: ?>

      <!-- Flash Messages -->
      <div id="alertContainer">
        <?php if (!empty($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Filter Card -->
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <form method="GET" action="" class="row g-3">
            <input type="hidden" name="controller" value="administrasi">
            <input type="hidden" name="action" value="form">
            
            <div class="col-md-6">
              <label class="form-label">Filter Kegiatan</label>
              <select name="kegiatan_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Kegiatan --</option>
                <?php if (isset($kegiatanList)): foreach ($kegiatanList as $k): ?>
                  <option value="<?= $k['id'] ?>" <?= (isset($_GET['kegiatan_id']) && $_GET['kegiatan_id'] == $k['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama_kegiatan']) ?>
                  </option>
                <?php endforeach; endif; ?>
              </select>
            </div>
            
            <div class="col-md-6 d-flex align-items-end">
              <button type="submit" class="btn btn-primary me-2">
                <i class="bi bi-funnel"></i> Filter
              </button>
              <a href="index.php?controller=administrasi&action=form" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Reset
              </a>
            </div>
          </form>
        </div>
      </div>

      <!-- Info Alert -->
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <strong>Petunjuk:</strong> 
        Upload dokumen Perjalanan Dinas dan Honorarium untuk setiap petugas. 
        <br><small class="text-muted">
          <strong>Catatan:</strong> 
          • Pegawai (PNS) tidak wajib mengisi Perjalanan Dinas dan tidak perlu mengisi Honorarium.
          • Mitra wajib mengisi kedua dokumen.
        </small>
      </div>

      <!-- Tombol Download Semua File (hanya tampil jika ada kegiatan yang dipilih) -->
      <?php if (!empty($_GET['kegiatan_id'])): ?>
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-success text-white">
          <i class="bi bi-download"></i> Download Semua File Kegiatan
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-auto">
              <a href="index.php?controller=administrasi&action=downloadAllFilesZip&kegiatan_id=<?= htmlspecialchars($_GET['kegiatan_id']) ?>&jenis=spd" 
                 class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="bi bi-file-earmark-zip"></i> Surat Tugas & SPD
              </a>
            </div>
            <div class="col-auto">
              <a href="index.php?controller=administrasi&action=downloadAllFilesZip&kegiatan_id=<?= htmlspecialchars($_GET['kegiatan_id']) ?>&jenis=laporan_perjalanan" 
                 class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="bi bi-file-earmark-zip"></i> Laporan Perjalanan
              </a>
            </div>
            <div class="col-auto">
              <a href="index.php?controller=administrasi&action=downloadAllFilesZip&kegiatan_id=<?= htmlspecialchars($_GET['kegiatan_id']) ?>&jenis=pengeluaran" 
                 class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="bi bi-file-earmark-zip"></i> Bukti Pengeluaran
              </a>
            </div>
            <div class="col-auto">
              <a href="index.php?controller=administrasi&action=downloadAllFilesZip&kegiatan_id=<?= htmlspecialchars($_GET['kegiatan_id']) ?>&jenis=foto" 
                 class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="bi bi-file-earmark-zip"></i> Foto Dokumentasi
              </a>
            </div>
            <div class="col-auto">
              <a href="index.php?controller=administrasi&action=downloadAllFilesZip&kegiatan_id=<?= htmlspecialchars($_GET['kegiatan_id']) ?>&jenis=bast" 
                 class="btn btn-outline-success btn-sm" target="_blank">
                <i class="bi bi-file-earmark-zip"></i> BAST
              </a>
            </div>
            <div class="col-auto">
              <a href="index.php?controller=administrasi&action=downloadAllFilesZip&kegiatan_id=<?= htmlspecialchars($_GET['kegiatan_id']) ?>&jenis=perjanjian" 
                 class="btn btn-outline-success btn-sm" target="_blank">
                <i class="bi bi-file-earmark-zip"></i> SPK / Perjanjian
              </a>
            </div>
            <div class="col-auto">
              <a href="index.php?controller=administrasi&action=downloadAllFilesZip&kegiatan_id=<?= htmlspecialchars($_GET['kegiatan_id']) ?>" 
                 class="btn btn-success btn-sm" target="_blank">
                <i class="bi bi-file-earmark-zip-fill"></i> Semua File
              </a>
            </div>
          </div>
          <small class="text-muted mt-2 d-block">
            <i class="bi bi-info-circle"></i> File akan didownload dalam format ZIP dengan nama: NamaKegiatan_JenisFile_NamaPetugas
          </small>
        </div>
      </div>
      <?php endif; ?>

      <!-- Table List Petugas -->
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
              <thead class="table-primary">
                <tr>
                  <th width="30">No</th>
                  <th>Nama Kegiatan</th>
                  <th>Struktur</th>
                  <th>Nama Petugas</th>
                  <th>Jenis</th>
                  <th>Peran</th>
                  <th width="150">Perjalanan Dinas</th>
                  <th width="150">Honorarium</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($assignments)): ?>
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                      <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                      <p class="mt-2">Tidak ada data petugas. Silakan tambahkan petugas di menu Input Petugas.</p>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php 
                  $no = 1;
                  foreach ($assignments as $a): 
                    $isPML = ($a['peran'] === 'PML');
                    $isSupervisi = ($a['peran'] === 'Supervisi');
                    $isPNS = ($a['petugas_jenis'] === 'PNS');
                    
                    // Cek status verifikasi
                    $isRejected = isset($a['verification_status']) && $a['verification_status'] === 'rejected_operator_tim';
                    $isPending = isset($a['verification_status']) && $a['verification_status'] === 'pending_operator_tim';
                    $isApproved = isset($a['verification_status']) && strpos($a['verification_status'], 'approved') !== false;
                    $isSent = $a['sent_to_verification'] ?? false;
                    
                    // Logika kelengkapan untuk bisa kirim:
                    // - Mitra: Wajib lengkap perjalanan DAN honorarium
                    // - PNS: Perjalanan opsional, Honorarium tidak perlu
                    if ($isPNS) {
                      // PNS: bisa kirim kapan saja (perjalanan opsional, honorarium tidak perlu)
                      $canSend = true;
                    } else {
                      // Mitra: wajib lengkap perjalanan dan honorarium
                      $canSend = $a['perjalanan_complete'] && $a['honorarium_complete'];
                    }
                  ?>
                    <tr class="<?= $isPML ? 'table-warning' : ($isSupervisi ? 'table-info' : '') ?> <?= $isRejected ? 'table-danger' : '' ?>">
                      <td class="text-center"><?= $no++ ?></td>
                      <td><?= htmlspecialchars($a['nama_kegiatan']) ?></td>

                      <!-- Struktur/Hierarki -->
                      <td>
                        <?php if ($isPML): ?>
                          <span class="badge bg-warning text-dark">
                            <i class="bi bi-person-badge"></i> PML (Pemeriksa)
                          </span>
                        <?php elseif ($isSupervisi): ?>
                          <span class="badge bg-primary">
                            <i class="bi bi-shield-check"></i> Supervisi (tanpa bawahan)
                          </span>
                        <?php else: ?>
                          <div>
                            <small class="text-muted">PPL dibawah:</small><br>
                            <?php if (!empty($a['pml_name'])): ?>
                              <span class="badge bg-warning text-dark">
                                <?= htmlspecialchars($a['pml_name']) ?>
                              </span>
                            <?php else: ?>
                              <small class="text-danger">Belum ditentukan</small>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                      </td>

                      <td><?= htmlspecialchars($a['petugas_nama'] ?? '-') ?></td>

                      <!-- Jenis -->
                      <td>
                        <span class="badge <?= $isPNS ? 'bg-primary' : 'bg-info' ?>">
                          <?= htmlspecialchars($a['petugas_jenis'] ?? '-') ?>
                        </span>
                      </td>

                      <!-- Peran -->
                      <td>
                        <span class="badge <?= $isPML ? 'bg-warning text-dark' : ($isSupervisi ? 'bg-primary' : 'bg-secondary') ?>">
                          <?= htmlspecialchars($a['peran']) ?>
                        </span>
                      </td>
                      
                      <!-- KOLOM PERJALANAN DINAS -->
                      <td class="text-center">
                        <?php if ($a['perjalanan_complete']): ?>
                          <span class="badge bg-success mb-2 d-block">
                            <i class="bi bi-check-circle"></i> Lengkap
                          </span>
                          <button type="button" 
                                  class="btn btn-sm btn-warning w-100"
                                  data-bs-toggle="modal" 
                                  data-bs-target="#modalPerjalananDinas"
                                  data-kegiatan-id="<?= $a['id'] ?>"
                                  data-petugas-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '') ?>"
                                  data-nama-kegiatan="<?= htmlspecialchars($a['nama_kegiatan']) ?>"
                                  data-mode="edit">
                            <i class="bi bi-pencil"></i> Edit
                          </button>
                        <?php else: ?>
                          <?php if ($isPNS): ?>
                            <span class="badge bg-secondary mb-2 d-block">
                              <i class="bi bi-dash-circle"></i> Opsional
                            </span>
                          <?php endif; ?>
                          <button type="button" 
                                  class="btn btn-sm btn-primary w-100"
                                  data-bs-toggle="modal" 
                                  data-bs-target="#modalPerjalananDinas"
                                  data-kegiatan-id="<?= $a['id'] ?>"
                                  data-petugas-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '') ?>"
                                  data-nama-kegiatan="<?= htmlspecialchars($a['nama_kegiatan']) ?>"
                                  data-mode="input">
                            <i class="bi bi-plus-circle"></i> Input
                          </button>
                        <?php endif; ?>
                      </td>
                      
                      <!-- KOLOM HONORARIUM -->
                      <td class="text-center">
                        <?php if ($isPNS): ?>
                          <!-- PNS tidak perlu input honorarium -->
                          <span class="badge bg-secondary d-block">
                            <i class="bi bi-dash-circle"></i> Tidak Perlu
                          </span>
                          <small class="text-muted">Pegawai</small>
                        <?php elseif ($a['honorarium_complete']): ?>
                          <span class="badge bg-success mb-2 d-block">
                            <i class="bi bi-check-circle"></i> Lengkap
                          </span>
                          <button type="button" 
                                  class="btn btn-sm btn-warning w-100"
                                  data-bs-toggle="modal" 
                                  data-bs-target="#modalHonorarium"
                                  data-kegiatan-id="<?= $a['id'] ?>"
                                  data-petugas-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '') ?>"
                                  data-nama-kegiatan="<?= htmlspecialchars($a['nama_kegiatan']) ?>"
                                  data-mode="edit">
                            <i class="bi bi-pencil"></i> Edit
                          </button>
                        <?php else: ?>
                          <button type="button" 
                                  class="btn btn-sm btn-primary w-100"
                                  data-bs-toggle="modal" 
                                  data-bs-target="#modalHonorarium"
                                  data-kegiatan-id="<?= $a['id'] ?>"
                                  data-petugas-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '') ?>"
                                  data-nama-kegiatan="<?= htmlspecialchars($a['nama_kegiatan']) ?>"
                                  data-mode="input">
                            <i class="bi bi-plus-circle"></i> Input
                          </button>
                        <?php endif; ?>
                      </td>
                      
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php endif; /* end activeTab petugas */ ?>

    </div>
  </div>
</div>

<!-- ========================================
     MODAL PERJALANAN DINAS (UPDATED - dengan Laporan Perjalanan & Download)
     ======================================== -->
<div class="modal fade" id="modalPerjalananDinas" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="POST" 
          action="<?= $baseUrl ?>/index.php?controller=administrasi&action=savePerjalanan"
          enctype="multipart/form-data">
      
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="bi bi-briefcase"></i> Upload Dokumen Perjalanan Dinas
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      
      <div class="modal-body">
        <!-- Hidden inputs -->
        <input type="hidden" name="kegiatan_petugas_id" id="perjalanan_kegiatan_id">
        <input type="hidden" name="mode" id="perjalanan_mode" value="input">
        
        <!-- Info Petugas -->
        <div class="alert alert-info" id="perjalanan_info">
          <strong>Petugas:</strong> <span id="perjalanan_nama_petugas"></span><br>
          <strong>Kegiatan:</strong> <span id="perjalanan_nama_kegiatan"></span>
        </div>
        
        <!-- Upload Surat Tugas dan SPD -->
        <div class="mb-3">
          <label for="file_spd" class="form-label">
            <i class="bi bi-file-pdf"></i> Upload Surat Tugas dan SPD (PDF) <span class="text-danger">*</span>
          </label>
          <input type="file" class="form-control" id="file_spd" 
                 name="file_spd" accept="application/pdf">
          <small class="form-text text-muted">Format: PDF, Maksimal 5MB</small>
          <div id="existing_spd" class="mt-2"></div>
        </div>
        
        <!-- Upload Laporan Perjalanan (NEW) -->
        <div class="mb-3">
          <label for="file_laporan_perjalanan" class="form-label">
            <i class="bi bi-file-text"></i> Upload Laporan Perjalanan (PDF) <span class="text-danger">*</span>
          </label>
          <input type="file" class="form-control" id="file_laporan_perjalanan" 
                 name="file_laporan_perjalanan" accept="application/pdf">
          <small class="form-text text-muted">Format: PDF, Maksimal 5MB</small>
          <div id="existing_laporan_perjalanan" class="mt-2"></div>
        </div>
        
        <!-- Upload Pengeluaran -->
        <div class="mb-3">
          <label for="file_pengeluaran" class="form-label">
            <i class="bi bi-file-pdf"></i> Upload Bukti Pengeluaran (PDF) <span class="text-danger">*</span>
          </label>
          <input type="file" class="form-control" id="file_pengeluaran" 
                 name="file_pengeluaran" accept="application/pdf">
          <small class="form-text text-muted">Format: PDF, Maksimal 5MB</small>
          <div id="existing_pengeluaran" class="mt-2"></div>
        </div>
        
        <!-- Upload Foto -->
        <div class="mb-3">
          <label class="form-label">
            <i class="bi bi-image"></i> Upload Foto Dokumentasi
          </label>
          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            Upload foto dokumentasi kegiatan (JPG, PNG, GIF). Maksimal 5MB per foto.
          </div>
          
          <div id="foto_container">
            <div class="input-group mb-2">
              <input type="text" name="keterangan_foto[]" class="form-control" 
                     placeholder="Keterangan foto" value="Dokumentasi Kegiatan">
              <input type="file" name="file_foto[]" class="form-control" 
                     accept="image/jpeg,image/jpg,image/png,image/gif">
              <button type="button" class="btn btn-success" onclick="addFotoRow()">
                <i class="bi bi-plus-circle"></i>
              </button>
            </div>
          </div>
          
          <div id="existing_fotos" class="mt-3"></div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Batal
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================
     MODAL HONORARIUM (UPDATED - dengan Download)
     ======================================== -->
<div class="modal fade" id="modalHonorarium" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="POST" 
          action="<?= $baseUrl ?>/index.php?controller=administrasi&action=saveHonorarium"
          enctype="multipart/form-data">
      
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i class="bi bi-cash-coin"></i> Upload Dokumen Honorarium Kegiatan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      
      <div class="modal-body">
        <!-- Hidden inputs -->
        <input type="hidden" name="kegiatan_petugas_id" id="honorarium_kegiatan_id">
        <input type="hidden" name="mode" id="honorarium_mode" value="input">
        
        <!-- Info Petugas -->
        <div class="alert alert-info" id="honorarium_info">
          <strong>Petugas:</strong> <span id="honorarium_nama_petugas"></span><br>
          <strong>Kegiatan:</strong> <span id="honorarium_nama_kegiatan"></span>
        </div>
        
        <!-- Upload BAST -->
        <div class="mb-3">
          <label for="file_bast" class="form-label">
            <i class="bi bi-file-pdf"></i> Upload BAST (Berita Acara Serah Terima) <span class="text-danger">*</span>
          </label>
          <input type="file" class="form-control" id="file_bast" 
                 name="file_bast" accept="application/pdf" required>
          <small class="form-text text-muted">Format: PDF, Maksimal 5MB</small>
          <div id="existing_bast" class="mt-2"></div>
        </div>
        
        <!-- Upload Perjanjian/SPK -->
        <div class="mb-3">
          <label for="file_perjanjian" class="form-label">
            <i class="bi bi-file-pdf"></i> Upload Surat Perjanjian Kerja / SPK <span class="text-danger">*</span>
          </label>
          <input type="file" class="form-control" id="file_perjanjian" 
                 name="file_perjanjian" accept="application/pdf" required>
          <small class="form-text text-muted">Format: PDF, Maksimal 5MB</small>
          <div id="existing_perjanjian" class="mt-2"></div>
        </div>
        
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>Perhatian:</strong> Pastikan nomor dan tanggal di BAST sesuai dengan Surat Perjanjian Kerja.
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Batal
        </button>
        <button type="submit" class="btn btn-success">
          <i class="bi bi-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================
     MODAL LIHAT CATATAN PENOLAKAN
     ======================================== -->
<div class="modal fade" id="modalCatatanPenolakan" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Catatan Penolakan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <strong>Alasan Penolakan:</strong>
          <p class="mb-0 mt-2" id="catatan_penolakan_text"></p>
        </div>
        <p class="text-muted"><i class="bi bi-info-circle"></i> Silakan perbaiki dokumen sesuai catatan di atas, kemudian klik tombol <strong>Kirim Ulang</strong>.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================
     JAVASCRIPT FUNCTIONS
     ======================================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Administrasi form loaded');
    
    // ========================================
    // MODAL PERJALANAN DINAS HANDLER
    // ========================================
    const modalPerjalananDinas = document.getElementById('modalPerjalananDinas');
    if (modalPerjalananDinas) {
        modalPerjalananDinas.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const kegiatanId = button.getAttribute('data-kegiatan-id');
            const mode = button.getAttribute('data-mode');
            const petugasNama = button.getAttribute('data-petugas-nama');
            const namaKegiatan = button.getAttribute('data-nama-kegiatan');
            
            document.getElementById('perjalanan_kegiatan_id').value = kegiatanId;
            document.getElementById('perjalanan_mode').value = mode;
            document.getElementById('perjalanan_nama_petugas').textContent = petugasNama;
            document.getElementById('perjalanan_nama_kegiatan').textContent = namaKegiatan;
            
            // Reset existing displays
            document.getElementById('existing_spd').innerHTML = '';
            document.getElementById('existing_laporan_perjalanan').innerHTML = '';
            document.getElementById('existing_pengeluaran').innerHTML = '';
            document.getElementById('existing_fotos').innerHTML = '';
            
            if (mode === 'edit') {
                loadExistingPerjalanan(kegiatanId);
                // Tidak required saat edit
                document.getElementById('file_spd').required = false;
                document.getElementById('file_laporan_perjalanan').required = false;
                document.getElementById('file_pengeluaran').required = false;
            } else {
                // Required saat input baru - tapi bisa di-override jika PNS
                // Biarkan server yang handle validasi
                document.getElementById('file_spd').required = false;
                document.getElementById('file_laporan_perjalanan').required = false;
                document.getElementById('file_pengeluaran').required = false;
            }
        });
    }
    
    // ========================================
    // MODAL HONORARIUM HANDLER
    // ========================================
    const modalHonorarium = document.getElementById('modalHonorarium');
    if (modalHonorarium) {
        modalHonorarium.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const kegiatanId = button.getAttribute('data-kegiatan-id');
            const mode = button.getAttribute('data-mode');
            const petugasNama = button.getAttribute('data-petugas-nama');
            const namaKegiatan = button.getAttribute('data-nama-kegiatan');
            
            document.getElementById('honorarium_kegiatan_id').value = kegiatanId;
            document.getElementById('honorarium_mode').value = mode;
            document.getElementById('honorarium_nama_petugas').textContent = petugasNama;
            document.getElementById('honorarium_nama_kegiatan').textContent = namaKegiatan;
            
            document.getElementById('existing_bast').innerHTML = '';
            document.getElementById('existing_perjanjian').innerHTML = '';
            
            if (mode === 'edit') {
                loadExistingHonorarium(kegiatanId);
                document.getElementById('file_bast').required = false;
                document.getElementById('file_perjanjian').required = false;
            } else {
                document.getElementById('file_bast').required = true;
                document.getElementById('file_perjanjian').required = true;
            }
        });
    }
    
    // ========================================
    // KIRIM VERIFIKASI BUTTON HANDLER
    // ========================================
    document.querySelectorAll('.btn-kirim-verifikasi').forEach(btn => {
        btn.addEventListener('click', function() {
            const kegiatanId = this.dataset.kegiatanId;
            const petugasNama = this.dataset.petugasNama;
            const namaKegiatan = this.dataset.namaKegiatan;
            kirimVerifikasi(kegiatanId, petugasNama, namaKegiatan, this);
        });
    });
    
    // ========================================
    // KIRIM ULANG BUTTON HANDLER
    // ========================================
    document.querySelectorAll('.btn-kirim-ulang').forEach(btn => {
        btn.addEventListener('click', function() {
            const kegiatanId = this.dataset.kegiatanId;
            const petugasNama = this.dataset.petugasNama;
            const namaKegiatan = this.dataset.namaKegiatan;
            
            if (confirm(`Yakin ingin mengirim ulang data administrasi?\n\nPetugas: ${petugasNama}\nKegiatan: ${namaKegiatan}`)) {
                kirimUlangVerifikasi(kegiatanId, this);
            }
        });
    });
    
    // ========================================
    // LIHAT CATATAN BUTTON HANDLER
    // ========================================
    document.querySelectorAll('.btn-lihat-catatan').forEach(btn => {
        btn.addEventListener('click', function() {
            const catatan = this.dataset.catatan;
            document.getElementById('catatan_penolakan_text').textContent = catatan;
            const modal = new bootstrap.Modal(document.getElementById('modalCatatanPenolakan'));
            modal.show();
        });
    });
});

// ========================================
// LOAD EXISTING FILES - PERJALANAN DINAS (UPDATED with Download button)
// ========================================
function loadExistingPerjalanan(kegiatanId) {
    fetch('index.php?controller=administrasi&action=getFiles&id=' + kegiatanId + '&jenis=perjalanan')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.files.perjalanan_dinas) {
                const perjalanan = data.files.perjalanan_dinas;
                
                // SPD (formerly SPPD)
                if (perjalanan.spd) {
                    document.getElementById('existing_spd').innerHTML = createFilePreview(perjalanan.spd, 'Surat Tugas & SPD');
                } else if (perjalanan.sppd) {
                    document.getElementById('existing_spd').innerHTML = createFilePreview(perjalanan.sppd, 'Surat Tugas & SPD');
                }
                
                // Laporan Perjalanan
                if (perjalanan.laporan_perjalanan) {
                    document.getElementById('existing_laporan_perjalanan').innerHTML = createFilePreview(perjalanan.laporan_perjalanan, 'Laporan Perjalanan');
                }
                
                // Pengeluaran
                if (perjalanan.pengeluaran) {
                    document.getElementById('existing_pengeluaran').innerHTML = createFilePreview(perjalanan.pengeluaran, 'Bukti Pengeluaran');
                }
                
                // Fotos
                if (perjalanan.fotos && perjalanan.fotos.length > 0) {
                    let html = '<div class="alert alert-info mb-3">';
                    html += '<strong><i class="bi bi-images"></i> Foto yang sudah diupload:</strong><br>';
                    html += '<div class="row g-2 mt-2">';
                    
                    perjalanan.fotos.forEach(foto => {
                        html += `<div class="col-md-4">
                            <div class="card border">
                                <div class="card-body p-2">
                                    <small><strong>${foto.keterangan || 'Foto'}</strong></small><br>
                                    <small class="text-muted">${foto.nama_file}</small>
                                    <div class="mt-1">
                                        <a href="${foto.file_path}" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                        <a href="${foto.file_path}" download class="btn btn-sm btn-primary"><i class="bi bi-download"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    });
                    
                    html += '</div></div>';
                    document.getElementById('existing_fotos').innerHTML = html;
                }
            }
        })
        .catch(error => console.error('Error loading existing perjalanan:', error));
}

// ========================================
// LOAD EXISTING FILES - HONORARIUM (UPDATED with Download button)
// ========================================
function loadExistingHonorarium(kegiatanId) {
    fetch('index.php?controller=administrasi&action=getFiles&id=' + kegiatanId + '&jenis=honorarium')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.files.honorarium) {
                const honorarium = data.files.honorarium;

                // BAST
                if (honorarium.bast) {
                    document.getElementById('existing_bast').innerHTML = createFilePreview(honorarium.bast, 'BAST');
                }
                
                // Perjanjian/SPK
                if (honorarium.perjanjian) {
                    document.getElementById('existing_perjanjian').innerHTML = createFilePreview(honorarium.perjanjian, 'SPK / Perjanjian');
                }
            }
        })
        .catch(error => console.error('Error loading existing honorarium:', error));
}

// ========================================
// CREATE FILE PREVIEW WITH DOWNLOAD BUTTON
// Format download: namaKegiatan_jenisFile_namaPetugas
// ========================================
function createFilePreview(file, label) {
    // Gunakan endpoint download yang akan format nama file dengan benar
    const downloadUrl = file.id 
        ? `index.php?controller=administrasi&action=downloadFileFormatted&file_id=${file.id}`
        : file.file_path;
    
    return `<div class="alert alert-success py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-check-circle"></i> <strong>${label}:</strong> ${file.nama_file}<br>
                <small class="text-muted">Upload file baru untuk mengganti</small>
            </div>
            <div>
                <a href="${file.file_path}" target="_blank" class="btn btn-sm btn-info" title="Lihat File">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="${downloadUrl}" class="btn btn-sm btn-primary" title="Download">
                    <i class="bi bi-download"></i>
                </a>
            </div>
        </div>
    </div>`;
}

// ========================================
// TAMBAH INPUT FOTO
// ========================================
function addFotoRow() {
    const container = document.getElementById('foto_container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="keterangan_foto[]" class="form-control" placeholder="Keterangan foto">
        <input type="file" name="file_foto[]" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif">
        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

// ========================================
// KIRIM VERIFIKASI FUNCTION
// ========================================
function kirimVerifikasi(kegiatanId, petugasNama, namaKegiatan, buttonElement) {
    const confirmation = confirm(`Yakin ingin mengirim data administrasi ini ke verifikasi?\n\nPetugas: ${petugasNama}\nKegiatan: ${namaKegiatan}\n\nSetelah dikirim, data akan diverifikasi oleh Operator Tim.`);
    
    if (!confirmation) return;
    
    const originalHtml = buttonElement.innerHTML;
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengirim...';
    
    const formData = new FormData();
    formData.append('kegiatan_petugas_id', kegiatanId);
    
    fetch('index.php?controller=administrasi&action=kirimVerifikasi', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', '✅ Data berhasil dikirim ke verifikasi!');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', '❌ Error: ' + (data.error || 'Gagal mengirim data'));
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        showAlert('danger', '❌ Terjadi kesalahan: ' + error.message);
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalHtml;
    });
}

// ========================================
// KIRIM ULANG VERIFIKASI FUNCTION
// ========================================
function kirimUlangVerifikasi(kegiatanId, buttonElement) {
    const originalHtml = buttonElement.innerHTML;
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengirim...';
    
    const formData = new FormData();
    formData.append('kegiatan_petugas_id', kegiatanId);
    
    fetch('index.php?controller=administrasi&action=kirimUlangVerifikasi', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', '✅ Data berhasil dikirim ulang ke verifikasi!');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', '❌ Error: ' + (data.error || 'Gagal mengirim ulang'));
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        showAlert('danger', '❌ Terjadi kesalahan: ' + error.message);
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalHtml;
    });
}

// ========================================
// SHOW ALERT
// ========================================
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
    
    document.getElementById('alertContainer').innerHTML = alertHtml;
    
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) alert.remove();
    }, 5000);
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>