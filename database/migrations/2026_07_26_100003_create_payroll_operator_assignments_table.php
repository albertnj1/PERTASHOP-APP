<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assign operator ke sistem penggajian toko tertentu.
     * Support tanggal_mulai & tanggal_selesai agar perubahan kebijakan
     * tengah bulan bisa di-handle tanpa menimpa data historis.
     */
    public function up(): void
    {
        Schema::create('payroll_operator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('payroll_system_id')->constrained('payroll_systems')->onDelete('cascade');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable(); // null = masih aktif
            $table->timestamps();

            $table->index(['operator_id', 'tanggal_mulai']);
            $table->index(['shop_id', 'payroll_system_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_operator_assignments');
    }
};
