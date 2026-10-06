<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex">
  <div class="sidebar">
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
  </div>
  <div class="flex-grow-1">
    <div class="content-wrapper p-4">

      <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
          <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Sinkronisasi Data Pegawai</h5>
          <small class="opacity-75">Sumber: community.bps.go.id · BPS Kabupaten Paniai (9410)</small>
        </div>
        <div class="card-body">

          <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
            <i class="bi bi-shield-lock-fill fs-5 mt-1"></i>
            <div>
              <strong>Prasyarat:</strong> Pastikan <strong>FortiClient VPN BPS</strong> sudah aktif dan
              terhubung sebelum menekan tombol di bawah. Tanpa VPN, halaman community tidak dapat diakses.
            </div>
          </div>

          <!-- Status & tombol fetch -->
          <div id="sync-status" class="mb-3"></div>

          <button id="btn-fetch" class="btn btn-primary" onclick="fetchPreview()">
            <i class="bi bi-cloud-download me-1"></i>Ambil Data dari Community BPS
          </button>

          <!-- Preview area -->
          <div id="preview-area" class="mt-4" style="display:none"></div>

          <!-- Tombol apply -->
          <div id="apply-area" class="mt-3" style="display:none">
            <hr>
            <button id="btn-apply" class="btn btn-success" onclick="applySync()">
              <i class="bi bi-check2-circle me-1"></i>Terapkan Perubahan yang Dipilih
            </button>
            <small class="text-muted ms-2">Hanya update gelar dan tambah pegawai baru yang dicentang.</small>
          </div>

          <!-- Riwayat sync -->
          <div id="log-area" class="mt-5">
            <h6 class="text-muted"><i class="bi bi-clock-history me-1"></i>Riwayat Sinkronisasi Terakhir</h6>
            <div id="log-table">
              <?php
              try {
                  require_once 'config/database.php';
                  $rows = $pdo->query(
                      "SELECT * FROM sync_log ORDER BY sync_time DESC LIMIT 10"
                  )->fetchAll(PDO::FETCH_ASSOC);
                  if ($rows): ?>
                  <table class="table table-sm table-bordered">
                    <thead class="table-light">
                      <tr><th>Waktu</th><th>Ditambah</th><th>Diperbarui</th><th>Error</th></tr>
                    </thead>
                    <tbody>
                      <?php foreach ($rows as $r): ?>
                      <tr>
                        <td><?= htmlspecialchars($r['sync_time']) ?></td>
                        <td class="text-success fw-bold"><?= (int)$r['added'] ?></td>
                        <td class="text-info fw-bold"><?= (int)$r['updated'] ?></td>
                        <td class="<?= $r['errors'] ? 'text-danger fw-bold' : 'text-muted' ?>"><?= (int)$r['errors'] ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  <?php else: ?>
                  <p class="text-muted small">Belum ada riwayat sinkronisasi.</p>
                  <?php endif;
              } catch (Exception $e) {
                  echo '<p class="text-muted small">Tabel riwayat belum tersedia (akan dibuat saat sinkronisasi pertama).</p>';
              }
              ?>
            </div>
          </div>

        </div><!-- card-body -->
      </div><!-- card -->

    </div><!-- content-wrapper -->
  </div><!-- flex-grow-1 -->
</div>

<script>
let previewData = null;

