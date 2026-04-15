<?php
/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * Provided as reference for internal learning purposes.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'models/User.php';
require_once 'models/Team.php';
require_once 'models/Role.php';

class TimController
{
    public function index()
    {
        if ($_SESSION['user']['role_id'] != 1) {
            echo "Anda tidak berhak mengakses halaman ini.";
            exit;
        }

        $userModel = new User();
        $teamModel = new Team();
        $roleModel = new Role();

        $users = $userModel->getAllWithTeamAndRole();
        $teams = $teamModel->getAll();
        $roles = $roleModel->getAll();

        include 'views/tim/index.php';
    }

    public function simpan()
    {
        if ($_SESSION['user']['role_id'] != 1) {
            echo "Akses ditolak.";
            exit;
        }

        $name     = $_POST['name']     ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role_id  = (int)($_POST['role_id'] ?? 0);
        // team_ids[] dari checkbox — kosong kalau role bukan operator.
        $team_ids = $_POST['team_ids'] ?? [];
        if (!is_array($team_ids)) $team_ids = [$team_ids];

        $userModel = new User();
        $userModel->insert($name, $username, $password, $role_id, $team_ids);

        header('Location: index.php?controller=tim');
        exit;
    }

    public function update()
    {
        if ($_SESSION['user']['role_id'] != 1) {
            echo "Akses ditolak.";
            exit;
        }

        $id       = $_POST['id']       ?? 0;
        $name     = $_POST['name']     ?? '';
        $username = $_POST['username'] ?? '';
        $role_id  = (int)($_POST['role_id'] ?? 0);
        $team_ids = $_POST['team_ids'] ?? [];
        if (!is_array($team_ids)) $team_ids = [$team_ids];

        $userModel = new User();
        $userModel->update($id, $name, $username, $role_id, $team_ids);

        header('Location: index.php?controller=tim');
        exit;
    }

    public function hapus()
    {
        if ($_SESSION['user']['role_id'] != 1) {
            echo "Akses ditolak.";
            exit;
        }

        $id = $_GET['id'];
        $userModel = new User();
        $userModel->delete($id);

        header('Location: index.php?controller=tim');
        exit;
    }

    /**
     * Halaman atur warna tim (khusus admin).
     * Warna digunakan untuk blok di Dashboard Matriks.
     */
    public function warna()
    {
        if ($_SESSION['user']['role_id'] != 1) {
            echo "Akses ditolak.";
            exit;
        }

        require 'config/database.php';

        // Pastikan kolom warna ada; kalau belum, buat on-the-fly
        try {
            $chk = $pdo->query("SHOW COLUMNS FROM teams LIKE 'warna'")->fetch();
            if (!$chk) {
                $pdo->exec("ALTER TABLE teams ADD COLUMN warna VARCHAR(7) DEFAULT '#6c757d' AFTER description");
            }
        } catch (Exception $e) {
            // biarkan, lanjut
        }

        $teams = $pdo->query("SELECT id, name, COALESCE(warna,'#6c757d') AS warna FROM teams ORDER BY name")
                     ->fetchAll(PDO::FETCH_ASSOC);

        include 'views/tim/warna.php';
    }

    /**
     * Simpan warna tim (POST).
     */
    public function simpanWarna()
    {
        if ($_SESSION['user']['role_id'] != 1) {
            echo "Akses ditolak.";
            exit;
        }

        require 'config/database.php';
        $warnaPerTim = $_POST['warna'] ?? []; // [team_id => '#rrggbb']

        try {
            $stmt = $pdo->prepare("UPDATE teams SET warna = ? WHERE id = ?");
            foreach ($warnaPerTim as $tid => $hex) {
                $hex = trim($hex);
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) continue;
                $stmt->execute([$hex, (int)$tid]);
            }
            $_SESSION['success'] = 'Warna tim berhasil disimpan.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menyimpan warna: ' . $e->getMessage();
        }

        header('Location: index.php?controller=tim&action=warna');
        exit;
    }
}
