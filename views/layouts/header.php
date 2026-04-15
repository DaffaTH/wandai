<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

date_default_timezone_set('Asia/Jakarta');

$name = $_SESSION['user']['name'] ?? '-';
$role = $_SESSION['user']['role_name'] ?? '-';
$userType = $_SESSION['user']['user_type'] ?? 'user';
$isMitra = ($userType === 'mitra');
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Dashboard - Wandai</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
  /* === OPTIMIZED CSS - CONSOLIDATED & PERFORMANCE ENHANCED === */
  
  /* CSS Variables - All in one place */
  :root {
    --bs-body-font-family: 'Poppins', sans-serif;
    --bs-font-sans-serif: 'Poppins', sans-serif;
    --primary-color: #F3C623;
    --bg-soft: #FEF3E2;
    --accent-1: #FFB22C;
    --accent-2: #FA812F;
    --sidebar-width: 250px;
    --header-height: 70px;
    --dark-primary: #1a1a1a;
    --dark-secondary: #2d3748;
    --orange-gradient: linear-gradient(135deg, #ff6b35 0%, #d84315 100%);
    --terracotta-gradient: linear-gradient(135deg, #ff8a65 0%, #bf5f36 100%);
    --primary-gradient: linear-gradient(135deg, #ff6b35 0%, #ff8c42 25%, #ffa726 50%, #ff7043 75%, #d84315 100%);
    --secondary-gradient: linear-gradient(135deg, #ff8a65 0%, #bf5f36 100%);
    --tertiary-gradient: linear-gradient(135deg, #ffcc80 0%, #ff8c42 100%);
    --success-gradient: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
    --warning-gradient: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    --danger-gradient: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
    --glass-bg: rgba(255, 255, 255, 0.25);
    --glass-border: rgba(255, 255, 255, 0.18);
    --glass-white: rgba(255, 255, 255, 0.95);
    --glass-orange: rgba(255, 140, 66, 0.1);
    --soft-orange: #fff3e0;
    --light-orange: #ffcc80;
    --orange-500: #ff7043;
    --orange-600: #ff5722;
  }

  /* Performance & Base Styles */
  * {
    will-change: auto;
    box-sizing: border-box;
  }

  body {
    background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 25%, #fff5f0 50%, #ffeee6 75%, #fff3e0 100%);
    font-family: var(--bs-body-font-family);
    min-height: 100vh;
    margin: 0;
    padding: 0;
  }

  .flatpickr-calendar { z-index: 2500 !important; }

  /* Modern Navbar */
  .modern-navbar {
    height: var(--header-height);
    background: rgba(255, 255, 255, 0.98);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    transition: box-shadow 0.2s ease;
  }

  .navbar-brand-modern {
    font-weight: 700;
    font-size: 1.3rem;
    background: linear-gradient(45deg, #ff8c42 0%, #ffa726 30%, #4ade80 65%, #3b82f6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.5px;
  }

  .navbar-logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    transition: transform 0.15s ease;
    will-change: transform;
    backface-visibility: hidden;
  }

  .navbar-logo:hover {
    transform: scale(1.05) translateZ(0);
    filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.15));
  }

  .user-profile {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 8px 16px;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 50px;
    backdrop-filter: blur(10px);
    transition: all 0.2s ease;
  }

  .user-profile:hover {
    background: rgba(255, 255, 255, 0.4);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }

  .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--orange-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
  }

  .user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }

  .user-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--dark-primary);
    margin: 0;
    line-height: 1.2;
  }

  .user-role {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
    font-weight: 500;
  }

  .logout-btn {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    border: none;
    border-radius: 25px;
    padding: 8px 20px;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3);
  }

  .logout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 65, 108, 0.4);
    color: white;
  }

  .logout-btn:active {
    transform: translateY(0);
  }

  /* Main Content */
  .main-content {
    margin-top: var(--header-height);
    min-height: calc(100vh - var(--header-height));
  }

  /* Footer */
  footer {
    background: rgba(255, 255, 255, 0.95);
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    margin-top: 2rem;
    padding: 1rem 0;
    color: #6b7280;
    font-size: 0.875rem;
  }

  /* Sidebar */
  .sidebar {
    background: rgba(255, 255, 255, 0.98);
    border-right: 1px solid rgba(255, 255, 255, 0.2);
    width: var(--sidebar-width);
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
    z-index: 999;
    padding-top: var(--header-height);
  }

  .sidebar h5 {
    padding: 1.5rem 1.2rem;
    font-size: 1.1rem;
    background: var(--terracotta-gradient);
    color: #fff;
    font-weight: 700;
    margin: 0;
    text-align: center;
    letter-spacing: 1px;
    text-transform: uppercase;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: var(--header-height);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* Modern Sidebar Navigation */
  .sidebar .nav {
    padding-top: 20px;
    height: calc(100vh - var(--header-height));
    overflow-y: auto;
    scroll-behavior: smooth;
  }

  .sidebar .nav-link {
    color: #374151;
    font-weight: 500;
    padding: 12px 20px;
    border-radius: 12px;
    margin: 4px 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    user-select: none;
    transition: all 0.1s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateZ(0);
    backface-visibility: hidden;
    perspective: 1000px;
    will-change: transform, background-color, color, box-shadow;
    contain: layout style paint;
    -webkit-tap-highlight-color: transparent;
  }

  .sidebar .nav-link::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.4s ease-out, height 0.4s ease-out, opacity 0.4s ease-out;
    opacity: 0;
    pointer-events: none;
    z-index: 1;
  }

  .sidebar .nav-link.ripple::before {
    width: 200px;
    height: 200px;
    opacity: 1;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                height 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                opacity 0.4s ease-out;
  }

  .sidebar .nav-link:hover {
    color: #fff;
    transform: translateX(8px) translateZ(0);
    background: var(--terracotta-gradient);
    box-shadow: 0 4px 12px rgba(191, 95, 54, 0.25), 0 1px 3px rgba(191, 95, 54, 0.12);
  }

  .sidebar .nav-link.active {
    color: #fff;
    background: var(--terracotta-gradient);
    transform: translateX(6px) translateZ(0);
    box-shadow: 0 6px 16px rgba(191, 95, 54, 0.3), 0 2px 4px rgba(191, 95, 54, 0.15);
  }

  .sidebar .nav-link:active,
  .sidebar .nav-link.clicking {
    transform: translateX(4px) scale(0.96) translateZ(0);
    transition: all 0.05s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(191, 95, 54, 0.4), inset 0 1px 2px rgba(0, 0, 0, 0.1);
    background: var(--terracotta-gradient);
    color: #fff;
  }

  .sidebar .nav-link i {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
    transition: transform 0.1s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
    position: relative;
  }

  .sidebar .nav-link:hover i,
  .sidebar .nav-link.active i {
    transform: scale(1.1) rotate(3deg) translateZ(0);
  }

  .sidebar .nav-link:active i,
  .sidebar .nav-link.clicking i {
    transform: scale(1.05) rotate(-1deg) translateZ(0);
  }

  .nav-link-text {
    z-index: 2;
    position: relative;
    transition: transform 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  .sidebar .nav-link:hover .nav-link-text {
    transform: translateX(2px) translateZ(0);
  }

  .sidebar .nav-link:focus {
    outline: 2px solid #ff8a65;
    outline-offset: 2px;
  }

  /* Content wrapper */
  .content-wrapper {
    margin-left: var(--sidebar-width);
    padding: 1.5rem;
    min-height: calc(100vh - var(--header-height));
    padding-top: 1rem;
  }

  /* Status Badges */
  .status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: 8px;
  }

  .status-mitra {
    background: var(--orange-gradient);
    color: white;
  }

  .status-admin {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
  }

  /* Container fluid */
  .container-fluid {
    width: 100%;
    padding-left: 0;
    padding-right: 0;
  }

  /* Component Styles - Consolidated */
  .card, .filter-card, .filter-section {
    background: var(--glass-white) !important;
    border: 1px solid rgba(255, 140, 66, 0.12) !important;
    border-radius: 16px !important;
    box-shadow: 0 2px 12px rgba(255, 112, 67, 0.04) !important;
  }

  .page-header, .monitoring-header {
    background: var(--glass-white);
    border: 1px solid rgba(255, 140, 66, 0.15);
    box-shadow: 0 4px 16px rgba(255, 112, 67, 0.06);
  }

  h1, .page-title, .monitoring-title {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* Buttons - All consolidated */
  .btn-primary, 
  .btn[style*="background-color: #28a745"], 
  .btn-success,
  button.btn.btn-success,
  a.btn.btn-success {
    background: var(--success-gradient) !important;
    border: none !important;
    color: white !important;
    border-radius: 25px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 4px 12px rgba(74, 222, 128, 0.25) !important;
  }

  .btn-primary:hover, 
  .btn-success:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 16px rgba(74, 222, 128, 0.35) !important;
    color: white !important;
  }

  .btn[style*="background-color: #007bff"],
  .btn-info,
  .btn-filter {
    background: var(--primary-gradient) !important;
    border: none !important;
    color: white !important;
    border-radius: 12px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 3px 12px rgba(255, 112, 67, 0.25) !important;
  }

  .btn-info:hover, .btn-filter:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 16px rgba(255, 112, 67, 0.35) !important;
    color: white !important;
  }

  .btn-warning {
    background: var(--warning-gradient) !important;
    border: none !important;
    color: white !important;
    border-radius: 8px !important;
    transition: all 0.2s ease !important;
  }

  .btn-warning:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 3px 10px rgba(245, 158, 11, 0.3) !important;
    color: white !important;
  }

  .btn-danger {
    background: var(--danger-gradient) !important;
    border: none !important;
    color: white !important;
    border-radius: 8px !important;
    transition: all 0.2s ease !important;
  }

  .btn-danger:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 3px 10px rgba(239, 68, 68, 0.3) !important;
    color: white !important;
  }

  /* Form Controls - All consolidated */
  .form-control, .form-select {
    border: 2px solid rgba(255, 140, 66, 0.15) !important;
    border-radius: 12px !important;
    background: var(--glass-white) !important;
    transition: all 0.2s ease !important;
  }

  .form-control:focus, .form-select:focus {
    border-color: var(--orange-500) !important;
    box-shadow: 0 0 0 3px rgba(255, 112, 67, 0.08) !important;
    background: white !important;
  }

  input[type="date"], select.form-control, select.form-select {
    border: 2px solid rgba(255, 140, 66, 0.15) !important;
    border-radius: 12px !important;
    background: var(--glass-white) !important;
  }

  input[type="search"], .form-control[placeholder*="Cari"] {
    border: 2px solid rgba(255, 140, 66, 0.15) !important;
    border-radius: 20px !important;
    background: var(--glass-white) !important;
  }

  /* Tables - All consolidated */
  .table-responsive, .card-body {
    background: var(--glass-white) !important;
    border-radius: 16px !important;
    overflow: hidden !important;
    box-shadow: 0 4px 16px rgba(255, 112, 67, 0.04) !important;
  }

  .table thead th, 
  .bg-secondary,
  .bg-dark {
    background: #bf5f36 !important;
    color: white !important;
    font-weight: 600 !important;
    border: none !important;
  }

  .table tbody td {
    background: var(--glass-white) !important;
    border-bottom: 1px solid rgba(255, 140, 66, 0.08) !important;
    border-left: none !important;
    border-right: none !important;
  }

  .table tbody tr:hover {
    background: var(--glass-orange) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(255, 112, 67, 0.04);
  }

  /* Progress Bars - All consolidated */
  .progress {
    background: rgba(255, 140, 66, 0.15) !important;
    border-radius: 8px !important;
  }

  .progress-bar {
    border-radius: 8px !important;
  }

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

  /* Pagination - All consolidated */
  .pagination .page-link {
    color: var(--orange-500) !important;
    border: 1px solid rgba(255, 140, 66, 0.15) !important;
    background: transparent !important;
    border-radius: 8px !important;
    margin: 0 2px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
  }

  .pagination .page-link:hover,
  .pagination .page-item.active .page-link {
    background: var(--primary-gradient) !important;
    border-color: var(--orange-500) !important;
    color: white !important;
    transform: translateY(-1px) !important;
  }

  /* Badges - All consolidated */
  .badge {
    border-radius: 16px !important;
    font-weight: 500 !important;
    padding: 5px 10px !important;
  }

  .badge-success, .bg-success {
    background: var(--success-gradient) !important;
    color: white !important;
  }

  .badge-warning, .bg-warning {
    background: var(--warning-gradient) !important;
    color: white !important;
  }

  .badge-danger, .bg-danger {
    background: var(--danger-gradient) !important;
    color: white !important;
  }

  .badge-info, .bg-info {
    background: var(--tertiary-gradient) !important;
    color: white !important;
  }

  /* Text Colors */
  .text-muted {
    color: #64748b !important;
  }

  .text-dark, .text-primary {
    color: #2d3748 !important;
  }

  /* Dashboard Chart Styling */
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
    background: var(--primary-gradient);
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

  /* Modal & Specific UI Components */
  .monitoring-row-pml {
    background-color: #fff3cd !important;
    font-weight: 600;
  }

  .monitoring-row-ppl {
    background-color: #f8f9fa;
  }

  .ppl-indent {
    padding-left: 1.5rem;
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

  /* Animations - All consolidated */
  @keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  @keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  @keyframes pulseGlow {
    0% { box-shadow: 0 0 0 0 rgba(191, 95, 54, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(191, 95, 54, 0.1); }
    100% { box-shadow: 0 0 0 0 rgba(191, 95, 54, 0); }
  }

  .sidebar .nav-link.loading {
    animation: pulseGlow 0.6s infinite;
  }
  
  /* Immediate click feedback */
  .sidebar .nav-link.clicking {
    pointer-events: auto;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* DataTables Styling - Consolidated */
  .dataTables_wrapper,
  .dataTables_wrapper .dataTables_filter input,
  .dataTables_wrapper .dataTables_length select,
  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate,
  table, .table, .badge, .btn, .form-control, .form-select {
    font-family: 'Poppins', sans-serif !important;
  }

  .dataTables_wrapper .row.align-items-center { 
    align-items: center !important; 
  }

  .dataTables_wrapper .dataTables_length label,
  .dataTables_wrapper .dataTables_filter label {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    margin: 0;
  }

  .dataTables_wrapper .dataTables_filter {
    text-align: right;
  }

  .dataTables_wrapper .dataTables_filter input {
    max-width: 320px;
    width: 100%;
  }

  .dataTables_wrapper .dataTables_paginate {
    display: flex;
    justify-content: flex-end;
    margin: 0;
  }

  .dataTables_wrapper .dataTables_paginate .pagination {
    margin: 0;
    justify-content: flex-end;
  }

  /* Performance Optimization Utilities */
  select option {
    background: white !important;
    color: #2d3748 !important;
  }

  .dropdown-menu {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  }

  .dropdown-item {
    padding: 0.5rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
  }

  .dropdown-item:last-child {
    border-bottom: none;
  }

  .dropdown-item:hover {
    background-color: #f8f9fa;
  }

  .dropdown-item.active {
    background-color: #0d6efd;
    color: white;
  }

  .petugas-info {
    font-size: 0.875rem;
    color: #6c757d;
  }

  /* Login Page Specific - Kept for compatibility */
  .login-container {
    position: relative;
    z-index: 10;
    animation: slideUp 0.8s ease-out;
  }

  .login-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 24px;
    padding: 3rem 2.5rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.2) inset;
    width: 100%;
    max-width: 440px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }

  .btn-loading {
    position: relative;
    color: transparent;
  }

  .btn-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid transparent;
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  /* Responsive Design - Consolidated */
  @media (max-width: 768px) {
    .user-info {
      display: none;
    }
    
    .user-profile {
      padding: 8px 12px;
    }
    
    .navbar-brand-modern {
      font-size: 1.1rem;
    }

    .navbar-logo {
      width: 45px;
      height: 45px;
    }
    
    .sidebar {
      transform: translateX(-100%);
      transition: transform 0.3s ease;
    }
    
    .sidebar.show {
      transform: translateX(0);
    }
    
    .content-wrapper {
      margin-left: 0;
      padding: 1rem;
    }

    .sidebar .nav-link {
      padding: 10px 16px;
      margin: 2px 8px;
    }
    
    .sidebar .nav-link:hover {
      transform: translateX(4px) translateZ(0);
    }
    
    .sidebar .nav-link.active {
      transform: translateX(3px) translateZ(0);
    }

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

    .btn {
      font-size: 0.85rem !important;
      padding: 7px 14px !important;
    }
    
    .form-control, .form-select {
      font-size: 0.9rem !important;
    }
  }

  @media (max-width: 992px) {
    .content-wrapper {
      padding: 1rem;
    }
  }

  /* Global Performance Transitions */
  .btn, .form-control, .form-select, .page-link {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
  }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg modern-navbar">
    <div class="container-fluid" style="padding-left: 2rem;">
      <!-- Brand dengan Logo -->
      <div class="d-flex align-items-center">
        <img src="public/assets/img/wandaicmprs.png" alt="LogoWandai" class="navbar-logo me-3">
        <span class="navbar-brand-modern">
          BPS Kabupaten Paniai
        </span>
      </div>

      <!-- Right side -->
      <div class="d-flex align-items-center">
        <!-- User Profile (klik untuk halaman Akun Saya, kecuali mitra) -->
        <?php if ($isMitra): ?>
          <div class="user-profile">
        <?php else: ?>
          <a href="index.php?controller=profile" class="user-profile text-decoration-none text-reset"
             title="Kelola akun saya">
        <?php endif; ?>
          <div class="user-avatar">
            <?= strtoupper(substr($name, 0, 2)) ?>
          </div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($name) ?></div>
            <div class="user-role">
              <?php if ($isMitra): ?>
                <span class="status-badge status-mitra">MITRA</span>
              <?php else: ?>
                <?= htmlspecialchars($role) ?>
                <?php if (in_array($role, ['Admin', 'admin'])): ?>
                  <span class="status-badge status-admin">ADMIN</span>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php if ($isMitra): ?>
          </div>
        <?php else: ?>
          </a>
        <?php endif; ?>

        <!-- Logout Button -->
        <a href="index.php?controller=auth&action=logout"
           class="btn logout-btn ms-3"
           onclick="return confirm('Yakin ingin logout?')">
          <i class="bi bi-box-arrow-right me-1"></i>
          Logout
        </a>
      </div>
    </div>
  </nav>

  <div class="main-content">

  <!-- Modern Sidebar Gesture Script - Optimized & More Responsive -->
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    // Enhanced responsive click handler
    navLinks.forEach(link => {
      let clickTimeout = null;
      
      const handleClick = function(e) {
        // Immediate visual feedback
        this.classList.add('clicking', 'ripple');
        
        // Remove clicking class quickly for better responsiveness
        clearTimeout(clickTimeout);
        clickTimeout = setTimeout(() => {
          this.classList.remove('clicking');
        }, 150);
        
        // Remove ripple effect faster
        setTimeout(() => this.classList.remove('ripple'), 300);
        
        // Remove loading state faster
        setTimeout(() => this.classList.remove('loading'), 400);
      };

      const handleMouseDown = function(e) {
        // Immediate feedback on mousedown
        this.classList.add('clicking');
      };

      const handleMouseUp = function(e) {
        // Remove clicking state on mouseup
        setTimeout(() => {
          this.classList.remove('clicking');
        }, 100);
      };

      const handleTouch = function(e) {
        // Immediate feedback for touch
        this.classList.add('clicking');
        this.style.transform = 'translateX(4px) scale(0.96) translateZ(0)';
        this.style.transition = 'all 0.05s cubic-bezier(0.4, 0, 0.2, 1)';
        
        const touchEnd = () => {
          setTimeout(() => {
            this.classList.remove('clicking');
            this.style.transform = '';
            this.style.transition = '';
          }, 100);
          this.removeEventListener('touchend', touchEnd);
        };
        
        this.addEventListener('touchend', touchEnd, { once: true });
      };

      const handleKeyboard = function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.classList.add('clicking');
          setTimeout(() => {
            this.classList.remove('clicking');
            this.click();
          }, 100);
        }
      };

      // Attach optimized event listeners with immediate feedback
      link.addEventListener('click', handleClick);
      link.addEventListener('mousedown', handleMouseDown);
      link.addEventListener('mouseup', handleMouseUp);
      link.addEventListener('mouseleave', handleMouseUp); // Remove clicking if mouse leaves
      link.addEventListener('touchstart', handleTouch, { passive: true });
      link.addEventListener('keydown', handleKeyboard);
      
      // Performance optimization
      link.addEventListener('mouseenter', () => {
        link.style.willChange = 'transform, background-color, color, box-shadow';
      });
      link.addEventListener('mouseleave', () => {
        setTimeout(() => link.style.willChange = 'auto', 200);
      });
    });

    // Utility functions for programmatic control
    window.navigateWithGesture = function(linkSelector) {
      const link = document.querySelector(linkSelector);
      if (link && link.classList.contains('nav-link')) {
        link.classList.add('clicking', 'ripple');
        link.click();
        setTimeout(() => {
          link.classList.remove('clicking', 'ripple');
        }, 300);
      }
    };

    window.toggleSidebarGestures = function(enabled = true) {
      navLinks.forEach(link => {
        link.style.pointerEvents = enabled ? 'auto' : 'none';
        link.style.opacity = enabled ? '1' : '0.6';
      });
    };

    // Mobile sidebar toggle
    window.toggleSidebar = function() {
      const sidebar = document.querySelector('.sidebar');
      if (sidebar) {
        sidebar.classList.toggle('show');
      }
    };

    // Optional: Add swipe gesture for mobile sidebar
    if ('ontouchstart' in window) {
      let touchStartX = 0;
      let touchStartY = 0;
      
      document.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
      }, { passive: true });
      
      document.addEventListener('touchmove', function(e) {
        if (!touchStartX || !touchStartY) return;
        
        const touchEndX = e.touches[0].clientX;
        const touchEndY = e.touches[0].clientY;
        
        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;
        
        // Horizontal swipe detection
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
          if (Math.abs(deltaX) > 50) { // Minimum swipe distance
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
              if (deltaX > 0 && touchStartX < 20) {
                // Swipe right from left edge - show sidebar
                sidebar.classList.add('show');
              } else if (deltaX < 0 && sidebar.classList.contains('show')) {
                // Swipe left - hide sidebar
                sidebar.classList.remove('show');
              }
            }
          }
        }
        
        // Reset touch positions
        touchStartX = 0;
        touchStartY = 0;
      }, { passive: true });
    }
  });
  </script>

</body>
</html>