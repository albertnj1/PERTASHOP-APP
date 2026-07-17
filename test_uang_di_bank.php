<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783180262_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);

$sheet = $spreadsheet->getSheetByName('REKAP KLT');
if ($sheet) {
    echo "Uang di Bank: " . $sheet->getCell('K16')->getCalculatedValue() . "\n";
    echo "DO: " . $sheet->getCell('K15')->getCalculatedValue() . "\n";
    echo "Kas Kecil: " . $sheet->getCell('K17')->getCalculatedValue() . "\n";
    echo "Sisa Stok: " . $sheet->getCell('K18')->getCalculatedValue() . "\n";
    
    // Let's also check column L where the values are
    echo "L15 (DO): " . $sheet->getCell('L15')->getCalculatedValue() . "\n";
    echo "L16 (Uang di Bank): " . $sheet->getCell('L16')->getCalculatedValue() . "\n";
    echo "L17 (Kas Kecil): " . $sheet->getCell('L17')->getCalculatedValue() . "\n";
} else {
    echo "REKAP KLT sheet not found\n";
}
