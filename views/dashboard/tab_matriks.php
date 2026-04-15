<?php
/**
 * Partial: Dashboard tab Matriks.
 * Menerima dari controller: $matriksData dan $bulanAktif.
 * $matriksData = ['bulan','tanggal','pegawai','mitra_paniai','mitra_intan_jaya','teams']
 */
$matriksData = $matriksData ?? [];
$bulanAktif  = $bulanAktif  ?? date('Y-m');
$sub         = $_GET['sub']    ?? 'pegawai';
$subMitra    = $_GET['submitra'] ?? 'paniai';

$tanggalArr   = $matriksData['tanggal'] ?? [];
$pegawai      = $matriksData['pegawai'] ?? [];
$mitraPaniai  = $matriksData['mitra_paniai'] ?? [];
$mitraIntan   = $matriksData['mitra_intan_jaya'] ?? [];
$teamsLegend  = $matriksData['teams'] ?? [];

$prevBulan = date('Y-m', strtotime($bulanAktif . '-01 -1 month'));
$nextBulan = date('Y-m', strtotime($bulanAktif . '-01 +1 month'));

// Pemetaan bulan Indonesia (tanpa memakai strftime yang sudah deprecated).
$bulanIndo = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
              '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
[$yy, $mm] = explode('-', $bulanAktif);
$namaBulan = ($bulanIndo[$mm] ?? $mm) . ' ' . $yy;

/** Helper: render 1 baris matriks (nama + sel tanggal berwarna). */
function render_matriks_rows(array $rows, array $tanggalArr): void
{
    if (empty($rows)) {
        echo '<tr><td colspan="' . (count($tanggalArr) + 1) . '" class="text-center text-muted py-4">Tidak ada data</td></tr>';
        return;
    }
    foreach ($rows as $r) {
        // Buat map hari => blok (tgl_start..tgl_end)
        $cells = array_fill(1, 31, null);
        foreach (($r['bloks'] ?? []) as $b) {
            for ($d = $b['tgl_start']; $d <= $b['tgl_end']; $d++) {
                $cells[$d] = $b;
            }
        }
        echo '<tr>';
        echo '<td class="matriks-nama">' . htmlspecialchars($r['name']) . '</td>';
        foreach ($tanggalArr as $t) {
            $d = $t['tgl'];
            $b = $cells[$d] ?? null;
            $weekend = $t['is_weekend'] ? ' matriks-weekend' : '';
            if ($b) {
                $warna = htmlspecialchars($b['warna']);
                $tip   = htmlspecialchars($b['nama_kegiatan'] . ' — ' . ($b['team_name'] ?? '') . ' (' . ($b['peran'] ?? '') . ')');
                // !important wajib: stylesheet global dashboard menerapkan
                // `.table tbody td { background: ... !important }` yang akan
                // menutup warna blok kalau kita tidak pakai !important di sini.
                echo '<td class="matriks-blok' . $weekend . '" '
                   . 'style="background:' . $warna . ' !important; background-color:' . $warna . ' !important;" '
                   . 'data-kp-id="' . (int)$b['kp_id'] . '" title="' . $tip . '"></td>';
            } else {
                echo '<td class="matriks-kosong' . $weekend . '"></td>';
            }
        }
        echo '</tr>';
    }
}
?>

