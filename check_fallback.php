<?php
$c = file_get_contents('resources/views/monthly_reports/show.blade.php');
preg_match('/\$investors = \$grandTotals\[\'investors\'\] \?\? \[\];.*?}/s', $c, $m);
print_r($m[0] ?? 'Not found');
