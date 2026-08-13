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
        Schema::table('investor_shop', function (Blueprint $table) {
            $table->unsignedDecimal('investasi_awal', 15, 2)->default(0)->after('persentase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investor_shop', function (Blueprint $table) {
            $table->dropColumn('investasi_awal');
        });
    }
};
