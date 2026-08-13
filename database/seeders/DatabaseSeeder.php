<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use App\Models\Price;
use App\Models\Incoming;
use App\Models\Investor;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\TestPump;
use App\Models\Corporation;
use App\Models\Pengeluaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ReportController;
use App\Models\Admin;
use App\Models\Operator;
use App\Models\Spending;
use App\Models\SpendingCategory;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. CORPORATIONS
        $corpSAL = Corporation::firstOrCreate(
            ['nama' => 'PT Serayu Agung Mandiri'],
            ['nama' => 'PT Serayu Agung Mandiri', 'alamat' => 'Banyumas']
        );

        $corpSPA = Corporation::firstOrCreate(
            ['nama' => 'CV SINERGY PETRAJAYA ABADI'],
            ['nama' => 'CV SINERGY PETRAJAYA ABADI', 'alamat' => 'Gumelar, Banyumas']
        );

        $corpKKB = Corporation::firstOrCreate(
            ['nama' => 'KPRI KOKARNABA BATURRADEN'],
            ['nama' => 'KPRI KOKARNABA BATURRADEN', 'alamat' => 'Baturraden, Banyumas']
        );

        $corpKBM = Corporation::firstOrCreate(
            ['nama' => 'CV KINA BERKAH MANDIRI'],
            ['nama' => 'CV KINA BERKAH MANDIRI', 'alamat' => 'Sumingkir, Banyumas']
        );

        // Helper for Investors
        $getOrCreateInvestor = function($name) {
            $slug = Str::slug($name);
            $email = $slug . '@investor.pertashop.com';
            
            $user = User::where('name', $name)->orWhere('email', $email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'role' => 'investor',
                    'password' => Hash::make('password123'),
                ]);
            } else {
                $user->update(['name' => $name, 'role' => 'investor']);
            }

            return Investor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_bank' => 'BCA',
                    'no_rekening' => '0000000000',
                    'atas_nama_rekening' => strtoupper($name),
                    'no_hp' => '08123456789'
                ]
            );
        };

        // Helper for Operators
        $syncShopOperators = function($shop, array $operatorNames) {
            $existingOps = Operator::where('shop_id', $shop->id)->orderBy('id', 'asc')->get();
            foreach ($operatorNames as $index => $opName) {
                $slug = Str::slug($opName);
                $email = $slug . '.' . Str::slug($shop->nama) . '@operator.pertashop.com';

                $user = User::where('name', $opName)->orWhere('email', $email)->first();

                if (!$user) {
                    $user = User::create([
                        'name' => $opName,
                        'email' => $email,
                        'role' => 'operator',
                        'password' => Hash::make('password123'),
                    ]);
                } else {
                    $user->update(['name' => $opName, 'role' => 'operator']);
                }

                if (isset($existingOps[$index])) {
                    $existingOps[$index]->update([
                        'user_id' => $user->id,
                        'shop_id' => $shop->id,
                    ]);
                } else {
                    Operator::create([
                        'shop_id' => $shop->id,
                        'user_id' => $user->id,
                        'no_hp' => '08123456789',
                        'alamat' => $shop->alamat ?? 'Alamat Operator',
                    ]);
                }
            }
        };

        // Helper for Shop Investor Pivot Sync
        $syncShopInvestors = function($shop, array $investorSpecs) use ($getOrCreateInvestor) {
            $pivotData = [];
            foreach ($investorSpecs as $spec) {
                $investor = $getOrCreateInvestor($spec['name']);
                $pivotData[$investor->id] = [
                    'persentase' => $spec['persentase'],
                    'nominal' => $spec['nominal'],
                    'investasi_awal' => $spec['nominal'],
                    'sub_investors' => isset($spec['sub_investors']) ? json_encode($spec['sub_investors']) : null,
                    'keterangan' => $spec['keterangan'] ?? null,
                ];
            }
            $shop->investors()->sync($pivotData);
        };

        // --- SUPER ADMIN ---
        User::firstOrCreate(
            ['email' => 'super-admin@pertashop.com'],
            [
                'name' => 'ALBERT NESTOR J',
                'role' => 'super-admin',
                'password' => Hash::make('superadmin123*')
            ]
        );
        User::firstOrCreate(
            ['email' => 'super-admin@gmail.com'],
            [
                'name' => 'ALBERT NESTOR J',
                'role' => 'super-admin',
                'password' => Hash::make('superadmin123*')
            ]
        );

        // 1. KEMUTUG LOR — 4P.53143
        $kemutug = Shop::firstOrCreate(
            ['kode' => '4P.53143'],
            [
                'nama' => 'Kemutug Lor',
                'corporation_id' => $corpKKB->id,
                'alamat' => 'Kel. Kemutug Lor Kec. Baturraden Kab. Banyumas',
                'total_investasi' => 470490000,
                'modal_awal' => 470490000,
            ]
        );
        $kemutug->update(['total_investasi' => 470490000, 'modal_awal' => 470490000, 'corporation_id' => $corpKKB->id]);
        $syncShopInvestors($kemutug, [
            [
                'name' => 'Koperasi Kokarnaba',
                'persentase' => 51.00,
                'nominal' => 239949900,
                'sub_investors' => [
                    ['nama' => 'KPPI Kokarnaba', 'persentase' => 22.32, 'nominal' => 105000000],
                    ['nama' => 'DUL SUKUR', 'persentase' => 4.25, 'nominal' => 20000000],
                    ['nama' => 'ASTRI LIANA MONIKA', 'persentase' => 3.19, 'nominal' => 15000000],
                    ['nama' => 'SUPENDI', 'persentase' => 21.24, 'nominal' => 99949000]
                ]
            ],
            ['name' => 'Victor Edward Asrikin', 'persentase' => 15.00, 'nominal' => 70573500],
            ['name' => 'Adlai Budiarto K.', 'persentase' => 12.00, 'nominal' => 56458800],
            ['name' => 'Koko Aribowo', 'persentase' => 12.00, 'nominal' => 56458800],
            ['name' => 'Kaswari', 'persentase' => 5.00, 'nominal' => 23524500],
            ['name' => 'Sugiyanto Kosim S.', 'persentase' => 5.00, 'nominal' => 23524500],
        ]);
        $syncShopOperators($kemutug, ['Dendi Supriyatno', 'Wawan']);

        // 2. KALITAPEN — 4P.53119
        $kalitapen = Shop::firstOrCreate(
            ['kode' => '4P.53119'],
            [
                'nama' => 'Kalitapen',
                'corporation_id' => $corpSAL->id,
                'alamat' => 'Kel. Kalitapen Kec. Purwojati Kab. Banyumas',
                'total_investasi' => 460000000,
                'modal_awal' => 460000000,
            ]
        );
        $kalitapen->update(['total_investasi' => 460000000, 'modal_awal' => 460000000, 'corporation_id' => $corpSAL->id]);
        $syncShopInvestors($kalitapen, [
            ['name' => 'Adlai Budiarto K.', 'persentase' => 70.00, 'nominal' => 322000000],
            ['name' => 'Victor Edward Asrikin', 'persentase' => 15.00, 'nominal' => 69000000],
            ['name' => 'Koko Aribowo', 'persentase' => 5.00, 'nominal' => 23000000],
            ['name' => 'Kaswari', 'persentase' => 5.00, 'nominal' => 23000000],
            ['name' => 'Sugiyanto Kosim S.', 'persentase' => 5.00, 'nominal' => 23000000],
        ]);
        $syncShopOperators($kalitapen, ['Muhammad Aulia Perdana']);

        // 3. KALIBENDA — 4P.53134
        $kalibenda = Shop::firstOrCreate(
            ['kode' => '4P.53134'],
            [
                'nama' => 'Kalibenda',
                'corporation_id' => $corpSAL->id,
                'alamat' => 'Kel. Kalibenda Kec. Ajibarang Kab. Banyumas',
                'total_investasi' => 408554000,
                'modal_awal' => 408554000,
            ]
        );
        $kalibenda->update(['total_investasi' => 408554000, 'modal_awal' => 408554000, 'corporation_id' => $corpSAL->id]);
        $syncShopInvestors($kalibenda, [
            ['name' => 'Koko Aribowo', 'persentase' => 65.00, 'nominal' => 265560100],
            ['name' => 'Victor Edward Asrikin', 'persentase' => 15.00, 'nominal' => 61283100],
            ['name' => 'Adlai Budiarto K.', 'persentase' => 10.00, 'nominal' => 40855400],
            ['name' => 'Kaswari', 'persentase' => 5.00, 'nominal' => 20427700],
            ['name' => 'Sugiyanto Kosim S.', 'persentase' => 5.00, 'nominal' => 20427700],
        ]);
        $syncShopOperators($kalibenda, ['Kaka Primadani']);

        // 4. PAGERALANG — 4P.53164
        $pageralang = Shop::firstOrCreate(
            ['kode' => '4P.53164'],
            [
                'nama' => 'Pageralang',
                'corporation_id' => $corpSAL->id,
                'alamat' => 'Kel. Pageralang Kec. Kemranjen Kab. Banyumas',
                'total_investasi' => 500000000,
                'modal_awal' => 500000000,
            ]
        );
        $pageralang->update(['total_investasi' => 500000000, 'modal_awal' => 500000000, 'corporation_id' => $corpSAL->id]);
        $syncShopInvestors($pageralang, [
            ['name' => 'Adlai Budiarto K.', 'persentase' => 80.00, 'nominal' => 400000000],
            ['name' => 'Sultoni', 'persentase' => 15.00, 'nominal' => 75000000],
            ['name' => 'Dwiyuliarto', 'persentase' => 3.00, 'nominal' => 15000000, 'keterangan' => 'Saham Hibah'],
            ['name' => 'Sudarko', 'persentase' => 2.00, 'nominal' => 10000000, 'keterangan' => 'Saham Hibah'],
        ]);
        $syncShopOperators($pageralang, ['Rian Rizky Milliarto']);

        // 5. GUMELAR — 4P.53158
        $gumelar = Shop::firstOrCreate(
            ['kode' => '4P.53158'],
            [
                'nama' => 'Gumelar',
                'corporation_id' => $corpSPA->id,
                'alamat' => 'Kel. Gumelar Kec. Gumelar Kab. Banyumas',
                'total_investasi' => 500000000,
                'modal_awal' => 500000000,
            ]
        );
        $gumelar->update(['total_investasi' => 500000000, 'modal_awal' => 500000000, 'corporation_id' => $corpSPA->id]);
        $syncShopInvestors($gumelar, [
            ['name' => 'Koko Aribowo', 'persentase' => 90.00, 'nominal' => 450000000],
            ['name' => 'Eko Cahyono', 'persentase' => 10.00, 'nominal' => 50000000, 'keterangan' => 'Saham Hibah'],
        ]);
        $syncShopOperators($gumelar, ['Wiki Triono', 'Erik Susanto', 'Andre Dwi Prasetiyo']);

        // 6. SUMINGKIR — 4P.532.40
        $sumingkir = Shop::firstOrCreate(
            ['kode' => '4P.532.40'],
            [
                'nama' => 'Sumingkir',
                'corporation_id' => $corpKBM->id,
                'alamat' => 'Sumingkir',
                'total_investasi' => 30000000,
                'modal_awal' => 30000000,
            ]
        );
        $sumingkir->update(['total_investasi' => 30000000, 'modal_awal' => 30000000, 'corporation_id' => $corpKBM->id]);
        $syncShopInvestors($sumingkir, [
            ['name' => 'Adlai Budiarto K.', 'persentase' => 45.00, 'nominal' => 13500000],
            ['name' => 'CV Kina Berkah Mandiri', 'persentase' => 35.00, 'nominal' => 10500000],
            ['name' => 'Dwiyuliarto', 'persentase' => 10.00, 'nominal' => 3000000],
            ['name' => 'BumDes Sumingkir', 'persentase' => 10.00, 'nominal' => 3000000],
        ]);
        $syncShopOperators($sumingkir, ['Muhammad Nur Aziz', 'Ahmad Syarifudin']);

        // --- SEED PRICES ---
        if (Price::count() == 0) {
            Price::insert([
                ['harga_beli' => 8173.50, 'harga_jual' => 9000, 'effective_at' => "2021-01-01 00:00:00", 'created_at' => "2021-01-01 00:00:00"],
                ['harga_beli' => 11682.30, 'harga_jual' => 12500, 'effective_at' => "2021-04-01 00:00:00", 'created_at' => "2021-04-01 00:00:00"],
                ['harga_beli' => 13687.50, 'harga_jual' => 14500, 'effective_at' => "2022-03-09 00:00:00", 'created_at' => "2022-03-09 00:00:00"],
                ['harga_beli' => 13085.95, 'harga_jual' => 13900, 'effective_at' => "2022-10-01 00:00:00", 'created_at' => "2022-10-01 00:00:00"],
                ['harga_beli' => 13079.96, 'harga_jual' => 13900, 'effective_at' => "2022-09-30 00:00:00", 'created_at' => "2022-09-30 00:00:00"],
                ['harga_beli' => 11977.59, 'harga_jual' => 12800, 'effective_at' => "2023-01-03 00:00:00", 'created_at' => "2023-01-03 00:00:00"],
                ['harga_beli' => 12478.66, 'harga_jual' => 13300, 'effective_at' => "2023-03-01 00:00:00", 'created_at' => "2023-03-01 00:00:00"],
                ['harga_beli' => 11676.94, 'harga_jual' => 12500, 'effective_at' => "2023-06-01 00:00:00", 'created_at' => "2023-06-01 00:00:00"],
                ['harga_beli' => 12478.66, 'harga_jual' => 13300, 'effective_at' => "2023-06-01 00:00:01", 'created_at' => "2023-06-01 00:00:01"]
            ]);
        }
    }
}
