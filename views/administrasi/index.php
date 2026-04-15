<?php
/**
 * ========================================
 * FILE: views/administrasi/index.php
 * DATA ADMINISTRASI & VERIFIKASI PEMBAYARAN
 * UPDATED: Context-aware title/badge display
 * - context=data → Data Administrasi (untuk semua role)
 * - context=verifikasi → Verifikasi Pembayaran (untuk bendahara)
 * ========================================
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';

// Role detection
$roleId = $_SESSION['user']['role_id'] ?? 4;
$roleMap = [1 => 'admin', 2 => 'ppk', 3 => 'bendahara', 4 => 'operator', 5 => 'kepala', 6 => 'kasubbag'];
$role = $roleMap[$roleId] ?? 'operator';
$isAdmin = ($roleId == 1);
$isBendahara = ($role === 'bendahara');
$canManagePayment = ($isBendahara || $isAdmin);

// Context detection for title/badge
$context = $_GET['context'] ?? '';

// Determine page title and badge based on context AND role
$pageTitle = 'Data Administrasi';
$pageBadge = '';
$pageBadgeClass = '';
$pageIcon = 'bi-database-check';

if ($context === 'verifikasi') {
    // Verifikasi Pembayaran context - untuk Bendahara
    $pageTitle = 'Verifikasi Pembayaran Administrasi';
    $pageBadge = 'Bendahara';
    $pageBadgeClass = 'bg-info';
    $pageIcon = 'bi-cash-coin';
} elseif ($context === 'data') {
    // Data Administrasi context - badge sesuai role yang login
    $pageTitle = 'Data Administrasi';
    $pageIcon = 'bi-database-check';
    switch ($role) {
        case 'admin':
            $pageBadge = 'Admin';
            $pageBadgeClass = 'bg-dark';
            break;
        case 'kepala':
            $pageBadge = 'Kepala';
            $pageBadgeClass = 'bg-success';
            break;
        case 'ppk':
            $pageBadge = 'PPK';
            $pageBadgeClass = 'bg-warning text-dark';
            break;
        case 'operator':
            $pageBadge = 'Operator';
            $pageBadgeClass = 'bg-secondary';
            break;
        case 'bendahara':
            $pageBadge = 'Bendahara';
            $pageBadgeClass = 'bg-info';
            break;
        default:
            $pageBadge = '';
    }
} else {
    // Default (no context) - use role-based title for backward compatibility
    if (($role ?? '') == 'mitra') {
        $pageTitle = 'Data Administrasi Saya';
        $pageBadge = '';
    } elseif (($role ?? '') == 'bendahara') {
        $pageTitle = 'Verifikasi Pembayaran Administrasi';
        $pageBadge = 'Bendahara';
        $pageBadgeClass = 'bg-info';
    } elseif (($role ?? '') == 'ppk') {
        $pageTitle = 'Verifikasi Administrasi PPK';
        $pageBadge = 'PPK';
        $pageBadgeClass = 'bg-warning text-dark';
    } elseif ($isAdmin) {
        $pageTitle = 'Administrasi';
        $pageBadge = 'Admin';
        $pageBadgeClass = 'bg-dark';
    } else {
        $pageTitle = 'Data Administrasi';
        // Set badge based on actual role
        switch ($role) {
            case 'kepala':
                $pageBadge = 'Kepala';
                $pageBadgeClass = 'bg-success';
                break;
            case 'operator':
                $pageBadge = 'Operator';
                $pageBadgeClass = 'bg-secondary';
                break;
        }
    }
}
?>

<div class="d-flex">
  <!-- Sidebar -->
  <div class="sidebar">
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
  </div>

  <!-- Main -->
  <div class="flex-grow-1">
    <!-- Content -->
    <div class="content-wrapper">
      <h4 class="mb-4">
        <i class="bi <?= $pageIcon ?>"></i>
        <?= htmlspecialchars($pageTitle) ?>
        <?php if (!empty($pageBadge)): ?>
          <span class="badge <?= $pageBadgeClass ?> ms-2"><?= htmlspecialchars($pageBadge) ?></span>
        <?php endif; ?>
      </h4>

      <!-- Alert Container untuk Session Messages -->
      <div id="alertContainer">
        <?php if (!empty($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Card Filter -->
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <form method="GET" action="index.php" class="row g-2">
            <input type="hidden" name="controller" value="administrasi">
            <input type="hidden" name="action" value="index">
            <!-- PENTING: Pertahankan context parameter saat filter -->
            <input type="hidden" name="context" value="<?= htmlspecialchars($context) ?>">

            <!-- Filter Tim - Semua role bisa lihat semua tim di Data Administrasi -->
            <div class="col-md-2">
              <select name="team_id" class="form-select">
                <option value="">-- Semua Tim --</option>
                <?php foreach ($teams as $t): ?>
                  <?php if ($t['name'] == "Kepala BPS Kabupaten Paniai") continue; ?>
                  <option value="<?= $t['id'] ?>" <?= (isset($_GET['team_id']) && $_GET['team_id'] == $t['id']) ? 'selected' : '' ?>>
                    <?= $t['name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if ($context === 'data'): ?>
            <!-- Filter Tahun (hanya untuk context=data) -->
            <div class="col-md-2">
              <select name="tahun" class="form-select">
                <option value="">-- Semua Tahun --</option>
                <?php 
                $currentYear = date('Y');
                for ($y = $currentYear + 1; $y >= $currentYear - 5; $y--): 
                ?>
                  <option value="<?= $y ?>" <?= (isset($_GET['tahun']) && $_GET['tahun'] == $y) ? 'selected' : '' ?>>
                    <?= $y ?>
                  </option>
                <?php endfor; ?>
              </select>
            </div>
            <?php endif; ?>

            <!-- Filter Bulan -->
            <div class="col-md-2">
              <input type="month" name="bulan" class="form-control" value="<?= $_GET['bulan'] ?? '' ?>" placeholder="Bulan">
            </div>

            <!-- Filter Jenis Kegiatan - Dynamic dari Database -->
            <div class="col-md-2">
              <select name="jenis_kegiatan" class="form-select">
                <option value="">-- Semua Jenis --</option>
                <?php if (!empty($jenisKegiatanList)): ?>
                  <?php foreach ($jenisKegiatanList as $jenis): ?>
                    <option value="<?= htmlspecialchars($jenis) ?>" <?= (isset($_GET['jenis_kegiatan']) && $_GET['jenis_kegiatan'] === $jenis) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($jenis) ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <!-- Fallback jika $jenisKegiatanList tidak tersedia -->
                  <option value="Pendataan" <?= (isset($_GET['jenis_kegiatan']) && $_GET['jenis_kegiatan'] === 'Pendataan') ? 'selected' : '' ?>>Pendataan</option>
                  <option value="Updating" <?= (isset($_GET['jenis_kegiatan']) && $_GET['jenis_kegiatan'] === 'Updating') ? 'selected' : '' ?>>Updating</option>
                  <option value="Pengolahan" <?= (isset($_GET['jenis_kegiatan']) && $_GET['jenis_kegiatan'] === 'Pengolahan') ? 'selected' : '' ?>>Pengolahan</option>
                  <option value="Perjalanan Dinas" <?= (isset($_GET['jenis_kegiatan']) && $_GET['jenis_kegiatan'] === 'Perjalanan Dinas') ? 'selected' : '' ?>>Perjalanan Dinas</option>
                <?php endif; ?>
              </select>
            </div>

            <!-- Tombol Filter & Reset -->
            <div class="col-md-<?= $context === 'data' ? '4' : '4' ?>">
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-funnel-fill"></i> Filter
                </button>
                <a href="index.php?controller=administrasi&action=index&context=<?= htmlspecialchars($context) ?>" class="btn btn-secondary">
                  <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Keterangan Warna (hanya untuk context=data) -->
      <?php if ($context === 'data'): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
          <div>
            <small class="text-muted"><strong>Keterangan Status Kegiatan:</strong></small>
            <span class="badge bg-secondary ms-2"><i class="bi bi-clock-history"></i> Belum Mulai</span>
            <span class="badge bg-primary ms-2"><i class="bi bi-play-circle"></i> Berjalan</span>
            <span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Selesai</span>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Card Data Tabel -->
      <div class="card shadow-sm">
        <div class="card-body">
          <?php if (empty($administrasi)): ?>
            <!-- Empty State -->
            <div class="text-center py-5">
              <i class="bi bi-inbox display-4 text-muted"></i>
              <?php if (($role ?? '') == 'mitra'): ?>
                <h5 class="mt-3 text-muted">Belum ada dokumen administrasi yang Anda upload</h5>
                <p class="text-muted">Klik menu "Input Administrasi" untuk mengunggah dokumen pertama Anda</p>
                <a href="index.php?controller=administrasi&action=form" class="btn btn-primary">
                  <i class="bi bi-plus-circle"></i> Upload Dokumen
                </a>
              <?php elseif ($context === 'verifikasi' || ($role ?? '') == 'bendahara'): ?>
                <h5 class="mt-3 text-muted">Belum ada dokumen yang perlu diverifikasi pembayaran</h5>
                <p class="text-muted">Dokumen akan muncul setelah PPK melakukan approve</p>
              <?php elseif ($context === 'data'): ?>
                <h5 class="mt-3 text-muted">Belum ada kegiatan yang ditemukan</h5>
                <p class="text-muted">Silakan tambahkan kegiatan melalui menu "Input Kegiatan" atau ubah filter pencarian</p>
              <?php elseif (($role ?? '') == 'ppk'): ?>
                <h5 class="mt-3 text-muted">Belum ada dokumen yang perlu diverifikasi</h5>
                <p class="text-muted">Dokumen akan muncul setelah ada yang diupload</p>
              <?php else: ?>
                <h5 class="mt-3 text-muted">Belum ada data administrasi</h5>
                <p class="text-muted">Data akan muncul setelah ada dokumen yang diupload</p>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <!-- Tabel Data -->
            <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle" id="datatable">
              <thead class="table-dark">
                <tr>
                  <th>No</th>
                  <th>Nama Kegiatan</th>
                  <th>Tim</th>
                  <th>Jenis Kegiatan</th>
                  <?php if ($context === 'data'): ?>
                    <th>Tanggal Kegiatan</th>
                    <th>Status Kegiatan</th>
                  <?php endif; ?>
                  <th>Total Petugas</th>
                  <th>Dokumen Kegiatan</th>
                  <th>Dokumen Per Petugas</th>
                  <th><?= ($context === 'verifikasi') ? 'Verifikasi Pembayaran' : 'Status Pembayaran' ?></th>
                  <th>Tanggal Lunas</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $no = 1;
                foreach ($administrasi as $item): 
                  // Cek apakah current user adalah yang upload dokumen ini
                  $canDelete = false;
                  if (isset($current_user_id) && isset($current_user_type)) {
                    $canDelete = ($item['uploaded_by'] == $current_user_id && $item['uploaded_by_type'] == $current_user_type);
                  }
                  
                  // VALIDASI RULE: Jika PPK approved dan Bendahara sudah bayar, tidak bisa delete
                  $statusPPK = $item['status_ppk'] ?? null;
                  $statusBendahara = $item['status_bendahara'] ?? null;
                  $isFullyApproved = ($statusPPK == 'approved' && $statusBendahara == 'approved');
                  if ($isFullyApproved) {
                    $canDelete = false; // Override: tidak bisa delete jika sudah fully approved
                  }
                  
                  // Admin bisa melakukan semua aksi
                  $canEditPPK = ($role == 'ppk') || $isAdmin;
                  $canEditBendahara = ($role == 'bendahara') || $isAdmin;

                  // === HITUNG POSISI DOKUMEN ===
                  $verifikasiStatus = $item['verifikasi_status'] ?? null;
                  $posisi = '';
                  $posisiBadge = '';
                  $posisiIcon = '';
                  $isDitolak = false; // Flag untuk menandai ada penolakan
                  
                  if ($verifikasiStatus) {
                    // Gunakan status dari tabel verifikasi_administrasi
                    switch ($verifikasiStatus) {
                      case 'belum_submit':
                        $posisi = 'Belum Submit'; $posisiBadge = 'bg-light text-dark border'; $posisiIcon = 'bi-dash-circle'; break;
                      case 'pending_operator_tim':
                      case 'approved_operator_tim':
                      case 'rejected_operator_tim':
                        $posisi = 'Operator Tim'; $posisiBadge = 'bg-secondary'; $posisiIcon = 'bi-person-gear'; break;
                      case 'pending_kepala':
                        $posisi = 'Kepala'; $posisiBadge = 'bg-primary'; $posisiIcon = 'bi-person-check'; break;
                      case 'approved_kepala':
                      case 'pending_ppk':
                        $posisi = 'PPK'; $posisiBadge = 'bg-warning text-dark'; $posisiIcon = 'bi-clipboard-check'; break;
                      case 'rejected_kepala':
                        // Ditolak kepala berarti dikembalikan ke Operator Tim untuk diperbaiki
                        $posisi = 'Operator Tim'; $posisiBadge = 'bg-danger'; $posisiIcon = 'bi-arrow-return-left'; $isDitolak = true; break;
                      case 'rejected_ppk':
                        // Ditolak PPK berarti dikembalikan ke Operator Tim untuk diperbaiki
                        $posisi = 'Operator Tim'; $posisiBadge = 'bg-danger'; $posisiIcon = 'bi-arrow-return-left'; $isDitolak = true; break;
                      case 'rejected_bendahara':
                        // Ditolak bendahara berarti dikembalikan ke Operator Tim untuk diperbaiki
                        $posisi = 'Operator Tim'; $posisiBadge = 'bg-danger'; $posisiIcon = 'bi-arrow-return-left'; $isDitolak = true; break;
                      case 'approved_ppk':
                        $posisi = 'Bendahara'; $posisiBadge = 'bg-info'; $posisiIcon = 'bi-cash-coin'; break;
                      case 'completed':
                        $posisi = 'Selesai'; $posisiBadge = 'bg-success'; $posisiIcon = 'bi-check-circle-fill'; break;
                      default:
                        $posisi = 'Belum Submit'; $posisiBadge = 'bg-light text-dark border'; $posisiIcon = 'bi-dash-circle';
                    }
                  } else {
                    // Fallback: tentukan dari status_ppk dan status_bendahara
                    if ($statusPPK == 'approved' && $statusBendahara == 'approved') {
                      $posisi = 'Selesai'; $posisiBadge = 'bg-success'; $posisiIcon = 'bi-check-circle-fill';
                    } elseif ($statusPPK == 'approved') {
                      $posisi = 'Bendahara'; $posisiBadge = 'bg-info'; $posisiIcon = 'bi-cash-coin';
                    } elseif ($statusPPK == 'rejected') {
                      // Ditolak PPK - dikembalikan ke Operator Tim
                      $posisi = 'Operator Tim'; $posisiBadge = 'bg-danger'; $posisiIcon = 'bi-arrow-return-left'; $isDitolak = true;
                    } elseif ($statusPPK == 'pending' && ($item['total_submitted'] ?? 0) > 0) {
                      $posisi = 'PPK'; $posisiBadge = 'bg-warning text-dark'; $posisiIcon = 'bi-clipboard-check';
                    } else {
                      $posisi = 'Belum Submit'; $posisiBadge = 'bg-light text-dark border'; $posisiIcon = 'bi-dash-circle';
                    }
                  }

                  // === DOKUMEN KEGIATAN ===
                  $hasKAK = !empty($item['has_kak']);
                  $hasSKKPA = !empty($item['has_sk_kpa']);
                  $hasDaftarNominatif = !empty($item['has_daftar_nominatif']);
                  $hasFormPermintaan = !empty($item['has_form_permintaan']);

                  // === TANGGAL LUNAS ===
                  // === TANGGAL LUNAS ===
                  // FIXED: Tanggal lunas hanya jika status = completed
                  $tanggalLunas = null;
                  if ($verifikasiStatus === 'completed') {
                    $tanggalLunas = $item['verified_bendahara_at'] ?? $item['tanggal_lunas'] ?? $item['updated_at'] ?? null;
                  }

                  // === STATUS PEMBAYARAN ===
                  $statusPembayaran = $item['status_pembayaran'] ?? 'pending';
                  // FIXED: isLunas hanya jika verifikasiStatus = completed
                  $isLunas = ($verifikasiStatus === 'completed');
                  
                  // === STATUS KEGIATAN (untuk context=data) ===
                  $statusKegiatan = $item['status_kegiatan'] ?? '';

                  // === KEGIATAN ID FOR DETAIL ===
                  // Gunakan kegiatan_detail_id jika ada, atau fallback ke id
                  $kegiatanIdForDetail = $item['kegiatan_detail_id'] ?? $item['id'] ?? null;
                ?>
                  <tr class="<?= $isLunas ? 'table-success' : '' ?>">
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($item['nama_kegiatan']) ?></strong></td>
                    
                    <!-- Kolom Tim -->
                    <td>
                      <span class="badge bg-secondary"><?= htmlspecialchars($item['team_name'] ?? '-') ?></span>
                    </td>
                    
                    <td>
                      <span class="badge bg-info"><?= htmlspecialchars($item['jenis_kegiatan']) ?></span>
                    </td>
                    
                    <?php if ($context === 'data'): ?>
                      <!-- Kolom Tanggal Kegiatan (hanya untuk context=data) -->
                      <td>
                        <?php if (!empty($item['tanggal_mulai']) && !empty($item['tanggal_selesai'])): ?>
                          <small>
                            <?= date('d M Y', strtotime($item['tanggal_mulai'])) ?><br>
                            s/d<br>
                            <?= date('d M Y', strtotime($item['tanggal_selesai'])) ?>
                          </small>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      
                      <!-- Kolom Status Kegiatan -->
                      <td>
                        <?php if ($statusKegiatan === 'sedang_berlangsung'): ?>
                          <span class="badge bg-primary"><i class="bi bi-play-circle"></i> Berjalan</span>
                        <?php elseif ($statusKegiatan === 'selesai'): ?>
                          <span class="badge bg-success"><i class="bi bi-check-circle"></i> Selesai</span>
                        <?php elseif ($statusKegiatan === 'belum_mulai'): ?>
                          <span class="badge bg-secondary"><i class="bi bi-clock-history"></i> Belum Mulai</span>
                        <?php else: ?>
                          <span class="badge bg-light text-dark border">-</span>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>

                    <!-- Kolom Total Petugas -->
                    <td class="text-center">
                      <span class="badge bg-dark"><?= $item['total_petugas'] ?? '-' ?></span>
                    </td>

                    <!-- Dokumen Kegiatan -->
                    <td>
                      <div style="font-size: 0.75rem;">
                        <button type="button" class="btn btn-sm p-0 border-0 btn-view-dokumen-kegiatan" 
                                data-kegiatan-id="<?= $kegiatanIdForDetail ?>"
                                data-jenis="kak"
                                data-has-file="<?= $hasKAK ? '1' : '0' ?>"
                                title="<?= $hasKAK ? 'Lihat KAK' : 'KAK belum ada' ?>">
                          <span class="badge <?= $hasKAK ? 'bg-success' : 'bg-secondary' ?>" style="font-size:0.65rem; padding:2px 5px; margin:1px; cursor:pointer;"><?= $hasKAK ? '✓' : '○' ?> KAK</span>
                        </button>
                        <button type="button" class="btn btn-sm p-0 border-0 btn-view-dokumen-kegiatan" 
                                data-kegiatan-id="<?= $kegiatanIdForDetail ?>"
                                data-jenis="sk_kpa"
                                data-has-file="<?= $hasSKKPA ? '1' : '0' ?>"
                                title="<?= $hasSKKPA ? 'Lihat SK KPA' : 'SK KPA belum ada' ?>">
                          <span class="badge <?= $hasSKKPA ? 'bg-success' : 'bg-secondary' ?>" style="font-size:0.65rem; padding:2px 5px; margin:1px; cursor:pointer;"><?= $hasSKKPA ? '✓' : '○' ?> SK KPA</span>
                        </button><br>
                        <button type="button" class="btn btn-sm p-0 border-0 btn-view-dokumen-kegiatan" 
                                data-kegiatan-id="<?= $kegiatanIdForDetail ?>"
                                data-jenis="daftar_nominatif"
                                data-has-file="<?= $hasDaftarNominatif ? '1' : '0' ?>"
                                title="<?= $hasDaftarNominatif ? 'Lihat Nominatif' : 'Nominatif belum ada' ?>">
                          <span class="badge <?= $hasDaftarNominatif ? 'bg-success' : 'bg-secondary' ?>" style="font-size:0.65rem; padding:2px 5px; margin:1px; cursor:pointer;"><?= $hasDaftarNominatif ? '✓' : '○' ?> Daftar Nominatif</span>
                        </button>
                        <button type="button" class="btn btn-sm p-0 border-0 btn-view-dokumen-kegiatan" 
                                data-kegiatan-id="<?= $kegiatanIdForDetail ?>"
                                data-jenis="form_permintaan"
                                data-has-file="<?= $hasFormPermintaan ? '1' : '0' ?>"
                                title="<?= $hasFormPermintaan ? 'Lihat Form' : 'Form belum ada' ?>">
                          <span class="badge <?= $hasFormPermintaan ? 'bg-success' : 'bg-secondary' ?>" style="font-size:0.65rem; padding:2px 5px; margin:1px; cursor:pointer;"><?= $hasFormPermintaan ? '✓' : '○' ?> Form Permintaan</span>
                        </button>
                      </div>
                    </td>

                    <!-- Dokumen Per Petugas -->
                    <td>
                      <?php 
                        // Gunakan kegiatan_detail_id jika ada, atau fallback ke id
                        $kegiatanIdForDetail = $item['kegiatan_detail_id'] ?? $item['id']; 
                        $isPosisiSelesai = ($posisi === 'Selesai');
                      ?>
                      <!-- Dokumen per petugas - Single button untuk view semua dokumen -->
                      <div class="btn-group-vertical btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-sm btn-view-dokumen-petugas"
                                data-kegiatan-id="<?= $kegiatanIdForDetail ?>"
                                data-kegiatan-nama="<?= htmlspecialchars($item['nama_kegiatan']) ?>"
                                title="Lihat Dokumen Petugas (ST & SPD, BAST, Bukti, SPK)">
                          <i class="bi bi-file-earmark-pdf"></i> ST & SPD
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm btn-view-dokumen-petugas"
                                data-kegiatan-id="<?= $kegiatanIdForDetail ?>"
                                data-kegiatan-nama="<?= htmlspecialchars($item['nama_kegiatan']) ?>"
                                data-jenis="bast"
                                title="Lihat BAST">
                          <i class="bi bi-file-earmark-check"></i> BAST
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm btn-view-dokumen-petugas"
                                data-kegiatan-id="<?= $kegiatanIdForDetail ?>"
                                data-kegiatan-nama="<?= htmlspecialchars($item['nama_kegiatan']) ?>"
                                data-jenis="bukti"
                                title="Lihat Bukti Pengeluaran">
                          <i class="bi bi-receipt"></i> Bukti
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm btn-view-dokumen-petugas"
                                data-kegiatan-id="<?= $kegiatanIdForDetail ?>"
                                data-kegiatan-nama="<?= htmlspecialchars($item['nama_kegiatan']) ?>"
                                data-jenis="spk"
                                title="Lihat SPK / Kontrak">
                          <i class="bi bi-file-earmark-richtext"></i> SPK
                        </button>
                      </div>
                    </td>

                    <!-- Status Pembayaran (editable admin/bendahara, read-only lainnya) -->
                    <td class="text-center">
                      <?php
                        // "Lunas" kalau verifikasiStatus=completed atau status_bendahara=approved
                        $sbStatus = $item['status_bendahara'] ?? null;
                        $isLunasSimple = ($verifikasiStatus === 'completed') || ($sbStatus === 'approved');
                        $adminId = $item['id'] ?? null;
                      ?>
                      <?php if ($canManagePayment && $adminId): ?>
                        <select class="form-select form-select-sm status-pembayaran-select"
                                data-id="<?= (int)$adminId ?>">
                          <option value="belum_lunas" <?= !$isLunasSimple ? 'selected' : '' ?>>Belum Lunas</option>
                          <option value="lunas" <?= $isLunasSimple ? 'selected' : '' ?>>Lunas</option>
                        </select>
                      <?php else: ?>
                        <?php if ($isLunasSimple): ?>
                          <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Lunas</span>
                        <?php else: ?>
                          <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Belum Lunas</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>

                    <!-- Tanggal Lunas -->
                    <td class="text-center">
                      <?php
                        $tanggalLunasDisplay = $item['verified_bendahara_at']
                                           ?? $item['tanggal_lunas']
                                           ?? null;
                        $isLunasForDate = $isLunasSimple;
                      ?>
                      <?php if ($isLunasForDate && $tanggalLunasDisplay): ?>
                        <small class="text-success fw-bold"><?= date('d M Y', strtotime($tanggalLunasDisplay)) ?></small><br>
                        <small class="text-muted"><?= date('H:i', strtotime($tanggalLunasDisplay)) ?></small>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
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

<!-- Modal Komentar PPK -->
<div class="modal fade" id="modalKomentarPPK" tabindex="-1" aria-labelledby="modalKomentarPPKLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="index.php?controller=administrasi&action=updateStatusPPK" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalKomentarPPKLabel">Input Komentar PPK</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="ppk_idnya">
          <input type="hidden" name="status_ppk" id="ppk_status">
          <div class="mb-3">
            <label for="komentar_ppk" class="form-label">Komentar</label>
            <textarea name="komentar_ppk" id="komentar_ppk" class="form-control" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Kirim</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Status PPK -->
<div class="modal fade" id="modalEditPPK" tabindex="-1">
  <div class="modal-dialog">
    <form action="index.php?controller=administrasi&action=updateStatusPPK" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">Edit Status PPK</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_ppk_id">
          
          <div class="mb-3">
            <label class="form-label">Status PPK</label>
            <select name="status_ppk" id="edit_ppk_status" class="form-select" required>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="edit_ppk_komentar" class="form-label">Komentar</label>
            <textarea name="komentar_ppk" id="edit_ppk_komentar" class="form-control" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Update Status</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Status Bendahara -->
<div class="modal fade" id="modalEditBendahara" tabindex="-1">
  <div class="modal-dialog">
    <form action="index.php?controller=administrasi&action=updateStatusBendahara" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title">Edit Status Pembayaran</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_bendahara_id">
          
          <div class="mb-3">
            <label class="form-label">Status Pembayaran</label>
            <select name="status_bendahara" id="edit_bendahara_status" class="form-select" required>
              <option value="approved">Sudah Bayar</option>
              <option value="rejected">Pending Bayar</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-info">Update Status</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Detail Petugas -->
<div class="modal fade" id="modalDetailPetugas" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-people"></i> Detail Per Petugas - <span id="modal_jenis_dokumen"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalDetailContent">
        <div class="text-center py-4">
          <div class="spinner-border text-primary"></div>
          <p>Memuat data...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Foto Dokumentasi -->
<div class="modal fade" id="modalFoto" tabindex="-1" aria-labelledby="modalFotoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
        <h5 class="modal-title" id="modalFotoLabel">
          <i class="bi bi-images me-2"></i>
          Dokumentasi Foto
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="fotoContainer" class="row g-3">
          <!-- Foto akan dimuat di sini -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal View Dokumen Kegiatan -->
<div class="modal fade" id="modalViewDokumenKegiatan" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> <span id="modal_dokumen_kegiatan_title">Dokumen Kegiatan</span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalDokumenKegiatanContent" style="min-height: 500px;">
        <div class="text-center py-4">
          <div class="spinner-border text-primary"></div>
          <p>Memuat dokumen...</p>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" id="btnDownloadDokumenKegiatan" class="btn btn-primary" download>
          <i class="bi bi-download"></i> Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal View Merged PDF Petugas -->
<div class="modal fade" id="modalViewMergedPdf" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-file-earmark-pdf-fill"></i> Dokumen Semua Petugas</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalMergedPdfContent" style="min-height: 500px;">
        <div class="text-center py-4">
          <div class="spinner-border text-primary"></div>
          <p>Memuat dokumen...</p>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" id="btnDownloadMergedPdf" class="btn btn-primary" download>
          <i class="bi bi-download"></i> Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tolak Bendahara -->
<div class="modal fade" id="modalTolakBendahara" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-x-circle"></i> Tolak Pembayaran</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="tolak_bendahara_kegiatan_id">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle"></i> Dokumen akan dikembalikan ke <strong>Operator</strong> untuk diperbaiki.
        </div>
        <div class="mb-3">
          <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
          <textarea id="tolak_bendahara_catatan" class="form-control" rows="3" required placeholder="Masukkan alasan penolakan..."></textarea>
        </div>
        <p class="text-muted small">Kegiatan: <strong id="tolak_bendahara_nama"></strong></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="btnConfirmTolakBendahara">
          <i class="bi bi-x-circle"></i> Tolak
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>

<style>
  .dataTables_wrapper,
  .dataTables_wrapper .dataTables_filter input,
  .dataTables_wrapper .dataTables_length select,
  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate,
  table, .table, .badge, .btn, .form-control, .form-select {
    font-family: 'Poppins', sans-serif !important;
  }
  
  .text-center.py-5 {
    padding: 3rem 1rem !important;
  }
  
  /* Style untuk button group dokumen per petugas */
  .btn-group-vertical .btn {
    font-size: 0.7rem !important;
    padding: 2px 6px !important;
  }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// Inisialisasi modal
