<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('approval_batch_id')->nullable()->index(); // Batch UUID untuk batch approval
            $table->unsignedBigInteger('daily_report_id')->nullable()->index();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('year_month', 7)->index(); // '2026-07'
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->unsignedBigInteger('acted_by')->constrained('users');
            $table->string('actor_role', 30)->default('operator');
            $table->text('reason')->nullable(); // Mandatory untuk REJECTED / REOPENED
            $table->json('snapshot_summary')->nullable(); // Snapshot score %, total vol, omset, THP
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_approval_histories');
    }
};
