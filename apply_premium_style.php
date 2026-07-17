<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Add table-premium to Page 1 Main Data Table
$content = str_replace('<table class="table-bordered mt-4">', '<table class="table-premium mt-4">', $content);

// Update Page 2 Laba Bersih Table Header
$content = preg_replace('/(PENGHASILAN.*?<\/td>)/s', '<td colspan="4" class="text-center fw-bold text-white" style="background-color: #2b6cb0;">PENGHASILAN</td>', $content);
$content = preg_replace('/(PENGELUARAN.*?<\/td>)/s', '<td colspan="4" class="text-center fw-bold text-white" style="background-color: #e53e3e;">PENGELUARAN</td>', $content);
$content = preg_replace('/(TOTAL PENGHASILAN.*?<\/td>)/s', '<td colspan="2" class="fw-bold" style="background-color: #ebf8ff; color: #2b6cb0;">TOTAL PENGHASILAN</td>', $content);
$content = preg_replace('/(TOTAL PENGELUARAN.*?<\/td>)/s', '<td colspan="2" class="fw-bold" style="background-color: #fff5f5; color: #e53e3e;">TOTAL PENGELUARAN</td>', $content);
$content = preg_replace('/(LABA KOTOR \(A-B\).*?<\/td>)/s', '<td colspan="2" class="fw-bold text-white" style="background-color: #38a169;">LABA KOTOR (A-B)</td>', $content);
$content = preg_replace('/(LABA BERSIH \(C-D\).*?<\/td>)/s', '<td colspan="2" class="fw-bold text-white" style="background-color: #38a169; font-size: 14px;">LABA BERSIH (C-D)</td>', $content);

// Ensure PAGE 3 POSISI MODAL KERJA retains its specific style
$content = str_replace('<td colspan="7" class="text-center fw-bold bg-light">POSISI MODAL KERJA</td>', '<td colspan="7" class="text-center fw-bold text-white" style="background-color: #2b6cb0;">POSISI MODAL KERJA</td>', $content);

// Page 4: Rekapitulasi Table Header
$content = str_replace('<thead class="bg-light text-center">', '<thead class="text-center text-white" style="background-color: #2b6cb0;">', $content);

// We should be careful to only replace what's matched.
file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Applied premium styling and colors.";
