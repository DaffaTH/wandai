<?php
/*
 * WANDAI System - Profile Controller
 * BPS Kabupaten Paniai
 *
 * Halaman "Akun Saya": setiap user dapat mengubah username (email) dan
 * password sendiri. Tidak boleh mengubah role / tim — hanya kredensial
 * pribadi.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'models/User.php';

class ProfileController
{
    /** Halaman utama: tampilkan form profil. */
    public function index()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        // Mitra punya alur akun terpisah — sementara arahkan ke dashboard.
        if (($_SESSION['user']['user_type'] ?? 'user') !== 'user') {
            $_SESSION['error'] = 'Halaman Akun hanya untuk pengguna internal.';
            header('Location: index.php?controller=dashboard');
            exit;
        }

        $userModel = new User();
        $user = $userModel->getById($_SESSION['user']['id']);

        if (!$user) {
            $_SESSION['error'] = 'Data akun tidak ditemukan.';
            header('Location: index.php?controller=dashboard');
            exit;
        }

        // Daftar semua tim user (banyak-ke-banyak via user_teams).
        $userTeams = $userModel->getTeamsByUserId($_SESSION['user']['id']);

        include 'views/profile/index.php';
    }

    /** Update username (email) — POST. */
    public function updateUsername()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $userId   = (int)$_SESSION['user']['id'];
        $newEmail = trim((string)($_POST['email'] ?? ''));

        try {
            if ($newEmail === '') {
                throw new Exception('Username (email) tidak boleh kosong.');
            }
            // Validasi format email longgar (server tetap menerima username
            // non-email untuk backward compatibility).
            if (strpos($newEmail, ' ') !== false) {
                throw new Exception('Username tidak boleh mengandung spasi.');
            }

            $userModel = new User();
            if ($userModel->emailExists($newEmail, $userId)) {
                throw new Exception('Username sudah dipakai akun lain.');
            }

            // Update kolom email saja, jangan ubah role/tim/data lain.
            $userModel->updateEmail($userId, $newEmail);

            // Sinkron ke session supaya header & alur lain ikut update.
            $_SESSION['user']['email'] = $newEmail;
            $_SESSION['success'] = 'Username berhasil diperbarui.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal mengubah username: ' . $e->getMessage();
        }

        header('Location: index.php?controller=profile');
        exit;
    }

    /** Update password — POST. Wajib konfirmasi password lama. */
    public function updatePassword()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $userId      = (int)$_SESSION['user']['id'];
        $oldPassword = (string)($_POST['old_password']     ?? '');
        $newPassword = (string)($_POST['new_password']     ?? '');
        $confirm     = (string)($_POST['confirm_password'] ?? '');

        try {
            if ($oldPassword === '' || $newPassword === '' || $confirm === '') {
                throw new Exception('Semua field password wajib diisi.');
            }
            if ($newPassword !== $confirm) {
                throw new Exception('Konfirmasi password baru tidak cocok.');
            }
            if (strlen($newPassword) < 6) {
                throw new Exception('Password baru minimal 6 karakter.');
            }

            $userModel = new User();
            $row = $userModel->getById($userId);
            if (!$row) throw new Exception('Akun tidak ditemukan.');

            // Verifikasi password lama: dukung bcrypt + legacy plain.
            $stored = (string)$row['password'];
            $ok = preg_match('/^\$2[aby]\$/', $stored)
                ? password_verify($oldPassword, $stored)
                : hash_equals($stored, $oldPassword);

            if (!$ok) throw new Exception('Password lama salah.');

            $userModel->updatePassword($userId, $newPassword);
            $_SESSION['success'] = 'Password berhasil diperbarui.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal mengubah password: ' . $e->getMessage();
        }

        header('Location: index.php?controller=profile');
        exit;
    }
}
