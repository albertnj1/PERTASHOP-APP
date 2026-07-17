<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MonthlyReport;
use App\Models\CapitalRecap;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;

$reports = MonthlyReport::all();
foreach ($reports as $report) {
    echo "Checking report ID " . $report->id . " file_path: " . $report->file_path . "\n";
    if (!$report->file_path || !Storage::disk('public')->exists($report->file_path)) {
        echo "  -> File missing in storage.\n";
        continue;
    }
    
    $shop = Shop::find($report->shop_id);
    if (!$shop) {
        echo "  -> Shop missing.\n";
        continue;
    }

    echo "  -> Processing " . $report->file_path . "...\n";
    $filePath = storage_path('app/public/' . $report->file_path);
    
    $reportDate = \Carbon\Carbon::parse($report->bulan_tahun);
    $reportMonth = $reportDate->month;
    $reportYear = $reportDate->year;
    
    try {
        $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $rekapModalSheet = null;
        foreach ($spreadsheet->getAllSheets() as $sh) {
            $title = strtolower($sh->getTitle());
            if (str_contains($title, 'rekap') && str_contains($title, 'modal')) {
                $rekapModalSheet = $sh;
                break;
            }
        }
        
        if ($rekapModalSheet) {
            // Read header row (Row 2) to establish column mapping dynamically
            $colMapping = [];
            $headerRow = 2;
            for ($c = 1; $c <= 15; $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $val = trim(strtolower($rekapModalSheet->getCell($colLetter . $headerRow)->getValue() ?? ''));
                
                if (str_contains($val, 'thn ke') || str_contains($val, 'tahun ke')) {
                    $colMapping['tahun_ke'] = $colLetter;
                } elseif ($val === 'bulan') {
                    $colMapping['bulan'] = $colLetter;
                } elseif (str_contains($val, 'nilai modal awal') || str_contains($val, 'modal awal')) {
                    $colMapping['nilai_modal_awal'] = $colLetter;
                } elseif (str_contains($val, 'penyusutan karena rugi') || str_contains($val, 'penyusutan rugi')) {
                    $colMapping['penyusutan_rugi'] = $colLetter;
                } elseif (str_contains($val, 'pajak') || str_contains($val, 'biaya bank')) {
                    $colMapping['penyusutan_pajak_bank'] = $colLetter;
                } elseif (str_contains($val, 'alokasi keuntungan') || str_contains($val, 'keuntungan')) {
                    $colMapping['penambahan_keuntungan'] = $colLetter;
                } elseif (str_contains($val, 'bunga bank')) {
                    $colMapping['penambahan_bunga_bank'] = $colLetter;
                } elseif (str_contains($val, 'nilai penambahan') || str_contains($val, 'penambahan / penyusutan  modal')) {
                    $colMapping['nilai_penambahan_penyusutan'] = $colLetter;
                } elseif (str_contains($val, 'akumulasi')) {
                    $colMapping['akumulasi_penambahan_penyusutan'] = $colLetter;
                } elseif (str_contains($val, 'posisi akhir')) {
                    $colMapping['posisi_akhir_modal'] = $colLetter;
                } elseif (str_contains($val, 'harga beli') || str_contains($val, 'harga pertamax')) {
                    $colMapping['harga_beli_pertamax'] = $colLetter;
                } elseif (str_contains($val, 'konversi')) {
                    $colMapping['konversi_liter'] = $colLetter;
                }
            }

            $col_tahun_ke = $colMapping['tahun_ke'] ?? 'A';
            $col_bulan = $colMapping['bulan'] ?? 'B';
            $col_modal_awal = $colMapping['nilai_modal_awal'] ?? 'C';
            $col_rugi = $colMapping['penyusutan_rugi'] ?? 'D';
            $col_pajak = $colMapping['penyusutan_pajak_bank'] ?? 'E';
            $col_keuntungan = $colMapping['penambahan_keuntungan'] ?? 'F';
            $col_bunga = $colMapping['penambahan_bunga_bank'] ?? 'G';
            $col_nilai_penambahan = $colMapping['nilai_penambahan_penyusutan'] ?? 'H';
            $col_akumulasi = $colMapping['akumulasi_penambahan_penyusutan'] ?? 'I';
            $col_posisi_akhir = $colMapping['posisi_akhir_modal'] ?? 'J';
            $col_harga_beli = $colMapping['harga_beli_pertamax'] ?? 'K';
            $col_konversi = $colMapping['konversi_liter'] ?? 'L';

            $highestRowRM = $rekapModalSheet->getHighestRow();
            $current_tahun_ke = 1;
            for ($row = 4; $row <= $highestRowRM; $row++) {
                $tahun_ke_raw = $rekapModalSheet->getCell($col_tahun_ke . $row)->getCalculatedValue();
                if ($tahun_ke_raw !== null && $tahun_ke_raw !== '') {
                    $current_tahun_ke = intval($tahun_ke_raw);
                }
                $tahun_ke = $current_tahun_ke;
                
                $bulan_raw = $rekapModalSheet->getCell($col_bulan . $row)->getCalculatedValue();
                
                $bulanRM = 0;
                $tahunRM = 0;
                
                if (is_numeric($bulan_raw)) {
                    try {
                        $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($bulan_raw);
                        $bulanRM = intval($dateObj->format('n'));
                        $tahunRM = intval($dateObj->format('Y'));
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                
                if (!$bulanRM || !$tahunRM) continue;
                
                // Prevent importing future placeholder months
                if ($tahunRM > $reportYear || ($tahunRM == $reportYear && $bulanRM > $reportMonth)) {
                    continue;
                }
                
                $modal_awal = floatval($rekapModalSheet->getCell($col_modal_awal . $row)->getCalculatedValue());
                $rugi = floatval($rekapModalSheet->getCell($col_rugi . $row)->getCalculatedValue());
                $pajak = floatval($rekapModalSheet->getCell($col_pajak . $row)->getCalculatedValue());
                $keuntungan = floatval($rekapModalSheet->getCell($col_keuntungan . $row)->getCalculatedValue());
                $bunga = floatval($rekapModalSheet->getCell($col_bunga . $row)->getCalculatedValue());
                $nilai_penambahan = floatval($rekapModalSheet->getCell($col_nilai_penambahan . $row)->getCalculatedValue());
                $akumulasi = floatval($rekapModalSheet->getCell($col_akumulasi . $row)->getCalculatedValue());
                $posisi_akhir = floatval($rekapModalSheet->getCell($col_posisi_akhir . $row)->getCalculatedValue());
                $harga_beli_rm = floatval($rekapModalSheet->getCell($col_harga_beli . $row)->getCalculatedValue());
                $konversi = floatval($rekapModalSheet->getCell($col_konversi . $row)->getCalculatedValue());

                CapitalRecap::updateOrCreate(
                    [
                        'shop_id' => $shop->id,
                        'bulan' => $bulanRM,
                        'tahun' => $tahunRM,
                    ],
                    [
                        'tahun_ke' => $tahun_ke,
                        'nilai_modal_awal' => $modal_awal,
                        'penyusutan_rugi' => $rugi,
                        'penyusutan_pajak_bank' => $pajak,
                        'penambahan_keuntungan' => $keuntungan,
                        'penambahan_bunga_bank' => $bunga,
                        'nilai_penambahan_penyusutan' => $nilai_penambahan,
                        'akumulasi_penambahan_penyusutan' => $akumulasi,
                        'posisi_akhir_modal' => $posisi_akhir,
                        'harga_beli_pertamax' => $harga_beli_rm,
                        'konversi_liter' => $konversi,
                    ]
                );
            }
            CapitalRecap::recalculateForShop($shop->id);
            echo "Imported Rekap Modal for " . $shop->nama . "!\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
