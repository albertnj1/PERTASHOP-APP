<?php
require __DIR__.'/vendor/autoload.php';

$filePath = 'C:/Users/user/Downloads/06. Sales Report Kemutug 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

echo "=== ALL SHEETS ===\n";
foreach ($spreadsheet->getAllSheets() as $idx => $sh) {
    echo "Sheet $idx: " . $sh->getTitle() . " (rows: " . $sh->getHighestRow() . ")\n";
}

// Look at each sheet for BKH-style data (date, operator, totalisator values)
foreach ($spreadsheet->getAllSheets() as $idx => $sh) {
    $rows = $sh->toArray(null, true, true, true);
    $title = $sh->getTitle();
    
    // Look for rows with dates (01-Jun-26 or 1/6/2026 etc.) AND numeric values in the 100k-20M range
    $hasBigNums = false;
    $hasSmallNums = false;
    foreach ($rows as $r) {
        foreach ($r as $val) {
            if (is_numeric($val)) {
                $fVal = floatval($val);
                if ($fVal > 100000 && $fVal < 20000000) $hasBigNums = true;
                if ($fVal > 100 && $fVal < 10000) $hasSmallNums = true;
            }
        }
    }
    
    echo "\nSheet $idx '$title': hasBigNums=" . ($hasBigNums ? 'YES' : 'no') . " | hasSmallNums=" . ($hasSmallNums ? 'YES' : 'no') . "\n";
    
    // If has small numbers, this might be the BKH sheet (volume in liters)
    if ($hasSmallNums && !$hasBigNums) {
        echo "  *** POSSIBLE BKH SHEET ***\n";
        // Show first few data rows
        $count = 0;
        foreach ($rows as $rowIdx => $r) {
            $nonEmpty = array_filter($r, fn($v) => $v !== null && $v !== '');
            if (count($nonEmpty) < 3) continue;
            echo "  Row $rowIdx: ";
            foreach (array_slice($r, 0, 10, true) as $col => $val) {
                if ($val !== null) echo "$col=" . (is_numeric($val) ? number_format($val, 2) : substr($val, 0, 15)) . " | ";
            }
            echo "\n";
            if (++$count >= 10) break;
        }
    }
    
    // Also show first few rows of sheets with big numbers
    if ($hasBigNums) {
        echo "  First data row with big numbers:\n";
        foreach ($rows as $rowIdx => $r) {
            foreach ($r as $val) {
                if (is_numeric($val) && floatval($val) > 100000 && floatval($val) < 20000000) {
                    echo "  Row $rowIdx: ";
                    foreach ($r as $col => $v) {
                        if ($v !== null && $v !== '') echo "$col=" . (is_numeric($v) ? number_format($v, 0) : substr((string)$v, 0, 15)) . " | ";
                    }
                    echo "\n";
                    break 2;
                }
            }
        }
    }
}
