<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\Investor;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShopInvestorSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Corporation $corp;
    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Super Admin Test',
            'email'    => 'admin.sync.' . uniqid() . '@pertashop.test',
            'role'     => 'super-admin',
            'password' => Hash::make('password123'),
        ]);

        $this->corp = Corporation::create([
            'nama'   => 'KPRI KOKARNABA BATURRADEN TEST',
            'alamat' => 'Baturraden',
        ]);

        $this->shop = Shop::create([
            'corporation_id' => $this->corp->id,
            'nama'           => 'Kemutug Lor Sync Test',
            'kode'           => 'KMTG-' . uniqid(),
            'alamat'         => 'Kemutug Lor',
            'stik_awal'      => 1000,
            'totalisator_awal' => 10000,
            'modal_awal'     => 470490000,
            'total_investasi'=> 470490000,
            'kapasitas'      => 6000,
            'skala'          => 1,
        ]);
    }

    public function test_updating_shop_modal_awal_automatically_resyncs_investor_nominals_and_total_investasi()
    {
        $this->actingAs($this->admin);

        // Buat 2 investor: 60% dan 40%
        $u1 = User::create(['name' => 'Investor 1', 'email' => 'inv1.' . uniqid() . '@test.com', 'role' => 'investor', 'password' => Hash::make('123')]);
        $inv1 = Investor::create(['user_id' => $u1->id, 'name' => 'Investor 1', 'nama_lengkap_gelar' => 'Investor 1', 'alamat_domisili' => 'Alamat', 'no_hp' => '081', 'nama_bank' => 'BCA', 'no_rekening' => '123', 'atas_nama_rekening' => 'Inv 1']);

        $u2 = User::create(['name' => 'Investor 2', 'email' => 'inv2.' . uniqid() . '@test.com', 'role' => 'investor', 'password' => Hash::make('123')]);
        $inv2 = Investor::create(['user_id' => $u2->id, 'name' => 'Investor 2', 'nama_lengkap_gelar' => 'Investor 2', 'alamat_domisili' => 'Alamat', 'no_hp' => '082', 'nama_bank' => 'BRI', 'no_rekening' => '456', 'atas_nama_rekening' => 'Inv 2']);

        $this->shop->investors()->attach([
            $inv1->id => ['persentase' => 60.00, 'nominal' => 282294000, 'investasi_awal' => 282294000],
            $inv2->id => ['persentase' => 40.00, 'nominal' => 188196000, 'investasi_awal' => 188196000],
        ]);

        // Pastikan nominal awal sesuai 470.490.000
        $this->assertEquals(470490000, $this->shop->investors->sum('pivot.nominal'));

        // Admin melakukan Edit Pertashop dan mengubah modal_awal menjadi 500.000.000
        $response = $this->put(route('shops.update', $this->shop->id), [
            'nama'                      => $this->shop->nama,
            'kode'                      => $this->shop->kode,
            'alamat'                    => $this->shop->alamat,
            'stik_awal'                 => 1000,
            'totalisator_awal'          => 10000,
            'corporation_id'            => $this->corp->id,
            'modal_awal'                => 500000000,
            'kapasitas'                 => 6000,
            'skala'                     => 1,
            'tanggal_mulai_operasional' => '2026-01-01',
        ]);

        $response->assertRedirect(route('shops.index'));

        $this->shop->refresh();

        // 1. Kolom modal_awal dan total_investasi pada tabel shops menjadi 500.000.000
        $this->assertEquals(500000000, floatval($this->shop->modal_awal));
        $this->assertEquals(500000000, floatval($this->shop->total_investasi));

        // 2. Nominal masing-masing investor pada tabel pivot otomatis terkalkulasi ulang
        $this->shop->load('investors');
        $p1 = $this->shop->investors->firstWhere('id', $inv1->id)->pivot;
        $p2 = $this->shop->investors->firstWhere('id', $inv2->id)->pivot;

        // 60% * 500.000.000 = 300.000.000
        $this->assertEquals(300000000, floatval($p1->nominal));
        $this->assertEquals(300000000, floatval($p1->investasi_awal));

        // 40% * 500.000.000 = 200.000.000
        $this->assertEquals(200000000, floatval($p2->nominal));
        $this->assertEquals(200000000, floatval($p2->investasi_awal));

        // 3. Akumulasi investor menjadi 500.000.000
        $this->assertEquals(500000000, floatval($this->shop->investors->sum('pivot.nominal')));
    }
}
