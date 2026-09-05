<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_systems', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_systems', 'tipe_skema')) {
                // Tipe skema: komisi_murni, gaji_pokok_murni, hibrid
                $table->string('tipe_skema', 30)->default('komisi_murni')->after('nama_sistem');
            }
        });

        // Sinkronisasi tipe_skema untuk data yang sudah ada
        try {
            // Jika ada gaji pokok & ada rate per liter -> hibrid
            DB::table('payroll_systems')
                ->where('ada_gaji_pokok', true)
                ->where('ada_rate_per_liter', true)
                ->update(['tipe_skema' => 'hibrid']);

            // Jika ada gaji pokok tapi TIDAK ada rate per liter -> gaji_pokok_murni
            DB::table('payroll_systems')
                ->where('ada_gaji_pokok', true)
                ->where('ada_rate_per_liter', false)
                ->update(['tipe_skema' => 'gaji_pokok_murni']);

            // Jika ada rate per liter tapi TIDAK ada gaji pokok -> komisi_murni
            DB::table('payroll_systems')
                ->where('ada_gaji_pokok', false)
                ->where('ada_rate_per_liter', true)
                ->update(['tipe_skema' => 'komisi_murni']);
        } catch (\Throwable $e) {
            // Abaikan jika tabel kosong saat migrasi pertama
        }

        // Sinkronisasi spesifik berdasarkan cabang sesuai kebutuhan bisnis:
        // 1. Komisi Murni: Kalitapen, Kalibenda, Pageralang, Kemutug
        // 2. Gaji Pokok Murni: Gumelar
        // 3. Hibrid: Sumingkir
        try {
            $shops = DB::table('shops')->select('id', 'nama')->get();
            foreach ($shops as $shop) {
                $nama = strtolower($shop->nama);

                if (str_contains($nama, 'gumelar')) {
                    DB::table('payroll_systems')
                        ->where('shop_id', $shop->id)
                        ->update([
                            'tipe_skema'         => 'gaji_pokok_murni',
                            'ada_gaji_pokok'     => true,
                            'ada_rate_per_liter' => false,
                        ]);
                } elseif (str_contains($nama, 'sumingkir')) {
                    DB::table('payroll_systems')
                        ->where('shop_id', $shop->id)
                        ->update([
                            'tipe_skema'         => 'hibrid',
                            'ada_gaji_pokok'     => true,
                            'ada_rate_per_liter' => true,
                        ]);
                } elseif (
                    str_contains($nama, 'kalitapen') ||
                    str_contains($nama, 'kalibenda') ||
                    str_contains($nama, 'pageralang') ||
                    str_contains($nama, 'kemutug')
                ) {
                    DB::table('payroll_systems')
                        ->where('shop_id', $shop->id)
                        ->update([
                            'tipe_skema'         => 'komisi_murni',
                            'ada_gaji_pokok'     => false,
                            'ada_rate_per_liter' => true,
                        ]);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika data shops belum ada
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_systems', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_systems', 'tipe_skema')) {
                $table->dropColumn('tipe_skema');
            }
        });
    }
};
