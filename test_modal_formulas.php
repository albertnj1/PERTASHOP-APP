<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783175069_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);

$sheet = $spreadsheet->getSheetByName('MODAL');
if ($sheet) {
    echo "=== SHEET MODAL FORMULAS ===\n";
    for ($r = 7; $r <= 21; $r++) {
        $cell = $sheet->getCell('I' . $r);
        $val = $cell->getCalculatedValue();
        $formula = $cell->getValue();
        echo "I$r: Val=$val | Formula=$formula\n";
    }
    echo "Other cells on right side:\n";
    echo "L10: " . $sheet->getCell('L10')->getCalculatedValue() . "\n";
    echo "L11: " . $sheet->getCell('L11')->getCalculatedValue() . "\n";
    echo "L12: " . $sheet->getCell('L12')->getCalculatedValue() . "\n";
    echo "L13: " . $sheet->getCell('L13')->getCalculatedValue() . "\n";
}
