<?php
require __DIR__.'/vendor/autoload.php';

// Find Kemutug Lor Excel file
$files = glob(__DIR__.'/storage/app/public/monthly_reports/*Kemutug*');
$files2 = glob(__DIR__.'/storage/app/public/monthly_reports/*kemutug*');
$files3 = glob(__DIR__.'/storage/app/public/monthly_reports/*KML*');
$files4 = glob(__DIR__.'/storage/app/public/monthly_reports/*KMT*');
$all = array_merge($files, $files2, $files3, $files4);

if (empty($all)) {
    // List all recent files
    $allFiles = glob(__DIR__.'/storage/app/public/monthly_reports/*');
    usort($allFiles, fn($a, $b) => filemtime($b) - filemtime($a));
    echo "Recent files:\n";
    foreach(array_slice($allFiles, 0, 5) as $f) {
        echo "  " . basename($f) . " (" . date('Y-m-d H:i', filemtime($f)) . ")\n";
    }
} else {
    foreach ($all as $f) {
        echo "Found: " . basename($f) . "\n";
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($f);
        // Check first sheet (BKH/daily)
        $sheetNames = [];
        foreach ($spreadsheet->getAllSheets() as $sh) {
            $sheetNames[] = $sh->getTitle();
        }
        echo "  Sheets: " . implode(', ', $sheetNames) . "\n";
        
        // Try to read BKH sheet first rows
        $sheet0 = $spreadsheet->getSheet(0);
        $rows = $sheet0->toArray(null, true, true, true);
        echo "  First 5 rows of sheet 0:\n";
        foreach(array_slice($rows, 1, 5) as $rowIdx => $r) {
            echo "    Row $rowIdx: " . json_encode(array_slice($r, 0, 8, true)) . "\n";
        }
    }
}
