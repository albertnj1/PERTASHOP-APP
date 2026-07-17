<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search1 = <<<'EOD'
        foreach($periods as $idx => $p) {
            if(isset($p['rincian_pengeluaran']) && is_array($p['rincian_pengeluaran'])) {
                foreach($p['rincian_pengeluaran'] as $rinc) {
                    $rincianAll[] = $rinc;
                    $totalBiaya += $rinc['nominal'] ?? $rinc['nom'] ?? 0;
                }
            }
        }
        $pengeluaranExtra = $report->pengeluaran_extra ?? [];
        foreach($pengeluaranExtra as $extra) {
            $rincianAll[] = $extra;
            $totalBiaya += $extra['nominal'] ?? $extra['nom'] ?? 0;
        }
EOD;

$replace1 = <<<'EOD'
        // User requested to ONLY use form inputs for Pengeluaran, NOT the Excel daily expenses
        $pengeluaranExtra = $report->pengeluaran_extra ?? [];
        foreach($pengeluaranExtra as $extra) {
            $rincianAll[] = $extra;
            $totalBiaya += $extra['nominal'] ?? $extra['nom'] ?? 0;
        }
EOD;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Removed Excel pengeluaran from show.blade.php\n";
