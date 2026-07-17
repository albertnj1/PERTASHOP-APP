<?php
require 'vendor/autoload.php';
$s = \PhpOffice\PhpSpreadsheet\IOFactory::load('C:/Users/user/Downloads/06. Sales Report Sumingkir 01 - 30 Juni 2026 2026.xlsx');
$sh = $s->getSheet(4); // SMK 31 MEI 2026
$rows = $sh->toArray(null, true, false, false);
echo "Sheet: " . $sh->getTitle() . "\n";
foreach (array_slice($rows, 0, 10) as $i => $row) {
    echo "Row $i: ";
    foreach ($row as $j => $col) {
        if ($col !== null && $col !== '') {
            echo "[$j] " . substr(str_replace("\n", " ", $col), 0, 50) . " | ";
        }
    }
    echo "\n";
}
