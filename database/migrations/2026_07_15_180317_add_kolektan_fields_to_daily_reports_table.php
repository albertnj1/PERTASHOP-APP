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
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->foreignId('kolektan_id')->nullable()->constrained('kolektans')->nullOnDelete();
            $table->dateTime('waktu_kolektan')->nullable();
            $table->decimal('setor_kolektan', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropForeign(['kolektan_id']);
            $table->dropColumn(['kolektan_id', 'waktu_kolektan', 'setor_kolektan']);
        });
    }
};
