<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783172470_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);

foreach ($spreadsheet->getAllSheets() as $sheet) {
    echo "=== SHEET: " . $sheet->getTitle() . " ===\n";
    $highestRow = min(200, $sheet->getHighestRow());
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
    
    for ($r = 1; $r <= $highestRow; $r++) {
        for ($c = 1; $c <= $highestColIndex; $c++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $val = trim($sheet->getCell($letter . $r)->getCalculatedValue());
            if (stripos($val, 'pengeluaran') !== false || stripos($val, 'biaya') !== false) {
                echo "Row $r, Col $letter: $val\n";
            }
        }
    }
}
