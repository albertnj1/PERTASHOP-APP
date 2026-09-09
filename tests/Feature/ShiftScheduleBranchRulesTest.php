<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Shop;
use App\Models\ShiftSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ShiftScheduleBranchRulesTest extends TestCase
{
    use DatabaseTransactions;

    private function getOrCreateSuperAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin_test_shifts@pertashop.com'],
            [
                'name'     => 'Super Admin Test',
                'password' => bcrypt('password'),
                'role'     => 'super-admin',
            ]
        );
    }

    private function getOrCreateShop(string $nama): Shop
    {
        return Shop::firstOrCreate(
            ['nama' => $nama],
            [
                'lokasi'     => $nama . ' Location',
                'modal_awal' => 500000000,
            ]
        );
    }

    private function createOperatorForShop(Shop $shop, string $prefix): Operator
    {
        $user = User::create([
            'name'     => "Operator {$prefix} {$shop->nama}",
            'email'    => "op_{$prefix}_" . uniqid() . "@pertashop.com",
            'password' => bcrypt('password'),
            'role'     => 'operator',
        ]);

        return Operator::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);
    }

    public function test_shop_helper_identifies_shift_vs_non_shift_correctly(): void
    {
        $kemutug    = $this->getOrCreateShop('Kemutug Lor');
        $kalitapen  = $this->getOrCreateShop('Kalitapen');
        $kalibenda  = $this->getOrCreateShop('Kalibenda');
        $pageralang = $this->getOrCreateShop('Pageralang');
        $gumelar    = $this->getOrCreateShop('Gumelar');
        $sumingkir  = $this->getOrCreateShop('Sumingkir');

        // Non-shift checks
        $this->assertFalse($kemutug->isShiftBased());
        $this->assertFalse($kalitapen->isShiftBased());
        $this->assertFalse($kalibenda->isShiftBased());
        $this->assertFalse($pageralang->isShiftBased());

        // Shift checks
        $this->assertTrue($gumelar->isShiftBased());
        $this->assertTrue($sumingkir->isShiftBased());

        // Shift config checks for Sumingkir (2 shifts, 06:00-13:30 & 13:30-21:00)
        $sumingkirConfig = $sumingkir->getShiftConfig();
        $this->assertEquals(2, $sumingkirConfig['total_shifts']);
        $this->assertEquals('06:00:00', $sumingkirConfig['shifts'][1]['jam_mulai']);
        $this->assertEquals('13:30:00', $sumingkirConfig['shifts'][1]['jam_selesai']);
        $this->assertEquals(7.5, $sumingkirConfig['shifts'][1]['durasi_jam']);
        $this->assertEquals('13:30:00', $sumingkirConfig['shifts'][2]['jam_mulai']);
        $this->assertEquals('21:00:00', $sumingkirConfig['shifts'][2]['jam_selesai']);
        $this->assertEquals(7.5, $sumingkirConfig['shifts'][2]['durasi_jam']);
        $this->assertArrayNotHasKey(3, $sumingkirConfig['shifts']);

        // Shift config checks for Gumelar (2 shifts, 07:00-15:00 & 14:30-22:30)
        $gumelarConfig = $gumelar->getShiftConfig();
        $this->assertEquals(2, $gumelarConfig['total_shifts']);
        $this->assertEquals('07:00:00', $gumelarConfig['shifts'][1]['jam_mulai']);
        $this->assertEquals('15:00:00', $gumelarConfig['shifts'][1]['jam_selesai']);
        $this->assertEquals('14:30:00', $gumelarConfig['shifts'][2]['jam_mulai']);
        $this->assertEquals('22:30:00', $gumelarConfig['shifts'][2]['jam_selesai']);
        $this->assertArrayNotHasKey(3, $gumelarConfig['shifts']);
    }

    public function test_non_shift_branch_stores_daily_presence_and_rejects_bulk_shift_generator(): void
    {
        $admin = $this->getOrCreateSuperAdmin();
        $shop = $this->getOrCreateShop('Kemutug Lor');
        $operator = $this->createOperatorForShop($shop, 'KMTG');

        $tanggal = '2026-10-15';

        // 1. Simpan presensi harian
        $response = $this->actingAs($admin)->post(route('shift-schedules.store'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => $tanggal,
            'status'      => 'hadir',
            'keterangan'  => 'Masuk harian penuh',
        ]);

        $response->assertSessionHas('success');

        $schedule = ShiftSchedule::where('shop_id', $shop->id)
            ->where('operator_id', $operator->id)
            ->where('tanggal', $tanggal)
            ->first();

        $this->assertNotNull($schedule);
        $this->assertEquals(1, $schedule->shift_ke);
        $this->assertEquals('hadir', $schedule->status);
        $this->assertEquals('07:00:00', $schedule->jam_mulai);
        $this->assertEquals('21:00:00', $schedule->jam_selesai);

        // 2. Coba jalankan bulk generator pola shift di Kemutug Lor (harus ditolak)
        $bulkResponse = $this->actingAs($admin)->post(route('shift-schedules.bulk'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'bulan'       => '2026-10',
            'pola'        => '2p_2s_1l',
        ]);

        $bulkResponse->assertSessionHasErrors('shop_id');
    }

    public function test_sumingkir_enforces_two_shifts_hours_and_rejects_shift_3(): void
    {
        $admin = $this->getOrCreateSuperAdmin();
        $shop = $this->getOrCreateShop('Sumingkir');
        $operator = $this->createOperatorForShop($shop, 'SMGK');

        // 1. Store Shift 1 di Sumingkir (harus jam 06:00 - 13:30)
        $resp1 = $this->actingAs($admin)->post(route('shift-schedules.store'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-10-01',
            'shift_ke'    => 1,
            'status'      => 'dijadwalkan',
        ]);
        $resp1->assertSessionHas('success');

        $s1 = ShiftSchedule::where('operator_id', $operator->id)->where('tanggal', '2026-10-01')->first();
        $this->assertNotNull($s1);
        $this->assertEquals('06:00:00', $s1->jam_mulai);
        $this->assertEquals('13:30:00', $s1->jam_selesai);

        // 2. Store Shift 2 di Sumingkir (harus jam 13:30 - 21:00)
        $resp2 = $this->actingAs($admin)->post(route('shift-schedules.store'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-10-02',
            'shift_ke'    => 2,
            'status'      => 'dijadwalkan',
        ]);
        $resp2->assertSessionHas('success');

        $s2 = ShiftSchedule::where('operator_id', $operator->id)->where('tanggal', '2026-10-02')->first();
        $this->assertNotNull($s2);
        $this->assertEquals('13:30:00', $s2->jam_mulai);
        $this->assertEquals('21:00:00', $s2->jam_selesai);

        // 3. Store Shift 3 di Sumingkir (HARUS DITOLAK karena Sumingkir hanya 2 shift)
        $resp3 = $this->actingAs($admin)->post(route('shift-schedules.store'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-10-03',
            'shift_ke'    => 3,
            'status'      => 'dijadwalkan',
        ]);
        $resp3->assertSessionHasErrors('shift_ke');

        // 4. Bulk generator di Sumingkir berhasil
        $bulkResp = $this->actingAs($admin)->post(route('shift-schedules.bulk'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'bulan'       => '2026-10',
            'pola'        => '2p_2s_1l',
        ]);
        $bulkResp->assertSessionHas('success');
    }

    public function test_ui_view_renders_conditional_elements_per_branch(): void
    {
        $admin = $this->getOrCreateSuperAdmin();
        $kemutug = $this->getOrCreateShop('Kemutug Lor');
        $sumingkir = $this->getOrCreateShop('Sumingkir');

        // 1. Check Kemutug Lor UI
        $resKemutug = $this->actingAs($admin)->get(route('shift-schedules.index', ['shop_id' => $kemutug->id]));
        $resKemutug->assertStatus(200);
        $resKemutug->assertSee('Jam Kerja Reguler Harian (Non-Shift)');
        $resKemutug->assertSee('Presensi & Jadwal Harian', false);
        $resKemutug->assertDontSee('Generate Pola Shift Otomatis');
        $resKemutug->assertDontSee('3 = Malam');

        // 2. Check Sumingkir UI
        $resSumingkir = $this->actingAs($admin)->get(route('shift-schedules.index', ['shop_id' => $sumingkir->id]));
        $resSumingkir->assertStatus(200);
        $resSumingkir->assertSee('Generate Pola Shift Otomatis');
        $resSumingkir->assertSee('06:00 - 13:30');
        $resSumingkir->assertSee('13:30 - 21:00');
        $resSumingkir->assertDontSee('3 = Malam');
    }
}
