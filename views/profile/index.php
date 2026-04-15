<?php
/**
 * Halaman Akun Saya — ubah username (email) & password.
 * $user      : detail akun (ProfileController::index()).
 * $userTeams : array tim user (banyak-ke-banyak) — id, name, warna, is_primary.
 */
include 'views/layouts/header.php';
include 'views/layouts/sidebar.php';

if (!function_exists('profileIsWarnaGelap')) {
    /** Luminance sederhana — tentukan warna teks badge tim. */
    function profileIsWarnaGelap($hex) {
        $hex = ltrim((string)$hex, '#');
        if (strlen($hex) !== 6) return true;
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return ((0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255) < 0.6;
    }
}
?>

<div class="content-wrapper">
  <div class="container-fluid px-4">

    <h1 class="page-title fw-bold mb-1">
      <i class="bi bi-person-circle me-2"></i>Akun Saya
    </h1>
    <p class="text-muted mb-4">Kelola username (email) dan password akun Anda.</p>

    <?php if (!empty($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i>
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row g-4">

      <!-- Identitas (read-only) -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="text-muted mb-3"><i class="bi bi-person-vcard me-1"></i>Identitas</h6>
            <table class="table table-sm mb-0">
              <tr><th style="width:40%">Nama</th><td><?= htmlspecialchars($user['nama_lengkap'] ?? $user['name']) ?></td></tr>
              <tr><th>NIP</th><td><?= htmlspecialchars($user['nip'] ?? '-') ?></td></tr>
              <tr><th>Jabatan</th><td><?= htmlspecialchars($user['jabatan'] ?? '-') ?></td></tr>
              <tr><th>Pangkat</th><td><?= htmlspecialchars($user['pangkat'] ?? '-') ?> (<?= htmlspecialchars($user['golongan'] ?? '-') ?>)</td></tr>
              <tr><th>Role</th><td><?= htmlspecialchars($user['role_name'] ?? '-') ?></td></tr>
              <tr>
                <th>Tim</th>
                <td>
                  <?php if (!empty($userTeams)): ?>
                    <div class="d-flex flex-column gap-1">
                      <?php foreach ($userTeams as $tim): ?>
                        <?php
                          $warna = $tim['warna'] ?: '#6c757d';
                          $textColor = profileIsWarnaGelap($warna) ? '#ffffff' : '#1f2937';
                        ?>
                        <span class="badge align-self-start"
                              style="background:<?= htmlspecialchars($warna) ?>; color:<?= $textColor ?>;">
                          <?= htmlspecialchars($tim['name']) ?>
                        </span>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            </table>
            <small class="text-muted d-block mt-2">
              <i class="bi bi-info-circle"></i> Identitas hanya bisa diubah oleh Admin.
            </small>
          </div>
        </div>
      </div>

      <!-- Form ubah username -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="text-muted mb-3"><i class="bi bi-at me-1"></i>Ubah Username (Email)</h6>
            <form method="POST" action="index.php?controller=profile&action=updateUsername">
              <div class="mb-3">
                <label class="form-label">Username saat ini</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">Username baru <span class="text-danger">*</span></label>
                <input type="text" name="email" class="form-control" required
                       value="<?= htmlspecialchars($user['email']) ?>"
                       placeholder="contoh: nama@bps.go.id">
                <small class="text-muted">Boleh berupa email atau username pendek (tanpa spasi).</small>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-save me-1"></i> Simpan Username
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Form ubah password -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="text-muted mb-3"><i class="bi bi-key me-1"></i>Ubah Password</h6>
            <form method="POST" action="index.php?controller=profile&action=updatePassword">
              <div class="mb-3">
                <label class="form-label">Password lama <span class="text-danger">*</span></label>
                <input type="password" name="old_password" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password baru <span class="text-danger">*</span></label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
                <small class="text-muted">Minimal 6 karakter.</small>
              </div>
              <div class="mb-3">
                <label class="form-label">Konfirmasi password baru <span class="text-danger">*</span></label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
              </div>
              <button type="submit" class="btn btn-warning w-100">
                <i class="bi bi-shield-lock me-1"></i> Ganti Password
              </button>
              <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle"></i> Password disimpan dalam bentuk terenkripsi (bcrypt) — tidak bisa dilihat ulang oleh siapa pun, termasuk Admin.
              </small>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
