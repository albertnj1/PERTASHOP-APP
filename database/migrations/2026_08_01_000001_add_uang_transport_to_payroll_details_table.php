<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom uang_transport ke payroll_details.
     * Dihitung per hari kerja (hari_kerja × rate_transport_per_hari dari payroll_systems).
     * Masuk ke Total_Tambahan slip gaji, TIDAK masuk ke basis prorata potongan alpha.
     */
    public function up(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_details', 'uang_transport')) {
                // Letakkan setelah thr (sebelum potongan_tidak_masuk)
                $table->decimal('uang_transport', 12, 2)->default(0)->after('thr');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_details', 'uang_transport')) {
                $table->dropColumn('uang_transport');
            }
        });
    }
};
