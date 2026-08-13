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

        // Ubah enum metode_split agar menyertakan flat_bulanan_prorata_hari
        try {
            DB::statement("ALTER TABLE payroll_systems MODIFY COLUMN metode_split ENUM('per_hari_penuh', 'proporsional_jam_kerja', 'flat_bulanan_prorata_hari') NOT NULL DEFAULT 'per_hari_penuh'");
        } catch (\Throwable $e) {
            // Untuk database SQLite atau perlakuan khusus
        }
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
