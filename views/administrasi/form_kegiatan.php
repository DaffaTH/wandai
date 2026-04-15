<?php
/**
 * Partial: Tab Kegiatan pada Input Administrasi.
 *
 * Menerima dari controller:
 * - $kegiatanList      : list kegiatan tim user (utk dropdown)
 * - $selectedKegiatan  : row kegiatan terpilih (atau null)
 * - $kegiatanJenisList : list kegiatan_jenis utk kegiatan terpilih
 * - $jenisActive       : jenis aktif (nama string)
 * - $selectedJenisRow  : row kegiatan_jenis yg terpilih
 * - $innasList         : list innas utk jenis Pelatihan/Briefing (bila berlaku)
 * - $fileMap           : map [jenis_dokumen => row | [user_id => row]] utk jenis terpilih
 */

$kegiatanList      = $kegiatanList      ?? [];
$selectedKegiatan  = $selectedKegiatan  ?? null;
$kegiatanJenisList = $kegiatanJenisList ?? [];
$jenisActive       = $jenisActive       ?? null;
$selectedJenisRow  = $selectedJenisRow  ?? null;
$innasList         = $innasList         ?? [];
$fileMap           = $fileMap           ?? [];

/** Daftar jenis dokumen per jenis kegiatan. */
$DOK_DEF = [
  'Pelatihan/Briefing' => [
    ['kak','KAK'],
    ['form_permintaan','Form Permintaan'],
    ['daftar_nominatif','Daftar Nominatif'],
    ['surat_tugas_innas','Surat Tugas Inda/Innas', 'per_innas'],
    ['surat_undangan_pelatihan','Surat Undangan Pelatihan'],
    ['absensi','Absensi'],
    ['laporan_kegiatan','Laporan Kegiatan'],
    ['laporan_innas','Laporan Inda/Innas', 'per_innas'],
    ['dokumentasi','Dokumentasi Kegiatan'],
  ],
  'Updating/Listing' => [
    ['kak','KAK'],
    ['form_permintaan','Form Permintaan'],
    ['daftar_nominatif','Daftar Nominatif'],
    ['surat_tugas_petugas','Surat Tugas Petugas', 'cross_petugas'],
    ['spd','SPD', 'cross_petugas'],
    ['visum_responden','Visum Responden'],
    ['laporan_perjalanan','Laporan Perjalanan Dinas', 'cross_petugas'],
    ['bukti_perjalanan','Bukti Perjalanan', 'cross_petugas'],
    ['dokumentasi','Dokumentasi Lapangan'],
  ],
  'Pendataan' => [
    ['kak','KAK'],
    ['form_permintaan','Form Permintaan'],
    ['daftar_nominatif','Daftar Nominatif'],
    ['surat_tugas_petugas','Surat Tugas Petugas', 'cross_petugas'],
    ['spd','SPD', 'cross_petugas'],
    ['visum_responden','Visum Responden'],
    ['laporan_perjalanan','Laporan Perjalanan Dinas', 'cross_petugas'],
    ['bukti_perjalanan','Bukti Perjalanan', 'cross_petugas'],
    ['dokumentasi','Dokumentasi Lapangan'],
  ],
  'Pengolahan' => [
    ['kak','KAK'],
    ['form_permintaan','Form Permintaan'],
    ['daftar_nominatif','Daftar Nominatif'],
    ['surat_tugas_petugas','Surat Tugas Petugas', 'cross_petugas'],
    ['dokumentasi','Dokumentasi Pengolahan'],
  ],
];

/** Helper render file preview. */
function render_file_row($label, $fileRow, $fieldName, $kjId, $kegiatanId, $jenisTab, $innasUserId = null) {
    $hasFile = !empty($fileRow);
    $baseAction = 'index.php?controller=administrasi&action=saveAdministrasiKegiatan';
    $delAction  = 'index.php?controller=administrasi&action=deleteAdministrasiKegiatan';
    ?>
    <div class="mb-3 p-3 border rounded <?= $hasFile ? 'border-success bg-light' : '' ?>">
      <label class="form-label fw-semibold">
        <i class="bi bi-file-earmark-pdf"></i> <?= htmlspecialchars($label) ?>
      </label>
      <?php if ($hasFile): ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span>
            <i class="bi bi-check-circle-fill text-success"></i>
            <a href="<?= htmlspecialchars($fileRow['file_path']) ?>" target="_blank">
              <?= htmlspecialchars($fileRow['nama_file']) ?>
            </a>
          </span>
          <form method="POST" action="<?= $delAction ?>" class="d-inline"
                onsubmit="return confirm('Hapus dokumen ini?');">
            <input type="hidden" name="file_id" value="<?= (int)$fileRow['id'] ?>">
            <input type="hidden" name="kegiatan_id" value="<?= (int)$kegiatanId ?>">
            <input type="hidden" name="jenis_tab" value="<?= htmlspecialchars($jenisTab) ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        </div>
      <?php endif; ?>
      <form method="POST" action="<?= $baseAction ?>" enctype="multipart/form-data" class="row g-2">
        <input type="hidden" name="kegiatan_jenis_id" value="<?= (int)$kjId ?>">
        <input type="hidden" name="jenis_dokumen" value="<?= htmlspecialchars($fieldName) ?>">
        <input type="hidden" name="kegiatan_id" value="<?= (int)$kegiatanId ?>">
        <input type="hidden" name="jenis_tab" value="<?= htmlspecialchars($jenisTab) ?>">
        <?php if ($innasUserId): ?>
          <input type="hidden" name="innas_user_id" value="<?= (int)$innasUserId ?>">
        <?php endif; ?>
        <div class="col">
          <input type="file" name="file" class="form-control form-control-sm" required
                 accept="application/pdf,image/*">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-<?= $hasFile ? 'warning' : 'primary' ?>">
            <i class="bi bi-upload"></i> <?= $hasFile ? 'Ganti' : 'Upload' ?>
          </button>
        </div>
      </form>
    </div>
    <?php
}
?>

