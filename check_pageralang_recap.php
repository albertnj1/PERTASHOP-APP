<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Pageralang report ID 45
$report = \App\Models\MonthlyReport::find(45);
$shop = $report->shop;
$month = \Carbon\Carbon::parse($report->bulan_tahun)->month;
$year = \Carbon\Carbon::parse($report->bulan_tahun)->year;

echo "=== Pageralang Report 45 (2026-06) ===\n";
echo "saldo_akhir_modal: " . number_format($report->saldo_akhir_modal, 2) . "\n";

// Check capital_recaps
$allRecaps = \App\Models\CapitalRecap::where('shop_id', $report->shop_id)
    ->orderBy('tahun')
    ->orderBy('bulan')
    ->get();

echo "\nAll CapitalRecaps for shop_id=" . $report->shop_id . ":\n";
foreach ($allRecaps as $rc) {
    echo "  " . $rc->tahun . "/" . $rc->bulan . " | posisi_akhir_modal=" . number_format($rc->posisi_akhir_modal, 2) . " | nilai_modal_awal=" . number_format($rc->nilai_modal_awal, 2) . "\n";
}

// Check the recap for June 2026
$recap = \App\Models\CapitalRecap::where('shop_id', $report->shop_id)
    ->where('bulan', $month)
    ->where('tahun', $year)
    ->first();

if ($recap) {
    echo "\nRecap for 2026-06:\n";
    echo "  posisi_akhir_modal: " . number_format($recap->posisi_akhir_modal, 2) . "\n";
    echo "  nilai_modal_awal: " . number_format($recap->nilai_modal_awal, 2) . "\n";
    echo "  akumulasi_penambahan_penyusutan: " . number_format($recap->akumulasi_penambahan_penyusutan, 2) . "\n";
} else {
    echo "\nNo recap for 2026-06\n";
}
