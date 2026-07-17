<?php
require __DIR__.'/vendor/autoload.php';

$filePath = 'C:/Users/user/Downloads/06. Sales Report Kemutug 01 - 30 Juni 2026.xlsx';
echo "Inspecting: " . basename($filePath) . "\n\n";

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

// List all sheets
echo "=== ALL SHEETS ===\n";
foreach ($spreadsheet->getAllSheets() as $idx => $sh) {
    echo "Sheet $idx: " . $sh->getTitle() . "\n";
}

// Find the BKH/daily sheet (usually the last one)
$sheetCount = $spreadsheet->getSheetCount();
$bkhSheet = $spreadsheet->getSheet($sheetCount - 1);
echo "\n=== BKH Sheet: " . $bkhSheet->getTitle() . " ===\n";

// Read first 15 rows to understand structure
$rows = $bkhSheet->toArray(null, true, true, true);
echo "Header/structure rows:\n";
$dataRowSample = null;
foreach ($rows as $rowIdx => $r) {
    $nonEmpty = array_filter($r, fn($v) => $v !== null && $v !== '');
    if (count($nonEmpty) < 3) continue;
    
    echo "Row $rowIdx: ";
    foreach ($r as $col => $val) {
        if ($val !== null && $val !== '') {
            $display = is_numeric($val) ? number_format((float)$val, 2) : $val;
            echo "$col=" . substr((string)$display, 0, 20) . " | ";
        }
    }
    echo "\n";
    
    // Check if this is a data row (has D and E as large numbers)
    if (is_numeric($r['D'] ?? '') && is_numeric($r['E'] ?? '') && floatval($r['D']) > 100000) {
        if (!$dataRowSample) {
            $dataRowSample = ['rowIdx' => $rowIdx, 'row' => $r];
            echo "  ^^^ FIRST DATA ROW (D and E are large numbers)\n";
        }
    }
}

// Find all data rows (rows with totalisator data)
echo "\n=== SAMPLE DATA ROWS (looking for totalisator columns) ===\n";
$count = 0;
foreach ($rows as $rowIdx => $r) {
    // A data row typically has: date, operator name, tot_awal, tot_akhir, etc.
    $colC = $r['C'] ?? '';
    $colD = $r['D'] ?? '';
    $colE = $r['E'] ?? '';
    if (is_numeric($colD) && is_numeric($colE) && floatval($colD) > 100000 && floatval($colE) > 100000) {
        echo "Row $rowIdx: C=$colC | D=" . number_format($colD) . " | E=" . number_format($colE) . " | F=" . ($r['F'] ?? '') . " | G=" . ($r['G'] ?? '') . "\n";
        if (++$count >= 5) break;
    }
}

// Look for rows where volume difference makes sense (100-3000 liter/day)
echo "\n=== LOOKING FOR PROPER VOLUME COLUMNS ===\n";
$count = 0;
foreach ($rows as $rowIdx => $r) {
    foreach ($r as $colLetter => $val) {
        if (is_numeric($val) && floatval($val) >= 100 && floatval($val) <= 5000) {
            // Check adjacent column
            $nextCol = chr(ord($colLetter) + 1);
            $nextVal = $r[$nextCol] ?? '';
            if (is_numeric($nextVal) && floatval($nextVal) >= 100 && floatval($nextVal) <= 5000) {
                $diff = abs(floatval($nextVal) - floatval($val));
                if ($diff > 50 && $diff < 3000) {
                    echo "Row $rowIdx: $colLetter=" . number_format($val, 2) . " | $nextCol=" . number_format($nextVal, 2) . " | diff=" . number_format($diff, 2) . "\n";
                    if (++$count >= 10) break 2;
                }
            }
        }
    }
}
