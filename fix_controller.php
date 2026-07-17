<?php
$file = 'app/Http/Controllers/MonthlyReportController.php';
$content = file_get_contents($file);

// Find all occurrences of floatval($request->something) and replace them with floatval(str_replace(',', '', $request->something))
// E.g. floatval($request->bbm_kl[$idx] ?? 0)
// E.g. floatval($request->uang_di_bank)

$replacements = [
    'floatval($request->bbm_kl[$idx] ?? 0)' => 'floatval(str_replace(",", "", $request->bbm_kl[$idx] ?? 0))',
    'floatval($request->bbm_harga_beli[$idx] ?? 0)' => 'floatval(str_replace(",", "", $request->bbm_harga_beli[$idx] ?? 0))',
    'floatval($request->bbm_harga_jual[$idx] ?? 0)' => 'floatval(str_replace(",", "", $request->bbm_harga_jual[$idx] ?? 0))',
    'floatval($request->saldo_awal_modal)' => 'floatval(str_replace(",", "", $request->saldo_awal_modal))',
    'floatval($request->do_di_pertamina)' => 'floatval(str_replace(",", "", $request->do_di_pertamina))',
    'floatval($request->uang_di_bank)' => 'floatval(str_replace(",", "", $request->uang_di_bank))',
    'floatval($request->kas_kecil)' => 'floatval(str_replace(",", "", $request->kas_kecil))',
    'floatval($request->piutang)' => 'floatval(str_replace(",", "", $request->piutang))',
    'floatval($request->bunga_bank)' => 'floatval(str_replace(",", "", $request->bunga_bank))',
    'floatval($request->pajak_bank)' => 'floatval(str_replace(",", "", $request->pajak_bank))',
    'floatval($request->pengeluaran_nom[$idx] ?? 0)' => 'floatval(str_replace(",", "", $request->pengeluaran_nom[$idx] ?? 0))',
    'floatval($request->totalisator_awal)' => 'floatval(str_replace(",", "", $request->totalisator_awal))',
];

foreach ($replacements as $old => $new) {
    $content = str_replace($old, $new, $content);
}

file_put_contents($file, $content);
echo "Done replacing Controller float parsing.\n";
