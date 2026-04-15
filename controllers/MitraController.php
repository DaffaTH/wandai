<?php
/*
 * WANDAI System - Mitra Controller
 * BPS Kabupaten Paniai
 * 
 * UPDATED:
 * - Username diganti menjadi email
 * - Tambah wilayah_kerja (Paniai/Intan Jaya/Deiyai)
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'models/Mitra.php';

class MitraController
{
    public function index()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new Mitra();
        $mitraList = $model->listAll();
        $wilayahList = Mitra::getWilayahList();

        include 'views/mitra/index.php';
    }

    public function store()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new Mitra();

        try {
            // Validasi email unique
            $email = trim($_POST['email']);
            if ($model->emailExists($email)) {
                throw new Exception('Email sudah digunakan');
            }

            // Validasi format email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format email tidak valid');
            }

            $model->create([
                'nama' => trim($_POST['nama']),
                'email' => $email,
                'nik' => trim($_POST['nik']),
                'alamat' => trim($_POST['alamat']),
                'wilayah_kerja' => $_POST['wilayah_kerja'] ?? 'Paniai',
                'password' => $_POST['password']
            ]);

            $_SESSION['success'] = 'Mitra berhasil ditambahkan';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan mitra: ' . $e->getMessage();
        }

        header('Location: index.php?controller=mitra');
        exit;
    }

    public function update()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new Mitra();
        $id = (int)$_POST['id'];

        try {
            // Validasi email unique (exclude current id)
            $email = trim($_POST['email']);
            if ($model->emailExists($email, $id)) {
                throw new Exception('Email sudah digunakan oleh mitra lain');
            }

            // Validasi format email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format email tidak valid');
            }

            $model->update($id, [
                'nama' => trim($_POST['nama']),
                'email' => $email,
                'nik' => trim($_POST['nik']),
                'alamat' => trim($_POST['alamat']),
                'wilayah_kerja' => $_POST['wilayah_kerja'] ?? 'Paniai',
                'status' => $_POST['status'],
                'password' => $_POST['password'] ?? ''
            ]);

            $_SESSION['success'] = 'Data mitra berhasil diupdate';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal mengupdate mitra: ' . $e->getMessage();
        }

        header('Location: index.php?controller=mitra');
        exit;
    }

    public function delete()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        $model = new Mitra();
        $id = (int)$_POST['id'];

        try {
            $model->delete($id);
            $_SESSION['success'] = 'Mitra berhasil dihapus';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus mitra: ' . $e->getMessage();
        }

        header('Location: index.php?controller=mitra');
        exit;
    }

    /**
     * Import mitra dari Excel
     */
    public function import()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'File tidak valid';
            header('Location: index.php?controller=mitra');
            exit;
        }

        require_once 'vendor/autoload.php';

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Skip header
            array_shift($rows);

            $dataArray = [];
            foreach ($rows as $index => $row) {
                if (empty(trim($row[0] ?? ''))) continue;
                
                $dataArray[] = [
                    'row_number' => $index + 2,
                    'nama' => trim($row[0] ?? ''),
                    'email' => trim($row[1] ?? ''),
                    'nik' => trim($row[2] ?? ''),
                    'alamat' => trim($row[3] ?? ''),
                    'wilayah_kerja' => trim($row[4] ?? 'Paniai'),
                    'password' => trim($row[5] ?? '9502'),
                    'status' => trim($row[6] ?? 'aktif')
                ];
            }

            $model = new Mitra();
            $result = $model->validateImportData($dataArray);

            if (!empty($result['valid_data'])) {
                $inserted = $model->bulkInsert($result['valid_data']);
                $_SESSION['success'] = "$inserted mitra berhasil diimport";
            }

            if (!empty($result['errors'])) {
                $errorMsg = [];
                foreach (array_slice($result['errors'], 0, 5) as $row => $errs) {
                    $errorMsg[] = "Baris $row: " . implode(', ', $errs);
                }
                $_SESSION['warning'] = implode('<br>', $errorMsg);
            }

        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal import: ' . $e->getMessage();
        }

        header('Location: index.php?controller=mitra');
        exit;
    }

    /**
     * Download template import mitra
     */
    public function downloadTemplate()
    {
        require_once 'vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Mitra');

        // Header
        $headers = ['Nama', 'Email', 'NIK', 'Alamat', 'Wilayah Kerja', 'Password', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4472C4');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Contoh data
        $sheet->setCellValue('A2', 'Nama Mitra');
        $sheet->setCellValue('B2', 'mitra@example.com');
        $sheet->setCellValue('C2', '9401234567890123');
        $sheet->setCellValue('D2', 'Jl. Contoh No. 1');
        $sheet->setCellValue('E2', 'Paniai');
        $sheet->setCellValue('F2', '9502');
        $sheet->setCellValue('G2', 'aktif');

        // Sheet 2: Keterangan
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Keterangan');
        
        $sheet2->setCellValue('A1', 'Kolom');
        $sheet2->setCellValue('B1', 'Keterangan');
        $sheet2->setCellValue('C1', 'Contoh');
        $sheet2->getStyle('A1:C1')->getFont()->setBold(true);

        $info = [
            ['Nama', 'Nama lengkap mitra (wajib)', 'Ahmad Supardi'],
            ['Email', 'Email untuk login, harus unik (wajib)', 'ahmad@email.com'],
            ['NIK', '16 digit angka (wajib)', '9401234567890123'],
            ['Alamat', 'Alamat lengkap (wajib)', 'Jl. Merdeka No. 1'],
            ['Wilayah Kerja', 'Paniai / Intan Jaya / Deiyai', 'Paniai'],
            ['Password', 'Password login (default: 9502)', '9502'],
            ['Status', 'aktif / nonaktif', 'aktif']
        ];

        $row = 2;
        foreach ($info as $i) {
            $sheet2->setCellValue('A' . $row, $i[0]);
            $sheet2->setCellValue('B' . $row, $i[1]);
            $sheet2->setCellValue('C' . $row, $i[2]);
            $row++;
        }

        // Auto width
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Import_Mitra.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export semua mitra ke Excel
     */
    public function export()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        require_once 'vendor/autoload.php';

        $model = new Mitra();
        $data = $model->exportAll();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Mitra');

        // Header
        $headers = ['Nama', 'Email', 'NIK', 'Alamat', 'Wilayah Kerja', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // Data
        $row = 2;
        foreach ($data as $mitra) {
            $sheet->setCellValue('A' . $row, $mitra['nama']);
            $sheet->setCellValue('B' . $row, $mitra['email']);
            $sheet->setCellValue('C' . $row, $mitra['nik']);
            $sheet->setCellValue('D' . $row, $mitra['alamat']);
            $sheet->setCellValue('E' . $row, $mitra['wilayah_kerja']);
            $sheet->setCellValue('F' . $row, $mitra['status']);
            $row++;
        }

        // Auto width
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Data_Mitra_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
