<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = App\Models\MonthlyReport::all();
foreach ($reports as $report) {
    if ($report->saldo_awal_modal > 0 && $report->saldo_awal_modal < 1000) {
        $report->saldo_awal_modal = $report->saldo_awal_modal * 1000000;
    }
    // Also fix do_di_pertamina for old reports
    if ($report->do_di_pertamina > 0 && $report->do_di_pertamina < 100) {
        $report->do_di_pertamina = $report->do_di_pertamina * 1000;
    }
    // Fix kas kecil
    if ($report->kas_kecil > 0 && $report->kas_kecil < 10000) {
        $report->kas_kecil = $report->kas_kecil * 1000;
    }
    
    // Add stok_awal_fisik if missing
    $grandTotals = $report->grand_totals;
    if (!isset($grandTotals['stok_awal_fisik'])) {
        $grandTotals['stok_awal_fisik'] = 1731; // fallback
        $report->grand_totals = $grandTotals;
    }
    
    $report->save();
}
echo "Cleaned up all historical reports in DB!\n";
