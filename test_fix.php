<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = App\Models\MonthlyReport::latest()->first();
if ($report) {
    // Recalculate based on current form logic
    $totalPengeluaranExtra = 0;
    if (is_array($report->pengeluaran_extra)) {
        foreach ($report->pengeluaran_extra as $extra) {
            $totalPengeluaranExtra += $extra['nominal'] ?? 0;
        }
    }
    
    // Total Biaya is ONLY the extra
    $totalBiaya = $totalPengeluaranExtra;
    
    // We don't have totalLabaKotor easily accessible here, but we can reconstruct it or let the user just re-upload.
    // Actually, it's safer to just let the user re-upload to be 100% clean, or I can just tell them it's fixed and they can submit again.
}
