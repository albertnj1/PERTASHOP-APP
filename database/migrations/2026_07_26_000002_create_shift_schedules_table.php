<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('operator_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->unsignedTinyInteger('shift_ke')->default(1); // 1, 2, dst.
            $table->enum('status', ['dijadwalkan', 'hadir', 'alpha', 'izin', 'sakit'])->default('dijadwalkan');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Satu operator hanya bisa punya 1 slot per shift per hari
            $table->unique(['operator_id', 'tanggal', 'shift_ke'], 'shift_unique_per_operator');
            // Index untuk query per toko per tanggal (digunakan dashboard & kalender)
            $table->index(['shop_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_schedules');
    }
};
