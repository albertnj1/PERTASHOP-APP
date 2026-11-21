<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah kolom status shift_schedules ke VARCHAR(30) agar fleksibel mendukung status libur/diliburkan
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE shift_schedules DROP CONSTRAINT IF EXISTS shift_schedules_status_check');
        }
        Schema::table('shift_schedules', function (Blueprint $table) {
            $table->string('status', 30)->default('dijadwalkan')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_schedules', function (Blueprint $table) {
            $table->string('status', 20)->default('dijadwalkan')->change();
        });
    }
};
