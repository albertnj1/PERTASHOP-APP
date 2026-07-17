<?php
$file = 'resources/views/monthly_reports/create.blade.php';
$content = file_get_contents($file);

// Replace specific inputs
$replacements = [
    '<input type="number" step="0.001" name="totalisator_awal"' => '<input type="text" inputmode="decimal" name="totalisator_awal"',
    '<input type="number" step="0.01" name="bbm_kl[]"' => '<input type="text" inputmode="decimal" name="bbm_kl[]"',
    '<input type="number" step="0.01" name="bbm_harga_beli[]"' => '<input type="text" inputmode="decimal" name="bbm_harga_beli[]"',
    '<input type="number" step="0.01" name="bbm_harga_jual[]"' => '<input type="text" inputmode="decimal" name="bbm_harga_jual[]"',
    '<input type="number" step="0.01" name="saldo_awal_modal"' => '<input type="text" inputmode="decimal" name="saldo_awal_modal"',
    '<input type="number" step="0.01" name="do_di_pertamina"' => '<input type="text" inputmode="decimal" name="do_di_pertamina"',
    '<input type="number" name="uang_di_bank"' => '<input type="text" inputmode="decimal" name="uang_di_bank"',
    '<input type="number" name="kas_kecil"' => '<input type="text" inputmode="decimal" name="kas_kecil"',
    '<input type="number" name="piutang"' => '<input type="text" inputmode="decimal" name="piutang"',
    '<input type="number" name="bunga_bank"' => '<input type="text" inputmode="decimal" name="bunga_bank"',
    '<input type="number" name="pajak_bank"' => '<input type="text" inputmode="decimal" name="pajak_bank"',
    '<input type="number" name="pengeluaran_nom[]"' => '<input type="text" inputmode="decimal" name="pengeluaran_nom[]"',
];

foreach ($replacements as $old => $new) {
    $content = str_replace($old, $new, $content);
}

// In the JS Auto-calculate block, replace parseFloat with comma-sanitized parseFloat
$oldJs = "const kl = parseFloat(klInput.value) || 0;
            const beli = parseFloat(beliInput.value) || 0;";
$newJs = "const kl = parseFloat(klInput.value.replace(/,/g, '')) || 0;
            const beli = parseFloat(beliInput.value.replace(/,/g, '')) || 0;";
$content = str_replace($oldJs, $newJs, $content);

file_put_contents($file, $content);
echo "Done replacing HTML inputs.\n";
