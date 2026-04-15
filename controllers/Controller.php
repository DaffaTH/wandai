<?php
/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * Provided as reference for internal learning purposes.
 */

class Controller
{
    public function model($model)
    {
        require_once __DIR__ . '/../models/' . $model . '.php';
        return new $model;
    }

    public function view($view, $data = [])
    {
        extract($data);
        require_once __DIR__ . '/../views/' . $view . '.php';
    }
}