<?php
$content = file_get_contents('app/Http/Controllers/MonthlyReportController.php');

$search = '        $dataParsed = [];
        $grandTotals = [';

$replace = '        // Investors
        $investorsData = [];
        if ($request->has(\'investor_nama\') && is_array($request->investor_nama)) {
            foreach ($request->investor_nama as $idx => $nama) {
                if (!empty($nama)) {
                    // Find investor from DB based on name to get rekening
                    $invModel = \App\Models\Investor::whereHas(\'user\', function($q) use ($nama) {
                        $q->where(\'name\', \'like\', \'%\' . $nama . \'%\');
                    })->orWhere(\'atas_nama_rekening\', \'like\', \'%\' . $nama . \'%\')->first();

                    $investorsData[] = [
                        \'nama\' => $nama,
                        \'persen\' => floatval($request->investor_persen[$idx] ?? 0),
                        \'nama_bank\' => $invModel ? $invModel->nama_bank : \'\',
                        \'no_rekening\' => $invModel ? $invModel->no_rekening : \'\',
                        \'atas_nama_rekening\' => $invModel ? $invModel->atas_nama_rekening : $nama
                    ];
                }
            }
        }

        $dataParsed = [];
        $grandTotals = [
            \'investors\' => $investorsData,';

$content = str_replace($search, $replace, $content);

file_put_contents('app/Http/Controllers/MonthlyReportController.php', $content);
echo "Injected investors logic properly!";
