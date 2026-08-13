<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sistem penggajian per toko.
     * Dikonfigurasi di Data Master, digunakan sebagai aturan hitung otomatis
     * saat generate penggajian bulanan.
     */
    public function up(): void
    {
        Schema::create('payroll_systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('nama_sistem');
            $table->boolean('ada_rate_per_liter')->default(true);
            $table->decimal('rate_per_liter', 10, 2)->default(200);
            $table->boolean('ada_gaji_pokok')->default(false);
            $table->decimal('nominal_gaji_pokok', 12, 2)->nullable();
            // Potongan per hari alpha — bisa beda tiap toko
            $table->decimal('potongan_per_hari_alpha', 10, 2)->default(0);
            // Perlakuan losses/gain saat menghitung volume_dihitung per hari
            $table->enum('perlakuan_losses_gain', [
                'losses_only',          // Losses dikurangi, Gain diabaikan
                'losses_dan_gain',      // Keduanya berlaku (plus/minus)
                'abaikan_losses_gain',  // Losses/gain tidak dihitung ke gaji
            ])->default('losses_only');
            // Metode pembagian gaji harian antar operator
            $table->enum('metode_split', [
                'per_hari_penuh',           // Operator bertugas hari itu dapat 100%
                'proporsional_jam_kerja',   // Proporsional jam_mulai/jam_selesai
            ])->default('per_hari_penuh');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_systems');
    }
};
