<?php
require __DIR__.'/vendor/autoload.php';

$file = 'c:\\xampp\\htdocs\\Pertashop App_Laravel\\sal-pertashop\\storage\\app\\public\\monthly_reports\\1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0);

echo "Harga Jual (O1): " . $sheet->getCell('O1')->getCalculatedValue() . "\n";
