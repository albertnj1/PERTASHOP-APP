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
            if (!Schema::hasColumn('daily_reports', 'excel_upload_id')) {
                $table->foreignId('excel_upload_id')
                    ->nullable()
                    ->after('price_id')
                    ->constrained('excel_uploads')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropForeign(['excel_upload_id']);
            $table->dropColumn(['excel_upload_id']);
        });
    }
};
