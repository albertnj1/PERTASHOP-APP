<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $SAL = Corporation::factory()->create([
            'nama' => 'PT Serayu Agung Mandiri',
        ]);

        $SPA = Corporation::factory()->create([
            'nama' => 'PT Sinergy Petrajaya Abadi',
        ]);

        $KKB = Corporation::factory()->create([
            'nama' => 'KPRI Kokanaba Baturraden',
        ]);

        $kalitapen = Shop::factory()->for($SAL)->create([
            'nama' => 'Kalitapen',
            'kode' => '4P.53119',
            'alamat' => 'Kel. Kalitapen Kec. Purwojati Kab. Banyumas',
        ]);

        $user1 = User::factory()->create([
            'name' => 'Andi',
            'role' => 'operator',
            'email' => 'operator1@kalitapen.com'
        ]);

        $user2 = User::factory()->create([
            'name' => 'Budi',
            'role' => 'operator',
            'email' => 'operator2@kalitapen.com'
        ]);

        $user3 = User::factory()->create(
            [
                'name' => 'Dwi Yuliarto',
                'role' => 'admin',
                'email' => 'admin@kalitapen.com'
            ]
        );

        Operator::factory()->for($kalitapen)->for($user1)->create();
        Operator::factory()->for($kalitapen)->for($user2)->create();
        Admin::factory()->for($kalitapen)->for($user3)->create();

        $kalibenda = Shop::factory()->for($SAL)->hasOperators(2)->create([
            'nama' => 'Kalibenda',
            'kode' => '4P.53134',
            'alamat' => 'Kel. Kalibenda Kec. Ajibarang Kab. Banyumas',
        ]);
        $pageralang = Shop::factory()->for($SAL)->hasOperators(2)->create([
            'nama' => 'Pageralang',
            'kode' => '4P.53164',
            'alamat' => 'Kel. Pageralang Kec. Kemranjen Kab. Banyumas',
        ]);

        $gumelar = Shop::factory()->for($SPA)->hasOperators(2)->create([
            'nama' => 'Gumelar',
            'kode' => '4P.53158',
            'alamat' => 'Kel. Gumelar Kec. Gumelar Kab. Banyumas',
        ]);

        $kemutug = Shop::factory()->for($KKB)->hasOperators(2)->create([
            'nama' => 'Kemutug Lor',
            'kode' => '4P.53143',
            'alamat' => 'Kel. Kemutug Lor Kec. Baturraden Kab. Banyumas',
        ]);

        $sumingkir = Shop::factory()->for($SAL)->hasOperators(2)->create([
            'nama' => 'Sumingkir',
            'kode' => '4P.532.40',
            'alamat' => 'Sumingkir',
        ]);

        User::factory()->create([
            'name' => 'Febri Sutomo',
            'email' => 'super-admin@gmail.com',
            'role' => 'super-admin'
        ]);

        #create user with role investor
        $koko = User::create([
            'name' => 'Koko Aribowo',
            'email' => 'koko@gmail.com',
            'role' => 'investor',
            'password' => Hash::make('123'),
        ]);

        $adlai = User::create([
            'name' => 'R. Adlai BT Kalapaaking',
            'email' => 'adlai@gmail.com',
            'role' => 'investor',
            'password' => Hash::make('123'),
        ]);

        $eko = User::create([
            'name' => 'Eko Cahyonoo',
            'email' => 'eko@gmail.com',
            'role' => 'investor',
            'password' => Hash::make('123'),
        ]);

        #create investor for user with role investor
        $investor_koko = $koko->investor()->create([
            'nama_bank' => 'BCA',
            'no_rekening' => '7510 6699 96',
            'atas_nama_rekening' => 'PT. TRIMITRA CIPTA KREASI',
            'no_hp' => '08123456789',
        ]);
        $investor_adlai = $adlai->investor()->create([
            'nama_bank' => 'Mandiri',
            'no_rekening' => '13900 2109 0000',
            'atas_nama_rekening' => 'R. ADLAI BT KALAPAAKING',
            'no_hp' => '08123456789',
        ]);
        $investor_eko = $eko->investor()->create([
            'nama_bank' => 'BCA',
            'no_rekening' => '4240 2645 82',
            'atas_nama_rekening' => 'EKO CAHYONO',
            'no_hp' => '08123456789',
        ]);

        #attach investor to shop
        #attach to shop kalitapen
        $investor_koko->shops()->attach($kalitapen, ['persentase' => 75]);
        $investor_adlai->shops()->attach($kalitapen, ['persentase' => 15]);
        $investor_eko->shops()->attach($kalitapen, ['persentase' => 10]);
        #attach to shop kalibenda

        Price::insert([
            [
                'harga_beli' => 8173.50,
                'harga_jual' => 9000,
                'created_at' => "2021-01-01"
            ],
            [
                'harga_beli' => 11682.30,
                'harga_jual' => 12500,
                'created_at' => "2021-04-01"
            ],
            [
                'harga_beli' => 13687.50,
                'harga_jual' => 14500,
                'created_at' => "2022-03-09"
            ],
            [
                'harga_beli' => 13085.95,
                'harga_jual' => 13900,
                'created_at' => "2022-10-01"
            ],
            [
                'harga_beli' => 13079.96,
                'harga_jual' => 13900,
                'created_at' => "2022-09-30"
            ],
            [
                'harga_beli' => 11977.59,
                'harga_jual' => 12800,
                'created_at' => "2023-01-03"
            ],
            [
                'harga_beli' => 12478.66,
                'harga_jual' => 13300,
                'created_at' => "2023-03-01"
            ],
            [
                'harga_beli' => 11676.94,
                'harga_jual' => 12500,
                'created_at' => "2023-06-01"
            ],
            [
                'harga_beli' => 12478.66,
                'harga_jual' => 13300,
                'created_at' => "2023-06-01"
            ],
            [
                'harga_beli'
            ]
        ]);


        Supplier::factory(3)->create();


        $purchase = Purchase::create([
            'no_so' => 123456,
            'shop_id' => $kalitapen->id,
            'supplier_id' => 1,
            'volume' => 2000,
            'created_at' => "2023-08-28",
            'total_bayar' => 2000 * Price::latest()->first()->harga_beli,
        ]);


        Incoming::create([
            'shop_id' => 1,
            'purchase_id' => $purchase->id,
            'operator_id' => 1,
            'sopir' => 'Andi',
            'no_polisi' => 'R 3242 JK',
            'volume' => 2000,
            'stik_awal' => 60,
            'stik_akhir' => 155.3,
            'created_at' => now()
        ]);

        SpendingCategory::insert([
            ['nama' => 'Gaji Operator'],
            ['nama' => 'Gaji Admin'],
            ['nama' => 'Ongkos Bongkar'],
            ['nama' => 'Biaya Transfer'],
            ['nama' => 'Fotocopy & ATK'],
            ['nama' => 'Listrik'],
            ['nama' => 'Air Bersih'],
            ['nama' => 'Cashback'],
            ['nama' => 'Internet']
        ]);

        SpendingCategory::create(['id' => 99, 'nama' => 'Lain-lain']);

        Spending::insert([
            [
                'shop_id' => $kalitapen->id,
                'operator_id' => 1,
                'created_at' => now(),
                'spending_category_id' => 3,
                'jumlah' => 10000
            ],
            [
                'shop_id' => $kalitapen->id,
                'operator_id' => 1,
                'created_at' => now(),
                'spending_category_id' => 5,
                'jumlah' => 1800
            ],
            [
                'shop_id' => $kalitapen->id,
                'operator_id' => 1,
                'created_at' => now(),
                'spending_category_id' => 6,
                'jumlah' => 105000
            ],
        ]);

        Spending::create([
            'shop_id' => $kalitapen->id,
            'operator_id' => 1,
            'created_at' => now(),
            'spending_category_id' => 99,
            'keterangan' => 'Seragam',
            'jumlah' => 110000
        ]);

        TestPump::create([
            'created_at' => now(),
            'totalisator_awal' => 120000,
            'totalisator_akhir' => 120010,
            'operator_id' => 1,
            'shop_id' => $kalitapen->id
        ]);

        \App\Models\DepositDestination::firstOrCreate([
            'name' => 'Sinergy'
        ], [
            'description' => 'Rekening Sinergy'
        ]);

        \App\Models\DepositDestination::firstOrCreate([
            'name' => 'Pak Yusuf'
        ], [
            'description' => 'Setoran Pak Yusuf'
        ]);
    }
}
