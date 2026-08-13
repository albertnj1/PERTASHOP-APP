<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah konfigurasi metode potongan alpha dan rate transport ke payroll_systems.
     *
     * metode_potongan_alpha:
     *   - 'nominal_tetap'    → pakai potongan_per_hari_alpha sebagai nominal Rp flat (default, backward-compatible)
     *   - 'prorata_gaji_pokok' → Rate harian = (gaji_pokok + gaji_variable) / standar_hari_kerja
     *                            Potongan alpha = jumlah_hari_alpha × rate_harian_dinamis
     *
     * rate_transport_per_hari:
     *   - Nominal Rp per hari kerja yang hadir (misalnya Rp 20.000/hari)
     *   - Default 0 = tidak ada transport (backward-compatible)
     */
    public function up(): void
    {
        Schema::table('payroll_systems', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_systems', 'rate_transport_per_hari')) {
                $table->decimal('rate_transport_per_hari', 10, 2)->default(0)->after('standar_hari_kerja');
            }
        });

        // Tambah metode_potongan_alpha sebagai enum (MySQL)
        try {
            DB::statement("ALTER TABLE payroll_systems ADD COLUMN IF NOT EXISTS metode_potongan_alpha ENUM('nominal_tetap', 'prorata_gaji_pokok') NOT NULL DEFAULT 'nominal_tetap' AFTER rate_transport_per_hari");
        } catch (\Throwable $e) {
            // Fallback untuk SQLite atau database yang tidak support ADD COLUMN IF NOT EXISTS
            if (!Schema::hasColumn('payroll_systems', 'metode_potongan_alpha')) {
                Schema::table('payroll_systems', function (Blueprint $table) {
                    $table->string('metode_potongan_alpha', 30)->default('nominal_tetap')->after('rate_transport_per_hari');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('payroll_systems', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('payroll_systems', 'rate_transport_per_hari')) {
                $cols[] = 'rate_transport_per_hari';
            }
            if (Schema::hasColumn('payroll_systems', 'metode_potongan_alpha')) {
                $cols[] = 'metode_potongan_alpha';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
