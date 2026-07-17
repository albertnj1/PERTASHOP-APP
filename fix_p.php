<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search = <<<'EOD'
        $saldoAwal = $report->saldo_awal_modal ?? 0;
        $doPertamina = ($report->do_di_pertamina ?? 0) * ($p['harga_beli'] ?? 0);
        $kasKecil = $report->kas_kecil ?? 0;
        $piutang = $report->piutang ?? 0;
        $sisaStokKerja = $currentStokAwal * ($p['harga_beli'] ?? 0);
EOD;

$replace = <<<'EOD'
        $lastHargaBeli = !empty($periods) ? end($periods)['harga_beli'] : 0;
        $saldoAwal = $report->saldo_awal_modal ?? 0;
        $doPertamina = ($report->do_di_pertamina ?? 0) * $lastHargaBeli;
        $kasKecil = $report->kas_kecil ?? 0;
        $piutang = $report->piutang ?? 0;
        $sisaStokKerja = $currentStokAwal * $lastHargaBeli;
EOD;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Fixed possible undefined \$p\n";
