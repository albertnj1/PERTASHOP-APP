<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_reports', 'backdate_file_id')) {
                $table->foreignId('backdate_file_id')
                    ->nullable()
                    ->after('excel_upload_id')
                    ->constrained('backdate_excel_files')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('daily_reports', 'stik_awal')) {
                $table->double('stik_awal')->nullable()->after('totalisator_awal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            if (Schema::hasColumn('daily_reports', 'backdate_file_id')) {
                $table->dropForeign(['backdate_file_id']);
                $table->dropColumn('backdate_file_id');
            }
            if (Schema::hasColumn('daily_reports', 'stik_awal')) {
                $table->dropColumn('stik_awal');
            }
        });
    }
};
