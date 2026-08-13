<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('operator_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('bulan');   // 1–12
            $table->unsignedSmallInteger('tahun');  // e.g. 2026
            $table->unsignedInteger('total_dijadwalkan')->default(0);
            $table->unsignedInteger('total_hadir')->default(0);
            $table->unsignedInteger('total_alpha')->default(0);
            $table->unsignedInteger('total_izin')->default(0);
            $table->unsignedInteger('total_sakit')->default(0);
            $table->timestamps();

            $table->unique(['operator_id', 'bulan', 'tahun'], 'attendance_recap_unique');
            $table->index(['shop_id', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_recaps');
    }
};
