<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783175069_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);

$sheet = $spreadsheet->getSheetByName('MODAL');
if ($sheet) {
    echo "\n=== SHEET MODAL ===\n";
    for ($r = 1; $r <= 25; $r++) {
        for ($c = 1; $c <= 10; $c++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $val = $sheet->getCell($letter . $r)->getCalculatedValue();
            if (!empty($val)) {
                echo "$letter$r: $val\n";
            }
        }
    }
}
