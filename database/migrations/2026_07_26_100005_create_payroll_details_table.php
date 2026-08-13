<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detail gaji per operator per periode.
     * Komponen otomatis (gaji_variable, gaji_pokok, potongan_tidak_masuk, tabungan_setoran)
     * diisi saat generate. Komponen manual (lembur, bonus, potongan_hutang, tabungan_gaji)
     * diisi/di-override oleh admin.
     */
    public function up(): void
    {
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->onDelete('cascade');
            $table->foreignId('operator_id')->constrained()->onDelete('cascade');

            // === Komponen Otomatis ===
            $table->unsignedInteger('total_hari_kerja')->default(0);
            $table->decimal('liter_bagian', 10, 2)->default(0);    // Total liter bagian operator sebulan
            $table->decimal('gaji_variable', 12, 2)->default(0);   // liter_bagian × rate_per_liter
            $table->decimal('gaji_pokok', 12, 2)->default(0);      // Dari payroll_systems (jika ada_gaji_pokok=true)

            // === Komponen Manual (diisi/di-override admin) ===
            $table->decimal('lembur', 12, 2)->default(0);
            $table->decimal('lembur_hari_raya', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);

            // === Potongan ===
            // Auto-suggest dari attendance_recaps × potongan_per_hari_alpha, bisa di-override
            $table->decimal('potongan_tidak_masuk', 12, 2)->default(0);
            // Manual input admin
            $table->decimal('tabungan_gaji', 12, 2)->default(0);
            // Auto-pull dari employee_savings (sum setoran bulan berjalan), bisa di-override
            $table->decimal('tabungan_setoran', 12, 2)->default(0);
            // Auto-pull/manual dari employee_loans, bisa di-override
            $table->decimal('potongan_hutang', 12, 2)->default(0);

            // === Hasil Akhir ===
            $table->decimal('take_home_pay', 12, 2)->default(0);
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->unique(['payroll_period_id', 'operator_id'], 'payroll_detail_unique');
            $table->index('payroll_period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
