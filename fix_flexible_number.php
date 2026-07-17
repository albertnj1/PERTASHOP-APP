<?php
$file = 'app/Http/Controllers/MonthlyReportController.php';
$content = file_get_contents($file);

$func = '
    private function parseFlexibleNumber($val) {
        if (empty($val)) return 0;
        if (is_numeric($val)) return floatval($val);
        $val = trim($val);
        // Remove spaces
        $val = str_replace(" ", "", $val);
        
        $lastComma = strrpos($val, ",");
        $lastDot = strrpos($val, ".");
        
        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                // Comma is decimal
                $val = str_replace(".", "", $val);
                $val = str_replace(",", ".", $val);
            } else {
                // Dot is decimal
                $val = str_replace(",", "", $val);
            }
        } elseif ($lastComma !== false) {
            // Only commas exist
            $parts = explode(",", $val);
            if (strlen(end($parts)) !== 3) {
                // If the part after the last comma is NOT 3 digits, it is likely a decimal
                $val = str_replace(",", ".", $val);
            } else {
                // Likely thousands separator
                $val = str_replace(",", "", $val);
            }
        } elseif ($lastDot !== false) {
            // Only dots exist
            $parts = explode(".", $val);
            if (strlen(end($parts)) === 3 && count($parts) > 1) {
                // If it ends with exactly 3 digits, usually it is thousands separator in ID (e.g. 67.000)
                $val = str_replace(".", "", $val);
            } else {
                // Otherwise it is decimal (e.g. 67.5)
            }
        }
        
        return floatval($val);
    }

    public function store(Request $request)
';

$content = str_replace('public function store(Request $request)', ltrim($func), $content);

// Replace all floatval(str_replace(",", "", ...)) with $this->parseFlexibleNumber(...)
$replacements = [
    'floatval(str_replace(",", "", $request->bbm_kl[$idx] ?? 0))' => '$this->parseFlexibleNumber($request->bbm_kl[$idx] ?? 0)',
    'floatval(str_replace(",", "", $request->bbm_harga_beli[$idx] ?? 0))' => '$this->parseFlexibleNumber($request->bbm_harga_beli[$idx] ?? 0)',
    'floatval(str_replace(",", "", $request->bbm_harga_jual[$idx] ?? 0))' => '$this->parseFlexibleNumber($request->bbm_harga_jual[$idx] ?? 0)',
    'floatval(str_replace(",", "", $request->saldo_awal_modal))' => '$this->parseFlexibleNumber($request->saldo_awal_modal)',
    'floatval(str_replace(",", "", $request->do_di_pertamina))' => '$this->parseFlexibleNumber($request->do_di_pertamina)',
    'floatval(str_replace(",", "", $request->uang_di_bank))' => '$this->parseFlexibleNumber($request->uang_di_bank)',
    'floatval(str_replace(",", "", $request->kas_kecil))' => '$this->parseFlexibleNumber($request->kas_kecil)',
    'floatval(str_replace(",", "", $request->piutang))' => '$this->parseFlexibleNumber($request->piutang)',
    'floatval(str_replace(",", "", $request->bunga_bank))' => '$this->parseFlexibleNumber($request->bunga_bank)',
    'floatval(str_replace(",", "", $request->pajak_bank))' => '$this->parseFlexibleNumber($request->pajak_bank)',
    'floatval(str_replace(",", "", $request->pengeluaran_nom[$idx] ?? 0))' => '$this->parseFlexibleNumber($request->pengeluaran_nom[$idx] ?? 0)',
    'floatval(str_replace(",", "", $request->totalisator_awal ?? $shop->totalisator_awal))' => '$this->parseFlexibleNumber($request->totalisator_awal ?? $shop->totalisator_awal)',
];

foreach ($replacements as $old => $new) {
    $content = str_replace($old, $new, $content);
}

file_put_contents($file, $content);
echo "Done replacing float parsing with parseFlexibleNumber.\n";