let modalDetailPetugas;

$(document).ready(function() {
  // DataTable
  if ($('#datatable').length > 0 && $('#datatable tbody tr').length > 0) {
    $('#datatable').DataTable({
      "language": {
        "search": "Cari:",
        "lengthMenu": "Tampilkan _MENU_ data",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
        "infoFiltered": "(disaring dari _MAX_ total data)",
        "paginate": {
          "first": "Pertama",
          "last": "Terakhir",
          "next": "Selanjutnya",
          "previous": "Sebelumnya"
        }
      },
      "scrollX": true
    });
  }

  // Inisialisasi modal detail petugas
  modalDetailPetugas = new bootstrap.Modal(document.getElementById('modalDetailPetugas'));

  // Event listener untuk tombol PPK
  $('#datatable').on('click', '.btn-ppk', function() {
    var id = $(this).data('id');
    var status = $(this).data('status');
    $('#ppk_idnya').val(id);
    $('#ppk_status').val(status);
    $('#modalKomentarPPK').modal('show');
  });

  // Event listener untuk tombol detail petugas
  document.querySelectorAll('.btn-detail-petugas').forEach(btn => {
    btn.addEventListener('click', function() {
      const kegiatanId = this.dataset.kegiatanId;
      const jenis = this.dataset.jenis;
      loadDetailPetugas(kegiatanId, jenis);
    });
  });
  
  // Event listener untuk tombol Lunas - FIXED: Use AJAX instead of redirect
  document.querySelectorAll('.btn-lunas').forEach(btn => {
    btn.addEventListener('click', function() {
      const nama = this.dataset.kegiatanNama;
      const kegiatanId = this.dataset.kegiatanId;
      const button = this;
      
      if (confirm('Konfirmasi pembayaran LUNAS untuk kegiatan "' + nama + '"?')) {
        // Show loading
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        // Use AJAX instead of redirect
        fetch('index.php?controller=administrasi&action=markLunas&kegiatan_id=' + kegiatanId)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showAlert('success', '✅ ' + data.message);
              // Reload halaman setelah 1.5 detik
              setTimeout(() => location.reload(), 1500);
            } else {
              showAlert('danger', '❌ ' + (data.error || 'Gagal mengubah status'));
              button.disabled = false;
              button.innerHTML = '<i class="bi bi-check-circle"></i> Lunas';
            }
          })
          .catch(error => {
            showAlert('danger', '❌ Error: ' + error.message);
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-check-circle"></i> Lunas';
          });
      }
    });
  });
  
  // Event listener untuk tombol Belum Lunas
  document.querySelectorAll('.btn-belum-lunas').forEach(btn => {
    btn.addEventListener('click', function() {
      const nama = this.dataset.kegiatanNama;
      const kegiatanId = this.dataset.kegiatanId;
      if (confirm('Ubah status kegiatan "' + nama + '" menjadi BELUM LUNAS?')) {
        updatePembayaran(kegiatanId, 'markBelumLunas');
      }
    });
  });

  // Event listener untuk tombol Tolak Bendahara
  document.querySelectorAll('.btn-tolak-bendahara').forEach(btn => {
    btn.addEventListener('click', function() {
      const kegiatanId = this.dataset.kegiatanId;
      const nama = this.dataset.kegiatanNama;
      document.getElementById('tolak_bendahara_kegiatan_id').value = kegiatanId;
      document.getElementById('tolak_bendahara_nama').textContent = nama;
      document.getElementById('tolak_bendahara_catatan').value = '';
      const modal = new bootstrap.Modal(document.getElementById('modalTolakBendahara'));
      modal.show();
    });
  });

  // Event listener untuk konfirmasi tolak bendahara
  document.getElementById('btnConfirmTolakBendahara')?.addEventListener('click', function() {
    const kegiatanId = document.getElementById('tolak_bendahara_kegiatan_id').value;
    const catatan = document.getElementById('tolak_bendahara_catatan').value;
    if (!catatan.trim()) {
      alert('Alasan penolakan harus diisi!');
      return;
    }
    tolakPembayaranBendahara(kegiatanId, catatan);
  });

  // Event listener untuk view dokumen kegiatan
  document.querySelectorAll('.btn-view-dokumen-kegiatan').forEach(btn => {
    btn.addEventListener('click', function() {
      const kegiatanId = this.dataset.kegiatanId;
      const jenis = this.dataset.jenis;
      const hasFile = this.dataset.hasFile === '1';
      if (!hasFile) {
        alert('Dokumen belum tersedia');
        return;
      }
      viewDokumenKegiatan(kegiatanId, jenis);
    });
  });

  // Event listener untuk view merged PDF
  document.querySelectorAll('.btn-view-merged-pdf').forEach(btn => {
    btn.addEventListener('click', function() {
      const kegiatanId = this.dataset.kegiatanId;
      viewMergedPdfPetugas(kegiatanId);
    });
  });

  // Event listener untuk view dokumen petugas (ST & SPD, BAST, Bukti, SPK) - langsung view
  document.querySelectorAll('.btn-view-dokumen-petugas').forEach(btn => {
    btn.addEventListener('click', function() {
      const kegiatanId = this.dataset.kegiatanId;
      const kegiatanNama = this.dataset.kegiatanNama;
      const jenis = this.dataset.jenis || 'spd'; // default ke SPD
      viewDokumenPetugasMerged(kegiatanId, kegiatanNama, jenis);
    });
  });
});

