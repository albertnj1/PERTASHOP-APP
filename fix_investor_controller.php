<?php
$file = 'app/Http/Controllers/MonthlyReportController.php';
$content = file_get_contents($file);

$investorLogic = "
        // Parse Investors
        \$profitSharing = [];
        if (\$request->has('investor_nama') && is_array(\$request->investor_nama)) {
            foreach (\$request->investor_nama as \$index => \$nama) {
                if (!empty(trim(\$nama))) {
                    \$persen = floatval(\$request->investor_persen[\$index] ?? 0);
                    \$profitSharing[] = [
                        'nama' => trim(\$nama),
                        'persentase' => \$persen
                    ];
                }
            }
        }

        \$grandTotals = [
            'pendapatan_operator' => 0,
            'jumlah_pendapatan_bersih' => 0,
            'disetorkan' => 0,
            'loses' => 0,
            'qris' => 0,
            'test_pump' => 0,
            'pengeluaran_rutin' => 0,
            'do_di_pertamina' => \$this->parseFlexibleNumber(\$request->do_di_pertamina),
            'uang_di_bank' => \$this->parseFlexibleNumber(\$request->uang_di_bank),
            'kas_kecil' => \$this->parseFlexibleNumber(\$request->kas_kecil),
            'piutang' => \$this->parseFlexibleNumber(\$request->piutang),
            'bunga_bank' => \$this->parseFlexibleNumber(\$request->bunga_bank),
            'pajak_bank' => \$this->parseFlexibleNumber(\$request->pajak_bank),
            'saldo_awal_modal' => \$this->parseFlexibleNumber(\$request->saldo_awal_modal),
            'total_bbm_period' => \$totalBbmPeriod,
            'profit_sharing' => \$profitSharing
        ];
";

$oldGrandTotals = "
        \$grandTotals = [
            'pendapatan_operator' => 0,
            'jumlah_pendapatan_bersih' => 0,
            'disetorkan' => 0,
            'loses' => 0,
            'qris' => 0,
            'test_pump' => 0,
            'pengeluaran_rutin' => 0,
            'do_di_pertamina' => \$this->parseFlexibleNumber(\$request->do_di_pertamina),
            'uang_di_bank' => \$this->parseFlexibleNumber(\$request->uang_di_bank),
            'kas_kecil' => \$this->parseFlexibleNumber(\$request->kas_kecil),
            'piutang' => \$this->parseFlexibleNumber(\$request->piutang),
            'bunga_bank' => \$this->parseFlexibleNumber(\$request->bunga_bank),
            'pajak_bank' => \$this->parseFlexibleNumber(\$request->pajak_bank),
            'saldo_awal_modal' => \$this->parseFlexibleNumber(\$request->saldo_awal_modal),
            'total_bbm_period' => \$totalBbmPeriod
        ];
";

$content = str_replace(trim($oldGrandTotals), trim($investorLogic), $content);

file_put_contents($file, $content);
echo "Done modifying MonthlyReportController.php\n";
