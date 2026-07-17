<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783162469_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getActiveSheet();

$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();

echo "Scanning Excel file for 'Harga Beli'...\n";

for ($row = 1; $row <= $highestRow; $row++) {
    for ($col = 'A'; $col !== $highestColumn; $col++) {
        $cellValue = $sheet->getCell($col . $row)->getCalculatedValue();
        if (is_string($cellValue) && stripos($cellValue, 'Harga Beli') !== false) {
            echo "Row $row Col $col: $cellValue\n";
        }
    }
}

// Dump the first 15 rows to see headers
echo "\nFirst 15 rows:\n";
for ($row = 1; $row <= 15; $row++) {
    $rowData = [];
    for ($col = 'A'; $col <= 'G'; $col++) {
        $rowData[] = $sheet->getCell($col . $row)->getCalculatedValue();
    }
    echo implode(" | ", $rowData) . "\n";
}
