<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Shop;
use App\Models\User;
use App\Models\Price;
use App\Models\Operator;
use App\Models\Corporation;
use App\Models\DailyReport;
use App\Models\Spending;
use App\Models\SpendingCategory;
use App\Models\TestPump;
use App\Models\Incoming;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\PayrollSystem;
use App\Models\BusinessRule;
use App\Models\BusinessRuleVersion;
use App\Services\DailyReportCalculationService;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

class KemutugMarch2026VerificationTest extends TestCase
{
    protected Shop $shop;
    protected User $user;
    protected Operator $operatorDendi;
    protected Operator $operatorWawan;
    protected Price $price;
    protected PayrollSystem $payrollSystem;
    protected Purchase $purchase;
    protected array $dataset;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Bersihkan database sqlite untuk isolasi test
        DailyReport::clearInternalCache();
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table('spendings')->delete();
        DB::table('incomings')->delete();
        DB::table('test_pumps')->delete();
        DB::table('daily_reports')->delete();
        DB::table('payroll_daily_splits')->delete();
        DB::table('payroll_details')->delete();
        DB::table('payroll_periods')->delete();
        DB::table('payroll_systems')->delete();
        DB::table('purchases')->delete();
        DB::table('suppliers')->delete();
        DB::table('prices')->delete();
        DB::table('operators')->delete();
        DB::table('users')->delete();
        DB::table('shops')->delete();
        DB::table('corporations')->delete();
        DB::table('spending_categories')->delete();
        DB::statement('PRAGMA foreign_keys = ON');

        // 2. Seed business rules
        $this->seed(\Database\Seeders\BusinessRuleSeeder::class);

        // 3. User & Corporation
        $this->user = User::create([
            'name'     => 'Admin Test',
            'email'    => 'admin@test.pertashop.com',
            'password' => bcrypt('secret'),
        ]);

        $corp = Corporation::create([
            'nama'   => 'KPRI KOKARNABA',
            'alamat' => 'Baturraden',
        ]);

        // 4. Shop Kemutug
        $this->shop = Shop::create([
            'nama'                     => 'Pertashop Kemutug Lor',
            'kode'                     => '4P.53143',
            'corporation_id'           => $corp->id,
            'alamat'                   => 'Kemutug Lor, Baturraden',
            'skala'                    => 21.49, // Koefisien tangki cm -> liter
            'totalisator_awal'         => 498129.800,
            'stik_awal'                => 70.4,
            'modal_awal'               => 470490000,
            'uang_belum_disetor_awal'  => -26020818,
        ]);

        $supplier = Supplier::create([
            'nama'   => 'PT Pertamina Patra Niaga',
            'alamat' => 'Cilacap',
        ]);

        $this->purchase = Purchase::create([
            'no_so'       => 'SO-2026-001',
            'shop_id'     => $this->shop->id,
            'supplier_id' => $supplier->id,
            'volume'      => 30000,
            'total_bayar' => 30000 * 11276.08,
        ]);

        // 5. Operators
        $userDendi = User::create([
            'name'     => 'Dendi Supriyatno',
            'email'    => 'dendi@test.com',
            'password' => bcrypt('secret'),
        ]);
        $this->operatorDendi = Operator::create([
            'user_id' => $userDendi->id,
            'shop_id' => $this->shop->id,
            'nik'     => 'OP-01',
        ]);

        $userWawan = User::create([
            'name'     => 'Wawan',
            'email'    => 'wawan@test.com',
            'password' => bcrypt('secret'),
        ]);
        $this->operatorWawan = Operator::create([
            'user_id' => $userWawan->id,
            'shop_id' => $this->shop->id,
            'nik'     => 'OP-02',
        ]);

