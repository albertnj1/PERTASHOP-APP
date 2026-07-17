<?php
$content = file_get_contents('app/Http/Controllers/MonthlyReportController.php');

$search = "        $dataParsed = [];\n        $grandTotals = [";
$replace = "        // Investors\n        \$investorsData = [];\n        if (\$request->has('investor_nama') && is_array(\$request->investor_nama)) {\n            foreach (\$request->investor_nama as \$idx => \$nama) {\n                if (!empty(\$nama)) {\n                    // Find investor from DB based on name to get rekening, or just use what's matched\n                    // For now we just get the inputs\n                    \$invModel = App\Models\Investor::whereHas('user', function(\$q) use (\$nama) {\n                        \$q->where('name', 'like', '%' . \$nama . '%');\n                    })->orWhere('atas_nama_rekening', 'like', '%' . \$nama . '%')->first();\n\n                    \$investorsData[] = [\n                        'nama' => \$nama,\n                        'persen' => floatval(\$request->investor_persen[\$idx] ?? 0),\n                        'nama_bank' => \$invModel ? \$invModel->nama_bank : '',\n                        'no_rekening' => \$invModel ? \$invModel->no_rekening : '',\n                        'atas_nama_rekening' => \$invModel ? \$invModel->atas_nama_rekening : \$nama\n                    ];\n                }\n            }\n        }\n\n        \$dataParsed = [];\n        \$grandTotals = [\n            'investors' => \$investorsData,";

$content = str_replace($search, $replace, $content);

file_put_contents('app/Http/Controllers/MonthlyReportController.php', $content);
echo "Injected investors logic!";
