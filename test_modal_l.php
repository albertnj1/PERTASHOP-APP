<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783175069_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('MODAL');

for ($r = 8; $r <= 20; $r++) {
    for ($c = 11; $c <= 14; $c++) { // Columns K to N
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
        $val = $sheet->getCell($letter . $r)->getCalculatedValue();
        $formula = $sheet->getCell($letter . $r)->getValue();
        if (!empty($val)) {
            echo "$letter$r: Val=$val | Formula=$formula\n";
        }
    }
}
