<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../layouts/header.php';

/**
 * Cek apakah warna hex cukup gelap sehingga teks putih lebih jelas.
 * Return true kalau gelap (→ pakai teks putih), false kalau terang (→ pakai teks gelap).
 */
function isWarnaGelap($hex)
{
    $hex = ltrim((string)$hex, '#');
    if (strlen($hex) !== 6) return true;
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    // Luminance sederhana (rec. 709).
    $lum = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    return $lum < 0.6;
}
?>

<div class="d-flex">
    <div class="sidebar">
      <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    </div>
    <div class="flex-grow-1">


    <div class="content-wrapper p-4">
      <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
          <h5 class="mb-0"><i class="bi bi-people-fill"></i> Master Anggota Tim</h5>
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-person-plus-fill"></i> Tambah Anggota Tim
          </button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle" id="datatable">
              <thead class="table-light">
                <tr>
                  <th style="width: 50px;">No</th>
                  <th>Nama</th>
                  <th>Username</th>
                  <th>Role</th>
                  <th>Tim</th>
                  <th style="width: 120px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1;
                foreach ($users as $user): ?>
                  <?php
                    $timNames = trim((string)($user['team_names'] ?? '')) !== ''
                              ? explode('||', $user['team_names'])
                              : [];
                    $timWarnas = trim((string)($user['team_warnas'] ?? '')) !== ''
                              ? explode('||', $user['team_warnas'])
                              : [];
                    $timIdsCsv = $user['team_ids'] ?? '';
                  ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= ucfirst($user['role_name'] ?? '') ?></span></td>
                    <td>
                      <?php if (!empty($timNames)): ?>
                        <?php foreach ($timNames as $i => $tn): ?>
                          <?php
                            $warna = $timWarnas[$i] ?? '#6c757d';
                            $textColor = isWarnaGelap($warna) ? '#ffffff' : '#1f2937';
                          ?>
                          <span class="badge tim-badge me-1 mb-1"
                                style="background:<?= htmlspecialchars($warna) ?>; color:<?= $textColor ?>;">
                            <?= htmlspecialchars($tn) ?>
                          </span>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <span class="text-muted small">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= (int)$user['id'] ?>"
                        data-name="<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>"
                        data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>"
                        data-role="<?= (int)$user['role_id'] ?>"
                        data-teams="<?= htmlspecialchars($timIdsCsv, ENT_QUOTES) ?>"
                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                        <i class="bi bi-pencil-fill"></i>
                      </button>
                      <a href="index.php?controller=tim&action=hapus&id=<?= (int)$user['id'] ?>"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('Yakin hapus?')">
                        <i class="bi bi-trash-fill"></i>
                      </a>
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

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="index.php?controller=tim&action=simpan">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Tambah Anggota</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" name="name" class="form-control mb-2" placeholder="Nama" required>
        <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
        <input type="text" name="password" class="form-control mb-2" placeholder="Password" required>
        <select name="role_id" class="form-select mb-2 role-select" required>
          <option value="">-- Pilih Role --</option>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int)$role['id'] ?>"><?= ucfirst($role['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <!-- Tim: hanya muncul untuk Operator (role_id = 6) -->
        <div class="team-wrapper d-none">
          <label class="form-label fw-semibold mb-1">
            <i class="bi bi-people"></i> Pilih Tim
            <small class="text-muted fw-normal">(boleh lebih dari satu)</small>
          </label>
          <div class="team-checklist border rounded p-2" style="max-height:180px; overflow-y:auto;">
            <?php foreach ($teams as $team): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="team_ids[]" value="<?= (int)$team['id'] ?>"
                       id="tambah-team-<?= (int)$team['id'] ?>">
                <label class="form-check-label" for="tambah-team-<?= (int)$team['id'] ?>">
                  <?= htmlspecialchars($team['name']) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="index.php?controller=tim&action=update">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Edit Anggota</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit-id">
        <input type="text" name="name" id="edit-name" class="form-control mb-2" placeholder="Nama" required>
        <input type="text" name="username" id="edit-username" class="form-control mb-2" placeholder="Username" required>
        <select name="role_id" id="edit-role" class="form-select mb-2 role-select" required>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int)$role['id'] ?>"><?= ucfirst($role['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <!-- Tim: hanya muncul untuk Operator (role_id = 6) -->
        <div class="team-wrapper d-none">
          <label class="form-label fw-semibold mb-1">
            <i class="bi bi-people"></i> Pilih Tim
            <small class="text-muted fw-normal">(boleh lebih dari satu)</small>
          </label>
          <div class="team-checklist border rounded p-2" style="max-height:180px; overflow-y:auto;">
            <?php foreach ($teams as $team): ?>
              <div class="form-check">
                <input class="form-check-input edit-team-cb" type="checkbox"
                       name="team_ids[]" value="<?= (int)$team['id'] ?>"
                       id="edit-team-<?= (int)$team['id'] ?>">
                <label class="form-check-label" for="edit-team-<?= (int)$team['id'] ?>">
                  <?= htmlspecialchars($team['name']) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Role id Operator di database (mapping baru): 6
  const ROLE_OPERATOR = 6;

  // Perlihatkan/sembunyikan bagian tim berdasarkan role yang dipilih.
  // Tim hanya relevan untuk role Operator.
  function toggleTeamWrapper(modalEl) {
    const roleSel = modalEl.querySelector('.role-select');
    const wrapper = modalEl.querySelector('.team-wrapper');
    if (!roleSel || !wrapper) return;
    const isOperator = parseInt(roleSel.value, 10) === ROLE_OPERATOR;
    wrapper.classList.toggle('d-none', !isOperator);
    // Kalau bukan operator, uncheck semua supaya tidak terkirim.
    if (!isOperator) {
      wrapper.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    }
  }

  document.addEventListener('change', function (e) {
    if (e.target && e.target.classList.contains('role-select')) {
      const modalEl = e.target.closest('.modal');
      if (modalEl) toggleTeamWrapper(modalEl);
    }
  });

  // Setiap kali modal dibuka, evaluasi ulang status tim.
  document.addEventListener('shown.bs.modal', function (e) {
    toggleTeamWrapper(e.target);
  });

  // Populate modal edit dari tombol edit.
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-edit');
    if (!btn) return;
    const modalEl = document.getElementById('modalEdit');
    modalEl.querySelector('#edit-id').value       = btn.dataset.id       || '';
    modalEl.querySelector('#edit-name').value     = btn.dataset.name     || '';
    modalEl.querySelector('#edit-username').value = btn.dataset.username || '';
    modalEl.querySelector('#edit-role').value     = btn.dataset.role     || '';

    // Reset dan centang checkbox sesuai data-teams (CSV).
    const csv = (btn.dataset.teams || '').trim();
    const selected = csv ? csv.split(',').map(s => s.trim()) : [];
    modalEl.querySelectorAll('.edit-team-cb').forEach(cb => {
      cb.checked = selected.includes(cb.value);
    });

    toggleTeamWrapper(modalEl);
  });

  $(function () {
    $('#datatable').DataTable({
      // TOP: length di kiri (auto width), search di kanan (ms-auto -> dorong ke kanan)
      dom:
        "<'row g-2 align-items-center mb-2'<'col-auto'l><'col ms-auto text-end'f>>" +
        "rt" +
        // BOTTOM: info kiri, paging kanan
        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
      language: {
        lengthMenu: "Tampilkan _MENU_ entri",
        search: "Cari:",
        info: "Menampilkan _START_–_END_ dari _TOTAL_ entri",
        paginate: { previous: "Sebelumnya", next: "Berikutnya" }
      }
    });

    // Perapihan kontrol
    $('#datatable_length select').addClass('form-select form-select-sm');
    $('#datatable_filter input')
      .addClass('form-control form-control-sm')
      .attr('placeholder', 'Cari anggota…');

    // Pastikan kontainer-nya benar-benar rata kanan
    $('#datatable_filter').addClass('d-flex justify-content-end');
    $('#datatable_length').addClass('d-flex align-items-center gap-2');
  });
