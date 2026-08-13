<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_reports', 'business_rule_snapshot')) {
                $table->json('business_rule_snapshot')->nullable()->after('status_lifecycle');
            }
            if (!Schema::hasColumn('daily_reports', 'business_rule_version_ids')) {
                $table->json('business_rule_version_ids')->nullable()->after('business_rule_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn(['business_rule_snapshot', 'business_rule_version_ids']);
        });
    }
};
