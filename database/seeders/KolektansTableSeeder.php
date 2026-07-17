<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KolektansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Kolektan::create([
            'nama_kolektan' => 'Kolektan Pertamina',
            'pin' => '123456'
        ]);
    }
}
