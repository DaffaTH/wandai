<?php
/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * Provided as reference for internal learning purposes.
 */

class Role {
    private $db;

    public function __construct() {
        require 'config/database.php';
        $this->db = $pdo;
    }

    public function getAll() {
        // Urutan dropdown role: Admin → Kepala → Kasubbag → PPK → Bendahara → Operator → lainnya.
        $sql = "SELECT * FROM roles
                ORDER BY
                  CASE LOWER(name)
                    WHEN 'admin'     THEN 1
                    WHEN 'kepala'    THEN 2
                    WHEN 'kasubbag'  THEN 3
                    WHEN 'ppk'       THEN 4
                    WHEN 'bendahara' THEN 5
                    WHEN 'operator'  THEN 6
                    ELSE 7
                  END,
                  name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}