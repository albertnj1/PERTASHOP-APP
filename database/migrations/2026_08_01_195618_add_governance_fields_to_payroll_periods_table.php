<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'approval_status')) {
                $table->string('approval_status', 30)->default('draft')->after('status'); // 'draft', 'submitted', 'approved', 'paid'
            }
            if (!Schema::hasColumn('payroll_periods', 'rule_version_snapshot')) {
                $table->json('rule_version_snapshot')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('payroll_periods', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('rule_version_snapshot');
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'rule_version_snapshot', 'approved_by', 'approved_at']);
        });
    }
};
