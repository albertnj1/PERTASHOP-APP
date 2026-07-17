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
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->unsignedDecimal('test_pump_volume', 10, 3)->default(0)->after('totalisator_akhir');
            $table->unsignedDecimal('penerimaan_volume', 10, 2)->default(0)->after('test_pump_volume');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn([
                'test_pump_volume',
                'penerimaan_volume',
            ]);
        });
    }
};
