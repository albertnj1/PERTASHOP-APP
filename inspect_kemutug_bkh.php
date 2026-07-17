<?php
require 'vendor/autoload.php';
$s = \PhpOffice\PhpSpreadsheet\IOFactory::load('C:/Users/user/Downloads/06. Sales Report Kemutug 01 - 30 Juni 2026.xlsx');
$sh = $s->getSheet(1); // REKAP KMT 01-30 Juni 2026
$rows = $sh->toArray();
echo "Row 3: ";
foreach($rows[2] as $k => $v) {
    if ($v) echo "[$k]" . substr(str_replace("\n", " ", (string)$v), 0, 30) . " | ";
}
echo "\nRow 4: ";
foreach($rows[3] as $k => $v) {
    if ($v) echo "[$k]" . substr(str_replace("\n", " ", (string)$v), 0, 30) . " | ";
}
echo "\n";
