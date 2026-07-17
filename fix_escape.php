<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

$content = str_replace('{{ $arrow }}', '{!! $arrow !!}', $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed escaping!";
