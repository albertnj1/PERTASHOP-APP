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
        Schema::table('incomings', function (Blueprint $table) {
            $table->decimal('density_15c', 8, 4)->nullable()->after('terima_density');
            $table->boolean('is_pertamax')->nullable()->after('density_15c');
            $table->decimal('losses_gain', 10, 2)->nullable()->after('is_pertamax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incomings', function (Blueprint $table) {
            $table->dropColumn(['density_15c', 'is_pertamax', 'losses_gain']);
        });
    }
};
