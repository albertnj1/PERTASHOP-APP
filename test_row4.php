<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783172127_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('REKAP KLT 01-30 Juni 2026');

for ($c = 1; $c <= 50; $c++) {
    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
    $val = $sheet->getCell($letter . 4)->getCalculatedValue();
    echo "$letter: $val\n";
}
