<?php
/**
 * ========================================
 * SIDEBAR - FIXED ROLE MAP
 * ========================================
 *
 * ROLE ID (sesuai database, mapping baru):
 * 1 = Admin
 * 2 = Kepala
 * 3 = Kasubbag
 * 4 = PPK
 * 5 = Bendahara
 * 6 = Operator
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Deteksi tipe user
$userType = $_SESSION['user']['user_type'] ?? 'user';
$isMitra = ($userType === 'mitra');

// Untuk user biasa, ambil role dari session
if (!$isMitra) {
  $roleId = $_SESSION['user']['role_id'];

  // ========================================
  // ROLE MAP YANG BENAR (sesuai database)
  // ========================================
  $roleMap = [
    1 => 'admin',
    2 => 'kepala',
    3 => 'kasubbag',
    4 => 'ppk',
    5 => 'bendahara',
    6 => 'operator'
  ];

  $role = $roleMap[$roleId] ?? '';
}

function isActive($menu)
{
  $current = $_GET['controller'] ?? 'dashboard';
  return ($current == $menu) ? 'active' : '';
}

// Active untuk "Anggota Tim" → hanya saat controller=tim & action bukan 'warna'
function isActiveAnggotaTim()
{
  $controller = $_GET['controller'] ?? 'dashboard';
  $action     = $_GET['action']     ?? 'index';
  return ($controller === 'tim' && $action !== 'warna') ? 'active' : '';
}

// Active untuk "Warna Tim" → hanya saat controller=tim & action=warna
function isActiveWarnaTim()
{
  $controller = $_GET['controller'] ?? 'dashboard';
  $action     = $_GET['action']     ?? 'index';
  return ($controller === 'tim' && $action === 'warna') ? 'active' : '';
}

// Active state untuk Data Administrasi (context=data)
function isActiveAdministrasiData()
{
  $controller = $_GET['controller'] ?? 'dashboard';
  $action = $_GET['action'] ?? 'index';
  $context = $_GET['context'] ?? '';
  
  return ($controller === 'administrasi' && ($action === 'index' || $action === '') && $context === 'data') ? 'active' : '';
}

// Active state untuk Input Administrasi Form
function isActiveAdministrasiForm()
{
  $controller = $_GET['controller'] ?? 'dashboard';
  $action = $_GET['action'] ?? 'index';

  return ($controller === 'administrasi' && $action === 'form') ? 'active' : '';
}
?>

<aside class="sidebar d-flex flex-column shadow-sm">
  <h5 class="text-center">WANDAI</h5>

  <ul class="nav nav-pills flex-column gap-1">
    
    <!-- 1. DASHBOARD - SEMUA USER -->
    <li class="nav-item">
      <a class="nav-link <?= isActive('dashboard') ?>" href="index.php?controller=dashboard">
        <i class="bi bi-house-door-fill me-2"></i>
        <span class="nav-link-text">Dashboard</span>
      </a>
    </li>

    <?php if ($isMitra): ?>
      <!-- MENU KHUSUS MITRA -->
      <li class="nav-item">
        <a class="nav-link <?= isActiveAdministrasiForm() ?>" href="index.php?controller=administrasi&action=form">
          <i class="bi bi-folder-check me-2"></i>
          <span class="nav-link-text">Input Administrasi</span>
        </a>
      </li>
      
      <li class="nav-item">
        <a class="nav-link <?= isActiveAdministrasiData() ?>" href="index.php?controller=administrasi&context=data">
          <i class="bi bi-database-check me-2"></i>
          <span class="nav-link-text">Data Administrasi</span>
        </a>
      </li>

    <?php else: ?>
      <!-- MENU USER (Admin, Kepala, PPK, Operator, Bendahara) -->
      
      <!-- 2. Anggota Tim - Admin Only -->
      <?php if ($role == 'admin'): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActiveAnggotaTim() ?>" href="index.php?controller=tim">
            <i class="bi bi-people-fill me-2"></i>
            <span class="nav-link-text">Anggota Tim</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= isActiveWarnaTim() ?>"
             href="index.php?controller=tim&action=warna">
            <i class="bi bi-palette-fill me-2"></i>
            <span class="nav-link-text">Warna Tim</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- 3. Input Mitra - Admin Only -->
      <?php if ($role === 'admin'): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActive('mitra') ?>" href="index.php?controller=mitra">
            <i class="bi bi-people-fill me-2"></i>
            <span class="nav-link-text">Input Mitra</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- 4. Input Kegiatan - Admin, Operator -->
      <?php if (in_array($role, ['admin', 'operator'])): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActive('kegiatan') ?>" href="index.php?controller=kegiatan">
            <i class="bi bi-journal-text me-2"></i>
            <span class="nav-link-text">Input Kegiatan</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- 5. Input Petugas - Admin, Operator -->
      <?php if (in_array($role, ['admin', 'operator'])): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActive('petugas_kegiatan') ?>" href="index.php?controller=petugas_kegiatan">
            <i class="bi bi-person-check-fill me-2"></i>
            <span class="nav-link-text">Input Petugas</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- 6. Input Administrasi - Admin, Operator -->
      <?php if (in_array($role, ['admin', 'operator'])): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActiveAdministrasiForm() ?>" href="index.php?controller=administrasi&action=form">
            <i class="bi bi-folder-check me-2"></i>
            <span class="nav-link-text">Input Administrasi</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- DATA ADMINISTRASI - Semua role -->
      <?php if (in_array($role, ['admin', 'kepala', 'kasubbag', 'ppk', 'operator', 'bendahara'])): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActiveAdministrasiData() ?>" href="index.php?controller=administrasi&context=data">
            <i class="bi bi-database-check me-2"></i>
            <span class="nav-link-text">Data Administrasi</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- 10. Beban Kerja - Admin & Operator -->
      <?php if (in_array($roleId, [1, 6])): ?>
        <li class="nav-item">
          <a class="nav-link <?= isActive('beban_kerja') ?>" href="index.php?controller=beban_kerja">
            <i class="bi bi-graph-up-arrow me-2"></i>
            <span class="nav-link-text">Beban Kerja</span>
          </a>
        </li>
      <?php endif; ?>

    <?php endif; ?>

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