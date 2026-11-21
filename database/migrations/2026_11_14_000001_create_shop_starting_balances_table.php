<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan uang_belum_disetor_awal dan stok_awal pada tabel shops jika belum ada
        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                if (!Schema::hasColumn('shops', 'uang_belum_disetor_awal')) {
                    $table->decimal('uang_belum_disetor_awal', 15, 2)->default(0)->after('skala');
                }
                if (!Schema::hasColumn('shops', 'stok_awal')) {
                    $table->decimal('stok_awal', 10, 3)->nullable()->after('stik_awal');
                }
            });
        }

        // 2. Tabel khusus saldo awal per gerai per periode bulan (untuk bulan pertama operasional / cut-off)
        if (!Schema::hasTable('shop_starting_balances')) {
            Schema::create('shop_starting_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');
                $table->decimal('totalisator_awal', 12, 3);
                $table->decimal('stok_awal', 10, 3);
                $table->decimal('uang_belum_disetor', 15, 2);
                $table->string('keterangan')->nullable();
                $table->timestamps();

                $table->unique(['shop_id', 'year', 'month']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_starting_balances');

        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                if (Schema::hasColumn('shops', 'uang_belum_disetor_awal')) {
                    $table->dropColumn('uang_belum_disetor_awal');
                }
                if (Schema::hasColumn('shops', 'stok_awal')) {
                    $table->dropColumn('stok_awal');
                }
            });
        }
    }
};

