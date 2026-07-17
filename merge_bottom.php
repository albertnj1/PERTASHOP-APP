<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');
$bottom = file_get_contents('page1_bottom.blade.php');

$pattern = '/<table class="table-bordered mt-4" style="width: 50%; float: right;">.*?<\/table>\s*<div style="clear:both;"><\/div>/s';
$content = preg_replace($pattern, $bottom, $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Replaced!";
