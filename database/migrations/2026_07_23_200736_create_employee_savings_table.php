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
        Schema::create('employee_savings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->enum('jenis', ['setoran', 'pengambilan']);
            $table->unsignedDecimal('jumlah', 10, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_savings');
    }
};
