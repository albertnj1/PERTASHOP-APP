<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\Operator;
use App\Models\PeriodLock;
use App\Models\Price;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MonthlyRecapDynamicTabAndValidationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected Shop $shop;
    protected Operator $operator;
    protected Price $price;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::where('role', 'super-admin')->first()
            ?? User::factory()->create(['role' => 'super-admin']);

        $corp = \App\Models\Corporation::first()
            ?? \App\Models\Corporation::create(['nama' => 'PT Test Corp', 'alamat' => 'Alamat']);

        $uniqueCode = 'TEST.' . rand(10000, 99999);
        $this->shop = Shop::create([
            'corporation_id'   => $corp->id,
            'kode'             => $uniqueCode,
            'nama'             => 'Outlet Test ' . $uniqueCode,
            'alamat'           => 'Jl. Raya Test',
            'skala'            => 21.49,
            'stik_awal'        => 100,
            'totalisator_awal' => 500000,
            'modal_awal'       => 60000000,
        ]);

        $this->operator = Operator::create([
            'user_id' => $this->superAdmin->id,
            'shop_id' => $this->shop->id,
        ]);

        $this->price = Price::create([
            'shop_id'      => $this->shop->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'status'       => true,
            'effective_at' => '2026-01-01',
        ]);
    }

    public function test_monitoring_daily_reports_page_renders_dynamic_tabs(): void
    {
        $this->actingAs($this->superAdmin);

        // Buat 1 daily report
        DailyReport::create([
            'shop_id'           => $this->shop->id,
            'operator_id'       => $this->operator->id,
            'price_id'          => $this->price->id,
            'report_date'       => '2026-05-01',
            'totalisator_awal'  => 1000,
            'totalisator_akhir' => 1500,
            'stik_awal'         => 80,
            'stik_akhir'        => 75,
            'stok_awal'         => 1680,
            'disetorkan'        => 5000000,
            'created_at'        => '2026-05-01 17:00:00',
        ]);

        $response = $this->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
            'month'   => 5,
            'year'    => 2026,
        ]));

        $response->assertStatus(200);

        // Pastikan Tab 1 dan Tab 2 ada di halaman
        $response->assertSee('Pratinjau Rekap Bulanan (49 Kolom Excel)');
        $response->assertSee('Daftar Laporan Harian (Operasional Input)');
        $response->assertSee('id="monitoringTabs"', false);
        $response->assertSee('id="tab-recap"', false);
        $response->assertSee('id="tab-daily"', false);

        // Status awal: Belum divalidasi
        $response->assertSee('BELUM DIVALIDASI (DRAFT)');
        $response->assertSee('Validasi &amp; Kunci Bulan Ini', false);
    }

    public function test_validate_period_locks_month_and_prevents_edits(): void
    {
        $this->actingAs($this->superAdmin);

        $report = DailyReport::create([
            'shop_id'           => $this->shop->id,
            'operator_id'       => $this->operator->id,
            'price_id'          => $this->price->id,
            'report_date'       => '2026-05-02',
            'totalisator_awal'  => 1500,
            'totalisator_akhir' => 2000,
            'stik_awal'         => 75,
            'stik_akhir'        => 70,
            'stok_awal'         => 1180,
            'disetorkan'        => 6000000,
            'status_lifecycle'  => 'draft',
            'created_at'        => '2026-05-02 17:00:00',
        ]);

        // POST Validasi & Kunci Periode
        $postRes = $this->post(route('monitoring.daily-reports.validate-period'), [
            'shop_id' => $this->shop->id,
            'month'   => 5,
            'year'    => 2026,
            'action'  => 'lock',
        ]);

        $postRes->assertRedirect(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
            'month'   => 5,
            'year'    => 2026,
        ]));

        // Cek bahwa PeriodLock aktif
        $this->assertTrue(PeriodLock::isLocked($this->shop->id, '2026-05'));

        // Cek bahwa status_lifecycle berubah menjadi verified
        $report->refresh();
        $this->assertEquals('verified', $report->status_lifecycle);

        // Verifikasi bahwa Observer menolak pengeditan saat terkunci
        $this->expectException(\RuntimeException::class);
        $report->update(['totalisator_akhir' => 2500]);
    }

    public function test_export_pdf_requires_validation_first(): void
    {
        $this->actingAs($this->superAdmin);

        // Pastikan periode belum terkunci
        PeriodLock::where('shop_id', $this->shop->id)->where('year_month', '2026-05')->delete();

        // Coba unduh PDF saat belum divalidasi -> Harus redirect dan pesan error
        $response = $this->get(route('monitoring.daily-reports.export-pdf', [
            'shop_id' => $this->shop->id,
            'month'   => 5,
            'year'    => 2026,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Kunci periode
        PeriodLock::create([
            'shop_id'    => $this->shop->id,
            'year_month' => '2026-05',
            'is_locked'  => true,
            'locked_at'  => now(),
            'locked_by'  => $this->superAdmin->id,
        ]);

        // Coba unduh PDF saat sudah divalidasi -> Harus berhasil 200 dan menghasilkan PDF
        $pdfRes = $this->get(route('monitoring.daily-reports.export-pdf', [
            'shop_id' => $this->shop->id,
            'month'   => 5,
            'year'    => 2026,
        ]));

        $pdfRes->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfRes->headers->get('content-type'));
        $this->assertStringContainsString('REKAP_RESMI_', $pdfRes->headers->get('content-disposition'));
    }

    public function test_reopen_period_unlocks_month(): void
    {
        $this->actingAs($this->superAdmin);

        // Kunci dulu
        PeriodLock::updateOrCreate(
            ['shop_id' => $this->shop->id, 'year_month' => '2026-05'],
            ['is_locked' => true, 'locked_at' => now(), 'locked_by' => $this->superAdmin->id]
        );
        $this->assertTrue(PeriodLock::isLocked($this->shop->id, '2026-05'));

        // POST Unlock / Reopen
        $unlockRes = $this->post(route('monitoring.daily-reports.validate-period'), [
            'shop_id' => $this->shop->id,
            'month'   => 5,
            'year'    => 2026,
            'action'  => 'unlock',
        ]);

        $unlockRes->assertRedirect();
        $this->assertFalse(PeriodLock::isLocked($this->shop->id, '2026-05'));
    }

    public function test_unpopulated_month_has_zero_losses_and_gain(): void
    {
        // Bulan Juni 2026 (sama sekali belum ada laporan harian)
        $recap = \App\Services\PertashopDailyRecapService::getMonthlyRecap($this->shop->id, 6, 2026);

        $tot = $recap['summary']['row_total_35'];
        $this->assertEquals(0.0, $tot['T_losses_gain_liter'], 'Bulan tanpa laporan harian tidak boleh menghasilkan kerugian semu!');
        $this->assertEquals(0.0, $tot['U_losses_gain_rp'], 'Nominal rupiah HPP losses harus 0!');
        $this->assertEquals('Pas', $tot['V_status_losses_gain']);

        // Pastikan di view monitoring tidak menampilkan nilai minus absurd
        $this->actingAs($this->superAdmin);
        $res = $this->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
            'month'   => 6,
            'year'    => 2026,
        ]));

        $res->assertStatus(200);
        $res->assertDontSee('-104.995');
        $res->assertDontSee('-1.310.206.890');
    }
}