        // 6. Prices (effective_at snapshot per tanggal efektif)
        $this->price = Price::create([
            'shop_id'      => $this->shop->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        // 7. Spending Categories
        $cats = [
            1  => 'Ongkos Bongkar',
            2  => 'Biaya Transfer',
            3  => 'Fotocopy ATK',
            4  => 'Listrik',
            5  => 'Air Bersih',
            6  => 'Cashback',
            7  => 'Internet',
            99 => 'Lain-lain',
        ];
        foreach ($cats as $id => $nama) {
            SpendingCategory::create(['id' => $id, 'nama' => $nama]);
        }

        // 8. Payroll System
        $this->payrollSystem = PayrollSystem::create([
            'nama_sistem'           => 'Skema Komisi Kemutug',
            'shop_id'               => $this->shop->id,
            'rate_per_liter'        => 200,
            'komisi_per_liter'      => 200,
            'tipe_skema'            => 'komisi_murni',
            'ada_rate_per_liter'    => true,
            'ada_gaji_pokok'        => false,
            'nominal_gaji_pokok'    => 0,
            'perlakuan_losses_gain' => 'losses_only',
            'metode_split'          => 'per_hari_penuh',
        ]);

        // 9. Load dataset
        $datasetPath = base_path('tests/kemutug_maret_2026_dataset.json');
        $this->dataset = json_decode(file_get_contents($datasetPath), true);

        // 10. Seed daily reports
        $this->seedDatasetReports();
    }

    protected function seedDatasetReports(): void
    {
        foreach ($this->dataset['daily_reports'] as $row) {
            $tgl = str_pad($row['tgl'], 2, '0', STR_PAD_LEFT);
            $dateStr = "2026-03-{$tgl}";
            $opId = ($row['operator'] === 'Wawan') ? $this->operatorWawan->id : $this->operatorDendi->id;

            $setoranTotal = floatval(($row['setoran_bri'] ?? 0) + ($row['setoran_qris'] ?? 0) + ($row['setoran_transfer'] ?? 0));

            $report = DailyReport::create([
                'shop_id'             => $this->shop->id,
                'operator_id'         => $opId,
                'price_id'            => $this->price->id,
                'totalisator_awal'    => $row['totalisator_awal'],
                'totalisator_akhir'   => $row['totalisator_akhir'],
                'stik_akhir'          => $row['stok_aktual_cm'],
                'stok_awal'           => $row['stok_awal'],
                'test_pump_volume'    => $row['test_pump_vol'],
                'penerimaan_volume'   => $row['terima_bbm'],
                'bbm_keluar_lain'     => $row['kirim_kemutug_vol'] ?? 0,
                'disetorkan'          => $setoranTotal,
                'diverifikasi'        => true,
                'setor_tunai'         => floatval($row['setoran_bri'] ?? 0),
                'setor_qris'          => floatval($row['setoran_qris'] ?? 0),
                'setor_transfer'      => floatval($row['setoran_transfer'] ?? 0),
                'harga_jual_snapshot' => 12200,
                'harga_beli_snapshot' => 11276.08,
                'report_date'         => $dateStr,
                'created_at'          => "{$dateStr} 20:00:00",
                'updated_at'          => "{$dateStr} 20:00:00",
            ]);

            // Test pump
            if ($row['test_pump_vol'] > 0) {
                TestPump::create([
                    'daily_report_id'   => $report->id,
                    'shop_id'           => $this->shop->id,
                    'operator_id'       => $opId,
                    'totalisator_awal'  => $row['totalisator_awal'],
                    'totalisator_akhir' => $row['totalisator_awal'] + $row['test_pump_vol'],
                    'volume'            => $row['test_pump_vol'],
                    'created_at'        => "{$dateStr} 10:00:00",
                ]);
            }

            // Incoming
            if ($row['terima_bbm'] > 0) {
                Incoming::create([
                    'daily_report_id' => $report->id,
                    'purchase_id'     => $this->purchase->id,
                    'shop_id'         => $this->shop->id,
                    'operator_id'     => $opId,
                    'volume'          => $row['terima_bbm'],
                    'sopir'           => 'Supir Tangki',
                    'no_polisi'       => 'R-1234-AA',
                    'stik_awal'       => 0,
                    'stik_akhir'      => 0,
                    'created_at'      => "{$dateStr} 12:00:00",
                ]);
            }

            // Spendings
            $spendingMappings = [
                1 => $row['ongkos_bongkar'] ?? 0,
                2 => $row['biaya_transfer'] ?? 0,
                3 => $row['fotocopy_atk'] ?? 0,
                4 => $row['listrik'] ?? 0,
                5 => $row['air_bersih'] ?? 0,
                6 => $row['cashback'] ?? 0,
                7 => $row['internet'] ?? 0,
                99 => $row['lain_lain'] ?? 0,
            ];

            foreach ($spendingMappings as $catId => $amt) {
                if ($amt > 0) {
                    Spending::create([
                        'daily_report_id'      => $report->id,
                        'spending_category_id' => $catId,
                        'shop_id'              => $this->shop->id,
                        'operator_id'          => $opId,
                        'jumlah'               => $amt,
                        'keterangan'           => ($catId == 99) ? ($row['keterangan_lain'] ?: 'Lain-lain') : '-',
                        'created_at'           => "{$dateStr} 18:00:00",
                    ]);
                }
            }
        }

        // Lakukan rekalkulasi berantai awal dengan saldo awal Maret 2026: -26020818
        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-01'),
            -26020818,
            498129.8,
            1512.896
        );
    }

