<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DepositCategory;

class DepositCategorySeeder extends Seeder
{
    /**
     * Seed universal deposit categories (Tunai, QRIS, Transfer Bank).
     */
    public function run(): void
    {
        $defaultCategories = [
            [
                'shop_id'                => null, // Global / Universal
                'nama_kategori'          => 'Tunai',
                'termasuk_dalam_setoran' => true,
            ],
            [
                'shop_id'                => null, // Global / Universal
                'nama_kategori'          => 'QRIS',
                'termasuk_dalam_setoran' => true,
            ],
            [
                'shop_id'                => null, // Global / Universal
                'nama_kategori'          => 'Transfer Bank',
                'termasuk_dalam_setoran' => true,
            ],
        ];

        foreach ($defaultCategories as $cat) {
            DepositCategory::firstOrCreate(
                ['nama_kategori' => $cat['nama_kategori'], 'shop_id' => $cat['shop_id']],
                $cat
            );
        }
    }
}
