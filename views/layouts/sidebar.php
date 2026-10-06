<?php
/**
 * SIDEBAR — Portal SPJ
 * ROLE ID: 1=Admin, 2=Kepala, 3=Kasubbag, 4=PPK, 5=Bendahara, 6=Operator
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$userType = $_SESSION['user']['user_type'] ?? 'user';
$isMitra  = ($userType === 'mitra');

if (!$isMitra) {
  $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
  $roleMap = [1=>'admin',2=>'kepala',3=>'kasubbag',4=>'ppk',5=>'bendahara',6=>'operator'];
  $role = $roleMap[$roleId] ?? '';
} else {
  $roleId = 0;
  $role   = '';
}


function isActive($menu)
{
  $current = $_GET['controller'] ?? 'dashboard';
  return ($current == $menu) ? 'active' : '';
}

function isActiveAnggotaTim()
{
  $c = $_GET['controller'] ?? 'dashboard';
  $a = $_GET['action']     ?? 'index';
  return ($c === 'tim' && $a !== 'warna') ? 'active' : '';
}

function isActiveWarnaTim()
{
  $c = $_GET['controller'] ?? 'dashboard';
  $a = $_GET['action']     ?? 'index';
  return ($c === 'tim' && $a === 'warna') ? 'active' : '';
}

function isActiveAdministrasiForm()
{
  $c = $_GET['controller'] ?? 'dashboard';
  $a = $_GET['action'] ?? 'index';
  return ($c === 'administrasi' && $a === 'form') ? 'active' : '';
}
?>

<aside class="sidebar d-flex flex-column shadow-sm">

  <?php if ($isMitra): ?>
    <h5 class="text-center" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
      <i class="bi bi-person-check-fill me-1" style="font-size:.9rem"></i> MITRA
    </h5>
  <?php else: ?>
    <h5 class="text-center">WANDAI</h5>
  <?php endif; ?>

  <ul class="nav nav-pills flex-column gap-1">

    <?php if ($isMitra): ?>
      <!-- MENU KHUSUS MITRA -->
      <li class="nav-item">
        <a class="nav-link <?= (($_GET['controller'] ?? '') === 'mitra' && ($_GET['action'] ?? '') === 'portal') ? 'active' : '' ?>"
           href="index.php?controller=mitra&action=portal">
          <i class="bi bi-cloud-upload-fill me-2"></i>
          <span class="nav-link-text">Portal Saya</span>
        </a>
      </li>

    <?php else: ?>
      <!-- MENU USER -->

      <!-- Dashboard -->
      <li class="nav-item">
        <a class="nav-link <?= isActive('dashboard') ?>" href="index.php?controller=dashboard">
          <i class="bi bi-house-door-fill me-2"></i>
          <span class="nav-link-text">Dashboard</span>
        </a>
      </li>

      <!-- Anggota Tim & Warna Tim - Admin Only -->
      <?php if ($role === 'admin'): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActiveAnggotaTim() ?>" href="index.php?controller=tim">
            <i class="bi bi-people-fill me-2"></i>
            <span class="nav-link-text">Anggota Tim</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= isActiveWarnaTim() ?>" href="index.php?controller=tim&action=warna">
            <i class="bi bi-palette-fill me-2"></i>
            <span class="nav-link-text">Warna Tim</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= isActive('mitra') ?>" href="index.php?controller=mitra">
            <i class="bi bi-people-fill me-2"></i>
            <span class="nav-link-text">Input Mitra</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= isActive('master_wilayah') ?>" href="index.php?controller=master_wilayah">
            <i class="bi bi-map me-2"></i>
            <span class="nav-link-text">Master Wilayah</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= isActive('sync') ?>" href="index.php?controller=sync">
            <i class="bi bi-arrow-repeat me-2"></i>
            <span class="nav-link-text">Sync Pegawai</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- Input Kegiatan - Semua role -->
      <li class="nav-item">
        <a class="nav-link <?= isActive('kegiatan') ?>" href="index.php?controller=kegiatan">
          <i class="bi bi-journal-text me-2"></i>
          <span class="nav-link-text">Input Kegiatan</span>
        </a>
      </li>

      <!-- Input Petugas - Admin, Operator -->
      <?php if (in_array($role, ['admin', 'operator'])): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActive('petugas_kegiatan') ?>" href="index.php?controller=petugas_kegiatan">
            <i class="bi bi-person-check-fill me-2"></i>
            <span class="nav-link-text">Input Petugas</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- Input Administrasi - Semua role -->
      <li class="nav-item">
        <a class="nav-link <?= isActiveAdministrasiForm() ?>" href="index.php?controller=administrasi&action=form">
          <i class="bi bi-folder-check me-2"></i>
          <span class="nav-link-text">Input Administrasi</span>
        </a>
      </li>

      <!-- Beban Kerja - Admin, Kepala, Kasubbag, PPK, Bendahara -->
      <?php if (in_array($roleId, [1, 2, 3, 4, 5])): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActive('beban_kerja') ?>" href="index.php?controller=beban_kerja">
            <i class="bi bi-graph-up-arrow me-2"></i>
            <span class="nav-link-text">Beban Kerja</span>
          </a>
        </li>
      <?php endif; ?>

    <?php endif; /* end !$isMitra */ ?>

  </ul>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const navLinks = document.querySelectorAll('.sidebar .nav-link');
  navLinks.forEach(link => {
    link.setAttribute('title', link.textContent.trim());
  });
});
</script>
