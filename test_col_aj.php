<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783172470_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('REKAP KLT 01-30 Juni 2026');

for ($r = 1; $r <= 5; $r++) {
    echo "Row $r: " . $sheet->getCell('AJ' . $r)->getCalculatedValue() . "\n";
}
