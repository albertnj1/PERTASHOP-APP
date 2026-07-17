<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$file = storage_path('app/public/monthly_reports/1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx');
$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$bkh = $spreadsheet->getSheet(0);
$hargaJual = $bkh->getCell('O1')->getCalculatedValue();
echo 'Harga Jual from O1: ' . $hargaJual . "\n";

$bkh = $spreadsheet->getSheet(0);

$bkh = $spreadsheet->getSheet(0);

for ($r = 4; $r <= 10; $r++) {
    $vol = $bkh->getCell('F' . $r)->getCalculatedValue();
    $rupiah = $bkh->getCell('G' . $r)->getCalculatedValue();
    echo "Row $r: Vol = $vol | Rupiah = $rupiah\n";
}


