<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan jam_mulai & jam_selesai ke shift_schedules
     * agar metode split proporsional per jam kerja bisa dihitung otomatis.
     */
    public function up(): void
    {
        Schema::table('shift_schedules', function (Blueprint $table) {
            $table->time('jam_mulai')->nullable()->after('shift_ke');
            $table->time('jam_selesai')->nullable()->after('jam_mulai');
        });
    }

    public function down(): void
    {
        Schema::table('shift_schedules', function (Blueprint $table) {
            $table->dropColumn(['jam_mulai', 'jam_selesai']);
        });
    }
};
