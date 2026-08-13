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
        if (!Schema::hasTable('import_profiles')) {
            Schema::create('import_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('profile_name');
                $table->string('workbook_signature')->unique();
                $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
                $table->json('mapping_config');
                $table->integer('header_row')->default(1);
                $table->integer('use_count')->default(1);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_profiles');
    }
};
