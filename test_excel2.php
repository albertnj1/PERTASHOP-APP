<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783162469_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);

foreach ($spreadsheet->getSheetNames() as $sheetName) {
    echo "=== SHEET: $sheetName ===\n";
    $sheet = $spreadsheet->getSheetByName($sheetName);
    for ($row = 1; $row <= 15; $row++) {
        $rowData = [];
        for ($col = 'A'; $col <= 'G'; $col++) {
            $rowData[] = $sheet->getCell($col . $row)->getCalculatedValue();
        }
        echo implode(" | ", $rowData) . "\n";
    }
    echo "\n";
}