</script>

<style>
  /* Badge tim mengikuti warna tim yang juga dipakai di Dashboard Matriks */
  .tim-badge{
    font-weight:600;
    letter-spacing:.2px;
    padding:.4rem .6rem;
    border-radius:.6rem;
  }
</style>

<style>
  /* Pagination benar-benar rata kanan */
  #datatable_wrapper .dataTables_paginate{
    display: flex;
    justify-content: flex-end;
    gap: .25rem;
  }

  /* Info kiri, pagination kanan pada bar bawah */
  #datatable_wrapper .row:last-child{
    display: flex;
    align-items: center;
  }
  #datatable_wrapper .dataTables_info{
    margin-right: auto; /* dorong info ke kiri */
  }

  /* (Opsional) kosmetik tombol halaman */
  #datatable_wrapper .dataTables_paginate .paginate_button{
    border-radius: .5rem;
    padding: .35rem .6rem;
  }
</style>


<style>
  /* Typography konsisten */
  .dataTables_wrapper,
  .dataTables_wrapper .dataTables_filter input,
  .dataTables_wrapper .dataTables_length select,
  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate,
  table, .table, .badge, .btn, .form-control, .form-select {
    font-family: 'Poppins', sans-serif !important;
  }

  /* Label length & filter rapi */
  #datatable_wrapper .dataTables_length label,
  #datatable_wrapper .dataTables_filter label{
    margin: 0;
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  /* Input search lebar wajar tapi tetap responsif */
  #datatable_wrapper .dataTables_filter input{
    width: 100%;
    max-width: 320px;
  }
</style>


<style>
  .dataTables_wrapper,
  .dataTables_wrapper .dataTables_filter input,
  .dataTables_wrapper .dataTables_length select,
  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate,
  table, .table, .badge, .btn, .form-control, .form-select {
    font-family: 'Poppins', sans-serif !important;
  }

  /* rapihkan label length & filter */
  #datatable_wrapper .dataTables_length label,
  #datatable_wrapper .dataTables_filter label{
    margin: 0;
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  /* input search lebar pas, responsif */
  #datatable_wrapper .dataTables_filter input{
    max-width: 260px;
    width: 100%;
  }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>