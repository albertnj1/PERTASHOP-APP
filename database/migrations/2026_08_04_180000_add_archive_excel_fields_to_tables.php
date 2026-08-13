<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_reports', 'sumber_data')) {
                $table->enum('sumber_data', [
                    'input_manual',
                    'import_excel_arsip'
                ])->default('input_manual')->after('status_lifecycle');
            }
            if (!Schema::hasColumn('daily_reports', 'sumber_file_excel')) {
                $table->string('sumber_file_excel')->nullable()->after('sumber_data');
            }
        });

        // Modify payroll_periods status column to allow 'archived'
        try {
            DB::statement("ALTER TABLE payroll_periods MODIFY COLUMN status ENUM('draft', 'final', 'archived') NOT NULL DEFAULT 'draft'");
        } catch (\Throwable $e) {
            if (Schema::hasColumn('payroll_periods', 'status')) {
                Schema::table('payroll_periods', function (Blueprint $table) {
                    $table->string('status', 30)->default('draft')->change();
                });
            }
        }

        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'sumber_file_excel')) {
                $table->string('sumber_file_excel')->nullable()->after('status');
            }
        });

        // Make payroll_system_id nullable for archived periods if needed
        try {
            DB::statement("ALTER TABLE payroll_periods MODIFY COLUMN payroll_system_id BIGINT UNSIGNED NULL");
        } catch (\Throwable $e) {
            // Ignore if already nullable or not supported
        }
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('daily_reports', 'sumber_data')) {
                $cols[] = 'sumber_data';
            }
            if (Schema::hasColumn('daily_reports', 'sumber_file_excel')) {
                $cols[] = 'sumber_file_excel';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_periods', 'sumber_file_excel')) {
                $table->dropColumn('sumber_file_excel');
            }
        });
    }
};
