<?php
require 'vendor/autoload.php';
$s = \PhpOffice\PhpSpreadsheet\IOFactory::load('C:/Users/user/Downloads/06. Sales Report Sumingkir 01 - 30 Juni 2026 2026.xlsx');
$sh = $s->getSheet(0); // BKH
$rows = $sh->toArray();

$opStartCol = -1;
for ($r = 0; $r <= 2; $r++) {
    if (!isset($rows[$r])) continue;
    foreach ($rows[$r] as $c => $val) {
        if (is_string($val) && str_contains(strtolower($val), 'gaji opr')) {
            $opStartCol = $c;
            break 2;
        }
    }
}

if ($opStartCol == -1) {
    echo "Gaji Opr column not found!\n";
    exit;
}

echo "Found Gaji Opr at col: $opStartCol\n";
$operators = [];
$r3 = $rows[2];
for ($c = $opStartCol; $c < count($r3); $c++) {
    $name = trim(strtoupper((string)($r3[$c] ?? '')));
    if (!empty($name) && !in_array($name, ['TOTAL', 'JUMLAH'])) {
        $operators[$c] = $name;
    } else if (empty($name)) {
        break; // stop at first empty cell after operators
    }
}
print_r($operators);

$opSalaries = [];
foreach ($operators as $opName) {
    $opSalaries[$opName] = [
        'operator_nama' => $opName,
        'hari_jaga' => 0,
        'total_penjualan_b' => 0,
        'losses_c' => 0,
        'penjualan_losses_d' => 0,
        'gaji' => 0
    ];
}

for ($i = 3; $i < count($rows); $i++) {
    $r = $rows[$i];
    $tgl_str = trim($r[1] ?? '');
    if (empty($tgl_str) || !is_numeric($tgl_str)) {
        $tgl_str = trim($r[0] ?? '');
    }
    if (empty($tgl_str) || !is_numeric($tgl_str)) continue;
    
    $vol_teoritis = floatval(preg_replace('/[^\d\.\-]/', '', $r[5] ?? 0));
    $tp_vol = floatval(preg_replace('/[^\d\.\-]/', '', $r[7] ?? 0));
    $losses_vol = floatval(preg_replace('/[^\d\.\-]/', '', str_replace(['(', ')'], ['-', ''], $r[19] ?? 0)));
    
    $b_vol = max(0, $vol_teoritis - $tp_vol);
    $c_vol = $losses_vol;
    
    $day_gajis = [];
    $total_gaji_day = 0;
    foreach ($operators as $c => $opName) {
        $gaji = floatval(preg_replace('/[^\d\.\-]/', '', str_replace(['(', ')'], ['-', ''], $r[$c] ?? 0)));
        if ($gaji > 0) {
            $day_gajis[$opName] = $gaji;
            $total_gaji_day += $gaji;
        }
    }
    
    if ($total_gaji_day > 0) {
        foreach ($day_gajis as $opName => $gaji) {
            $ratio = $gaji / $total_gaji_day;
            $op_b = $b_vol * $ratio;
            $op_c = $c_vol * $ratio;
            $op_d = $op_b + $op_c;
            
            $opSalaries[$opName]['hari_jaga'] += 1;
            $opSalaries[$opName]['total_penjualan_b'] += $op_b;
            $opSalaries[$opName]['losses_c'] += $op_c;
            $opSalaries[$opName]['penjualan_losses_d'] += $op_d;
            $opSalaries[$opName]['gaji'] += $gaji;
        }
    }
}

print_r($opSalaries);
