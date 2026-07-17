<?php
$file = 'app/Http/Controllers/MonthlyReportController.php';
$content = file_get_contents($file);

$search1 = <<<'EOD'
        // Modal & Financial Variables
        $saldo_awal_modal = floatval($request->saldo_awal_modal ?? 0);
        $do_di_pertamina = floatval($request->do_di_pertamina ?? 0);
        $uang_di_bank = floatval($request->uang_di_bank ?? 0);
        $kas_kecil = floatval($request->kas_kecil ?? 0);
        $piutang = floatval($request->piutang ?? 0);
        $bunga_bank = floatval($request->bunga_bank ?? 0);
        $pajak_bank = floatval($request->pajak_bank ?? 0);
EOD;

$replace1 = <<<'EOD'
        // Modal & Financial Variables
        $saldo_awal_modal = $this->parseFlexibleNumber($request->saldo_awal_modal ?? 0);
        // If DO is inputted < 100, assume it's in KL, multiply by 1000
        $rawDo = $this->parseFlexibleNumber($request->do_di_pertamina ?? 0);
        $do_di_pertamina = $rawDo > 0 && $rawDo < 100 ? $rawDo * 1000 : $rawDo;
        $uang_di_bank = $this->parseFlexibleNumber($request->uang_di_bank ?? 0);
        $kas_kecil = $this->parseFlexibleNumber($request->kas_kecil ?? 0);
        $piutang = $this->parseFlexibleNumber($request->piutang ?? 0);
        $bunga_bank = $this->parseFlexibleNumber($request->bunga_bank ?? 0);
        $pajak_bank = $this->parseFlexibleNumber($request->pajak_bank ?? 0);
        $stok_awal_fisik = $this->parseFlexibleNumber($request->stok_awal_fisik ?? 0);
        $grandTotals['stok_awal_fisik'] = $stok_awal_fisik;
EOD;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Fixed financial variables in controller\n";
