<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backdate v2: Tambah kolom untuk menyimpan status & hasil processing engine.
     */
    public function up(): void
    {
        Schema::table('backdate_excel_files', function (Blueprint $table) {
            if (!Schema::hasColumn('backdate_excel_files', 'processing_status')) {
                $table->string('processing_status', 20)->default('pending')->after('keterangan');
            }
            if (!Schema::hasColumn('backdate_excel_files', 'processing_result')) {
                $table->longText('processing_result')->nullable()->after('processing_status');
            }
            if (!Schema::hasColumn('backdate_excel_files', 'error_message')) {
                $table->text('error_message')->nullable()->after('processing_result');
            }
            if (!Schema::hasColumn('backdate_excel_files', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('error_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('backdate_excel_files', function (Blueprint $table) {
            $table->dropColumn(['processing_status', 'processing_result', 'error_message', 'processed_at']);
        });
    }
};