// FUNGSI EDIT STATUS PPK
function editStatusPPK(id, currentStatus, currentKomentar) {
  $('#edit_ppk_id').val(id);
  $('#edit_ppk_status').val(currentStatus);
  $('#edit_ppk_komentar').val(currentKomentar);
  $('#modalEditPPK').modal('show');
}

// FUNGSI EDIT STATUS BENDAHARA
function editStatusBendahara(id, currentStatus) {
  $('#edit_bendahara_id').val(id);
  $('#edit_bendahara_status').val(currentStatus);
  $('#modalEditBendahara').modal('show');
}

// FUNGSI LOAD DETAIL PETUGAS
function loadDetailPetugas(kegiatanId, jenis) {
  const jenisLabels = {
    'spd': 'Surat Tugas & SPD',
    'laporan_perjalanan': 'Laporan Perjalanan',
    'pengeluaran': 'Bukti Pengeluaran',
    'bast': 'BAST',
    'perjanjian': 'SPK / Kontrak'
  };
  
  document.getElementById('modal_jenis_dokumen').textContent = jenisLabels[jenis] || jenis;
  document.getElementById('modalDetailContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p>Memuat data...</p></div>';
  modalDetailPetugas.show();
  
  fetch('index.php?controller=administrasi&action=getDetailPetugasDokumen&kegiatan_id=' + kegiatanId + '&jenis=' + jenis)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        let html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-dark"><tr><th>No</th><th>Nama Petugas</th><th>Jenis</th><th>Peran</th><th>File</th></tr></thead><tbody>';
        
        if (data.data && data.data.length > 0) {
          data.data.forEach((p, i) => {
            html += '<tr><td>' + (i+1) + '</td><td>' + escapeHtml(p.petugas_nama) + '</td>'
              + '<td><span class="badge ' + (p.petugas_jenis === 'PNS' ? 'bg-primary' : 'bg-info') + '">' + escapeHtml(p.petugas_jenis) + '</span></td>'
              + '<td><span class="badge bg-secondary">' + escapeHtml(p.peran) + '</span></td>'
              + '<td>' + (p.file_path ? '<a href="' + escapeHtml(p.file_path) + '" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a> <a href="' + escapeHtml(p.file_path) + '" download class="btn btn-sm btn-primary"><i class="bi bi-download"></i></a>' : '<span class="badge bg-warning">Tidak ada</span>') + '</td></tr>';
          });
        } else {
          html += '<tr><td colspan="5" class="text-center text-muted">Tidak ada data petugas</td></tr>';
        }
        
        html += '</tbody></table></div>';
        document.getElementById('modalDetailContent').innerHTML = html;
      } else {
        document.getElementById('modalDetailContent').innerHTML = '<div class="alert alert-danger">' + (data.error || 'Gagal memuat data') + '</div>';
      }
    })
    .catch(e => {
      document.getElementById('modalDetailContent').innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
    });
}

