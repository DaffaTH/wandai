<?php
/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * 
 * UPDATED: Login menggunakan EMAIL (bukan username)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'models/User.php';
require_once 'models/Mitra.php';

class AuthController
{

    // Default method saat akses controller=auth tanpa action
    public function index()
    {
        $this->login(); // arahkan langsung ke login
    }

    public function login()
    {
        include 'views/auth/login.php';
    }

    public function prosesLogin()
    {
        // UPDATED: Bisa login pakai email ATAU username lama (backward compatible)
        $loginInput = trim($_POST['username'] ?? $_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($loginInput) || empty($password)) {
            $error = "Email dan password harus diisi!";
            include 'views/auth/login.php';
            return;
        }

        // 1. Coba login sebagai user biasa (admin, kepala, ppk, dll)
        $userModel = new User();
        $user = $userModel->getByEmail($loginInput);

        // Helper: verifikasi password dengan bcrypt; fallback ke plain
        // (untuk data legacy yang masih plain-text), lalu auto-rehash agar
        // entry plain-text di-upgrade jadi hash bcrypt yang aman.
        $verifyPassword = function (array $row, string $plain) use ($userModel): bool {
            $stored = (string)($row['password'] ?? '');
            // Hash bcrypt selalu diawali $2y$ / $2a$ / $2b$ (60 char)
            if (preg_match('/^\$2[aby]\$/', $stored)) {
                return password_verify($plain, $stored);
            }
            // Legacy plain-text: cocokkan langsung, lalu upgrade
            if (hash_equals($stored, $plain)) {
                $userModel->updatePassword((int)$row['user_id'], $plain);
                return true;
            }
            return false;
        };

        if ($user && $verifyPassword($user, $password)) {
            // Format nama lengkap dengan gelar
            $namaLengkap = $this->formatNamaLengkap(
                $user['name'],
                $user['gelar_depan'] ?? null,
                $user['gelar_belakang'] ?? null
            );
            
            // Login berhasil sebagai user biasa
            $_SESSION['user'] = [
                'id' => $user['user_id'],
                'name' => $namaLengkap,
                'email' => $user['email'],
                'nip' => $user['nip'] ?? null,
                'role_id' => $user['role_id'],
                'team_id' => $user['team_id'],
                'role_name' => $user['role_name'],
                'user_type' => 'user',
                // Data tambahan untuk dokumen
                'gelar_depan' => $user['gelar_depan'] ?? null,
                'gelar_belakang' => $user['gelar_belakang'] ?? null,
                'pangkat' => $user['pangkat'] ?? null,
                'golongan' => $user['golongan'] ?? null,
                'jabatan' => $user['jabatan'] ?? null
            ];
            header('Location: index.php?controller=dashboard');
            exit;
        }

        // 2. Jika gagal, coba login sebagai mitra
        $mitraModel = new Mitra();
        $mitra = $mitraModel->getByEmail($loginInput);

        // Mitra juga memakai plain-text saat seed lama; kalau sudah hash bcrypt, verify.
        $mitraOk = false;
        if ($mitra) {
            $stored = (string)($mitra['password'] ?? '');
            if (preg_match('/^\$2[aby]\$/', $stored)) {
                $mitraOk = password_verify($password, $stored);
            } else {
                $mitraOk = hash_equals($stored, $password);
            }
        }
        if ($mitra && $mitraOk) {
            // Cek apakah mitra aktif
            if ($mitra['status'] !== 'aktif') {
                $error = "Akun mitra Anda sedang nonaktif. Hubungi admin!";
                include 'views/auth/login.php';
                return;
            }

            // Login berhasil sebagai mitra
            $_SESSION['user'] = [
                'id' => $mitra['id'],
                'name' => $mitra['nama'],
                'email' => $mitra['email'],
                'nik' => $mitra['nik'] ?? null,
                'role_id' => 99, // ID khusus untuk mitra
                'team_id' => null,
                'role_name' => 'Mitra',
                'user_type' => 'mitra',
                'wilayah_kerja' => $mitra['wilayah_kerja'] ?? 'Paniai'
            ];
            header('Location: index.php?controller=dashboard');
            exit;
        }

        // 3. Jika kedua-duanya gagal
        $error = "Email atau password salah!";
        include 'views/auth/login.php';
    }

    /**
     * Format nama lengkap dengan gelar
     */
    private function formatNamaLengkap($nama, $gelarDepan = null, $gelarBelakang = null)
    {
        $result = '';
        
        if (!empty($gelarDepan)) {
            $result .= $gelarDepan . ' ';
        }
        
        $result .= $nama;
        
        if (!empty($gelarBelakang)) {
            $result .= ', ' . $gelarBelakang;
        }
        
        return $result;
    }

    public function logout()
    {
        session_destroy();
        header('Location: index.php?controller=auth&action=login');
        exit;
    }
}