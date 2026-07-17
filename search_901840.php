<?php
require __DIR__.'/vendor/autoload.php';

$filePath = __DIR__.'/storage/app/public/monthly_reports/1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

$sheet3 = $spreadsheet->getSheet(3);
$rows3 = $sheet3->toArray();

foreach ($rows3 as $idx => $r3) {
    $col0 = trim(strtolower((string)($r3[0] ?? '')));
    $col1 = trim(strtolower((string)($r3[1] ?? '')));
    $col10 = trim(strtolower((string)($r3[10] ?? '')));
    
    foreach ($r3 as $cIdx => $cVal) {
        if (is_numeric($cVal) && abs($cVal - 901840) < 1) {
            echo "Found 901840 at row $idx, col $cIdx. Row content: " . json_encode($r3) . "\n";
        }
        if (is_numeric($cVal) && abs($cVal - 4654123) < 1) {
            echo "Found 4654123 at row $idx, col $cIdx. Row content: " . json_encode($r3) . "\n";
        }
    }
}
