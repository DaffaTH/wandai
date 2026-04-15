<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';
if (!isset($_SESSION['user'])) {
  header('Location: index.php?controller=auth&action=login');
  exit;
}


// Cek apakah user adalah mitra
$userType = $_SESSION['user']['user_type'] ?? 'user';
$isMitra = ($userType === 'mitra');
$role = $_SESSION['user']['role_name'] ?? '';

// Default tahun berjalan
$currentYear = date('Y');
$filterYear = $_GET['tahun'] ?? $currentYear;
?>

<style>
/* Modern Chart Styling */
.chart-container {
  position: relative;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.95) 100%);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 8px 32px rgba(255, 112, 67, 0.08);
  border: 1px solid rgba(255, 140, 66, 0.1);
}

.chart-title {
  font-size: 1.1rem;
  font-weight: 600;
  text-align: center;
  margin-bottom: 1rem;
  background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 25%, #ffa726 50%, #ff7043 75%, #d84315 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.chart-wrapper {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.chart-center-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
  z-index: 10;
}

.chart-center-number {
  font-size: 2.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, #ff6b35, #ff7043);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  margin-bottom: 0.2rem;
}

.chart-center-label {
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Custom Legend */
.modern-legend {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.5rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 15px;
  backdrop-filter: blur(10px);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 25px;
  font-size: 0.85rem;
  font-weight: 500;
  border: 1px solid rgba(255, 140, 66, 0.1);
  transition: all 0.3s ease;
}

.legend-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  background: rgba(255, 255, 255, 0.95);
}

.legend-color {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
  .chart-center-number {
    font-size: 2rem;
  }
  
  .modern-legend {
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
  }
  
  .legend-item {
    min-width: 120px;
    justify-content: center;
  }
}

