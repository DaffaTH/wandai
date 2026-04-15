<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';
?>

<style>
/* ========== RESPONSIVE DESIGN ========== */
.content-wrapper {
    padding: 1rem;
}

.card-body.d-flex.flex-wrap {
    gap: 0.5rem !important;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

#datatable {
    min-width: 1400px;
}

/* Sortable Column Styling */
.sortable-column {
    cursor: pointer;
    user-select: none;
}
.sortable-column:hover {
    background-color: #e9ecef !important;
}
.sortable-column .sort-icon {
    transition: all 0.2s ease;
}
.sortable-column:hover .sort-icon {
    color: #495057 !important;
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 0.5rem;
    }
    
    .card-body.d-flex.flex-wrap .btn {
        flex: 1 1 calc(50% - 0.5rem);
        font-size: 0.75rem;
        padding: 0.5rem;
    }
    
    h4.mb-4 {
        font-size: 1.1rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
}

@media (max-width: 576px) {
    .card-body.d-flex.flex-wrap .btn {
        flex: 1 1 100%;
    }
}

.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.ppl-card {
    transition: all 0.3s ease;
}

.ppl-card:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
}

.conditional-field-info {
    background-color: #e7f3ff;
    border-left: 4px solid #0d6efd;
    padding: 0.75rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
}

.dataTables_wrapper,
table, .table, .badge, .btn, .form-control, .form-select {
    font-family: 'Poppins', sans-serif !important;
}

#modalDaftarPetugas .table-responsive {
    max-height: 400px;
    overflow-y: auto;
}

/* Fix filter layout */
.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: end;
}

.filter-row > div {
    flex: 1;
    min-width: 150px;
}

@media (max-width: 768px) {
    .filter-row > div {
        flex: 1 1 100%;
    }
}

.validation-error {
    color: #dc3545;
    font-size: 0.85rem;
}

.validation-success {
    color: #198754;
    font-size: 0.85rem;
}

.generate-all-card {
    border: 2px solid #198754;
}

.generate-all-card .card-header {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
}
</style>

