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
            if (!Schema::hasColumn('daily_reports', 'klarifikasi_selisih')) {
                $table->text('klarifikasi_selisih')->nullable()->after('foto_stik');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn(['klarifikasi_selisih']);
        });
    }
};
