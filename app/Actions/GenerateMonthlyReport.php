<?php

namespace App\Actions;

use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\Incoming;
use App\Models\MonthlyReport;
use App\Models\CapitalRecap;
use App\Models\SpendingCategory;
use Illuminate\Support\Carbon;

class GenerateMonthlyReport
{
    /**
     * Generate atau update MonthlyReport berdasarkan data DailyReports dari database.
     * 
     * @param int $shopId
     * @param string $yearMonth Format YYYY-MM (e.g. "2026-07")
     * @return MonthlyReport
     */
    public function handle(int $shopId, string $yearMonth): MonthlyReport
    {
        $date = Carbon::createFromFormat('Y-m', $yearMonth);
        $year = (int) $date->format('Y');
        $month = (int) $date->format('m');
        $bulanTahunText = $date->translatedFormat('F Y');

        $shop = Shop::findOrFail($shopId);

        // Fetch daily reports for this shop and month
        $dailyReports = DailyReport::with(['spendings.category', 'incomings', 'price'])
            ->where('shop_id', $shopId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($dailyReports->isEmpty()) {
            throw new \Exception("Tidak ada data Laporan Harian untuk {$shop->nama} pada bulan {$bulanTahunText}.");
        }

        $categories = SpendingCategory::all()->keyBy('id');

        $dataParsed = [];
        $grandTotals = [
            'total_volume'           => 0,
            'total_rupiah_penjualan' => 0,
            'total_pengeluaran'      => 0,
            'total_pendapatan'       => 0,
            'total_setor_tunai'      => 0,
            'total_setor_qris'       => 0,
            'total_setor_transfer'   => 0,
            'total_setor_kolektan'   => 0,
            'total_disetorkan'       => 0,
            'total_losses_gain'      => 0,
            'pengeluaran_per_kategori' => [],
        ];

        foreach ($categories as $catId => $cat) {
            $grandTotals['pengeluaran_per_kategori'][$catId] = 0;
        }

        $firstReport = $dailyReports->first();
        $lastReport = $dailyReports->last();

        foreach ($dailyReports as $dr) {
            $vol = (float) $dr->volume_penjualan_teoritis;
            $rupiahPenjualan = (float) $dr->rupiah_penjualan;
            $pengeluaran = (float) $dr->total_spendings;
            $pendapatan = (float) $dr->pendapatan;

            $setorTunai = (float) $dr->setor_tunai;
            $setorQris = (float) $dr->setor_qris;
            $setorTransfer = (float) $dr->setor_transfer;
            $setorKolektan = (float) $dr->setor_kolektan;
            $disetorkan = (float) $dr->disetorkan;
            $losses = (float) $dr->losses_gain;

            // Grand totals accumulation
            $grandTotals['total_volume'] += $vol;
            $grandTotals['total_rupiah_penjualan'] += $rupiahPenjualan;
            $grandTotals['total_pengeluaran'] += $pengeluaran;
            $grandTotals['total_pendapatan'] += $pendapatan;
            $grandTotals['total_setor_tunai'] += $setorTunai;
            $grandTotals['total_setor_qris'] += $setorQris;
            $grandTotals['total_setor_transfer'] += $setorTransfer;
            $grandTotals['total_setor_kolektan'] += $setorKolektan;
            $grandTotals['total_disetorkan'] += $disetorkan;
            $grandTotals['total_losses_gain'] += $losses;

            $spendingMap = [];
            foreach ($dr->spendings as $sp) {
                $amt = (float) $sp->jumlah;
                $spendingMap[$sp->spending_category_id] = $amt;
                if (isset($grandTotals['pengeluaran_per_kategori'][$sp->spending_category_id])) {
                    $grandTotals['pengeluaran_per_kategori'][$sp->spending_category_id] += $amt;
                }
            }

            $dataParsed[] = [
                'tanggal'                  => Carbon::parse($dr->created_at)->format('Y-m-d'),
                'totalisator_awal'         => (float) $dr->totalisator_awal,
                'totalisator_akhir'        => (float) $dr->totalisator_akhir,
                'volume_penjualan_teoritis'=> $vol,
                'harga_jual'               => (float) ($dr->price?->harga_jual ?? 0),
                'rupiah_penjualan'         => $rupiahPenjualan,
                'stik_awal'                => (float) $dr->stik_awal,
                'stok_awal'                => (float) $dr->stok_awal,
                'penerimaan_volume'        => (float) $dr->penerimaan_volume,
                'stik_akhir'               => (float) $dr->stik_akhir,
                'stok_akhir_aktual'        => (float) $dr->stok_akhir_aktual,
                'stok_akhir_teoritis'      => (float) $dr->stok_akhir_teoritis,
                'losses_gain'              => $losses,
                'total_pengeluaran'        => $pengeluaran,
                'pendapatan'               => $pendapatan,
                'setor_tunai'              => $setorTunai,
                'setor_qris'               => $setorQris,
                'setor_transfer'           => $setorTransfer,
                'setor_kolektan'           => $setorKolektan,
                'disetorkan'               => $disetorkan,
                'selisih_setoran'          => (float) $dr->selisih_setoran,
                'belum_disetorkan'         => (float) $dr->belum_disetorkan,
                'spendings'                => $spendingMap,
            ];
        }

        // Fetch BBM Datang (Incomings)
        $incomings = Incoming::where('shop_id', $shopId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'asc')
            ->get();

        $bbmDatang = $incomings->map(fn ($inc) => [
            'tanggal'        => Carbon::parse($inc->created_at)->format('Y-m-d'),
            'no_polisi'      => $inc->no_polisi,
            'sopir'          => $inc->sopir,
            'volume'         => (float) $inc->volume,
            'penerimaan_real'=> (float) ($inc->penerimaan_real ?? $inc->volume),
            'losses_gain'    => (float) $inc->losses_gain,
        ])->toArray();

        // Capital Recap positions
        $capitalRecap = CapitalRecap::where('shop_id', $shopId)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->first();

        $saldoAwalModal = $capitalRecap ? (float) $capitalRecap->saldo_awal : (float) $shop->modal_awal;
        $saldoAkhirModal = $capitalRecap ? (float) $capitalRecap->saldo_akhir : (float) $shop->modal_awal;

        return MonthlyReport::updateOrCreate(
            [
                'shop_id'     => $shopId,
                'bulan_tahun' => $bulanTahunText,
            ],
            [
                'totalisator_awal'  => (float) $firstReport->totalisator_awal,
                'totalisator_akhir' => (float) $lastReport->totalisator_akhir,
                'grand_totals'      => $grandTotals,
                'data_parsed'       => $dataParsed,
                'bbm_datang'        => $bbmDatang,
                'saldo_awal_modal'  => $saldoAwalModal,
                'saldo_akhir_modal' => $saldoAkhirModal,
                'kas_kecil'         => $grandTotals['total_disetorkan'],
            ]
        );
    }
}