<div class="d-flex">
  <div class="sidebar">
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
  </div>

  <div class="flex-grow-1">
    <div class="content-wrapper">
      <h4 class="mb-4"><i class="bi bi-people"></i> Monitoring Petugas per Kegiatan</h4>

      <!-- Flash Messages -->
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <pre style="margin:0; white-space: pre-wrap;"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></pre>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <pre style="margin:0; white-space: pre-wrap; font-size: 0.85rem;"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></pre>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (!empty($_SESSION['info'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- ========== INLINE KONFIRMASI HONOR (tambah petugas) ========== -->
      <?php if (!empty($_SESSION['honor_confirm_pending'])): 
        $honorConfirmData = $_SESSION['honor_confirm_pending'];
      ?>
      <div class="card shadow-sm mb-4 border-warning" id="honorConfirmCard">
        <div class="card-header bg-warning text-dark">
          <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Konfirmasi Honor Melebihi Batas
          </h5>
        </div>
        <div class="card-body">
          <div class="alert alert-warning mb-3">
            <strong><i class="bi bi-info-circle"></i> Perhatian!</strong><br>
            Honor mitra berikut akan melebihi <strong>Rp 4.000.000</strong> pada bulan ini jika kegiatan "<strong><?= htmlspecialchars($honorConfirmData['kegiatan_nama'] ?? 'Kegiatan') ?></strong>" ditambahkan:
          </div>
          
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="table-danger">
                <tr>
                  <th>Nama Mitra</th>
                  <th>Peran</th>
                  <th class="text-end">Honor Saat Ini</th>
                  <th class="text-end">+ Honor Baru</th>
                  <th class="text-end">= Total Proyeksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($honorConfirmData['mitra_list'] as $m): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($m['nama']) ?></strong></td>
                  <td><span class="badge bg-<?= $m['peran'] === 'PML' ? 'primary' : 'success' ?>"><?= $m['peran'] ?></span></td>
                  <td class="text-end">Rp <?= number_format($m['current'], 0, ',', '.') ?></td>
                  <td class="text-end text-success">+ Rp <?= number_format($m['baru'], 0, ',', '.') ?></td>
                  <td class="text-end text-danger"><strong>Rp <?= number_format($m['proyeksi'], 0, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <div class="alert alert-info py-2 mb-3">
            <i class="bi bi-question-circle"></i> Apakah Anda yakin ingin melanjutkan penambahan petugas ini?
          </div>
          
          <div class="d-flex justify-content-center gap-3">
            <form method="POST" action="index.php?controller=petugas_kegiatan&action=processConfirmHonor" style="display:inline;">
              <input type="hidden" name="action" value="confirm">
              <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle me-2"></i>Ya, Lanjutkan
              </button>
            </form>
            
            <form method="POST" action="index.php?controller=petugas_kegiatan&action=processConfirmHonor" style="display:inline;">
              <input type="hidden" name="action" value="cancel">
              <button type="submit" class="btn btn-danger">
                <i class="bi bi-x-circle me-2"></i>Tidak, Batalkan
              </button>
            </form>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- ========== INLINE KONFIRMASI IMPORT EXCEL ========== -->
      <?php if (!empty($_SESSION['import_honor_confirm'])): 
        $importConfirmData = $_SESSION['import_honor_confirm'];
      ?>
      <div class="card shadow-sm mb-4 border-warning" id="importConfirmCard">
        <div class="card-header bg-warning text-dark">
          <h5 class="mb-0">
            <i class="bi bi-file-earmark-excel me-2"></i>
            Konfirmasi Import Excel - Honor Melebihi Batas
          </h5>
        </div>
        <div class="card-body">
          <div class="alert alert-warning mb-3">
            <strong><i class="bi bi-info-circle"></i> Perhatian!</strong><br>
            Import untuk kegiatan "<strong><?= htmlspecialchars($importConfirmData['kegiatan_nama'] ?? 'Kegiatan') ?></strong>" memiliki mitra yang honornya akan melebihi <strong>Rp 4.000.000</strong>:
          </div>
          
          <!-- Mitra yang perlu konfirmasi -->
          <?php if (!empty($importConfirmData['mitra_list'])): ?>
          <h6 class="text-warning"><i class="bi bi-exclamation-triangle"></i> Perlu Konfirmasi (Honor akan melebihi Rp 4 Juta):</h6>
          <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm">
              <thead class="table-warning">
                <tr>
                  <th>Baris</th>
                  <th>Nama Mitra</th>
                  <th>Peran</th>
                  <th class="text-end">Honor Saat Ini</th>
                  <th class="text-end">+ Honor Baru</th>
                  <th class="text-end">= Total Proyeksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($importConfirmData['mitra_list'] as $m): ?>
                <tr>
                  <td><?= $m['row'] ?></td>
                  <td><strong><?= htmlspecialchars($m['nama']) ?></strong></td>
                  <td><span class="badge bg-<?= $m['peran'] === 'PML' ? 'primary' : 'success' ?>"><?= $m['peran'] ?></span></td>
                  <td class="text-end">Rp <?= number_format($m['current'], 0, ',', '.') ?></td>
                  <td class="text-end text-success">+ Rp <?= number_format($m['baru'], 0, ',', '.') ?></td>
                  <td class="text-end text-danger"><strong>Rp <?= number_format($m['proyeksi'], 0, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
          
          <!-- Mitra yang diblokir -->
          <?php if (!empty($importConfirmData['blocked_list'])): ?>
          <h6 class="text-danger"><i class="bi bi-x-circle"></i> Diblokir (Tidak akan diimport):</h6>
          <ul class="list-group mb-3">
            <?php foreach (array_slice($importConfirmData['blocked_list'], 0, 5) as $blocked): ?>
            <li class="list-group-item list-group-item-danger py-1 small"><?= htmlspecialchars($blocked) ?></li>
            <?php endforeach; ?>
            <?php if (count($importConfirmData['blocked_list']) > 5): ?>
            <li class="list-group-item py-1 small text-muted">...dan <?= count($importConfirmData['blocked_list']) - 5 ?> lainnya</li>
            <?php endif; ?>
          </ul>
          <?php endif; ?>
          
          <!-- Error -->
          <?php if (!empty($importConfirmData['error_list'])): ?>
          <h6 class="text-danger"><i class="bi bi-exclamation-circle"></i> Error:</h6>
          <ul class="list-group mb-3">
            <?php foreach (array_slice($importConfirmData['error_list'], 0, 5) as $error): ?>
            <li class="list-group-item list-group-item-danger py-1 small"><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          
          <div class="alert alert-info py-2 mb-3">
            <i class="bi bi-question-circle"></i> Apakah Anda yakin ingin melanjutkan import? Mitra dengan honor melebihi batas akan tetap diimport jika Anda klik "Ya, Lanjutkan".
          </div>
          
          <div class="d-flex justify-content-center gap-3">
            <form method="POST" action="index.php?controller=petugas_kegiatan&action=processImportConfirm" style="display:inline;">
              <input type="hidden" name="action" value="confirm">
              <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle me-2"></i>Ya, Lanjutkan Import
              </button>
            </form>
            
            <form method="POST" action="index.php?controller=petugas_kegiatan&action=processImportConfirm" style="display:inline;">
              <input type="hidden" name="action" value="cancel">
              <button type="submit" class="btn btn-danger">
                <i class="bi bi-x-circle me-2"></i>Batalkan Import
              </button>
            </form>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($_SESSION['import_details'])): ?>
        <div class="alert alert-info alert-dismissible fade show">
          <h6><i class="bi bi-info-circle"></i> Detail Import:</h6>
          <?php if (!empty($_SESSION['import_details']['errors'])): ?>
            <div class="mb-2">
              <strong>❌ Baris Gagal (Email tidak ditemukan):</strong>
              <ul class="mb-0">
                <?php foreach (array_slice($_SESSION['import_details']['errors'], 0, 5, true) as $row => $errors): ?>
                  <li><?= is_array($errors) ? implode(', ', $errors) : $errors ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <?php if (!empty($_SESSION['import_details']['blocked'])): ?>
            <div class="mb-2">
              <strong>🚫 Diblokir (OB/Honor melebihi):</strong>
              <ul class="mb-0">
                <?php foreach (array_slice($_SESSION['import_details']['blocked'], 0, 5, true) as $row => $blocked): ?>
                  <li><?= is_array($blocked) ? implode(', ', $blocked) : $blocked ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <?php if (!empty($_SESSION['import_details']['warnings'])): ?>
            <div>
              <strong>⚠️ Warning (Duplikasi/Dilewati):</strong>
              <ul class="mb-0">
                <?php foreach (array_slice($_SESSION['import_details']['warnings'], 0, 5, true) as $row => $warnings): ?>
                  <li><?= is_array($warnings) ? implode(', ', $warnings) : $warnings ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['import_details']); ?>
      <?php endif; ?>

      <!-- Card Tombol Aksi -->
      <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2">
          <button type="button" data-bs-toggle="modal" data-bs-target="#modalTambah"
                  class="btn btn-outline-success d-flex align-items-center gap-1">
            <i class="bi bi-person-plus"></i><strong>Tambah Petugas</strong>
          </button>

          <button type="button" data-bs-toggle="modal" data-bs-target="#modalImport"
                  class="btn btn-outline-primary d-flex align-items-center gap-1">
            <i class="bi bi-file-earmark-excel"></i><strong>Import Excel</strong>
          </button>

          <button type="button" data-bs-toggle="modal" data-bs-target="#modalDaftarPetugas"
                  class="btn btn-outline-info d-flex align-items-center gap-1">
            <i class="bi bi-people-fill"></i><strong>Lihat Daftar Petugas</strong>
          </button>
          
          <button type="button" data-bs-toggle="modal" data-bs-target="#modalHonorMitra"
                  class="btn btn-outline-warning d-flex align-items-center gap-1">
            <i class="bi bi-cash-coin"></i><strong>Lihat Honor Mitra</strong>
          </button>
          
          <a href="index.php?controller=petugas_kegiatan&action=downloadTemplate" 
            class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-download"></i><strong>Download Template</strong>
          </a>

          <button type="button" id="btnHapusMultiple" class="btn btn-outline-danger d-flex align-items-center gap-1" style="display: none;">
            <i class="bi bi-trash-fill"></i><strong>Hapus (<span id="selectedCount">0</span>)</strong>
          </button>
        </div>
      </div>

      <!-- Card Filter -->
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <form method="GET" action="index.php" id="filterForm">
            <input type="hidden" name="controller" value="petugas_kegiatan">
            <input type="hidden" name="action" value="index">
            
            <div class="filter-row">
              <!-- Filter Tim: tersedia untuk SEMUA roles -->
              <div>
                <label class="form-label small">Tim</label>
                <select name="team_id" id="filter_team_id" class="form-select form-select-sm">
                  <option value="">-- Semua Tim --</option>
                  <?php foreach($allTeams ?? [] as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (isset($_GET['team_id']) && $_GET['team_id'] == $t['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($t['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div>
                <label class="form-label small">Kegiatan</label>
                <select name="kegiatan_id" id="filter_kegiatan_id" class="form-select form-select-sm">
                  <option value="">-- Semua Kegiatan --</option>
                  <?php foreach($allKegiatans ?? [] as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= (isset($_GET['kegiatan_id']) && $_GET['kegiatan_id'] == $k['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($k['nama_kegiatan']) ?><?= isset($k['team_name']) ? ' ['.$k['team_name'].']' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div style="flex: 0 0 auto;">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-sm w-100">
                  <i class="bi bi-funnel-fill"></i> Filter
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

<?php if (!empty($_GET['kegiatan_id'])): 
    $selectedKegiatanId = (int)$_GET['kegiatan_id'];
    $selectedKegiatanName = '';
    foreach ($allKegiatans as $k) {
        if ($k['id'] == $selectedKegiatanId) {
            $selectedKegiatanName = $k['nama_kegiatan'];
            break;
        }
    }
?>
<!-- ========== CARD GENERATE & DOWNLOAD SEMUA DOKUMEN ========== -->
<div class="card shadow-sm mb-4 generate-all-card">
    <div class="card-header text-white">
        <h6 class="mb-0">
            <i class="bi bi-lightning-charge me-2"></i>Generate & Download Dokumen: <?= htmlspecialchars($selectedKegiatanName) ?>
        </h6>
    </div>
    <div class="card-body">
        <!-- Generate Semua Section -->
        <div class="mb-4">
            <h6 class="text-success"><i class="bi bi-file-earmark-plus me-1"></i> Generate Semua Dokumen</h6>
            <p class="small text-muted mb-2">
                Klik tombol untuk membuka form pengisian data dokumen. Isi semua data sekali lalu generate untuk semua petugas sekaligus.
            </p>
            
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-success btn-sm btn-open-bulk-generate" 
                        data-bs-toggle="modal" data-bs-target="#modalBulkGenerate"
                        data-jenis="spk" data-kegiatan-id="<?= $selectedKegiatanId ?>" data-kegiatan-name="<?= htmlspecialchars($selectedKegiatanName) ?>">
                    <i class="bi bi-file-text me-1"></i> Generate Semua SPK
                </button>
                <button type="button" class="btn btn-success btn-sm btn-open-bulk-generate" 
                        data-bs-toggle="modal" data-bs-target="#modalBulkGenerate"
                        data-jenis="bast" data-kegiatan-id="<?= $selectedKegiatanId ?>" data-kegiatan-name="<?= htmlspecialchars($selectedKegiatanName) ?>">
                    <i class="bi bi-file-check me-1"></i> Generate Semua BAST
                </button>
                <button type="button" class="btn btn-info btn-sm btn-open-bulk-generate" 
                        data-bs-toggle="modal" data-bs-target="#modalBulkGenerate"
                        data-jenis="surat_tugas" data-kegiatan-id="<?= $selectedKegiatanId ?>" data-kegiatan-name="<?= htmlspecialchars($selectedKegiatanName) ?>">
                    <i class="bi bi-file-person me-1"></i> Generate Semua Surat Tugas
                </button>
                <button type="button" class="btn btn-warning btn-sm btn-open-bulk-generate" 
                        data-bs-toggle="modal" data-bs-target="#modalBulkGenerate"
                        data-jenis="sppd" data-kegiatan-id="<?= $selectedKegiatanId ?>" data-kegiatan-name="<?= htmlspecialchars($selectedKegiatanName) ?>">
                    <i class="bi bi-envelope me-1"></i> Generate Semua SPD
                </button>
            </div>
        </div>
        
        <hr>
        
        <!-- Download Section -->
        <div>
            <h6 class="text-primary"><i class="bi bi-download me-1"></i> Download Dokumen</h6>
            <p class="small text-muted mb-2">
                Download dokumen yang sudah di-generate. Pilih format PDF (gabungan) atau Word (per-petugas).
            </p>
            
            <div class="d-flex flex-wrap gap-2">
                <!-- Download PDF -->
                <div class="btn-group">
                    <a href="index.php?controller=petugas_kegiatan&action=downloadAllDokumen&kegiatan_id=<?= $selectedKegiatanId ?>&format=pdf" 
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download Semua (PDF)
                    </a>
                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?controller=petugas_kegiatan&action=downloadDokumenByJenis&kegiatan_id=<?= $selectedKegiatanId ?>&jenis=spk&format=pdf">
                            <i class="bi bi-file-text text-primary me-2"></i>SPK (PDF)
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?controller=petugas_kegiatan&action=downloadDokumenByJenis&kegiatan_id=<?= $selectedKegiatanId ?>&jenis=bast&format=pdf">
                            <i class="bi bi-file-check text-success me-2"></i>BAST (PDF)
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?controller=petugas_kegiatan&action=downloadDokumenByJenis&kegiatan_id=<?= $selectedKegiatanId ?>&jenis=surat_tugas&format=pdf">
                            <i class="bi bi-file-person text-info me-2"></i>Surat Tugas (PDF)
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?controller=petugas_kegiatan&action=downloadDokumenByJenis&kegiatan_id=<?= $selectedKegiatanId ?>&jenis=sppd&format=pdf">
                            <i class="bi bi-envelope text-warning me-2"></i>SPD (PDF)
                        </a></li>
                    </ul>
                </div>
                
                <!-- Download Word -->
                <div class="btn-group">
                    <a href="index.php?controller=petugas_kegiatan&action=downloadAllDokumen&kegiatan_id=<?= $selectedKegiatanId ?>&format=docx" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark-word me-1"></i> Download Semua (Word)
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?controller=petugas_kegiatan&action=downloadDokumenByJenis&kegiatan_id=<?= $selectedKegiatanId ?>&jenis=spk&format=docx">
                            <i class="bi bi-file-text text-primary me-2"></i>SPK (Word)
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?controller=petugas_kegiatan&action=downloadDokumenByJenis&kegiatan_id=<?= $selectedKegiatanId ?>&jenis=bast&format=docx">
                            <i class="bi bi-file-check text-success me-2"></i>BAST (Word)
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?controller=petugas_kegiatan&action=downloadDokumenByJenis&kegiatan_id=<?= $selectedKegiatanId ?>&jenis=surat_tugas&format=docx">
                            <i class="bi bi-file-person text-info me-2"></i>Surat Tugas (Word)
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?controller=petugas_kegiatan&action=downloadDokumenByJenis&kegiatan_id=<?= $selectedKegiatanId ?>&jenis=sppd&format=docx">
                            <i class="bi bi-envelope text-warning me-2"></i>SPD (Word)
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="mt-3 small">
            <strong>Catatan:</strong>
            <ul class="mb-0">
                <li><strong>Generate Semua:</strong> Buka form, isi data bersama (nomor, tanggal, dll), lalu generate untuk semua petugas sekaligus</li>
                <li><strong>Download PDF:</strong> Gabungan semua dokumen dalam 1 file PDF per jenis</li>
                <li><strong>Download Word:</strong> File Word terpisah per petugas (dalam ZIP)</li>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

      <!-- Card Data Tabel -->
      <div class="card shadow-sm">
        <div class="card-body">
          <?php 
          $assignments = $assignments ?? [];
          if (empty($assignments)): ?>
            <div class="text-center py-5">
              <i class="bi bi-people display-1 text-muted"></i>
              <h5 class="mt-3 text-muted">Belum ada petugas yang ditugaskan</h5>
              <p class="text-muted">Klik "Tambah Petugas" atau "Import Excel"</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm" id="datatable">
                <thead class="table-light">
                  <tr>
                    <th width="30"><input class="form-check-input" type="checkbox" id="selectAll" autocomplete="off"></th>
                    <th>No</th>
                    <th>Kegiatan</th>
                    <th>Struktur</th>
                    <th class="sortable-column" data-sort="nama" style="cursor:pointer;">
                      Nama Petugas 
                      <i class="bi bi-arrow-down-up sort-icon text-muted ms-1"></i>
                    </th>
                    <th>Jenis</th>
                    <th>Peran</th>
                    <th class="sortable-column" data-sort="realisasi" style="cursor:pointer;">
                      Realisasi 
                      <i class="bi bi-arrow-down-up sort-icon text-muted ms-1"></i>
                    </th>
                    <th>Target</th>
                    <th>Progress</th>
                    <th>SPK</th>
                    <th>BAST</th>
                    <th>SURTUG</th>
                    <th>SPD</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no = 1; foreach ($assignments as $a):
                    $pct = ($a['target'] > 0) ? round(($a['realisasi'] / $a['target']) * 100, 2) : 0;
                    $isMitra = ($a['petugas_jenis'] ?? '') === 'Mitra';
                    $isPML = ($a['peran'] === 'PML');
                    $isPPL = ($a['peran'] === 'PPL');
                    // Kepala / Kasubbag dicatat sebagai 'Supervisi'. Tidak punya
                    // bawahan, tidak dihitung target/realisasi, tapi tetap dapat
                    // Surat Tugas + SPD.
                    $isSupervisi = ($a['peran'] === 'Supervisi');
                    
                    // Data dokumen dari import
                    $hasNoSpk = !empty($a['no_spk']);
                    $hasNoBast = !empty($a['no_bast']);
                    $hasNoSurtug = !empty($a['no_surat_tugas']);
                    $hasNoSppd = !empty($a['no_sppd']); // FIXED: gunakan no_sppd sesuai database
                    $hasTanggalSurat = !empty($a['tanggal_surat']);
                    $hasPeriodeMulai = !empty($a['periode_mulai']);
                    $hasPeriodeSelesai = !empty($a['periode_selesai']);
                    $hasAsal = !empty($a['asal']);
                    $hasTujuan = !empty($a['tujuan']);
                  ?>
                    <tr class="<?= $isPML ? 'table-warning pml-row' : ($isSupervisi ? 'table-info supervisi-row' : 'ppl-row') ?>"
                        data-peran="<?= $a['peran'] ?>"
                        data-pml-id="<?= $isPPL ? ($a['pml_id'] ?? '') : $a['id'] ?>"
                        data-row-id="<?= $a['id'] ?>"
                        data-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '') ?>"
                        data-realisasi="<?= $a['realisasi'] ?>"
                        data-kegiatan-id="<?= $a['kegiatan_detail_id'] ?>">
                      <td>
                        <input class="form-check-input row-checkbox" type="checkbox" 
                               value="<?= $a['id'] ?>" 
                               data-petugas-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '') ?>"
                               autocomplete="off">
                      </td>
                      <td><?= $no++ ?></td>
                      <td><small><?= htmlspecialchars($a['nama_kegiatan']) ?></small></td>
                      <td>
                        <?php if ($isPML): ?>
                          <span class="badge bg-warning text-dark"><i class="bi bi-person-badge"></i> PML</span>
                        <?php elseif ($isSupervisi): ?>
                          <span class="badge bg-primary"><i class="bi bi-shield-check"></i> Supervisi</span>
                        <?php elseif ($isPPL): ?>
                          <?php if (!empty($a['pml_nama'])): ?>
                            <small class="text-muted">└ PML: <strong><?= htmlspecialchars($a['pml_nama']) ?></strong></small>
                          <?php else: ?>
                            <small class="text-muted">-</small>
                          <?php endif; ?>
                        <?php else: ?>
                          <small class="text-muted">-</small>
                        <?php endif; ?>
                      </td>
                      <td><strong><?= htmlspecialchars($a['petugas_nama'] ?? '') ?></strong></td>
                      <td><span class="badge <?= $isMitra ? 'bg-info' : 'bg-primary' ?>"><?= $a['petugas_jenis'] ?></span></td>
                      <td>
                        <?php if ($isSupervisi): ?>
                          <span class="badge bg-primary">Supervisi</span>
                        <?php else: ?>
                          <span class="badge <?= $isPML ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $a['peran'] ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <?= $isSupervisi ? '<span class="text-muted">—</span>' : (int)$a['realisasi'] ?>
                      </td>
                      <td class="text-center">
                        <?= $isSupervisi ? '<span class="text-muted">—</span>' : (int)$a['target'] ?>
                      </td>
                      <td>
                        <?php if ($isSupervisi): ?>
                          <span class="text-muted">—</span>
                        <?php else: ?>
                          <div class="progress" style="height: 18px; min-width: 80px;">
                            <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : ($pct >= 50 ? 'bg-info' : 'bg-warning') ?>"
                                 style="width: <?= min($pct, 100) ?>%">
                              <?= $pct ?>%
                            </div>
                          </div>
                        <?php endif; ?>
                      </td>
                      
                      <!-- SPK -->
                      <td class="text-center">
                        <?php if ($isMitra): ?>
                          <?php if (!empty($a['spk_id'])): ?>
                            <span class="badge bg-success" title="SPK sudah ada">✓</span>
                          <?php else: ?>
                            <button type="button" class="btn btn-outline-primary btn-sm btn-generate-spk"
                                    data-bs-toggle="modal" data-bs-target="#modalGenerateSPK"
                                    data-id="<?= $a['id'] ?>"
                                    data-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '-', ENT_QUOTES) ?>"
                                    data-has-nomor="<?= $hasNoSpk ? '1' : '0' ?>"
                                    data-nomor="<?= htmlspecialchars($a['no_spk'] ?? '', ENT_QUOTES) ?>"
                                    data-has-tanggal="<?= $hasTanggalSurat ? '1' : '0' ?>"
                                    data-tanggal="<?= $a['tanggal_surat'] ?? '' ?>"
                                    data-has-periode-mulai="<?= $hasPeriodeMulai ? '1' : '0' ?>"
                                    data-periode-mulai="<?= $a['periode_mulai'] ?? '' ?>"
                                    data-has-periode-selesai="<?= $hasPeriodeSelesai ? '1' : '0' ?>"
                                    data-periode-selesai="<?= $a['periode_selesai'] ?? '' ?>"
                                    data-target="<?= (int)$a['target'] ?>"
                                    data-honor-satuan="<?= (int)($a['honor_satuan'] ?? 0) ?>">
                              <i class="bi bi-file-text"></i>
                            </button>
                          <?php endif; ?>
                        <?php else: ?>
                          <small class="text-muted">-</small>
                        <?php endif; ?>
                      </td>

                      <!-- BAST -->
                      <td class="text-center">
                        <?php if ($isMitra): ?>
                          <?php if (!empty($a['bast_id'])): ?>
                            <span class="badge bg-success" title="BAST sudah ada">✓</span>
                          <?php else: ?>
                            <button type="button" class="btn btn-outline-success btn-sm btn-generate-bast"
                                    data-bs-toggle="modal" data-bs-target="#modalGenerateBAST"
                                    data-id="<?= $a['id'] ?>"
                                    data-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '-', ENT_QUOTES) ?>"
                                    data-has-nomor="<?= $hasNoBast ? '1' : '0' ?>"
                                    data-nomor="<?= htmlspecialchars($a['no_bast'] ?? '', ENT_QUOTES) ?>"
                                    data-has-tanggal="<?= $hasTanggalSurat ? '1' : '0' ?>"
                                    data-tanggal="<?= $a['tanggal_surat'] ?? '' ?>"
                                    data-realisasi="<?= (int)$a['realisasi'] ?>">
                              <i class="bi bi-file-check"></i>
                            </button>
                          <?php endif; ?>
                        <?php else: ?>
                          <small class="text-muted">-</small>
                        <?php endif; ?>
                      </td>

                      <!-- SURTUG -->
                      <td class="text-center">
                        <?php if ($isMitra || $isPML || $isSupervisi): ?>
                          <?php if (!empty($a['surtug_id'])): ?>
                            <span class="badge bg-success" title="Surat Tugas sudah ada">✓</span>
                          <?php else: ?>
                            <button type="button" class="btn btn-info btn-sm btn-generate-surtug"
                                    data-bs-toggle="modal" data-bs-target="#modalGenerateSuratTugas"
                                    data-id="<?= $a['id'] ?>"
                                    data-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '-', ENT_QUOTES) ?>"
                                    data-has-nomor="<?= $hasNoSurtug ? '1' : '0' ?>"
                                    data-nomor="<?= htmlspecialchars($a['no_surat_tugas'] ?? '', ENT_QUOTES) ?>"
                                    data-has-tanggal="<?= $hasTanggalSurat ? '1' : '0' ?>"
                                    data-tanggal="<?= $a['tanggal_surat'] ?? '' ?>"
                                    data-has-periode-mulai="<?= $hasPeriodeMulai ? '1' : '0' ?>"
                                    data-periode-mulai="<?= $a['periode_mulai'] ?? '' ?>"
                                    data-has-periode-selesai="<?= $hasPeriodeSelesai ? '1' : '0' ?>"
                                    data-periode-selesai="<?= $a['periode_selesai'] ?? '' ?>">
                              <i class="bi bi-file-text"></i>
                            </button>
                          <?php endif; ?>
                        <?php else: ?>
                          <small class="text-muted">-</small>
                        <?php endif; ?>
                      </td>

                      <!-- SPD -->
                      <td class="text-center">
                        <?php if ($isMitra || $isPML || $isSupervisi): ?>
                          <?php if (!empty($a['sppd_id'])): ?>
                            <span class="badge bg-success" title="SPD sudah ada">✓</span>
                          <?php else: ?>
                            <button type="button" class="btn btn-warning btn-sm btn-generate-spd"
                                    data-bs-toggle="modal" data-bs-target="#modalGenerateSPD"
                                    data-id="<?= $a['id'] ?>"
                                    data-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '-', ENT_QUOTES) ?>"
                                    data-has-nomor="<?= $hasNoSppd ? '1' : '0' ?>"
                                    data-nomor="<?= htmlspecialchars($a['no_sppd'] ?? '', ENT_QUOTES) ?>"
                                    data-has-tanggal="<?= $hasTanggalSurat ? '1' : '0' ?>"
                                    data-tanggal="<?= $a['tanggal_surat'] ?? '' ?>"
                                    data-has-periode-mulai="<?= $hasPeriodeMulai ? '1' : '0' ?>"
                                    data-periode-mulai="<?= $a['periode_mulai'] ?? '' ?>"
                                    data-has-periode-selesai="<?= $hasPeriodeSelesai ? '1' : '0' ?>"
                                    data-periode-selesai="<?= $a['periode_selesai'] ?? '' ?>"
                                    data-has-asal="<?= $hasAsal ? '1' : '0' ?>"
                                    data-asal="<?= htmlspecialchars($a['asal'] ?? '', ENT_QUOTES) ?>"
                                    data-has-tujuan="<?= $hasTujuan ? '1' : '0' ?>"
                                    data-tujuan="<?= htmlspecialchars($a['tujuan'] ?? '', ENT_QUOTES) ?>">
                              <i class="bi bi-envelope"></i>
                            </button>
                          <?php endif; ?>
                        <?php else: ?>
                          <small class="text-muted">-</small>
                        <?php endif; ?>
                      </td>

                      <!-- Aksi -->
                      <td class="text-center">
                        <!-- Tombol Edit untuk semua peran -->
                        <button type="button" class="btn btn-warning btn-sm btn-edit-realisasi"
                                data-bs-toggle="modal" data-bs-target="#modalEditRealisasi"
                                data-kegiatan-petugas-id="<?= $a['id'] ?>"
                                data-petugas-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '') ?>"
                                data-nama-kegiatan="<?= htmlspecialchars($a['nama_kegiatan']) ?>"
                                data-peran="<?= $a['peran'] ?>"
                                data-target="<?= $a['target'] ?>"
                                data-realisasi="<?= $a['realisasi'] ?>"
                                data-petugas-source="<?= $a['petugas_source'] ?>"
                                data-petugas-id="<?= $a['petugas_id'] ?>"
                                data-honor-satuan="<?= $a['honor_satuan'] ?? 0 ?>"
                                data-is-mitra="<?= $isMitra ? '1' : '0' ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-delete"
                                data-delete-id="<?= $a['id'] ?>"
                                data-petugas-nama="<?= htmlspecialchars($a['petugas_nama'] ?? '') ?>">
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ========== MODAL TAMBAH PETUGAS (Alur PML-PPL) ========== -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formTambahPetugas" method="POST" action="index.php?controller=petugas_kegiatan&action=simpan">
        <input type="hidden" name="confirm_honor_exceed" id="confirm_honor_exceed" value="0">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="modalTambahLabel"><i class="bi bi-person-plus me-2"></i>Tambah Petugas Baru</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          
          <!-- Kegiatan -->
          <div class="mb-3">
            <label class="form-label">Kegiatan <span class="text-danger">*</span></label>
            <select name="kegiatan_detail_id" id="kegiatan_detail_id" class="form-select" required>
              <option value="">-- Pilih Kegiatan --</option>
              <?php 
              // Admin: semua kegiatan, Operator: sesuai tim
              $formKegiatans = $kegiatansForForm ?? $allKegiatans ?? [];
              foreach ($formKegiatans as $k): 
                $teamLabel = isset($k['team_name']) ? ' ['.$k['team_name'].']' : '';
                $honorSatuan = $k['honor_satuan'] ?? 0;
                $rentangMulai = $k['rentang_waktu_mulai'] ?? '';
              ?>
                <option value="<?= $k['id'] ?>" 
                        data-honor-satuan="<?= $honorSatuan ?>"
                        data-rentang-mulai="<?= $rentangMulai ?>"
                        <?= (isset($_GET['kegiatan_id']) && $_GET['kegiatan_id'] == $k['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($k['nama_kegiatan']) ?><?= $teamLabel ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">
              <?php if($_SESSION['user']['role_id'] == 1): ?>
                <i class="bi bi-info-circle"></i> Admin: Dapat memilih semua kegiatan dari semua tim
              <?php else: ?>
                <i class="bi bi-info-circle"></i> Operator: Hanya kegiatan dari tim yang Anda ikuti
              <?php endif; ?>
            </small>
          </div>

          <!-- ========== STEP 1: PILIH PML ========== -->
          <div class="alert alert-info">
            <strong><i class="bi bi-person-badge"></i> Step 1:</strong> Pilih PML (Pengawas/Pemeriksa Lapangan)
          </div>

          <div class="mb-3">
            <label class="form-label">Nama PML <span class="text-danger">*</span></label>
            <select name="pml_data" id="pml_data" class="form-select" required>
              <option value="">-- Pilih PML --</option>
              <?php if(isset($petugasOptions) && !empty($petugasOptions)): ?>
                <?php 
                $grouped = [];
                foreach ($petugasOptions as $p) {
                    $grouped[$p['source']][] = $p;
                }
                ?>
                <?php if(isset($grouped['users'])): ?>
                  <optgroup label="════ ANGGOTA TIM / PEGAWAI ════">
                    <?php foreach($grouped['users'] as $u):
                        $uRole = (int)($u['role_id'] ?? 0);
                        // Kepala/Kasubbag otomatis masuk alur Supervisi di JS,
                        // tapi label option tetap "Nama (email)" biar konsisten
                        // dengan peran lain — info Supervisi muncul di alert
                        // di bawah setelah dipilih.
                        // role_id baru: 2=Kepala, 3=Kasubbag (alur Supervisi)
                        $isSupervisi = in_array($uRole, [2, 3], true);
                    ?>
                      <option value="<?= $u['id'] ?>|users"
                              data-role-id="<?= $uRole ?>"
                              data-supervisi="<?= $isSupervisi ? '1' : '0' ?>">
                        <?= htmlspecialchars($u['nama']) ?> (<?= $u['identifier'] ?>)
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
                <?php if(isset($grouped['mitra'])): ?>
                  <optgroup label="════ MITRA ════">
                    <?php foreach($grouped['mitra'] as $m): ?>
                      <option value="<?= $m['id'] ?>|mitra">
                        <?= htmlspecialchars($m['nama']) ?> (<?= $m['identifier'] ?>)
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
              <?php endif; ?>
            </select>
            <input type="hidden" name="pml_id" id="pml_id">
            <input type="hidden" name="pml_source" id="pml_source">
            <input type="hidden" name="is_supervisi" id="is_supervisi" value="0">
          </div>

          <!-- Info Supervisi: muncul saat user terpilih ber-role Kepala/Kasubbag -->
          <div class="alert alert-primary d-none" id="supervisi_info">
            <strong><i class="bi bi-shield-check"></i> Mode Supervisi:</strong>
            Kepala/Kasubbag tidak punya bawahan (tanpa PPL) dan tidak dihitung
            target maupun realisasinya. Surat Tugas &amp; SPD tetap dapat digenerate.
          </div>

          <!-- Info Auto-Calculate -->
          <div class="alert alert-light border" id="auto_calc_info">
            <i class="bi bi-calculator"></i> <strong>Target & Realisasi PML</strong> akan dihitung otomatis dari total PPL di bawahnya.
          </div>
          <input type="hidden" name="pml_realisasi" value="0">
          <input type="hidden" name="pml_target" value="0">

          <!-- ========== STEP 2: JUMLAH PPL ========== -->
          <div class="alert alert-warning" id="step2_alert" style="display: none;">
            <strong><i class="bi bi-people"></i> Step 2:</strong> Berapa PPL yang dibawahi PML ini?
          </div>

          <div class="mb-3" id="jumlah_ppl_container" style="display: none;">
            <label class="form-label">Jumlah PPL <span class="text-danger">*</span></label>
            <input type="number" name="jumlah_ppl" id="jumlah_ppl" class="form-control" 
                   min="0" max="20" value="0" disabled>
            <small class="text-muted">
              <i class="bi bi-info-circle"></i> Masukkan jumlah PPL (Pencacah Lapangan) yang dibawahi PML ini
            </small>
          </div>

          <!-- ========== STEP 3: DYNAMIC PPL FORMS ========== -->
          <div id="ppl_forms_container" style="display: none;">
            <div class="alert alert-success">
              <strong><i class="bi bi-person-check"></i> Step 3:</strong> Pilih PPL yang dibawahi
            </div>
            <div id="ppl_forms"></div>
          </div>

          <!-- Keterangan -->
          <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="2" 
                      placeholder="Catatan tambahan untuk penugasan ini (opsional)"></textarea>
          </div>

          <!-- Info Data Dokumen -->
          <div class="alert alert-secondary">
            <i class="bi bi-info-circle"></i> <strong>Data Dokumen</strong> (No SPK, BAST, Surat Tugas, SPD, dll) 
            akan diisi saat <strong>Generate Dokumen</strong> per petugas atau bulk generate.
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Batal
          </button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Simpan Penugasan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========== MODAL EDIT TARGET & REALISASI ========== -->
<div class="modal fade" id="modalEditRealisasi" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Target & Realisasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="index.php?controller=petugas_kegiatan&action=update_realisasi" id="formEditRealisasi">
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <input type="hidden" id="edit_petugas_source">
          <input type="hidden" id="edit_petugas_id">
          <input type="hidden" id="edit_honor_satuan">
          <input type="hidden" id="edit_is_mitra">
          <div class="mb-3">
            <label class="form-label">Petugas</label>
            <input type="text" id="edit_nama_petugas" class="form-control-plaintext fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Kegiatan</label>
            <input type="text" id="edit_nama_kegiatan" class="form-control-plaintext" readonly>
          </div>
          <div class="row mb-3">
            <div class="col-6">
              <label class="form-label">Peran</label>
              <input type="text" id="edit_peran" class="form-control-plaintext" readonly>
            </div>
            <div class="col-6">
              <label class="form-label">Honor/Satuan</label>
              <input type="text" id="edit_honor_display" class="form-control-plaintext" readonly>
            </div>
          </div>
          <div class="row">
            <div class="col-6">
              <label class="form-label">Target <span class="text-danger">*</span></label>
              <input type="number" name="target" id="edit_target" class="form-control" min="0" required>
            </div>
            <div class="col-6">
              <label class="form-label">Realisasi <span class="text-danger">*</span></label>
              <input type="number" name="realisasi" id="edit_realisasi" class="form-control" min="0" required>
            </div>
          </div>
          <!-- Alert honor melebihi batas -->
          <div id="editHonorAlert" class="alert alert-warning mt-3" style="display: none;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span id="editHonorAlertText"></span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========== MODAL DAFTAR PETUGAS ========== -->
<div class="modal fade" id="modalDaftarPetugas" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="bi bi-people"></i> Daftar Petugas <span class="badge bg-light text-dark ms-2" id="totalPetugasCount">0</span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <input type="text" id="searchPetugas" class="form-control" placeholder="🔍 Cari nama...">
        </div>
        <div id="petugasLoading" class="text-center py-4" style="display: none;">
          <div class="spinner-border text-info"></div>
        </div>
        <div class="table-responsive" id="petugasTableContainer">
          <table class="table table-bordered table-sm">
            <thead class="table-light"><tr><th>No</th><th>Nama</th><th>Jenis</th><th>Username</th></tr></thead>
            <tbody id="petugasTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ========== MODAL HONOR MITRA ========== -->
<div class="modal fade" id="modalHonorMitra" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Rekapitulasi Honor Mitra</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Filter Bulan & Tahun -->
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <label class="form-label small">Bulan</label>
            <select id="filterHonorBulan" class="form-select form-select-sm">
              <option value="01" <?= date('m') == '01' ? 'selected' : '' ?>>Januari</option>
              <option value="02" <?= date('m') == '02' ? 'selected' : '' ?>>Februari</option>
              <option value="03" <?= date('m') == '03' ? 'selected' : '' ?>>Maret</option>
              <option value="04" <?= date('m') == '04' ? 'selected' : '' ?>>April</option>
              <option value="05" <?= date('m') == '05' ? 'selected' : '' ?>>Mei</option>
              <option value="06" <?= date('m') == '06' ? 'selected' : '' ?>>Juni</option>
              <option value="07" <?= date('m') == '07' ? 'selected' : '' ?>>Juli</option>
              <option value="08" <?= date('m') == '08' ? 'selected' : '' ?>>Agustus</option>
              <option value="09" <?= date('m') == '09' ? 'selected' : '' ?>>September</option>
              <option value="10" <?= date('m') == '10' ? 'selected' : '' ?>>Oktober</option>
              <option value="11" <?= date('m') == '11' ? 'selected' : '' ?>>November</option>
              <option value="12" <?= date('m') == '12' ? 'selected' : '' ?>>Desember</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small">Tahun</label>
            <select id="filterHonorTahun" class="form-select form-select-sm">
              <?php for($y = date('Y'); $y >= 2024; $y--): ?>
              <option value="<?= $y ?>" <?= date('Y') == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button type="button" id="btnFilterHonor" class="btn btn-warning btn-sm w-100">
              <i class="bi bi-funnel"></i> Filter
            </button>
          </div>
        </div>
        
        <!-- Loading -->
        <div id="honorLoading" class="text-center py-4" style="display: none;">
          <div class="spinner-border text-warning"></div>
          <p class="mt-2 text-muted">Memuat data honor...</p>
        </div>
        
        <!-- Tabel Honor -->
        <div class="table-responsive">
          <table class="table table-bordered table-sm table-hover">
            <thead class="table-warning">
              <tr>
                <th width="40">No</th>
                <th>Nama Mitra</th>
                <th class="text-end">Total Honor (Rp)</th>
                <th width="100">Status</th>
              </tr>
            </thead>
            <tbody id="honorTableBody">
              <tr><td colspan="4" class="text-center text-muted">Klik tombol Filter untuk memuat data</td></tr>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <th colspan="2" class="text-end">Total Keseluruhan:</th>
                <th class="text-end" id="totalHonorAll">Rp 0</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
        
        <div class="alert alert-info py-2 mt-2">
          <small>
            <i class="bi bi-info-circle"></i> 
            <strong>Keterangan Status:</strong><br>
            <span class="badge bg-success">Aman</span> = Honor &lt; Rp 3.000.000<br>
            <span class="badge bg-warning text-dark">Perhatian</span> = Honor Rp 3.000.000 - Rp 4.000.000<br>
            <span class="badge bg-danger">Melebihi</span> = Honor &gt; Rp 4.000.000<br>
            <span class="badge bg-dark">OB</span> = Mitra mengikuti kegiatan dengan satuan OB
          </small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ========== MODAL IMPORT EXCEL ========== -->
<div class="modal fade" id="modalImport" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="index.php?controller=petugas_kegiatan&action=import" enctype="multipart/form-data">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Import Excel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Kegiatan <span class="text-danger">*</span></label>
          <select name="kegiatan_id" class="form-select" required>
            <option value="">-- Pilih Kegiatan --</option>
            <?php foreach ($allKegiatans ?? [] as $k): ?>
              <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kegiatan']) ?><?= isset($k['team_name']) ? ' ['.$k['team_name'].']' : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">File Excel <span class="text-danger">*</span></label>
          <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
        </div>
        <div class="alert alert-info py-2">
          <small>
            <i class="bi bi-info-circle"></i> Format: Email Petugas, Peran (PML/PPL), Target, Realisasi, Asal, Tujuan, Alat Angkutan, No SPK, No BAST, No Surat Tugas, No SPD, Tanggal Surat, Periode Mulai, Periode Selesai, Keterangan
          </small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Import</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ========== MODAL GENERATE SPK ========== -->
<div class="modal fade" id="modalGenerateSPK" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <form class="modal-content" method="POST" action="index.php?controller=dokumen&action=generateSPKSnlik" id="formGenerateSPK">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-file-text"></i> Generate SPK</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- FIXED: Hidden field dengan name yang benar -->
        <input type="hidden" name="kegiatan_petugas_id" id="spk_kegiatan_petugas_id" value="">
        
        <div class="alert alert-info py-2 mb-3" id="spk_petugas_info">
          <i class="bi bi-person"></i> Petugas: <strong id="spk_petugas_nama">-</strong>
        </div>
        
        <div id="spk_info_import" class="conditional-field-info" style="display: none;">
          <i class="bi bi-info-circle"></i> Beberapa field sudah terisi dari import Excel.
        </div>

        <div class="mb-3" id="spk_nomor_container">
          <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
          <input type="text" name="nomor_surat" id="spk_nomor_surat" class="form-control" placeholder="130/.../SPK/..." required>
        </div>

        <div class="mb-3" id="spk_tanggal_container">
          <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
          <input type="date" name="tanggal_surat" id="spk_tanggal_surat" class="form-control" required>
        </div>

        <div class="row">
          <div class="col-md-6" id="spk_periode_mulai_container">
            <div class="mb-3">
              <label class="form-label">Periode Mulai <span class="text-danger">*</span></label>
              <input type="date" name="periode_mulai" id="spk_periode_mulai" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6" id="spk_periode_selesai_container">
            <div class="mb-3">
              <label class="form-label">Periode Selesai <span class="text-danger">*</span></label>
              <input type="date" name="periode_selesai" id="spk_periode_selesai" class="form-control" required>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Honorarium</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" id="spk_honorarium_display" class="form-control bg-light" readonly>
            <input type="hidden" name="honorarium" id="spk_honorarium">
          </div>
          <small class="text-muted">Dihitung otomatis dari target × honor per satuan kegiatan</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><i class="bi bi-file-text"></i> Generate</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ========== MODAL GENERATE BAST ========== -->
<div class="modal fade" id="modalGenerateBAST" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <form class="modal-content" method="POST" action="index.php?controller=dokumen&action=generateBAST" id="formGenerateBAST">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-file-check"></i> Generate BAST</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="kegiatan_petugas_id" id="bast_kegiatan_petugas_id" value="">
        
        <div class="alert alert-info py-2 mb-3">
          <i class="bi bi-person"></i> Petugas: <strong id="bast_petugas_nama">-</strong>
        </div>
        
        <div id="bast_info_import" class="conditional-field-info" style="display: none;">
          <i class="bi bi-info-circle"></i> Beberapa field sudah terisi dari import Excel.
        </div>

        <div class="row">
          <div class="col-md-6" id="bast_nomor_container">
            <div class="mb-3">
              <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
              <input type="text" name="nomor_surat" id="bast_nomor_surat" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6" id="bast_tanggal_container">
            <div class="mb-3">
              <label class="form-label">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_surat" id="bast_tanggal_surat" class="form-control" required>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Jumlah Realisasi</label>
          <input type="number" name="jumlah_realisasi" id="bast_realisasi" class="form-control bg-light" readonly>
          <small class="text-muted">Nilai realisasi diambil dari data yang sudah diinput sebelumnya.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success"><i class="bi bi-file-check"></i> Generate</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ========== MODAL GENERATE SURAT TUGAS ========== -->
<div class="modal fade" id="modalGenerateSuratTugas" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <form class="modal-content" method="POST" action="index.php?controller=dokumen&action=generateSuratTugas" id="formGenerateSurtug">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="bi bi-file-text"></i> Generate Surat Tugas</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="kegiatan_petugas_id" id="surtug_kegiatan_petugas_id" value="">
        
        <div class="alert alert-info py-2 mb-3">
          <i class="bi bi-person"></i> Petugas: <strong id="surtug_petugas_nama">-</strong>
        </div>
        
        <div id="surtug_info_import" class="conditional-field-info" style="display: none;">
          <i class="bi bi-info-circle"></i> Beberapa field sudah terisi dari import Excel.
        </div>

        <div class="row">
          <div class="col-md-6" id="surtug_nomor_container">
            <div class="mb-3">
              <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
              <input type="text" name="nomor_surat" id="surtug_nomor_surat" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6" id="surtug_tanggal_container">
            <div class="mb-3">
              <label class="form-label">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_surat" id="surtug_tanggal_surat" class="form-control" required>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6" id="surtug_periode_mulai_container">
            <div class="mb-3">
              <label class="form-label">Periode Mulai <span class="text-danger">*</span></label>
              <input type="date" name="periode_mulai" id="surtug_periode_mulai" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6" id="surtug_periode_selesai_container">
            <div class="mb-3">
              <label class="form-label">Periode Selesai <span class="text-danger">*</span></label>
              <input type="date" name="periode_selesai" id="surtug_periode_selesai" class="form-control" required>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-info"><i class="bi bi-file-text"></i> Generate</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ========== MODAL GENERATE SPD ========== -->
<div class="modal fade" id="modalGenerateSPD" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <form class="modal-content" method="POST" action="index.php?controller=dokumen&action=generateSPPD" id="formGenerateSPPD">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-envelope"></i> Generate SPD</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="kegiatan_petugas_id" id="spd_kegiatan_petugas_id" value="">
        
        <div class="alert alert-info py-2 mb-3">
          <i class="bi bi-person"></i> Petugas: <strong id="spd_petugas_nama">-</strong>
        </div>
        
        <div id="spd_info_import" class="conditional-field-info" style="display: none;">
          <i class="bi bi-info-circle"></i> Beberapa field sudah terisi dari import Excel.
        </div>

        <div class="row">
          <div class="col-md-6" id="spd_nomor_container">
            <div class="mb-3">
              <label class="form-label">Nomor SPD <span class="text-danger">*</span></label>
              <input type="text" name="nomor_surat" id="spd_nomor_surat" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6" id="spd_tanggal_container">
            <div class="mb-3">
              <label class="form-label">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_surat" id="spd_tanggal_surat" class="form-control" required>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6" id="spd_periode_mulai_container">
            <div class="mb-3">
              <label class="form-label">Periode Mulai <span class="text-danger">*</span></label>
              <input type="date" name="periode_mulai" id="spd_periode_mulai" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6" id="spd_periode_selesai_container">
            <div class="mb-3">
              <label class="form-label">Periode Selesai <span class="text-danger">*</span></label>
              <input type="date" name="periode_selesai" id="spd_periode_selesai" class="form-control" required>
            </div>
          </div>
        </div>

        <div class="mb-3" id="spd_asal_container">
          <label class="form-label">Asal <span class="text-danger">*</span></label>
          <input type="text" name="asal" id="spd_asal" class="form-control" placeholder="Contoh: Nabire" required>
        </div>

        <div class="mb-3" id="spd_tujuan_container">
          <label class="form-label">Tujuan <span class="text-danger">*</span></label>
          <div id="tujuan_list">
            <div class="input-group mb-2">
              <input type="text" name="tujuan[]" class="form-control" placeholder="Tujuan 1" required>
              <button type="button" class="btn btn-outline-success btn-add-tujuan"><i class="bi bi-plus"></i></button>
            </div>
          </div>
          <small class="text-muted">Klik + untuk menambah tujuan lebih dari 1</small>
        </div>

        <div class="form-check form-switch border-top pt-3">
          <input class="form-check-input" type="checkbox" id="spd_gabung_surtug">
          <label class="form-check-label" for="spd_gabung_surtug">
            <i class="bi bi-file-earmark-plus me-1"></i>
            <strong>Gabung dengan Surat Tugas</strong> (format Sept 2022)
          </label>
          <div><small class="text-muted">Jika dicentang, output berupa 1 file docx berisi Surat Tugas + SPD + Lembar Belakang.</small></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-warning"><i class="bi bi-envelope"></i> Generate</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ========== MODAL GENERATE SEMUA (LAMA) ========== -->
<div class="modal fade" id="modalGenerateAll" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-lightning-charge"></i> Generate Semua <span id="generateAllJenisLabel"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="generateAllLoading" class="text-center py-4">
          <div class="spinner-border text-success"></div>
          <p class="mt-2">Memvalidasi data...</p>
        </div>
        
        <div id="generateAllResult" style="display: none;">
          <div class="alert alert-info">
            <div class="row">
              <div class="col-4 text-center">
                <h4 id="generateAllTotal">0</h4>
                <small>Total Mitra</small>
              </div>
              <div class="col-4 text-center">
                <h4 class="text-success" id="generateAllValid">0</h4>
                <small>Data Lengkap</small>
              </div>
              <div class="col-4 text-center">
                <h4 class="text-danger" id="generateAllError">0</h4>
                <small>Data Tidak Lengkap</small>
              </div>
            </div>
          </div>
          
          <div id="generateAllErrors" style="display: none;">
            <h6 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Petugas dengan Data Tidak Lengkap:</h6>
            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
              <table class="table table-sm table-bordered">
                <thead class="table-light">
                  <tr><th>Nama</th><th>Kekurangan Data</th></tr>
                </thead>
                <tbody id="generateAllErrorList"></tbody>
              </table>
            </div>
          </div>
          
          <div id="generateAllSuccess" style="display: none;">
            <div class="alert alert-success">
              <i class="bi bi-check-circle"></i> Semua data sudah lengkap dan siap di-generate!
            </div>
          </div>
          
          <div class="mb-3" id="generateAllHonorariumSection" style="display: none;">
            <label class="form-label">Honorarium (untuk SPK) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" id="generateAllHonorarium" class="form-control" value="400000">
            </div>
            <small class="text-muted">Honorarium akan sama untuk semua petugas</small>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <form method="POST" action="index.php?controller=dokumen&action=generateBulk" id="formGenerateBulk">
          <input type="hidden" name="kegiatan_id" id="generateAllKegiatanId">
          <input type="hidden" name="jenis_dokumen" id="generateAllJenis">
          <input type="hidden" name="honorarium" id="generateAllHonorariumHidden">
          <button type="submit" class="btn btn-success" id="btnGenerateAllSubmit" disabled>
            <i class="bi bi-lightning-charge"></i> Generate <span id="generateAllValidCount">0</span> Dokumen
          </button>
        </form>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ========== MODAL BULK GENERATE DENGAN TABEL ========== -->
<div class="modal fade" id="modalBulkGenerate" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-lightning-charge"></i> Bulk Generate <span id="bulkJenisLabel">SPK</span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="index.php?controller=dokumen&action=bulkGenerateWithTable" id="formBulkGenerateTable">
        <div class="modal-body">
          <input type="hidden" name="kegiatan_id" id="bulk_kegiatan_id">
          <input type="hidden" name="jenis_dokumen" id="bulk_jenis_dokumen">
          
          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> <strong>Kegiatan:</strong> <span id="bulkKegiatanName">-</span>
          </div>
          
          <!-- Data Bersama - HANYA TANGGAL SURAT -->
          <div class="card mb-3">
            <div class="card-header bg-light">
              <strong><i class="bi bi-gear"></i> Data Bersama (Berlaku untuk Semua Petugas)</strong>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="mb-0">
                    <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_surat" id="bulk_tanggal_surat" class="form-control" required>
                  </div>
                </div>
                <!-- SPD: Asal & Alat Angkutan bersama -->
                <div class="col-md-4" id="bulk_spd_asal_field" style="display: none;">
                  <div class="mb-0">
                    <label class="form-label">Tempat Asal <span class="text-danger">*</span></label>
                    <input type="text" name="asal" id="bulk_asal" class="form-control" value="Enarotali">
                  </div>
                </div>
                <div class="col-md-4" id="bulk_spd_alat_field" style="display: none;">
                  <div class="mb-0">
                    <label class="form-label">Alat Angkutan</label>
                    <select name="alat_angkutan" id="bulk_alat_angkutan" class="form-select">
                      <option value="Kendaraan Umum">Kendaraan Umum</option>
                      <option value="Kendaraan Dinas">Kendaraan Dinas</option>
                      <option value="Pesawat Udara">Pesawat Udara</option>
                      <option value="Jonson">Jonson</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Daftar Petugas -->
          <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <strong><i class="bi bi-people"></i> Daftar Petugas (<span id="bulkPetugasCount">0</span> petugas)</strong>
              <div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllBulk">
                  <i class="bi bi-check-all"></i> Pilih Semua
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAllBulk">
                  <i class="bi bi-x"></i> Batal Pilih
                </button>
              </div>
            </div>
            <div class="card-body p-0">
              <div id="bulkLoadingPetugas" class="text-center py-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Memuat daftar petugas...</p>
              </div>
              <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-hover table-sm mb-0" id="tableBulkPetugas">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th width="40"><input type="checkbox" id="bulkSelectAll" class="form-check-input"></th>
                      <th>No</th>
                      <th>Nama Petugas</th>
                      <th>Peran</th>
                      <th>Nomor Surat <span class="text-danger">*</span></th>
                      <th id="thPeriodeMulai">Periode Mulai</th>
                      <th id="thPeriodeSelesai">Periode Selesai</th>
                      <th id="thHonorarium" style="display:none;">Honorarium</th>
                      <th id="thTujuan" style="display:none;">Tujuan</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyBulkPetugas">
                    <!-- Dynamic content -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          
          <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong> Pastikan semua data sudah diisi untuk setiap petugas yang dipilih.
          </div>
        </div>
        <div class="modal-footer">
          <span class="me-auto text-muted">
            <strong id="bulkSelectedCount">0</strong> petugas dipilih
          </span>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success" id="btnSubmitBulkGenerate">
            <i class="bi bi-lightning-charge"></i> Generate Dokumen
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========== SCRIPTS ========== -->
<!-- CRITICAL: Self-contained modal handler (runs before jQuery, isolated from other errors) -->
<script>
(function() {
    'use strict';
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModalHandlers);
    } else {
        initModalHandlers();
    }
    
    function initModalHandlers() {
        console.log('[Modal Handler] Initializing...');
        
        // Use event delegation on document.body for maximum reliability
        document.body.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-generate-spk, .btn-generate-bast, .btn-generate-surtug, .btn-generate-spd');
            if (!btn) return;
            
            try {
                // Get data using getAttribute (more reliable than dataset for dynamically rendered content)
                var id = btn.getAttribute('data-id');
                var nama = btn.getAttribute('data-nama') || '-';
                
                console.log('[Modal Handler] Button clicked:', btn.className, 'ID:', id, 'Nama:', nama);
                
                // SPK Handler
                if (btn.classList.contains('btn-generate-spk')) {
                    setField('spk_kegiatan_petugas_id', id);
                    setText('spk_petugas_nama', nama);
                    setField('spk_nomor_surat', btn.getAttribute('data-has-nomor') === '1' ? btn.getAttribute('data-nomor') : '');
                    setField('spk_tanggal_surat', btn.getAttribute('data-has-tanggal') === '1' ? btn.getAttribute('data-tanggal') : '');
                    setField('spk_periode_mulai', btn.getAttribute('data-has-periode-mulai') === '1' ? btn.getAttribute('data-periode-mulai') : '');
                    setField('spk_periode_selesai', btn.getAttribute('data-has-periode-selesai') === '1' ? btn.getAttribute('data-periode-selesai') : '');
                    
                    // Kalkulasi honorarium otomatis: target × honor_satuan
                    var target = parseInt(btn.getAttribute('data-target')) || 0;
                    var honorSatuan = parseInt(btn.getAttribute('data-honor-satuan')) || 0;
                    var honorarium = target * honorSatuan;
                    
                    setField('spk_honorarium', honorarium);
                    var displayEl = document.getElementById('spk_honorarium_display');
                    if (displayEl) {
                        displayEl.value = formatRupiah(honorarium);
                    }
                    
                    toggleInfo('spk_info_import', btn.getAttribute('data-has-nomor') === '1' || btn.getAttribute('data-has-tanggal') === '1');
                }
                
                // BAST Handler
                if (btn.classList.contains('btn-generate-bast')) {
                    setField('bast_kegiatan_petugas_id', id);
                    setText('bast_petugas_nama', nama);
                    setField('bast_nomor_surat', btn.getAttribute('data-has-nomor') === '1' ? btn.getAttribute('data-nomor') : '');
                    setField('bast_tanggal_surat', btn.getAttribute('data-has-tanggal') === '1' ? btn.getAttribute('data-tanggal') : '');
                    setField('bast_realisasi', btn.getAttribute('data-realisasi') || '0');
                    toggleInfo('bast_info_import', btn.getAttribute('data-has-nomor') === '1' || btn.getAttribute('data-has-tanggal') === '1');
                }
                
                // SURTUG Handler
                if (btn.classList.contains('btn-generate-surtug')) {
                    setField('surtug_kegiatan_petugas_id', id);
                    setText('surtug_petugas_nama', nama);
                    setField('surtug_nomor_surat', btn.getAttribute('data-has-nomor') === '1' ? btn.getAttribute('data-nomor') : '');
                    setField('surtug_tanggal_surat', btn.getAttribute('data-has-tanggal') === '1' ? btn.getAttribute('data-tanggal') : '');
                    setField('surtug_periode_mulai', btn.getAttribute('data-has-periode-mulai') === '1' ? btn.getAttribute('data-periode-mulai') : '');
                    setField('surtug_periode_selesai', btn.getAttribute('data-has-periode-selesai') === '1' ? btn.getAttribute('data-periode-selesai') : '');
                    toggleInfo('surtug_info_import', btn.getAttribute('data-has-nomor') === '1' || btn.getAttribute('data-has-tanggal') === '1');
                }
                
                // SPD Handler
                if (btn.classList.contains('btn-generate-spd')) {
                    setField('spd_kegiatan_petugas_id', id);
                    setText('spd_petugas_nama', nama);
                    setField('spd_nomor_surat', btn.getAttribute('data-has-nomor') === '1' ? btn.getAttribute('data-nomor') : '');
                    setField('spd_tanggal_surat', btn.getAttribute('data-has-tanggal') === '1' ? btn.getAttribute('data-tanggal') : '');
                    setField('spd_periode_mulai', btn.getAttribute('data-has-periode-mulai') === '1' ? btn.getAttribute('data-periode-mulai') : '');
                    setField('spd_periode_selesai', btn.getAttribute('data-has-periode-selesai') === '1' ? btn.getAttribute('data-periode-selesai') : '');
                    setField('spd_asal', btn.getAttribute('data-has-asal') === '1' ? btn.getAttribute('data-asal') : '');
                    
                    var tujuanList = document.getElementById('tujuan_list');
                    if (tujuanList) {
                        var tujuanVal = btn.getAttribute('data-has-tujuan') === '1' ? (btn.getAttribute('data-tujuan') || '') : '';
                        tujuanList.innerHTML = '<div class="input-group mb-2"><input type="text" name="tujuan[]" class="form-control" placeholder="Tujuan 1" value="' + escapeHtml(tujuanVal) + '" required><button type="button" class="btn btn-outline-success btn-add-tujuan"><i class="bi bi-plus"></i></button></div>';
                    }
                    toggleInfo('spd_info_import', btn.getAttribute('data-has-nomor') === '1' || btn.getAttribute('data-has-tanggal') === '1');
                }
                
            } catch(err) {
                console.error('[Modal Handler] Error:', err);
            }
        });
        
        console.log('[Modal Handler] Ready!');
    }
    
    // Helper functions
    function setField(id, value) {
        var el = document.getElementById(id);
        if (el) el.value = value || '';
    }
    
    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '-';
    }
    
    function toggleInfo(id, show) {
        var el = document.getElementById(id);
        if (el) el.style.display = show ? 'block' : 'none';
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    
    function formatRupiah(num) {
        if (!num) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
})();
</script>

<!-- CRITICAL: Bulk Generate Handler (isolated, runs after jQuery loads) -->
<script>
window.initBulkGenerateHandler = function() {
    console.log('[Bulk Handler] Initializing...');
    
    var currentJenis = '';
    var petugasData = [];
    
    // Handler untuk tombol bulk generate
    document.body.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-open-bulk-generate');
        if (!btn) return;
        
        try {
            var jenis = btn.getAttribute('data-jenis');
            var kegiatanId = btn.getAttribute('data-kegiatan-id');
            var kegiatanName = btn.getAttribute('data-kegiatan-name') || '-';
            
            console.log('[Bulk Handler] Clicked:', {jenis: jenis, kegiatanId: kegiatanId, kegiatanName: kegiatanName});
            
            currentJenis = jenis;
            
            // Set hidden fields
            var bulkKegiatanIdEl = document.getElementById('bulk_kegiatan_id');
            var bulkJenisEl = document.getElementById('bulk_jenis_dokumen');
            var bulkKegiatanNameEl = document.getElementById('bulkKegiatanName');
            var bulkJenisLabelEl = document.getElementById('bulkJenisLabel');
            
            if (bulkKegiatanIdEl) bulkKegiatanIdEl.value = kegiatanId || '';
            if (bulkJenisEl) bulkJenisEl.value = jenis || '';
            if (bulkKegiatanNameEl) bulkKegiatanNameEl.textContent = kegiatanName;
            
            var jenisLabels = {
                'spk': 'SPK',
                'bast': 'BAST',
                'surat_tugas': 'Surat Tugas',
                'sppd': 'SPD'
            };
            if (bulkJenisLabelEl) bulkJenisLabelEl.textContent = jenisLabels[jenis] || jenis;
            
            // Show/hide fields based on jenis
            toggleElement('bulk_spd_asal_field', jenis === 'sppd');
            toggleElement('bulk_spd_alat_field', jenis === 'sppd');
            toggleElement('thHonorarium', jenis === 'spk');
            toggleElement('thTujuan', jenis === 'sppd');
            toggleElement('thPeriodeMulai', jenis !== 'bast');
            toggleElement('thPeriodeSelesai', jenis !== 'bast');
            
            // Load petugas via AJAX
            if (kegiatanId && kegiatanId !== '') {
                loadPetugasForBulkNative(kegiatanId, jenis);
            } else {
                var loadingEl = document.getElementById('bulkLoadingPetugas');
                var tbodyEl = document.getElementById('tbodyBulkPetugas');
                if (loadingEl) loadingEl.style.display = 'none';
                if (tbodyEl) tbodyEl.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Kegiatan tidak dipilih. Pilih kegiatan di filter terlebih dahulu.</td></tr>';
            }
            
        } catch(err) {
            console.error('[Bulk Handler] Error:', err);
        }
    });
    
    function toggleElement(id, show) {
        var el = document.getElementById(id);
        if (el) el.style.display = show ? '' : 'none';
    }
    
    function loadPetugasForBulkNative(kegiatanId, jenis) {
        var loadingEl = document.getElementById('bulkLoadingPetugas');
        var tbodyEl = document.getElementById('tbodyBulkPetugas');
        var countEl = document.getElementById('bulkPetugasCount');
        
        if (loadingEl) loadingEl.style.display = 'block';
        if (tbodyEl) tbodyEl.innerHTML = '';
        
        console.log('[Bulk Handler] Loading petugas:', kegiatanId, jenis);
        
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'index.php?controller=petugas_kegiatan&action=getPetugasForBulk&kegiatan_id=' + encodeURIComponent(kegiatanId) + '&jenis=' + encodeURIComponent(jenis), true);
        xhr.setRequestHeader('Accept', 'application/json');
        
        xhr.onload = function() {
            if (loadingEl) loadingEl.style.display = 'none';
            
            console.log('[Bulk Handler] Response status:', xhr.status);
            console.log('[Bulk Handler] Response text:', xhr.responseText.substring(0, 500));
            
            try {
                var res = JSON.parse(xhr.responseText);
                
                if (res.success && res.data && res.data.length > 0) {
                    petugasData = res.data;
                    var html = '';
                    var no = 1;
                    
                    res.data.forEach(function(p) {
                        var hasDoc = false;
                        if (jenis === 'spk' && p.spk_id) hasDoc = true;
                        if (jenis === 'bast' && p.bast_id) hasDoc = true;
                        if (jenis === 'surat_tugas' && p.surtug_id) hasDoc = true;
                        if (jenis === 'sppd' && p.sppd_id) hasDoc = true;
                        
                        var statusBadge = hasDoc 
                            ? '<span class="badge bg-success">Sudah Ada</span>' 
                            : '<span class="badge bg-warning">Belum</span>';
                        
                        html += '<tr class="' + (hasDoc ? 'table-success' : '') + '">';
                        html += '<td><input type="checkbox" name="petugas_ids[]" value="' + p.id + '" class="form-check-input bulk-checkbox" ' + (hasDoc ? 'disabled' : '') + '></td>';
                        html += '<td>' + no++ + '</td>';
                        html += '<td><strong>' + (p.petugas_nama || '-') + '</strong></td>';
                        html += '<td><span class="badge ' + (p.peran === 'PML' ? 'bg-warning text-dark' : 'bg-secondary') + '">' + (p.peran || '-') + '</span></td>';
                        html += '<td><input type="text" name="nomor_surat[' + p.id + ']" class="form-control form-control-sm" placeholder="Nomor" ' + (hasDoc ? 'disabled' : '') + ' value="' + (p.no_spk || p.no_bast || p.no_surat_tugas || p.no_sppd || '') + '"></td>';
                        
                        if (jenis !== 'bast') {
                            html += '<td><input type="date" name="periode_mulai[' + p.id + ']" class="form-control form-control-sm" ' + (hasDoc ? 'disabled' : '') + ' value="' + (p.periode_mulai || '') + '"></td>';
                            html += '<td><input type="date" name="periode_selesai[' + p.id + ']" class="form-control form-control-sm" ' + (hasDoc ? 'disabled' : '') + ' value="' + (p.periode_selesai || '') + '"></td>';
                        }
                        
                        if (jenis === 'spk') {
                            html += '<td><input type="number" name="honorarium[' + p.id + ']" class="form-control form-control-sm" placeholder="Rp" ' + (hasDoc ? 'disabled' : '') + ' value="400000"></td>';
                        }
                        
                        if (jenis === 'sppd') {
                            html += '<td><input type="text" name="tujuan[' + p.id + ']" class="form-control form-control-sm" placeholder="Tujuan" ' + (hasDoc ? 'disabled' : '') + ' value="' + (p.tujuan || '') + '"></td>';
                        }
                        
                        html += '<td>' + statusBadge + '</td>';
                        html += '</tr>';
                    });
                    
                    if (tbodyEl) tbodyEl.innerHTML = html;
                    if (countEl) countEl.textContent = res.data.length;
                    
                    // Bind checkbox events after data loaded
                    bindBulkCheckboxEvents();
                    
                } else {
                    if (tbodyEl) tbodyEl.innerHTML = '<tr><td colspan="10" class="text-center text-muted">Tidak ada petugas untuk kegiatan ini</td></tr>';
                    if (countEl) countEl.textContent = '0';
                }
            } catch(e) {
                console.error('[Bulk Handler] Parse error:', e);
                if (tbodyEl) tbodyEl.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Gagal memproses data: ' + e.message + '</td></tr>';
            }
        };
        
        xhr.onerror = function() {
            if (loadingEl) loadingEl.style.display = 'none';
            console.error('[Bulk Handler] XHR Error');
            if (tbodyEl) tbodyEl.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Gagal memuat data dari server</td></tr>';
        };
        
        xhr.send();
    }
    
    console.log('[Bulk Handler] Ready!');
    
    // Function to bind checkbox events
    function bindBulkCheckboxEvents() {
        var checkboxes = document.querySelectorAll('.bulk-checkbox');
        var selectAllBtn = document.getElementById('btnSelectAllBulk');
        var selectAllCheckbox = document.getElementById('bulkSelectAll');
        var deselectAllBtn = document.getElementById('btnDeselectAllBulk');
        
        // Update count function
        function updateCount() {
            var checkedCount = document.querySelectorAll('.bulk-checkbox:checked').length;
            var countEl = document.getElementById('bulkSelectedCount');
            if (countEl) countEl.textContent = checkedCount;
            console.log('[Bulk Handler] Selected count:', checkedCount);
        }
        
        // Bind change event to each checkbox
        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', updateCount);
        });
        
        // Select all button
        if (selectAllBtn) {
            selectAllBtn.onclick = function() {
                checkboxes.forEach(function(cb) {
                    if (!cb.disabled) cb.checked = true;
                });
                updateCount();
            };
        }
        
        // Select all header checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.onclick = function() {
                checkboxes.forEach(function(cb) {
                    if (!cb.disabled) cb.checked = this.checked;
                }.bind(this));
                updateCount();
            };
        }
        
        // Deselect all button
        if (deselectAllBtn) {
            deselectAllBtn.onclick = function() {
                checkboxes.forEach(function(cb) {
                    cb.checked = false;
                });
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                updateCount();
            };
        }
        
        // Initial count
        updateCount();
    }
};

