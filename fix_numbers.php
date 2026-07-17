<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Find the start of the period block:
// <div style="font-size: 11px; font-family: 'Times New Roman', Times, serif; margin-bottom: 10px;">
// and add position: relative; and the number box.

$search = '<div style="font-size: 11px; font-family: \'Times New Roman\', Times, serif; margin-bottom: 10px;">';
$replace = '<div style="position: relative; font-size: 11px; font-family: \'Times New Roman\', Times, serif; margin-bottom: 10px;">
        <div style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); border: 4px solid #000; padding: 10px 25px; font-size: 60px; font-weight: normal; font-family: \'Times New Roman\', Times, serif;">
            {{ $idx }}
        </div>';

$content = str_replace($search, $replace, $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed!";
