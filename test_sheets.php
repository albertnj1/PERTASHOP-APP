<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$files = glob(storage_path('app/public/monthly_reports/*.xlsx'));
$file = storage_path('app/public/monthly_reports/1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx');
$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);

$rekapModalSheet = null;
foreach ($spreadsheet->getAllSheets() as $sh) {
    $title = strtolower($sh->getTitle());
    if (str_contains($title, 'rekap') && str_contains($title, 'modal')) {
        $rekapModalSheet = $sh;
        break;
    }
}

if ($rekapModalSheet) {
    echo "Found Rekap Modal Sheet: " . $rekapModalSheet->getTitle() . "\n";
    $highestRowRM = $rekapModalSheet->getHighestRow();
    echo "Highest Row: " . $highestRowRM . "\n";
    for ($row = 2; $row <= 5; $row++) {
        echo "Row $row:\n";
        foreach (range('A', 'L') as $col) {
            echo "  $col: " . $rekapModalSheet->getCell($col . $row)->getCalculatedValue() . "\n";
        }
    }
} else {
    echo "Rekap Modal sheet not found.\n";
}
