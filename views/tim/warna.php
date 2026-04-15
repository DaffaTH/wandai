<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';

$teams = $teams ?? [];
// Palet default
$palet = ['#3b82f6','#22c55e','#f59e0b','#a855f7','#ef4444','#06b6d4','#84cc16','#ec4899','#6366f1','#14b8a6'];
?>

<div class="d-flex">
  <div class="sidebar"><?php include __DIR__ . '/../layouts/sidebar.php'; ?></div>
  <div class="flex-grow-1">
    <div class="content-wrapper p-4">

      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
          <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
          <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-palette-fill"></i> Atur Warna Tim</h5>
          <a href="index.php?controller=tim" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
        <div class="card-body">
          <p class="text-muted">
            Warna ini digunakan pada <strong>Dashboard Matriks</strong> untuk membedakan blok penugasan tiap tim.
            Klik kotak warna untuk mengganti, atau pilih cepat dari palet di bawah.
          </p>

          <form method="POST" action="index.php?controller=tim&action=simpanWarna">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width:50px">No</th>
                    <th>Nama Tim</th>
                    <th style="width:150px">Warna</th>
                    <th>Palet Cepat</th>
                    <th style="width:180px">Preview</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($teams as $i => $t): $tid = (int)$t['id']; ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                    <td>
                      <input type="color" class="form-control form-control-color warna-input"
                             name="warna[<?= $tid ?>]"
                             value="<?= htmlspecialchars($t['warna']) ?>"
                             data-tid="<?= $tid ?>" style="width:100%; height:38px;">
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($palet as $hex): ?>
                          <button type="button" class="btn btn-sm p-0 border palet-btn"
                                  data-tid="<?= $tid ?>" data-hex="<?= $hex ?>"
                                  style="width:22px; height:22px; background:<?= $hex ?>;"
                                  title="<?= $hex ?>"></button>
                        <?php endforeach; ?>
                      </div>
                    </td>
                    <td>
                      <span class="preview-blok d-inline-block rounded"
                            data-tid="<?= $tid ?>"
                            style="width:120px; height:28px; background:<?= htmlspecialchars($t['warna']) ?>;"></span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (empty($teams)): ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">Belum ada tim.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="mt-3 pt-3 border-top">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Warna
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.palet-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const tid = this.dataset.tid;
    const hex = this.dataset.hex;
    const inp = document.querySelector('.warna-input[data-tid="' + tid + '"]');
    const prev = document.querySelector('.preview-blok[data-tid="' + tid + '"]');
    if (inp)  inp.value = hex;
    if (prev) prev.style.background = hex;
  });
});
document.querySelectorAll('.warna-input').forEach(inp => {
  inp.addEventListener('input', function () {
    const prev = document.querySelector('.preview-blok[data-tid="' + this.dataset.tid + '"]');
    if (prev) prev.style.background = this.value;
  });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
