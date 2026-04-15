<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';

$wilayahList = ['Paniai', 'Intan Jaya', 'Deiyai'];
?>

<style>
.content-wrapper { padding: 1.5rem; background-color: #f8f9fa; min-height: 100vh; }
.card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px 12px 0 0 !important; }
.table th { background-color: #f8f9fa; font-weight: 600; }
.badge-aktif { background-color: #28a745; }
.badge-nonaktif { background-color: #dc3545; }
.badge-wilayah { background-color: #17a2b8; }
</style>

<div class="d-flex">
    <div class="sidebar"><?php include __DIR__ . '/../layouts/sidebar.php'; ?></div>
    <div class="flex-grow-1">
        <div class="content-wrapper">
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Data Mitra</h5>
                    <div>
                        <button class="btn btn-light btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalImport">
                            <i class="bi bi-upload me-1"></i>Import Excel
                        </button>
                        <a href="index.php?controller=mitra&action=downloadTemplate" class="btn btn-outline-light btn-sm me-2">
                            <i class="bi bi-download me-1"></i>Template
                        </a>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Mitra
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tableMitra">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>NIK</th>
                                    <th>Alamat</th>
                                    <th>Wilayah Kerja</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mitraList ?? [] as $i => $mitra): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($mitra['nama']) ?></strong></td>
                                    <td><small><?= htmlspecialchars($mitra['email']) ?></small></td>
                                    <td><code><?= htmlspecialchars($mitra['nik']) ?></code></td>
                                    <td><small><?= htmlspecialchars($mitra['alamat']) ?></small></td>
                                    <td>
                                        <span class="badge badge-wilayah"><?= htmlspecialchars($mitra['wilayah_kerja'] ?? 'Paniai') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $mitra['status'] ?>"><?= ucfirst($mitra['status']) ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning btn-edit" 
                                                data-id="<?= $mitra['id'] ?>"
                                                data-nama="<?= htmlspecialchars($mitra['nama']) ?>"
                                                data-email="<?= htmlspecialchars($mitra['email']) ?>"
                                                data-nik="<?= htmlspecialchars($mitra['nik']) ?>"
                                                data-alamat="<?= htmlspecialchars($mitra['alamat']) ?>"
                                                data-wilayah="<?= htmlspecialchars($mitra['wilayah_kerja'] ?? 'Paniai') ?>"
                                                data-status="<?= $mitra['status'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-delete" 
                                                data-id="<?= $mitra['id'] ?>"
                                                data-nama="<?= htmlspecialchars($mitra['nama']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Mitra -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?controller=mitra&action=store" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Mitra</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="contoh@email.com">
                        <small class="text-muted">Email digunakan sebagai identifier unik untuk login dan import</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIK <span class="text-danger">*</span></label>
                        <input type="text" name="nik" class="form-control" required maxlength="16" pattern="\d{16}">
                        <small class="text-muted">16 digit angka</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea name="alamat" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wilayah Kerja <span class="text-danger">*</span></label>
                        <select name="wilayah_kerja" class="form-select" required>
                            <?php foreach ($wilayahList as $wilayah): ?>
                                <option value="<?= $wilayah ?>"><?= $wilayah ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Wilayah kerja untuk Surat Tugas</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="4">
                        <small class="text-muted">Minimal 4 karakter</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Mitra -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?controller=mitra&action=update" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Mitra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIK <span class="text-danger">*</span></label>
                        <input type="text" name="nik" id="edit_nik" class="form-control" required maxlength="16">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea name="alamat" id="edit_alamat" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wilayah Kerja <span class="text-danger">*</span></label>
                        <select name="wilayah_kerja" id="edit_wilayah" class="form-select" required>
                            <?php foreach ($wilayahList as $wilayah): ?>
                                <option value="<?= $wilayah ?>"><?= $wilayah ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control" minlength="4">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import Excel -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?controller=mitra&action=import" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Import Data Mitra</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Format Excel:</strong>
                        <ul class="mb-0 small">
                            <li><strong>Kolom:</strong> Nama, Email, NIK, Alamat, Wilayah Kerja, Password, Status</li>
                            <li><strong>Email</strong> harus unik (tidak boleh duplikat)</li>
                            <li><strong>NIK</strong> harus 16 digit angka</li>
                            <li><strong>Wilayah Kerja:</strong> Paniai / Intan Jaya / Deiyai</li>
                            <li>Password kosong akan diset default: <code>9502</code></li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File Excel (.xlsx, .xls)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete -->
<div class="modal fade" id="modalDelete" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?controller=mitra&action=delete" method="POST">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus mitra <strong id="delete_nama"></strong>?</p>
                    <p class="text-muted small">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#tableMitra').DataTable({
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            paginate: { previous: "Sebelumnya", next: "Selanjutnya" }
        }
    });

    // Edit
    $('.btn-edit').click(function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_nama').val($(this).data('nama'));
        $('#edit_email').val($(this).data('email'));
        $('#edit_nik').val($(this).data('nik'));
        $('#edit_alamat').val($(this).data('alamat'));
        $('#edit_wilayah').val($(this).data('wilayah'));
        $('#edit_status').val($(this).data('status'));
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    });

    // Delete
    $('.btn-delete').click(function() {
        $('#delete_id').val($(this).data('id'));
        $('#delete_nama').text($(this).data('nama'));
        new bootstrap.Modal(document.getElementById('modalDelete')).show();
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
