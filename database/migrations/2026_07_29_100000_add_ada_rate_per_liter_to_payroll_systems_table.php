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
        Schema::table('payroll_systems', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_systems', 'ada_rate_per_liter')) {
                $table->boolean('ada_rate_per_liter')->default(true)->after('nama_sistem');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_systems', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_systems', 'ada_rate_per_liter')) {
                $table->dropColumn('ada_rate_per_liter');
            }
        });
    }
};
