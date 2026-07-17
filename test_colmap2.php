<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/public/monthly_reports/1783172470_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('REKAP KLT 01-30 Juni 2026');

$colMap = [
    'pengeluaran' => null,
];
$highestCol = $sheet->getHighestColumn();
$highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
for ($r = 1; $r <= 10; $r++) {
    for ($c = 1; $c <= $highestColIndex; $c++) {
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
        $val = strtolower(trim($sheet->getCell($letter . $r)->getValue()));
        if (str_contains($val, 'pengeluaran') && !str_contains($val, 'ket') && empty($colMap['pengeluaran'])) {
            echo "Found 'pengeluaran' at $letter$r: $val\n";
            $colMap['pengeluaran'] = $letter;
        }
    }
}
echo "Final pengeluaran column: " . $colMap['pengeluaran'] . "\n";
