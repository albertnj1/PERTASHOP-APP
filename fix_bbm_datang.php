<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search = <<<'EOD'
        foreach($periods as $idx => $p) {
            $jmlBeliL = $currentStokAwal + $p['tot_bbm_datang'];
            $jmlBeliRp = ($currentStokAwal * $p['harga_beli']) + $p['tot_bbm_datang_rp'];
EOD;

$replace = <<<'EOD'
        $bbmDatangArray = $grandTotals['bbm_datang'] ?? [];
        
        foreach($periods as $idx => $p) {
            $totBbmDatang = 0;
            $totBbmDatangRp = 0;
            foreach ($bbmDatangArray as $bbm) {
                if (($bbm['periode'] ?? 1) == $idx) {
                    $totBbmDatang += floatval($bbm['volume']);
                    $totBbmDatangRp += floatval($bbm['volume']) * floatval($p['harga_beli']);
                }
            }
            
            $jmlBeliL = $currentStokAwal + $totBbmDatang;
            $jmlBeliRp = ($currentStokAwal * $p['harga_beli']) + $totBbmDatangRp;
EOD;

$content = str_replace($search, $replace, $content);

// Also I need to replace $p['tot_bbm_datang'] where it's used in page1Periods array creation!
$search2 = <<<'EOD'
            $page1Periods[$idx] = [
                'stok_awal' => $currentStokAwal,
                'bbm_datang' => $p['tot_bbm_datang'],
EOD;

$replace2 = <<<'EOD'
            $page1Periods[$idx] = [
                'stok_awal' => $currentStokAwal,
                'bbm_datang' => $totBbmDatang,
EOD;

$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Fixed show.blade.php\n";
