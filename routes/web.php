<?php

use App\Models\Incoming;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\IncomingController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SpendingController;
use App\Http\Controllers\TestPumpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LabaKotorController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LabaBersihController;
use App\Http\Controllers\CorporationController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\ModalController;
use App\Http\Controllers\ProfitSharingController;
use App\Http\Controllers\KolektanController;
use App\Http\Controllers\CapitalRecapController;
use App\Http\Controllers\ShiftScheduleController;
use App\Http\Controllers\OperatorProfileController;
use App\Http\Controllers\FinanceCashflowController;
use App\Http\Controllers\BenchmarkingController;
use App\Http\Controllers\PayrollSystemController;
use App\Http\Controllers\PayrollOperatorAssignmentController;
use App\Http\Controllers\PayrollController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('login', [LoginController::class, 'index'])->name('login');
Route::post('login', [LoginController::class, 'authenticate'])->name('authenticate');
Route::match(['get', 'post', 'delete'], 'logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resources([
        'incomings' => IncomingController::class,
        'test-pumps' => TestPumpController::class,
        'daily-reports' => DailyReportController::class,
        'spendings' => SpendingController::class,
    ]);
    Route::resource('prices', PriceController::class);

    // ── Fase C.1 — Approval & Lock Workflow Routes ────────────────────────────
    Route::get('/reports/approval', [\App\Http\Controllers\ReportApprovalController::class, 'index'])->name('reports.approval.index');
    Route::post('/reports/approval/transition-batch', [\App\Http\Controllers\ReportApprovalController::class, 'transitionBatch'])->name('reports.approval.transition-batch');
    Route::post('/reports/approval/lock-period', [\App\Http\Controllers\ReportApprovalController::class, 'lockPeriod'])->name('reports.approval.lock-period');
    Route::post('/reports/approval/reopen-period', [\App\Http\Controllers\ReportApprovalController::class, 'reopenPeriod'])->name('reports.approval.reopen-period');

    // ── Fase C.2 — Business Rule Registry Routes ──────────────────────────────
    Route::get('/business-rules', [\App\Http\Controllers\BusinessRuleController::class, 'index'])->name('business-rules.index');
    Route::post('/business-rules/version', [\App\Http\Controllers\BusinessRuleController::class, 'storeVersion'])->name('business-rules.version.store');

    // ── Fase C.3 — Payroll & THP Aggregation Routes ───────────────────────────
    Route::get('/payrolls/aggregation', [\App\Http\Controllers\PayrollAggregationController::class, 'index'])->name('payrolls.aggregation.index');
    Route::post('/payrolls/aggregation/generate', [\App\Http\Controllers\PayrollAggregationController::class, 'generate'])->name('payrolls.aggregation.generate');
    Route::post('/payrolls/aggregation/approve', [\App\Http\Controllers\PayrollAggregationController::class, 'approve'])->name('payrolls.aggregation.approve');
    Route::get('/payrolls/aggregation/slip/{detailId}', [\App\Http\Controllers\PayrollAggregationController::class, 'exportSlipPdf'])->name('payrolls.aggregation.slip');

    // ── Fase D.2 — Investor Financial Dashboard ───────────────────────────────
    Route::get('/investor/dashboard', [\App\Http\Controllers\InvestorDashboardController::class, 'index'])->name('investor.dashboard');
    Route::get('operator/performa', [OperatorProfileController::class, 'index'])->name('operator.performa');
    Route::get('operator/slip-gaji/{payrollDetail}/export-pdf', [OperatorProfileController::class, 'exportSlipPdf'])->name('operator.slip-gaji.pdf');

    // Kasbon / Employee Loans (Operator dapat mengajukan & melihat riwayat kasbon miliknya)
    Route::resource('employee-loans', \App\Http\Controllers\EmployeeLoanController::class);
    Route::post('employee-loans/{employeeLoan}/approve', [\App\Http\Controllers\EmployeeLoanController::class, 'approve'])->name('employee-loans.approve');
    Route::post('employee-loans/{employeeLoan}/reject', [\App\Http\Controllers\EmployeeLoanController::class, 'reject'])->name('employee-loans.reject');

    Route::middleware('role:super-admin,admin,investor')->group(function () {
        Route::resources([
            'shops' => ShopController::class,
            'operators' => OperatorController::class,
        ]);
        
        Route::post('monthly-reports/generate', [\App\Http\Controllers\MonthlyReportController::class, 'generateFromDailyReports'])->name('monthly-reports.generate');
        Route::post('monthly-reports/recalculate-cascade/{shop}', [\App\Http\Controllers\MonthlyReportController::class, 'recalculateCascade'])->name('monthly-reports.recalculate-cascade');
        Route::get('monthly-reports/{id}/export-pdf', [\App\Http\Controllers\MonthlyReportController::class, 'exportPdf'])->name('monthly-reports.export-pdf');
        Route::get('monthly-reports/{id}/download', [\App\Http\Controllers\MonthlyReportController::class, 'download'])->name('monthly-reports.download');
        Route::resource('monthly-reports', \App\Http\Controllers\MonthlyReportController::class);

        // Sistem Penggajian: halaman index bisa dilihat investor (Tab Perbandingan Toko)
        Route::get('payroll-systems', [PayrollSystemController::class, 'index'])->name('payroll-systems.index');
        Route::get('payroll-systems/by-shop/{shop}', [PayrollSystemController::class, 'byShop'])->name('payroll-systems.by-shop');

        // Arsip Upload File Excel Backdate (per Pertashop Container)
        Route::get('backdate-excel-files', [\App\Http\Controllers\BackdateExcelFileController::class, 'index'])->name('backdate-excel-files.index');
        Route::post('backdate-excel-files', [\App\Http\Controllers\BackdateExcelFileController::class, 'store'])->name('backdate-excel-files.store');
        Route::delete('backdate-excel-files/delete-all', [\App\Http\Controllers\BackdateExcelFileController::class, 'deleteAll'])->name('backdate-excel-files.delete-all');
        Route::post('backdate-excel-files/restore-all', [\App\Http\Controllers\BackdateExcelFileController::class, 'restoreAll'])->name('backdate-excel-files.restore-all');
        Route::delete('backdate-excel-files/empty-trash', [\App\Http\Controllers\BackdateExcelFileController::class, 'emptyTrash'])->name('backdate-excel-files.empty-trash');
        Route::post('backdate-excel-files/{id}/restore', [\App\Http\Controllers\BackdateExcelFileController::class, 'restore'])->name('backdate-excel-files.restore');
        Route::delete('backdate-excel-files/{id}/force-delete', [\App\Http\Controllers\BackdateExcelFileController::class, 'forceDelete'])->name('backdate-excel-files.force-delete');
        Route::post('backdate-excel-files/{backdateExcelFile}/sync', [\App\Http\Controllers\BackdateExcelFileController::class, 'sync'])->name('backdate-excel-files.sync');
        Route::get('backdate-excel-files/{backdateExcelFile}', [\App\Http\Controllers\BackdateExcelFileController::class, 'show'])->name('backdate-excel-files.show');
        Route::get('backdate-excel-files/{backdateExcelFile}/stream', [\App\Http\Controllers\BackdateExcelFileController::class, 'stream'])->name('backdate-excel-files.stream');
        Route::get('backdate-excel-files/{backdateExcelFile}/download', [\App\Http\Controllers\BackdateExcelFileController::class, 'download'])->name('backdate-excel-files.download');
        Route::delete('backdate-excel-files/{backdateExcelFile}', [\App\Http\Controllers\BackdateExcelFileController::class, 'destroy'])->name('backdate-excel-files.destroy');

        // Laporan Keuangan & Kasflow
        Route::get('finance/cashflow', [FinanceCashflowController::class, 'index'])->name('finance.cashflow');

        // Jadwal Shift
        Route::resource('shift-schedules', ShiftScheduleController::class);
        Route::post('shift-schedules/bulk', [ShiftScheduleController::class, 'bulkStore'])->name('shift-schedules.bulk');
        Route::post('shift-schedules/request-swap', [ShiftScheduleController::class, 'requestSwap'])->name('shift-schedules.request-swap');
        Route::post('shift-schedules/{shiftSchedule}/swap', [ShiftScheduleController::class, 'swap'])->name('shift-schedules.swap');
        Route::post('shift-swaps/{swap}/approve', [ShiftScheduleController::class, 'approveSwap'])->name('shift-swaps.approve');
        Route::post('shift-swaps/{swap}/reject', [ShiftScheduleController::class, 'rejectSwap'])->name('shift-swaps.reject');

        // Laporan Keuangan Per Outlet
        Route::prefix('laba-kotor')->group(function () {
            Route::get('/', [LabaKotorController::class, 'index'])->name('laba-kotor.index');
            Route::get('/{shop_id}/{year_month}', [LabaKotorController::class, 'edit'])->name('laba-kotor.edit');
        });

        Route::prefix('laba-bersih')->group(function () {
            Route::get('/', [LabaBersihController::class, 'index'])->name('laba-bersih.index');
            Route::get('/{shop_id}/{year_month}', [LabaBersihController::class, 'edit'])->name('laba-bersih.edit');
            Route::post('/{shop_id}/{year_month}/alokasi_modal', [LabaBersihController::class, 'alokasi_modal'])->name('laba-bersih.alokasi-modal');
        });

        Route::prefix('modal')->group(function () {
            Route::get('/', [ModalController::class, 'index'])->name('modal.index');
            Route::get('/{shop_id}/{year_month}', [ModalController::class, 'edit'])->name('modal.edit');
        });

        Route::prefix('profit-sharing')->group(function () {
            Route::get('/', [ProfitSharingController::class, 'index'])->name('profit-sharing.index');
            Route::get('/{shop_id}/{year_month}', [ProfitSharingController::class, 'edit'])->name('profit-sharing.edit');
        });
    });

    Route::middleware('role:super-admin,admin')->group(function () {
        Route::resources([
            'purchases' => PurchaseController::class,
            'corporations' => CorporationController::class,
            'investors' => InvestorController::class,
            'kolektans' => KolektanController::class,
        ]);
        Route::get('analytics/benchmarking', [BenchmarkingController::class, 'index'])->name('analytics.benchmarking');
        Route::get('investors/{investor}/export-pdf', [\App\Http\Controllers\InvestorController::class, 'exportPdf'])->name('investors.export-pdf');

        // Pusat Tindakan (Action Center)
        Route::get('action-center', [\App\Http\Controllers\ActionCenterController::class, 'index'])->name('action-center.index');

        // ─── Penggajian Operator ────────────────────────────────────────────
        // Data Master: Sistem Penggajian (CUD — read sudah di group investor di atas)
        Route::get('payroll-systems/create', [PayrollSystemController::class, 'create'])->name('payroll-systems.create');
        Route::post('payroll-systems', [PayrollSystemController::class, 'store'])->name('payroll-systems.store');
        Route::get('payroll-systems/{payrollSystem}/edit', [PayrollSystemController::class, 'edit'])->name('payroll-systems.edit');
        Route::put('payroll-systems/{payrollSystem}', [PayrollSystemController::class, 'update'])->name('payroll-systems.update');
        Route::patch('payroll-systems/{payrollSystem}', [PayrollSystemController::class, 'update']);
        Route::delete('payroll-systems/{payrollSystem}', [PayrollSystemController::class, 'destroy'])->name('payroll-systems.destroy');
        Route::get('payroll-systems/{payrollSystem}', [PayrollSystemController::class, 'show'])->name('payroll-systems.show');

        // Data Master: Assign Operator ke Sistem Penggajian
        Route::get('payroll-operator-assignments/operators-by-shop/{shop}', [PayrollOperatorAssignmentController::class, 'operatorsByShop'])->name('payroll-operator-assignments.operators-by-shop');
        Route::resource('payroll-operator-assignments', PayrollOperatorAssignmentController::class)->except(['edit', 'update']);

        // Proses Penggajian Bulanan
        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::post('payroll/bulk-generate', [PayrollController::class, 'bulkGenerate'])->name('payroll.bulk-generate');
        Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::patch('payroll/{payroll}/detail/{detail}', [PayrollController::class, 'updateDetail'])->name('payroll.detail.update');
        Route::post('payroll/{payroll}/detail/{detail}/items', [PayrollController::class, 'addItem'])->name('payroll.detail.add-item');
        Route::delete('payroll/{payroll}/detail/{detail}/items/{item}', [PayrollController::class, 'deleteItem'])->name('payroll.detail.delete-item');
        Route::post('payroll/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::get('payroll/{payroll}/export-pdf', [PayrollController::class, 'exportPdf'])->name('payroll.export-pdf');
        Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
        // ────────────────────────────────────────────────────────────────────
    });

    Route::post('/investors/{investor}/add-investment', [InvestorController::class, 'addInvestment'])->name('investors.add-investment');

    Route::get('/capital-recaps', [CapitalRecapController::class, 'index'])->name('capital-recaps.index');
    Route::post('/capital-recaps/recalculate/{shop}', [CapitalRecapController::class, 'recalculate'])->name('capital-recaps.recalculate');
    Route::get('/capital-recaps/import', [CapitalRecapController::class, 'importForm'])->name('capital-recaps.import');
    Route::post('/capital-recaps/import', [CapitalRecapController::class, 'importStore'])->name('capital-recaps.import.store');

    Route::patch('/operators/{operator}/toggle-status', [OperatorController::class, 'toggleStatus'])->name('operators.toggle-status');
    Route::patch('/operators/{operator}/update-credentials', [OperatorController::class, 'updateCredentials'])->name('operators.update-credentials');
    Route::patch('/investors/{investor}/update-credentials', [InvestorController::class, 'updateCredentials'])->name('investors.update-credentials');

    Route::get('/shops/{shop}/investors', [ShopController::class, 'investor'])->name('shops.investors');
    Route::post('/shops/{shop}/investors', [ShopController::class, 'investorStore'])->name('shops.investors.store');
    Route::put('/shops/{shop}/investors', [ShopController::class, 'investorUpdate'])->name('shops.investors.update');
    Route::delete('/shops/{shop}/investors', [ShopController::class, 'investorDestroy'])->name('shops.investors.destroy');
    Route::get('/shops/{shop}/modal-details', [ShopController::class, 'getModalDetails'])->name('shops.modal-details');



    Route::get('/price-audit-logs', function () {
        $logs = \App\Models\PriceAuditLog::with(['user', 'shop'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($l) => [
                'id'             => $l->id,
                'user'           => $l->user?->name ?? '—',
                'shop'           => $l->shop?->nama ?? 'Global',
                'action'         => $l->action,
                'harga_beli_lama'=> $l->harga_beli_lama,
                'harga_jual_lama'=> $l->harga_jual_lama,
                'harga_beli_baru'=> $l->harga_beli_baru,
                'harga_jual_baru'=> $l->harga_jual_baru,
                'created_at'     => $l->created_at,
            ]);
        return response()->json($logs);
    })->name('price-audit-logs.index');

    // Dashboard: get active prices per outlet for admin form
    Route::get('/dashboard/prices', function () {
        $shops = \App\Models\Shop::all();
        $result = $shops->map(function ($shop) {
            $active = \App\Models\Price::where('shop_id', $shop->id)
                ->where('effective_at', '<=', now())
                ->orderBy('effective_at', 'desc')
                ->first();
            if (!$active) {
                $active = \App\Models\Price::whereNull('shop_id')
                    ->where('effective_at', '<=', now())
                    ->orderBy('effective_at', 'desc')
                    ->first();
            }
            return [
                'shop_id'    => $shop->id,
                'shop_nama'  => $shop->nama,
                'shop_kode'  => $shop->kode,
                'harga_beli' => $active ? $active->harga_beli : 0,
                'harga_jual' => $active ? $active->harga_jual : 0,
                'effective_at' => $active ? $active->effective_at : null,
            ];
        });
        return response()->json($result);
    })->name('dashboard.prices.index')->middleware(['auth']);

    // Dashboard: store new price from admin
    Route::post('/dashboard/prices', [PriceController::class, 'storeFromDashboard'])
        ->name('dashboard.prices.store')
        ->middleware(['auth']);
});
