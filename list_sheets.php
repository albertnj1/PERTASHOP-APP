<?php
require 'vendor/autoload.php';
$s = \PhpOffice\PhpSpreadsheet\IOFactory::load('C:/Users/user/Downloads/06. Sales Report Sumingkir 01 - 30 Juni 2026 2026.xlsx');
foreach ($s->getAllSheets() as $i => $sh) {
    echo $i . ': ' . $sh->getTitle() . PHP_EOL;
}
