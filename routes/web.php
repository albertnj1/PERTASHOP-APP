<?php

use App\Models\Incoming;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleController;
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
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\KolektanController;
use App\Http\Controllers\CapitalRecapController;

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
Route::delete('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resources([
        'sales' => SaleController::class,
        'purchases' => PurchaseController::class,
        'incomings' => IncomingController::class,
        'test-pumps' => TestPumpController::class,
        'daily-reports' => DailyReportController::class,
        'spendings' => SpendingController::class,

        'corporations' => CorporationController::class,
        'prices' => PriceController::class,
        'shops' => ShopController::class,
        'investors' => InvestorController::class,
        'operators' => OperatorController::class,
        'kolektans' => KolektanController::class,
    ]);

    Route::get('/capital-recaps', [CapitalRecapController::class, 'index'])->name('capital-recaps.index');

    Route::patch('/operators/{operator}/toggle-status', [OperatorController::class, 'toggleStatus'])->name('operators.toggle-status');

    Route::get('/shops/{shop}/investors', [ShopController::class, 'investor'])->name('shops.investors');
    Route::post('/shops/{shop}/investors', [ShopController::class, 'investorStore'])->name('shops.investors.store');
    Route::put('/shops/{shop}/investors', [ShopController::class, 'investorUpdate'])->name('shops.investors.update');
    Route::delete('/shops/{shop}/investors', [ShopController::class, 'investorDestroy'])->name('shops.investors.destroy');
    Route::get('/shops/{shop}/modal-details', [ShopController::class, 'getModalDetails'])->name('shops.modal-details');

    Route::get('daily-report-uploads/{id}/download', [\App\Http\Controllers\DailyReportUploadController::class, 'download'])->name('daily-report-uploads.download');
    Route::resource('daily-report-uploads', \App\Http\Controllers\DailyReportUploadController::class);
    
    Route::get('monthly-reports/{id}/download', [\App\Http\Controllers\MonthlyReportController::class, 'download'])->name('monthly-reports.download');
    Route::resource('monthly-reports', \App\Http\Controllers\MonthlyReportController::class);

    // Route::prefix('spendings')->group(function () {
    //     Route::get('/', [SpendingController::class, 'index'])->name('spendings.index');
    //     Route::get('/create', [SpendingController::class, 'create'])->name('spendings.create');
    //     Route::get('/{shop_id}/{year_month}', [SpendingController::class, 'edit'])->name('spendings.edit');
    // });

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
