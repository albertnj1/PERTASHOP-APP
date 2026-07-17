<?php
require __DIR__.'/vendor/autoload.php';

// Find latest Kemutug Lor Excel file
$allFiles = glob(__DIR__.'/storage/app/public/monthly_reports/*.xlsx');
usort($allFiles, fn($a, $b) => filemtime($b) - filemtime($a));

echo "Latest 3 files:\n";
foreach(array_slice($allFiles, 0, 3) as $f) {
    echo "  " . basename($f) . " (" . date('Y-m-d H:i:s', filemtime($f)) . ")\n";
}

// Check the latest file
$latestFile = $allFiles[0] ?? null;
if (!$latestFile) {
    echo "No Excel files found!\n";
    exit;
}

echo "\nInspecting: " . basename($latestFile) . "\n";
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($latestFile);

foreach ($spreadsheet->getAllSheets() as $idx => $sheet) {
    echo "\nSheet $idx: " . $sheet->getTitle() . "\n";
}

// Read BKH sheet (the last sheet, usually index count-1 or first)
// Find the BKH sheet
$bkhSheet = null;
foreach ($spreadsheet->getAllSheets() as $idx => $sheet) {
    $title = strtolower($sheet->getTitle());
    if (str_contains($title, 'bkh') || str_contains($title, 'buku') || str_contains($title, 'harian')) {
        $bkhSheet = $sheet;
        echo "\nFound BKH sheet: " . $sheet->getTitle() . " at index $idx\n";
        break;
    }
}

// Try last sheet as BKH
if (!$bkhSheet) {
    $count = $spreadsheet->getSheetCount();
    $bkhSheet = $spreadsheet->getSheet($count - 1);
    echo "\nUsing last sheet as BKH: " . $bkhSheet->getTitle() . "\n";
}

$rows = $bkhSheet->toArray(null, true, true, true);

echo "\nFirst 10 rows of BKH sheet:\n";
$count = 0;
foreach ($rows as $rowIdx => $r) {
    echo "Row $rowIdx: ";
    // Show columns A through J
    for ($c = 'A'; $c <= 'J'; $c++) {
        $val = $r[$c] ?? '';
        if ($val !== null && $val !== '') {
            echo "$c=" . (is_numeric($val) ? number_format($val, 2) : $val) . " | ";
        }
    }
    echo "\n";
    if (++$count >= 15) break;
}

// Find rows with numeric data (data rows)
echo "\nSearching for data rows (rows with large numeric values in D or E columns)...\n";
$count = 0;
foreach ($rows as $rowIdx => $r) {
    $colD = $r['D'] ?? '';
    $colE = $r['E'] ?? '';
    if (is_numeric($colD) && $colD > 100000) {
        echo "Row $rowIdx: D=$colD | E=$colE | F=" . ($r['F'] ?? '') . " | G=" . ($r['G'] ?? '') . "\n";
        if (++$count >= 5) break;
    }
}
