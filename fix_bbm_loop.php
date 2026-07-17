<?php
$file = 'app/Http/Controllers/MonthlyReportController.php';
$content = file_get_contents($file);

$search1 = <<<'EOD'
        // BBM Datang
        $bbm_datang = [];
        if ($request->has('bbm_tanggal') && is_array($request->bbm_tanggal)) {
            foreach ($request->bbm_tanggal as $idx => $tgl) {
                if (!empty($tgl)) {
                    $bbm_kl = $this->parseFlexibleNumber($request->bbm_kl[$idx] ?? 0);
                    $liter = $bbm_kl * 1000;
                    $bbm_datang[] = [
                        'tanggal' => $tgl,
                        'liter' => $liter,
                        'harga_beli' => $this->parseFlexibleNumber($request->bbm_harga_beli[$idx] ?? 0),
                        'harga_jual' => $this->parseFlexibleNumber($request->bbm_harga_jual[$idx] ?? 0),
                        'periode' => intval($request->bbm_periode[$idx] ?? 1)
                    ];
                }
            }
        }
EOD;

$replace1 = <<<'EOD'
        // BBM Datang
        $bbm_datang = [];
        if ($request->has('bbm_kl') && is_array($request->bbm_kl)) {
            foreach ($request->bbm_kl as $idx => $kl_val) {
                $bbm_kl = $this->parseFlexibleNumber($kl_val);
                if ($bbm_kl > 0) {
                    $liter = $bbm_kl * 1000;
                    $tgl = $request->bbm_tanggal[$idx] ?? '';
                    $bbm_datang[] = [
                        'tanggal' => empty($tgl) ? date('Y-m-d') : $tgl,
                        'liter' => $liter,
                        'harga_beli' => $this->parseFlexibleNumber($request->bbm_harga_beli[$idx] ?? 0),
                        'harga_jual' => $this->parseFlexibleNumber($request->bbm_harga_jual[$idx] ?? 0),
                        'periode' => intval($request->bbm_periode[$idx] ?? 1)
                    ];
                }
            }
        }
EOD;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Fixed BBM Datang parsing to prioritize bbm_kl\n";
