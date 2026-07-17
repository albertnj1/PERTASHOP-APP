<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Insert Canvas 1 at the very end of Page 1 (before <!-- PAGE 2: RINCIAN PENGELUARAN -->)
$canvas1 = <<<EOT

        <!-- CHART PENJUALAN HARIAN -->
        <div style="margin-top: 20px; height: 250px; width: 100%; border: 1px solid #ddd; padding: 10px; border-radius: 8px;" class="shadow-sm">
            <canvas id="chartPenjualan"></canvas>
        </div>
EOT;

// Only inject if not already there
if (strpos($content, 'id="chartPenjualan"') === false) {
    $content = str_replace('<!-- PAGE 2: RINCIAN PENGELUARAN -->', $canvas1 . "\n    <!-- PAGE 2: RINCIAN PENGELUARAN -->", $content);
}

// Insert Canvas 4 at the beginning of Page 4 (after report-header)
$canvas4 = <<<EOT

        <!-- CHART PERTUMBUHAN MODAL -->
        <div style="margin-top: 20px; margin-bottom: 20px; height: 300px; width: 100%; border: 1px solid #ddd; padding: 10px; border-radius: 8px;" class="shadow-sm">
            <canvas id="chartModal"></canvas>
        </div>
EOT;

// Let's check where Page 4 starts
if (strpos($content, 'id="chartModal"') === false) {
    $content = preg_replace('/(REKAPITULASI NILAI MODAL.*?<\/div>)/s', "$1" . $canvas4, $content);
    // If it still fails, let's just insert it before '<table class="table-bordered mt-4">' in Page 4
    if (strpos($content, 'id="chartModal"') === false) {
        $content = str_replace('<table class="table-bordered mt-4">', $canvas4 . "\n        <table class="table-bordered mt-4">", $content);
    }
}

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Injected canvas tags.";
