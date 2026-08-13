<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            // Running Balance Setoran — saldo kumulatif belum disetor (carry-forward hari ke hari)
            $table->decimal('running_balance_setoran', 14, 2)->default(0)
                  ->after('disetorkan')
                  ->comment('Saldo berjalan kumulatif belum disetor. Carry-forward otomatis dari hari sebelumnya.');

            // Status Lifecycle Laporan
            $table->string('status_lifecycle', 20)->default('draft')
                  ->after('running_balance_setoran')
                  ->comment('draft | imported | validated | approved | locked');

            // Index untuk performa query
            $table->index(['shop_id', 'created_at'], 'idx_daily_reports_shop_date');
            $table->index('status_lifecycle', 'idx_daily_reports_lifecycle');
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropIndex('idx_daily_reports_shop_date');
            $table->dropIndex('idx_daily_reports_lifecycle');
            $table->dropColumn(['running_balance_setoran', 'status_lifecycle']);
        });
    }
};
