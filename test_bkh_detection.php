<?php
require __DIR__.'/vendor/autoload.php';

$filePath = 'C:/Users/user/Downloads/06. Sales Report Kemutug 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

echo "Testing BKH sheet detection for: " . basename($filePath) . "\n\n";

$sheet = null;
$bkhSheetKeywords = ['rekap', 'bkh', 'harian', 'penjualan', 'stok-penjualan'];

// Priority 1: Find sheet with "Totalisator" header in first 3 rows
foreach ($spreadsheet->getAllSheets() as $shIdx => $sh) {
    $shTitle = strtolower($sh->getTitle());
    $skipKeywords = ['laba bersih', 'modal', 'gaji', 'profit sharing', 'pembelian do', 'hutang', 'setoran'];
    $shouldSkip = false;
    foreach ($skipKeywords as $sk) {
        if (str_contains($shTitle, $sk)) { $shouldSkip = true; break; }
    }
    if ($shouldSkip) {
        echo "  Skip sheet $shIdx: '{$sh->getTitle()}' (matches skip keyword)\n";
        continue;
    }
    
    echo "  Checking sheet $shIdx: '{$sh->getTitle()}'...\n";
    for ($r = 1; $r <= 3; $r++) {
        for ($c = 3; $c <= 7; $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $cellVal = strtolower(trim((string)($sh->getCell($colLetter . $r)->getValue() ?? '')));
            if (str_contains($cellVal, 'totalisator')) {
                echo "    FOUND 'totalisator' at {$colLetter}{$r}: '$cellVal'\n";
                $sheet = $sh;
                break 3;
            }
        }
    }
}

if ($sheet) {
    echo "\n==> SELECTED SHEET (Priority 1): '" . $sheet->getTitle() . "'\n";
} else {
    echo "\n  Not found by header, trying by keyword...\n";
    foreach ($spreadsheet->getAllSheets() as $shIdx => $sh) {
        $shTitle = strtolower($sh->getTitle());
        foreach ($bkhSheetKeywords as $keyword) {
            if (str_contains($shTitle, $keyword)) {
                $sheet = $sh;
                echo "==> SELECTED SHEET (Priority 2 - keyword '$keyword'): '" . $sh->getTitle() . "'\n";
                break 2;
            }
        }
    }
}

if ($sheet) {
    // Read first data row
    $rows = $sheet->toArray(null, true, true, true);
    echo "\nFirst few data rows:\n";
    $count = 0;
    foreach ($rows as $rowIdx => $r) {
        $nonEmpty = array_filter($r, fn($v) => $v !== null && $v !== '');
        if (count($nonEmpty) < 3) continue;
        echo "Row $rowIdx: ";
        foreach (array_slice($r, 0, 8, true) as $col => $val) {
            if ($val !== null) echo "$col=" . (is_numeric($val) ? number_format((float)$val, 3) : substr((string)$val, 0, 15)) . " | ";
        }
        echo "\n";
        if (++$count >= 8) break;
    }
}
