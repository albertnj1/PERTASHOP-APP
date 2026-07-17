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
        Schema::create('excel_rekaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('excel_uploads')->cascadeOnDelete();
            $table->double('harga_tebus_active', 12, 4)->default(0);
            $table->double('harga_jual_active', 12, 2)->default(0);
            $table->json('detail_do')->nullable();
            $table->json('detail_pengeluaran_rutin')->nullable();
            $table->json('detail_pembagian_hasil')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_rekaps');
    }
};
