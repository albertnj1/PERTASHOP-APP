<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = App\Models\MonthlyReport::all();
foreach ($reports as $report) {
    if (is_array($report->pengeluaran_extra)) {
        $extras = $report->pengeluaran_extra;
        $modified = false;
        foreach ($extras as &$ex) {
            if (isset($ex['nominal']) && $ex['nominal'] > 0 && $ex['nominal'] < 1000) {
                $ex['nominal'] = $ex['nominal'] * 1000;
                $modified = true;
            }
        }
        if ($modified) {
            $report->pengeluaran_extra = $extras;
            $report->save();
        }
    }
}
echo "Cleaned up pengeluaran_extra in DB!\n";