// Run after jQuery loads
if (typeof jQuery !== 'undefined') {
    window.initBulkGenerateHandler();
} else {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(window.initBulkGenerateHandler, 100);
    });
}
</script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
// ========== INLINE HELPER FUNCTIONS (tidak terpengaruh error lain) ==========
function setSPKData(id, nama, hasNomor, nomor, hasTanggal, tanggal, hasPeriodeMulai, periodeMulai, hasPeriodeSelesai, periodeSelesai) {
    console.log('setSPKData called:', id, nama);
    document.getElementById('spk_kegiatan_petugas_id').value = id;
    document.getElementById('spk_petugas_nama').textContent = nama || '-';
    document.getElementById('spk_nomor_surat').value = (hasNomor == '1' || hasNomor == 1) ? (nomor || '') : '';
    document.getElementById('spk_tanggal_surat').value = (hasTanggal == '1' || hasTanggal == 1) ? (tanggal || '') : '';
    document.getElementById('spk_periode_mulai').value = (hasPeriodeMulai == '1' || hasPeriodeMulai == 1) ? (periodeMulai || '') : '';
    document.getElementById('spk_periode_selesai').value = (hasPeriodeSelesai == '1' || hasPeriodeSelesai == 1) ? (periodeSelesai || '') : '';
    document.getElementById('spk_honorarium').value = '';
    var infoImport = document.getElementById('spk_info_import');
    if (infoImport) infoImport.style.display = (hasNomor == '1' || hasTanggal == '1' || hasPeriodeMulai == '1' || hasPeriodeSelesai == '1') ? 'block' : 'none';
}

