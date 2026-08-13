<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rincian liter per operator per hari dalam satu periode.
     * Selalu dibuat saat generate (baik per_hari_penuh maupun proporsional)
     * untuk transparansi dan audit trail.
     * Admin bisa lihat detail per hari di UI, dan override untuk kasus pengecualian.
     */
    public function up(): void
    {
        Schema::create('payroll_daily_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->onDelete('cascade');
            $table->foreignId('operator_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->decimal('volume_penjualan_aktual', 10, 3)->default(0); // Sebelum losses treatment
            $table->decimal('volume_dihitung', 10, 3)->default(0);         // Setelah losses treatment
            $table->decimal('liter_bagian', 10, 3)->default(0);            // Bagian operator hari itu
            $table->decimal('proporsi', 5, 4)->default(1.0000);            // 1.0000 = 100% (per_hari_penuh), < 1 = proporsional
            // 'otomatis' = dari shift_schedules, 'manual' = di-override admin
            $table->enum('sumber', ['otomatis', 'manual'])->default('otomatis');
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'operator_id', 'tanggal'], 'payroll_daily_split_unique');
            $table->index(['payroll_period_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_daily_splits');
    }
};
