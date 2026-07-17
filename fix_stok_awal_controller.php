<?php
$file = 'app/Http/Controllers/MonthlyReportController.php';
$content = file_get_contents($file);

$createMethodOld = "
        \$lastStokAktual = \$lastReport ? (\$lastReport->data_parsed['stok_aktual'] ?? 0) : 0;
        
        return view('monthly_reports.create', compact('shop', 'lastTotalisator', 'lastSaldoModal'));
";
$createMethodNew = "
        \$lastStokAktual = \$lastReport ? (\$lastReport->grand_totals['stok_aktual'] ?? 0) : 0;
        
        return view('monthly_reports.create', compact('shop', 'lastTotalisator', 'lastSaldoModal', 'lastStokAktual'));
";
$content = str_replace(trim($createMethodOld), trim($createMethodNew), $content);

$storeMethodOld = "
            'saldo_awal_modal' => \$this->parseFlexibleNumber(\$request->saldo_awal_modal),
            'total_bbm_period' => \$totalBbmPeriod,
";
$storeMethodNew = "
            'saldo_awal_modal' => \$this->parseFlexibleNumber(\$request->saldo_awal_modal),
            'stok_awal_fisik' => \$this->parseFlexibleNumber(\$request->stok_awal_fisik),
            'total_bbm_period' => \$totalBbmPeriod,
";
$content = str_replace(trim($storeMethodOld), trim($storeMethodNew), $content);

file_put_contents($file, $content);
echo "Done updating MonthlyReportController for stok_awal_fisik\n";
