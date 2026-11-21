<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix: totalisator_akhir decimal(10,3) -> decimal(15,3) to handle large values (>9,999,999)
     * Fix: stik_akhir decimal(5,2) -> decimal(10,2) to handle larger stik values
     */
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            // totalisator_akhir: was decimal(10,3), max 9,999,999.999
            // New: decimal(15,3), max 999,999,999,999.999
            $table->decimal('totalisator_akhir', 15, 3)->unsigned()->nullable()->change();
            
            // stik_akhir: was decimal(5,2), max 999.99
            // New: decimal(10,2), max 99,999,999.99
            $table->decimal('stik_akhir', 10, 2)->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->decimal('totalisator_akhir', 10, 3)->unsigned()->nullable()->change();
            $table->decimal('stik_akhir', 5, 2)->unsigned()->nullable()->change();
        });
    }
};
