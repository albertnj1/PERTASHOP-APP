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
        Schema::create('daily_report_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->date('tanggal');
            $table->double('totalisator_awal');
            $table->double('totalisator_akhir');
            $table->double('qris')->default(0);
            $table->double('pengeluaran')->default(0);
            $table->string('file_path')->nullable(); // For Excel/PNG/JPG proof
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_report_uploads');
    }
};
