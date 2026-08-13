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
        Schema::create('payroll_detail_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_detail_id')->constrained('payroll_details')->onDelete('cascade');
            $table->enum('tipe', ['tambahan', 'potongan']);
            $table->string('nama_item'); // "Kelebihan Transfer", "Uang Hilang", "Bonus Penjualan Oli", dll
            $table->decimal('jumlah', 12, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_detail_items');
    }
};
