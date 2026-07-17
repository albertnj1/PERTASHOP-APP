<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

$search = '$investors = $grandTotals[\'investors\'] ?? [];';
$replace = '$investors = $grandTotals[\'investors\'] ?? [];
        if (empty($investors)) {
            $shopInvs = $report->shop->investors ?? collect();
            $fallback = [];
            foreach($shopInvs as $si) {
                $fallback[] = [
                    \'nama\' => $si->user ? $si->user->name : $si->atas_nama_rekening,
                    \'persen\' => count($shopInvs) > 0 ? (100 / count($shopInvs)) : 0,
                    \'nama_bank\' => $si->nama_bank,
                    \'no_rekening\' => $si->no_rekening,
                    \'atas_nama_rekening\' => $si->atas_nama_rekening
                ];
            }
            $investors = $fallback;
        }';

$content = str_replace($search, $replace, $content);
file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Injected fallback logic!";