function setBASTData(id, nama, hasNomor, nomor, hasTanggal, tanggal, realisasi) {
    console.log('setBASTData called:', id, nama);
    document.getElementById('bast_kegiatan_petugas_id').value = id;
    document.getElementById('bast_petugas_nama').textContent = nama || '-';
    document.getElementById('bast_nomor_surat').value = (hasNomor == '1' || hasNomor == 1) ? (nomor || '') : '';
    document.getElementById('bast_tanggal_surat').value = (hasTanggal == '1' || hasTanggal == 1) ? (tanggal || '') : '';
    document.getElementById('bast_realisasi').value = realisasi || 0;
    var infoImport = document.getElementById('bast_info_import');
    if (infoImport) infoImport.style.display = (hasNomor == '1' || hasTanggal == '1') ? 'block' : 'none';
}

function setSurtugData(id, nama, hasNomor, nomor, hasTanggal, tanggal, hasPeriodeMulai, periodeMulai, hasPeriodeSelesai, periodeSelesai) {
    console.log('setSurtugData called:', id, nama);
    document.getElementById('surtug_kegiatan_petugas_id').value = id;
    document.getElementById('surtug_petugas_nama').textContent = nama || '-';
    document.getElementById('surtug_nomor_surat').value = (hasNomor == '1' || hasNomor == 1) ? (nomor || '') : '';
    document.getElementById('surtug_tanggal_surat').value = (hasTanggal == '1' || hasTanggal == 1) ? (tanggal || '') : '';
    document.getElementById('surtug_periode_mulai').value = (hasPeriodeMulai == '1' || hasPeriodeMulai == 1) ? (periodeMulai || '') : '';
    document.getElementById('surtug_periode_selesai').value = (hasPeriodeSelesai == '1' || hasPeriodeSelesai == 1) ? (periodeSelesai || '') : '';
    var infoImport = document.getElementById('surtug_info_import');
    if (infoImport) infoImport.style.display = (hasNomor == '1' || hasTanggal == '1' || hasPeriodeMulai == '1' || hasPeriodeSelesai == '1') ? 'block' : 'none';
}

