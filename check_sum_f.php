<?php
require __DIR__.'/vendor/autoload.php';

$file = 'c:\\xampp\\htdocs\\Pertashop App_Laravel\\sal-pertashop\\storage\\app\\public\\monthly_reports\\1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0);

$sumF = 0;
for ($row = 4; $row <= 33; $row++) {
    $sumF += floatval($sheet->getCell('F' . $row)->getCalculatedValue());
}
echo "Sum F calculated directly from Excel: $sumF\n";