/* Progress Bar Styling untuk Dashboard - Hanya 3 Warna */
.progress-custom {
  height: 10px;
  border-radius: 10px;
  background: rgba(255, 140, 66, 0.15);
  overflow: hidden;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.progress-bar-red {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  border-radius: 10px;
}

.progress-bar-blue {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border-radius: 10px;
}

.progress-bar-green {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  border-radius: 10px;
}

/* Styling khusus untuk modal monitoring */
.monitoring-row-pml {
  background-color: #fff3cd !important;
  font-weight: 600;
}

.monitoring-row-ppl {
  background-color: #f8f9fa;
}

/* PERBAIKAN: Hapus styling ppl-indent yang membuat garis dan icon */
.ppl-indent {
  padding-left: 1.5rem;
  /* Hapus semua styling :before yang membuat garis └─ */
}

.progress-mini {
  height: 16px;
  border-radius: 8px;
  background: rgba(0,0,0,0.1);
  overflow: hidden;
}

.clickable-kegiatan-row {
  transition: all 0.2s ease;
  cursor: pointer;
}

.clickable-kegiatan-row:hover {
  background-color: #f8f9fa !important;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Filter styling */
.filter-card {
  background: white;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border: 1px solid rgba(255, 140, 66, 0.1);
}
</style>

<div class="d-flex">
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
  
  <div class="flex-grow-1">
    <div class="content-wrapper p-4">
      <?php
      $activeTab = $activeTab ?? 'matriks';
      ?>
      <?php if ($isMitra): ?>
        <h3 class="mb-3">Dashboard Monitoring Kegiatan Mitra</h3>
        <p class="text-muted mb-4">Selamat datang, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</p>
      <?php else: ?>
        <h3 class="mb-3">
          <?= $activeTab === 'monitoring'
                ? 'Dashboard Monitoring Administrasi dan Kegiatan'
                : 'Dashboard Matriks' ?>
        </h3>
        <p class="text-muted mb-4">Selamat datang, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</p>

        <!-- Tab navigation (Matriks default, Monitoring) -->
        <ul class="nav nav-tabs mb-4" id="dashboardMainTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab==='matriks' ? 'active' : '' ?>"
               href="?controller=dashboard&tab=matriks">
              <i class="bi bi-grid-3x3-gap-fill me-1"></i> Matriks
            </a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab==='monitoring' ? 'active' : '' ?>"
               href="?controller=dashboard&tab=monitoring">
              <i class="bi bi-speedometer2 me-1"></i> Monitoring
            </a>
          </li>
        </ul>

        <?php if ($activeTab === 'matriks'): ?>
          <?php include __DIR__ . '/tab_matriks.php'; ?>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($isMitra || $activeTab === 'monitoring'): ?>
      <!-- FILTER SECTION (untuk non-mitra) -->
      <?php if (!$isMitra): ?>
      <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">
          <input type="hidden" name="controller" value="dashboard">
          <input type="hidden" name="action" value="index">

          <!-- Filter Tim -->
          <div class="col-md-5">
            <label class="form-label fw-semibold">
              <i class="bi bi-people text-primary me-1"></i> Filter Tim
            </label>
            <select name="team_id" class="form-select" onchange="this.form.submit()">
              <option value="">-- Semua Tim --</option>
              <?php foreach ($teams as $t): ?>
                <?php if ($t['name'] === 'Kepala BPS Kabupaten Paniai') continue; ?>
                <option value="<?= $t['id'] ?>" <?= (($_GET['team_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Filter Tahun -->
          <div class="col-md-4">
            <label class="form-label fw-semibold">
              <i class="bi bi-calendar3 text-primary me-1"></i> Filter Tahun
            </label>
            <select name="tahun" class="form-select" onchange="this.form.submit()">
              <option value="all" <?= ($filterYear === 'all') ? 'selected' : '' ?>>-- Semua Tahun --</option>
              <?php if (!empty($availableYears)): ?>
                <?php foreach ($availableYears as $year): ?>
                  <?php if ($year): ?>
                  <option value="<?= $year ?>" <?= ($filterYear == $year) ? 'selected' : '' ?>>
                    <?= $year ?>
                  </option>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- Fallback jika tidak ada data tahun -->
                <option value="<?= $currentYear ?>" <?= ($filterYear == $currentYear) ? 'selected' : '' ?>>
                  <?= $currentYear ?>
                </option>
              <?php endif; ?>
            </select>
          </div>

          <!-- Tombol Reset -->
          <div class="col-md-3">
            <a href="index.php?controller=dashboard&action=index" class="btn btn-outline-secondary w-100">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
            </a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <?php
      // Data untuk dashboard
      // $kegiatanTable sudah di-set dari controller (baik untuk mitra maupun user biasa)
      // Untuk mitra: kegiatan yang sudah di-assign ke mitra tersebut
      // Untuk user biasa: kegiatan berdasarkan filter tim dan role

      // PERBAIKAN: Hitung status kegiatan berdasarkan WAKTU/TANGGAL (bukan progress)
      // - Belum Mulai = tanggal hari ini < tanggal mulai kegiatan
      // - Berjalan = tanggal hari ini >= tanggal mulai DAN <= tanggal selesai
      // - Selesai = tanggal hari ini > tanggal selesai
      $statusCounts = [];
      $today = date('Y-m-d'); // Tanggal hari ini
      
      if (!empty($kegiatanTable)) {
        $mappedStatus = array_map(function($kegiatan) use ($today) {
          $tanggalMulai = $kegiatan['rentang_waktu_mulai'] ?? null;
          $tanggalSelesai = $kegiatan['rentang_waktu_selesai'] ?? null;
          
          // Jika tanggal tidak ada, default ke 'belum mulai'
          if (empty($tanggalMulai) || empty($tanggalSelesai)) {
            return 'belum mulai';
          }
          
          // Logika status berdasarkan WAKTU
          if ($today > $tanggalSelesai) {
            // Tanggal hari ini sudah melewati tanggal selesai
            return 'selesai';
          } elseif ($today >= $tanggalMulai && $today <= $tanggalSelesai) {
            // Tanggal hari ini dalam rentang waktu kegiatan
            return 'berjalan';
          } else {
            // Tanggal hari ini belum sampai tanggal mulai
            return 'belum mulai';
          }
        }, $kegiatanTable);
        
        $statusCounts = array_count_values($mappedStatus);
      }

      // Pastikan semua status ada dengan nilai 0 jika tidak ada data
      $statusCounts['belum mulai'] = $statusCounts['belum mulai'] ?? 0;
      $statusCounts['berjalan'] = $statusCounts['berjalan'] ?? 0;
      $statusCounts['selesai'] = $statusCounts['selesai'] ?? 0;

      // Hitung total & selesai dari status yang sudah diperbaiki
      $total = count($kegiatanTable);
      $selesai = $statusCounts['selesai'];
      $persenSelesai = $total > 0 ? round(($selesai / $total) * 100, 2) : 0;
      ?>

<!-- Card Mitra Beban Tertinggi (untuk non-mitra) -->
      <?php if (!$isMitra && (isset($mitraBebanTertinggiPaniai) || isset($mitraBebanTertinggiIntanJaya))): ?>
      <div class="row mb-4">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Mitra dengan Beban Kerja Tertinggi</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <!-- Paniai -->
                <div class="col-md-6 mb-3 mb-md-0">
                  <div class="p-3 rounded" style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border-left: 4px solid #22c55e;">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <small class="text-muted">Wilayah Paniai</small>
                        <?php if ($mitraBebanTertinggiPaniai): ?>
                          <h5 class="mb-0 fw-bold" style="color: #16a34a;"><?= htmlspecialchars($mitraBebanTertinggiPaniai['nama']) ?></h5>
                          <span class="badge bg-success mt-1"><?= $mitraBebanTertinggiPaniai['jumlah_kegiatan'] ?> Kegiatan</span>
                        <?php else: ?>
                          <h5 class="mb-0 text-muted">-</h5>
                          <small class="text-muted">Tidak ada data</small>
                        <?php endif; ?>
                      </div>
                      <div class="text-end">
                        <i class="bi bi-geo-alt-fill fs-2" style="color: #22c55e;"></i>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Intan Jaya -->
                <div class="col-md-6">
                  <div class="p-3 rounded" style="background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%); border-left: 4px solid #f97316;">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <small class="text-muted">Wilayah Intan Jaya</small>
                        <?php if ($mitraBebanTertinggiIntanJaya): ?>
                          <h5 class="mb-0 fw-bold" style="color: #ea580c;"><?= htmlspecialchars($mitraBebanTertinggiIntanJaya['nama']) ?></h5>
                          <span class="badge text-white mt-1" style="background-color: #f97316;"><?= $mitraBebanTertinggiIntanJaya['jumlah_kegiatan'] ?> Kegiatan</span>
                        <?php else: ?>
                          <h5 class="mb-0 text-muted">-</h5>
                          <small class="text-muted">Tidak ada data</small>
                        <?php endif; ?>
                      </div>
                      <div class="text-end">
                        <i class="bi bi-geo-alt-fill fs-2" style="color: #f97316;"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- CARD REMINDER DEADLINE (H-7) -->
      <?php if (!$isMitra && !empty($upcomingDeadlines)): ?>
        <div class="row mb-4">
          <div class="col-12">
            <div class="card border-warning shadow-sm">
              <div class="card-header bg-warning text-dark d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                <h6 class="mb-0 fw-bold">Peringatan Deadline Kegiatan</h6>
                <button class="btn btn-sm btn-outline-dark ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReminder" aria-expanded="true">
                  <i class="bi bi-chevron-down"></i>
                </button>
              </div>
              <div class="collapse show" id="collapseReminder">
                <div class="card-body">
                  <div class="alert alert-warning mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong><?= count($upcomingDeadlines) ?> kegiatan</strong> mendekati atau melewati deadline. Harap segera ditindaklanjuti!
                  </div>
                  
                  <div class="row">
                    <?php foreach ($upcomingDeadlines as $reminder): 
                      $urgencyColor = match($reminder['urgency_level']) {
                        'overdue' => 'danger',
                        'critical' => 'danger',  
                        'warning' => 'warning',
                        default => 'info'
                      };
                      
                      $urgencyText = match($reminder['urgency_level']) {
                        'overdue' => 'Terlambat ' . abs($reminder['days_remaining']) . ' hari',
                        'critical' => 'Sisa ' . $reminder['days_remaining'] . ' hari',
                        'warning' => 'Sisa ' . $reminder['days_remaining'] . ' hari',
                        default => 'Normal'
                      };
                      
                      $urgencyIcon = match($reminder['urgency_level']) {
                        'overdue' => 'bi-x-circle-fill',
                        'critical' => 'bi-exclamation-circle-fill',
                        'warning' => 'bi-clock-fill',
                        default => 'bi-info-circle'
                      };
                    ?>
                      <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 border-<?= $urgencyColor ?> shadow-sm">
                          <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                              <span class="badge bg-<?= $urgencyColor ?> d-flex align-items-center">
                                <i class="<?= $urgencyIcon ?> me-1"></i>
                                <?= htmlspecialchars($urgencyText) ?>
                              </span>
                              <small class="text-muted"><?= htmlspecialchars($reminder['team_name'] ?? '-') ?></small>
                            </div>

                            <h6 class="card-title text-truncate mb-1" title="<?= htmlspecialchars($reminder['nama_kegiatan']) ?>">
                              <?= htmlspecialchars($reminder['nama_kegiatan']) ?>
                            </h6>

                            <?php
                              $jenisR = trim((string)($reminder['jenis_kegiatan'] ?? ''));
                              if ($jenisR !== ''):
                                $jenisColorsR = [
                                    'Pelatihan/Briefing' => 'bg-info text-dark',
                                    'Updating/Listing'   => 'bg-primary',
                                    'Pendataan'          => 'bg-success',
                                    'Pengolahan'         => 'bg-warning text-dark',
                                ];
                                $clsR = $jenisColorsR[$jenisR] ?? 'bg-secondary';
                            ?>
                              <div class="mb-2">
                                <span class="badge <?= $clsR ?>"><?= htmlspecialchars($jenisR) ?></span>
                              </div>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                              <small class="text-muted">
                                <i class="bi bi-calendar-event me-1"></i>
                                Target: <?= !empty($reminder['rentang_waktu_selesai']) ? date('d M Y', strtotime($reminder['rentang_waktu_selesai'])) : '-' ?>
                              </small>
                            </div>
                            
                            <div class="mb-2">
                              <small class="text-muted d-block">Progress: <?= $reminder['progress'] ?>%</small>
                              <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?= $reminder['progress'] >= 80 ? 'success' : ($reminder['progress'] >= 50 ? 'warning' : 'danger') ?>" 
                                     style="width: <?= $reminder['progress'] ?>%"></div>
                              </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                              <small class="text-muted">
                                <?= $reminder['realisasi'] ?>/<?= $reminder['target'] ?> <?= htmlspecialchars($reminder['satuan'] ?? '') ?>
                              </small>
                              <button class="btn btn-sm btn-outline-<?= $urgencyColor ?>" 
                                      onclick="openMonitoringDetail(<?= $reminder['id'] ?>)"
                                      title="Lihat detail kegiatan">
                                <i class="bi bi-eye"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- PERBAIKAN: Kembalikan 3 card utama dengan struktur yang benar -->
      <div class="row">
        <!-- Card 1: Ringkasan jumlah -->
        <div class="col-md-4 mb-3">
          <div class="card text-center shadow-sm h-100">
            <div class="card-body">
              <h6>JUMLAH KEGIATAN</h6>
              <h2 class="fw-bold"><?= $total ?></h2>
              <p class="mb-1">KEGIATAN SELESAI</p>
              <p class="fw-bold text-success"><?= $selesai ?> (<?= $persenSelesai ?>%)</p>
              <div class="progress progress-custom">
                <?php if ($persenSelesai >= 100): ?>
                  <div class="progress-bar progress-bar-green" style="width: 100%;"></div>
                <?php elseif ($persenSelesai > 0): ?>
                  <div class="progress-bar progress-bar-blue" style="width: <?= $persenSelesai ?>%;"></div>
                <?php else: ?>
                  <div class="progress-bar progress-bar-red" style="width: 5%;"></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 2: Donut Chart -->
        <div class="col-md-5 mb-3">
          <div class="card shadow-sm h-100">
            <div class="card-body chart-container">
              <h6 class="chart-title">STATUS KEGIATAN</h6>
              <?php if ($total > 0): ?>
                <div class="chart-wrapper">
                  <canvas id="donutChart" style="max-height:300px; max-width:400px;"></canvas>
                </div>
              <?php else: ?>
                <div class="text-center py-5">
                  <i class="bi bi-pie-chart display-4 text-muted"></i>
                  <?php if ($isMitra): ?>
                    <p class="text-muted mt-3">Belum ada kegiatan yang ditugaskan</p>
                    <small class="text-muted">Menunggu assignment dari operator</small>
                  <?php else: ?>
                    <p class="text-muted mt-3">Belum ada data kegiatan</p>
                    <small class="text-muted">Silakan cek filter atau tambahkan kegiatan baru</small>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Card 3: Ringkasan status kegiatan -->
        <div class="col-md-3 mb-3">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>Status</th>
                    <th>Jumlah</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($total > 0): ?>
                    <?php foreach ($statusCounts as $status => $count):
                      $persenKeg = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                    ?>
                      <tr>
                        <td><?= ucfirst(htmlspecialchars($status)) ?></td>
                        <td><?= $count ?> (<?= $persenKeg ?>%)</td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td>Belum Mulai</td>
                      <td>0 (0%)</td>
                    </tr>
                    <tr>
                      <td>Berjalan</td>
                      <td>0 (0%)</td>
                    </tr>
                    <tr>
                      <td>Selesai</td>
                      <td>0 (0%)</td>
                    </tr>
                  <?php endif; ?>
                  <tr class="fw-bold">
                    <td>Total</td>
                    <td><?= $total ?> (100%)</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabel daftar kegiatan dengan kolom Realisasi & Target -->
      <div class="row mt-3">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <?php if ($isMitra): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6>Daftar Kegiatan Saya</h6>
                  <?php if ($total > 0): ?>
                    <span class="badge bg-primary"><?= $total ?> Kegiatan</span>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6>Daftar Kegiatan</h6>
                  <?php if ($total > 0): ?>
                    <span class="badge bg-primary"><?= $total ?> Kegiatan</span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              
              <div class="table-responsive">
                <table class="table table-bordered table-striped">
                  <thead class="table-light">
                    <tr>
                      <th>No</th>
                      <th>Nama Kegiatan</th>
                      <th>Jenis</th>
                      <?php if (!$isMitra): ?>
                        <th>Tim</th>
                      <?php endif; ?>
                      <th>Rentang Waktu</th>
                      <th>Realisasi</th>
                      <th>Target</th>
                      <th>Progres</th>
                      <th>Detail</th>
                      <?php if ($isMitra): ?>
                        <th>Tim</th>
                      <?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($kegiatanTable)): ?>
                      <?php $no = 1; foreach ($kegiatanTable as $row): ?>
                        <tr data-kegiatan-id="<?= $row['id'] ?>" 
                            class="clickable-kegiatan-row" 
                            title="Klik untuk melihat monitoring detail PML/PPL">
                          <td><?= $no++ ?></td>
                          <td><?= htmlspecialchars($row['nama_kegiatan']) ?></td>
                          <td>
                            <?php
                              $jenisRow = trim((string)($row['jenis_kegiatan'] ?? ''));
                              if ($jenisRow === '') {
                                  echo '<span class="text-muted">—</span>';
                              } else {
                                  $jenisColors = [
                                      'Pelatihan/Briefing' => 'bg-info text-dark',
                                      'Updating/Listing'   => 'bg-primary',
                                      'Pendataan'          => 'bg-success',
                                      'Pengolahan'         => 'bg-warning text-dark',
                                  ];
                                  $cls = $jenisColors[$jenisRow] ?? 'bg-secondary';
                                  echo '<span class="badge ' . $cls . '">' . htmlspecialchars($jenisRow) . '</span>';
                              }
                            ?>
                          </td>
                          <?php if (!$isMitra): ?>
                            <td>
                              <span class="badge bg-secondary">
                                <?= htmlspecialchars($row['team_name'] ?? 'Tidak Ada Tim') ?>
                              </span>
                            </td>
                          <?php endif; ?>
                          <td><?php
                            // PERBAIKAN: Gunakan field name yang benar sesuai database
                            $tanggalMulai   = $row['rentang_waktu_mulai']   ?? '';
                            $tanggalSelesai = $row['rentang_waktu_selesai'] ?? '';

                            if (!empty($tanggalMulai) && !empty($tanggalSelesai)) {
                              echo '<i class="bi bi-calendar-event text-primary"></i> ';
                              echo date('d M Y', strtotime($tanggalMulai)) . ' s.d ' . date('d M Y', strtotime($tanggalSelesai));

                              // Badge peringatan deadline
                              $progressVal = (float)($row['progress'] ?? 0);
                              if ($progressVal < 100) {
                                  $today        = strtotime(date('Y-m-d'));
                                  $deadlineTs   = strtotime($tanggalSelesai);
                                  $daysDiff     = (int) floor(($deadlineTs - $today) / 86400);

                                  if ($daysDiff < 0) {
                                      $absDays = abs($daysDiff);
                                      echo '<br><span class="badge bg-danger mt-1"><i class="bi bi-exclamation-triangle-fill"></i> Terlambat ' . $absDays . ' hari</span>';
                                  } elseif ($daysDiff === 0) {
                                      echo '<br><span class="badge bg-danger mt-1"><i class="bi bi-alarm-fill"></i> Deadline hari ini</span>';
                                  } elseif ($daysDiff <= 3) {
                                      echo '<br><span class="badge bg-warning text-dark mt-1"><i class="bi bi-alarm"></i> ' . $daysDiff . ' hari lagi</span>';
                                  } elseif ($daysDiff <= 7) {
                                      echo '<br><span class="badge bg-warning text-dark mt-1"><i class="bi bi-clock"></i> ' . $daysDiff . ' hari lagi</span>';
                                  }
                              } else {
                                  echo '<br><span class="badge bg-success mt-1"><i class="bi bi-check-circle-fill"></i> Selesai</span>';
                              }
                            } else {
                              echo '-';
                            }
                          ?></td>
                          <td class="text-center"><?= $row['realisasi'] ?? 0 ?></td>
                          <td class="text-center"><?= $row['target'] ?? 0 ?></td>
                          <td>
                            <?php 
                            $progress = (float)($row['progress'] ?? 0);
                            $progressColor = $progress >= 100 ? 'success' : ($progress >= 50 ? 'warning' : 'danger');
                            ?>
                            <div class="progress" style="height: 20px;">
                              <div class="progress-bar bg-<?= $progressColor ?>" 
                                   role="progressbar" 
                                   style="width: <?= $progress ?>%;" 
                                   aria-valuenow="<?= $progress ?>" 
                                   aria-valuemin="0" 
                                   aria-valuemax="100">
                                <?= number_format($progress, 2) ?>%
                              </div>
                            </div>
                          </td>
                          <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="openMonitoringDetail(<?= $row['id'] ?>)"
                                    title="Lihat detail monitoring">
                              <i class="bi bi-eye"></i>
                            </button>
                          </td>
                          <?php if ($isMitra): ?>
                            <td>
                              <span class="badge bg-info">
                                <?= htmlspecialchars($row['team_name'] ?? '-') ?>
                              </span>
                            </td>
                          <?php endif; ?>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="<?= $isMitra ? 9 : 9 ?>" class="text-center py-4 text-muted">
                          <i class="bi bi-inbox display-6"></i>
                          <p class="mt-2 mb-0">Belum ada data</p>
                          <?php if (!$isMitra): ?>
                            <small>Silakan cek filter atau tambahkan kegiatan baru di menu Input Kegiatan</small>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; /* monitoring tab wrapper */ ?>
    </div>
  </div>
</div>

<!-- Modal Monitoring Detail -->
<div class="modal fade" id="modalMonitoringDetail" tabindex="-1" aria-labelledby="modalMonitoringDetailLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalMonitoringDetailLabel">
          <i class="bi bi-bar-chart-line me-2"></i>Detail Monitoring Kegiatan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Loading indicator -->
        <div id="monitoring-loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-3 text-muted">Memuat data monitoring...</p>
        </div>
        
        <!-- Content -->
        <div id="monitoring-content" style="display: none;">
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th>No</th>
                  <th>PML</th>
                  <th>PPL</th>
                  <th>Realisasi</th>
                  <th>Target</th>
                  <th>Progress</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody id="monitoring-table-body">
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Empty state -->
        <div id="monitoring-empty" style="display: none;" class="text-center py-5">
          <i class="bi bi-inbox display-4 text-muted"></i>
          <h6 class="mt-3">Tidak ada data</h6>
          <p class="text-muted">Data monitoring tidak tersedia</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit Komentar (untuk Kepala) -->
<?php if ($role === 'kepala'): ?>
<div class="modal fade" id="modalEditKomentar" tabindex="-1" aria-labelledby="modalEditKomentarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="modalEditKomentarLabel">
          <i class="bi bi-chat-left-text me-2"></i>Edit Komentar Petugas
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditKomentar">
        <div class="modal-body">
          <input type="hidden" id="komentar-kegiatan-petugas-id">
          
          <div class="mb-3">
            <label class="form-label">Nama Petugas</label>
            <input type="text" id="komentar-nama-petugas" class="form-control" readonly>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Peran</label>
            <input type="text" id="komentar-peran" class="form-control" readonly>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Komentar/Catatan</label>
            <textarea id="komentar-text" name="komentar" class="form-control" rows="4" placeholder="Tambahkan komentar atau catatan..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">
            <i class="bi bi-save me-1"></i> Simpan Komentar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Donut Chart
<?php if (!$isMitra && $total > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('donutChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Belum Mulai', 'Berjalan', 'Selesai'],
                datasets: [{
                    data: [
                        <?= $statusCounts['belum mulai'] ?? 0 ?>,
                        <?= $statusCounts['berjalan'] ?? 0 ?>,
                        <?= $statusCounts['selesai'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        '#ef4444',  // Red - Belum Mulai
                        '#3b82f6',  // Blue - Berjalan
                        '#22c55e'   // Green - Selesai
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});
<?php endif; ?>

// Variables untuk modal
let currentModal = null;

// Function buka modal monitoring detail - DIPERBAIKI
function openMonitoringDetail(kegiatanId) {
    console.log('Opening monitoring detail for kegiatan:', kegiatanId);
    
    // Cek apakah Bootstrap tersedia
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded!');
        alert('Error: Bootstrap tidak tersedia. Silakan refresh halaman.');
        return;
    }
    
    // Close existing modal if any
    if (currentModal) {
        try {
            currentModal.hide();
        } catch(e) {
            console.log('Error hiding modal:', e);
        }
        currentModal = null;
    }
    
    const modalElement = document.getElementById('modalMonitoringDetail');
    if (!modalElement) {
        console.error('Modal element not found');
        alert('Error: Modal element tidak ditemukan!');
        return;
    }
    
    // Reset state
    const loadingEl = document.getElementById('monitoring-loading');
    const contentEl = document.getElementById('monitoring-content');
    const emptyEl = document.getElementById('monitoring-empty');
    
    if (loadingEl) loadingEl.style.display = 'block';
    if (contentEl) contentEl.style.display = 'none';
    if (emptyEl) emptyEl.style.display = 'none';
    
    try {
        // Create new modal instance
        currentModal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true
        });
        
        // Handle modal hidden
        modalElement.addEventListener('hidden.bs.modal', function () {
            console.log('Modal hidden, cleaning up');
            currentModal = null;
            
            // Force remove backdrop jika masih ada
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Pastikan body class modal-open dihapus
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, { once: true });
        
        // Show modal
        currentModal.show();
        
        // Load data
        loadMonitoringData(kegiatanId);
    } catch(e) {
        console.error('Error creating/showing modal:', e);
        alert('Error membuka modal: ' + e.message);
    }
}

// Function load data AJAX - TANPA JQUERY
function loadMonitoringData(kegiatanId) {
    console.log('Loading monitoring data for kegiatan:', kegiatanId);
    
    const loadingEl = document.getElementById('monitoring-loading');
    const contentEl = document.getElementById('monitoring-content');
    const emptyEl = document.getElementById('monitoring-empty');
    
    // Show loading
    if (loadingEl) loadingEl.style.display = 'block';
    if (contentEl) contentEl.style.display = 'none';
    if (emptyEl) emptyEl.style.display = 'none';
    
    // AJAX request menggunakan fetch
    fetch(`index.php?controller=dashboard&action=getMonitoringDetail&kegiatan_id=${kegiatanId}`)
        .then(response => response.json())
        .then(data => {
            console.log('AJAX response:', data);
            if (loadingEl) loadingEl.style.display = 'none';
            
            if (data.success && data.data && data.data.length > 0) {
                renderMonitoringTable(data.data);
                if (contentEl) contentEl.style.display = 'block';
            } else {
                if (emptyEl) {
                    emptyEl.style.display = 'block';
                    const h6 = emptyEl.querySelector('h6');
                    const p = emptyEl.querySelector('p');
                    if (h6) h6.textContent = 'Belum ada petugas yang ditugaskan';
                    if (p) p.textContent = 'Kegiatan ini belum memiliki assignment petugas PML dan PPL.';
                }
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
            if (loadingEl) loadingEl.style.display = 'none';
            if (emptyEl) {
                emptyEl.style.display = 'block';
                const h6 = emptyEl.querySelector('h6');
                const p = emptyEl.querySelector('p');
                if (h6) h6.textContent = 'Gagal memuat data monitoring';
                if (p) p.innerHTML = `Terjadi kesalahan: <strong>${error.message}</strong>`;
            }
        });
}

// PERBAIKAN: Function render table tanpa icon dan garis
function renderMonitoringTable(data) {
    const tbody = document.getElementById('monitoring-table-body');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox"></i> Belum ada petugas yang ditugaskan
                </td>
            </tr>
        `;
        return;
    }
    
    // Group data by PML
    const pmlGroups = {};
    
    // Collect PML first
    data.forEach(petugas => {
        if (petugas.peran === 'PML') {
            pmlGroups[petugas.id] = {
                pml: petugas,
                ppl: []
            };
        }
    });
    
    // Add PPL to their PML groups
    data.forEach(petugas => {
        if (petugas.peran === 'PPL' && petugas.pml_id && pmlGroups[petugas.pml_id]) {
            pmlGroups[petugas.pml_id].ppl.push(petugas);
        }
    });
    
    let rowNumber = 1;
    
    // Render PML and PPL
    Object.values(pmlGroups).forEach(group => {
        if (!group.pml) return;
        
        const pml = group.pml;
        const pplCount = group.ppl.length;
        
        // PERBAIKAN: PML Row - hapus kolom Aksi, ubah Keterangan jadi jumlah PPL
        const pmlRow = document.createElement('tr');
        pmlRow.className = 'monitoring-row-pml';
        pmlRow.innerHTML = `
            <td class="fw-bold">${rowNumber++}</td>
            <td class="fw-bold">
                ${pml.petugas_nama}
                <br><small class="text-muted">${pml.petugas_jenis}</small>
            </td>
            <td class="text-muted">-</td>
            <td class="fw-bold">${pml.realisasi}</td>
            <td class="fw-bold">${pml.target}</td>
            <td>
                <div class="progress progress-mini">
                    <div class="progress-bar ${getProgressColor(pml.progress)}" 
                         style="width: ${pml.progress}%"></div>
                </div>
                <small>${pml.progress}%</small>
            </td>
            <td>
                <span class="badge bg-info">${pplCount} PPL</span>
            </td>
        `;
        tbody.appendChild(pmlRow);
        
        // PERBAIKAN: PPL Rows - hapus kolom Aksi, ubah Keterangan jadi PML: nama_pml
        group.ppl.forEach(ppl => {
            const pplRow = document.createElement('tr');
            pplRow.className = 'monitoring-row-ppl';
            pplRow.innerHTML = `
                <td>${rowNumber++}</td>
                <td class="text-muted">-</td>
                <td class="ppl-indent">
                    ${ppl.petugas_nama}
                    <br><small class="text-muted">${ppl.petugas_jenis}</small>
                </td>
                <td>${ppl.realisasi}</td>
                <td>${ppl.target}</td>
                <td>
                    <div class="progress progress-mini">
                        <div class="progress-bar ${getProgressColor(ppl.progress)}" 
                             style="width: ${ppl.progress}%"></div>
                    </div>
                    <small>${ppl.progress}%</small>
                </td>
                <td>
                    <small class="text-muted">PML: ${pml.petugas_nama}</small>
                </td>
            `;
            tbody.appendChild(pplRow);
        });
    });
}

// Helper functions
function getProgressColor(progress) {
    if (progress >= 100) return 'bg-success';
    if (progress >= 80) return 'bg-info';
    if (progress >= 60) return 'bg-warning';
    return 'bg-danger';
}

// Function edit komentar
function editKomentar(kegiatanPetugasId, namaPetugas, peran, komentar) {
    const userRole = '<?= $role ?>';
    if (userRole !== 'kepala') {
        alert('Hanya Kepala yang dapat mengedit komentar');
        return;
    }
    
    const idField = document.getElementById('komentar-kegiatan-petugas-id');
    const namaField = document.getElementById('komentar-nama-petugas');
    const peranField = document.getElementById('komentar-peran');
    const komentarField = document.getElementById('komentar-text');
    
    if (idField) idField.value = kegiatanPetugasId;
    if (namaField) namaField.value = namaPetugas;
    if (peranField) peranField.value = peran;
    if (komentarField) komentarField.value = komentar;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditKomentar'));
    modal.show();
}

// Handle form submit edit komentar
const form = document.getElementById('formEditKomentar');
if (form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('kegiatan_petugas_id', document.getElementById('komentar-kegiatan-petugas-id').value);
        
        fetch('index.php?controller=dashboard&action=updateKomentar', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Komentar berhasil disimpan');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditKomentar'));
                if (modal) modal.hide();
            } else {
                alert('Error: ' + (data.error || 'Gagal menyimpan komentar'));
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error.message);
        });
    });
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>