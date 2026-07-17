<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search1 = <<<'EOD'
        $rincianAll = [];
        $totalBiaya = 0;
        foreach($periods as $p) {
            if(isset($p['rincian_pengeluaran']) && is_array($p['rincian_pengeluaran'])) {
                foreach($p['rincian_pengeluaran'] as $rinc) {
                    $rincianAll[] = $rinc;
                    $totalBiaya += $rinc['nominal'] ?? $rinc['nom'] ?? 0;
                }
            }
        }
EOD;

$replace1 = <<<'EOD'
        $rincianAll = [];
        $totalBiaya = 0;
        // User requested to ONLY use form inputs for Pengeluaran, NOT the Excel daily expenses
        $pengeluaranExtra = $report->pengeluaran_extra ?? [];
        foreach($pengeluaranExtra as $extra) {
            $rincianAll[] = $extra;
            $totalBiaya += floatval($extra['nominal'] ?? $extra['nom'] ?? 0);
        }
EOD;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Fixed rincianAll correctly in show.blade.php\n";
