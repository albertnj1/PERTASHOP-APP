<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('year_month', 7)->index(); // '2026-07'
            $table->enum('lock_type', ['soft', 'hard'])->default('hard');
            $table->boolean('is_locked')->default(false);
            $table->unsignedBigInteger('locked_by')->nullable()->constrained('users');
            $table->timestamp('locked_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'year_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_locks');
    }
};
