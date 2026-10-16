<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StocktakeController;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Auth
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated Routes (cookie session)
Route::middleware(['web', 'role:owner,staff'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Products (All users; for staff last_buy_price is masked)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/categories', function () {
        return response()->json(['code' => 200, 'data' => Category::orderBy('name')->get()]);
    });

    // Sales (All users)
    Route::post('/sales', [SaleController::class, 'store']);
    Route::post('/sales/summary', [SaleController::class, 'storeSummary']);
    Route::get('/sales', [SaleController::class, 'history']);
    Route::post('/sales/{id}/void', [SaleController::class, 'voidSale']);

    // Dashboard & Warnings (All users)
    Route::get('/dashboard/summary', [DashboardController::class, 'index']);

    // Stocktake (All users can draft; only owner confirms)
    Route::post('/stocktakes', [StocktakeController::class, 'start']);
    Route::get('/stocktakes/{id}', [StocktakeController::class, 'show']);
    Route::put('/stocktakes/{id}/items', [StocktakeController::class, 'updateDraft']);

    // Owner-Only Endpoints (403 for staff)
    Route::middleware('role:owner')->group(function () {
        // Products management
        Route::post('/products', [ProductController::class, 'store']);
        Route::patch('/products/{id}', [ProductController::class, 'update']);
        Route::post('/products/{id}/deactivate', [ProductController::class, 'deactivate']);

        // Purchases (BEL-01 to BEL-04)
        Route::post('/purchases', [PurchaseController::class, 'store']);
        Route::get('/purchases', [PurchaseController::class, 'history']);
        Route::post('/purchases/{id}/void', [PurchaseController::class, 'voidPurchase']);

        // Waste action (DSH-03)
        Route::post('/batches/{id}/waste', [DashboardController::class, 'waste']);

        // Confirm Stocktake & Opening Balance
        Route::post('/stocktakes/{id}/confirm', [StocktakeController::class, 'confirm']);
        Route::post('/opening-balance', [StocktakeController::class, 'storeOpeningBalance']);

        // Cash & Closing
        Route::get('/cash/daily', [CashController::class, 'index']);
        Route::post('/cash/close', [CashController::class, 'close']);
        Route::post('/cash/reopen', [CashController::class, 'reopen']);

        // Expenses
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::post('/expenses', [ExpenseController::class, 'store']);

        // Export
        Route::get('/export/{dataset}', [ExportController::class, 'download']);

        // User management
        Route::get('/users', function () {
            return response()->json(['code' => 200, 'data' => User::all()]);
        });
    });
});