// FUNGSI UPDATE PEMBAYARAN
function updatePembayaran(kegiatanId, action) {
  const fd = new FormData();
  fd.append('kegiatan_id', kegiatanId);
  fetch('index.php?controller=administrasi&action=' + action, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showAlert('success', data.message);
        setTimeout(() => location.reload(), 1500);
      } else {
        showAlert('danger', data.error || 'Gagal mengupdate status');
      }
    })
    .catch(e => showAlert('danger', 'Error: ' + e.message));
}

// FUNGSI SHOW ALERT
function showAlert(type, message) {
  document.getElementById('alertContainer').innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
  setTimeout(() => { 
    const a = document.querySelector('.alert'); 
    if (a) a.remove(); 
  }, 5000);
}

// Data files untuk modal foto
const filesData = <?= json_encode($files ?? []) ?>;

// FUNGSI SHOW FOTO MODAL
function showFotoModal(administrasiId) {
  const modal = new bootstrap.Modal(document.getElementById('modalFoto'));
  const container = document.getElementById('fotoContainer');
  container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
  modal.show();
  
  // Ambil file dari data yang sudah ada
  const fotoFiles = [];
  
  if (filesData[administrasiId]) {
    filesData[administrasiId].forEach(file => {
      const filePath = (file.file_path || '').toLowerCase();
      const fileName = (file.nama_file || '').toLowerCase();
      if (filePath.indexOf('foto_') !== -1 || 
          fileName.indexOf('foto') !== -1 ||
          ['jpg', 'jpeg', 'png', 'gif'].includes(filePath.split('.').pop())) {
        fotoFiles.push(file);
      }
    });
  }
  
  if (fotoFiles.length === 0) {
    container.innerHTML = '<div class="col-12 text-center text-muted">Tidak ada foto</div>';
    return;
  }
  
  let html = '';
  fotoFiles.forEach((foto, index) => {
    html += `
      <div class="col-md-4">
        <div class="card">
          <img src="public/${foto.file_path}" class="card-img-top" style="height: 200px; object-fit: cover; cursor: pointer;" 
               onclick="window.open('public/${foto.file_path}', '_blank')"
               onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'200\\' height=\\'200\\'%3E%3Crect fill=\\'%23ddd\\' width=\\'200\\' height=\\'200\\'/%3E%3Ctext fill=\\'%23999\\' x=\\'50%25\\' y=\\'50%25\\' text-anchor=\\'middle\\' dy=\\'.3em\\'%3EGambar tidak dapat dimuat%3C/text%3E%3C/svg%3E'">
          <div class="card-body p-2">
            <small class="text-muted">${escapeHtml(foto.nama_file || 'Foto ' + (index + 1))}</small>
            <a href="public/${foto.file_path}" download class="btn btn-sm btn-outline-primary float-end" title="Download">
              <i class="bi bi-download"></i>
            </a>
          </div>
        </div>
      </div>
    `;
  });
  
  container.innerHTML = html;
}

