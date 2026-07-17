<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search1 = <<<'EOD'
        $bbmDatangArray = $grandTotals['bbm_datang'] ?? [];
EOD;

$replace1 = <<<'EOD'
        $bbmDatangArray = $report->bbm_datang ?? [];
EOD;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Fixed bbmDatangArray source in show.blade.php\n";
