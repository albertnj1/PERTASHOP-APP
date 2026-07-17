<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

// Replace all $p['...'] with ($p['...'] ?? 0) except for string fields like start_date, end_date
$replacements = [
    "\$p['harga_beli']" => "(\$p['harga_beli'] ?? 0)",
    "\$p['harga_jual']" => "(\$p['harga_jual'] ?? 0)",
    "\$p['tot_akhir']" => "(\$p['tot_akhir'] ?? 0)",
    "\$p['tot_awal']" => "(\$p['tot_awal'] ?? 0)",
    "\$p['test_pump']" => "(\$p['test_pump'] ?? 0)",
    "\$p['stok_aktual']" => "(\$p['stok_aktual'] ?? 0)",
    "\$p['start_date']" => "(\$p['start_date'] ?? '-')",
    "\$p['end_date']" => "(\$p['end_date'] ?? '-')",
];

foreach ($replacements as $search => $replace) {
    // Avoid double replacing if it's already ?? 0
    // Actually, simple str_replace might be tricky if some already have ?? 0
    // We will just do it, but first remove existing ?? 0 if any
}

// Better way: use regex to replace $p['key'] with ($p['key'] ?? 0)
// For safe replacement, let's just write a script to replace them exactly where they are used.

$content = preg_replace("/\\\$p\['(harga_beli|harga_jual|tot_akhir|tot_awal|test_pump|stok_aktual)'\]/", "(\$p['$1'] ?? 0)", $content);
$content = preg_replace("/\\\$p\['(start_date|end_date)'\]/", "(\$p['$1'] ?? '-')", $content);

// We also need to fix end($periods)['harga_beli']
$content = str_replace("end(\$periods)['harga_beli']", "(end(\$periods)['harga_beli'] ?? 0)", $content);

file_put_contents($file, $content);
echo "Fixed missing keys in show.blade.php\n";
