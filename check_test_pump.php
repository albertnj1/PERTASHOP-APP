<?php
require __DIR__.'/vendor/autoload.php';

$file = 'c:\\xampp\\htdocs\\Pertashop App_Laravel\\sal-pertashop\\storage\\app\\public\\monthly_reports\\1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$bkhSheet = $spreadsheet->getSheet(0);

$sumTestPumpRp = 0;
for ($row = 4; $row <= 33; $row++) {
    $rp = floatval($bkhSheet->getCell('I' . $row)->getCalculatedValue());
    $sumTestPumpRp += $rp;
}
echo "Total Column I (Test Pump Rp): $sumTestPumpRp\n";

$sumAktualVol = 0;
$sumAktualRp = 0;
for ($row = 4; $row <= 33; $row++) {
    $vol = floatval($bkhSheet->getCell('L' . $row)->getCalculatedValue());
    $rp = floatval($bkhSheet->getCell('M' . $row)->getCalculatedValue());
    $sumAktualVol += $vol;
    $sumAktualRp += $rp;
}
echo "Total Column L (Aktual Vol): $sumAktualVol\n";
echo "Total Column M (Aktual Rp): $sumAktualRp\n";
