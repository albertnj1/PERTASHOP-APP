<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Meningkatkan presisi kolom PostgreSQL untuk volume, harga, rupiah, saldo berjalan, dan koefisien tangki.
     * Volume: NUMERIC(15,4)
     * Harga: NUMERIC(14,4)
     * Rupiah / Saldo Berjalan: NUMERIC(18,4)
     * Koefisien Tangki (shops.skala): NUMERIC(10,4)
     */
    public function up(): void
    {
        // 1. Tabel daily_reports
        if (Schema::hasTable('daily_reports')) {
            Schema::table('daily_reports', function (Blueprint $table) {
                // Volume columns -> NUMERIC(15,4)
                $table->decimal('bbm_keluar_lain', 15, 4)->default(0)->change();
                $table->decimal('penerimaan_volume', 15, 4)->default(0)->change();
                $table->decimal('test_pump_volume', 15, 4)->default(0)->change();
                $table->decimal('totalisator_akhir', 15, 4)->change();
                $table->decimal('stik_akhir', 10, 4)->change();

                // Snapshot Harga -> NUMERIC(14,4)
                $table->decimal('harga_jual_snapshot', 14, 4)->nullable()->change();
                $table->decimal('harga_beli_snapshot', 14, 4)->nullable()->change();

                // Saldo & Rupiah -> NUMERIC(18,4)
                $table->decimal('running_balance_setoran', 18, 4)->default(0)->change();
                $table->decimal('disetorkan', 18, 4)->default(0)->change();
                if (Schema::hasColumn('daily_reports', 'setor_tunai')) {
                    $table->decimal('setor_tunai', 18, 4)->default(0)->change();
                }
                if (Schema::hasColumn('daily_reports', 'setor_qris')) {
                    $table->decimal('setor_qris', 18, 4)->default(0)->change();
                }
                if (Schema::hasColumn('daily_reports', 'setor_transfer')) {
                    $table->decimal('setor_transfer', 18, 4)->default(0)->change();
                }
            });
        }

        // 2. Tabel shops
        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                // Koefisien tangki (skala) -> NUMERIC(10,4)
                $table->decimal('skala', 10, 4)->default(21.49)->change();
                // Totalisator & stok awal
                $table->decimal('totalisator_awal', 15, 4)->default(0)->change();
                $table->decimal('stik_awal', 10, 4)->default(0)->change();
                if (Schema::hasColumn('shops', 'stok_awal')) {
                    $table->decimal('stok_awal', 15, 4)->nullable()->change();
                }
                if (Schema::hasColumn('shops', 'uang_belum_disetor_awal')) {
                    $table->decimal('uang_belum_disetor_awal', 18, 4)->default(0)->change();
                }
            });
        }

        // 3. Tabel prices
        if (Schema::hasTable('prices')) {
            Schema::table('prices', function (Blueprint $table) {
                $table->decimal('harga_jual', 14, 4)->change();
                $table->decimal('harga_beli', 14, 4)->change();
                if (Schema::hasColumn('prices', 'totalisator_perubahan')) {
                    $table->decimal('totalisator_perubahan', 15, 4)->nullable()->change();
                }
            });
        }

        // 4. Tabel shop_starting_balances
        if (Schema::hasTable('shop_starting_balances')) {
            Schema::table('shop_starting_balances', function (Blueprint $table) {
                $table->decimal('totalisator_awal', 15, 4)->change();
                $table->decimal('stok_awal', 15, 4)->change();
                $table->decimal('uang_belum_disetor', 18, 4)->change();
            });
        }

        // 5. Tabel incomings & spendings
        if (Schema::hasTable('incomings')) {
            Schema::table('incomings', function (Blueprint $table) {
                $table->decimal('volume', 15, 4)->change();
            });
        }
        if (Schema::hasTable('spendings')) {
            Schema::table('spendings', function (Blueprint $table) {
                $table->decimal('jumlah', 18, 4)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback ke NUMERIC default jika dibutuhkan
    }
};

