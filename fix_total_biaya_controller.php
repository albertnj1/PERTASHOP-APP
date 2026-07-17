<?php
$file = 'app/Http/Controllers/MonthlyReportController.php';
$content = file_get_contents($file);

$search2 = <<<'EOD'
        $totalBiaya = $grandTotals['total_pengeluaran_rutin'] + $totalPengeluaranExtra;
        $labaBersih = $totalLabaKotor - $totalBiaya;
EOD;

$replace2 = <<<'EOD'
        $totalBiaya = $totalPengeluaranExtra; // User requested to ONLY use form inputs, ignore Excel expenses
        $labaBersih = $totalLabaKotor - $totalBiaya;
EOD;

$content = str_replace($search2, $replace2, $content);
file_put_contents($file, $content);
echo "Fixed totalBiaya in MonthlyReportController\n";