function setSPDData(id, nama, hasNomor, nomor, hasTanggal, tanggal, hasPeriodeMulai, periodeMulai, hasPeriodeSelesai, periodeSelesai, hasAsal, asal, hasTujuan, tujuan) {
    console.log('setSPDData called:', id, nama);
    document.getElementById('spd_kegiatan_petugas_id').value = id;
    document.getElementById('spd_petugas_nama').textContent = nama || '-';
    document.getElementById('spd_nomor_surat').value = (hasNomor == '1' || hasNomor == 1) ? (nomor || '') : '';
    document.getElementById('spd_tanggal_surat').value = (hasTanggal == '1' || hasTanggal == 1) ? (tanggal || '') : '';
    document.getElementById('spd_periode_mulai').value = (hasPeriodeMulai == '1' || hasPeriodeMulai == 1) ? (periodeMulai || '') : '';
    document.getElementById('spd_periode_selesai').value = (hasPeriodeSelesai == '1' || hasPeriodeSelesai == 1) ? (periodeSelesai || '') : '';
    document.getElementById('spd_asal').value = (hasAsal == '1' || hasAsal == 1) ? (asal || '') : '';
    var tujuanList = document.getElementById('tujuan_list');
    var tujuanVal = (hasTujuan == '1' || hasTujuan == 1) ? (tujuan || '') : '';
    tujuanList.innerHTML = '<div class="input-group mb-2"><input type="text" name="tujuan[]" class="form-control" placeholder="Tujuan 1" value="' + tujuanVal + '" required><button type="button" class="btn btn-outline-success btn-add-tujuan"><i class="bi bi-plus"></i></button></div>';
    var infoImport = document.getElementById('spd_info_import');
    if (infoImport) infoImport.style.display = (hasNomor == '1' || hasTanggal == '1' || hasPeriodeMulai == '1' || hasPeriodeSelesai == '1' || hasAsal == '1' || hasTujuan == '1') ? 'block' : 'none';
}

