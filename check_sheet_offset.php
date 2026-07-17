<?php
require __DIR__.'/vendor/autoload.php';

$filePath = 'C:/Users/user/Downloads/06. Sales Report Kemutug 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

echo "Sheet structure:\n";
foreach ($spreadsheet->getAllSheets() as $idx => $sh) {
    echo "  Sheet $idx: '" . $sh->getTitle() . "'\n";
}

// Controller reads:
// Sheet 2 (index 2) for KLB (Harga Beli/Jual)
// Sheet 3 (index 3) for KLT (Laba Bersih data)
// Rekap Modal by name

$sheets = $spreadsheet->getAllSheets();
echo "\nChecking KLB (sheet index 2 / 'KMT Stok-Penjualan-Laba Ktr' equivalent):\n";
if (isset($sheets[2])) {
    $sh2 = $sheets[2];
    echo "  Found: '" . $sh2->getTitle() . "'\n";
    $rows2 = $sh2->toArray(null, true, false, false);
    foreach ($rows2 as $i => $r) {
        foreach ($r as $colIdx => $colVal) {
            if (is_string($colVal) && (str_contains(strtolower($colVal), 'harga beli') || str_contains(strtolower($colVal), 'harga jual'))) {
                echo "  Found harga di row $i, col $colIdx: $colVal\n";
            }
        }
        if ($i > 30) break;
    }
}

// Check what's at index 3 for KLT
echo "\nChecking KLT (sheet index 3 / 'KMT Laba Bersih' equivalent):\n";
if (isset($sheets[3])) {
    $sh3 = $sheets[3];
    echo "  Found: '" . $sh3->getTitle() . "'\n";
    $rows3 = $sh3->toArray();
    foreach ($rows3 as $r3) {
        $col0 = strtolower(trim($r3[0] ?? ''));
        $col10 = strtolower(trim($r3[10] ?? ''));
        if (str_contains($col0, 'pembagian laba') || str_contains($col10, 'laba kotor') || str_contains($col10, 'laba bersih')) {
            echo "  KLT key found: col0='$col0' | col10='$col10'\n";
        }
    }
} else {
    echo "  Sheet 3 NOT FOUND! Total sheets: " . count($sheets) . "\n";
}

// The Kemutug Lor has extra Sheet 0 (Hutang BBPTU), so all indices are shifted by 1
// Sheet 1 = BKH (REKAP KMT)
// Sheet 2 = Pembelian DO (NOT KLB Stok-Penjualan!)
// Sheet 3 = KMT Stok-Penjualan-Laba Ktr (This IS the KLB sheet, at index 3 not 2!)
// Sheet 4 = KMT Laba Bersih (This IS the KLT sheet, at index 4 not 3!)
echo "\n=== IMPORTANT: Sheets are shifted by 1 due to extra 'Hutang BBPTU' sheet! ===\n";
echo "Looking for harga beli/jual in sheet 3 (KMT Stok-Penjualan-Laba Ktr):\n";
if (isset($sheets[3])) {
    $rows3 = $sheets[3]->toArray(null, true, false, false);
    foreach ($rows3 as $i => $r) {
        foreach ($r as $colIdx => $colVal) {
            if (is_string($colVal) && (str_contains(strtolower($colVal), 'harga beli') || str_contains(strtolower($colVal), 'harga jual'))) {
                echo "  Found at sheet3, row $i, col $colIdx: $colVal\n";
            }
        }
        if ($i > 30) break;
    }
}

echo "\nLooking for laba kotor in sheet 4 (KMT Laba Bersih):\n";
if (isset($sheets[4])) {
    $rows4 = $sheets[4]->toArray();
    foreach ($rows4 as $r4) {
        $col10 = strtolower(trim($r4[10] ?? ''));
        $col0 = strtolower(trim($r4[0] ?? ''));
        if (str_contains($col10, 'laba') || str_contains($col0, 'pembagian')) {
            echo "  KLT data: col0='$col0' | col10='$col10' | col14='" . ($r4[14] ?? '') . "'\n";
        }
    }
}
