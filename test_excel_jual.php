<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('storage/app/public/monthly_reports/1783185321_06. Sales Report Kalibenda 01 - 30 Juni 2026.xlsx');
$sheet = $spreadsheet->getSheetByName('KLT Stok-Penjualan-Laba Ktr');

if ($sheet) {
    // Usually Liter Terjual is around D19 or K19 depending on layout. Let's just find "B. JUMLAH PENJUALAN"
    foreach ($sheet->getRowIterator() as $row) {
        $rowIndex = $row->getRowIndex();
        $cellVal = $sheet->getCell('B' . $rowIndex)->getCalculatedValue();
        if (strpos(strval($cellVal), 'B. JUMLAH PENJUALAN') !== false) {
            echo "JUMLAH PENJUALAN found at row $rowIndex\n";
            echo "Value K: " . $sheet->getCell('K' . $rowIndex)->getCalculatedValue() . "\n";
            echo "Value L: " . $sheet->getCell('L' . $rowIndex)->getCalculatedValue() . "\n";
        }
    }
}
