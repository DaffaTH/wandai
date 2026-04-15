<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';

// FIX: Mapping variable dari controller ($kegiatanList) ke view ($kegiatan)
$kegiatan = $kegiatanList ?? [];
$pjUsers  = $pjUsers ?? [];   // kandidat Penanggung Jawab (Kepala/Kasubbag/PPK)
?>

<style>
/* ========== RESPONSIVE & CLEAN DESIGN ========== */
.content-wrapper {
    padding: 1.5rem;
    background-color: #f8f9fa;
    min-height: 100vh;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1.5rem;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0 !important;
    padding: 1rem 1.5rem;
}

/* Filter Section */
.filter-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.filter-row .filter-item {
    flex: 1;
    min-width: 180px;
}

.filter-row .filter-item label {
    font-size: 0.8rem;
    font-weight: 500;
    color: #6c757d;
    margin-bottom: 0.35rem;
    display: block;
}

.filter-row .filter-btn {
    flex: 0 0 auto;
}

/* Table Styling */
.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    font-weight: 500;
    font-size: 0.85rem;
    padding: 0.85rem 0.75rem;
    border: none;
    white-space: nowrap;
}

.table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    font-size: 0.875rem;
    border-color: #f0f0f0;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Progress Bar */
.progress {
    height: 22px;
    border-radius: 11px;
    background-color: #e9ecef;
    overflow: hidden;
}

.progress-bar {
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 22px;
    transition: width 0.3s ease;
}

/* Badges */
.badge {
    font-weight: 500;
    padding: 0.4em 0.65em;
    font-size: 0.75rem;
}

/* Action Buttons */
.btn-action {
    padding: 0.35rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 6px;
}

.btn-action i {
    font-size: 0.9rem;
}

/* Modal Styling */
.modal-header {
    border-radius: 12px 12px 0 0;
}

.modal-content {
    border-radius: 12px;
    border: none;
}

/* Section Title in Modal */
.section-title {
    font-weight: 600;
    color: #667eea;
    border-bottom: 2px solid #667eea;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
    margin-top: 1rem;
}

/* Responsive */
@media (max-width: 992px) {
    .filter-row .filter-item {
        min-width: 140px;
    }
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-row .filter-item {
        width: 100%;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .page-title {
        font-size: 1.25rem;
    }
}

/* DataTables Overrides */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}

.dataTables_wrapper .dataTables_filter input {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 0.4rem 0.75rem;
}

.page-link {
    border-radius: 6px !important;
    margin: 0 2px;
}

/* Empty State */
.empty-state {
    padding: 3rem 1rem;
    text-align: center;
}

.empty-state i {
    font-size: 4rem;
    color: #dee2e6;
}

.empty-state h5 {
    color: #6c757d;
    margin-top: 1rem;
}
</style>

