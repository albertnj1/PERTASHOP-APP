<?php
require __DIR__.'/vendor/autoload.php';

$file = 'c:\\xampp\\htdocs\\Pertashop App_Laravel\\sal-pertashop\\storage\\app\\public\\monthly_reports\\1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0);

for ($row = 4; $row <= 33; $row++) {
    $f = floatval($sheet->getCell('F' . $row)->getCalculatedValue());
    $g_form = $sheet->getCell('G' . $row)->getValue();
    $g_calc = floatval($sheet->getCell('G' . $row)->getCalculatedValue());
    echo "Row $row - F: $f, G Formula: $g_form, G Calc: $g_calc\n";
}
