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
        Schema::create('deposit_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->onDelete('cascade');
            $table->string('nama_kategori'); // "Tunai", "QRIS", "Transfer Bank", "Piutang", "Kolektan", dll
            $table->boolean('termasuk_dalam_setoran')->default(true); // apakah dihitung dalam formula disetorkan
            $table->timestamps();
        });

        Schema::create('daily_report_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('daily_reports')->onDelete('cascade');
            $table->foreignId('deposit_category_id')->constrained('deposit_categories')->onDelete('cascade');
            $table->string('nama_bank')->nullable(); // "Mandiri", "BRI", "BCA", dll
            $table->decimal('jumlah', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['daily_report_id', 'deposit_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_report_deposits');
        Schema::dropIfExists('deposit_categories');
    }
};
