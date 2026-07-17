<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('storage/app/public/monthly_reports/1783185321_06. Sales Report Kalibenda 01 - 30 Juni 2026.xlsx');
$sheetName = $spreadsheet->getSheetNames()[0]; // Just get the first sheet which is usually the REKAP
$sheet = $spreadsheet->getSheetByName($sheetName);

if (!$sheet) {
    echo "Sheet not found!\n";
    exit;
}

echo "Sheet Name: $sheetName\n";
// Let's just find where 6471758 is.
foreach ($sheet->getRowIterator() as $row) {
    $rowIndex = $row->getRowIndex();
    foreach ($sheet->getColumnIterator() as $col) {
        $colIndex = $col->getColumnIndex();
        $cell = $sheet->getCell($colIndex . $rowIndex);
        $val = $cell->getCalculatedValue();
        if (is_numeric($val) && abs($val - 6471758) < 1000) {
            echo "Found near 6471758 at {$colIndex}{$rowIndex}: " . $val . "\n";
        }
    }
}