$(document).ready(function() {
    // ========== EVENT HANDLERS UNTUK TOMBOL GENERATE (menggunakan data-attributes) ==========
    $(document).on('click', '.btn-generate-spk', function() {
        var btn = $(this);
        setSPKData(
            btn.data('id'),
            btn.data('nama'),
            btn.data('has-nomor'),
            btn.data('nomor'),
            btn.data('has-tanggal'),
            btn.data('tanggal'),
            btn.data('has-periode-mulai'),
            btn.data('periode-mulai'),
            btn.data('has-periode-selesai'),
            btn.data('periode-selesai')
        );
    });
    
    $(document).on('click', '.btn-generate-bast', function() {
        var btn = $(this);
        setBASTData(
            btn.data('id'),
            btn.data('nama'),
            btn.data('has-nomor'),
            btn.data('nomor'),
            btn.data('has-tanggal'),
            btn.data('tanggal'),
            btn.data('realisasi')
        );
    });
    
    $(document).on('click', '.btn-generate-surtug', function() {
        var btn = $(this);
        setSurtugData(
            btn.data('id'),
            btn.data('nama'),
            btn.data('has-nomor'),
            btn.data('nomor'),
            btn.data('has-tanggal'),
            btn.data('tanggal'),
            btn.data('has-periode-mulai'),
            btn.data('periode-mulai'),
            btn.data('has-periode-selesai'),
            btn.data('periode-selesai')
        );
    });
    
    $(document).on('click', '.btn-generate-spd', function() {
        var btn = $(this);
        // Reset checkbox "gabung dengan surat tugas" setiap kali modal dibuka
        $('#spd_gabung_surtug').prop('checked', false);
        setSPDData(
            btn.data('id'),
            btn.data('nama'),
            btn.data('has-nomor'),
            btn.data('nomor'),
            btn.data('has-tanggal'),
            btn.data('tanggal'),
            btn.data('has-periode-mulai'),
            btn.data('periode-mulai'),
            btn.data('has-periode-selesai'),
            btn.data('periode-selesai'),
            btn.data('has-asal'),
            btn.data('asal'),
            btn.data('has-tujuan'),
            btn.data('tujuan')
        );
    });

    // Swap action form saat checkbox "Gabung Surat Tugas" aktif
    $(document).on('submit', '#formGenerateSPPD', function () {
        var url = 'index.php?controller=dokumen&action=' +
            ($('#spd_gabung_surtug').is(':checked') ? 'generateSuratTugasSPD' : 'generateSPPD');
        $(this).attr('action', url);
    });

    // DataTable - Nonaktifkan sorting default (kita pakai custom sorting untuk PML-PPL)
    var dataTable = null;
    if ($('#datatable tbody tr').length > 0) {
        dataTable = $('#datatable').DataTable({
            pageLength: 10,
            ordering: false, // Nonaktifkan sorting default karena kita pakai custom sorting
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_",
                info: "_START_-_END_ dari _TOTAL_",
                paginate: { previous: "‹", next: "›" }
            },
            columnDefs: [
                { orderable: false, targets: '_all' }
            ]
        });
    }
    
    // ========== CUSTOM SORTING (hanya PML yang diurutkan, PPL mengikuti) ==========
    var sortState = { nama: null, realisasi: null };
    
    $(document).on('click', '.sortable-column', function() {
        var sortType = $(this).data('sort');
        var icon = $(this).find('.sort-icon');
        
        // Toggle sort direction
        if (sortState[sortType] === null || sortState[sortType] === 'desc') {
            sortState[sortType] = 'asc';
            icon.removeClass('bi-arrow-down-up bi-arrow-down').addClass('bi-arrow-up');
        } else {
            sortState[sortType] = 'desc';
            icon.removeClass('bi-arrow-down-up bi-arrow-up').addClass('bi-arrow-down');
        }
        
        // Reset other sort icons
        $('.sortable-column').not(this).find('.sort-icon')
            .removeClass('bi-arrow-up bi-arrow-down').addClass('bi-arrow-down-up');
        
        // Reset other sort states
        for (var key in sortState) {
            if (key !== sortType) sortState[key] = null;
        }
        
        customSortPML(sortType, sortState[sortType]);
    });
    
    function customSortPML(sortType, direction) {
        var tbody = $('#datatable tbody');
        var rows = tbody.find('tr').toArray();
        
        // Grup berdasarkan kegiatan dan PML
        // Key = kegiatan_id + pml_id
        var groups = {};
        
        rows.forEach(function(row) {
            var $row = $(row);
            var peran = $row.data('peran');
            var kegiatanId = $row.data('kegiatan-id');
            var pmlId = $row.data('pml-id');
            var rowId = $row.data('row-id');
            
            // Key untuk grouping: kegiatan + PML
            var key = kegiatanId + '_' + (peran === 'PML' ? rowId : pmlId);
            
            if (!groups[key]) {
                groups[key] = { pml: null, ppls: [], sortValue: null, kegiatanId: kegiatanId };
            }
            
            if (peran === 'PML') {
                groups[key].pml = row;
                // Set nilai untuk sorting berdasarkan PML
                if (sortType === 'nama') {
                    groups[key].sortValue = ($row.data('nama') || '').toString().toLowerCase();
                } else if (sortType === 'realisasi') {
                    groups[key].sortValue = parseFloat($row.data('realisasi')) || 0;
                }
            } else {
                groups[key].ppls.push(row);
            }
        });
        
        // Convert to array dan sort berdasarkan nilai PML
        var sortedGroups = Object.values(groups).sort(function(a, b) {
            // Jika tidak ada PML, taruh di akhir
            if (a.sortValue === null) return 1;
            if (b.sortValue === null) return -1;
            
            var result;
            if (direction === 'asc') {
                if (sortType === 'nama') {
                    result = a.sortValue.localeCompare(b.sortValue);
                } else {
                    result = a.sortValue - b.sortValue;
                }
            } else {
                if (sortType === 'nama') {
                    result = b.sortValue.localeCompare(a.sortValue);
                } else {
                    result = b.sortValue - a.sortValue;
                }
            }
            return result;
        });
        
        // Rebuild tbody - PML diikuti oleh PPL-nya
        tbody.empty();
        var no = 1;
        sortedGroups.forEach(function(group) {
            // Tampilkan PML dulu
            if (group.pml) {
                $(group.pml).find('td:eq(1)').text(no++);
                tbody.append(group.pml);
            }
            // Kemudian PPL-nya
            group.ppls.forEach(function(ppl) {
                $(ppl).find('td:eq(1)').text(no++);
                tbody.append(ppl);
            });
        });
        
        console.log('[Custom Sort] Sorted by', sortType, direction);
    }

    // Petugas select handler
    $('#petugas_select').on('change', function() {
        var val = $(this).val();
        if (val) {
            var parts = val.split('|');
            $('#petugas_id').val(parts[0]);
            $('#petugas_source').val(parts[1]);
        } else {
            $('#petugas_id, #petugas_source').val('');
        }
    });

    // Kegiatan select handler - Load PML
    $('#kegiatan_select').on('change', function() {
        var kegiatanId = $(this).val();
        var pmlSelect = $('#pml_select');
        pmlSelect.html('<option value="">-- Loading... --</option>').prop('disabled', true);
        
        if (kegiatanId) {
            $.get('index.php?controller=petugas_kegiatan&action=getPML&kegiatan_id=' + kegiatanId, function(res) {
                pmlSelect.html('<option value="">-- Pilih PML (Opsional) --</option>');
                if (res.success && res.data.length > 0) {
                    res.data.forEach(function(pml) {
                        pmlSelect.append('<option value="' + pml.id + '">' + pml.pml_nama + ' (Target: ' + pml.pml_target + ')</option>');
                    });
                }
                pmlSelect.prop('disabled', false);
            });
        }
    });

    // Peran select handler
    $('#peran_select').on('change', function() {
        var isPML = $(this).val() === 'PML';
        if (isPML) {
            $('#step2_alert, #jumlah_ppl_container').show();
            $('#jumlah_ppl').prop('disabled', false);
        } else {
            $('#step2_alert, #jumlah_ppl_container, #ppl_forms_container').hide();
            $('#jumlah_ppl').prop('disabled', true).val(0);
            $('#ppl_forms').empty();
        }
    });
    
    // Jumlah PPL Handler
    $('#jumlah_ppl').on('change input', function() {
        var count = parseInt($(this).val()) || 0;
        var container = $('#ppl_forms');
        container.empty();
        
        if (count > 0 && count <= 20) {
            $('#ppl_forms_container').show();
            
            // Build options HTML dari PHP
            var pplOptionsHtml = '';
            <?php foreach($petugasOptions ?? [] as $p): ?>
            pplOptionsHtml += '<option value="<?= $p['id'] ?>|<?= $p['source'] ?>"><?= addslashes(htmlspecialchars($p['label'] ?? '', ENT_QUOTES)) ?></option>';
            <?php endforeach; ?>
            
            for (var i = 1; i <= count; i++) {
                var html = '<div class="card mb-2 border-primary ppl-card">' +
                    '<div class="card-header bg-primary text-white py-1">PPL #' + i + '</div>' +
                    '<div class="card-body py-2">' +
                        '<div class="row g-2">' +
                            '<div class="col-md-6">' +
                                '<select name="ppl_data[]" class="form-select form-select-sm ppl-select" required>' +
                                    '<option value="">-- Pilih PPL --</option>' +
                                    pplOptionsHtml +
                                '</select>' +
                                '<input type="hidden" name="ppl_id[]" class="ppl-id">' +
                                '<input type="hidden" name="ppl_source[]" class="ppl-source">' +
                            '</div>' +
                            '<div class="col-md-3">' +
                                '<input type="number" name="ppl_target[]" class="form-control form-control-sm" min="1" value="1" placeholder="Target" required>' +
                            '</div>' +
                            '<div class="col-md-3">' +
                                '<input type="number" name="ppl_realisasi[]" class="form-control form-control-sm" min="0" value="0" placeholder="Realisasi">' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
                container.append(html);
            }
            
            $('.ppl-select').on('change', function() {
                var val = $(this).val();
                var card = $(this).closest('.card-body');
                if (val) {
                    var parts = val.split('|');
                    card.find('.ppl-id').val(parts[0]);
                    card.find('.ppl-source').val(parts[1]);
                }
            });
        } else {
            $('#ppl_forms_container').hide();
        }
    });
    
    // Edit Target & Realisasi - dengan validasi honor untuk mitra
    $(document).on('click', '.btn-edit-realisasi', function() {
        var btn = $(this);
        $('#edit_id').val(btn.data('kegiatan-petugas-id'));
        $('#edit_nama_petugas').val(btn.data('petugas-nama'));
        $('#edit_nama_kegiatan').val(btn.data('nama-kegiatan'));
        $('#edit_peran').val(btn.data('peran'));
        $('#edit_target').val(btn.data('target'));
        $('#edit_realisasi').val(btn.data('realisasi'));
        $('#edit_petugas_source').val(btn.data('petugas-source'));
        $('#edit_petugas_id').val(btn.data('petugas-id'));
        $('#edit_honor_satuan').val(btn.data('honor-satuan'));
        $('#edit_is_mitra').val(btn.data('is-mitra'));
        
        // Tampilkan honor/satuan
        var honorSatuan = parseFloat(btn.data('honor-satuan')) || 0;
        if (honorSatuan > 0) {
            $('#edit_honor_display').val('Rp ' + formatNumber(honorSatuan));
        } else {
            $('#edit_honor_display').val('-');
        }
        
        // Reset alert
        $('#editHonorAlert').hide();
        
        // Validasi awal
        checkHonorLimit();
    });
    
    // Validasi honor saat target berubah
    $('#edit_target').on('input change', function() {
        checkHonorLimit();
    });
    
    function checkHonorLimit() {
        var isMitra = $('#edit_is_mitra').val() === '1';
        if (!isMitra) {
            $('#editHonorAlert').hide();
            return;
        }
        
        var target = parseInt($('#edit_target').val()) || 0;
        var honorSatuan = parseFloat($('#edit_honor_satuan').val()) || 0;
        var totalHonor = target * honorSatuan;
        
        if (totalHonor > 4000000) {
            var petugasNama = $('#edit_nama_petugas').val();
            $('#editHonorAlertText').html('Honor untuk <strong>' + petugasNama + '</strong> dengan target ' + target + 
                ' x Rp ' + formatNumber(honorSatuan) + ' = <strong>Rp ' + formatNumber(totalHonor) + '</strong> melebihi Rp 4.000.000.');
            $('#editHonorAlert').show();
        } else {
            $('#editHonorAlert').hide();
        }
    }
    
    // Submit form edit dengan konfirmasi jika melebihi batas honor
    $('#formEditRealisasi').on('submit', function(e) {
        var isMitra = $('#edit_is_mitra').val() === '1';
        var target = parseInt($('#edit_target').val()) || 0;
        var honorSatuan = parseFloat($('#edit_honor_satuan').val()) || 0;
        var totalHonor = target * honorSatuan;
        var petugasNama = $('#edit_nama_petugas').val();
        
        if (isMitra && totalHonor > 4000000) {
            e.preventDefault();
            if (confirm('Apakah anda yakin untuk nama petugas berikut:\n\n' + petugasNama + '\n\nHonor: Rp ' + formatNumber(totalHonor) + ' melebihi rate honor Rp 4.000.000?')) {
                // Jika yakin, submit form
                this.submit();
            }
        }
    });
    
    // Delete
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('delete-id');
        var nama = $(this).data('petugas-nama');
        if (confirm('Hapus penugasan: ' + nama + '?')) {
            window.location.href = 'index.php?controller=petugas_kegiatan&action=delete&id=' + id;
        }
    });
    
    // Add more tujuan
    $(document).on('click', '.btn-add-tujuan', function() {
        var count = $('#tujuan_list .input-group').length + 1;
        $('#tujuan_list').append(
            '<div class="input-group mb-2">' +
                '<input type="text" name="tujuan[]" class="form-control" placeholder="Tujuan ' + count + '">' +
                '<button type="button" class="btn btn-outline-danger btn-remove-tujuan"><i class="bi bi-dash"></i></button>' +
            '</div>'
        );
    });
    
    $(document).on('click', '.btn-remove-tujuan', function() {
        $(this).closest('.input-group').remove();
    });
    
    // ========== GENERATE ALL HANDLER ==========
    $(document).on('click', '.btn-generate-all', function() {
        var jenis = $(this).data('jenis');
        var kegiatanId = $(this).data('kegiatan-id');
        
        var jenisLabels = {
            'spk': 'SPK',
            'bast': 'BAST',
            'surat_tugas': 'Surat Tugas',
            'sppd': 'SPD'
        };
        
        $('#generateAllJenisLabel').text(jenisLabels[jenis] || jenis);
        $('#generateAllKegiatanId').val(kegiatanId);
        $('#generateAllJenis').val(jenis);
        
        // Show/hide honorarium section
        $('#generateAllHonorariumSection').toggle(jenis === 'spk');
        
        // Reset UI
        $('#generateAllLoading').show();
        $('#generateAllResult').hide();
        $('#generateAllErrors').hide();
        $('#generateAllSuccess').hide();
        $('#btnGenerateAllSubmit').prop('disabled', true);
        
        // Open modal
        var modal = new bootstrap.Modal(document.getElementById('modalGenerateAll'));
        modal.show();
        
        // Fetch validation data
        $.get('index.php?controller=petugas_kegiatan&action=validateForBulkGenerate&kegiatan_id=' + kegiatanId + '&jenis=' + jenis, function(res) {
            $('#generateAllLoading').hide();
            $('#generateAllResult').show();
            
            if (res.success) {
                var data = res.data;
                $('#generateAllTotal').text(data.total);
                $('#generateAllValid').text(data.valid_count);
                $('#generateAllError').text(data.error_count);
                $('#generateAllValidCount').text(data.valid_count);
                
                if (data.error_count > 0) {
                    var errorHtml = '';
                    for (var id in data.errors) {
                        var err = data.errors[id];
                        errorHtml += '<tr><td>' + err.nama + '</td><td class="text-danger small">' + err.errors.join(', ') + '</td></tr>';
                    }
                    $('#generateAllErrorList').html(errorHtml);
                    $('#generateAllErrors').show();
                }
                
                if (data.valid_count > 0) {
                    $('#generateAllSuccess').show();
                    $('#btnGenerateAllSubmit').prop('disabled', false);
                }
            } else {
                alert('Error: ' + (res.error || 'Gagal memvalidasi data'));
            }
        }).fail(function() {
            $('#generateAllLoading').hide();
            alert('Error: Gagal mengambil data dari server');
        });
    });
    
    // Form Generate Bulk submit
    $('#formGenerateBulk').on('submit', function() {
        var jenis = $('#generateAllJenis').val();
        if (jenis === 'spk') {
            var honorarium = $('#generateAllHonorarium').val();
            $('#generateAllHonorariumHidden').val(honorarium);
        }
    });
    
    // ========== SELECT ALL & DELETE MULTIPLE ==========
    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', $(this).is(':checked'));
        updateDeleteButton();
    });
    
    $(document).on('change', '.row-checkbox', function() {
        updateDeleteButton();
    });
    
    function updateDeleteButton() {
        var checked = $('.row-checkbox:checked').length;
        console.log('Checked count:', checked); // Debug
        if (checked > 0) {
            $('#btnHapusMultiple').show();
            $('#selectedCount').text(checked);
        } else {
            $('#btnHapusMultiple').hide();
            $('#selectedCount').text('0');
            $('#selectAll').prop('checked', false);
        }
    }
    
    // PENTING: Reset semua checkbox dan counter saat page load
    $(document).ready(function() {
        // Uncheck semua checkbox
        $('.row-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        // Reset counter dan hide button
        $('#selectedCount').text('0');
        $('#btnHapusMultiple').hide();
    });
    
    // Hapus Multiple
    $('#btnHapusMultiple').on('click', function() {
        var ids = [];
        var names = [];
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
            names.push($(this).data('petugas-nama') || 'Unknown');
        });
        
        console.log('IDs to delete:', ids); // Debug
        
        // Validasi ada data yang dipilih
        if (ids.length === 0) {
            alert('Tidak ada data yang dipilih. Silakan centang checkbox terlebih dahulu.');
            return;
        }
        
        if (confirm('Hapus ' + ids.length + ' penugasan?\n\n' + names.slice(0, 5).join('\n') + (names.length > 5 ? '\n...dan lainnya' : ''))) {
            var form = $('<form method="POST" action="index.php?controller=petugas_kegiatan&action=hapusMultiple"></form>');
            ids.forEach(function(id) {
                form.append('<input type="hidden" name="ids[]" value="' + id + '">');
            });
            $('body').append(form);
            form.submit();
        }
    });
    
    // ========== FORM TAMBAH PETUGAS (Standard submit, not AJAX) ==========
    // Form sekarang submit secara normal ke action=simpan
    
    // ========== MODAL DAFTAR PETUGAS ==========
    $('#modalDaftarPetugas').on('show.bs.modal', function() {
        $('#petugasLoading').show();
        $('#petugasTableContainer').hide();
        
        $.get('index.php?controller=petugas_kegiatan&action=getDaftarPetugas', function(res) {
            $('#petugasLoading').hide();
            $('#petugasTableContainer').show();
            
            if (res.success) {
                var html = '';
                res.data.forEach(function(p, i) {
                    html += '<tr><td>' + (i+1) + '</td><td>' + p.nama + '</td><td>' + 
                            '<span class="badge ' + (p.jenis === 'Mitra' ? 'bg-info' : 'bg-primary') + '">' + p.jenis + '</span>' +
                            '</td><td>' + (p.username || '-') + '</td></tr>';
                });
                $('#petugasTableBody').html(html);
                $('#totalPetugasCount').text(res.data.length);
            }
        });
    });
    
    $('#searchPetugas').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#petugasTableBody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // ========== PML-PPL FORM HANDLER ==========
    var pmlDropdown = document.getElementById('pml_data');
    var pmlIdField = document.getElementById('pml_id');
    var pmlSourceField = document.getElementById('pml_source');
    var jumlahPPLContainer = document.getElementById('jumlah_ppl_container');
    var jumlahPPLInput = document.getElementById('jumlah_ppl');
    var pplFormsContainer = document.getElementById('ppl_forms_container');
    var pplForms = document.getElementById('ppl_forms');
    var step2Alert = document.getElementById('step2_alert');
    var modalTambah = document.getElementById('modalTambah');
    var formTambah = document.getElementById('formTambahPetugas');
    
    // Handler: PML dropdown change
    var supervisiInfo = document.getElementById('supervisi_info');
    var autoCalcInfo  = document.getElementById('auto_calc_info');
    var isSupervisiField = document.getElementById('is_supervisi');
    if (pmlDropdown) {
        pmlDropdown.addEventListener('change', function() {
            var value = this.value;
            var selected = this.options[this.selectedIndex];
            var isSupervisi = selected && selected.getAttribute('data-supervisi') === '1';

            if (value && value.includes('|')) {
                var parts = value.split('|');
                pmlIdField.value = parts[0];
                pmlSourceField.value = parts[1];

                if (isSupervisi) {
                    // Alur Supervisi: tanpa PPL, tanpa target/realisasi.
                    isSupervisiField.value = '1';
                    supervisiInfo.classList.remove('d-none');
                    if (autoCalcInfo) autoCalcInfo.classList.add('d-none');
                    step2Alert.style.display = 'none';
                    jumlahPPLContainer.style.display = 'none';
                    jumlahPPLInput.disabled = true;
                    jumlahPPLInput.value = '0';
                    pplFormsContainer.style.display = 'none';
                    pplForms.innerHTML = '';
                } else {
                    isSupervisiField.value = '0';
                    supervisiInfo.classList.add('d-none');
                    if (autoCalcInfo) autoCalcInfo.classList.remove('d-none');
                    step2Alert.style.display = 'block';
                    jumlahPPLContainer.style.display = 'block';
                    jumlahPPLInput.disabled = false;
                }
            } else {
                pmlIdField.value = '';
                pmlSourceField.value = '';
                isSupervisiField.value = '0';
                supervisiInfo.classList.add('d-none');
                if (autoCalcInfo) autoCalcInfo.classList.remove('d-none');
                step2Alert.style.display = 'none';
                jumlahPPLContainer.style.display = 'none';
                jumlahPPLInput.disabled = true;
                jumlahPPLInput.value = '0';
                pplFormsContainer.style.display = 'none';
                pplForms.innerHTML = '';
            }
        });
    }
    
    // Handler: Jumlah PPL change
    if (jumlahPPLInput) {
        jumlahPPLInput.addEventListener('input', function() {
            var jumlah = parseInt(this.value) || 0;
            console.log('Jumlah PPL:', jumlah);
            
            if (jumlah > 0 && jumlah <= 20) {
                pplFormsContainer.style.display = 'block';
                generatePPLForms(jumlah);
            } else {
                pplFormsContainer.style.display = 'none';
                pplForms.innerHTML = '';
            }
        });
        
        jumlahPPLInput.addEventListener('change', function() {
            var jumlah = parseInt(this.value) || 0;
            if (jumlah > 0 && jumlah <= 20) {
                pplFormsContainer.style.display = 'block';
                generatePPLForms(jumlah);
            } else {
                pplFormsContainer.style.display = 'none';
                pplForms.innerHTML = '';
            }
        });
    }
    
    // Function: Generate PPL Forms
    function generatePPLForms(jumlah) {
        pplForms.innerHTML = '';
        
        // Clone PML dropdown options (hanya MITRA untuk PPL)
        var pmlDropdownClone = pmlDropdown.cloneNode(true);
        var usersOptgroup = pmlDropdownClone.querySelector('optgroup[label*="ANGGOTA TIM"]');
        if (usersOptgroup) {
            usersOptgroup.remove();
        }
        var pplDropdownHTML = pmlDropdownClone.innerHTML;
        
        for (var i = 1; i <= jumlah; i++) {
            var pplFormHTML = '<div class="card mb-3 border-primary ppl-card">' +
                '<div class="card-header bg-primary text-white py-2">' +
                    '<strong>PPL #' + i + '</strong>' +
                '</div>' +
                '<div class="card-body">' +
                    '<div class="mb-3">' +
                        '<label class="form-label">Nama PPL #' + i + ' <span class="text-danger">*</span></label>' +
                        '<select class="form-select ppl-dropdown" data-index="' + i + '" required>' +
                            '<option value="">-- Pilih PPL #' + i + ' --</option>' +
                            pplDropdownHTML +
                        '</select>' +
                        '<input type="hidden" name="ppl_id[]" class="ppl-id-' + i + '">' +
                        '<input type="hidden" name="ppl_source[]" class="ppl-source-' + i + '">' +
                    '</div>' +
                    '<div class="row">' +
                        '<div class="col-md-6">' +
                            '<label class="form-label">Realisasi</label>' +
                            '<input type="number" name="ppl_realisasi[]" class="form-control" value="0" min="0">' +
                        '</div>' +
                        '<div class="col-md-6">' +
                            '<label class="form-label">Target <span class="text-danger">*</span></label>' +
                            '<input type="number" name="ppl_target[]" class="form-control" value="1" min="1" required>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
            pplForms.insertAdjacentHTML('beforeend', pplFormHTML);
        }
        
        // Attach handlers
        attachPPLDropdownHandlers();
    }
    
    // Function: Attach PPL dropdown handlers
    function attachPPLDropdownHandlers() {
        var pplDropdowns = document.querySelectorAll('.ppl-dropdown');
        pplDropdowns.forEach(function(dropdown) {
            dropdown.addEventListener('change', function() {
                var index = this.dataset.index;
                var value = this.value;
                
                if (value && value.includes('|')) {
                    var parts = value.split('|');
                    document.querySelector('.ppl-id-' + index).value = parts[0];
                    document.querySelector('.ppl-source-' + index).value = parts[1];
                } else {
                    document.querySelector('.ppl-id-' + index).value = '';
                    document.querySelector('.ppl-source-' + index).value = '';
                }
            });
        });
    }
    
    // Handler: Form submit validation
    if (formTambah) {
        formTambah.addEventListener('submit', function(e) {
            var kegiatanId = document.getElementById('kegiatan_detail_id').value;
            var pmlId = pmlIdField.value;
            var pmlSource = pmlSourceField.value;
            var jumlahPPL = parseInt(jumlahPPLInput.value) || 0;
            
            // Validasi kegiatan
            if (!kegiatanId) {
                e.preventDefault();
                alert('❌ Kegiatan belum dipilih!');
                document.getElementById('kegiatan_detail_id').focus();
                return false;
            }
            
            // Validasi PML
            if (!pmlId || !pmlSource) {
                e.preventDefault();
                alert('❌ PML belum dipilih!');
                pmlDropdown.focus();
                return false;
            }
            
            // Validasi PPL (jika ada)
            if (jumlahPPL > 0) {
                var pplIds = document.querySelectorAll('input[name="ppl_id[]"]');
                var allFilled = true;
                
                pplIds.forEach(function(input) {
                    if (!input.value) {
                        allFilled = false;
                    }
                });
                
                if (!allFilled) {
                    e.preventDefault();
                    alert('❌ Semua PPL harus dipilih!');
                    return false;
                }
            }
            
            return true;
        });
    }
    
    // Handler: Reset modal on close
    if (modalTambah) {
        modalTambah.addEventListener('hidden.bs.modal', function() {
            if (formTambah) formTambah.reset();
            if (pmlIdField) pmlIdField.value = '';
            if (pmlSourceField) pmlSourceField.value = '';
            if (jumlahPPLInput) {
                jumlahPPLInput.disabled = true;
                jumlahPPLInput.value = '0';
            }
            if (jumlahPPLContainer) jumlahPPLContainer.style.display = 'none';
            if (step2Alert) step2Alert.style.display = 'none';
            if (pplFormsContainer) pplFormsContainer.style.display = 'none';
            if (pplForms) pplForms.innerHTML = '';
        });
    }
});