// ── Fetch preview ──────────────────────────────────────────────────────────
async function fetchPreview() {
  const btn    = document.getElementById('btn-fetch');
  const status = document.getElementById('sync-status');

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengambil data…';
  status.innerHTML = '';
  document.getElementById('preview-area').style.display = 'none';
  document.getElementById('apply-area').style.display   = 'none';

  try {
    const res  = await fetch('?controller=sync&action=fetchPreview', { method: 'POST' });
    const data = await res.json();

    if (!data.success) {
      status.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i>${data.message}</div>`;
      return;
    }
    previewData = data;
    renderPreview(data);

  } catch (e) {
    status.innerHTML = `<div class="alert alert-danger">Error jaringan: ${e.message}</div>`;
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-download me-1"></i>Ambil Data dari Community BPS';
  }
}

// ── Render preview ─────────────────────────────────────────────────────────
function renderPreview(data) {
  const area      = document.getElementById('preview-area');
  const applyArea = document.getElementById('apply-area');
  const diff      = data.diff;
  const hasChanges = diff.new.length > 0 || diff.gelarUpdate.length > 0;

  let html = `
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card text-center border-0 bg-light">
          <div class="card-body py-2">
            <h3 class="mb-0">${data.total_community}</h3>
            <small class="text-muted">Di Community</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center border-0 bg-success bg-opacity-10">
          <div class="card-body py-2">
            <h3 class="mb-0 text-success">${diff.new.length}</h3>
            <small class="text-muted">Pegawai Baru</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center border-0 bg-info bg-opacity-10">
          <div class="card-body py-2">
            <h3 class="mb-0 text-info">${diff.gelarUpdate.length}</h3>
            <small class="text-muted">Perubahan Gelar</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center border-0 bg-warning bg-opacity-10">
          <div class="card-body py-2">
            <h3 class="mb-0 text-warning">${diff.missing.length}</h3>
            <small class="text-muted">Tidak di Community</small>
          </div>
        </div>
      </div>
    </div>`;

  // Pegawai baru
  if (diff.new.length > 0) {
    html += `<h6 class="text-success"><i class="bi bi-person-plus-fill me-1"></i>Pegawai Baru (${diff.new.length})</h6>
    <div class="table-responsive mb-4">
    <table class="table table-sm table-bordered align-middle">
      <thead class="table-success">
        <tr>
          <th style="width:36px"><input type="checkbox" id="chk-all-new" checked onchange="toggleAll('new',this.checked)"></th>
          <th>Nama Asli (dari Community)</th><th>Nama</th><th>Gelar Depan</th><th>Gelar Belakang</th>
        </tr>
      </thead>
      <tbody>`;
    diff.new.forEach((e, i) => {
      html += `<tr>
        <td><input type="checkbox" class="chk-new" data-idx="${i}" checked></td>
        <td>${esc(e.nama_asli)}</td>
        <td>${esc(e.name)}</td>
        <td>${esc(e.gelar_depan)}</td>
        <td>${esc(e.gelar_belakang)}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
  }

  // Perubahan gelar
  if (diff.gelarUpdate.length > 0) {
    html += `<h6 class="text-info"><i class="bi bi-pencil-fill me-1"></i>Perubahan Gelar (${diff.gelarUpdate.length})</h6>
    <div class="table-responsive mb-4">
    <table class="table table-sm table-bordered align-middle">
      <thead class="table-info">
        <tr>
          <th style="width:36px"><input type="checkbox" id="chk-all-upd" checked onchange="toggleAll('upd',this.checked)"></th>
          <th>Nama</th><th>Gelar Depan Lama → Baru</th><th>Gelar Belakang Lama → Baru</th>
        </tr>
      </thead>
      <tbody>`;
    diff.gelarUpdate.forEach((e, i) => {
      const gdChange = (e.local_gelar_depan || '-') + ' → ' + (e.gelar_depan || '-');
      const gbChange = (e.local_gelar_belakang || '-') + ' → ' + (e.gelar_belakang || '-');
      html += `<tr>
        <td><input type="checkbox" class="chk-upd" data-idx="${i}" checked></td>
        <td>${esc(e.name)}</td>
        <td>${esc(gdChange)}</td>
        <td>${esc(gbChange)}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
  }

  // Tidak di community (informasi saja)
  if (diff.missing.length > 0) {
    html += `<details class="mb-3">
      <summary class="text-warning cursor-pointer">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Di lokal tapi tidak ditemukan di Community (${diff.missing.length}) — <em>tidak dihapus otomatis</em>
      </summary>
      <ul class="mt-2 small">`;
    diff.missing.forEach(e => { html += `<li>${esc(e.name)}</li>`; });
    html += '</ul></details>';
  }

  if (!hasChanges) {
    html += `<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>Data sudah sinkron, tidak ada perubahan.</div>`;
  }

  area.innerHTML = html;
  area.style.display = 'block';
  if (hasChanges) applyArea.style.display = 'block';
}

// ── Apply sync ─────────────────────────────────────────────────────────────
async function applySync() {
  if (!previewData) return;

  const diff = previewData.diff;

  // Kumpulkan yang dicentang
  const toAdd = [];
  document.querySelectorAll('.chk-new:checked').forEach(cb => {
    toAdd.push(diff.new[parseInt(cb.dataset.idx)]);
  });

  const toUpdate = [];
  document.querySelectorAll('.chk-upd:checked').forEach(cb => {
    toUpdate.push(diff.gelarUpdate[parseInt(cb.dataset.idx)]);
  });

  if (!toAdd.length && !toUpdate.length) {
    alert('Tidak ada perubahan yang dipilih.');
    return;
  }

  if (!confirm(`Terapkan: ${toAdd.length} pegawai baru + ${toUpdate.length} update gelar?`)) return;

  const btn = document.getElementById('btn-apply');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menerapkan…';

  try {
    const res  = await fetch('?controller=sync&action=apply', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ add: toAdd, update: toUpdate }),
    });
    const data = await res.json();

    if (data.success) {
      let msg = `Selesai: <strong>${data.added}</strong> ditambah, <strong>${data.updated}</strong> gelar diperbarui.`;
      if (data.errors.length) msg += `<br><small class="text-danger">${data.errors.join('<br>')}</small>`;
      document.getElementById('sync-status').innerHTML =
        `<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>${msg}</div>`;
      document.getElementById('apply-area').style.display = 'none';
      setTimeout(() => location.reload(), 3000);
    } else {
      document.getElementById('sync-status').innerHTML =
        `<div class="alert alert-danger">${data.message}</div>`;
    }
  } catch (e) {
    document.getElementById('sync-status').innerHTML =
      `<div class="alert alert-danger">Error: ${e.message}</div>`;
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Terapkan Perubahan yang Dipilih';
  }
}

// ── Helpers ────────────────────────────────────────────────────────────────
function toggleAll(type, checked) {
  document.querySelectorAll(`.chk-${type}`).forEach(cb => { cb.checked = checked; });
}

function esc(str) {
  if (!str) return '<span class="text-muted">-</span>';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
