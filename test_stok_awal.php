<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783172127_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);

foreach ($spreadsheet->getAllSheets() as $sheet) {
    $highestRow = min(200, $sheet->getHighestRow());
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
    
    for ($r = 1; $r <= $highestRow; $r++) {
        for ($c = 1; $c <= $highestColIndex; $c++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $val = $sheet->getCell($letter . $r)->getCalculatedValue();
            if (is_array($val)) continue;
            $val = trim($val);
            if (strpos($val, '1731') !== false) {
                echo "Found in " . $sheet->getTitle() . " at $letter$r: $val\n";
            }
        }
    }
}
