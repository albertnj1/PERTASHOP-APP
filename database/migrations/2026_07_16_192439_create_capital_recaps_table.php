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
        Schema::create('capital_recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->integer('bulan'); // 1 - 12
            $table->integer('tahun'); // e.g., 2024
            $table->integer('tahun_ke'); // e.g., 1, 2, 3
            
            $table->decimal('nilai_modal_awal', 20, 2)->default(0);
            $table->decimal('penyusutan_rugi', 20, 2)->default(0);
            $table->decimal('penyusutan_pajak_bank', 20, 2)->default(0);
            $table->decimal('penambahan_keuntungan', 20, 2)->default(0);
            $table->decimal('penambahan_bunga_bank', 20, 2)->default(0);
            
            $table->decimal('nilai_penambahan_penyusutan', 20, 2)->default(0);
            $table->decimal('akumulasi_penambahan_penyusutan', 20, 2)->default(0);
            
            $table->decimal('posisi_akhir_modal', 20, 2)->default(0);
            
            $table->decimal('harga_beli_pertamax', 15, 2)->default(0);
            $table->decimal('konversi_liter', 20, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_recaps');
    }
};
