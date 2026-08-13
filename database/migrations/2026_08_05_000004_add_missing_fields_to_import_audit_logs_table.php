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
        if (Schema::hasTable('import_audit_logs')) {
            Schema::table('import_audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('import_audit_logs', 'import_batch_id')) {
                    $table->string('import_batch_id')->nullable()->index();
                }
                if (!Schema::hasColumn('import_audit_logs', 'sumber_file_excel')) {
                    $table->string('sumber_file_excel')->nullable();
                }
                if (!Schema::hasColumn('import_audit_logs', 'file_hash')) {
                    $table->string('file_hash')->nullable()->index();
                }
                if (!Schema::hasColumn('import_audit_logs', 'workbook_signature')) {
                    $table->string('workbook_signature')->nullable()->index();
                }
                if (!Schema::hasColumn('import_audit_logs', 'recognition_source')) {
                    $table->string('recognition_source')->default('dynamic');
                }
                if (!Schema::hasColumn('import_audit_logs', 'app_version')) {
                    $table->string('app_version')->default('2.4.0');
                }
                if (!Schema::hasColumn('import_audit_logs', 'total_rows')) {
                    $table->integer('total_rows')->default(0);
                }
                if (!Schema::hasColumn('import_audit_logs', 'inserted_rows')) {
                    $table->integer('inserted_rows')->default(0);
                }
                if (!Schema::hasColumn('import_audit_logs', 'skipped_rows')) {
                    $table->integer('skipped_rows')->default(0);
                }
                if (!Schema::hasColumn('import_audit_logs', 'decision_log')) {
                    $table->json('decision_log')->nullable();
                }
                if (!Schema::hasColumn('import_audit_logs', 'performance_metrics')) {
                    $table->json('performance_metrics')->nullable();
                }
                if (!Schema::hasColumn('import_audit_logs', 'confidence_breakdown')) {
                    $table->json('confidence_breakdown')->nullable();
                }
                if (!Schema::hasColumn('import_audit_logs', 'rollback_reason')) {
                    $table->string('rollback_reason')->nullable();
                }
                if (!Schema::hasColumn('import_audit_logs', 'status')) {
                    $table->string('status')->default('completed');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
