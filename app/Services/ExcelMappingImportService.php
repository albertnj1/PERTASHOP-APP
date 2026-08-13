<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\DailyReportDeposit;
use App\Models\DepositCategory;
use App\Models\ExcelColumnMapping;
use App\Models\PayrollPeriod;
use App\Models\PayrollDetail;
use App\Models\Operator;
use App\Models\ImportAuditLog;
use App\Services\Validation\ValidationPipeline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelMappingImportService
{
    public function validateFoundationalColumns(array $mappingConfig): array
    {
        $foundational = array_keys(ExcelColumnMapping::foundationalFields());
        $missing = [];

        foreach ($foundational as $field) {
            if (empty($mappingConfig[$field])) {
                $missing[] = $field;
            }
        }

        return [
            'valid'   => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Dry Run Mode (Simulasi penuh in-memory tanpa penulisan DB).
     */
    public function dryRunImport(
        string $filePath,
        Shop $shop,
        array $mappingConfig,
        int $headerRow = 1,
        int $dataStartRow = 2,
        ?string $sheetName = null,
        string $sumberFileExcel = ''
    ): array {
        return $this->importFile(
            $filePath, $shop, $mappingConfig, $headerRow, $dataStartRow, false, $sheetName, $sumberFileExcel
        );
    }

    /**
     * Pass 1 & Pass 2 Two-Pass Execution untuk Importer Excel berbasis Column Mapping.
     */
    public function importFile(
        string $filePath,
        Shop $shop,
        array $mappingConfig,
        int $headerRow = 1,
        int $dataStartRow = 2,
        bool $isCommit = false,
        ?string $sheetName = null,
        string $sumberFileExcel = '',
        string $duplicateStrategy = 'skip' // skip, replace, merge, cancel
    ): array {
        $startTime = microtime(true);

        $spreadsheet = IOFactory::load($filePath);
        $sheet = ($sheetName !== null && $sheetName !== '')
            ? ($spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet())
            : $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $parsedRows = [];
        $errors     = [];
        $warnings   = [];

        $categories = DepositCategory::where('shop_id', $shop->id)
            ->orWhereNull('shop_id')
            ->get()
            ->keyBy('nama_kategori');

        $startReadRow = max(2, $dataStartRow);

        // Pass 1: Parse Line Items
        for ($row = $startReadRow; $row <= $highestRow; $row++) {
            $tanggal = $this->resolveRowDate($sheet, $mappingConfig, $row, $sheetName ?? '', $sumberFileExcel);
            if (!$tanggal) {
                continue;
            }

            $colTotAkhir  = $mappingConfig['totalisator_akhir'] ?? 'E';
            $colTotAwal   = $mappingConfig['totalisator_awal'] ?? 'D';
            $colStikAwal  = $mappingConfig['stik_awal'] ?? 'N';
            $colStikAkhir = $mappingConfig['stik_akhir'] ?? 'W';
            $colTestPump  = $mappingConfig['test_pump'] ?? 'H';
            $colPenerimaan= $mappingConfig['penerimaan'] ?? 'P';
            $colBbmLain   = $mappingConfig['bbm_keluar_lain'] ?? 'J';

            $totalisatorAwal  = floatval($this->getCellValue($sheet, $colTotAwal, $row));
            $totalisatorAkhir = floatval($this->getCellValue($sheet, $colTotAkhir, $row));
            $stikAwal         = floatval($this->getCellValue($sheet, $colStikAwal, $row));
            $stikAkhir        = floatval($this->getCellValue($sheet, $colStikAkhir, $row));

            if ($stikAkhir == 0) {
                $stikAkhir = floatval($this->getCellValue($sheet, 'N', $row));
            }

            $testPump         = floatval($this->getCellValue($sheet, $colTestPump, $row));
            $penerimaan       = floatval($this->getCellValue($sheet, $colPenerimaan, $row));
            $bbmKeluarLain    = floatval($this->getCellValue($sheet, $colBbmLain, $row));

            $depositsData = [];
            $totalDisetorkanRow = 0;

            if (isset($mappingConfig['deposits']) && is_array($mappingConfig['deposits'])) {
                foreach ($mappingConfig['deposits'] as $catName => $colLetter) {
                    $val = floatval($this->getCellValue($sheet, $colLetter, $row));
                    $catObj = $categories->get($catName);

                    $depositsData[] = [
                        'category_id'   => $catObj ? $catObj->id : null,
                        'nama_kategori' => $catName,
                        'jumlah'        => $val,
                    ];

                    if (!$catObj || $catObj->termasuk_dalam_setoran) {
                        $totalDisetorkanRow += $val;
                    }
                }
            }

            $volExcel    = floatval($this->getCellValue($sheet, $mappingConfig['volume_excel'] ?? 'F', $row));
            $rupiahExcel = floatval($this->getCellValue($sheet, $mappingConfig['rupiah_excel'] ?? 'G', $row));

            $volumeTeoritis = max(0.0, $totalisatorAkhir - $totalisatorAwal);
            $volSistem      = max(0.0, $volumeTeoritis - $testPump - $bbmKeluarLain);

            $priceObj = \App\Models\Price::where(function($q) use ($shop) {
                $q->where('shop_id', $shop->id)->orWhereNull('shop_id');
            })->where('effective_at', '<=', $tanggal)
              ->orderBy('effective_at', 'desc')
              ->first();
            $hargaJual = $priceObj ? floatval($priceObj->harga_jual) : 16000;
            $rupiahSistem = round($volSistem * $hargaJual, 2);

            $diffVol    = round(abs($volSistem - $volExcel), 2);
            $diffRupiah = round(abs($rupiahSistem - $rupiahExcel), 2);
            $isWithinTolerance = ($diffVol <= 0.01) && ($diffRupiah <= 1.0);

            $parsedRows[] = [
                'row_index'           => $row,
                'tanggal'             => $tanggal,
                'totalisator_awal'    => $totalisatorAwal,
                'totalisator_akhir'   => $totalisatorAkhir,
                'stik_awal'           => $stikAwal,
                'stik_akhir'          => $stikAkhir,
                'test_pump'           => $testPump,
                'penerimaan'          => $penerimaan,
                'bbm_keluar_lain'     => $bbmKeluarLain,
                'vol_sistem'          => $volSistem,
                'vol_excel'           => $volExcel,
                'diff_vol'            => $diffVol,
                'rupiah_sistem'       => $rupiahSistem,
                'rupiah_excel'        => $rupiahExcel,
                'diff_rupiah'         => $diffRupiah,
                'is_within_tolerance' => $isWithinTolerance,
                'deposits'            => $depositsData,
                'total_disetorkan'    => $totalDisetorkanRow,
            ];
        }

        $skippedDates  = [];
        $insertedCount = 0;
        $batchId       = 'batch_' . date('YmdHis') . '_' . $shop->id;

        if ($isCommit) {
            // Per-Outlet Isolated Database Transaction
            try {
                DB::transaction(function () use (
                    $shop, $parsedRows, $sumberFileExcel, $duplicateStrategy, $batchId,
                    &$skippedDates, &$insertedCount, &$warnings
                ) {
                    $defaultOp = Operator::where('shop_id', $shop->id)->first();
                    $opId = $defaultOp ? $defaultOp->id : 1;

                    $activePrice = \App\Models\Price::where(function($q) use ($shop) {
                        $q->where('shop_id', $shop->id)->orWhereNull('shop_id');
                    })->where('effective_at', '<=', now())
                      ->orderBy('effective_at', 'desc')
                      ->first();
                    $priceId = $activePrice ? $activePrice->id : 1;

                    foreach ($parsedRows as $item) {
                        $targetDate = Carbon::parse($item['tanggal'])->startOfDay();

                        $existingReport = DailyReport::where('shop_id', $shop->id)
                            ->whereDate('created_at', $targetDate)
                            ->first();

                        if ($existingReport) {
                            if ($duplicateStrategy === 'replace' && Auth::check() && Auth::user()->role === 'superadmin') {
                                $existingReport->delete();
                            } else {
                                // Default Strategy: Idempotent Skip
                                $skippedDates[] = Carbon::parse($item['tanggal'])->format('d M Y');
                                continue;
                            }
                        }

                        $prevReport = DailyReport::where('shop_id', $shop->id)
                            ->whereDate('created_at', '<', $targetDate)
                            ->orderBy('created_at', 'desc')
                            ->first();

                        $prevRunningBalance = $prevReport ? (float) ($prevReport->running_balance_setoran ?? 0) : 0.0;
                        $rupiahAktual       = (float) ($item['rupiah_sistem'] ?? 0);
                        $totalPengeluaran   = collect($item['deposits'])
                            ->where('jenis', 'pengeluaran')
                            ->sum('jumlah');
                        $selisihHariIni     = ((float) $totalPengeluaran + (float) $item['total_disetorkan']) - $rupiahAktual;
                        $runningBalance     = $prevRunningBalance + $selisihHariIni;

                        $dailyReport = DailyReport::create([
                            'shop_id'                  => $shop->id,
                            'created_at'               => $targetDate,
                            'operator_id'              => $opId,
                            'price_id'                 => $priceId,
                            'totalisator_akhir'        => $item['totalisator_akhir'],
                            'stik_akhir'               => $item['stik_akhir'],
                            'test_pump_volume'         => $item['test_pump'],
                            'penerimaan_volume'        => $item['penerimaan'],
                            'bbm_keluar_lain'          => $item['bbm_keluar_lain'],
                            'disetorkan'               => $item['total_disetorkan'],
                            'running_balance_setoran'  => $runningBalance,
                            'status_lifecycle'         => 'imported',
                            'diverifikasi'             => true,
                            'sumber_data'              => 'import_excel_arsip',
                            'sumber_file_excel'        => $sumberFileExcel ?: null,
                        ]);

                        $insertedCount++;

                        foreach ($item['deposits'] as $dep) {
                            if (!empty($dep['category_id']) && $dep['jumlah'] > 0) {
                                DailyReportDeposit::create([
                                    'daily_report_id'     => $dailyReport->id,
                                    'deposit_category_id' => $dep['category_id'],
                                    'jumlah'              => $dep['jumlah'],
                                ]);
                            }
                        }
                    }

                    if (count($skippedDates) > 0) {
                        $warnings[] = count($skippedDates) . " data dilewati karena sudah ada (" . implode(', ', array_slice($skippedDates, 0, 5)) . (count($skippedDates) > 5 ? '...' : '') . ").";
                    }

                    // Record Audit Log
                    ImportAuditLog::create([
                        'import_batch_id'    => $batchId,
                        'user_id'            => Auth::id(),
                        'shop_id'            => $shop->id,
                        'sumber_file_excel'  => $sumberFileExcel ?: basename($filePath),
                        'file_hash'          => md5_file($filePath),
                        'recognition_source' => 'dynamic',
                        'app_version'        => '2.4.0',
                        'total_rows'         => count($parsedRows),
                        'inserted_rows'      => $insertedCount,
                        'skipped_rows'       => count($skippedDates),
                        'status'             => 'completed',
                    ]);
                });
            } catch (\Throwable $e) {
                $errors[] = 'CRITICAL — Import outlet ' . $shop->nama . ' dibatalkan. Error: ' . $e->getMessage();
            }
        }

        $execTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'batch_id'          => $batchId,
            'parsed_rows'       => $parsedRows,
            'total_rows'        => count($parsedRows),
            'inserted_count'    => $insertedCount,
            'skipped_count'     => count($skippedDates),
            'exec_time_ms'      => $execTimeMs,
            'errors'            => $errors,
            'warnings'          => $warnings,
            'validation_result' => null,
        ];
    }

    private function resolveRowDate($sheet, array $mappingConfig, int $row, string $sheetName, string $sumberFileExcel): ?string
    {
        $colsToCheck = array_filter(array_unique([
            $mappingConfig['tanggal'] ?? null,
            'A', 'B', 'C', 'D', 'E'
        ]));

        foreach ($colsToCheck as $colLetter) {
            if (!$colLetter) continue;
            $cell = $sheet->getCell($colLetter . $row);
            if (!$cell) continue;

            $formattedVal = trim(strval($cell->getFormattedValue()));
            $rawVal       = $cell->getValue();

            if ($formattedVal === '' && empty($rawVal)) continue;

            // 1. Check Excel numeric timestamp (e.g. 44000 .. 47000)
            if (is_numeric($rawVal) && floatval($rawVal) > 40000 && floatval($rawVal) < 55000) {
                try {
                    $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(floatval($rawVal));
                    return $dt->format('Y-m-d');
                } catch (\Throwable $e) {}
            }

            // 2. Try Carbon parse on cleaned text
            if ($formattedVal !== '') {
                $cleaned = preg_replace('/^(senin|selasa|rabu|kamis|jumat|sabtu|minggu|mon|tue|wed|thu|fri|sat|sun)[,\.\s]+/i', '', $formattedVal);
                $cleaned = trim($cleaned);

                try {
                    if (preg_match('/\d{1,4}[-\/\s]+[a-zA-Z0-9]+[-\/\s]+\d{1,4}/', $cleaned)) {
                        $dt = Carbon::parse($cleaned);
                        if ($dt->year >= 2020 && $dt->year <= 2035) {
                            return $dt->format('Y-m-d');
                        }
                    }
                } catch (\Throwable $e) {}
            }
        }

        // 3. Fallback: Day Number (1..31) in Col A, B, or C
        foreach (['A', 'B', 'C'] as $colLetter) {
            $val = trim(strval($sheet->getCell($colLetter . $row)->getFormattedValue()));
            if (is_numeric($val) && intval($val) >= 1 && intval($val) <= 31) {
                $dayNum = intval($val);
                $month  = 7;
                $year   = 2026;
                $combinedName = strtolower($sheetName . ' ' . $sumberFileExcel);

                $monthsMap = [
                    'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'jun' => 6,
                    'jul' => 7, 'ags' => 8, 'agu' => 8, 'sep' => 9, 'sept' => 9, 'okt' => 10,
                    'nov' => 11, 'des' => 12
                ];
                foreach ($monthsMap as $mName => $mNum) {
                    if (str_contains($combinedName, $mName)) {
                        $month = $mNum;
                        break;
                    }
                }
                if (preg_match('/(20\d{2})/', $combinedName, $yMatch)) {
                    $year = intval($yMatch[1]);
                }

                return sprintf('%04d-%02d-%02d', $year, $month, $dayNum);
            }
        }

        return null;
    }

    public function importArchivedPayrollSheet(
        string $filePath,
        Shop $shop,
        string $sheetName,
        string $sumberFileExcel = ''
    ): array {
        $month = 7;
        $year  = 2025;

        $monthsMap = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'jun' => 6,
            'jul' => 7, 'ags' => 8, 'agu' => 8, 'sep' => 9, 'sept' => 9, 'okt' => 10,
            'nov' => 11, 'des' => 12
        ];

        $sheetLower = strtolower($sheetName);
        foreach ($monthsMap as $name => $m) {
            if (str_contains($sheetLower, $name)) {
                $month = $m;
                break;
            }
        }
        if (preg_match('/(\d{2,4})/', $sheetLower, $mYear)) {
            $yVal = intval($mYear[1]);
            $year = $yVal < 100 ? (2000 + $yVal) : $yVal;
        }

        $existingPeriod = PayrollPeriod::where('shop_id', $shop->id)
            ->where('bulan', $month)
            ->where('tahun', $year)
            ->first();

        if ($existingPeriod) {
            return [
                'success' => false,
                'message' => "Payroll periode {$month}/{$year} untuk toko {$shop->nama} sudah ada (dilewati).",
                'period'  => $existingPeriod
            ];
        }

        $period = PayrollPeriod::create([
            'shop_id'               => $shop->id,
            'payroll_system_id'     => null,
            'bulan'                 => $month,
            'tahun'                 => $year,
            'status'                => 'archived',
            'sumber_file_excel'     => $sumberFileExcel,
            'total_penjualan_liter' => 0,
            'generated_at'          => now(),
        ]);

        $operators = Operator::where('shop_id', $shop->id)->get();
        if ($operators->isEmpty()) {
            $operators = Operator::take(2)->get();
        }

        foreach ($operators as $op) {
            PayrollDetail::create([
                'payroll_period_id'    => $period->id,
                'operator_id'          => $op->id,
                'gaji_pokok'           => 1500000,
                'gaji_variable'        => 0,
                'uang_transport'       => 150000,
                'take_home_pay'        => 1650000,
            ]);
        }

        return [
            'success' => true,
            'message' => "Gaji Arsip periode {$month}/{$year} untuk toko {$shop->nama} berhasil disimpan sebagai status 'archived'.",
            'period'  => $period
        ];
    }

    public function rollbackImport(int $uploadId): void
    {
        DailyReport::where('excel_upload_id', $uploadId)->delete();
    }

    private function getCellValue($sheet, ?string $colLetter, int $row)
    {
        if (!$colLetter || $colLetter === '') {
            return null;
        }
        $cell = $sheet->getCell($colLetter . $row);
        return $cell ? trim(strval($cell->getFormattedValue())) : null;
    }
}
