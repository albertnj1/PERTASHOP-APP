<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Shop;
use App\Models\User;
use App\Models\Investor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$data = [
    // 1. Kemutug Lor
    [
        'shop_kode' => '4P.53143', 
        'total_investasi' => 470490000,
        'investors' => [
            [
                'name' => 'Koperasi Kokarnaba',
                'persentase' => 51.00,
                'nominal' => 239949900,
                'sub_investors' => [
                    ['name' => 'KPPI Kokarnaba', 'persentase' => 22.32, 'nominal' => 105000000],
                    ['name' => 'DUL SUKUR', 'persentase' => 4.25, 'nominal' => 20000000],
                    ['name' => 'ASTRI LIANA MONIKA', 'persentase' => 3.19, 'nominal' => 15000000],
                    ['name' => 'SUPENDI', 'persentase' => 21.24, 'nominal' => 99949000],
                ]
            ],
            ['name' => 'Victor Edward Asrikin', 'persentase' => 15.00, 'nominal' => 70573500],
            ['name' => 'Adlai Budiarto K.', 'persentase' => 12.00, 'nominal' => 56458800],
            ['name' => 'Koko Aribowo', 'persentase' => 12.00, 'nominal' => 56458800],
            ['name' => 'Kaswari', 'persentase' => 5.00, 'nominal' => 23524500],
            ['name' => 'Sugiyanto Kosim S.', 'persentase' => 5.00, 'nominal' => 23524500],
        ]
    ],
    // 2. Kalitapen
    [
        'shop_kode' => '4P.53119',
        'total_investasi' => 460000000,
        'investors' => [
            ['name' => 'Adlai Budiarto K. / PT.SAM', 'persentase' => 70.00, 'nominal' => 322000000],
            ['name' => 'Victor Edward Asrikin', 'persentase' => 15.00, 'nominal' => 69000000],
            ['name' => 'Koko Aribowo', 'persentase' => 5.00, 'nominal' => 23000000],
            ['name' => 'Kaswari', 'persentase' => 5.00, 'nominal' => 23000000],
            ['name' => 'Sugiyanto Kosim S.', 'persentase' => 5.00, 'nominal' => 23000000],
        ]
    ],
    // 3. Kalibenda
    [
        'shop_kode' => '4P.53134',
        'total_investasi' => 408554000,
        'investors' => [
            ['name' => 'Koko Aribowo', 'persentase' => 65.00, 'nominal' => 265560100],
            ['name' => 'Victor Edward Asrikin', 'persentase' => 15.00, 'nominal' => 61283100],
            ['name' => 'Adlai Budiarto K. / PT.SAM', 'persentase' => 10.00, 'nominal' => 40855400],
            ['name' => 'Kaswari', 'persentase' => 5.00, 'nominal' => 20427700],
            ['name' => 'Sugiyanto Kosim S.', 'persentase' => 5.00, 'nominal' => 20427700],
        ]
    ],
    // 4. Pageralang
    [
        'shop_kode' => '4P.53164',
        'total_investasi' => 500000000,
        'investors' => [
            ['name' => 'Adlai Budiarto K. / PT.SAM', 'persentase' => 80.00, 'nominal' => 400000000],
            ['name' => 'Sultoni', 'persentase' => 15.00, 'nominal' => 75000000],
            ['name' => 'Dwiyuliarto', 'persentase' => 3.00, 'nominal' => 15000000, 'notes' => 'Saham Hibah'],
            ['name' => 'Sudarko', 'persentase' => 2.00, 'nominal' => 10000000, 'notes' => 'Saham Hibah'],
        ]
    ],
    // 5. Gumelar
    [
        'shop_kode' => '4P.53158',
        'total_investasi' => 500000000,
        'investors' => [
            ['name' => 'Koko Aribowo', 'persentase' => 90.00, 'nominal' => 450000000],
            ['name' => 'Eko Cahyono', 'persentase' => 10.00, 'nominal' => 50000000, 'notes' => 'Saham Hibah'],
        ]
    ],
    // 6. Sumingkir (CV Kina Berkah Mandiri)
    [
        'shop_kode' => '4P.532.40', // Sumingkir
        'total_investasi' => 30000000,
        'investors' => [
            ['name' => 'Adlai Budiarto K. / PT.SAM', 'persentase' => 45.00, 'nominal' => 13500000],
            ['name' => 'CV Kina Berkah Mandiri', 'persentase' => 35.00, 'nominal' => 10500000],
            ['name' => 'Dwiyuliarto', 'persentase' => 10.00, 'nominal' => 3000000],
            ['name' => 'BumDes Sumingkir', 'persentase' => 10.00, 'nominal' => 3000000],
        ]
    ]
];

DB::beginTransaction();
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    DB::table('investor_shop')->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    foreach ($data as $shopData) {
        $shop = Shop::where('kode', $shopData['shop_kode'])->first();
        if (!$shop) {
            echo "Warning: Shop with kode {$shopData['shop_kode']} not found. Skipping.\n";
            continue;
        }

        // Update shop total investasi
        $shop->total_investasi = $shopData['total_investasi'];
        $shop->save();

        foreach ($shopData['investors'] as $invData) {
            $name = $invData['name'];
            $email = Str::slug($name) . '@investor.com';
            
            // Find or create User
            $user = User::where('name', $name)->orWhere('email', $email)->first();
            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'investor'
                ]);
            }
            
            // Find or create Investor Profile
            $investor = Investor::firstOrCreate(['user_id' => $user->id]);

            // Append "Saham Hibah" to sub_investors if there's a note
            $subInvestors = isset($invData['sub_investors']) ? $invData['sub_investors'] : null;
            if (isset($invData['notes']) && $invData['notes'] == 'Saham Hibah') {
                $subInvestors = [['name' => 'Saham Hibah']]; // A hacky way to store the note so we can display it later
            }

            // Attach to shop
            DB::table('investor_shop')->insert([
                'investor_id' => $investor->id,
                'shop_id' => $shop->id,
                'persentase' => $invData['persentase'],
                'nominal' => $invData['nominal'],
                'sub_investors' => $subInvestors ? json_encode($subInvestors) : null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        echo "Data inserted for {$shop->nama} ({$shop->kode})\n";
    }

    DB::commit();
    echo "All data seeded successfully!\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
