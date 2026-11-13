<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Price;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyReportCalculationService
{
    /**
     * Rekalkulasi berantai (cascade recalculation) untuk satu gerai
     * mulai dari tanggal tertentu hingga akhir bulan/periode.
     *
     * Berantai:
     * - totalisator_awal = totalisator_akhir hari sebelumnya
     * - stok_awal        = stok_akhir_aktual hari sebelumnya
     * - running_balance  = running_balance hari sebelumnya + selisih_setoran
     */
    public static function recalculateCascade(
        int $shopId,
        ?Carbon $fromDate = null,
        ?float $initialStartingBalance = null,
        ?float $initialStartingTotalisator = null,
        ?float $initialStartingStok = null
    ): void {
        DailyReport::clearInternalCache();

        $query = DailyReport::where('shop_id', $shopId)->orderBy('created_at', 'asc');
        $allReports = $query->get();

        if ($allReports->isEmpty()) {
            return;
        }

        $shop = Shop::find($shopId);
        $runningBalance = $initialStartingBalance ?? floatval($shop?->uang_belum_disetor_awal ?? 0);
        $prevTotalisatorAkhir = $initialStartingTotalisator ?? floatval($shop?->totalisator_awal ?? 0);
        $prevStokAkhir = $initialStartingStok ?? floatval($shop?->stok_awal ?? ($shop ? $shop->stik_awal * ($shop->skala ?: 21.49) : 0));

        if ($initialStartingBalance === null && $allReports->isNotEmpty()) {
            $firstReport = $allReports->first();
            $firstDate = Carbon::parse($firstReport->report_date ?? $firstReport->created_at);
            $prevMonthReport = DailyReport::where('shop_id', $shopId)
                ->where('created_at', '<', $firstDate)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($prevMonthReport && $prevMonthReport->running_balance_setoran !== null) {
                $runningBalance = floatval($prevMonthReport->running_balance_setoran);
                $prevTotalisatorAkhir = floatval($prevMonthReport->totalisator_akhir);
                $prevStokAkhir = floatval($prevMonthReport->stok_akhir_aktual);
            }
        }

        $isFirst = true;

        foreach ($allReports as $report) {
            $reportDate = $report->report_date ? $report->report_date->toDateString() : Carbon::parse($report->created_at)->toDateString();

            // 1. Totalisator Awal & Stok Awal
            if ($isFirst) {
                $totalisatorAwal = (floatval($report->totalisator_awal) > 0) ? floatval($report->totalisator_awal) : $prevTotalisatorAkhir;
                $stokAwal = (floatval($report->stok_awal) > 0) ? floatval($report->stok_awal) : $prevStokAkhir;
                $isFirst = false;
            } else {
                $totalisatorAwal = $prevTotalisatorAkhir;
                $stokAwal = $prevStokAkhir;
            }

            // 2. Snapshot Harga (jika belum ada atau 0)
            $hargaJual = floatval($report->harga_jual_snapshot ?? 0);
            $hargaBeli = floatval($report->harga_beli_snapshot ?? 0);

            if ($hargaJual <= 0 || $hargaBeli <= 0) {
                $price = Price::where('shop_id', $shopId)
                    ->where('effective_at', '<=', Carbon::parse($reportDate)->endOfDay())
                    ->orderBy('effective_at', 'desc')
                    ->first();

                if (!$price) {
                    $price = Price::whereNull('shop_id')
                        ->where('effective_at', '<=', Carbon::parse($reportDate)->endOfDay())
                        ->orderBy('effective_at', 'desc')
                        ->first();
                }

                if ($price) {
                    $hargaJual = $hargaJual > 0 ? $hargaJual : floatval($price->harga_jual);
                    $hargaBeli = $hargaBeli > 0 ? $hargaBeli : floatval($price->harga_beli);
                }
            }

            // Temporarily set attributes on the model to calculate selisih_setoran
            $report->totalisator_awal = $totalisatorAwal;
            $report->stok_awal = $stokAwal;
            $report->harga_jual_snapshot = $hargaJual;
            $report->harga_beli_snapshot = $hargaBeli;

            // 3. Selisih Setoran & Running Balance
            // selisih_setoran = disetorkan - pendapatan_akhir
            // running_balance = running_balance_sebelumnya + selisih_setoran
            $selisihSetoran = $report->selisih_setoran;
            $runningBalance = round($runningBalance + $selisihSetoran, 0);

            // 4. Update data ke database
            DB::table('daily_reports')
                ->where('id', $report->id)
                ->update([
                    'totalisator_awal'       => $totalisatorAwal,
                    'stok_awal'              => $stokAwal,
                    'harga_jual_snapshot'    => $hargaJual,
                    'harga_beli_snapshot'    => $hargaBeli,
                    'running_balance_setoran' => $runningBalance,
                    'updated_at'             => now(),
                ]);

            // Untuk hari berikutnya
            $prevTotalisatorAkhir = floatval($report->totalisator_akhir);
            $prevStokAkhir = floatval($report->stok_akhir_aktual);
        }

        DailyReport::clearInternalCache();
    }
}

