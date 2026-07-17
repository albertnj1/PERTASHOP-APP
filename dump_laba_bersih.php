<?php
require __DIR__.'/vendor/autoload.php';

$filePath = __DIR__.'/storage/app/public/monthly_reports/1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

$sheet3 = $spreadsheet->getSheet(3);
$rows3 = $sheet3->toArray(null, true, true, true);

foreach ($rows3 as $idx => $r3) {
    echo "Row $idx: " . json_encode($r3) . "\n";
}