<!-- Dropdown pilih kegiatan -->
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" action="" class="row g-2 align-items-end">
      <input type="hidden" name="controller" value="administrasi">
      <input type="hidden" name="action" value="form">
      <input type="hidden" name="tab" value="kegiatan">
      <div class="col-md-8">
        <label class="form-label mb-1">Pilih Kegiatan</label>
        <select name="kegiatan_id" class="form-select" onchange="this.form.submit()">
          <option value="">-- Pilih Kegiatan --</option>
          <?php foreach ($kegiatanList as $k): ?>
            <option value="<?= (int)$k['id'] ?>"
              <?= $selectedKegiatan && $selectedKegiatan['id'] == $k['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama_kegiatan']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if (!$selectedKegiatan): ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Pilih kegiatan untuk mulai mengupload dokumen administrasi.
  </div>
<?php elseif (empty($kegiatanJenisList)): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i> Kegiatan ini belum memiliki jenis. Silakan edit kegiatan dan tentukan jenis.
  </div>
<?php else: ?>

  <!-- Sub-tab per jenis kegiatan -->
  <ul class="nav nav-pills mb-3">
    <?php foreach ($kegiatanJenisList as $kj): ?>
      <li class="nav-item">
        <a class="nav-link <?= $kj['jenis'] === $jenisActive ? 'active' : '' ?>"
           href="index.php?controller=administrasi&action=form&tab=kegiatan&kegiatan_id=<?= (int)$selectedKegiatan['id'] ?>&jenis=<?= urlencode($kj['jenis']) ?>">
          <?= htmlspecialchars($kj['jenis']) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if (!$selectedJenisRow): ?>
    <div class="alert alert-info">Pilih jenis kegiatan.</div>
  <?php else:
      $kjId = (int)$selectedJenisRow['id'];
      $jenisTab = $selectedJenisRow['jenis'];
      $dokList = $DOK_DEF[$jenisTab] ?? [];
  ?>
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <strong><?= htmlspecialchars($selectedKegiatan['nama_kegiatan']) ?></strong>
        &mdash; <?= htmlspecialchars($jenisTab) ?>
        <?php if (!empty($selectedJenisRow['tanggal_mulai'])): ?>
          <small class="ms-2 opacity-75">
            (<?= htmlspecialchars($selectedJenisRow['tanggal_mulai']) ?>
            s.d. <?= htmlspecialchars($selectedJenisRow['tanggal_selesai'] ?? '-') ?>)
          </small>
        <?php endif; ?>
      </div>
      <div class="card-body">

        <?php foreach ($dokList as $dok):
            $field = $dok[0];
            $label = $dok[1];
            $variant = $dok[2] ?? 'normal';

            // Cross-sync hint untuk dokumen yg seharusnya diisi di tab Petugas
            if ($variant === 'cross_petugas') {
                // Cek apakah ada file utk dokumen ini di administrasi_kegiatan_files.
                // Kalau ada, tampilkan; kalau tidak, tampilkan badge "sudah terisi di Input Administrasi Petugas"
                // (karena SPD/ST dsb umumnya diinput per-petugas, bukan per-kegiatan).
                if (!empty($fileMap[$field])) {
                    render_file_row($label, $fileMap[$field], $field, $kjId, (int)$selectedKegiatan['id'], $jenisTab);
                } else {
                    ?>
                    <div class="mb-3 p-3 border rounded bg-light">
                      <label class="form-label fw-semibold">
                        <i class="bi bi-file-earmark-pdf"></i> <?= htmlspecialchars($label) ?>
                      </label>
                      <div class="alert alert-secondary mb-2">
                        <i class="bi bi-info-circle"></i>
                        Dokumen ini umumnya sudah diisi di
                        <a href="index.php?controller=administrasi&action=form&tab=petugas&kegiatan_id=<?= (int)$selectedKegiatan['id'] ?>">
                          <strong>Input Administrasi Petugas</strong>
                        </a>.
                        Upload di sini hanya bila file sudah berisikan dokumen dari semua petugas.
                      </div>
                      <form method="POST" action="index.php?controller=administrasi&action=saveAdministrasiKegiatan"
                            enctype="multipart/form-data" class="row g-2">
                        <input type="hidden" name="kegiatan_jenis_id" value="<?= $kjId ?>">
                        <input type="hidden" name="jenis_dokumen" value="<?= htmlspecialchars($field) ?>">
                        <input type="hidden" name="kegiatan_id" value="<?= (int)$selectedKegiatan['id'] ?>">
                        <input type="hidden" name="jenis_tab" value="<?= htmlspecialchars($jenisTab) ?>">
                        <div class="col">
                          <input type="file" name="file" class="form-control form-control-sm"
                                 accept="application/pdf,image/*" required>
                        </div>
                        <div class="col-auto">
                          <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-upload"></i> Upload (opsional)
                          </button>
                        </div>
                      </form>
                    </div>
                    <?php
                }
            } elseif ($variant === 'per_innas') {
                // Perlu satu upload per innas
                ?>
                <div class="mb-3 p-3 border rounded">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-file-earmark-pdf"></i> <?= htmlspecialchars($label) ?>
                    <small class="text-muted">(per Inda/Innas)</small>
                  </label>
                  <?php if (empty($innasList)): ?>
                    <div class="alert alert-warning mb-0">
                      <i class="bi bi-exclamation-triangle"></i>
                      Belum ada Inda/Innas. Tambahkan di halaman Tambah Kegiatan.
                    </div>
                  <?php else:
                    foreach ($innasList as $in):
                      $uid = (int)$in['user_id'];
                      $existing = $fileMap[$field][$uid] ?? null;
                  ?>
                      <div class="p-2 mb-2 border rounded <?= $existing ? 'bg-light border-success' : '' ?>">
                        <div class="fw-semibold mb-1">
                          <i class="bi bi-person-circle"></i> <?= htmlspecialchars($in['user_name'] ?? $in['name'] ?? '-') ?>
                        </div>
                        <?php if ($existing): ?>
                          <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>
                              <i class="bi bi-check-circle-fill text-success"></i>
                              <a href="<?= htmlspecialchars($existing['file_path']) ?>" target="_blank">
                                <?= htmlspecialchars($existing['nama_file']) ?>
                              </a>
                            </span>
                            <form method="POST" action="index.php?controller=administrasi&action=deleteAdministrasiKegiatan"
                                  class="d-inline" onsubmit="return confirm('Hapus dokumen ini?');">
                              <input type="hidden" name="file_id" value="<?= (int)$existing['id'] ?>">
                              <input type="hidden" name="kegiatan_id" value="<?= (int)$selectedKegiatan['id'] ?>">
                              <input type="hidden" name="jenis_tab" value="<?= htmlspecialchars($jenisTab) ?>">
                              <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                              </button>
                            </form>
                          </div>
                        <?php endif; ?>
                        <form method="POST" action="index.php?controller=administrasi&action=saveAdministrasiKegiatan"
                              enctype="multipart/form-data" class="row g-2">
                          <input type="hidden" name="kegiatan_jenis_id" value="<?= $kjId ?>">
                          <input type="hidden" name="jenis_dokumen" value="<?= htmlspecialchars($field) ?>">
                          <input type="hidden" name="innas_user_id" value="<?= $uid ?>">
                          <input type="hidden" name="kegiatan_id" value="<?= (int)$selectedKegiatan['id'] ?>">
                          <input type="hidden" name="jenis_tab" value="<?= htmlspecialchars($jenisTab) ?>">
                          <div class="col">
                            <input type="file" name="file" class="form-control form-control-sm"
                                   accept="application/pdf,image/*" required>
                          </div>
                          <div class="col-auto">
                            <button class="btn btn-sm btn-<?= $existing ? 'warning' : 'primary' ?>">
                              <i class="bi bi-upload"></i> <?= $existing ? 'Ganti' : 'Upload' ?>
                            </button>
                          </div>
                        </form>
                      </div>
                  <?php endforeach; endif; ?>
                </div>
                <?php
            } else {
                // Normal: 1 file per kegiatan_jenis
                render_file_row($label, $fileMap[$field] ?? null, $field, $kjId, (int)$selectedKegiatan['id'], $jenisTab);
            }
        endforeach; ?>

      </div>
    </div>
  <?php endif; ?>

<?php endif; ?>
