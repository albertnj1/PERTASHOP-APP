<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = App\Models\MonthlyReport::all();
foreach ($reports as $report) {
    if ($report->uang_di_bank > 0 && $report->uang_di_bank < 10000) {
        $report->uang_di_bank = $report->uang_di_bank * 1000;
    }
    if ($report->uang_di_bank < 0 && $report->uang_di_bank > -10000) {
        $report->uang_di_bank = $report->uang_di_bank * 1000;
    }
    
    if ($report->piutang > 0 && $report->piutang < 10000) {
        $report->piutang = $report->piutang * 1000;
    }
    
    if ($report->bunga_bank > 0 && $report->bunga_bank < 10000) {
        $report->bunga_bank = $report->bunga_bank * 1000;
    }
    
    if ($report->pajak_bank > 0 && $report->pajak_bank < 10000) {
        $report->pajak_bank = $report->pajak_bank * 1000;
    }
    
    $report->save();
}
echo "Cleaned up all historical reports for bunga/pajak/piutang!\n";