    /**
     * Uji 1: Verifikasi kecocokan seluruh angka Maret 2026 Kemutug dengan Bagian 6 Spesifikasi
     */
    public function test_maret_2026_kemutug_daily_and_monthly_totals_match_specification(): void
    {
        $reports = DailyReport::where('shop_id', $this->shop->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertCount(31, $reports);

        // --- Verifikasi Harian Spesifik Sesuai Bagian 6 ---

        // Tgl 1: totalisator_awal 498129.8, akhir 498926.81, stok aktual 125.2 cm
        // -> penjualan_aktual_volume 797.01 (Rp9.723.522)
        $day1 = $reports->firstWhere('report_date', Carbon::parse('2026-03-01'));
        $this->assertEqualsWithDelta(498129.800, $day1->totalisator_awal, 0.001);
        $this->assertEqualsWithDelta(498926.810, $day1->totalisator_akhir, 0.001);
        $this->assertEqualsWithDelta(797.010, $day1->volume_penjualan_aktual, 0.001);
        $this->assertEquals(9723522, $day1->rupiah_penjualan_aktual);
        $this->assertEqualsWithDelta(2690.548, $day1->stok_akhir_aktual, 0.001);

        // Tgl 2: totalisator_awal 498926.81, akhir 500334.02
        // -> penjualan_aktual_volume 1407.21 (Rp17.167.962)
        $day2 = $reports->firstWhere('report_date', Carbon::parse('2026-03-02'));
        $this->assertEqualsWithDelta(498926.810, $day2->totalisator_awal, 0.001);
        $this->assertEqualsWithDelta(500334.020, $day2->totalisator_akhir, 0.001);
        $this->assertEqualsWithDelta(1407.210, $day2->volume_penjualan_aktual, 0.001);
        $this->assertEquals(17167962, $day2->rupiah_penjualan_aktual);

        // Tgl 7: ada test_pump 10 liter (Rp122.000)
        $day7 = $reports->firstWhere('report_date', Carbon::parse('2026-03-07'));
        $this->assertEqualsWithDelta(10.0, $day7->test_pump, 0.001);
        $this->assertEqualsWithDelta(771.640, $day7->volume_penjualan_aktual, 0.001);
        $this->assertEquals(9414008, $day7->rupiah_penjualan_aktual);

        // --- Verifikasi Akumulasi Bulanan Sesuai Bagian 6 Spesifikasi ---
        $totalVolAktual       = $reports->sum('volume_penjualan_aktual');
        $totalRpAktual        = $reports->sum('rupiah_penjualan_aktual');
        $totalLossesGainLiter = $reports->sum('losses_gain');
        $totalLossesGainRp    = $reports->sum('losses_gain_rupiah');
        $totalPengeluaran     = $reports->sum('pengeluaran');
        $totalSetoran         = $reports->sum('disetorkan');
        $saldoAkhirBelumSetor = $reports->last()->running_balance_setoran;
        $totalOmsetPlusLosses = $reports->sum('omset_plus_losses');

        // Target Spesifikasi Bagian 6:
        // 1. penjualan_aktual_volume total = 23584.93 liter
        $this->assertEqualsWithDelta(23584.930, $totalVolAktual, 0.01, 'Total Volume Penjualan Aktual');

        // 2. penjualan_aktual_rupiah total = 287.736.146
        $this->assertEqualsWithDelta(287736146, $totalRpAktual, 1.0, 'Total Rupiah Penjualan Aktual');

        // 3. losses_gain_liter total (net) = -355.613
        $this->assertEqualsWithDelta(-355.613, $totalLossesGainLiter, 0.01, 'Total Net Losses/Gain Liter');

        // 4. losses_gain_rupiah total (net) = -4.009.921
        $this->assertEqualsWithDelta(-4009921, $totalLossesGainRp, 2.0, 'Total Net Losses/Gain Rupiah');

        // 5. total_pengeluaran = 5.388.900
        $this->assertEquals(5388900, $totalPengeluaran, 'Total Pengeluaran');

        // 6. total setoran (BRI+QRIS+Transfer) = 276.754.938
        $this->assertEquals(276754938, $totalSetoran, 'Total Setoran');

        // 7. total_uang_belum_disetor akhir = -31.613.126
        $this->assertEquals(-31613126, $saldoAkhirBelumSetor, 'Saldo Akhir Belum Disetor');

        // 8. omset_plus_losses total = 23217.367 liter
        $this->assertEqualsWithDelta(23217.367, $totalOmsetPlusLosses, 0.01, 'Total Omset Plus Losses');

        // --- Verifikasi Penggajian Operator via PayrollCalculationService ---
        $payrollService = app(PayrollCalculationService::class);
        $payrollPeriod = $payrollService->generate($this->shop->id, 3, 2026, $this->user->id);

        $this->assertEqualsWithDelta(23217.367, $payrollPeriod->total_penjualan_liter, 0.01);

        $detailDendi = $payrollPeriod->details->firstWhere('operator_id', $this->operatorDendi->id);
        $detailWawan = $payrollPeriod->details->firstWhere('operator_id', $this->operatorWawan->id);

        $this->assertNotNull($detailDendi);
        $this->assertNotNull($detailWawan);

        // Dendi: 21046.648 L x 200 = 4.209.330
        $this->assertEqualsWithDelta(21046.648, $detailDendi->liter_bagian, 0.01);
        $this->assertEquals(4209330, $detailDendi->gaji_variable);

        // Wawan: 2170.719 L x 200 = 434.144
        $this->assertEqualsWithDelta(2170.719, $detailWawan->liter_bagian, 0.01);
        $this->assertEquals(434144, $detailWawan->gaji_variable);

        // Total Gaji Operator = 4.209.330 + 434.144 = 4.643.474 (selisih 1 Rp akibat pembulatan per operator)
        $totalGaji = $payrollPeriod->details->sum('gaji_variable');
        $this->assertEqualsWithDelta(4643473, $totalGaji, 1.0, 'Total Gaji Operator');
    }

    /**
     * Uji 2: Rekalkulasi berantai (cascade recalculation) saat hari ke-10 diedit.
     * Mengubah data hari 10 wajib mengalirkan perubahan ke hari 11 hingga 31
     * untuk totalisator_awal, stok_awal, dan saldo belum disetor.
     */
    public function test_cascade_recalculation_when_day_10_is_edited(): void
    {
        $day10 = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-10')
            ->firstOrFail();

        $day11Before = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-11')
            ->firstOrFail();

        $day31Before = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-31')
            ->firstOrFail();

        $originalDay10Akhir = $day10->totalisator_akhir;
        $originalDay10StikAkhir = $day10->stik_akhir;
        $originalDay10RunningBalance = $day10->running_balance_setoran;
        $originalDay31RunningBalance = $day31Before->running_balance_setoran;

        // --- Skenario A: Edit Totalisator Akhir Hari 10 (+50 L) ---
        // Penjualan hari 10 bertambah 50 L (50 x 12.200 = Rp 610.000).
        // Selisih setoran hari 10 berkurang Rp 610.000.
        // Totalisator awal hari 11 harus ikut naik 50 L.
        $newTotalisatorAkhirDay10 = $originalDay10Akhir + 50.000;
        DB::table('daily_reports')->where('id', $day10->id)->update([
            'totalisator_akhir' => $newTotalisatorAkhirDay10,
        ]);

        // Jalankan rekalkulasi berantai dari hari 10
        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-10'),
            -26020818,
            498129.8,
            1512.896
        );

        $day10After = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-10')
            ->firstOrFail();

        $day11After = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-11')
            ->firstOrFail();

        // 1. Totalisator Awal hari 11 HARUS mengikuti Totalisator Akhir hari 10 yang baru
        $this->assertEqualsWithDelta(
            $newTotalisatorAkhirDay10,
            $day11After->totalisator_awal,
            0.001,
            'Totalisator awal Hari 11 harus ter-update mengikuti Hari 10'
        );

        // 2. Running balance Hari 10 berkurang tepat Rp 610.000 (kurang setor bertambah)
        $this->assertEquals(
            $originalDay10RunningBalance - 610000,
            $day10After->running_balance_setoran,
            'Running balance hari 10 harus ter-update mengikuti perubahan totalisator'
        );

        // Kembalikan totalisator hari 10 ke semula
        DB::table('daily_reports')->where('id', $day10->id)->update([
            'totalisator_akhir' => $originalDay10Akhir,
        ]);

        // --- Skenario B: Edit Pengeluaran Hari 10 (Tambah biaya ATK Rp 100.000) ---
        // Pengeluaran bertambah Rp 100.000 -> Pendapatan kas berkurang Rp 100.000
        // -> Selisih setoran naik Rp 100.000 -> Running balance hari 10 s.d. 31 naik Rp 100.000
        Spending::create([
            'daily_report_id'      => $day10->id,
            'spending_category_id' => 3, // ATK
            'shop_id'              => $this->shop->id,
            'operator_id'          => $this->operatorDendi->id,
            'jumlah'               => 100000,
            'keterangan'           => 'Tambahan ATK Kasbon',
            'created_at'           => '2026-03-10 18:00:00',
        ]);

        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-10'),
            -26020818,
            498129.8,
            1512.896
        );

