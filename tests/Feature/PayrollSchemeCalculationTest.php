<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\DailyReport;
use App\Models\Operator;
use App\Models\PayrollPeriod;
use App\Models\PayrollSystem;
use App\Models\Price;
use App\Models\Shop;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollSchemeCalculationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Corporation $corp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'     => 'Super Admin Test',
            'email'    => 'superadmin.test@pertashop.com',
            'role'     => 'super-admin',
            'password' => Hash::make('password123'),
        ]);

        $this->corp = Corporation::create([
            'nama'   => 'PT Pertashop Jaya Test',
            'alamat' => 'Banyumas',
        ]);
    }

    protected function createShop(string $nama, string $kode): Shop
    {
        return Shop::create([
            'corporation_id' => $this->corp->id,
            'nama'           => $nama,
            'kode'           => $kode,
            'alamat'         => 'Alamat ' . $nama,
        ]);
    }

    protected function createDailyReport(Shop $shop, Operator $operator, float $volume): DailyReport
    {
        $price = Price::create([
            'shop_id'      => $shop->id,
            'harga_beli'   => 12000,
            'harga_jual'   => 14000,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        $rupiah = $volume * 14000;

        return DailyReport::create([
            'shop_id'                   => $shop->id,
            'operator_id'               => $operator->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => $volume,
            'volume_penjualan_teoritis' => $volume,
            'rupiah_penjualan'          => $rupiah,
            'disetorkan'                => $rupiah,
            'setor_tunai'               => $rupiah,
            'losses_gain'               => 0,
            'selisih_setoran'           => 0,
            'totalisator_awal'          => 1000,
            'totalisator_akhir'         => 1000 + $volume,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'created_at'                => '2026-06-15 10:00:00',
        ]);
    }

    /**
     * Uji pemetaan skema default berdasarkan nama cabang.
     */
    public function test_default_payroll_scheme_by_branch_name(): void
    {
        $kalitapen = $this->createShop('Kalitapen', '4P.53119');
        $kalibenda = $this->createShop('Kalibenda', '4P.53134');
        $pageralang = $this->createShop('Pageralang', '4P.53164');
        $kemutug = $this->createShop('Kemutug Lor', '4P.53143');
        $gumelar = $this->createShop('Gumelar', '4P.53158');
        $sumingkir = $this->createShop('Sumingkir', '4P.532.40');

        $this->assertEquals('komisi_murni', $kalitapen->getDefaultPayrollScheme());
        $this->assertEquals('komisi_murni', $kalibenda->getDefaultPayrollScheme());
        $this->assertEquals('komisi_murni', $pageralang->getDefaultPayrollScheme());
        $this->assertEquals('komisi_murni', $kemutug->getDefaultPayrollScheme());
        $this->assertEquals('gaji_pokok_murni', $gumelar->getDefaultPayrollScheme());
        $this->assertEquals('hibrid', $sumingkir->getDefaultPayrollScheme());
    }

    /**
     * Uji sinkronisasi otomatis boolean flag pada model PayrollSystem saat tipe_skema disimpan.
     */
    public function test_payroll_system_model_auto_sync(): void
    {
        $shop = $this->createShop('Outlet Dummy', '4P.DUMMY');

        // 1. Komisi Murni
        $ps1 = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Komisi Murni Test',
            'tipe_skema'              => 'komisi_murni',
            'rate_per_liter'          => 200,
            'nominal_gaji_pokok'      => 1500000,
            'potongan_per_hari_alpha' => 50000,
            'perlakuan_losses_gain'   => 'losses_only',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);
        $this->assertTrue($ps1->ada_rate_per_liter);
        $this->assertFalse($ps1->ada_gaji_pokok);
        $this->assertNull($ps1->nominal_gaji_pokok);
        $this->assertTrue($ps1->isKomisiMurni());

        // 2. Gaji Pokok Murni
        $ps2 = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Gaji Pokok Murni Test',
            'tipe_skema'              => 'gaji_pokok_murni',
            'rate_per_liter'          => 250,
            'nominal_gaji_pokok'      => 1800000,
            'potongan_per_hari_alpha' => 50000,
            'perlakuan_losses_gain'   => 'losses_only',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);
        $this->assertFalse($ps2->ada_rate_per_liter);
        $this->assertEquals(0, (float) $ps2->rate_per_liter);
        $this->assertTrue($ps2->ada_gaji_pokok);
        $this->assertEquals(1800000, (float) $ps2->nominal_gaji_pokok);
        $this->assertTrue($ps2->isGajiPokokMurni());

        // 3. Hibrid
        $ps3 = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Hibrid Test',
            'tipe_skema'              => 'hibrid',
            'rate_per_liter'          => 200,
            'nominal_gaji_pokok'      => 1500000,
            'potongan_per_hari_alpha' => 50000,
            'perlakuan_losses_gain'   => 'losses_only',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);
        $this->assertTrue($ps3->ada_rate_per_liter);
        $this->assertTrue($ps3->ada_gaji_pokok);
        $this->assertEquals(200, (float) $ps3->rate_per_liter);
        $this->assertEquals(1500000, (float) $ps3->nominal_gaji_pokok);
        $this->assertTrue($ps3->isHibrid());
    }

    /**
     * Uji kalkulasi penggajian untuk cabang Komisi Murni (Kalitapen):
     * Formula: Total Liter Penjualan × Nominal Komisi per Liter (Gaji Pokok = 0)
     */
    public function test_calculation_sistem_komisi_murni_kalitapen(): void
    {
        $shop = $this->createShop('Kalitapen', '4P.53119');

        $opUser = User::create([
            'name'     => 'Operator Kalitapen',
            'email'    => 'op.kalitapen@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);

        $operator = Operator::create([
            'shop_id' => $shop->id,
            'user_id' => $opUser->id,
        ]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Kalitapen',
            'tipe_skema'              => 'komisi_murni',
            'rate_per_liter'          => 200,
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Buat 1 laporan harian dengan volume 1000 liter
        $this->createDailyReport($shop, $operator, 1000);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detail = $period->details->first();
        $this->assertNotNull($detail);
        $this->assertEquals(1000, (float) $detail->liter_bagian);
        $this->assertEquals(0, (float) $detail->gaji_pokok, 'Komisi Murni tidak boleh ada gaji pokok');
        $this->assertEquals(200000, (float) $detail->gaji_variable, '1000 liter * Rp 200 = Rp 200.000');
        $this->assertEquals(200000, (float) $detail->take_home_pay);
    }

    /**
     * Uji kalkulasi penggajian untuk cabang Gaji Pokok Murni (Gumelar):
     * Formula: Nominal Gaji Pokok (Gaji Variable / Komisi Liter = 0)
     */
    public function test_calculation_sistem_gaji_pokok_murni_gumelar(): void
    {
        $shop = $this->createShop('Gumelar', '4P.53158');

        $opUser = User::create([
            'name'     => 'Operator Gumelar',
            'email'    => 'op.gumelar@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);

        $operator = Operator::create([
            'shop_id' => $shop->id,
            'user_id' => $opUser->id,
        ]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Gumelar',
            'tipe_skema'              => 'gaji_pokok_murni',
            'nominal_gaji_pokok'      => 1750000,
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Buat laporan harian dengan volume 1500 liter
        $this->createDailyReport($shop, $operator, 1500);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detail = $period->details->first();
        $this->assertNotNull($detail);
        $this->assertEquals(1750000, (float) $detail->gaji_pokok, 'Gaji Pokok harus sesuai nominal');
        $this->assertEquals(0, (float) $detail->gaji_variable, 'Gaji Pokok Murni tidak boleh ada komisi liter');
        $this->assertEquals(1750000, (float) $detail->take_home_pay);
    }

    /**
     * Uji kalkulasi penggajian untuk cabang Hibrid (Sumingkir):
     * Formula: Nominal Gaji Pokok + (Total Liter Penjualan × Nominal Komisi per Liter)
     */
    public function test_calculation_sistem_hibrid_sumingkir(): void
    {
        $shop = $this->createShop('Sumingkir', '4P.532.40');

        $opUser = User::create([
            'name'     => 'Operator Sumingkir',
            'email'    => 'op.sumingkir@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);

        $operator = Operator::create([
            'shop_id' => $shop->id,
            'user_id' => $opUser->id,
        ]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Sumingkir',
            'tipe_skema'              => 'hibrid',
            'nominal_gaji_pokok'      => 1500000,
            'rate_per_liter'          => 200,
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Buat laporan harian dengan volume 2000 liter
        $this->createDailyReport($shop, $operator, 2000);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detail = $period->details->first();
        $this->assertNotNull($detail);
        $this->assertEquals(1500000, (float) $detail->gaji_pokok, 'Gaji Pokok harus Rp 1.500.000');
        $this->assertEquals(400000, (float) $detail->gaji_variable, '2000 liter * Rp 200 = Rp 400.000');
        $this->assertEquals(1900000, (float) $detail->take_home_pay, 'THP = Rp 1.500.000 + Rp 400.000 = Rp 1.900.000');
    }

    /**
     * Uji kalkulasi pembagian liter (split shift):
     * Memastikan liter_bagian mengambil data penjualan riil dari hari/shift di mana
     * operator benar-benar bertugas, bukan membagi rata total penjualan bulanan.
     */
    public function test_split_shift_distributes_liters_by_actual_working_days_not_flat_monthly(): void
    {
        $shop = $this->createShop('Kalitapen Shift Test', '4P.53119-ST');

        $user1 = User::create([
            'name'     => 'Operator 1 Shift',
            'email'    => 'op1.shift@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);
        $op1 = Operator::create(['shop_id' => $shop->id, 'user_id' => $user1->id]);

        $user2 = User::create([
            'name'     => 'Operator 2 Shift',
            'email'    => 'op2.shift@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);
        $op2 = Operator::create(['shop_id' => $shop->id, 'user_id' => $user2->id]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Split Test',
            'tipe_skema'              => 'komisi_murni',
            'rate_per_liter'          => 200,
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Hari 1: 1000 liter, hanya Operator 1 yang bertugas
        $price = Price::create([
            'shop_id'      => $shop->id,
            'harga_beli'   => 12000,
            'harga_jual'   => 14000,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        DailyReport::create([
            'shop_id'                   => $shop->id,
            'operator_id'               => $op1->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => 1000,
            'volume_penjualan_teoritis' => 1000,
            'rupiah_penjualan'          => 14000000,
            'disetorkan'                => 14000000,
            'losses_gain'               => 0,
            'selisih_setoran'           => 0,
            'totalisator_awal'          => 1000,
            'totalisator_akhir'         => 2000,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'created_at'                => '2026-06-10 10:00:00',
        ]);

        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $op1->id,
            'tanggal'     => '2026-06-10',
            'shift_ke'    => 1,
            'status'      => 'hadir',
        ]);

        // Hari 2: 1000 liter, Operator 1 (Shift 1) dan Operator 2 (Shift 2) bertugas bersama
        DailyReport::create([
            'shop_id'                   => $shop->id,
            'operator_id'               => $op1->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => 1000,
            'volume_penjualan_teoritis' => 1000,
            'rupiah_penjualan'          => 14000000,
            'disetorkan'                => 14000000,
            'losses_gain'               => 0,
            'selisih_setoran'           => 0,
            'totalisator_awal'          => 2000,
            'totalisator_akhir'         => 3000,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'created_at'                => '2026-06-11 10:00:00',
        ]);

        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $op1->id,
            'tanggal'     => '2026-06-11',
            'shift_ke'    => 1,
            'status'      => 'hadir',
        ]);
        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $op2->id,
            'tanggal'     => '2026-06-11',
            'shift_ke'    => 2,
            'status'      => 'hadir',
        ]);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detailOp1 = $period->details->where('operator_id', $op1->id)->first();
        $detailOp2 = $period->details->where('operator_id', $op2->id)->first();

        // Operator 1: 1000 L (Hari 1) + 500 L (Hari 2) = 1500 L -> Komisi Rp 300.000
        $this->assertEquals(1500, (float) $detailOp1->liter_bagian);
        $this->assertEquals(300000, (float) $detailOp1->gaji_variable);

        // Operator 2: 0 L (Hari 1) + 500 L (Hari 2) = 500 L -> Komisi Rp 100.000
        $this->assertEquals(500, (float) $detailOp2->liter_bagian);
        $this->assertEquals(100000, (float) $detailOp2->gaji_variable);
    }

    /**
     * Uji penanganan potongan alpha prorata & uang transport di skema Gaji Pokok Murni (Gumelar):
     * - Potongan Alpha Prorata: (Gaji_Pokok / Standar_Hari_Kerja) * Hari_Alpha
     * - Uang Transport dihitung dari hari masuk kerja dan TIDAK dimasukkan ke basis prorata alpha.
     */
    public function test_potongan_alpha_prorata_and_transport_in_gaji_pokok_murni(): void
    {
        $shop = $this->createShop('Gumelar Alpha Test', '4P.53158-AT');

        $user = User::create([
            'name'     => 'Operator Gumelar Alpha',
            'email'    => 'op.gumelar.alpha@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);
        $operator = Operator::create(['shop_id' => $shop->id, 'user_id' => $user->id]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Gumelar Prorata',
            'tipe_skema'              => 'gaji_pokok_murni',
            'nominal_gaji_pokok'      => 2600000, // Rp 2.600.000
            'standar_hari_kerja'      => 26,      // Rate harian = 2.600.000 / 26 = Rp 100.000/hari
            'metode_potongan_alpha'   => 'prorata_gaji_pokok',
            'rate_transport_per_hari' => 20000,   // Rp 20.000/hari kerja
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Buat 1 laporan harian kehadiran (1 hari kerja)
        $this->createDailyReport($shop, $operator, 500);

        // 2 hari alpha via ShiftSchedule (otomatis men-trigger kalkulasi AttendanceRecap)
        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-06-01',
            'shift_ke'    => 1,
            'status'      => 'alpha',
        ]);
        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-06-02',
            'shift_ke'    => 1,
            'status'      => 'alpha',
        ]);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detail = $period->details->first();
        $this->assertNotNull($detail);

        // Gaji pokok Rp 2.600.000
        $this->assertEquals(2600000, (float) $detail->gaji_pokok);
        $this->assertEquals(0, (float) $detail->gaji_variable);

        // Uang transport = 1 hari kerja (dari 1 daily report yang dihitung) * 20.000 = Rp 20.000
        $this->assertEquals(20000, (float) $detail->uang_transport);

        // Potongan Alpha Prorata: 2 hari * (2.600.000 / 26) = 2 * Rp 100.000 = Rp 200.000
        $this->assertEquals(200000, (float) $detail->potongan_alpha);

        // THP = (2.600.000 + 20.000) - 200.000 = Rp 2.420.000
        $this->assertEquals(2420000, (float) $detail->take_home_pay);
    }
}

