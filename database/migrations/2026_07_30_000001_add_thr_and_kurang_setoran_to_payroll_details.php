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
        Schema::table('payroll_details', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_details', 'thr')) {
                $table->decimal('thr', 12, 2)->default(0)->after('bonus');
            }
            if (!Schema::hasColumn('payroll_details', 'kurang_setoran')) {
                $table->decimal('kurang_setoran', 12, 2)->default(0)->after('potongan_tidak_masuk');
            }
            if (!Schema::hasColumn('payroll_details', 'sisa_kurang_bayar')) {
                $table->decimal('sisa_kurang_bayar', 12, 2)->default(0)->after('take_home_pay');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->dropColumn(['thr', 'kurang_setoran', 'sisa_kurang_bayar']);
        });
    }
};