// ========== FILTER TIM: UPDATE DROPDOWN KEGIATAN (TANPA AUTO SUBMIT) ==========
$(document).ready(function() {
    // Ketika tim berubah: update dropdown kegiatan SAJA (tidak auto submit)
    $('#filter_team_id').on('change', function() {
        var teamId = $(this).val();
        var $kegiatanDropdown = $('#filter_kegiatan_id');
        
        // Reset kegiatan dropdown
        $kegiatanDropdown.html('<option value="">Memuat...</option>');
        $kegiatanDropdown.prop('disabled', true);
        
        // Update dropdown kegiatan di filter berdasarkan tim
        $.get('index.php?controller=petugas_kegiatan&action=getKegiatanByTeam&team_id=' + teamId, function(res) {
            if (res.success) {
                var filterKegiatanHtml = '<option value="">-- Semua Kegiatan --</option>';
                
                res.data.forEach(function(k) {
                    var teamLabel = k.team_name ? ' [' + k.team_name + ']' : '';
                    filterKegiatanHtml += '<option value="' + k.id + '">' + k.nama_kegiatan + teamLabel + '</option>';
                });
                
                // Update filter dropdown
                $kegiatanDropdown.html(filterKegiatanHtml);
                $kegiatanDropdown.prop('disabled', false);
            }
        }).fail(function() {
            $kegiatanDropdown.html('<option value="">-- Semua Kegiatan --</option>');
            $kegiatanDropdown.prop('disabled', false);
        });
    });
});

