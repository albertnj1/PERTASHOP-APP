<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MonthlyReport;

$latest = MonthlyReport::latest()->first();
if ($latest) {
    echo "ID: " . $latest->id . "\n";
    $data = $latest->data_parsed;
    echo "excel_laba_kotor: " . ($data['excel_laba_kotor'] ?? 'null') . "\n";
    
    $dailyData = $data['daily_data'] ?? [];
    $totalOmset = collect($dailyData)->sum('rupiah_jual');
    $jualAktual = collect($dailyData)->sum('penjualan_aktual');
    $totalBiaya = collect($dailyData)->sum('biaya.total');
    echo "total_omset: " . $totalOmset . "\n";
    echo "total_volume: " . $jualAktual . "\n";
    echo "total_biaya: " . $totalBiaya . "\n";
    
    $opSalary = $data['operator_salary'] ?? [];
    print_r($opSalary);
} else {
    echo "No reports found.\n";
}
