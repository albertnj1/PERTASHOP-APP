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
            if (!Schema::hasColumn('payroll_systems', 'standar_hari_kerja')) {
                $table->unsignedInteger('standar_hari_kerja')->default(26)->after('metode_split');
            }
        });

        // Ubah enum/string metode_split agar menyertakan flat_bulanan_prorata_hari
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payroll_systems DROP CONSTRAINT IF EXISTS payroll_systems_metode_split_check');
        }
        Schema::table('payroll_systems', function (Blueprint $table) {
            $table->string('metode_split', 50)->default('per_hari_penuh')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_systems', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_systems', 'standar_hari_kerja')) {
                $table->dropColumn('standar_hari_kerja');
            }
        });
    }
};
