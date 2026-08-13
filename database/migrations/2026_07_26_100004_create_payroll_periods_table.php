<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header periode penggajian (per toko per bulan).
     * Status draft = bisa diedit/di-generate ulang.
     * Status final = terkunci, tidak bisa di-generate ulang.
     */
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            // Simpan referensi sistem yang dipakai saat generate, untuk histori
            $table->foreignId('payroll_system_id')->constrained('payroll_systems');
            $table->unsignedTinyInteger('bulan');   // 1–12
            $table->unsignedSmallInteger('tahun');  // e.g. 2026
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->decimal('total_penjualan_liter', 12, 2)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Satu periode per toko per bulan per tahun
            $table->unique(['shop_id', 'bulan', 'tahun'], 'payroll_period_unique');
            $table->index(['shop_id', 'tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
