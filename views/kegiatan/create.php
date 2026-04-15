<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';

$allUsers = $allUsers ?? [];   // anggota tim (semua users aktif) utk dropdown innas
$teams    = $teams    ?? [];
$pjUsers  = $pjUsers  ?? [];   // kandidat Penanggung Jawab (Kepala/Kasubbag/PPK)

$JENIS_LIST = ['Pelatihan/Briefing','Updating/Listing','Pendataan','Pengolahan'];
$SATUAN_OPTS = ['dokumen'=>'Dokumen','segmen'=>'Segmen','responden'=>'Responden','sampel'=>'Sampel','SLS'=>'SLS','BS'=>'BS'];
?>

<style>
.content-wrapper { padding: 1.5rem; background-color: #f8f9fa; min-height: 100vh; }
.card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;
               border-radius: 12px 12px 0 0 !important; padding: 1rem 1.5rem; }
.form-label { font-weight: 500; color: #495057; margin-bottom: 0.35rem; }
.form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,.25); }
.jenis-row { display:none; }
.jenis-row.active { display:table-row; }
.jenis-row td { vertical-align: top; }
.disabled-cell { background: #e9ecef !important; }
.innas-row { display:none; }
.innas-row.active { display:table-row; }
.jenis-table input, .jenis-table select { font-size: .9rem; }
.jenis-table th { font-size: .8rem; }

/* Checklist Inda/Innas untuk jenis Pelatihan/Briefing */
.innas-wrapper {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: .75rem;
}
.innas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: .35rem .75rem;
  max-height: 240px;
  overflow-y: auto;
  padding: .25rem;
}
.innas-grid .form-check { margin: 0; }
.innas-grid .form-check-label { font-size: .9rem; }
.innas-toolbar { display:flex; gap:.5rem; align-items:center; margin-top:.5rem; flex-wrap:wrap; }
.innas-toolbar .innas-search { flex: 1 1 180px; max-width: 280px; }
.innas-count { font-size: .8rem; color: #6c757d; }
</style>

<div class="d-flex">
  <div class="sidebar"><?php include __DIR__ . '/../layouts/sidebar.php'; ?></div>
  <div class="flex-grow-1">
    <div class="content-wrapper">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Kegiatan Baru</h5>
          <a href="index.php?controller=kegiatan" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
          </a>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="index.php?controller=kegiatan&action=store" class="needs-validation" novalidate>
            <div class="row g-3">
              <!-- Nama Kegiatan -->
              <div class="col-12">
                <label for="nama_kegiatan" class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_kegiatan" name="nama_kegiatan" required
                       placeholder="Contoh: Sakernas 2026">
                <div class="invalid-feedback">Nama kegiatan wajib diisi.</div>
              </div>

              <!-- Tim (admin only) -->
              <?php if ($_SESSION['user']['role_id'] == 1): ?>
              <div class="col-md-6">
                <label for="team_id" class="form-label">Tim Penanggung Jawab <span class="text-danger">*</span></label>
                <select class="form-select" id="team_id" name="team_id" required>
                  <option value="">-- Pilih Tim --</option>
                  <?php foreach ($teams as $team): ?>
                    <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Tim wajib dipilih.</div>
              </div>
              <?php endif; ?>

              <!-- Penanggung Jawab Kegiatan (untuk BAST Honor) -->
              <div class="col-md-6">
                <label for="penanggung_jawab_user_id" class="form-label">
                  Penanggung Jawab Kegiatan
                  <small class="text-muted fw-normal">(untuk BAST Honor)</small>
                </label>
                <select class="form-select" id="penanggung_jawab_user_id" name="penanggung_jawab_user_id">
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

              <!-- Jenis Kegiatan (checkbox multi-pilih) -->
              <div class="col-12">
                <label class="form-label">Jenis Kegiatan <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-3 p-3 border rounded bg-light">
                  <?php foreach ($JENIS_LIST as $j): $slug = md5($j); ?>
                    <div class="form-check">
                      <input class="form-check-input jenis-check" type="checkbox"
                             name="jenis[]" value="<?= htmlspecialchars($j) ?>"
                             id="jenis-<?= $slug ?>" data-jenis="<?= htmlspecialchars($j) ?>">
                      <label class="form-check-label" for="jenis-<?= $slug ?>">
                        <?= htmlspecialchars($j) ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
                <small class="text-muted">Bisa pilih lebih dari 1. Satuan, Program, Output, Komponen, dan Honor untuk
                  "Pelatihan/Briefing" otomatis dinonaktifkan.</small>
              </div>

              <!-- Tabel per Jenis -->
              <div class="col-12">
                <div class="table-responsive">
                  <table class="table table-bordered jenis-table align-middle">
                    <thead class="table-light">
                      <tr>
                        <th style="min-width:140px">Jenis</th>
                        <th style="min-width:120px">Satuan</th>
                        <th style="min-width:140px">Tanggal Mulai</th>
                        <th style="min-width:140px">Tanggal Selesai</th>
                        <th style="min-width:130px">Program</th>
                        <th style="min-width:130px">Output</th>
                        <th style="min-width:130px">Komponen</th>
                        <th style="min-width:140px">Honor/Satuan (Rp)</th>
                      </tr>
                    </thead>
                    <tbody id="jenisTbody">
                      <?php foreach ($JENIS_LIST as $j):
                        $slug = md5($j);
                        $isPelatihan = ($j === 'Pelatihan/Briefing');
                      ?>
                      <tr class="jenis-row" data-jenis="<?= htmlspecialchars($j) ?>">
                        <td><strong><?= htmlspecialchars($j) ?></strong></td>
                        <td class="<?= $isPelatihan ? 'disabled-cell' : '' ?>">
                          <?php if (!$isPelatihan): ?>
                            <select class="form-select form-select-sm"
                                    name="row[<?= htmlspecialchars($j) ?>][satuan]">
                              <option value="">-- Pilih Satuan --</option>
                              <?php foreach ($SATUAN_OPTS as $k=>$lbl): ?>
                                <option value="<?= $k ?>"><?= $lbl ?></option>
                              <?php endforeach; ?>
                            </select>
                          <?php endif; ?>
                        </td>
                        <td>
                          <input type="date" class="form-control form-control-sm row-start"
                                 name="row[<?= htmlspecialchars($j) ?>][tanggal_mulai]">
                        </td>
                        <td>
                          <input type="date" class="form-control form-control-sm row-end"
                                 name="row[<?= htmlspecialchars($j) ?>][tanggal_selesai]">
                        </td>
                        <td class="<?= $isPelatihan ? 'disabled-cell' : '' ?>">
                          <?php if (!$isPelatihan): ?>
                            <input type="text" class="form-control form-control-sm"
                                   name="row[<?= htmlspecialchars($j) ?>][program]"
                                   placeholder="Contoh: 054.01.GG">
                          <?php endif; ?>
                        </td>
                        <td class="<?= $isPelatihan ? 'disabled-cell' : '' ?>">
                          <?php if (!$isPelatihan): ?>
                            <input type="text" class="form-control form-control-sm"
                                   name="row[<?= htmlspecialchars($j) ?>][output]"
                                   placeholder="Contoh: 2905.BMN.001">
                          <?php endif; ?>
                        </td>
                        <td class="<?= $isPelatihan ? 'disabled-cell' : '' ?>">
                          <?php if (!$isPelatihan): ?>
                            <input type="text" class="form-control form-control-sm"
                                   name="row[<?= htmlspecialchars($j) ?>][komponen]"
                                   placeholder="Contoh: 001">
                          <?php endif; ?>
                        </td>
                        <td class="<?= $isPelatihan ? 'disabled-cell' : '' ?>">
                          <?php if (!$isPelatihan): ?>
                            <div class="input-group input-group-sm">
                              <span class="input-group-text">Rp</span>
                              <input type="text" inputmode="numeric"
                                     class="form-control form-control-sm honor-input"
                                     name="row[<?= htmlspecialchars($j) ?>][honor_satuan]"
                                     placeholder="Contoh: 400.000">
                            </div>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php if ($isPelatihan): ?>
                      <tr class="innas-row" data-jenis="Pelatihan/Briefing">
                        <td colspan="8">
                          <label class="form-label mb-1">
                            <i class="bi bi-people-fill me-1"></i>
                            Inda/Innas
                            <small class="text-muted fw-normal">(boleh pilih lebih dari satu)</small>
                          </label>
                          <div class="innas-wrapper">
                            <div class="innas-toolbar mb-2">
                              <input type="text" class="form-control form-control-sm innas-search"
                                     placeholder="Cari nama...">
                              <button type="button" class="btn btn-outline-primary btn-sm innas-select-all">
                                <i class="bi bi-check-all"></i> Pilih semua
                              </button>
                              <button type="button" class="btn btn-outline-secondary btn-sm innas-clear">
                                <i class="bi bi-x-lg"></i> Kosongkan
                              </button>
                              <span class="innas-count ms-auto">0 terpilih</span>
                            </div>
                            <div class="innas-grid" id="innasGrid">
                              <?php foreach ($allUsers as $u): ?>
                                <div class="form-check innas-item"
                                     data-name="<?= htmlspecialchars(mb_strtolower($u['name'])) ?>">
                                  <input class="form-check-input innas-check" type="checkbox"
                                         name="row[Pelatihan/Briefing][innas][]"
                                         value="<?= (int)$u['id'] ?>"
                                         id="innas-<?= (int)$u['id'] ?>">
                                  <label class="form-check-label" for="innas-<?= (int)$u['id'] ?>">
                                    <?= htmlspecialchars($u['name']) ?>
                                    
                                  </label>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        </td>
                      </tr>
                      <?php endif; ?>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="mt-4 pt-3 border-top">
              <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Kegiatan</button>
              <button type="reset" class="btn btn-secondary"><i class="bi bi-arrow-clockwise me-1"></i>Reset</button>
              <a href="index.php?controller=kegiatan" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Batal
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  'use strict';
  const checks   = document.querySelectorAll('.jenis-check');
  const innasRow = document.querySelector('.innas-row[data-jenis="Pelatihan/Briefing"]');
  const innasChecks = document.querySelectorAll('.innas-check');
  const innasItems  = document.querySelectorAll('.innas-item');
  const innasSearch = document.querySelector('.innas-search');
  const innasCount  = document.querySelector('.innas-count');
  const btnSelectAll = document.querySelector('.innas-select-all');
  const btnClear     = document.querySelector('.innas-clear');

  function updateInnasCount() {
    if (!innasCount) return;
    const n = document.querySelectorAll('.innas-check:checked').length;
    innasCount.textContent = n + ' terpilih';
  }
  updateInnasCount();

  function toggleRows() {
    checks.forEach(chk => {
      const jenis = chk.dataset.jenis;
      const row = document.querySelector('.jenis-row[data-jenis="' + CSS.escape(jenis) + '"]');
      if (!row) return;
      if (chk.checked) {
        row.classList.add('active');
        if (jenis === 'Pelatihan/Briefing' && innasRow) innasRow.classList.add('active');
        row.querySelectorAll('input[type="date"], select[name$="[satuan]"], input[name$="[program]"], input[name$="[output]"], input[name$="[komponen]"], input[name$="[honor_satuan]"]').forEach(i => {
          if (i.closest('.disabled-cell')) return;
          i.required = (i.type === 'date');  // tgl wajib
        });
      } else {
        row.classList.remove('active');
        if (jenis === 'Pelatihan/Briefing' && innasRow) {
          innasRow.classList.remove('active');
          // Uncheck semua inda/innas supaya tidak terkirim saat jenis dibatalkan
          innasChecks.forEach(cb => cb.checked = false);
          if (innasSearch) innasSearch.value = '';
          innasItems.forEach(it => it.style.display = '');
          updateInnasCount();
        }
        row.querySelectorAll('input, select').forEach(i => i.required = false);
      }
    });
  }
  checks.forEach(c => c.addEventListener('change', toggleRows));
  toggleRows();

  // Checkbox Inda/Innas: update counter saat berubah
  innasChecks.forEach(cb => cb.addEventListener('change', updateInnasCount));

  // Pencarian cepat pada daftar Inda/Innas
  if (innasSearch) {
    innasSearch.addEventListener('input', function () {
      const q = this.value.trim().toLowerCase();
      innasItems.forEach(item => {
        const name = item.dataset.name || '';
        item.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
      });
    });
  }

  // Tombol "Pilih semua" hanya memilih yang terlihat (menghormati filter pencarian)
  if (btnSelectAll) {
    btnSelectAll.addEventListener('click', function () {
      innasItems.forEach(item => {
        if (item.style.display === 'none') return;
        const cb = item.querySelector('.innas-check');
        if (cb) cb.checked = true;
      });
      updateInnasCount();
    });
  }
  if (btnClear) {
    btnClear.addEventListener('click', function () {
      innasChecks.forEach(cb => cb.checked = false);
      updateInnasCount();
    });
  }

  // Format titik ribuan pada input Honor/Satuan (format Indonesia: 400.000)
  // - Menyimpan cursor relatif supaya mengetik tidak "melompat" ke akhir.
  // - Saat submit, titik dihapus supaya PHP bisa cast ke float tanpa salah.
  const honorInputs = document.querySelectorAll('.honor-input');
  function formatRibuan(v) {
    const digits = String(v).replace(/\D+/g, '');
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
  honorInputs.forEach(inp => {
    inp.addEventListener('input', function () {
      // Hitung jumlah digit sebelum posisi caret untuk menjaga posisi ketik.
      const before = this.value.slice(0, this.selectionStart || 0);
      const digitsBefore = (before.match(/\d/g) || []).length;
      const formatted = formatRibuan(this.value);
      this.value = formatted;
      // Pindahkan caret setelah ke-N digit (skip titik).
      let pos = 0, count = 0;
      while (pos < formatted.length && count < digitsBefore) {
        if (/\d/.test(formatted[pos])) count++;
        pos++;
      }
      this.setSelectionRange(pos, pos);
    });
    inp.addEventListener('blur', function () { this.value = formatRibuan(this.value); });
  });

  // Validasi form
  const form = document.querySelector('.needs-validation');
  form.addEventListener('submit', function (e) {
    // Minimal satu jenis harus dipilih
    const anyChecked = Array.from(checks).some(c => c.checked);
    if (!anyChecked) {
      e.preventDefault(); e.stopPropagation();
      alert('Minimal satu Jenis Kegiatan harus dipilih.');
      form.classList.add('was-validated');
      return;
    }
    // Validasi tanggal per jenis (mulai < selesai)
    let ok = true;
    document.querySelectorAll('.jenis-row.active').forEach(row => {
      const s = row.querySelector('.row-start');
      const e2 = row.querySelector('.row-end');
      if (s && e2 && s.value && e2.value && new Date(e2.value) < new Date(s.value)) {
        ok = false;
        e2.setCustomValidity('Tanggal selesai harus >= tanggal mulai');
      } else if (e2) {
        e2.setCustomValidity('');
      }
    });
    if (!ok || !form.checkValidity()) {
      e.preventDefault(); e.stopPropagation();
      return;
    }
    // Strip titik dari honor sebelum dikirim supaya server terima angka murni.
    honorInputs.forEach(inp => { inp.value = inp.value.replace(/\./g, ''); });
    form.classList.add('was-validated');
  });
})();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
