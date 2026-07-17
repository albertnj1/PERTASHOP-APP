<?php
require 'vendor/autoload.php';
$s = \PhpOffice\PhpSpreadsheet\IOFactory::load('C:/Users/user/Downloads/06. Sales Report Sumingkir 01 - 30 Juni 2026 2026.xlsx');
$sh = $s->getSheet(0); // BKH
$rows = $sh->toArray();
echo "BKH Sheet: " . $sh->getTitle() . "\n";
for ($i=0; $i<4; $i++) {
    echo "Row " . ($i+1) . ": ";
    foreach($rows[$i] as $k => $v) {
        if ($v !== null && $v !== '') {
            echo "[$k]" . substr(str_replace("\n", " ", (string)$v), 0, 30) . " | ";
        }
    }
    echo "\n";
}
