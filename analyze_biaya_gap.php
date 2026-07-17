<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Inspect report 42 (Kalitapen) - biaya breakdown
$report = \App\Models\MonthlyReport::find(42);
$dataParsed = $report->data_parsed;
$dailyData = $dataParsed['daily_data'] ?? [];

$t_biaya = 0;
foreach ($dailyData as $row) {
    $t_biaya += floatval($row['biaya']['total'] ?? 0);
}
$totalGajiOperator = collect($dataParsed['operator_salaries'] ?? [])->sum('gaji');
$extraSpendingsSum = collect($dataParsed['pengeluaran_extra'] ?? [])->sum('nominal');

$biayaInExcel = floatval($dataParsed['total_biaya'] ?? 0);
$biayaRecalc = $t_biaya + $totalGajiOperator + $extraSpendingsSum;
$diff = $biayaInExcel - $biayaRecalc;

echo "=== Kalitapen Biaya Breakdown ===\n";
echo "Total Biaya in Excel (data_parsed['total_biaya']): " . number_format($biayaInExcel) . "\n";
echo "  t_biaya (from BKH daily rows): " . number_format($t_biaya) . "\n";
echo "  Gaji Operator: " . number_format($totalGajiOperator) . "\n";
echo "  Extra Spendings (pengeluaran_extra): " . number_format($extraSpendingsSum) . "\n";
echo "  Recalculated Total: " . number_format($biayaRecalc) . "\n";
echo "  Diff: " . number_format($diff) . "\n";
echo "\nExplanation: The difference is because Excel's 'KLT Laba Bersih' sheet includes\n";
echo "items not captured in BKH (daily) rows: e.g. LAIN2, OPERASIONAL JUS, etc.\n";
echo "These are not standard spending categories and were captured in Excel but NOT in\n";
echo "individual daily_reports spendings table.\n";

// Check what's in the sheet 3 (KLT Laba Bersih)
$filePath = __DIR__.'/storage/app/public/monthly_reports/1783188597_06. Sales Report Kalitapen 01 - 30 Juni 2026.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
$sheet3 = $spreadsheet->getSheet(3);
$rows3 = $sheet3->toArray(null, true, true, true);

echo "\n=== Sheet 3 Biaya Items ===\n";
foreach ($rows3 as $idx => $r3) {
    $colB = trim((string)($r3['B'] ?? ''));
    $colN = trim((string)($r3['N'] ?? ''));
    if (!empty($colB) && !empty($colN) && stripos($colB, 'total') === false) {
        echo "  $colB => $colN\n";
    }
}
