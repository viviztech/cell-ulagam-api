<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CashMovementController;
use App\Http\Controllers\Api\V1\CashSessionController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpenseCategoryController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductImeiController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SaleReturnController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\ShopController;
use App\Http\Controllers\Api\V1\StockMovementController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health check endpoint (public)
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0.0',
        ]);
    });

    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Authenticated routes
    Route::middleware(['auth:sanctum', 'shop'])->group(function () {
        // Auth
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/change-password', [AuthController::class, 'changePassword']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Super Admin only routes
        Route::middleware('role:super_admin')->group(function () {
            Route::apiResource('shops', ShopController::class);
            Route::patch('/shops/{shop}/toggle-active', [ShopController::class, 'toggleActive']);
            Route::get('/shops/{shop}/stats', [ShopController::class, 'stats']);
            Route::get('/dashboard/super-admin', [DashboardController::class, 'superAdmin']);
            Route::get('/reports/cross-shop/overview', [ReportController::class, 'crossShopOverview']);
        });

        // Super Admin + Shop Admin routes
        Route::middleware('role:super_admin,shop_admin')->group(function () {
            Route::apiResource('users', UserController::class);
            Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{category}', [CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
            Route::apiResource('expense-categories', ExpenseCategoryController::class)->except(['index', 'show']);
            Route::get('/settings', [SettingController::class, 'index']);
            Route::put('/settings', [SettingController::class, 'update']);
        });

        // Super Admin + Shop Admin + Manager routes
        Route::middleware('role:super_admin,shop_admin,manager')->group(function () {
            Route::apiResource('suppliers', SupplierController::class);
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{product}', [ProductController::class, 'update']);
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);
            Route::post('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);
            Route::get('/products/{product}/imeis', [ProductImeiController::class, 'index']);
            Route::post('/products/{product}/imeis', [ProductImeiController::class, 'store']);
            Route::get('/imeis/{imei}', [ProductImeiController::class, 'show']);
            Route::put('/imeis/{imei}', [ProductImeiController::class, 'update']);
            Route::delete('/imeis/{imei}', [ProductImeiController::class, 'destroy']);
            Route::apiResource('expenses', ExpenseController::class);
            Route::apiResource('sale-returns', SaleReturnController::class)->only(['index', 'store', 'show']);
            Route::get('/cash-movements', [CashMovementController::class, 'index']);
            Route::post('/cash-movements', [CashMovementController::class, 'store']);
            Route::get('/stock-movements', [StockMovementController::class, 'index']);
            Route::get('/stock-movements/{stockMovement}', [StockMovementController::class, 'show']);

            // Reports
            Route::prefix('reports')->group(function () {
                Route::get('/daily-summary', [ReportController::class, 'dailySummary']);
                Route::get('/sales-summary', [ReportController::class, 'salesSummary']);
                Route::get('/stock-report', [ReportController::class, 'stockReport']);
            });
        });

        // All authenticated users
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        Route::get('/expense-categories', [ExpenseCategoryController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/search', [ProductController::class, 'search']);
        Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
        Route::get('/products/options', [ProductController::class, 'options']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::get('/imeis/search', [ProductImeiController::class, 'search']);
        Route::get('/imeis/available', [ProductImeiController::class, 'available']);
        Route::get('/customers/search', [CustomerController::class, 'search']);
        Route::apiResource('customers', CustomerController::class);
        Route::get('/sales', [SaleController::class, 'index']);
        Route::post('/sales', [SaleController::class, 'store']);
        Route::get('/sales/today', [SaleController::class, 'today']);
        Route::get('/sales/by-user', [SaleController::class, 'byUser']);
        Route::get('/sales/{sale}', [SaleController::class, 'show']);
        Route::post('/cash-sessions/open', [CashSessionController::class, 'open']);
        Route::get('/cash-sessions/current', [CashSessionController::class, 'current']);
        Route::get('/cash-sessions', [CashSessionController::class, 'index']);
        Route::get('/cash-sessions/{cashSession}', [CashSessionController::class, 'show']);
        Route::post('/cash-sessions/{cashSession}/close', [CashSessionController::class, 'close']);
        Route::get('/settings/{key}', [SettingController::class, 'show']);
    });
});
