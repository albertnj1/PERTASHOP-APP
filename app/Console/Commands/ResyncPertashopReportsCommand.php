<?php

namespace App\Console\Commands;

use App\Models\DailyReport;
use App\Models\MonthlyReport;
use App\Models\Shop;
use App\Services\MonthlyReportCalculationService;
use Illuminate\Console\Command;

class ResyncPertashopReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pertashop:resync-reports {shop_id? : ID Toko yang ingin disinkronkan (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menormalkan data laporan harian dan menghitung ulang laporan bulanan serta modal agar 100% presisi dengan formula Excel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shopId = $this->argument('shop_id');
        $shops = $shopId ? Shop::where('id', $shopId)->get() : Shop::all();

        if ($shops->isEmpty()) {
            $this->error("Toko tidak ditemukan.");
            return 1;
        }

        $this->info("=== MEMULAI RE-SINKRONISASI LAPORAN PERTASHOP ===");

        foreach ($shops as $shop) {
            $this->newLine();
            $this->info("Memproses Toko: [{$shop->id}] {$shop->nama} (Kode: {$shop->kode})");

            $dailyReports = DailyReport::where('shop_id', $shop->id)
                ->orderBy('created_at', 'asc')
                ->get();

            $totalDaily = $dailyReports->count();
            $this->line("  Ditemukan {$totalDaily} laporan harian.");

            $correctedCount = 0;
            $skala = floatval($shop->skala > 0 ? $shop->skala : 21.0);

            foreach ($dailyReports as $dr) {
                $changed = false;
                $stik = floatval($dr->stik_akhir);

                // Normalisasi jika stik_akhir tersimpan dalam satuan liter (> 200 L)
                // Konversikan kembali ke CM agar data mentah stik murni CM
                if ($stik > 200) {
                    $cm = round($stik / $skala, 2);
                    $dr->stik_akhir = $cm;
                    $dr->stok_awal = $dr->stok_awal ?? ($stik);
                    $changed = true;
                    $correctedCount++;
                }

                // Pastikan totalisator_awal dan stok_awal terisi jika sebelumnya kosong
                if ($dr->totalisator_awal === null) {
                    $prev = DailyReport::where('shop_id', $shop->id)
                        ->where('created_at', '<', $dr->created_at)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($prev) {
                        $dr->totalisator_awal = $prev->totalisator_akhir;
                        $dr->stok_awal = $prev->stok_akhir_aktual;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $dr->saveQuietly();
                }
            }

            if ($correctedCount > 0) {
                $this->warn("  {$correctedCount} data stik/stok harian berhasil dinormalkan.");
            }

            // Sinkronisasi bulanan & akumulasi modal
            $this->line("  Menjalankan sinkronisasi berantai laporan bulanan & rekap modal...");
            try {
                MonthlyReportCalculationService::syncAndRecalculate($shop);
                $this->info("  Kalkulasi bulanan & posisi modal berhasil disinkronkan.");
            } catch (\Throwable $e) {
                $this->error("  Gagal sinkronisasi bulanan: " . $e->getMessage());
            }

            // Tampilkan cuplikan ringkasan bulanan
            $mReports = MonthlyReport::where('shop_id', $shop->id)
                ->orderBy('bulan_tahun', 'asc')
                ->get();

            if ($mReports->isNotEmpty()) {
                $rows = [];
                foreach ($mReports as $mr) {
                    $reportData = MonthlyReportCalculationService::buildReportData($mr);
                    $rows[] = [
                        $mr->bulan_tahun,
                        number_format($reportData['grand_total_laba_kotor'], 0, ',', '.'),
                        number_format($reportData['total_biaya'], 0, ',', '.'),
                        number_format($reportData['laba_bersih'], 0, ',', '.'),
                        number_format($reportData['total_saldo_akhir_modal'], 0, ',', '.'),
                    ];
                }
                $this->table(['Bulan', 'Laba Kotor (Rp)', 'Total Biaya (Rp)', 'Laba Bersih (Rp)', 'Posisi Modal (Rp)'], $rows);
            }
        }

        $this->newLine();
        $this->info("=== RE-SINKRONISASI SELESAI DENGAN SUKSES ===");
        return 0;
    }
}
