<?php
require __DIR__.'/vendor/autoload.php';

use App\Models\MonthlyReport;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$filePath = $argv[1] ?? 'C:/Users/user/Downloads/06. Sales Report Kemutug 01 - 30 Juni 2026.xlsx';
echo "Testing dynamic Excel parsing on: " . basename($filePath) . "\n\n";

$spreadsheet = IOFactory::load($filePath);

// 1. KLB Parsing Test
$klbSheet = null;
foreach ($spreadsheet->getAllSheets() as $sh) {
    $shTitle = strtolower($sh->getTitle());
    if (str_contains($shTitle, 'stok-penjualan') || str_contains($shTitle, 'stok penjualan') ||
        str_contains($shTitle, 'laba ktr') || str_contains($shTitle, 'laba kotor') ||
        (str_contains($shTitle, 'klb') && !str_contains($shTitle, 'rekap'))) {
        $klbSheet = $sh;
        break;
    }
}
if (!$klbSheet) {
    foreach ($spreadsheet->getAllSheets() as $sh) {
        $firstRows = $sh->toArray(null, true, false, false);
        foreach (array_slice($firstRows, 0, 20) as $r) {
            foreach ($r as $cv) {
                if (is_string($cv) && preg_match('/Harga Beli/i', $cv)) {
                    $klbSheet = $sh;
                    break 3;
                }
            }
        }
    }
}
echo "KLB Sheet found: " . ($klbSheet ? $klbSheet->getTitle() : 'NOT FOUND') . "\n";

// 2. KLT Parsing Test
$kltSheet = null;
foreach ($spreadsheet->getAllSheets() as $sh) {
    $shTitle = strtolower($sh->getTitle());
    if (str_contains($shTitle, 'laba bersih') || str_contains($shTitle, 'klt') ||
        (str_contains($shTitle, 'profit') && !str_contains($shTitle, 'sharing'))) {
        $kltSheet = $sh;
        break;
    }
}
echo "KLT Sheet found: " . ($kltSheet ? $kltSheet->getTitle() : 'NOT FOUND') . "\n";

// 3. Rekap Modal Parsing Test
$rekapModalSheet = null;
foreach ($spreadsheet->getAllSheets() as $sh) {
    $title = strtolower($sh->getTitle());
    if (str_contains($title, 'rekap') && str_contains($title, 'modal')) {
        $rekapModalSheet = $sh;
        break;
    }
}
echo "Rekap Modal Sheet found: " . ($rekapModalSheet ? $rekapModalSheet->getTitle() : 'NOT FOUND') . "\n";

// 4. BKH Parsing Test
$bkhSheet = null;
$bkhSheetKeywords = ['rekap', 'bkh', 'harian', 'penjualan', 'stok-penjualan'];
foreach ($spreadsheet->getAllSheets() as $shIdx => $sh) {
    $shTitle = strtolower($sh->getTitle());
    $skipKeywords = ['laba bersih', 'modal', 'gaji', 'profit sharing', 'pembelian do', 'hutang', 'setoran'];
    $shouldSkip = false;
    foreach ($skipKeywords as $sk) {
        if (str_contains($shTitle, $sk)) { $shouldSkip = true; break; }
    }
    if ($shouldSkip) continue;
    
    for ($r = 1; $r <= 3; $r++) {
        for ($c = 3; $c <= 7; $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $cellVal = strtolower(trim((string)($sh->getCell($colLetter . $r)->getValue() ?? '')));
            if (str_contains($cellVal, 'totalisator')) {
                $bkhSheet = $sh;
                break 3;
            }
        }
    }
}
if (!$bkhSheet) {
    foreach ($spreadsheet->getAllSheets() as $shIdx => $sh) {
        $shTitle = strtolower($sh->getTitle());
        foreach ($bkhSheetKeywords as $keyword) {
            if (str_contains($shTitle, $keyword)) {
                $bkhSheet = $sh;
                break 2;
            }
        }
    }
}
echo "BKH Sheet found: " . ($bkhSheet ? $bkhSheet->getTitle() : 'NOT FOUND') . "\n";

