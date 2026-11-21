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
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payroll_periods DROP CONSTRAINT IF EXISTS payroll_periods_status_check');
        }
        if (Schema::hasColumn('payroll_periods', 'status')) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->string('status', 30)->default('draft')->change();
            });
        }

        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'sumber_file_excel')) {
                $table->string('sumber_file_excel')->nullable()->after('status');
            }
            if (Schema::hasColumn('payroll_periods', 'payroll_system_id')) {
                $table->unsignedBigInteger('payroll_system_id')->nullable()->change();
            }
        });
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
