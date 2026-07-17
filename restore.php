<?php
$compiledFile = 'storage/framework/views/bc1c558bf6684a692832f0f55ae62788.php';
$content = file_get_contents($compiledFile);

// Find the start of Page 2
$start = strpos($content, '<!-- CHART PENJUALAN HARIAN -->');
if ($start !== false) {
    $p2 = substr($content, $start);
    
    // Decompile Laravel Blade syntax
    $p2 = preg_replace('/<\?php\s+echo\s+e\((.*?)\);\s+\?>/s', '{{ $1 }}', $p2);
    $p2 = preg_replace('/<\?php\s+echo\s+(.*?);\s+\?>/s', '{!! $1 !!}', $p2);
    
    // Replace loops
    $p2 = preg_replace('/<\?php\s+foreach\((.*?)\):\s+\?>/s', '@foreach($1)', $p2);
    $p2 = str_replace('<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>', '@endforeach', $p2);
    
    // Add missing parts to show.blade.php
    $showFile = 'resources/views/monthly_reports/show.blade.php';
    $showContent = file_get_contents($showFile);
    
    // Remove the trailing @endsection from show.blade.php
    $showContent = preg_replace('/@endsection\s*$/', '', $showContent);
    
    // Check if Page 2 is already in show.blade.php
    if (strpos($showContent, '<!-- PAGE 2: LABA BERSIH -->') === false) {
        $showContent .= "\n" . $p2;
    }
    
    file_put_contents($showFile, $showContent);
    echo "Restored Page 2, 3, 4.";
} else {
    echo "Could not find start point.";
}
