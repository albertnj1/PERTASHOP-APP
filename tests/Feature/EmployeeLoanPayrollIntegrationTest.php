<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\DailyReport;
use App\Models\EmployeeLoan;
use App\Models\Operator;
use App\Models\PayrollLoanDeduction;
use App\Models\PayrollPeriod;
use App\Models\PayrollSystem;
use App\Models\Price;
use App\Models\ShiftSchedule;
use App\Models\Shop;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeLoanPayrollIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Corporation $corp;
    protected Shop $shop;
    protected Operator $operator;
    protected User $opUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Super Admin Loan Test',
            'email'    => 'admin.loan.' . uniqid() . '@pertashop.test',
            'role'     => 'super-admin',
            'password' => Hash::make('password123'),
        ]);

        $this->corp = Corporation::create([
            'nama'   => 'PT Kasbon Test Corp',
            'alamat' => 'Purwokerto',
        ]);

        $this->shop = Shop::create([
            'corporation_id' => $this->corp->id,
            'nama'           => 'Pertashop Kemutug Test',
            'kode'           => 'KMTG-' . uniqid(),
            'alamat'         => 'Kemutug Lor',
        ]);

        $this->opUser = User::create([
            'name'     => 'Dendi Supriyatno',
            'email'    => 'dendi.' . uniqid() . '@operator.test',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);

        $this->operator = Operator::create([
            'user_id' => $this->opUser->id,
            'shop_id' => $this->shop->id,
        ]);
    }

    public function test_store_loan_with_tenor_calculates_installment_and_balance()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('employee-loans.store'), [
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-09-09',
            'jumlah'      => 600000,
            'tenor_bulan' => 3,
            'keterangan'  => 'Pinjaman darurat keluarga',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $loan = EmployeeLoan::where('operator_id', $this->operator->id)->latest()->first();
        $this->assertNotNull($loan);
        $this->assertEquals(600000, floatval($loan->jumlah));
        $this->assertEquals(3, $loan->tenor_bulan);
        $this->assertEquals(200000, floatval($loan->nominal_angsuran));
        $this->assertEquals(0, $loan->angsuran_ke);
        $this->assertEquals(600000, floatval($loan->sisa_pokok));
        $this->assertEquals('pending', $loan->status);
    }

    public function test_full_payroll_integration_multi_month_deduction_and_idempotency()
    {
        // 1. Setup sistem penggajian
        $system = PayrollSystem::create([
            'shop_id'                  => $this->shop->id,
            'nama_sistem'              => 'Sistem Gaji Pokok Murni Kemutug',
            'tipe_skema'               => 'gaji_pokok_murni',
            'nominal_gaji_pokok'       => 2000000,
            'ada_gaji_pokok'           => true,
            'ada_rate_per_liter'       => false,
            'rate_per_liter'           => 0,
            'rate_transport_per_hari'  => 0,
            'potongan_per_hari_alpha'  => 0,
            'metode_split'             => 'per_hari_penuh',
            'aktif'                    => true,
        ]);

        // 2. Setup kasbon 600.000 dengan tenor 3 bulan (200.000 / bln)
        $loan = EmployeeLoan::create([
            'operator_id'      => $this->operator->id,
            'tanggal'          => '2026-01-01',
            'jumlah'           => 600000,
            'tenor_bulan'      => 3,
            'nominal_angsuran' => 200000,
            'angsuran_ke'      => 0,
            'sisa_pokok'       => 600000,
            'status'           => 'approved',
            'approved_by'      => $this->admin->id,
            'approved_at'      => now(),
        ]);

        // Buat 1 shift dan laporan harian agar ada hari kerja
        ShiftSchedule::create([
            'shop_id'     => $this->shop->id,
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-01-10',
            'shift_ke'    => 1,
            'jam_mulai'   => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);

        $price = Price::create([
            'shop_id'      => $this->shop->id,
            'harga_beli'   => 12000,
            'harga_jual'   => 14000,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        DailyReport::create([
            'shop_id'                   => $this->shop->id,
            'operator_id'               => $this->operator->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => 500,
            'volume_penjualan_teoritis' => 500,
            'rupiah_penjualan'          => 500 * 14000,
            'disetorkan'                => 500 * 14000,
            'selisih_setoran'           => 0,
            'created_at'                => '2026-01-10 16:00:00',
        ]);

        $service = app(PayrollCalculationService::class);

        // ===== BULAN 1: GENERATE DRAFT =====
        $period1 = $service->generate($this->shop->id, 1, 2026, $this->admin->id);
        $detail1 = $period1->details->firstWhere('operator_id', $this->operator->id);

        $this->assertEquals(200000, floatval($detail1->potongan_hutang));
        // THP = 2.000.000 (gaji pokok) - 200.000 (kasbon) = 1.800.000
        $this->assertEquals(1800000, floatval($detail1->take_home_pay));

        // Cek snapshot payroll_loan_deductions
        $deduction1 = PayrollLoanDeduction::where('payroll_period_id', $period1->id)->first();
        $this->assertNotNull($deduction1);
        $this->assertEquals(1, $deduction1->cicilan_ke);
        $this->assertEquals(3, $deduction1->total_tenor);
        $this->assertEquals(200000, floatval($deduction1->nominal_potongan));
        $this->assertEquals(400000, floatval($deduction1->sisa_pokok_setelah));
        $this->assertFalse($deduction1->is_finalized);

        // KASBON DI DB MASIH UTUH KARENA DRAFT (IDEMPOTENCY CHECK)
        $loan->refresh();
        $this->assertEquals(600000, floatval($loan->sisa_pokok));
        $this->assertEquals(0, $loan->angsuran_ke);

        // RE-GENERATE ULANG DRAFT DI BULAN YANG SAMA
        $period1Re = $service->generate($this->shop->id, 1, 2026, $this->admin->id);
        $loan->refresh();
        $this->assertEquals(600000, floatval($loan->sisa_pokok), 'Regenerate draft tidak boleh double-deduct');
        $this->assertEquals(0, $loan->angsuran_ke);

        // FINALISASI BULAN 1
        $service->finalizePayrollLoans($period1Re);
        $period1Re->update(['status' => 'final']);

        $loan->refresh();
        $this->assertEquals(400000, floatval($loan->sisa_pokok));
        $this->assertEquals(1, $loan->angsuran_ke);
        $this->assertEquals('active', $loan->status);

        // IDEMPOTENCY FINALISASI: Panggil lagi finalizePayrollLoans tidak akan mengurangi dua kali
        $service->finalizePayrollLoans($period1Re);
        $loan->refresh();
        $this->assertEquals(400000, floatval($loan->sisa_pokok), 'Finalisasi ulang tidak boleh memotong sisa pokok dua kali');

        // ===== BULAN 2: GENERATE DRAFT & FINALIZE =====
        ShiftSchedule::create([
            'shop_id'     => $this->shop->id,
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-02-10',
            'shift_ke'    => 1,
            'jam_mulai'   => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);
        DailyReport::create([
            'shop_id'                   => $this->shop->id,
            'operator_id'               => $this->operator->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => 500,
            'volume_penjualan_teoritis' => 500,
            'rupiah_penjualan'          => 500 * 14000,
            'disetorkan'                => 500 * 14000,
            'selisih_setoran'           => 0,
            'created_at'                => '2026-02-10 16:00:00',
        ]);

        $period2 = $service->generate($this->shop->id, 2, 2026, $this->admin->id);
        $detail2 = $period2->details->firstWhere('operator_id', $this->operator->id);

        $this->assertEquals(200000, floatval($detail2->potongan_hutang));
        $deduction2 = PayrollLoanDeduction::where('payroll_period_id', $period2->id)->first();
        $this->assertEquals(2, $deduction2->cicilan_ke);
        $this->assertEquals(3, $deduction2->total_tenor);
        $this->assertEquals(200000, floatval($deduction2->sisa_pokok_setelah));

        $service->finalizePayrollLoans($period2);
        $period2->update(['status' => 'final']);

        $loan->refresh();
        $this->assertEquals(200000, floatval($loan->sisa_pokok));
        $this->assertEquals(2, $loan->angsuran_ke);
        $this->assertEquals('active', $loan->status);

        // ===== BULAN 3: ANGSURAN TERAKHIR (PELUNASAN) =====
        ShiftSchedule::create([
            'shop_id'     => $this->shop->id,
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-03-10',
            'shift_ke'    => 1,
            'jam_mulai'   => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);
        DailyReport::create([
            'shop_id'                   => $this->shop->id,
            'operator_id'               => $this->operator->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => 500,
            'volume_penjualan_teoritis' => 500,
            'rupiah_penjualan'          => 500 * 14000,
            'disetorkan'                => 500 * 14000,
            'selisih_setoran'           => 0,
            'created_at'                => '2026-03-10 16:00:00',
        ]);

        $period3 = $service->generate($this->shop->id, 3, 2026, $this->admin->id);
        $detail3 = $period3->details->firstWhere('operator_id', $this->operator->id);

        $this->assertEquals(200000, floatval($detail3->potongan_hutang));
        $deduction3 = PayrollLoanDeduction::where('payroll_period_id', $period3->id)->first();
        $this->assertEquals(3, $deduction3->cicilan_ke);
        $this->assertEquals(0, floatval($deduction3->sisa_pokok_setelah));

        $service->finalizePayrollLoans($period3);
        $period3->update(['status' => 'final']);

        $loan->refresh();
        $this->assertEquals(0, floatval($loan->sisa_pokok));
        $this->assertEquals(3, $loan->angsuran_ke);
        $this->assertEquals('lunas', $loan->status);

        // ===== BULAN 4: KASBON SUDAH LUNAS -> TIDAK ADA POTONGAN KASBON =====
        ShiftSchedule::create([
            'shop_id'     => $this->shop->id,
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-04-10',
            'shift_ke'    => 1,
            'jam_mulai'   => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);
        DailyReport::create([
            'shop_id'                   => $this->shop->id,
            'operator_id'               => $this->operator->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => 500,
            'volume_penjualan_teoritis' => 500,
            'rupiah_penjualan'          => 500 * 14000,
            'disetorkan'                => 500 * 14000,
            'selisih_setoran'           => 0,
            'created_at'                => '2026-04-10 16:00:00',
        ]);

        $period4 = $service->generate($this->shop->id, 4, 2026, $this->admin->id);
        $detail4 = $period4->details->firstWhere('operator_id', $this->operator->id);

        $this->assertEquals(0, floatval($detail4->potongan_hutang), 'Bulan ke-4 kasbon sudah lunas, tidak boleh dipotong');
        $this->assertEquals(2000000, floatval($detail4->take_home_pay), 'THP kembali utuh tanpa potongan kasbon');
        $this->assertEquals(0, PayrollLoanDeduction::where('payroll_period_id', $period4->id)->count());
    }
}
