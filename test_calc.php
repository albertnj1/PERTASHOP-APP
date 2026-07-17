<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = App\Models\MonthlyReport::latest()->first();
$p = $report->data_parsed['periods'][1] ?? null;

echo "Tot Akhir: " . ($p['tot_akhir'] ?? 0) . "\n";
echo "Tot Awal: " . ($p['tot_awal'] ?? 0) . "\n";
echo "Test Pump: " . ($p['test_pump'] ?? 0) . "\n";

$totJualL = ($p['tot_akhir'] ?? 0) - ($p['tot_awal'] ?? 0);
$jmlJualL = $totJualL - ($p['test_pump'] ?? 0);
echo "Jml Jual L: $jmlJualL\n";

$currentStokAwal = $report->stok_awal_fisik ?? 0;
$totBbmDatang = 5000;
$jmlBeliL = $currentStokAwal + $totBbmDatang;
$sisaStokL = $jmlBeliL - $jmlJualL;
$lossesL = ($p['stok_aktual'] ?? 0) - $sisaStokL;

$hargaBeli = floatval($p['harga_beli'] ?? 0);
$hargaJual = floatval($p['harga_jual'] ?? 0);

$jmlBeliRp = ($currentStokAwal * $hargaBeli) + ($totBbmDatang * $hargaBeli);
$sisaStokRp = $sisaStokL * $hargaBeli;
$lossesRp = $lossesL * $hargaBeli;
$penjualanBersihRp = $jmlBeliRp - $sisaStokRp - $lossesRp;
$labaKotorRp = ($jmlJualL * $hargaJual) - $penjualanBersihRp;

echo "Laba Kotor: " . $labaKotorRp . "\n";
