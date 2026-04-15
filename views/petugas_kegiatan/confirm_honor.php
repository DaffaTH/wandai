<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';
?>

<style>
.confirm-card {
    max-width: 700px;
    margin: 50px auto;
}
.honor-warning-table {
    margin: 20px 0;
}
.honor-warning-table th {
    background-color: #f8d7da;
}
</style>

<div class="container">
    <div class="card confirm-card shadow">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Konfirmasi Honor Melebihi Batas
            </h4>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong><i class="bi bi-info-circle"></i> Perhatian!</strong><br>
                Honor mitra berikut akan melebihi <strong>Rp 4.000.000</strong> pada bulan ini jika kegiatan ditambahkan:
            </div>
            
            <table class="table table-bordered honor-warning-table">
                <thead>
                    <tr>
                        <th>Nama Mitra</th>
                        <th>Peran</th>
                        <th>Honor Saat Ini</th>
                        <th>+ Honor Baru</th>
                        <th>= Total Proyeksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mitraList as $m): ?>
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
            
            <div class="alert alert-info">
                <i class="bi bi-question-circle"></i> Apakah Anda yakin ingin melanjutkan penambahan petugas ini?
            </div>
            
            <div class="d-flex justify-content-center gap-3 mt-4">
                <form method="POST" action="index.php?controller=petugas_kegiatan&action=processConfirmHonor" style="display:inline;">
                    <input type="hidden" name="action" value="confirm">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Ya, Lanjutkan
                    </button>
                </form>
                
                <form method="POST" action="index.php?controller=petugas_kegiatan&action=processConfirmHonor" style="display:inline;">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="bi bi-x-circle me-2"></i>Tidak, Batalkan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
