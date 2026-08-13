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
        if (!Schema::hasTable('import_audit_logs')) {
            Schema::create('import_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('import_batch_id')->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
                $table->string('sumber_file_excel');
                $table->string('file_hash')->nullable()->index();
                $table->string('workbook_signature')->nullable()->index();
                $table->string('recognition_source')->default('dynamic'); // profile, registry, dynamic, manual
                $table->string('app_version')->default('2.4.0');
                $table->integer('total_rows')->default(0);
                $table->integer('inserted_rows')->default(0);
                $table->integer('skipped_rows')->default(0);
                $table->json('decision_log')->nullable();
                $table->json('performance_metrics')->nullable();
                $table->json('confidence_breakdown')->nullable();
                $table->string('rollback_reason')->nullable();
                $table->string('status')->default('completed'); // completed, rolled_back, failed
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_audit_logs');
    }
};
