<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_swaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_schedule_id')->constrained('shift_schedules')->onDelete('cascade');
            $table->foreignId('operator_asal_id')->constrained('operators')->onDelete('cascade');
            $table->foreignId('operator_pengganti_id')->constrained('operators')->onDelete('cascade');
            // Alasan pergantian: izin / sakit / keperluan_pribadi / lainnya
            $table->string('alasan')->nullable();
            $table->text('keterangan')->nullable();
            // Admin yang memproses pergantian
            $table->foreignId('diubah_oleh')->constrained('users');
            $table->timestamp('waktu_perubahan')->useCurrent();
            $table->timestamps();

            $table->index('shift_schedule_id');
            $table->index('operator_asal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_swaps');
    }
};