<style>
.matriks-wrapper {
  overflow-x: auto;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0,0,0,.06);
}
.matriks-wrapper table {
  border-collapse: separate;
  border-spacing: 0;
  margin-bottom: 0;
  font-size: .82rem;
  min-width: 900px;
}
.matriks-wrapper thead th {
  position: sticky; top: 0; z-index: 2;
  background: #0f172a; color: #fff;
  border-bottom: 2px solid #020617;
  border-right: 1px solid rgba(255,255,255,.10);
  padding: .5rem .25rem;
  text-align: center;
  font-weight: 600;
  line-height: 1.15;
}
.matriks-wrapper thead th.matriks-weekend { background: #1e293b; }
.matriks-wrapper thead th.matriks-nama-head,
.matriks-wrapper td.matriks-nama {
  position: sticky; left: 0; z-index: 3;
  min-width: 210px; max-width: 260px;
  border-right: 2px solid #020617;
  padding: .55rem .85rem;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.matriks-wrapper thead th.matriks-nama-head {
  z-index: 4; background: #020617;
  text-align: left;
  font-size: .82rem;
  letter-spacing: .3px;
}
.matriks-wrapper td.matriks-nama { background: #fff !important; color: #0f172a; font-weight: 500; }
.matriks-wrapper tbody tr:nth-child(even) td.matriks-nama { background: #f8fafc !important; }
.matriks-wrapper tbody tr:hover td.matriks-nama { background: #fff7ed !important; }
/* Bootstrap .table men-set box-shadow & padding pada tiap sel, dan stylesheet
   global dashboard memakai `background: var(--glass-white) !important` untuk
   semua td. Reset pakai !important supaya warna blok matriks & sel kosong
   tetap tampil benar. */
.matriks-wrapper table.table > tbody > tr > td {
  box-shadow: none !important;
  padding: 0 !important;
}
.matriks-wrapper tbody tr { height: 34px; }
.matriks-wrapper tbody td {
  width: 30px;
  min-width: 30px;
  height: 34px;
  border-right: 1px solid #f1f5f9;
  border-bottom: 1px solid #f1f5f9;
}
.matriks-wrapper td.matriks-nama { padding: .55rem .85rem !important; }
.matriks-blok {
  cursor: pointer;
  position: relative;
  background-clip: padding-box;
}
.matriks-blok:hover { filter: brightness(1.12); box-shadow: inset 0 0 0 2px rgba(0,0,0,.15) !important; }
.matriks-wrapper tbody td.matriks-kosong { background: #fff !important; }
.matriks-wrapper tbody tr:nth-child(even) td.matriks-kosong { background: #fafbfc !important; }
.matriks-wrapper tbody td.matriks-kosong.matriks-weekend { background: #f1f5f9 !important; }
.matriks-wrapper tbody tr:nth-child(even) td.matriks-kosong.matriks-weekend { background: #e2e8f0 !important; }
.matriks-blok.matriks-weekend { opacity: .92; }
/* Perbaiki visibilitas teks hari (Sen/Sel/Rab/...) pada header gelap */
.hari-mini {
  display:block;
  font-size:.68rem;
  color:#ffffff;
  font-weight:700;
  letter-spacing:.4px;
  text-transform: uppercase;
  text-shadow: 0 1px 2px rgba(0,0,0,.45);
  margin-bottom: 2px;
}
.hari-tgl  {
  display:block;
  font-size:.95rem;
  color:#fde68a;
  font-weight:800;
  line-height:1.1;
}
.matriks-weekend .hari-mini { color:#fecaca; }
.matriks-weekend .hari-tgl  { color:#fca5a5; }
.legend-warna { display:inline-block; width:14px; height:14px; border-radius:3px; vertical-align:middle; margin-right:4px; box-shadow: inset 0 0 0 1px rgba(0,0,0,.08); }
</style>

<!-- Navigasi bulan + sub-tab -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
  <div>
    <a href="?controller=dashboard&tab=matriks&sub=<?= $sub ?>&submitra=<?= $subMitra ?>&bulan=<?= $prevBulan ?>"
       class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-chevron-left"></i> <?= date('M Y', strtotime($prevBulan . '-01')) ?>
    </a>
    <span class="fw-semibold mx-2"><?= htmlspecialchars($namaBulan) ?></span>
    <a href="?controller=dashboard&tab=matriks&sub=<?= $sub ?>&submitra=<?= $subMitra ?>&bulan=<?= $nextBulan ?>"
       class="btn btn-outline-secondary btn-sm">
      <?= date('M Y', strtotime($nextBulan . '-01')) ?> <i class="bi bi-chevron-right"></i>
    </a>
  </div>
  <div>
    <a href="?controller=dashboard&tab=matriks&sub=pegawai&bulan=<?= $bulanAktif ?>"
       class="btn btn-<?= $sub==='pegawai' ? 'primary' : 'outline-primary' ?> btn-sm">
      <i class="bi bi-person-badge"></i> Pegawai
    </a>
    <a href="?controller=dashboard&tab=matriks&sub=mitra&submitra=<?= $subMitra ?>&bulan=<?= $bulanAktif ?>"
       class="btn btn-<?= $sub==='mitra' ? 'primary' : 'outline-primary' ?> btn-sm">
      <i class="bi bi-people"></i> Mitra
    </a>
  </div>
</div>

<?php if ($sub === 'mitra'): ?>
<div class="mb-3">
  <a href="?controller=dashboard&tab=matriks&sub=mitra&submitra=paniai&bulan=<?= $bulanAktif ?>"
     class="btn btn-<?= $subMitra==='paniai' ? 'success' : 'outline-success' ?> btn-sm">
    Paniai
  </a>
  <a href="?controller=dashboard&tab=matriks&sub=mitra&submitra=intan_jaya&bulan=<?= $bulanAktif ?>"
     class="btn btn-<?= $subMitra==='intan_jaya' ? 'success' : 'outline-success' ?> btn-sm">
    Intan Jaya
  </a>
</div>
<?php endif; ?>

<!-- Matriks Table -->
<div class="matriks-wrapper mb-3">
  <table class="table table-sm">
    <thead>
      <tr>
        <th class="matriks-nama-head text-start">Nama</th>
        <?php foreach ($tanggalArr as $t): ?>
          <th class="<?= $t['is_weekend'] ? 'matriks-weekend' : '' ?>">
            <span class="hari-mini"><?= htmlspecialchars($t['hari']) ?></span>
            <span class="hari-tgl"><?= $t['tgl'] ?></span>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($sub === 'pegawai') {
          render_matriks_rows($pegawai, $tanggalArr);
      } elseif ($sub === 'mitra' && $subMitra === 'paniai') {
          render_matriks_rows($mitraPaniai, $tanggalArr);
      } else {
          render_matriks_rows($mitraIntan, $tanggalArr);
      }
      ?>
    </tbody>
  </table>
</div>

<!-- Legenda Warna Tim -->
<div class="card mb-4">
  <div class="card-body py-2">
    <small class="text-muted me-2"><i class="bi bi-palette me-1"></i>Warna Tim:</small>
    <?php foreach ($teamsLegend as $t): ?>
      <span class="me-3">
        <span class="legend-warna" style="background:<?= htmlspecialchars($t['warna']) ?>"></span>
        <small><?= htmlspecialchars($t['name']) ?></small>
      </span>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal info kegiatan -->
<div class="modal fade" id="modalMatriksInfo" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-info-circle me-1"></i> Info Kegiatan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="matriksInfoBody">
        <div class="text-center py-4 text-muted">Memuat...</div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('click', function(e) {
  const cell = e.target.closest('.matriks-blok');
  if (!cell) return;
  const kpId = cell.getAttribute('data-kp-id');
  if (!kpId) return;
  const body = document.getElementById('matriksInfoBody');
  body.innerHTML = '<div class="text-center py-4 text-muted">Memuat...</div>';
  const modal = new bootstrap.Modal(document.getElementById('modalMatriksInfo'));
  modal.show();
  fetch('index.php?controller=dashboard&action=matriksInfo&kp_id=' + encodeURIComponent(kpId))
    .then(r => r.json())
    .then(j => {
      if (!j.success) {
        body.innerHTML = '<div class="alert alert-danger mb-0">' + (j.error || 'Gagal memuat') + '</div>';
        return;
      }
      const d = j.data;
      body.innerHTML = `
        <table class="table table-sm mb-0">
          <tr><th style="width:35%">Kegiatan</th><td>${d.nama_kegiatan}</td></tr>
          <tr><th>Jenis</th><td>${d.jenis_kegiatan || '-'}</td></tr>
          <tr><th>Tim</th><td><span class="legend-warna" style="background:${d.warna}"></span> ${d.team_name || '-'}</td></tr>
          <tr><th>Petugas</th><td>${d.petugas_nama} <small class="text-muted">(${d.petugas_jenis})</small></td></tr>
          <tr><th>Peran</th><td>${d.peran || '-'}</td></tr>
          <tr><th>Periode</th><td>${d.tgl_mulai} s/d ${d.tgl_selesai}</td></tr>
          <tr><th>Target / Realisasi</th><td>${d.peran === 'Supervisi' ? '<span class="text-muted">— (tidak berlaku untuk Supervisi)</span>' : ((d.realisasi ?? 0) + ' / ' + (d.target ?? 0))}</td></tr>
        </table>
      `;
    })
    .catch(err => {
      body.innerHTML = '<div class="alert alert-danger mb-0">' + err.message + '</div>';
    });
});
</script>
