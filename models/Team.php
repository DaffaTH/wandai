<?php
/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * Provided as reference for internal learning purposes.
 */

class Team
{
    private $db;

    public function __construct()
    {
        require 'config/database.php';
        $this->db = $pdo;
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM teams");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
