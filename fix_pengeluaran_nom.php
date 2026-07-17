<?php
$file = 'app/Http/Controllers/MonthlyReportController.php';
$content = file_get_contents($file);

$search1 = <<<'EOD'
                    $nom = floatval($request->pengeluaran_nom[$index] ?? 0);
EOD;

$replace1 = <<<'EOD'
                    $nom = $this->parseFlexibleNumber($request->pengeluaran_nom[$index] ?? 0);
EOD;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Fixed pengeluaran_nom parsing in controller\n";
