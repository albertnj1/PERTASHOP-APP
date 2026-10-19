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
        if (!Schema::hasTable('price_confirmations')) {
            Schema::create('price_confirmations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('price_id');
                $table->unsignedBigInteger('shop_id');
                $table->unsignedBigInteger('operator_id')->nullable();
                $table->string('status_operator', 30)->default('menunggu_konfirmasi'); // 'menunggu_konfirmasi', 'dikonfirmasi'
                $table->string('foto_bukti')->nullable();
                $table->timestamp('dikonfirmasi_pada')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->foreign('price_id')->references('id')->on('prices')->onDelete('cascade');
                $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_confirmations');
    }
};
