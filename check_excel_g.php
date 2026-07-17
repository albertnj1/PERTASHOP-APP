<?php
require __DIR__.'/vendor/autoload.php';

$file = 'c:\\xampp\\htdocs\\Pertashop App_Laravel\\sal-pertashop\\storage\\app\\public\\monthly_reports\\1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
if (!file_exists($file)) {
    // try to find the latest file
    $files = glob('c:\\xampp\\htdocs\\Pertashop App_Laravel\\sal-pertashop\\storage\\app\\public\\monthly_reports\\*.xlsx');
    rsort($files);
    $file = $files[0];
}

echo "Testing file: " . basename($file) . "\n";
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$bkhSheet = $spreadsheet->getSheet(0);

$highestRow = $bkhSheet->getHighestRow();
$sumRp = 0;
for ($row = 4; $row <= $highestRow; $row++) {
    $rp = floatval($bkhSheet->getCell('G' . $row)->getCalculatedValue());
    if ($rp > 0) {
        echo "Row $row: Rp $rp\n";
        $sumRp += $rp;
    }
}
echo "Total Column G: $sumRp\n";
