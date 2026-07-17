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
        Schema::table('incomings', function (Blueprint $table) {
            $table->date('incoming_date')->nullable()->after('purchase_id');
            $table->decimal('maos_volume', 10, 2)->nullable()->after('no_polisi');
            $table->decimal('maos_suhu', 10, 2)->nullable()->after('maos_volume');
            $table->decimal('maos_density', 10, 2)->nullable()->after('maos_suhu');
            $table->time('jam_tiba')->nullable()->after('maos_density');
            $table->time('jam_berangkat')->nullable()->after('jam_tiba');
            $table->decimal('stock_terima_bbm', 10, 2)->nullable()->after('jam_berangkat');
            $table->string('dens_temp')->nullable()->after('stock_terima_bbm');
            $table->decimal('terima_volume', 10, 2)->nullable()->after('dens_temp');
            $table->decimal('terima_suhu', 10, 2)->nullable()->after('terima_volume');
            $table->decimal('terima_density', 10, 2)->nullable()->after('terima_suhu');
            $table->decimal('penerimaan_real', 10, 2)->nullable()->after('stik_akhir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incomings', function (Blueprint $table) {
            $table->dropColumn([
                'incoming_date', 'maos_volume', 'maos_suhu', 'maos_density',
                'jam_tiba', 'jam_berangkat', 'stock_terima_bbm', 'dens_temp',
                'terima_volume', 'terima_suhu', 'terima_density', 'penerimaan_real'
            ]);
        });
    }
};
