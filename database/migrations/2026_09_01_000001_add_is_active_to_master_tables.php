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
        if (Schema::hasTable('investors') && !Schema::hasColumn('investors', 'is_active')) {
            Schema::table('investors', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('user_id');
            });
        }

        if (Schema::hasTable('shops') && !Schema::hasColumn('shops', 'is_active')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('nama');
            });
        }

        if (Schema::hasTable('corporations') && !Schema::hasColumn('corporations', 'is_active')) {
            Schema::table('corporations', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('nama');
            });
        }

        if (Schema::hasTable('operators') && !Schema::hasColumn('operators', 'is_active')) {
            Schema::table('operators', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('investors') && Schema::hasColumn('investors', 'is_active')) {
            Schema::table('investors', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasTable('shops') && Schema::hasColumn('shops', 'is_active')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasTable('corporations') && Schema::hasColumn('corporations', 'is_active')) {
            Schema::table('corporations', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasTable('operators') && Schema::hasColumn('operators', 'is_active')) {
            Schema::table('operators', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
