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
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('bulan_tahun'); // e.g. "Juni 2026"
            $table->string('sheet_name')->nullable();
            $table->double('totalisator_awal')->default(0);
            $table->double('totalisator_akhir')->default(0);
            $table->string('pembayaran_ke')->nullable();
            $table->json('pengeluaran_extra')->nullable(); // [{"keterangan": "Beli Sapu", "nominal": 15000}]
            $table->string('file_path')->nullable();
            $table->json('grand_totals')->nullable();
            $table->json('data_parsed')->nullable(); // The array of daily rows
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
