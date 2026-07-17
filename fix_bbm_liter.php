<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search3 = <<<'EOD'
            foreach ($bbmDatangArray as $bbm) {
                if (($bbm['periode'] ?? 1) == $idx) {
                    $totBbmDatang += floatval($bbm['volume']);
                    $totBbmDatangRp += floatval($bbm['volume']) * floatval(($p['harga_beli'] ?? 0));
                }
            }
EOD;

$replace3 = <<<'EOD'
            foreach ($bbmDatangArray as $bbm) {
                if (($bbm['periode'] ?? 1) == $idx) {
                    $liter = $bbm['liter'] ?? $bbm['volume'] ?? 0;
                    $totBbmDatang += floatval($liter);
                    $totBbmDatangRp += floatval($liter) * floatval(($p['harga_beli'] ?? 0));
                }
            }
EOD;

$content = str_replace($search3, $replace3, $content);
file_put_contents($file, $content);
echo "Fixed bbm volume to liter in show.blade.php\n";
