<?php
/**
 * ========================================
 * FILE: views/beban_kerja/index.php
 * DASHBOARD BEBAN KERJA MITRA
 * 
 * UPDATED:
 * - Filter: Wilayah Kerja, Bulan, Tahun (default tahun berjalan)
 * - Kegiatan Berjalan/Selesai berdasarkan TANGGAL
 * - 2 Card Mitra dengan Beban Tertinggi (Paniai & Intan Jaya)
 * ========================================
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php?controller=auth&action=login');
    exit;
}

$role = $_SESSION['user']['role_name'] ?? '';
$filterTahun = $currentFilters['tahun'] ?? date('Y');
$filterBulan = $currentFilters['bulan'] ?? '';
$filterWilayah = $currentFilters['wilayah'] ?? '';
?>

<style>
.beban-kerja-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.98) 100%);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 8px 32px rgba(255, 112, 67, 0.08);
  border: 1px solid rgba(255, 140, 66, 0.1);
  margin-bottom: 1.5rem;
}

.stat-card {
  background: linear-gradient(135deg, #fff 0%, #fff5f0 100%);
  border-radius: 15px;
  padding: 1.25rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border-left: 4px solid;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  height: 100%;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.stat-card.primary { border-left-color: #ff6b35; }
.stat-card.success { border-left-color: #4ade80; }
.stat-card.paniai { border-left-color: #22c55e; }
.stat-card.intanjaya { border-left-color: #f97316; }

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
  background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stat-value.small { font-size: 1.1rem; }
.stat-value.paniai {
  background: linear-gradient(135deg, #22c55e 0%, #4ade80 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.stat-value.intanjaya {
  background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.stat-label {
  font-size: 0.8rem;
  color: #6b7280;
  margin: 0.5rem 0 0 0;
  font-weight: 500;
}

.stat-sublabel {
  font-size: 0.7rem;
  color: #9ca3af;
  margin-top: 0.25rem;
}

.table-modern {
  background: white;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.table-modern thead {
  background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
  color: white;
}

.table-modern thead th {
  border: none;
  padding: 1rem;
  font-weight: 600;
  font-size: 0.85rem;
}

.table-modern tbody tr:hover { background-color: #fff5f0; }
.table-modern tbody td {
  padding: 1rem;
  vertical-align: middle;
  border-bottom: 1px solid #f3f4f6;
}

.badge-kegiatan {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
}
.badge-kegiatan.high { background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%); color: white; }
.badge-kegiatan.medium { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; }
.badge-kegiatan.low { background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); color: white; }

.progress-bar-modern {
  height: 8px;
  border-radius: 10px;
  background: #e5e7eb;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  border-radius: 10px;
  background: linear-gradient(90deg, #ff6b35 0%, #ff8c42 50%, #ffa726 100%);
}

.filter-card {
  background: white;
  border-radius: 15px;
  padding: 1rem 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 1.5rem;
}

.bg-success { background-color: #22c55e !important; }
.bg-orange { background-color: #f97316 !important; }
</style>

<div class="d-flex">
  <div class="sidebar"><?php include __DIR__ . '/../layouts/sidebar.php'; ?></div>

  <div class="flex-grow-1">
    <div class="content-wrapper">
      <h4 class="mb-4">
        <i class="bi bi-graph-up-arrow"></i> Dashboard Beban Kerja Mitra
        <small class="text-muted fs-6 ms-2">(Tahun <?= htmlspecialchars($filterTahun) ?>)</small>
      </h4>
      
      <!-- Filter -->
      <div class="filter-card">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
          <input type="hidden" name="controller" value="beban_kerja">
          <div class="col-md-3">
            <label class="form-label small text-muted">Wilayah Kerja</label>
            <select name="wilayah" class="form-select">
              <option value="">-- Semua Wilayah --</option>
              <?php foreach ($availableWilayah ?? [] as $w): ?>
                <option value="<?= htmlspecialchars($w) ?>" <?= $filterWilayah === $w ? 'selected' : '' ?>><?= htmlspecialchars($w) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted">Tahun</label>
            <select name="tahun" class="form-select">
              <option value="all">-- Semua Tahun --</option>
              <?php foreach ($availableYears ?? [] as $y): ?>
                <option value="<?= $y ?>" <?= $filterTahun == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted">Bulan</label>
            <input type="month" name="bulan" class="form-control" value="<?= htmlspecialchars($filterBulan) ?>">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
          </div>
        </form>
      </div>
      
      <!-- Stats Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
          <div class="stat-card primary">
            <p class="stat-value"><?= $totalMitra ?></p>
            <p class="stat-label">Total Mitra Aktif</p>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-card success">
            <p class="stat-value"><?= $totalKegiatan ?></p>
            <p class="stat-label">Total Kegiatan</p>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-card paniai">
            <?php if ($mitraBebanTertinggiPaniai): ?>
              <p class="stat-value small paniai"><?= htmlspecialchars($mitraBebanTertinggiPaniai['nama']) ?></p>
              <p class="stat-label">Beban Tertinggi - Paniai</p>
              <p class="stat-sublabel"><span class="badge bg-success"><?= $mitraBebanTertinggiPaniai['jumlah_kegiatan'] ?> Kegiatan</span></p>
            <?php else: ?>
              <p class="stat-value small paniai">-</p>
              <p class="stat-label">Beban Tertinggi - Paniai</p>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-card intanjaya">
            <?php if ($mitraBebanTertinggiIntanJaya): ?>
              <p class="stat-value small intanjaya"><?= htmlspecialchars($mitraBebanTertinggiIntanJaya['nama']) ?></p>
              <p class="stat-label">Beban Tertinggi - Intan Jaya</p>
              <p class="stat-sublabel"><span class="badge bg-orange text-white"><?= $mitraBebanTertinggiIntanJaya['jumlah_kegiatan'] ?> Kegiatan</span></p>
            <?php else: ?>
              <p class="stat-value small intanjaya">-</p>
              <p class="stat-label">Beban Tertinggi - Intan Jaya</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="beban-kerja-card">
        <h5 class="mb-4" style="color: #ff6b35; font-weight: 600;">
          <i class="bi bi-people-fill me-2"></i>Daftar Mitra Berdasarkan Beban Kegiatan
        </h5>

        <?php if (empty($dataMitra)): ?>
          <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Belum ada data mitra.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Mitra</th>
                  <th>Wilayah</th>
                  <th>Jumlah Kegiatan</th>
                  <th>Berjalan</th>
                  <th>Selesai</th>
                  <th>Progress</th>
                  <th>Detail</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; foreach ($dataMitra as $mitra): 
                  $jumlahKegiatan = (int)$mitra['jumlah_kegiatan'];
                  $kegiatanBerjalan = (int)$mitra['kegiatan_berjalan'];
                  $kegiatanSelesai = (int)$mitra['kegiatan_selesai'];
                  $progress = (float)$mitra['progress_keseluruhan'];
                  
                  $badgeClass = 'low';
                  if ($jumlahKegiatan >= 5) $badgeClass = 'high';
                  elseif ($jumlahKegiatan >= 3) $badgeClass = 'medium';
                  
                  $wilayah = strtolower(trim($mitra['wilayah_kerja'] ?? ''));
                  $wilayahBadgeClass = 'bg-secondary';
                  if (strpos($wilayah, 'paniai') !== false) $wilayahBadgeClass = 'bg-success';
                  elseif (strpos($wilayah, 'intan') !== false) $wilayahBadgeClass = 'bg-orange text-white';
                ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($mitra['nama']) ?></strong></td>
                    <td><span class="badge <?= $wilayahBadgeClass ?>" style="font-size:0.7rem;"><?= htmlspecialchars($mitra['wilayah_kerja'] ?? '-') ?></span></td>
                    <td><span class="badge-kegiatan <?= $badgeClass ?>"><?= $jumlahKegiatan ?></span></td>
                    <td><span class="badge bg-primary"><?= $kegiatanBerjalan ?></span></td>
                    <td><span class="badge bg-success"><?= $kegiatanSelesai ?></span></td>
                    <td style="min-width:120px;">
                      <div class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1"><div class="progress-bar-modern"><div class="progress-fill" style="width:<?= min($progress, 100) ?>%"></div></div></div>
                        <span style="font-weight:600;color:#ff6b35;min-width:45px;"><?= number_format($progress, 1) ?>%</span>
                      </div>
                    </td>
                    <td>
                      <?php if ($jumlahKegiatan > 0): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="showDetailKegiatan(<?= $mitra['id'] ?>, '<?= htmlspecialchars($mitra['nama'], ENT_QUOTES) ?>')"><i class="bi bi-eye"></i></button>
                      <?php else: ?>-<?php endif; ?>
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

<!-- Modal -->
<div class="modal fade" id="modalDetailKegiatan" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#ff6b35 0%,#ff8c42 100%);color:white;">
        <h5 class="modal-title"><i class="bi bi-list-ul me-2"></i>Detail Kegiatan - <span id="modalMitraNama"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="loadingDetail" class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat...</p></div>
        <div id="contentDetail" style="display:none;">
          <table class="table table-bordered table-sm">
            <thead class="table-light">
              <tr><th>No</th><th>Nama Kegiatan</th><th>Jenis</th><th>Peran</th><th>Rentang Waktu</th><th>Status</th><th>Target</th><th>Realisasi</th><th>Progress</th><th>Tim</th></tr>
            </thead>
            <tbody id="detailKegiatanBody"></tbody>
          </table>
        </div>
        <div id="errorDetail" class="alert alert-danger" style="display:none;"><span id="errorMessage"></span></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const currentFilterTahun = '<?= htmlspecialchars($filterTahun) ?>';
const currentFilterBulan = '<?= htmlspecialchars($filterBulan) ?>';

function showDetailKegiatan(mitraId, mitraNama) {
  document.getElementById('modalMitraNama').textContent = mitraNama;
  const modal = new bootstrap.Modal(document.getElementById('modalDetailKegiatan'));
  modal.show();
  
  document.getElementById('loadingDetail').style.display = 'block';
  document.getElementById('contentDetail').style.display = 'none';
  document.getElementById('errorDetail').style.display = 'none';
  
  let url = `index.php?controller=beban_kerja&action=getDetailKegiatan&mitra_id=${mitraId}`;
  if (currentFilterTahun && currentFilterTahun !== 'all') url += `&tahun=${currentFilterTahun}`;
  if (currentFilterBulan) url += `&bulan=${currentFilterBulan}`;
  
  fetch(url).then(r => r.json()).then(data => {
    document.getElementById('loadingDetail').style.display = 'none';
    if (data.success && data.data && data.data.length > 0) {
      let html = '';
      data.data.forEach((k, i) => {
        const target = parseInt(k.target) || 0;
        const realisasi = parseInt(k.realisasi) || 0;
        const progress = parseFloat(k.progress) || 0;
        const status = k.status_kegiatan || '-';
        let statusClass = 'bg-secondary';
        if (status === 'Berjalan') statusClass = 'bg-primary';
        else if (status === 'Selesai') statusClass = 'bg-success';
        
        let waktu = '-';
        if (k.rentang_waktu_mulai && k.rentang_waktu_selesai) {
          const m = new Date(k.rentang_waktu_mulai).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
          const s = new Date(k.rentang_waktu_selesai).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
          waktu = m + ' - ' + s;
        }
        
        html += `<tr>
          <td>${i+1}</td>
          <td><strong>${escapeHtml(k.nama_kegiatan||'-')}</strong></td>
          <td><span class="badge bg-info">${escapeHtml(k.jenis_kegiatan||'-')}</span></td>
          <td><span class="badge bg-secondary">${escapeHtml(k.peran||'-')}</span></td>
          <td><small>${waktu}</small></td>
          <td><span class="badge ${statusClass}">${status}</span></td>
          <td class="text-end">${target.toLocaleString('id-ID')}</td>
          <td class="text-end">${realisasi.toLocaleString('id-ID')}</td>
          <td><div class="d-flex align-items-center gap-1"><div class="flex-grow-1"><div class="progress" style="height:5px;"><div class="progress-bar bg-success" style="width:${Math.min(progress,100)}%"></div></div></div><small style="color:#ff6b35;font-weight:600;">${progress.toFixed(1)}%</small></div></td>
          <td>${escapeHtml(k.team_name||'-')}</td>
        </tr>`;
      });
      document.getElementById('detailKegiatanBody').innerHTML = html;
      document.getElementById('contentDetail').style.display = 'block';
    } else {
      document.getElementById('errorMessage').textContent = 'Tidak ada data.';
      document.getElementById('errorDetail').style.display = 'block';
    }
  }).catch(e => {
    document.getElementById('loadingDetail').style.display = 'none';
    document.getElementById('errorMessage').textContent = 'Error: ' + e.message;
    document.getElementById('errorDetail').style.display = 'block';
  });
}

function escapeHtml(t) {
  const m = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
  return t ? t.replace(/[&<>"']/g, c => m[c]) : '';
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>