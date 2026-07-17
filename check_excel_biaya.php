<?php
require __DIR__.'/vendor/autoload.php';

$filePath = __DIR__.'/storage/app/public/monthly_reports/1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

// Check KLB sheet (usually index 1)
$sheet = $spreadsheet->getSheet(1);
echo "Sheet Name: " . $sheet->getTitle() . "\n";

// Find Biaya Operasional Total
for ($row = 1; $row <= 40; $row++) {
    for ($col = 'A'; $col <= 'G'; $col++) {
        $val = $sheet->getCell($col . $row)->getCalculatedValue();
        if (is_numeric($val) && abs($val - 4654123) < 1) {
            echo "Found 4654123 at $col$row\n";
        }
        if (is_string($val) && stripos($val, 'Biaya') !== false) {
            echo "Label '$val' at $col$row: " . $sheet->getCell('F' . $row)->getCalculatedValue() . " | " . $sheet->getCell('G' . $row)->getCalculatedValue() . "\n";
        }
    }
}
