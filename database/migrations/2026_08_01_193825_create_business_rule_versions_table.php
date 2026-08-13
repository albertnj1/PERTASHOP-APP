<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_rule_id')->index();
            $table->string('version_code', 30)->index(); // 'BR-PAYROLL-v1.0'
            $table->decimal('value_numeric', 15, 4)->nullable();
            $table->string('value_string', 255)->nullable();
            $table->json('value_json')->nullable();
            $table->dateTime('effective_from')->index(); // Gunakan dateTime untuk mencegah MySQL auto ON UPDATE CURRENT_TIMESTAMP
            $table->dateTime('effective_until')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->constrained('users');
            $table->text('change_reason')->nullable(); // Mandatory audit reason
            $table->timestamps();

            $table->foreign('business_rule_id')->references('id')->on('business_rules')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_rule_versions');
    }
};
