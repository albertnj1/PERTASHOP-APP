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
        Schema::table('daily_report_uploads', function (Blueprint $table) {
            $table->decimal('test_pump', 10, 2)->default(0)->after('totalisator_akhir');
            $table->text('keterangan_pengeluaran')->nullable()->after('pengeluaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_report_uploads', function (Blueprint $table) {
            $table->dropColumn(['test_pump', 'keterangan_pengeluaran']);
        });
    }
};
