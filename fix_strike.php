<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// We need to replace the Naik/Turun block with the dynamic strikethrough logic.
// Original block:
// @php
//     $isNaik = false;
//     $labelNT = 'Naik/Turun';
//     if ($idx > 1) {
//         $prevP = $page1Periods[$idx - 1] ?? null;
//         if ($prevP) {
//             $isNaik = ($p['harga_jual'] ?? 0) > ($prevP['harga_jual'] ?? 0);
//             $labelNT = $isNaik ? 'Naik' : 'Turun';
//         }
//     }
// @endphp
//
// <div style="font-size: 12px;">Setelah <span style="color:#0d6efd">Naik</span>/<span style="color:red">Turun</span></div>

$search = "            @php\n" .
"                \$isNaik = false;\n" .
"                \$labelNT = 'Naik/Turun';\n" .
"                if (\$idx > 1) {\n" .
"                    \$prevP = \$page1Periods[\$idx - 1] ?? null;\n" .
"                    if (\$prevP) {\n" .
"                        \$isNaik = (\$p['harga_jual'] ?? 0) > (\$prevP['harga_jual'] ?? 0);\n" .
"                        \$labelNT = \$isNaik ? 'Naik' : 'Turun';\n" .
"                    }\n" .
"                }\n" .
"            @endphp";

$replace = "            @php\n" .
"                \$isNaik = false;\n" .
"                \$isTurun = false;\n" .
"                if (\$idx > 1) {\n" .
"                    // use array keys properly since \$idx is 1, 2, 3 but array keys might be different if they don't start at 1\n" .
"                    \$keys = array_keys(\$page1Periods);\n" .
"                    \$currentIndex = array_search(\$idx, \$keys);\n" .
"                    if (\$currentIndex > 0) {\n" .
"                        \$prevKey = \$keys[\$currentIndex - 1];\n" .
"                        \$prevP = \$page1Periods[\$prevKey];\n" .
"                        \$isNaik = (\$p['harga_beli'] ?? 0) > (\$prevP['harga_beli'] ?? 0);\n" .
"                        \$isTurun = (\$p['harga_beli'] ?? 0) < (\$prevP['harga_beli'] ?? 0);\n" .
"                    }\n" .
"                }\n" .
"            @endphp";

$content = str_replace($search, $replace, $content);

$searchHtml = '<div style="font-size: 12px;">Setelah <span style="color:#0d6efd">Naik</span>/<span style="color:red">Turun</span></div>';
$replaceHtml = '<div style="font-size: 12px;">Setelah <span style="color:#0d6efd; {{ $isTurun ? \'text-decoration: line-through;\' : \'\' }}">Naik</span>/<span style="color:red; {{ $isNaik ? \'text-decoration: line-through;\' : \'\' }}">Turun</span></div>';

$content = str_replace($searchHtml, $replaceHtml, $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed strike!";
