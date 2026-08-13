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
        if (!Schema::hasColumn('investor_shop', 'keterangan')) {
            Schema::table('investor_shop', function (Blueprint $table) {
                $table->string('keterangan')->nullable()->after('sub_investors');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('investor_shop', 'keterangan')) {
            Schema::table('investor_shop', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }
    }
};
