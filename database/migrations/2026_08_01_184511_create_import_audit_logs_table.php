<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('shop_id')->nullable()->index();
            $table->string('file_name')->nullable();
            $table->string('sheet_name')->nullable();
            $table->integer('total_sheets')->default(0);
            $table->integer('total_records')->default(0);
            $table->integer('skipped_records')->default(0);
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->string('exception_level', 10)->nullable()
                  ->comment('critical | warning | info');
            $table->string('status', 20)->default('pending')
                  ->comment('pending | success | failed | rolled_back');
            $table->json('warnings')->nullable();
            $table->text('error_log')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_audit_logs');
    }
};
