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
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->string('pelanggan');
            $table->unsignedDecimal('jumlah_piutang', 12, 2);
            $table->unsignedDecimal('jumlah_dibayar', 12, 2)->default(0);
            $table->string('sumber_transaksi')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status', ['belum lunas', 'lunas'])->default('belum lunas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receivables');
    }
};
