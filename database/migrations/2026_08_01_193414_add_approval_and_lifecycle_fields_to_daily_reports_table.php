<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            // Metadata Actor Lifecycle
            if (!Schema::hasColumn('daily_reports', 'imported_by')) {
                $table->unsignedBigInteger('imported_by')->nullable()->after('status_lifecycle');
                $table->timestamp('imported_at')->nullable()->after('imported_by');
            }

            if (!Schema::hasColumn('daily_reports', 'validated_by')) {
                $table->unsignedBigInteger('validated_by')->nullable()->after('imported_at');
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }

            if (!Schema::hasColumn('daily_reports', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('validated_at');
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('daily_reports', 'locked_by')) {
                $table->unsignedBigInteger('locked_by')->nullable()->after('approved_at');
                $table->timestamp('locked_at')->nullable()->after('locked_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn([
                'imported_by', 'imported_at',
                'validated_by', 'validated_at',
                'approved_by', 'approved_at',
                'locked_by', 'locked_at'
            ]);
        });
    }
};