// ========== BULK GENERATE DENGAN TABEL ==========
$(document).ready(function() {
    var currentJenis = '';
    var petugasData = [];
    
    // Handler: Buka modal bulk generate (dengan event delegation + attr)
    $(document).on('click', '.btn-open-bulk-generate', function() {
        var $btn = $(this);
        // Gunakan attr() bukan data() untuk menghindari caching issues
        var jenis = $btn.attr('data-jenis');
        var kegiatanId = $btn.attr('data-kegiatan-id');
        var kegiatanName = $btn.attr('data-kegiatan-name') || '-';
        
        console.log('Bulk Generate clicked:', {jenis: jenis, kegiatanId: kegiatanId, kegiatanName: kegiatanName});
        
        currentJenis = jenis;
        
        // Set hidden fields
        $('#bulk_kegiatan_id').val(kegiatanId);
        $('#bulk_jenis_dokumen').val(jenis);
        $('#bulkKegiatanName').text(kegiatanName);
        
        // Set label
        var jenisLabels = {
            'spk': 'SPK',
            'bast': 'BAST',
            'surat_tugas': 'Surat Tugas',
            'sppd': 'SPD'
        };
        $('#bulkJenisLabel').text(jenisLabels[jenis] || jenis.toUpperCase());
        
        // Show/hide fields & columns based on jenis
        $('#bulk_spd_asal_field').toggle(jenis === 'sppd');
        $('#bulk_spd_alat_field').toggle(jenis === 'sppd');
        $('#thHonorarium').toggle(jenis === 'spk');
        $('#thTujuan').toggle(jenis === 'sppd');
        // Periode selalu tampil kecuali BAST
        $('#thPeriodeMulai').toggle(jenis !== 'bast');
        $('#thPeriodeSelesai').toggle(jenis !== 'bast');
        
        // Load petugas
        if (kegiatanId && kegiatanId !== '') {
            loadPetugasForBulk(kegiatanId, jenis);
        } else {
            $('#bulkLoadingPetugas').hide();
            $('#tbodyBulkPetugas').html('<tr><td colspan="10" class="text-center text-danger">Kegiatan tidak dipilih. Pilih kegiatan di filter terlebih dahulu.</td></tr>');
        }
    });
    
    // Function: Load petugas untuk bulk generate dengan kolom lengkap
    function loadPetugasForBulk(kegiatanId, jenis) {
        $('#bulkLoadingPetugas').show();
        $('#tbodyBulkPetugas').html('');
        
        console.log('Loading petugas for kegiatan:', kegiatanId, 'jenis:', jenis);
        
        $.ajax({
            url: 'index.php',
            type: 'GET',
            data: {
                controller: 'petugas_kegiatan',
                action: 'getPetugasForBulk',
                kegiatan_id: kegiatanId,
                jenis: jenis
            },
            dataType: 'json',
            success: function(res) {
                $('#bulkLoadingPetugas').hide();
                console.log('Response:', res);
                
                if (res.success && res.data && res.data.length > 0) {
                    petugasData = res.data;
                    var html = '';
                    var no = 1;
                    
                    res.data.forEach(function(p) {
                        // Check if already has document
                        var hasDoc = false;
                        if (jenis === 'spk' && p.spk_id) hasDoc = true;
                        if (jenis === 'bast' && p.bast_id) hasDoc = true;
                        if (jenis === 'surat_tugas' && p.surtug_id) hasDoc = true;
                        if (jenis === 'sppd' && p.sppd_id) hasDoc = true;
                        
                        var statusBadge = hasDoc 
                            ? '<span class="badge bg-success">Sudah Ada</span>' 
                            : '<span class="badge bg-warning">Belum</span>';
                        
                        html += '<tr class="' + (hasDoc ? 'table-success' : '') + '">';
                        html += '<td><input type="checkbox" name="petugas_ids[]" value="' + p.id + '" class="form-check-input bulk-checkbox" ' + (hasDoc ? 'disabled' : '') + '></td>';
                        html += '<td>' + no++ + '</td>';
                        html += '<td><strong>' + (p.petugas_nama || '-') + '</strong></td>';
                        html += '<td><span class="badge ' + (p.peran === 'PML' ? 'bg-warning text-dark' : 'bg-secondary') + '">' + (p.peran || '-') + '</span></td>';
                        
                        // Nomor Surat - selalu tampil
                        html += '<td><input type="text" name="nomor_surat[' + p.id + ']" class="form-control form-control-sm" placeholder="Nomor" ' + (hasDoc ? 'disabled' : '') + ' value="' + (p.no_spk || p.no_bast || p.no_surat_tugas || p.no_sppd || '') + '"></td>';
                        
                        // Periode Mulai - tampil kecuali BAST
                        if (jenis !== 'bast') {
                            html += '<td><input type="date" name="periode_mulai[' + p.id + ']" class="form-control form-control-sm" ' + (hasDoc ? 'disabled' : '') + ' value="' + (p.periode_mulai || '') + '"></td>';
                        }
                        
                        // Periode Selesai - tampil kecuali BAST
                        if (jenis !== 'bast') {
                            html += '<td><input type="date" name="periode_selesai[' + p.id + ']" class="form-control form-control-sm" ' + (hasDoc ? 'disabled' : '') + ' value="' + (p.periode_selesai || '') + '"></td>';
                        }
                        
                        // Honorarium - hanya untuk SPK
                        if (jenis === 'spk') {
                            html += '<td><input type="number" name="honorarium[' + p.id + ']" class="form-control form-control-sm" placeholder="Rp" ' + (hasDoc ? 'disabled' : '') + ' value="400000"></td>';
                        }
                        
                        // Tujuan - hanya untuk SPD
                        if (jenis === 'sppd') {
                            html += '<td><input type="text" name="tujuan[' + p.id + ']" class="form-control form-control-sm" placeholder="Tujuan" ' + (hasDoc ? 'disabled' : '') + ' value="' + (p.tujuan || '') + '"></td>';
                        }
                        
                        html += '<td>' + statusBadge + '</td>';
                        html += '</tr>';
                    });
                    
                    $('#tbodyBulkPetugas').html(html);
                    $('#bulkPetugasCount').text(res.data.length);
                    
                    // Update count on checkbox change
                    updateBulkSelectedCount();
                    $('.bulk-checkbox').on('change', updateBulkSelectedCount);
                } else {
                    $('#tbodyBulkPetugas').html('<tr><td colspan="10" class="text-center text-muted">Tidak ada petugas untuk kegiatan ini</td></tr>');
                    $('#bulkPetugasCount').text('0');
                }
            },
            error: function(xhr, status, error) {
                $('#bulkLoadingPetugas').hide();
                console.error('AJAX Error:', status, error);
                $('#tbodyBulkPetugas').html('<tr><td colspan="10" class="text-center text-danger">Gagal memuat data: ' + error + '</td></tr>');
            }
        });
    }
    
    // Function: Update selected count
    function updateBulkSelectedCount() {
        var count = $('.bulk-checkbox:checked').length;
        $('#bulkSelectedCount').text(count);
        console.log('[jQuery Bulk] Selected count:', count);
    }
    
    // Handler: Select all (using event delegation for dynamic elements)
    $(document).on('click change', '#btnSelectAllBulk, #bulkSelectAll', function() {
        $('.bulk-checkbox:not(:disabled)').prop('checked', true);
        updateBulkSelectedCount();
    });
    
    // Handler: Deselect all
    $(document).on('click', '#btnDeselectAllBulk', function() {
        $('.bulk-checkbox').prop('checked', false);
        updateBulkSelectedCount();
    });
    
    // Handler: Checkbox change (event delegation for dynamic checkboxes)
    $(document).on('change', '.bulk-checkbox', function() {
        updateBulkSelectedCount();
    });
    
    // Handler: Submit bulk generate
    $('#formBulkGenerateTable').on('submit', function(e) {
        var selectedCount = $('.bulk-checkbox:checked').length;
        
        if (selectedCount === 0) {
            e.preventDefault();
            alert('Pilih minimal 1 petugas!');
            return false;
        }
        
        // Validate nomor surat for selected
        var valid = true;
        $('.bulk-checkbox:checked').each(function() {
            var id = $(this).val();
            var nomorInput = $('input[name="nomor_surat[' + id + ']"]');
            if (!nomorInput.val().trim()) {
                valid = false;
                nomorInput.addClass('is-invalid');
            } else {
                nomorInput.removeClass('is-invalid');
            }
        });
        
        if (!valid) {
            e.preventDefault();
            alert('Nomor surat harus diisi untuk semua petugas yang dipilih!');
            return false;
        }
        
        // Confirm
        if (!confirm('Generate ' + selectedCount + ' dokumen?')) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // Reset modal on close
    $('#modalBulkGenerate').on('hidden.bs.modal', function() {
        $('#tbodyBulkPetugas').html('');
        $('#bulkPetugasCount').text('0');
        $('#bulkSelectedCount').text('0');
        petugasData = [];
    });
    
    // ========== HONOR MITRA HANDLER ==========
    $('#btnFilterHonor').on('click', function() {
        loadHonorMitra();
    });
    
    // Auto load when modal opens
    $('#modalHonorMitra').on('show.bs.modal', function() {
        loadHonorMitra();
    });
    
    function loadHonorMitra() {
        var bulan = $('#filterHonorBulan').val();
        var tahun = $('#filterHonorTahun').val();
        
        $('#honorLoading').show();
        $('#honorTableBody').html('');
        
        $.ajax({
            url: 'index.php?controller=petugas_kegiatan&action=getHonorMitra',
            method: 'GET',
            data: { bulan: bulan, tahun: tahun },
            dataType: 'json',
            success: function(res) {
                $('#honorLoading').hide();
                
                if (res.success && res.data.length > 0) {
                    var html = '';
                    var totalAll = 0;
                    
                    res.data.forEach(function(item, index) {
                        var honor = parseFloat(item.total_honor) || 0;
                        totalAll += honor;
                        
                        var statusBadge = '';
                        var rowClass = '';
                        var hasOB = item.has_ob == 1; // Cek apakah mitra punya kegiatan OB
                        
                        // Status OB ditampilkan terpisah
                        if (hasOB) {
                            statusBadge = '<span class="badge bg-dark">OB</span>';
                            rowClass = 'table-secondary';
                        } else if (honor > 4000000) {
                            statusBadge = '<span class="badge bg-danger">Melebihi</span>';
                            rowClass = 'table-danger';
                        } else if (honor >= 3000000) {
                            statusBadge = '<span class="badge bg-warning text-dark">Perhatian</span>';
                            rowClass = 'table-warning';
                        } else {
                            statusBadge = '<span class="badge bg-success">Aman</span>';
                        }
                        
                        html += '<tr class="' + rowClass + '">';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td><strong>' + item.nama + '</strong></td>';
                        html += '<td class="text-end">Rp ' + formatNumber(honor) + '</td>';
                        html += '<td class="text-center">' + statusBadge + '</td>';
                        html += '</tr>';
                    });
                    
                    $('#honorTableBody').html(html);
                    $('#totalHonorAll').text('Rp ' + formatNumber(totalAll));
                } else {
                    $('#honorTableBody').html('<tr><td colspan="4" class="text-center text-muted">Tidak ada data honor untuk periode ini</td></tr>');
                    $('#totalHonorAll').text('Rp 0');
                }
            },
            error: function(xhr, status, error) {
                $('#honorLoading').hide();
                $('#honorTableBody').html('<tr><td colspan="4" class="text-center text-danger">Gagal memuat data: ' + error + '</td></tr>');
            }
        });
    }
    
    // Form submit langsung - validasi dilakukan di server
    // Server akan redirect ke halaman konfirmasi jika honor melebihi batas
    
    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>