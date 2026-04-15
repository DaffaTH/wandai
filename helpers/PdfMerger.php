<?php
/**
 * PDF Merger menggunakan mPDF
 * Tidak perlu install library tambahan!
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

class PdfMerger
{
    private $files = [];
    
    /**
     * Tambahkan file PDF ke merger
     */
    public function addFile($filePath)
    {
        if (!file_exists($filePath)) {
            error_log("File not found: " . $filePath);
            return false;
        }
        
        // Validate PDF file
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            error_log("Not a PDF file: " . $filePath);
            return false;
        }
        
        $this->files[] = $filePath;
        return true;
    }
    
    /**
     * Tambahkan multiple files
     */
    public function addFiles($files)
    {
        $count = 0;
        foreach ($files as $file) {
            if ($this->addFile($file)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Merge dan save ke file
     */
    public function save($outputPath)
    {
        if (empty($this->files)) {
            error_log("No files to merge");
            return false;
        }
        
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P'
            ]);
            
            $firstFile = true;
            
            foreach ($this->files as $file) {
                try {
                    // Set source file
                    $pageCount = $mpdf->SetSourceFile($file);
                    
                    // Import all pages
                    for ($i = 1; $i <= $pageCount; $i++) {
                        // Add new page (except for first page of first file)
                        if (!$firstFile || $i > 1) {
                            $mpdf->AddPage();
                        }
                        
                        // Import page
                        $tplId = $mpdf->ImportPage($i);
                        
                        // Get template size
                        $size = $mpdf->getTemplateSize($tplId);
                        
                        // Use template
                        $mpdf->UseTemplate($tplId, 0, 0, $size['width'], $size['height']);
                        
                        $firstFile = false;
                    }
                    
                } catch (Exception $e) {
                    error_log("Error importing file {$file}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Save output
            $mpdf->Output($outputPath, 'F');
            
            return file_exists($outputPath);
            
        } catch (Exception $e) {
            error_log("Error merging PDFs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get file count
     */
    public function getFileCount()
    {
        return count($this->files);
    }
    
    /**
     * Reset merger
     */
    public function reset()
    {
        $this->files = [];
    }
}