<div class="d-flex">
    <div class="sidebar">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    </div>

    <div class="flex-grow-1">
        <div class="content-wrapper">
            <h4 class="page-title">
                <i class="bi bi-clipboard-data text-primary"></i> Monitoring Kegiatan
            </h4>

            <!-- Flash Messages -->
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Action Button -->
            <div class="mb-3">
                <a href="index.php?controller=kegiatan&action=create" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Kegiatan
                </a>
            </div>

            <!-- Filter Card -->
            <div class="filter-card shadow-sm">
                <form method="GET" action="index.php">
                    <input type="hidden" name="controller" value="kegiatan">
                    <div class="filter-row">
                        <div class="filter-item">
                            <label>Tim</label>
                            <select name="team_id" class="form-select form-select-sm">
                                <option value="">-- Semua Tim --</option>
                                <?php foreach ($teams ?? [] as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= (isset($_GET['team_id']) && $_GET['team_id'] == $t['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label>Bulan</label>
                            <input type="month" name="bulan" class="form-control form-control-sm" 
                                   value="<?= htmlspecialchars($_GET['bulan'] ?? '') ?>">
                        </div>
                        
                        <div class="filter-item">
                            <label>Jenis Kegiatan</label>
                            <select name="jenis" class="form-select form-select-sm">
                                <?php
                                  $selJenis = $_GET['jenis'] ?? '';
                                  $jenisOpts = ['Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan'];
                                ?>
                                <option value="">-- Semua Jenis --</option>
                                <?php foreach ($jenisOpts as $jo): ?>
                                    <option value="<?= htmlspecialchars($jo) ?>"
                                            <?= $selJenis === $jo ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jo) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="other" <?= $selJenis === 'other' ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="filter-btn">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-funnel-fill me-1"></i> Filter
                            </button>
                            <a href="index.php?controller=kegiatan" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Data Table -->
            <div class="table-container shadow-sm">
                <?php if (empty($kegiatan)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h5>Belum ada kegiatan</h5>
                        <p class="text-muted">Klik "Tambah Kegiatan" untuk menambahkan kegiatan baru</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="dataTable">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th>Nama Kegiatan</th>
                                    <th>Tim</th>
                                    <th>Jenis</th>
                                    <th>Rentang Waktu</th>
                                    <th width="100">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($kegiatan as $k): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($k['nama_kegiatan']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($k['team_name'] ?? '-') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($k['jenis_kegiatan'] ?? '-') ?></span>
                                        </td>
                                        <td>
                                            <small>
                                                <?= date('d/m/Y', strtotime($k['rentang_waktu_mulai'])) ?> -<br>
                                                <?= date('d/m/Y', strtotime($k['rentang_waktu_selesai'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-warning btn-action btn-edit"
                                                        data-bs-toggle="modal" data-bs-target="#modalEditKegiatan"
                                                        data-id="<?= $k['id'] ?>"
                                                        data-nama="<?= htmlspecialchars($k['nama_kegiatan']) ?>"
                                                        data-jenis="<?= htmlspecialchars($k['jenis_kegiatan'] ?? '') ?>"
                                                        data-mulai="<?= $k['rentang_waktu_mulai'] ?>"
                                                        data-selesai="<?= $k['rentang_waktu_selesai'] ?>"
                                                        data-satuan="<?= htmlspecialchars($k['satuan'] ?? '') ?>"
                                                        data-program="<?= htmlspecialchars($k['program'] ?? '') ?>"
                                                        data-output="<?= htmlspecialchars($k['output'] ?? '') ?>"
                                                        data-komponen="<?= htmlspecialchars($k['komponen'] ?? '') ?>"
                                                        data-honor-satuan="<?= $k['honor_satuan'] ?? '' ?>"
                                                        data-pj="<?= $k['penanggung_jawab_user_id'] ?? '' ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-success btn-action btn-bast"
                                                        data-bs-toggle="modal" data-bs-target="#modalBastKegiatan"
                                                        data-id="<?= $k['id'] ?>"
                                                        data-nama="<?= htmlspecialchars($k['nama_kegiatan']) ?>"
                                                        title="Generate BAST Honor">
                                                    <i class="bi bi-file-earmark-text"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-action btn-delete"
                                                        data-id="<?= $k['id'] ?>"
                                                        data-nama="<?= htmlspecialchars($k['nama_kegiatan']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
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

<!-- Modal Tambah Kegiatan -->
<div class="modal fade" id="modalTambahKegiatan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Kegiatan Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?controller=kegiatan&action=store">
                <div class="modal-body">
                    <!-- Informasi Dasar -->
                    <h6 class="section-title"><i class="bi bi-info-circle me-2"></i>Informasi Dasar</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kegiatan" class="form-control" required 
                                   placeholder="Contoh: Sensus Ekonomi 2026 - Pendataan Listing">
                        </div>
                        
                        <?php
                        // Admin (1) dan Operator (6) bisa pilih Tim — mapping baru
                        $canSelectTeam = in_array($_SESSION['user']['role_id'], [1, 6]);
                        ?>
                        <?php if ($canSelectTeam): ?>
                        <div class="col-md-6">
                            <label class="form-label">Tim <span class="text-danger">*</span></label>
                            <select name="team_id" class="form-select" required>
                                <option value="">-- Pilih Tim --</option>
                                <?php foreach ($teams ?? [] as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= ($t['id'] == ($_SESSION['user']['team_id'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-<?= $canSelectTeam ? '6' : '12' ?>">
                            <label class="form-label">Jenis Kegiatan <span class="text-danger">*</span></label>
                            <select name="jenis_kegiatan" class="form-select jenis-select" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="Pendataan">Pendataan</option>
                                <option value="Updating">Updating</option>
                                <option value="Pengolahan">Pengolahan</option>
                                <option value="other">Lainnya...</option>
                            </select>
                        </div>
                        
                        <div class="col-12 jenis-lainnya-container" style="display: none;">
                            <label class="form-label">Jenis Kegiatan Lainnya <span class="text-danger">*</span></label>
                            <input type="text" name="jenis_kegiatan_lainnya" class="form-control" 
                                   placeholder="Tuliskan jenis kegiatan...">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="rentang_waktu_mulai" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="rentang_waktu_selesai" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <select name="satuan" class="form-select" required>
                                <option value="Dok">Dok (Dokumen)</option>
                                <option value="OB">OB (Orang/Bulan)</option>
                                <option value="OK">OK (Orang/Kegiatan)</option>
                                <option value="Ruta">Ruta (Rumah Tangga)</option>
                                <option value="EA">EA (Enumeration Area)</option>
                                <option value="SGMN">SGMN (Segmen)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Pembebanan Anggaran -->
                    <h6 class="section-title"><i class="bi bi-cash-stack me-2"></i>Pembebanan Anggaran</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Program</label>
                            <input type="text" name="program" class="form-control" placeholder="Contoh: 054.01.GG">
                            <small class="text-muted">Kode program anggaran</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Output</label>
                            <input type="text" name="output" class="form-control" placeholder="Contoh: 2905.BMN.001">
                            <small class="text-muted">Kode output kegiatan</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Komponen</label>
                            <input type="text" name="komponen" class="form-control" placeholder="Contoh: 001">
                            <small class="text-muted">Kode komponen</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Honor/Satuan (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="honor_satuan" class="form-control" placeholder="Contoh: 400000" min="0">
                            </div>
                            <small class="text-muted">Honor per satuan untuk mitra</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Catatan:</strong> Target dan Realisasi akan dihitung otomatis dari Input Petugas.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kegiatan -->
<div class="modal fade" id="modalEditKegiatan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Kegiatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?controller=kegiatan&action=update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <!-- Informasi Dasar -->
                    <h6 class="section-title"><i class="bi bi-info-circle me-2"></i>Informasi Dasar</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kegiatan" id="edit_nama" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Jenis Kegiatan <span class="text-danger">*</span></label>
                            <select name="jenis_kegiatan" id="edit_jenis" class="form-select jenis-select" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="Pelatihan/Briefing">Pelatihan/Briefing</option>
                                <option value="Updating/Listing">Updating/Listing</option>
                                <option value="Pendataan">Pendataan</option>
                                <option value="Pengolahan">Pengolahan</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <select name="satuan" id="edit_satuan" class="form-select" required>
                                <option value="">-- Pilih Satuan --</option>
                                <option value="dokumen">Dokumen</option>
                                <option value="segmen">Segmen</option>
                                <option value="responden">Responden</option>
                                <option value="sampel">Sampel</option>
                                <option value="SLS">SLS</option>
                                <option value="BS">BS</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="rentang_waktu_mulai" id="edit_mulai" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="rentang_waktu_selesai" id="edit_selesai" class="form-control" required>
                        </div>
                    </div>
                    
                    <!-- Pembebanan Anggaran -->
                    <h6 class="section-title"><i class="bi bi-cash-stack me-2"></i>Pembebanan Anggaran</h6>
                    <div class="row g-3" id="edit_budget_section">
                        <div class="col-md-4">
                            <label class="form-label">Program</label>
                            <input type="text" name="program" id="edit_program" class="form-control" placeholder="Contoh: 054.01.GG">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Output</label>
                            <input type="text" name="output" id="edit_output" class="form-control" placeholder="Contoh: 2905.BMN.001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Komponen</label>
                            <input type="text" name="komponen" id="edit_komponen" class="form-control" placeholder="Contoh: 001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Honor/Satuan (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" name="honor_satuan"
                                       id="edit_honor_satuan" class="form-control honor-input"
                                       placeholder="Contoh: 400.000">
                            </div>
                            <small class="text-muted">Honor per satuan untuk mitra</small>
                        </div>
                    </div>

                    <!-- Penanggung Jawab Kegiatan (untuk BAST Honor) -->
                    <h6 class="section-title mt-3"><i class="bi bi-person-badge me-2"></i>Penanggung Jawab</h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">
                                Penanggung Jawab Kegiatan
                                <small class="text-muted fw-normal">(untuk BAST Honor)</small>
                            </label>
                            <select name="penanggung_jawab_user_id" id="edit_pj" class="form-select">
                                <option value="">-- Pilih Penanggung Jawab --</option>
                                <?php foreach ($pjUsers as $pj): ?>
                                    <option value="<?= (int)$pj['id'] ?>">
                                        <?= htmlspecialchars($pj['name']) ?>
                                        <?php if (!empty($pj['role_name'])): ?>
                                            (<?= htmlspecialchars($pj['role_name']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Biasanya Kepala/Kasubbag/PPK. Dipakai sebagai penandatangan BAST Honor.</small>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0 d-none" id="edit_pelatihan_note">
                        <i class="bi bi-info-circle me-1"></i>
                        Untuk jenis <strong>Pelatihan/Briefing</strong>, kolom Satuan, Program,
                        Output, Komponen, dan Honor/Satuan dibiarkan kosong.
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Target dan Realisasi dihitung otomatis dari Input Petugas.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Generate BAST Honor per Kegiatan -->
<div class="modal fade" id="modalBastKegiatan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i>Generate BAST Honor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?controller=dokumen&action=generateBASTKegiatan">
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        BAST Honor akan dibuat untuk <strong id="bast_nama_kegiatan">-</strong>
                        mencakup semua petugas di kegiatan ini.
                    </p>
                    <input type="hidden" name="kegiatan_detail_id" id="bast_kegiatan_id">
                    <div class="mb-3">
                        <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nomor_surat" required
                               placeholder="Contoh: 001/BAST/93.91/2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal_surat" required
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Pastikan <strong>Penanggung Jawab Kegiatan</strong> sudah diisi (edit kegiatan bila belum).
                        Bila kosong, Kepala akan dipakai sebagai fallback.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-download me-1"></i> Generate & Download
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // DataTable
    if ($('#dataTable').length) {
        $('#dataTable').DataTable({
            pageLength: 10,
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ entri",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_ entri",
                paginate: { next: "›", previous: "‹" },
                emptyTable: "Tidak ada data"
            },
            order: [[0, 'asc']]
        });
    }
    
    // Helper: format angka jadi "400.000" (titik ribuan Indonesia)
    function formatRibuan(v) {
        var digits = String(v == null ? '' : v).replace(/\D+/g, '');
        if (!digits) return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Jenis kegiatan 4-canonical. Kalau "Pelatihan/Briefing": Satuan, Program,
    // Output, Komponen, dan Honor/Satuan dikunci, dikosongkan, dan placeholder
    // contohnya juga dihilangkan (tidak relevan untuk Pelatihan/Briefing).
    function applyEditPelatihanMode(isPelatihan) {
        var fields = ['edit_satuan', 'edit_program', 'edit_output',
                      'edit_komponen', 'edit_honor_satuan'];
        fields.forEach(function (id) {
            var $el = $('#' + id);
            if (!$el.length) return;

            // Simpan placeholder asli sekali saja supaya bisa dikembalikan nanti.
            if ($el.attr('placeholder') && typeof $el.data('ph-original') === 'undefined') {
                $el.data('ph-original', $el.attr('placeholder'));
            }

            if (isPelatihan) {
                $el.val('').prop('disabled', true).prop('required', false);
                // Kosongkan placeholder contoh supaya cell benar-benar kosong.
                if ($el.is('input')) $el.attr('placeholder', '');
                // Untuk <select>, kosongkan teks option pertama ("-- Pilih Satuan --").
                if ($el.is('select')) {
                    var $first = $el.find('option').first();
                    if (typeof $first.data('label-original') === 'undefined') {
                        $first.data('label-original', $first.text());
                    }
                    $first.text('');
                }
                $el.addClass('bg-light text-muted');
            } else {
                $el.prop('disabled', false).removeClass('bg-light text-muted');
                // Kembalikan placeholder asli.
                if ($el.is('input')) {
                    var orig = $el.data('ph-original');
                    if (orig) $el.attr('placeholder', orig);
                }
                if ($el.is('select')) {
                    var $first = $el.find('option').first();
                    var origLabel = $first.data('label-original');
                    if (origLabel) $first.text(origLabel);
                }
                // Hanya Satuan yang required di mode non-Pelatihan
                $el.prop('required', id === 'edit_satuan');
            }
        });
        $('#edit_pelatihan_note').toggleClass('d-none', !isPelatihan);
    }

    // Re-apply toggle saat user mengubah jenis secara manual di modal Edit.
    $(document).on('change', '#edit_jenis', function () {
        applyEditPelatihanMode($(this).val() === 'Pelatihan/Briefing');
    });

    // Edit button handler
    $(document).on('click', '.btn-edit', function () {
        var btn = $(this);
        $('#edit_id').val(btn.data('id'));
        $('#edit_nama').val(btn.data('nama'));
        $('#edit_mulai').val(btn.data('mulai'));
        $('#edit_selesai').val(btn.data('selesai'));

        var jenis = btn.data('jenis') || '';
        // Fallback untuk data lama (kolom jenis_kegiatan string ganjil)
        var canonical = ['Pelatihan/Briefing', 'Updating/Listing', 'Pendataan', 'Pengolahan'];
        $('#edit_jenis').val(canonical.indexOf(jenis) !== -1 ? jenis : '');

        // Isi field lainnya dulu
        $('#edit_satuan').val(btn.data('satuan') || '');
        $('#edit_program').val(btn.data('program') || '');
        $('#edit_output').val(btn.data('output') || '');
        $('#edit_komponen').val(btn.data('komponen') || '');
        $('#edit_honor_satuan').val(formatRibuan(btn.data('honor-satuan') || ''));
        $('#edit_pj').val(btn.data('pj') || '');

        // Lalu kunci/kosongkan sesuai mode
        applyEditPelatihanMode(jenis === 'Pelatihan/Briefing');
    });

    // Titik ribuan untuk field honor di modal edit
    $(document).on('input', '#edit_honor_satuan', function () {
        var before = this.value.slice(0, this.selectionStart || 0);
        var digitsBefore = (before.match(/\d/g) || []).length;
        var formatted = formatRibuan(this.value);
        this.value = formatted;
        var pos = 0, count = 0;
        while (pos < formatted.length && count < digitsBefore) {
            if (/\d/.test(formatted[pos])) count++;
            pos++;
        }
        this.setSelectionRange(pos, pos);
    });

    // Strip titik sebelum submit supaya server dapat angka murni.
    $(document).on('submit', '#modalEditKegiatan form', function () {
        var $h = $('#edit_honor_satuan');
        $h.val(String($h.val() || '').replace(/\./g, ''));
    });
    
    // BAST Kegiatan button handler
    $(document).on('click', '.btn-bast', function () {
        var btn = $(this);
        $('#bast_kegiatan_id').val(btn.data('id'));
        $('#bast_nama_kegiatan').text(btn.data('nama') || '-');
    });

    // Delete button handler - FIX: Gunakan POST dengan form
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        
        if (confirm('Apakah Anda yakin ingin menghapus kegiatan:\n\n"' + nama + '"?\n\nData yang sudah dihapus tidak dapat dikembalikan.')) {
            // Create form untuk POST request
            var form = $('<form>', {
                'method': 'POST',
                'action': 'index.php?controller=kegiatan&action=delete'
            });
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'id',
                'value': id
            }));
            $('body').append(form);
            form.submit();
        }
    });
    
    // Reset form on modal close
    $('#modalTambahKegiatan').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.jenis-lainnya-container').hide();
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>