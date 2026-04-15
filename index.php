<?php
/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * Provided as reference for internal learning purposes.
 * 
 * UPDATED: Support untuk routing verifikasi operator tim
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi DB
require_once 'config/database.php';

// Routing
$controller = $_GET['controller'] ?? 'auth';
$action     = $_GET['action'] ?? 'index';

// Handle special controller names dengan underscore
$controllerMappings = [
    'petugas_kegiatan' => 'Petugas_kegiatanController',
    'beban_kerja' => 'BebanKerjaController',
    'data_administrasi' => 'Data_administrasiController',
    // Tambahkan mapping lain jika diperlukan
];

// Tentukan nama file dan class
if (isset($controllerMappings[$controller])) {
    $className = $controllerMappings[$controller];
    $controllerFile = 'controllers/' . $className . '.php';
} else {
    // Default behavior untuk controller lain
    $className = ucfirst($controller) . 'Controller';
    $controllerFile = 'controllers/' . $className . '.php';
}

// DEBUG
if (!file_exists($controllerFile)) {
    die("❌ Controller file not found: <b>$controllerFile</b><br>
         Looking for controller: <b>$controller</b><br>
         Expected class: <b>$className</b><br>
         Current directory: <b>" . getcwd() . "</b>");
}

require_once $controllerFile;

if (!class_exists($className)) {
    die("❌ Class <b>$className</b> not found in file <b>$controllerFile</b>");
}

$obj = new $className();

if (!method_exists($obj, $action)) {
    die("❌ Method <b>$action</b> not found in controller <b>$className</b><br>
         Available methods: <b>" . implode(', ', get_class_methods($obj)) . "</b>");
}

// Execute the action
$obj->$action();