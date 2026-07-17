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
            $table->decimal('nominal', 15, 2)->default(0)->after('persentase');
            $table->json('sub_investors')->nullable()->after('nominal');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->decimal('total_investasi', 15, 2)->default(0)->after('modal_awal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investor_shop', function (Blueprint $table) {
            $table->dropColumn('nominal');
            $table->dropColumn('sub_investors');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('total_investasi');
        });
    }
};
