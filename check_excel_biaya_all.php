<?php
require __DIR__.'/vendor/autoload.php';

$filePath = __DIR__.'/storage/app/public/monthly_reports/1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

foreach ($spreadsheet->getAllSheets() as $sheet) {
    echo "\n=== Sheet Name: " . $sheet->getTitle() . " ===\n";
    for ($row = 1; $row <= 50; $row++) {
        for ($col = 'A'; $col <= 'H'; $col++) {
            $val = $sheet->getCell($col . $row)->getCalculatedValue();
            if (is_numeric($val) && abs($val - 4654123) < 1) {
                echo "Found 4654123 at $col$row\n";
            }
            if (is_numeric($val) && abs($val - 901840) < 1) {
                echo "Found diff 901840 at $col$row\n";
            }
            if (is_string($val) && stripos($val, 'Biaya') !== false) {
                echo "Label '$val' at $col$row (Value in next cols): " . $sheet->getCell(++$col . $row)->getCalculatedValue() . " | " . $sheet->getCell(++$col . $row)->getCalculatedValue() . "\n";
                // decrement col back
                $col = chr(ord($col) - 2);
            }
        }
    }
}
