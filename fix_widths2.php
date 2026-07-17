<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

$content = str_replace('<td width="35%" style="white-space: nowrap; overflow: hidden; max-width: 150px;">{{ $inv[\'nama\'] }}', '<td width="70%" style="white-space: nowrap; overflow: hidden; max-width: 450px;">{{ $inv[\'nama\'] }}', $content);
$content = str_replace('<td width="48%" class="text-right">', '<td width="14%" class="text-right">', $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed table 2 widths!";