// FUNGSI ESCAPE HTML
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
}

// FUNGSI TOLAK PEMBAYARAN BENDAHARA
function tolakPembayaranBendahara(kegiatanId, catatan) {
  const fd = new FormData();
  fd.append('kegiatan_id', kegiatanId);
  fd.append('catatan', catatan);
  fetch('index.php?controller=administrasi&action=rejectByBendahara', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('modalTolakBendahara')).hide();
        showAlert('success', data.message || 'Pembayaran berhasil ditolak dan dikembalikan ke Operator');
        setTimeout(() => location.reload(), 1500);
      } else {
        showAlert('danger', data.error || 'Gagal menolak pembayaran');
      }
    })
    .catch(e => showAlert('danger', 'Error: ' + e.message));
}

// FUNGSI VIEW DOKUMEN KEGIATAN
function viewDokumenKegiatan(kegiatanId, jenis) {
  const jenisLabels = {
    'kak': 'KAK (Kerangka Acuan Kerja)',
    'sk_kpa': 'SK KPA',
    'daftar_nominatif': 'Daftar Nominatif',
    'form_permintaan': 'Form Permintaan'
  };
  
  document.getElementById('modal_dokumen_kegiatan_title').textContent = jenisLabels[jenis] || jenis;
  document.getElementById('modalDokumenKegiatanContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p>Memuat dokumen...</p></div>';
  
  const modal = new bootstrap.Modal(document.getElementById('modalViewDokumenKegiatan'));
  modal.show();
  
  fetch('index.php?controller=administrasi&action=getDokumenKegiatan&kegiatan_id=' + kegiatanId + '&jenis=' + jenis)
    .then(r => r.json())
    .then(data => {
      if (data.success && data.file_path) {
        const filePath = data.file_path;
        const isPdf = filePath.toLowerCase().endsWith('.pdf');
        
        document.getElementById('btnDownloadDokumenKegiatan').href = filePath;
        
        if (isPdf) {
          document.getElementById('modalDokumenKegiatanContent').innerHTML = 
            '<iframe src="' + escapeHtml(filePath) + '" style="width:100%; height:600px; border:none;"></iframe>';
        } else {
          document.getElementById('modalDokumenKegiatanContent').innerHTML = 
            '<div class="text-center"><img src="' + escapeHtml(filePath) + '" class="img-fluid" style="max-height:600px;"></div>';
        }
      } else {
        document.getElementById('modalDokumenKegiatanContent').innerHTML = '<div class="alert alert-warning">Dokumen tidak ditemukan</div>';
        document.getElementById('btnDownloadDokumenKegiatan').href = '#';
      }
    })
    .catch(e => {
      document.getElementById('modalDokumenKegiatanContent').innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
    });
}

// FUNGSI VIEW MERGED PDF PETUGAS
function viewMergedPdfPetugas(kegiatanId) {
  document.getElementById('modalMergedPdfContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p>Memuat dan menggabungkan dokumen petugas...</p></div>';
  
  const modal = new bootstrap.Modal(document.getElementById('modalViewMergedPdf'));
  modal.show();
  
  fetch('index.php?controller=administrasi&action=getMergedPdfPetugas&kegiatan_id=' + kegiatanId)
    .then(r => r.json())
    .then(data => {
      if (data.success && data.file_path) {
        const filePath = data.file_path;
        document.getElementById('btnDownloadMergedPdf').href = filePath;
        document.getElementById('modalMergedPdfContent').innerHTML = 
          '<iframe src="' + escapeHtml(filePath) + '" style="width:100%; height:600px; border:none;"></iframe>';
      } else {
        document.getElementById('modalMergedPdfContent').innerHTML = '<div class="alert alert-warning">' + (data.error || 'Dokumen tidak ditemukan') + '</div>';
        document.getElementById('btnDownloadMergedPdf').href = '#';
      }
    })
    .catch(e => {
      document.getElementById('modalMergedPdfContent').innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
    });
}

// FUNGSI VIEW DOKUMEN PETUGAS MERGED (ST & SPD, BAST, Bukti, SPK)
function viewDokumenPetugasMerged(kegiatanId, kegiatanNama, jenis) {
  const jenisLabels = {
    'spd': 'ST & SPD (Surat Tugas & Surat Perjalanan Dinas)',
    'bast': 'BAST (Berita Acara Serah Terima)',
    'bukti': 'Bukti Pengeluaran',
    'spk': 'SPK (Surat Perjanjian Kontrak)'
  };
  
  const title = (jenisLabels[jenis] || jenis) + ' - ' + kegiatanNama;
  document.getElementById('modal_merged_pdf_title').textContent = title;
  document.getElementById('modalMergedPdfContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p>Memuat dokumen petugas...</p></div>';
  
  const modal = new bootstrap.Modal(document.getElementById('modalViewMergedPdf'));
  modal.show();
  
  fetch('index.php?controller=administrasi&action=getDokumenPetugasByJenis&kegiatan_id=' + kegiatanId + '&jenis=' + jenis)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        let html = '';
        
        if (data.data && data.data.length > 0) {
          // Group by peran (PML dan PPL)
          const pmlDocs = data.data.filter(d => d.peran === 'PML');
          const pplDocs = data.data.filter(d => d.peran === 'PPL');
          
          html = '<div class="accordion" id="accordionDokPetugas">';
          
          // PML Section
          if (pmlDocs.length > 0) {
            html += `<div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePML">
                  <i class="bi bi-person-badge me-2"></i> PML (${pmlDocs.length} Petugas)
                </button>
              </h2>
              <div id="collapsePML" class="accordion-collapse collapse show" data-bs-parent="#accordionDokPetugas">
                <div class="accordion-body p-0">
                  <div class="list-group list-group-flush">`;
            
            pmlDocs.forEach(doc => {
              html += `<div class="list-group-item d-flex justify-content-between align-items-center">
                <div><strong>${escapeHtml(doc.petugas_nama)}</strong> <span class="badge bg-primary">PNS</span></div>
                ${doc.file_path 
                  ? `<div><a href="${escapeHtml(doc.file_path)}" target="_blank" class="btn btn-sm btn-info me-1"><i class="bi bi-eye"></i></a>
                     <a href="${escapeHtml(doc.file_path)}" download class="btn btn-sm btn-primary"><i class="bi bi-download"></i></a></div>`
                  : '<span class="badge bg-warning">Tidak ada file</span>'}
              </div>`;
            });
            
            html += '</div></div></div></div>';
          }
          
          // PPL Section  
          if (pplDocs.length > 0) {
            html += `<div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button ${pmlDocs.length === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePPL">
                  <i class="bi bi-people me-2"></i> PPL (${pplDocs.length} Petugas)
                </button>
              </h2>
              <div id="collapsePPL" class="accordion-collapse collapse ${pmlDocs.length === 0 ? 'show' : ''}" data-bs-parent="#accordionDokPetugas">
                <div class="accordion-body p-0">
                  <div class="list-group list-group-flush">`;
            
            pplDocs.forEach(doc => {
              html += `<div class="list-group-item d-flex justify-content-between align-items-center">
                <div><strong>${escapeHtml(doc.petugas_nama)}</strong> <span class="badge ${doc.petugas_jenis === 'PNS' ? 'bg-primary' : 'bg-info'}">${escapeHtml(doc.petugas_jenis)}</span></div>
                ${doc.file_path 
                  ? `<div><a href="${escapeHtml(doc.file_path)}" target="_blank" class="btn btn-sm btn-info me-1"><i class="bi bi-eye"></i></a>
                     <a href="${escapeHtml(doc.file_path)}" download class="btn btn-sm btn-primary"><i class="bi bi-download"></i></a></div>`
                  : '<span class="badge bg-warning">Tidak ada file</span>'}
              </div>`;
            });
            
            html += '</div></div></div></div>';
          }
          
          html += '</div>';
          
          // Download ZIP button
          html += `<div class="mt-3 text-center">
            <a href="index.php?controller=administrasi&action=downloadDokumenPetugasZip&kegiatan_id=${kegiatanId}&jenis=${jenis}" 
               class="btn btn-success">
              <i class="bi bi-file-earmark-zip"></i> Download Semua (ZIP)
            </a>
          </div>`;
        } else {
          html = '<div class="alert alert-warning">Tidak ada dokumen untuk ditampilkan</div>';
        }
        
        document.getElementById('modalMergedPdfContent').innerHTML = html;
        document.getElementById('btnDownloadMergedPdf').href = 'index.php?controller=administrasi&action=downloadDokumenPetugasZip&kegiatan_id=' + kegiatanId + '&jenis=' + jenis;
      } else {
        document.getElementById('modalMergedPdfContent').innerHTML = '<div class="alert alert-warning">' + (data.error || 'Dokumen tidak ditemukan') + '</div>';
        document.getElementById('btnDownloadMergedPdf').href = '#';
      }
    })
    .catch(e => {
      document.getElementById('modalMergedPdfContent').innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
    });
}

// ========================================
// STATUS PEMBAYARAN DROPDOWN (admin/bendahara)
// ========================================
document.addEventListener('change', function (e) {
  const sel = e.target.closest('.status-pembayaran-select');
  if (!sel) return;

  const id = sel.dataset.id;
  const status = sel.value;
  sel.disabled = true;

  const fd = new FormData();
  fd.append('id', id);
  fd.append('status', status);

  fetch('index.php?controller=administrasi&action=updateStatusPembayaran', {
    method: 'POST',
    body: fd
  })
  .then(r => r.json())
  .then(j => {
    sel.disabled = false;
    if (j.success) {
      // Reload agar tanggal lunas ikut terbarui
      location.reload();
    } else {
      alert('Gagal update status: ' + (j.error || 'unknown'));
    }
  })
  .catch(err => {
    sel.disabled = false;
    alert('Error: ' + err.message);
  });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>