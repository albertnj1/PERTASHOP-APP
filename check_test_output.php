<?php
$c = file_get_contents('test_output_11.html');
preg_match('/Pembagian Laba Bersih :.*?Catatan :/s', $c, $m);
echo "Report 11:\n";
print_r($m[0] ?? 'Not found');
echo "\n\nReport 10:\n";
$c2 = file_get_contents('test_output_10.html');
preg_match('/Pembagian Laba Bersih :.*?Catatan :/s', $c2, $m2);
print_r($m2[0] ?? 'Not found');
