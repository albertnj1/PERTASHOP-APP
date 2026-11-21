<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\DailyReport;
use App\Models\Incoming;
use App\Models\Operator;
use App\Models\PayrollSystem;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\Shop;
use App\Models\Spending;
use App\Models\SpendingCategory;
use App\Models\Supplier;
use App\Models\TestPump;
use App\Models\User;
use App\Services\DailyReportCalculationService;
use App\Services\PertashopDailyRecapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KemutugMarch2026ExactCellVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected Shop $shop;
    protected User $superAdmin;
    protected Operator $operatorDendi;
    protected Operator $operatorWawan;
    protected Price $price;
    protected Purchase $purchase;
    protected array $dataset;

    protected function setUp(): void
    {
        parent::setUp();

        DailyReport::clearInternalCache();
        Schema::disableForeignKeyConstraints();
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
        Schema::enableForeignKeyConstraints();

        $this->seed(\Database\Seeders\BusinessRuleSeeder::class);

        // 1. User Super Admin
        $this->superAdmin = User::create([
            'name'     => 'Super Admin Rekap',
            'email'    => 'admin.rekap@pertashop.test',
            'role'     => 'super-admin',
            'password' => bcrypt('password123'),
        ]);

        // 2. Corporation & Shop
        $corp = Corporation::create([
            'nama'   => 'KPRI KOKARNABA BATURRADEN',
            'alamat' => 'Baturraden, Banyumas',
        ]);

        $this->shop = Shop::create([
            'nama'                     => 'Pertashop Kemutug Lor',
            'kode'                     => '4P.53143',
            'corporation_id'           => $corp->id,
            'alamat'                   => 'Kemutug Lor, Baturraden',
            'skala'                    => 21.49, // Koefisien tangki 21.49 L/cm
            'totalisator_awal'         => 498129.800,
            'stik_awal'                => 70.4,
            'modal_awal'               => 470490000,
            'uang_belum_disetor_awal'  => -26020818,
            'is_active'                => true,
        ]);

        // 3. Supplier & Purchase
        $supplier = Supplier::create([
            'nama'   => 'PT Pertamina Patra Niaga',
            'alamat' => 'Cilacap',
        ]);

        $this->purchase = Purchase::create([
            'no_so'       => 'SO-2026-KMT-03',
            'shop_id'     => $this->shop->id,
            'supplier_id' => $supplier->id,
            'volume'      => 30000,
            'total_bayar' => 30000 * 11276.08,
        ]);

        // 4. Operators
        $userDendi = User::create([
            'name'     => 'Dendi Supriyatno',
            'email'    => 'dendi.kmt@pertashop.test',
            'role'     => 'operator',
            'password' => bcrypt('secret'),
        ]);
        $this->operatorDendi = Operator::create([
            'user_id' => $userDendi->id,
            'shop_id' => $this->shop->id,
            'nik'     => 'OP-DENDI',
        ]);

        $userWawan = User::create([
            'name'     => 'Wawan',
            'email'    => 'wawan.kmt@pertashop.test',
            'role'     => 'operator',
            'password' => bcrypt('secret'),
        ]);
        $this->operatorWawan = Operator::create([
            'user_id' => $userWawan->id,
            'shop_id' => $this->shop->id,
            'nik'     => 'OP-WAWAN',
        ]);

        // 5. Prices
        $this->price = Price::create([
            'shop_id'      => $this->shop->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        // 6. Payroll System
        PayrollSystem::create([
            'shop_id'                  => $this->shop->id,
            'nama_sistem'              => 'Sistem Komisi Kemutug',
            'ada_rate_per_liter'       => true,
            'rate_per_liter'           => 200,
            'ada_gaji_pokok'           => false,
            'nominal_gaji_pokok'       => 0,
            'potongan_per_hari_alpha'  => 0,
            'perlakuan_losses_gain'    => 'losses_only',
            'metode_split'             => 'per_hari_penuh',
            'aktif'                    => true,
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
        foreach ($cats as $id => $name) {
            SpendingCategory::create(['id' => $id, 'nama' => $name]);
        }

        // 8. Load Dataset acuan nilai sel tersimpan Excel
        $datasetPath = base_path('tests/kemutug_maret_2026_expected_dari_excel.json');
        if (!file_exists($datasetPath)) {
            $datasetPath = base_path('tests/kemutug_maret_2026_dataset.json');
        }
        $this->dataset = json_decode(file_get_contents($datasetPath), true);

        // 9. Seed 31 Days
        $this->seedDailyReportsFromDataset();
    }

    protected function seedDailyReportsFromDataset(): void
    {
        foreach ($this->dataset['daily_reports'] as $row) {
            $day = $row['tgl'];
            $dateStr = sprintf('2026-03-%02d', $day);
            $opId = ($row['operator'] === 'Wawan') ? $this->operatorWawan->id : $this->operatorDendi->id;

            $disetorkan = ($row['setoran_bri'] ?? 0) + ($row['setoran_qris'] ?? 0) + ($row['setoran_transfer'] ?? 0);

            $report = DailyReport::create([
                'shop_id'             => $this->shop->id,
                'operator_id'         => $opId,
                'price_id'            => $this->price->id,
                'report_date'         => $dateStr,
                'totalisator_awal'    => $row['totalisator_awal'],
                'totalisator_akhir'   => $row['totalisator_akhir'],
                'stik_awal'           => 0,
                'stik_akhir'          => $row['stok_aktual_cm'],
                'stok_awal'           => $row['stok_awal'],
                'disetorkan'          => $disetorkan,
                'setor_tunai'         => $row['setoran_bri'] ?? 0,
                'setor_qris'          => $row['setoran_qris'] ?? 0,
                'setor_transfer'      => $row['setoran_transfer'] ?? 0,
                'test_pump_volume'    => $row['test_pump_vol'] ?? 0,
                'bbm_keluar_lain'     => $row['kirim_kemutug_vol'] ?? 0,
                'penerimaan_volume'   => $row['terima_bbm'] ?? 0,
                'harga_jual_snapshot' => 12200,
                'harga_beli_snapshot' => 11276.08,
                'status_lifecycle'    => 'approved',
                'created_at'          => "{$dateStr} 20:00:00",
                'updated_at'          => "{$dateStr} 20:00:00",
            ]);

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

            $spendings = [
                1 => $row['ongkos_bongkar'] ?? 0,
                2 => $row['biaya_transfer'] ?? 0,
                3 => $row['fotocopy_atk'] ?? 0,
                4 => $row['listrik'] ?? 0,
                5 => $row['air_bersih'] ?? 0,
                6 => $row['cashback'] ?? 0,
                7 => $row['internet'] ?? 0,
                99 => $row['lain_lain'] ?? 0,
            ];

            foreach ($spendings as $catId => $amt) {
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

        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-01'),
            -26020818,
            498129.8,
            1512.896
        );
    }

    /**
     * UJI Bagian 7.3: Verifikasi 713 Sel Harian (31 hari x 23 kolom HASIL)
     * Toleransi delta < 0.001 untuk angka volume dan < 1 rupiah untuk uang.
     * Status teks ("Pas", "Losses", "Gain") sama persis.
     */
    public function test_exact_713_daily_cells_match_excel_kemutug_maret_2026(): void
    {
        $recap = PertashopDailyRecapService::getMonthlyRecap($this->shop->id, 3, 2026);
        $rows = $recap['rows'];

        $this->assertCount(31, $rows, 'Harus ada tepat 31 hari laporan.');

        $expectedDatasetMap = collect($this->dataset['daily_reports'])->keyBy('tgl');

        $verifiedCellCount = 0;

        foreach ($rows as $day => $calculated) {
            $expected = $expectedDatasetMap->get($day);
            $this->assertNotNull($expected, "Data ekspektasi hari ke-{$day} harus ada.");

            // 1. Kolom D: Totalisator Awal
            $this->assertEqualsWithDelta($expected['totalisator_awal'], $calculated['D_totalisator_awal'], 0.000001, "Hari {$day} Kolom D (Totalisator Awal)");
            $verifiedCellCount++;

            // 2. Kolom F: Penjualan Teoritis Volume
            $this->assertEqualsWithDelta($expected['penjualan_teoritis_vol'], $calculated['F_penjualan_teoritis_vol'], 0.000001, "Hari {$day} Kolom F (Teoritis Vol)");
            $verifiedCellCount++;

            // 3. Kolom G: Penjualan Teoritis Rupiah
            $this->assertEqualsWithDelta($expected['penjualan_teoritis_rp'], $calculated['G_penjualan_teoritis_rp'], 0.000001, "Hari {$day} Kolom G (Teoritis Rp)");
            $verifiedCellCount++;

            // 4. Kolom I: Test Pump Rupiah
            $this->assertEqualsWithDelta($expected['test_pump_rp'], $calculated['I_test_pump_rp'], 0.000001, "Hari {$day} Kolom I (Test Pump Rp)");
            $verifiedCellCount++;

            // 5. Kolom K: Kirim Kemutug Rupiah
            $this->assertEqualsWithDelta($expected['kirim_kemutug_rp'], $calculated['K_bbm_keluar_lain_rp'], 0.000001, "Hari {$day} Kolom K (Kirim Rp)");
            $verifiedCellCount++;

            // 6. Kolom L: Penjualan Aktual Volume
            $this->assertEqualsWithDelta($expected['penjualan_aktual_vol'], $calculated['L_penjualan_aktual_vol'], 0.000001, "Hari {$day} Kolom L (Aktual Vol)");
            $verifiedCellCount++;

            // 7. Kolom M: Penjualan Aktual Rupiah
            $this->assertEqualsWithDelta($expected['penjualan_aktual_rp'], $calculated['M_penjualan_aktual_rp'], 0.000001, "Hari {$day} Kolom M (Aktual Rp)");
            $verifiedCellCount++;

            // 8. Kolom N: Stok Awal
            $this->assertEqualsWithDelta($expected['stok_awal'], $calculated['N_stok_awal'], 0.000001, "Hari {$day} Kolom N (Stok Awal)");
            $verifiedCellCount++;

            // 9. Kolom Q: Stok Teoritis
            $this->assertEqualsWithDelta($expected['stok_teoritis'], $calculated['Q_stok_teoritis'], 0.000001, "Hari {$day} Kolom Q (Stok Teoritis)");
            $verifiedCellCount++;

            // 10. Kolom S: Stok Aktual Volume
            $this->assertEqualsWithDelta($expected['stok_aktual_vol'], $calculated['S_stok_aktual_vol'], 0.000001, "Hari {$day} Kolom S (Stok Aktual Vol)");
            $verifiedCellCount++;

            // 11. Kolom T: Nominal Losses / Gain Liter
            $this->assertEqualsWithDelta($expected['losses_gain_liter'], $calculated['T_losses_gain_liter'], 0.000001, "Hari {$day} Kolom T (Losses/Gain L)");
            $verifiedCellCount++;

            // 12. Kolom U: Nominal Rupiah
            $this->assertEqualsWithDelta($expected['losses_gain_rp'], $calculated['U_losses_gain_rp'], 0.000001, "Hari {$day} Kolom U (Losses/Gain Rp)");
            $verifiedCellCount++;

            // 13. Kolom V: Losses / Gain Status
            $this->assertSame($expected['status_losses_gain'], $calculated['V_status_losses_gain'], "Hari {$day} Kolom V (Status)");
            $verifiedCellCount++;

            // 14. Kolom W: Stok Akhir
            $this->assertEqualsWithDelta($expected['stok_akhir'], $calculated['W_stok_akhir'], 0.000001, "Hari {$day} Kolom W (Stok Akhir)");
            $verifiedCellCount++;

            // 15. Kolom X: Jumlah Actual Penjualan
            $this->assertEqualsWithDelta($expected['penjualan_aktual_rp'], $calculated['X_jumlah_actual_penjualan'], 0.000001, "Hari {$day} Kolom X (Jumlah Aktual Penjualan)");
            $verifiedCellCount++;

            // 16. Kolom AG: Total Pengeluaran
            $this->assertEqualsWithDelta($expected['total_pengeluaran'], $calculated['AG_total_pengeluaran'], 0.000001, "Hari {$day} Kolom AG (Total Pengeluaran)");
            $verifiedCellCount++;

            // 17. Kolom AJ: Pendapatan Dikurangi Pengeluaran
            $this->assertEqualsWithDelta($expected['pendapatan_dikurangi_pengeluaran'], $calculated['AJ_pendapatan_minus_pengeluaran'], 0.000001, "Hari {$day} Kolom AJ (Pendapatan - Biaya)");
            $verifiedCellCount++;

            // 18. Kolom AO: Selisih Uang Yang Disetorkan dg Setoran
            $this->assertEqualsWithDelta($expected['selisih_setoran'], $calculated['AO_selisih_setoran'], 0.000001, "Hari {$day} Kolom AO (Selisih Setoran)");
            $verifiedCellCount++;

            // 19. Kolom AP: Total Uang Yang Belum Disetorkan
            $this->assertEqualsWithDelta($expected['total_uang_belum_disetor'], $calculated['AP_total_uang_belum_disetor'], 0.000001, "Hari {$day} Kolom AP (Uang Belum Disetor)");
            $verifiedCellCount++;

            // 20. Kolom AS: Omset Kotor (= L)
            $this->assertEqualsWithDelta($expected['penjualan_aktual_vol'], $calculated['AS_omset_kotor'], 0.000001, "Hari {$day} Kolom AS (Omset Kotor)");
            $verifiedCellCount++;

            // 21. Kolom AT: Status Gaji (= V)
            $this->assertSame($expected['status_losses_gain'], $calculated['AT_status_gaji'], "Hari {$day} Kolom AT (Status Gaji)");
            $verifiedCellCount++;

            // 22. Kolom AU: Losses / Gain Gaji (= T)
            $this->assertEqualsWithDelta($expected['losses_gain_liter'], $calculated['AU_losses_gain_gaji'], 0.000001, "Hari {$day} Kolom AU (Losses/Gain Gaji)");
            $verifiedCellCount++;

            // 23. Kolom AV: Omset + Losses
            $expectedAv = ($expected['status_losses_gain'] === 'Losses')
                ? ($expected['penjualan_aktual_vol'] + $expected['losses_gain_liter'])
                : $expected['penjualan_aktual_vol'];
            $this->assertEqualsWithDelta($expectedAv, $calculated['AV_omset_plus_losses'], 0.000001, "Hari {$day} Kolom AV (Omset + Losses)");
            $verifiedCellCount++;
        }

        // 31 hari x 23 kolom = 713 sel terverifikasi
        $this->assertEquals(713, $verifiedCellCount, 'Tepat 713 sel harian terverifikasi.');
    }

    /**
     * UJI Bagian 7.4: Verifikasi Seluruh Baris Rekap Bulanan (Baris 35, 36, 37, 38, 39)
     */
    public function test_monthly_recap_summary_rows_match_excel_specification(): void
    {
        $recap = PertashopDailyRecapService::getMonthlyRecap($this->shop->id, 3, 2026);
        $summary = $recap['summary'];
        $tot = $summary['row_total_35'];
        $avg = $summary['row_averages_closing_36_39'];

        $expectedSummary = $this->dataset['monthly_summary'];

        // F35: total penjualan teoritis volume = 23594.93
        $this->assertEqualsWithDelta($expectedSummary['F35_penjualan_teoritis_vol'], $tot['F_penjualan_teoritis_vol'], 0.000001);

        // G35: total penjualan teoritis rupiah = 287.858.146
        $this->assertEqualsWithDelta($expectedSummary['G35_penjualan_teoritis_rp'], $tot['G_penjualan_teoritis_rp'], 0.000001);

        // H35: total test pump vol = 10
        $this->assertEqualsWithDelta($expectedSummary['H35_test_pump_vol'], $tot['H_test_pump_vol'], 0.000001);

        // L35: total penjualan aktual volume = 23584.93
        $this->assertEqualsWithDelta($expectedSummary['L35_penjualan_aktual_vol'], $tot['L_penjualan_aktual_vol'], 0.000001);

        // M35: total penjualan aktual rupiah = 287.736.146
        $this->assertEqualsWithDelta($expectedSummary['M35_penjualan_aktual_rp'], $tot['M_penjualan_aktual_rp'], 0.000001);

        // T35: losses_gain bersih = -355.613
        $this->assertEqualsWithDelta($expectedSummary['T35_losses_gain_liter'], $tot['T_losses_gain_liter'], 0.000001);

        // U35: nominal rupiah losses/gain dari dataset tersimpan = -4009920.6370400735
        $this->assertEqualsWithDelta($expectedSummary['U35_losses_gain_rp'], $tot['U_losses_gain_rp'], 0.000001);

        // V35: status bulanan = Losses
        $this->assertSame($expectedSummary['V35_status_losses_gain'], $tot['V_status_losses_gain']);

        // AG35: total pengeluaran = 5.388.900
        $this->assertEquals($expectedSummary['AG35_total_pengeluaran'], $tot['AG_total_pengeluaran']);

        // AL36: total setoran (AL+AM+AN) = 276.754.938
        $this->assertEquals($expectedSummary['AL36_total_disetor'], $avg['AL36_total_disetor']);

        // AP35: saldo belum disetor hari terakhir dari dataset tersimpan = -31613125.999999918
        $this->assertEqualsWithDelta($expectedSummary['AP35_total_uang_belum_disetor'], $tot['AP_total_uang_belum_disetor'], 0.000001);

        // AV35: total omset + losses = 23217.367
        $this->assertEqualsWithDelta($expectedSummary['AV35_omset_plus_losses'], $tot['AV_omset_plus_losses'], 0.000001);

        // F36: rata-rata liter aktual per hari > 0 dari dataset tersimpan = 786.164333333333
        $this->assertEqualsWithDelta($expectedSummary['F36_avg_liter'], $avg['F36_avg_liter'], 0.000001);

        // Cross-check: AJ36 == AP35
        $this->assertEqualsWithDelta($expectedSummary['AJ36_selisih_disetor'], $avg['AJ36_selisih_disetor'], 0.000001);
        $this->assertTrue($summary['cross_checks']['aj36_equals_ap35']);

        // Cross-check: Pembelian BBM == Terima BBM
        $this->assertTrue($summary['cross_checks']['pembelian_equals_terima']);

        // Penggajian Operator (Desimal murni presisi tinggi dari dataset tersimpan):
        // Dendi: 4.209.329,60
        // Wawan: 434.143,80
        $opSalaries = collect($summary['operator_salaries'])->keyBy('operator_name');
        $this->assertEqualsWithDelta(21046.648, $opSalaries['Dendi Supriyatno']['total_av'], 0.01);
        $this->assertEqualsWithDelta($expectedSummary['dendi_gaji'], $opSalaries['Dendi Supriyatno']['gaji'], 0.000001);

        $this->assertEqualsWithDelta(2170.719, $opSalaries['Wawan']['total_av'], 0.01);
        $this->assertEqualsWithDelta($expectedSummary['wawan_gaji'], $opSalaries['Wawan']['gaji'], 0.000001);

        // Total Gaji = 4.643.473,40
        $totalOpGaji = $summary['operator_salaries_summary']['total_gaji'];
        $this->assertEqualsWithDelta($expectedSummary['total_gaji'], $totalOpGaji, 0.000001);
    }

    /**
     * UJI Fitur Pemantauan Superadmin: Akses Halaman, Polling JSON, dan Ekspor Excel
     */
    public function test_monitoring_superadmin_features(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Akses Halaman Pantauan
        $response = $this->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
            'month'   => 3,
            'year'    => 2026,
        ]));
        $response->assertStatus(200);
        $response->assertSee('Pantauan Laporan Harian Super Admin');
        $response->assertSee('REKAP 4P.53143');
        $response->assertSee('Dendi Supriyatno');

        // 2. Endpoint Polling Data JSON
        $jsonResp = $this->getJson(route('monitoring.daily-reports.data', [
            'shop_id' => $this->shop->id,
            'month'   => 3,
            'year'    => 2026,
        ]));
        $jsonResp->assertStatus(200);
        $jsonResp->assertJsonPath('has_changes', true);
        $this->assertCount(31, $jsonResp->json('recap.rows'));

        // 3. Endpoint Ringkasan Semua Gerai
        $overviewResp = $this->getJson(route('monitoring.daily-reports.overview'));
        $overviewResp->assertStatus(200);
        $overviewData = $overviewResp->json();
        $this->assertNotEmpty($overviewData);
        $firstShop = collect($overviewData)->firstWhere('shop_id', $this->shop->id);
        $this->assertNotNull($firstShop);
        $this->assertSame('Pertashop Kemutug Lor', $firstShop['shop_nama']);

        // 4. Ekspor Berkas Excel
        $excelResp = $this->get(route('monitoring.daily-reports.export-excel', [
            'shop_id' => $this->shop->id,
            'month'   => 3,
            'year'    => 2026,
        ]));
        $excelResp->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $excelResp->headers->get('content-type'));
        $this->assertStringContainsString('REKAP_4P53143_03_2026.xlsx', $excelResp->headers->get('content-disposition'));
    }

    /**
     * UJI Butir 1: Kolom X = M (Bukan M + K) saat Kirim Gerai / BBM Keluar Lain (J) > 0
     * Disertai verifikasi rumus manual Excel untuk AJ, AO, dan AP
     */
    public function test_kolom_x_equals_m_with_kirim_bbm_keluar_lain_greater_than_zero(): void
    {
        // 1. Buat outlet baru untuk pengujian Kirim
        $shopKirim = Shop::create([
            'nama'            => 'Pertashop Uji Kirim',
            'kode'            => '4P.99999',
            'corporation_id'  => $this->shop->corporation_id,
            'alamat'          => 'Jl. Uji Kirim',
            'skala'           => 21.49,
            'totalisator_awal'=> 100000.000,
            'stik_awal'       => 100.0,
            'modal_awal'      => 100000000,
            'is_active'       => true,
        ]);

        $userOp = User::create([
            'name'     => 'Operator Kirim',
            'email'    => 'op.kirim@pertashop.test',
            'role'     => 'operator',
            'password' => bcrypt('secret'),
        ]);
        $op = Operator::create([
            'user_id' => $userOp->id,
            'shop_id' => $shopKirim->id,
            'nik'     => 'OP-KIRIM',
        ]);

        $priceKirim = Price::create([
            'shop_id'      => $shopKirim->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        // Simpan saldo awal bulan di tabel shop_starting_balances
        \App\Models\ShopStartingBalance::create([
            'shop_id'            => $shopKirim->id,
            'year'               => 2026,
            'month'              => 4,
            'totalisator_awal'   => 100000.000,
            'stok_awal'          => 2000.000,
            'uang_belum_disetor' => -1000000.00, // Saldo awal: minus 1 juta
        ]);

        // Buat 1 laporan harian pada tanggal 2026-04-01 dengan:
        // - Totalisator Awal = 100000.000, Totalisator Akhir = 101000.000 (F = 1000.000 L)
        // - Test Pump = 10.000 L (H = 10, I = 10 x 12200 = 122.000)
        // - Kirim Gerai (J) = 50.000 L (K = 50 x 12950 = 647.500)
        // - Penjualan Aktual (L) = F - H - J = 1000 - 10 - 50 = 940.000 L
        // - Penjualan Aktual Rp (M) = 940 x 12200 = 11.468.000
        // - Pengeluaran (AG) = Ongkos bongkar 10.000
        // - Setoran = BRI 10.000.000 + QRIS 500.000 = 10.500.000
        $reportKirim = DailyReport::create([
            'shop_id'             => $shopKirim->id,
            'operator_id'         => $op->id,
            'price_id'            => $priceKirim->id,
            'report_date'         => '2026-04-01',
            'totalisator_awal'    => 100000.000,
            'totalisator_akhir'   => 101000.000,
            'stik_awal'           => 100.0,
            'stik_akhir'          => 50.0,
            'stok_awal'           => 2000.000,
            'test_pump_volume'    => 10.000,
            'bbm_keluar_lain'     => 50.000, // J > 0 (Kirim Kemutug / Keluar Lain)
            'penerimaan_volume'   => 0,
            'disetorkan'          => 10500000,
            'setor_tunai'         => 10000000,
            'setor_qris'          => 500000,
            'setor_transfer'      => 0,
            'harga_jual_snapshot' => 12200,
            'harga_beli_snapshot' => 11276.08,
            'status_lifecycle'    => 'approved',
            'created_at'          => '2026-04-01 20:00:00',
        ]);

        Spending::create([
            'daily_report_id'      => $reportKirim->id,
            'spending_category_id' => 1,
            'shop_id'              => $shopKirim->id,
            'operator_id'          => $op->id,
            'jumlah'               => 10000,
            'keterangan'           => 'Ongkos bongkar uji',
            'created_at'           => '2026-04-01 12:00:00',
        ]);

        $recap = PertashopDailyRecapService::getMonthlyRecap($shopKirim->id, 4, 2026);
        $day1 = $recap['rows'][1];

        // 1. Kolom J & K: Kirim Gerai
        $this->assertEqualsWithDelta(50.000, $day1['J_bbm_keluar_lain_vol'], 0.000001);
        $this->assertEqualsWithDelta(50.000 * 12950, $day1['K_bbm_keluar_lain_rp'], 0.000001);

        // 2. Kolom L & M: Aktual Penjualan
        $this->assertEqualsWithDelta(940.000, $day1['L_penjualan_aktual_vol'], 0.000001);
        $this->assertEqualsWithDelta(11468000, $day1['M_penjualan_aktual_rp'], 0.000001);

        // 3. Kolom X: JUMLAH ACTUAL PENJUALAN WAJIB SAMA DENGAN M (X = M = 11.468.000), BUKAN (M + K = 12.115.500)
        $this->assertEqualsWithDelta(11468000, $day1['X_jumlah_actual_penjualan'], 0.000001, 'Kolom X harus persis sama dengan M');
        $this->assertNotEquals(11468000 + 647500, $day1['X_jumlah_actual_penjualan'], 'Kolom X TIDAK boleh M + K');

        // 4. Kolom AG: Total Pengeluaran = 10.000
        $this->assertEqualsWithDelta(10000, $day1['AG_total_pengeluaran'], 0.000001);

        // 5. Kolom AJ: Pendapatan - Pengeluaran = X - AG = 11.468.000 - 10.000 = 11.458.000
        $this->assertEqualsWithDelta(11458000, $day1['AJ_pendapatan_minus_pengeluaran'], 0.000001);

        // --- Verifikasi Butir 1: Q, T, U, V, AV, AW dengan Kirim (J) > 0 ---
        // Parameter:
        // N (Stok Awal) = 2000.000
        // P (Terima BBM) = 0
        // J (Kirim Kemutug) = 50.000
        // L (Penjualan Aktual) = 940.000
        // Rumus Excel Stok Teoritis: Q = N + P - J - L
        // Q = 2000 + 0 - 50 - 940 = 1010.000
        $expectedQ = 2000.000 + 0 - 50.000 - 940.000;
        $this->assertEqualsWithDelta($expectedQ, $day1['Q_stok_teoritis'], 0.000001, 'Q = N + P - J - L');
        $this->assertEqualsWithDelta(1010.000, $day1['Q_stok_teoritis'], 0.000001);

        // S (Stok Aktual Fisik Tangki) = stik 50 cm * 21.49 = 1074.500
        $expectedS = 50.0 * 21.49;
        $this->assertEqualsWithDelta($expectedS, $day1['S_stok_aktual_vol'], 0.000001);

        // T (Losses / Gain Liter) = S - Q = 1074.500 - 1010.000 = +64.500 (Gain)
        $expectedT = $expectedS - $expectedQ;
        $this->assertEqualsWithDelta($expectedT, $day1['T_losses_gain_liter'], 0.000001, 'T = S - Q');
        $this->assertEqualsWithDelta(64.500, $day1['T_losses_gain_liter'], 0.000001);

        // U (Losses / Gain Rupiah) = T * harga_beli = 64.500 * 11276.08 = 727307.16
        $expectedU = $expectedT * 11276.08;
        $this->assertEqualsWithDelta($expectedU, $day1['U_losses_gain_rp'], 0.000001, 'U = T * harga_beli');

        // V (Status Losses/Gain) = T > 0 => "Gain"
        $this->assertSame('Gain', $day1['V_status_losses_gain']);

        // AV (Omset + Losses):
        // Rumus Excel: IF(V="Losses", L + T, L). Karena V="Gain", maka AV = L = 940.000 (Gain TIDAK menambah omset)
        $expectedAv = ($day1['V_status_losses_gain'] === 'Losses') ? (940.000 + $expectedT) : 940.000;
        $this->assertEqualsWithDelta(940.000, $day1['AV_omset_plus_losses'], 0.000001, 'AV = IF(V="Losses", L+T, L)');

        // AW (Komisi Operator):
        // Rumus Excel: AV * komisi_per_liter = 940.000 * 200 = 188.000
        $expectedAw = $expectedAv * 200;
        $this->assertEqualsWithDelta(188000, $day1['AW_gaji_harian'], 0.000001, 'AW = AV * komisi_per_liter');

        // 6. Kolom AO: Selisih Setoran = (AG + AL + AM + AN) - X = (10.000 + 10.000.000 + 500.000 + 0) - 11.468.000 = -958.000
        $this->assertEqualsWithDelta(-958000, $day1['AO_selisih_setoran'], 0.000001);

        // 7. Kolom AP: Total Belum Disetor = Saldo Awal (-1.000.000) + AO (-958.000) = -1.958.000
        $this->assertEqualsWithDelta(-1958000, $day1['AP_total_uang_belum_disetor'], 0.000001);
    }

    /**
     * UJI Butir 5: Middleware Peran pada /monitoring/daily-reports*
     * Bukti bahwa Guest, Operator, dan Investor DITOLAK (302/403) dan Super Admin DITERIMA (200)
     */
    public function test_monitoring_access_control_rejects_operator_and_investor(): void
    {
        // 1. Guest (Tanpa Login) -> Ditolak / Diarahkan ke login (302)
        $respGuest = $this->get(route('monitoring.daily-reports', ['shop_id' => $this->shop->id]));
        $respGuest->assertStatus(302);
        $respGuest->assertRedirect('login');

        // 2. User Operator -> Ditolak (403 Forbidden)
        $respOperator = $this->actingAs($this->operatorDendi->user)->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
        ]));
        $respOperator->assertStatus(403);

        // 3. User Investor -> Ditolak (403 Forbidden)
        $this->flushSession();
        $userInvestor = User::create([
            'name'     => 'Investor Harian',
            'email'    => 'investor.harian@pertashop.test',
            'role'     => 'investor',
            'password' => bcrypt('secret'),
        ]);
        \App\Models\Investor::create([
            'user_id' => $userInvestor->id,
            'nik'     => 'INV-HARIAN',
        ]);
        $respInvestor = $this->actingAs($userInvestor)->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
        ]));
        $respInvestor->assertStatus(403);

        // 4. Super Admin -> Diterima (200 OK)
        $this->flushSession();
        $respSuperAdmin = $this->actingAs($this->superAdmin)->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
        ]));
        $respSuperAdmin->assertStatus(200);
    }

    /**
     * UJI Butir 4: Saldo Awal Bulan (Bulan Pertama Manual via ShopStartingBalance, Bulan Ke-2 Otomatis Estafet)
     * Diuji untuk gerai selain Kemutug (Pertashop Kalitapen)
     */
    public function test_saldo_awal_bulan_pertama_manual_dan_bulan_berikutnya_otomatis_untuk_gerai_lain(): void
    {
        // 1. Buat gerai non-Kemutug (Kalitapen)
        $corp = Corporation::first();
        $shopKalitapen = Shop::create([
            'nama'            => 'Pertashop Kalitapen',
            'kode'            => '4P.53112',
            'corporation_id'  => $corp->id,
            'alamat'          => 'Kalitapen, Banyumas',
            'skala'           => 21.00,
            'totalisator_awal'=> 200000.000,
            'stik_awal'       => 80.0,
            'modal_awal'      => 60000000,
            'is_active'       => true,
        ]);

        $userOpKlt = User::create([
            'name'     => 'Operator Kalitapen',
            'email'    => 'op.kalitapen@pertashop.test',
            'role'     => 'operator',
            'password' => bcrypt('secret'),
        ]);
        $opKalitapen = Operator::create([
            'user_id' => $userOpKlt->id,
            'shop_id' => $shopKalitapen->id,
            'nik'     => 'OP-KLT',
        ]);

        $priceKalitapen = Price::create([
            'shop_id'      => $shopKalitapen->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        // Input saldo awal manual bulan pertama (Januari 2026) via tabel shop_starting_balances
        \App\Models\ShopStartingBalance::create([
            'shop_id'            => $shopKalitapen->id,
            'year'               => 2026,
            'month'              => 1,
            'totalisator_awal'   => 205000.000,
            'stok_awal'          => 1800.000,
            'uang_belum_disetor' => -5000000.00,
            'keterangan'         => 'Saldo awal pembukaan operasional Januari 2026',
        ]);

        // Ambil rekap Januari 2026 (Bulan Pertama)
        $recapJan = PertashopDailyRecapService::getMonthlyRecap($shopKalitapen->id, 1, 2026);
        $this->assertEqualsWithDelta(205000.000, $recapJan['saldo_awal']['totalisator_awal_hari_pertama'], 0.000001);
        $this->assertEqualsWithDelta(1800.000, $recapJan['saldo_awal']['stok_awal_hari_pertama'], 0.000001);
        $this->assertEqualsWithDelta(-5000000.00, $recapJan['saldo_awal']['uang_belum_disetor_periode_lalu'], 0.000001);

        // Buat laporan penutupan di akhir Januari (31 Januari 2026)
        $closingReportJan = DailyReport::create([
            'shop_id'                 => $shopKalitapen->id,
            'operator_id'             => $opKalitapen->id,
            'price_id'                => $priceKalitapen->id,
            'report_date'             => '2026-01-31',
            'totalisator_awal'        => 215000.000,
            'totalisator_akhir'       => 216200.000, // Totalisator akhir bulan = 216200.000
            'stik_awal'               => 70.0,
            'stik_akhir'              => 65.0,
            'stok_awal'               => 1500.000,
            'stok_akhir_aktual'       => 1365.000, // Stok akhir aktual = 1365.000
            'disetorkan'              => 0,
            'running_balance_setoran' => -3250000.00, // Saldo berjalan belum disetor akhir Januari = -3.250.000
            'status_lifecycle'        => 'approved',
            'created_at'              => '2026-01-31 20:00:00',
        ]);

        // 2. Ambil rekap Februari 2026 (Bulan Berikutnya) -> Wajib OTOMATIS estafet dari 31 Januari
        $recapFeb = PertashopDailyRecapService::getMonthlyRecap($shopKalitapen->id, 2, 2026);

        $this->assertEqualsWithDelta(216200.000, $recapFeb['saldo_awal']['totalisator_awal_hari_pertama'], 0.000001, 'Totalisator awal Feb wajib otomatis dari akhir Jan');
        $this->assertEqualsWithDelta(1365.000, $recapFeb['saldo_awal']['stok_awal_hari_pertama'], 0.000001, 'Stok awal Feb wajib otomatis dari stok akhir Jan');
        $this->assertEqualsWithDelta(-3250000.00, $recapFeb['saldo_awal']['uang_belum_disetor_periode_lalu'], 0.000001, 'Uang belum disetor Feb wajib otomatis dari running balance akhir Jan');

        // 3. Test Peringatan jika input manual di shop_starting_balances berbeda dari hasil estafet bulan sebelumnya (Butir 9)
        \App\Models\ShopStartingBalance::create([
            'shop_id'            => $shopKalitapen->id,
            'year'               => 2026,
            'month'              => 2,
            'totalisator_awal'   => 216300.000, // Berbeda dari 216200.000
            'stok_awal'          => 1400.000,   // Berbeda dari 1365.000
            'uang_belum_disetor' => -4000000.00,// Berbeda dari -3250000.00
            'keterangan'         => 'Koreksi manual saldo awal Februari',
        ]);

        $recapFebOverride = PertashopDailyRecapService::getMonthlyRecap($shopKalitapen->id, 2, 2026);
        $this->assertTrue($recapFebOverride['saldo_awal']['is_manual_override'] ?? false);
        $this->assertNotNull($recapFebOverride['saldo_awal']['warning']);
        $this->assertStringContainsString('PERINGATAN: Input manual saldo awal', $recapFebOverride['saldo_awal']['warning']);
    }

    /**
     * UJI Butir 8: Verifikasi Isi Berkas Ekspor Excel Maret 2026 Sesuai Format Acuan Excel
     */
    public function test_exported_excel_file_matches_dataset_and_template(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('monitoring.daily-reports.export-excel', [
            'shop_id' => $this->shop->id,
            'month'   => 3,
            'year'    => 2026,
        ]));

        $response->assertStatus(200);

        // Simpan output berkas sementara untuk dibaca oleh PhpSpreadsheet
        $tempPath = storage_path('framework/testing/test_export_kemutug_maret_2026.xlsx');
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }
        file_put_contents($tempPath, $response->streamedContent());

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Cek Judul Sheet / Sel Header
        $titleA1 = (string) $sheet->getCell('A1')->getValue();
        $this->assertStringContainsString('REKAP', strtoupper($titleA1));

        // 2. Cek Sampel Baris Harian Tanggal 1 (Baris 6 pada template ekspor: Header ada di baris 4 & 5)
        // Kolom D (Totalisator Awal Hari 1) = 498129.8
        $cellD6 = floatval($sheet->getCell('D6')->getValue());
        $this->assertEqualsWithDelta(498129.8, $cellD6, 0.000001);

        // Kolom E (Totalisator Akhir Hari 1) = 498926.81
        $cellE6 = floatval($sheet->getCell('E6')->getValue());
        $this->assertEqualsWithDelta(498926.81, $cellE6, 0.000001);

        // Kolom F (Teoritis Vol Hari 1) = 797.01
        $cellF6 = floatval($sheet->getCell('F6')->getValue());
        $this->assertEqualsWithDelta(797.01, $cellF6, 0.00001);

        // Kolom G (Teoritis Rp Hari 1) = 9723522
        $cellG6 = floatval($sheet->getCell('G6')->getValue());
        $this->assertEqualsWithDelta(9723522, $cellG6, 0.00001);

        // Kolom L (Aktual Penjualan Vol Hari 1) = 797.01
        $cellL6 = floatval($sheet->getCell('L6')->getValue());
        $this->assertEqualsWithDelta(797.01, $cellL6, 0.00001);

        // Kolom X (Jumlah Actual Penjualan) = 9723522
        $cellX6 = floatval($sheet->getCell('X6')->getValue());
        $this->assertEqualsWithDelta(9723522, $cellX6, 0.00001);

        // Kolom Tgl 31 (Baris 6 + 30 = Baris 36)
        $cellD36 = floatval($sheet->getCell('D36')->getValue());
        $this->assertEqualsWithDelta(520733.95, $cellD36, 0.000001);

        // Kolom AP Tgl 31 (Total Uang Belum Disetor Hari 31) = -31613125.999999918 (-31613126)
        $cellAP36 = floatval($sheet->getCell('AP36')->getValue());
        $this->assertEqualsWithDelta(-31613125.999999918, $cellAP36, 0.0001);

        // Baris 37: Baris Total (Total Penjualan Aktual Vol L) = 23584.93
        $cellL37 = floatval($sheet->getCell('L37')->getValue());
        $this->assertEqualsWithDelta(23584.93, $cellL37, 0.0001);

        // Hapus file sementara
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    /**
     * UJI Butir 5: Verifikasi Pencatatan Audit Log (Old Values, New Values, User, Waktu)
     * Mencakup DailyReport (operator input), Spending, DailyReportDeposit, dan Incoming
     */
    public function test_audit_logs_record_old_and_new_values_for_operator_input_spendings_deposits_and_incoming(): void
    {
        $this->actingAs($this->superAdmin);

        $report = DailyReport::where('shop_id', $this->shop->id)->whereDate('report_date', '2026-03-01')->first();
        $this->assertNotNull($report);

        // 1. Audit Log Perubahan DailyReport (Input Operator)
        $oldDisetorkan = $report->disetorkan;
        $report->update([
            'disetorkan' => $oldDisetorkan + 500000,
        ]);

        $logReport = \App\Models\AuditLog::where('model_type', 'DailyReport')
            ->where('model_id', $report->id)
            ->where('action', 'UPDATE_OPERATOR_INPUT')
            ->latest()
            ->first();

        $this->assertNotNull($logReport, 'AuditLog UPDATE_OPERATOR_INPUT harus tercatat');
        $this->assertEquals($this->superAdmin->id, $logReport->user_id);
        $this->assertArrayHasKey('disetorkan', $logReport->new_values);
        $this->assertEquals($oldDisetorkan + 500000, $logReport->new_values['disetorkan']);
        $this->assertNotNull($logReport->created_at);

        // 2. Audit Log Perubahan Spending
        $spending = Spending::where('daily_report_id', $report->id)->first();
        if (!$spending) {
            $spending = Spending::create([
                'daily_report_id'      => $report->id,
                'spending_category_id' => 1,
                'shop_id'              => $this->shop->id,
                'operator_id'          => $this->operatorDendi->id,
                'jumlah'               => 15000,
                'keterangan'           => 'Uji spending log',
            ]);
        }
        $spending->update(['jumlah' => 25000]);

        $logSpending = \App\Models\AuditLog::where('model_type', 'Spending')
            ->where('model_id', $spending->id)
            ->where('action', 'UPDATE_SPENDING')
            ->latest()
            ->first();

        $this->assertNotNull($logSpending, 'AuditLog UPDATE_SPENDING harus tercatat');
        $this->assertEquals($this->superAdmin->id, $logSpending->user_id);
        $this->assertEquals(25000, $logSpending->new_values['jumlah']);

        // 3. Audit Log Perubahan Incoming / Purchase
        $incoming = Incoming::where('daily_report_id', $report->id)->first();
        if ($incoming) {
            $incoming->update(['volume' => 2500]);
            $logIncoming = \App\Models\AuditLog::where('model_type', 'Incoming')
                ->where('model_id', $incoming->id)
                ->where('action', 'UPDATE_INCOMING_PURCHASE')
                ->latest()
                ->first();
            $this->assertNotNull($logIncoming, 'AuditLog UPDATE_INCOMING_PURCHASE harus tercatat');
            $this->assertEquals(2500, $logIncoming->new_values['volume']);
        }

        // 4. Audit Log Perubahan DailyReportDeposit
        $depCat = \App\Models\DepositCategory::firstOrCreate(['nama_kategori' => 'BRI']);
        $deposit = \App\Models\DailyReportDeposit::create([
            'daily_report_id' => $report->id,
            'deposit_category_id' => $depCat->id,
            'jumlah' => 1000000,
        ]);
        $deposit->update(['jumlah' => 1500000]);
        $logDeposit = \App\Models\AuditLog::where('model_type', 'DailyReportDeposit')
            ->where('model_id', $deposit->id)
            ->where('action', 'UPDATE_DEPOSIT')
            ->latest()
            ->first();
        $this->assertNotNull($logDeposit, 'AuditLog UPDATE_DEPOSIT harus tercatat');
        $this->assertEquals(1500000, $logDeposit->new_values['jumlah']);
    }
}

