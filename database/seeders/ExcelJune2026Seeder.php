<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExcelJune2026Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clean any existing reports in June 2026
        DB::table('spendings')->where('created_at', '>=', '2026-06-01')->delete();
        DB::table('incomings')->where('created_at', '>=', '2026-06-01')->delete();
        DB::table('purchases')->where('created_at', '>=', '2026-06-01')->delete();
        DB::table('daily_report_periods')->where('created_at', '>=', '2026-05-31')->delete();
        DB::table('daily_reports')->where('created_at', '>=', '2026-05-31')->delete();

        // 2. Align and Update Prices Table
        DB::table('prices')->where('id', 16)->update([
            'effective_at' => '2026-06-01 00:00:00',
            'created_at' => '2026-06-01 00:00:00'
        ]);
        DB::table('prices')->where('id', 17)->update([
            'effective_at' => '2026-06-10 00:00:00',
            'created_at' => '2026-06-10 00:00:00'
        ]);
        DB::table('prices')->where('id', 18)->update([
            'effective_at' => '2026-06-18 00:00:00',
            'created_at' => '2026-06-18 00:00:00'
        ]);
        DB::table('prices')->where('id', 19)->update([
            'effective_at' => '2026-06-18 00:00:00',
            'created_at' => '2026-06-18 00:00:00'
        ]);
        DB::table('prices')->where('id', 20)->update([
            'effective_at' => '2026-06-18 00:00:00',
            'created_at' => '2026-06-18 00:00:00'
        ]);
        DB::table('prices')->where('id', 21)->update([
            'effective_at' => '2026-06-10 00:00:00',
            'created_at' => '2026-06-10 00:00:00'
        ]);
        DB::table('prices')->where('id', 22)->update([
            'effective_at' => '2026-06-10 00:00:00',
            'created_at' => '2026-06-10 00:00:00'
        ]);
        
        // Seed Pageralang specific price if not exist
        DB::table('prices')->updateOrInsert(
            ['shop_id' => 3, 'effective_at' => '2026-06-18 00:00:00'],
            [
                'harga_beli' => 15050,
                'harga_jual' => 15900,
                'created_at' => '2026-06-18 00:00:00',
                'updated_at' => '2026-06-18 00:00:00'
            ]
        );

        // 3. Seed Pageralang (shop_id = 3, operator_id = 3)
        // 31 Mei (to establish starting stock and starting totalisator)
        $p1 = DB::table('daily_reports')->insertGetId([
            'shop_id' => 3,
            'operator_id' => 3,
            'price_id' => 16,
            'totalisator_akhir' => 289828.837,
            'stik_akhir' => 102.90, // 2208.234 / 21.46
            'setor_tunai' => 0,
            'diverifikasi' => 1,
            'created_at' => '2026-05-31 17:00:00',
            'updated_at' => '2026-05-31 17:00:00'
        ]);
        // 01 Juni
        $p2 = DB::table('daily_reports')->insertGetId([
            'shop_id' => 3,
            'operator_id' => 3,
            'price_id' => 16,
            'totalisator_akhir' => 290054.489,
            'stik_akhir' => 91.99, // 1974.320 / 21.46
            'setor_tunai' => 2752954,
            'diverifikasi' => 1,
            'created_at' => '2026-06-01 17:00:00',
            'updated_at' => '2026-06-01 17:00:00'
        ]);
        // 02 Juni
        $p3 = DB::table('daily_reports')->insertGetId([
            'shop_id' => 3,
            'operator_id' => 3,
            'price_id' => 16,
            'totalisator_akhir' => 290324.875,
            'stik_akhir' => 79.20, // 1699.632 / 21.46
            'setor_tunai' => 3298709,
            'diverifikasi' => 1,
            'created_at' => '2026-06-02 17:00:00',
            'updated_at' => '2026-06-02 17:00:00'
        ]);


        // 4. Seed Sumingkir (shop_id = 2, operator_id = 2)
        // 31 Mei (to establish starting stock and starting totalisator)
        $s1 = DB::table('daily_reports')->insertGetId([
            'shop_id' => 2,
            'operator_id' => 2,
            'price_id' => 16,
            'totalisator_akhir' => 264846.867,
            'stik_akhir' => 43.00, // 903 / 21.00
            'setor_tunai' => 0,
            'diverifikasi' => 1,
            'created_at' => '2026-05-31 17:00:00',
            'updated_at' => '2026-05-31 17:00:00'
        ]);
        // 01 Juni (DO 2000L and Spending 106.000)
        $s2 = DB::table('daily_reports')->insertGetId([
            'shop_id' => 2,
            'operator_id' => 2,
            'price_id' => 16,
            'totalisator_akhir' => 265324.506,
            'stik_akhir' => 114.30, // 2400.300 / 21.00
            'setor_tunai' => 5721196,
            'diverifikasi' => 1,
            'created_at' => '2026-06-01 17:00:00',
            'updated_at' => '2026-06-01 17:00:00'
        ]);

        $purchaseId = DB::table('purchases')->insertGetId([
            'no_so' => 'SO-SUMINGKIR-20260601',
            'shop_id' => 2,
            'supplier_id' => 1,
            'volume' => 2000,
            'total_bayar' => 22000000, // 2000 * 11000
            'created_at' => '2026-06-01 08:00:00',
            'updated_at' => '2026-06-01 08:00:00'
        ]);

        DB::table('incomings')->insert([
            'daily_report_id' => $s2,
            'shop_id' => 2,
            'operator_id' => 2,
            'purchase_id' => $purchaseId,
            'sopir' => 'Pak Driver',
            'no_polisi' => 'B 1234 XY',
            'volume' => 2000,
            'stik_awal' => 43.00,
            'stik_akhir' => 138.20,
            'created_at' => '2026-06-01 10:00:00',
            'updated_at' => '2026-06-01 10:00:00'
        ]);

        DB::table('spendings')->insert([
            'daily_report_id' => $s2,
            'spending_category_id' => 99,
            'keterangan' => 'Biaya Operasional 01 Juni',
            'jumlah' => 106000,
            'shop_id' => 2,
            'operator_id' => 2,
            'created_at' => '2026-06-01 11:00:00',
            'updated_at' => '2026-06-01 11:00:00'
        ]);

        // 02 Juni (Spending 273.000)
        $s3 = DB::table('daily_reports')->insertGetId([
            'shop_id' => 2,
            'operator_id' => 2,
            'price_id' => 16,
            'totalisator_akhir' => 266030.525,
            'stik_akhir' => 81.20, // 1705.200 / 21.00
            'setor_tunai' => 8340432,
            'diverifikasi' => 1,
            'created_at' => '2026-06-02 17:00:00',
            'updated_at' => '2026-06-02 17:00:00'
        ]);

        DB::table('spendings')->insert([
            'daily_report_id' => $s3,
            'spending_category_id' => 99,
            'keterangan' => 'Biaya Operasional 02 Juni',
            'jumlah' => 273000,
            'shop_id' => 2,
            'operator_id' => 2,
            'created_at' => '2026-06-02 11:00:00',
            'updated_at' => '2026-06-02 11:00:00'
        ]);


        // 5. Seed empty starting/transacting states for other shops so they are clean
        // PS Kalitapen (shop_id = 1, operator_id = 1)
        DB::table('daily_reports')->insert([
            'shop_id' => 1, 'operator_id' => 1, 'price_id' => 16, 'totalisator_akhir' => 320761.390, 'stik_akhir' => 92.60, 'setor_tunai' => 0, 'diverifikasi' => 1, 'created_at' => '2026-05-31 17:00:00', 'updated_at' => '2026-05-31 17:00:00'
        ]);
        DB::table('daily_reports')->insert([
            'shop_id' => 1, 'operator_id' => 1, 'price_id' => 16, 'totalisator_akhir' => 320761.390, 'stik_akhir' => 92.60, 'setor_tunai' => 0, 'diverifikasi' => 1, 'created_at' => '2026-06-01 17:00:00', 'updated_at' => '2026-06-01 17:00:00'
        ]);
        DB::table('daily_reports')->insert([
            'shop_id' => 1, 'operator_id' => 1, 'price_id' => 16, 'totalisator_akhir' => 320761.390, 'stik_akhir' => 92.60, 'setor_tunai' => 0, 'diverifikasi' => 1, 'created_at' => '2026-06-02 17:00:00', 'updated_at' => '2026-06-02 17:00:00'
        ]);

        // PS Gumelar (shop_id = 4, operator_id = 4)
        DB::table('daily_reports')->insert([
            'shop_id' => 4, 'operator_id' => 4, 'price_id' => 16, 'totalisator_akhir' => 113644.880, 'stik_akhir' => 98.20, 'setor_tunai' => 0, 'diverifikasi' => 1, 'created_at' => '2026-05-31 17:00:00', 'updated_at' => '2026-05-31 17:00:00'
        ]);
        DB::table('daily_reports')->insert([
            'shop_id' => 4, 'operator_id' => 4, 'price_id' => 16, 'totalisator_akhir' => 113644.880, 'stik_akhir' => 98.20, 'setor_tunai' => 0, 'diverifikasi' => 1, 'created_at' => '2026-06-01 17:00:00', 'updated_at' => '2026-06-01 17:00:00'
        ]);
        DB::table('daily_reports')->insert([
            'shop_id' => 4, 'operator_id' => 4, 'price_id' => 16, 'totalisator_akhir' => 113644.880, 'stik_akhir' => 98.20, 'setor_tunai' => 0, 'diverifikasi' => 1, 'created_at' => '2026-06-02 17:00:00', 'updated_at' => '2026-06-02 17:00:00'
        ]);

        // PS Kemutug Lor (shop_id = 5, operator_id = 6)
        DB::table('daily_reports')->insert([
            'shop_id' => 5, 'operator_id' => 6, 'price_id' => 16, 'totalisator_akhir' => 178141.680, 'stik_akhir' => 90.30, 'setor_tunai' => 0, 'diverifikasi' => 1, 'created_at' => '2026-05-31 17:00:00', 'updated_at' => '2026-05-31 17:00:00'
        ]);
        DB::table('daily_reports')->insert([
            'shop_id' => 5, 'operator_id' => 6, 'price_id' => 16, 'totalisator_akhir' => 178141.680, 'stik_akhir' => 90.30, 'setor_tunai' => 0, 'diverifikasi' => 1, 'created_at' => '2026-06-01 17:00:00', 'updated_at' => '2026-06-01 17:00:00'
        ]);
        DB::table('daily_reports')->insert([
            'shop_id' => 5, 'operator_id' => 6, 'price_id' => 16, 'totalisator_akhir' => 178141.680, 'stik_akhir' => 90.30, 'setor_tunai' => 0, 'diverifikasi' => 1, 'created_at' => '2026-06-02 17:00:00', 'updated_at' => '2026-06-02 17:00:00'
        ]);
    }
}
