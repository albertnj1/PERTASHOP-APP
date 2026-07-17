<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search2 = <<<'EOD'
        // --- Calculate Page 1 Variables ---
        $stokAwalFisik = $report->stok_awal_fisik ?? 0;
EOD;

$replace2 = <<<'EOD'
        // --- Calculate Page 1 Variables ---
        $stokAwalFisik = $grandTotals['stok_awal_fisik'] ?? ($report->stok_awal_fisik ?? 0);
EOD;

$content = str_replace($search2, $replace2, $content);
file_put_contents($file, $content);
echo "Fixed stokAwalFisik in show.blade.php\n";
