<?php
require __DIR__.'/vendor/autoload.php';

$filePath = 'C:/Users/user/Downloads/06. Sales Report Kemutug 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

// Get the detected BKH sheet
$bkhSheet = $spreadsheet->getSheet(1); // "REKAP KMT 01-30 Juni 2026"
$rows = $bkhSheet->toArray(null, true, true, false); // With formatting, no calculated values

// Show header rows
echo "=== HEADER ROWS ===\n";
for ($ri = 1; $ri <= 3; $ri++) {
    $r = $rows[$ri] ?? [];
    echo "Row $ri: ";
    foreach ($r as $col => $val) {
        if ($val !== null && $val !== '') {
            echo $col . "=" . substr((string)$val, 0, 20) . " | ";
        }
    }
    echo "\n";
}

// Show first few data rows with index mapping (0-indexed for toArray())
$rowsArr = $bkhSheet->toArray(); // 0-indexed
echo "\n=== FIRST DATA ROW (index mapping r=3 = row 4) ===\n";
$dataRow = $rowsArr[3] ?? []; // Row 4 (first data row)
foreach ($dataRow as $idx => $val) {
    if ($val !== null && $val !== '' && $val !== 0) {
        echo "  Index $idx: " . (is_numeric($val) ? number_format($val, 3) : substr((string)$val, 0, 30)) . "\n";
    }
}

// Map expected columns
echo "\n=== COLUMN MAPPING (controller expects) ===\n";
echo "r[1] (index 1 = col B): No/Tgl = " . ($dataRow[1] ?? 'N/A') . "\n";
echo "r[3] (index 3 = col D): totalisator_awal = " . number_format($dataRow[3] ?? 0, 3) . "\n";
echo "r[4] (index 4 = col E): totalisator_akhir = " . number_format($dataRow[4] ?? 0, 3) . "\n";
echo "r[5] (index 5 = col F): vol_teoritis = " . number_format($dataRow[5] ?? 0, 3) . "\n";
echo "r[6] (index 6 = col G): rp_teoritis = " . number_format($dataRow[6] ?? 0, 0) . "\n";
echo "r[7] (index 7 = col H): tp_vol = " . ($dataRow[7] ?? 0) . "\n";

// Look for setor/setoran columns
echo "\nLooking for setoran/transfer columns...\n";
for ($idx = 30; $idx < min(50, count($dataRow)); $idx++) {
    $val = $dataRow[$idx] ?? '';
    if ($val !== '' && $val !== null && $val !== 0) {
        echo "  Index $idx: " . (is_numeric($val) ? number_format($val, 0) : substr((string)$val, 0, 30)) . "\n";
    }
}