        $day10AfterPengeluaran = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-10')
            ->firstOrFail();

        $day11AfterPengeluaran = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-11')
            ->firstOrFail();

        $day31AfterPengeluaran = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-31')
            ->firstOrFail();

        $this->assertEquals(
            $originalDay10RunningBalance + 100000,
            $day10AfterPengeluaran->running_balance_setoran,
            'Running balance hari 10 harus berantai ter-update (+100.000)'
        );

        $this->assertEquals(
            $originalDay31RunningBalance + 100000,
            $day31AfterPengeluaran->running_balance_setoran,
            'Running balance akhir bulan (hari 31) harus berantai ter-update (+100.000)'
        );

        // --- Skenario C: Edit Stok Aktual Akhir Hari 10 (+5 cm) ---
        // Naikkan stik akhir dari nilai lama sebesar 5 cm (5 cm * 21.49 = 107.45 Liter)
        $newStikAkhirDay10 = $originalDay10StikAkhir + 5.0;
        DB::table('daily_reports')->where('id', $day10->id)->update([
            'stik_akhir' => $newStikAkhirDay10,
        ]);

        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-10'),
            -26020818,
            498129.8,
            1512.896
        );

        $day11AfterStok = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-11')
            ->firstOrFail();

        $expectedStokAwalDay11 = round($newStikAkhirDay10 * 21.49, 3);
        $this->assertEqualsWithDelta(
            $expectedStokAwalDay11,
            $day11AfterStok->stok_awal,
            0.001,
            'Stok awal Hari 11 harus ter-update mengikuti Stok Aktual Akhir Hari 10'
        );
    }

    /**
     * Uji 3: Aturan "losses_only" merupakan konfigurasi fleksibel per gerai lewat business_rules
     */
    public function test_business_rule_perlakuan_losses_gain_per_gerai(): void
    {
        // 1. Secara default gerai Kemutug memakai losses_only:
        // Losses (-10 L) mengurangi volume, Gain (+10 L) diabaikan
        $vol1 = $this->payrollSystem->hitungVolumeDihitung(100.0, -10.0);
        $this->assertEqualsWithDelta(90.0, $vol1, 0.001);

        $vol2 = $this->payrollSystem->hitungVolumeDihitung(100.0, 10.0);
        $this->assertEqualsWithDelta(100.0, $vol2, 0.001);

        // 2. Ubah konfigurasi via PayrollSystem outlet menjadi 'losses_dan_gain'
        $this->payrollSystem->update(['perlakuan_losses_gain' => 'losses_dan_gain']);
        $vol3 = $this->payrollSystem->hitungVolumeDihitung(100.0, 10.0);
        $this->assertEqualsWithDelta(110.0, $vol3, 0.001);

        // 3. Ubah konfigurasi via BusinessRule terpusat dengan mapping per-gerai
        $rule = BusinessRule::where('code', 'PERLAKUAN_LOSSES_GAIN')->first();
        if ($rule) {
            BusinessRuleVersion::create([
                'business_rule_id' => $rule->id,
                'version_code'     => 'BR-LOSSGAIN-v2.0',
                'value_json'       => [
                    (string) $this->shop->id => 'abaikan_losses_gain',
                    'default'                => 'losses_only',
                ],
                'effective_from'   => '2026-01-01 00:00:00',
                'effective_until'  => null,
                'is_active'        => true,
                'created_by'       => $this->user->id,
                'change_reason'    => 'Uji override aturan bisnis per gerai',
            ]);

            // Pada abaikan_losses_gain, losses (-10) maupun gain (+10) tidak berpengaruh ke komisi
            $vol4 = $this->payrollSystem->hitungVolumeDihitung(100.0, -10.0);
            $this->assertEqualsWithDelta(100.0, $vol4, 0.001);

            $vol5 = $this->payrollSystem->hitungVolumeDihitung(100.0, 10.0);
            $this->assertEqualsWithDelta(100.0, $vol5, 0.001);
        }
    }
}
