<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StocktakeController;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', function () {
    return redirect()->route('sales.index');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes (Staff and Owner)
Route::middleware(['role:owner,staff'])->group(function () {
    // Sales
    Route::get('/jual', [SaleController::class, 'index'])->name('sales.index');
    Route::post('/jual', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/jual/riwayat', [SaleController::class, 'history'])->name('sales.history');
    Route::post('/jual/{id}/void', [SaleController::class, 'voidSale'])->name('sales.void');
    Route::get('/jual/rekap', [SaleController::class, 'summaryForm'])->name('sales.summary');
    Route::post('/jual/rekap', [SaleController::class, 'storeSummary'])->name('sales.summary.store');

    // Dashboard & Alerts
    Route::get('/peringatan', [DashboardController::class, 'index'])->name('dashboard.index');

    // Stocktake
    Route::get('/opname', [StocktakeController::class, 'index'])->name('stocktake.index');
    Route::post('/opname', [StocktakeController::class, 'start'])->name('stocktake.start');
    Route::get('/opname/{id}', [StocktakeController::class, 'show'])->name('stocktake.show');
    Route::post('/opname/{id}/draft', [StocktakeController::class, 'updateDraft'])->name('stocktake.draft');

    // Products (Staff can view)
    Route::get('/produk', [ProductController::class, 'index'])->name('products.index');

    // Owner-Only Web Routes
    Route::middleware(['role:owner'])->group(function () {
        // Purchases (Barang Masuk)
        Route::get('/beli', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/beli', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/beli/riwayat', [PurchaseController::class, 'history'])->name('purchases.history');
        Route::post('/beli/{id}/void', [PurchaseController::class, 'voidPurchase'])->name('purchases.void');

        // Products Modification
        Route::post('/produk', [ProductController::class, 'store'])->name('products.store');
        Route::post('/produk/{id}', [ProductController::class, 'update'])->name('products.update');
        Route::post('/produk/{id}/nonaktifkan', [ProductController::class, 'deactivate'])->name('products.deactivate');

        // Waste Action
        Route::post('/peringatan/buang/{batchId}', [DashboardController::class, 'waste'])->name('dashboard.waste');

        // Stocktake confirmation & Opening Balance
        Route::post('/opname/{id}/konfirmasi', [StocktakeController::class, 'confirm'])->name('stocktake.confirm');
        Route::get('/opname-saldo-awal', [StocktakeController::class, 'openingBalanceForm'])->name('stocktake.opening');
        Route::post('/opname-saldo-awal', [StocktakeController::class, 'storeOpeningBalance'])->name('stocktake.opening.store');

        // Cash & Closing
        Route::get('/kas', [CashController::class, 'index'])->name('cash.index');
        Route::post('/kas/tutup', [CashController::class, 'close'])->name('cash.close');
        Route::post('/kas/{id}/buka-ulang', [CashController::class, 'reopen'])->name('cash.reopen');

        // Expenses
        Route::get('/kas/pengeluaran', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/kas/pengeluaran', [ExpenseController::class, 'store'])->name('expenses.store');

        // Export
        Route::get('/ekspor', [ExportController::class, 'index'])->name('export.index');
        Route::get('/ekspor/unduh/{dataset}', [ExportController::class, 'download'])->name('export.download');

        // Settings & Staff Management
        Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/pengaturan/pegawai', [SettingController::class, 'storeStaff'])->name('settings.staff.store');
        Route::post('/pengaturan/pegawai/{id}/reset-pin', [SettingController::class, 'resetStaffPin'])->name('settings.staff.reset_pin');
        Route::post('/pengaturan/pegawai/{id}/toggle', [SettingController::class, 'toggleStaffStatus'])->name('settings.staff.toggle');
        Route::post('/pengaturan/kategori/{id}', [SettingController::class, 'updateCategory'])->name('settings.category.update');
    });
});
