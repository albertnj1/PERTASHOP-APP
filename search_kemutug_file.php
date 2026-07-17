<?php
require __DIR__.'/vendor/autoload.php';

// Inspect ANY Excel file with "Kemutug" in the name from Desktop, Downloads, etc.
$searchPaths = [
    'C:/Users/user/Desktop/',
    'C:/Users/user/Downloads/',
    'C:/Users/user/Documents/',
    __DIR__ . '/storage/app/public/monthly_reports/',
];

echo "Searching for Kemutug Excel files...\n";
foreach ($searchPaths as $path) {
    $files = glob($path . '*emutug*') ?: [];
    $files2 = glob($path . '*KML*') ?: [];
    $files3 = glob($path . '*KMT*') ?: [];
    $all = array_merge($files, $files2, $files3);
    foreach ($all as $f) {
        echo "FOUND: $f (" . date('Y-m-d H:i', filemtime($f)) . ")\n";
    }
}
