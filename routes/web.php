<?php

use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\ProcurementUiController;
use App\Http\Controllers\ProcurementFlowController;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

Route::prefix('ui')->name('ui.')->middleware('auth')->group(function () {
    Route::get('/dashboard', [ProcurementUiController::class, 'dashboard'])->name('dashboard');

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::PURCHASING->value.','.UserRole::SPPG_USER->value)->group(function () {
        Route::get('/purchase-requests', [ProcurementUiController::class, 'purchaseRequests'])->name('purchase-requests.index');
        Route::post('/purchase-requests', [ProcurementUiController::class, 'storePurchaseRequest'])->name('purchase-requests.store');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value)->group(function () {
        Route::get('/approvals', [ProcurementUiController::class, 'approvalQueue'])->name('approvals.index');
        Route::post('/purchase-requests/{purchaseRequest}/approve', [ProcurementUiController::class, 'approvePurchaseRequest'])->name('purchase-requests.approve');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::PURCHASING->value)->group(function () {
        Route::get('/purchase-orders', [ProcurementUiController::class, 'purchaseOrders'])->name('purchase-orders.index');
        Route::post('/purchase-requests/{purchaseRequest}/generate-po', [ProcurementUiController::class, 'generatePurchaseOrder'])->name('purchase-requests.generate-po');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::ADMIN_GUDANG->value)->group(function () {
        Route::get('/deliveries', [ProcurementUiController::class, 'deliveries'])->name('deliveries.index');
        Route::get('/stock-movements', [ProcurementUiController::class, 'stockMovements'])->name('stock-movements.index');
        Route::get('/stock-alerts', [ProcurementUiController::class, 'stockAlerts'])->name('stock-alerts.index');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::FINANCE->value)->group(function () {
        Route::get('/invoices', [ProcurementUiController::class, 'invoices'])->name('invoices.index');
        Route::post('/deliveries/{delivery}/generate-invoice', [ProcurementUiController::class, 'generateInvoice'])->name('deliveries.generate-invoice');
        Route::get('/billing-cycles', [ProcurementUiController::class, 'billingCycles'])->name('billing-cycles.index');
        Route::get('/payments', [ProcurementUiController::class, 'payments'])->name('payments.index');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::PURCHASING->value)->prefix('master-data')->name('master-data.')->group(function () {
        Route::get('/sppgs', [ProcurementUiController::class, 'masterSppgs'])->name('sppgs.index');
        Route::post('/sppgs', [ProcurementUiController::class, 'storeSppg'])->name('sppgs.store');
        Route::get('/vendors', [ProcurementUiController::class, 'masterVendors'])->name('vendors.index');
        Route::post('/vendors', [ProcurementUiController::class, 'storeVendor'])->name('vendors.store');
        Route::get('/products', [ProcurementUiController::class, 'masterProducts'])->name('products.index');
        Route::post('/products', [ProcurementUiController::class, 'storeProduct'])->name('products.store');
        Route::get('/price-histories', [ProcurementUiController::class, 'priceHistories'])->name('price-histories.index');
        Route::post('/price-histories', [ProcurementUiController::class, 'storePriceHistory'])->name('price-histories.store');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value)->group(function () {
        Route::get('/users-roles', [ProcurementUiController::class, 'usersRoles'])->name('users-roles.index');
        Route::get('/audit-trails', [ProcurementUiController::class, 'auditTrails'])->name('audit-trails.index');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::FINANCE->value.','.UserRole::PURCHASING->value)->group(function () {
        Route::get('/vendor-performances', [ProcurementUiController::class, 'vendorPerformances'])->name('vendor-performances.index');
        Route::get('/price-trends', [ProcurementUiController::class, 'priceTrends'])->name('price-trends.index');
    });
});

Route::prefix('procurement')->group(function () {
    Route::post('/purchase-requests', [ProcurementFlowController::class, 'createPurchaseRequest']);
    Route::post('/purchase-requests/{purchaseRequest}/approve', [ProcurementFlowController::class, 'approvePurchaseRequest']);
    Route::post('/purchase-requests/{purchaseRequest}/purchase-orders', [ProcurementFlowController::class, 'generatePurchaseOrder']);
    Route::post('/deliveries/{delivery}/invoices', [ProcurementFlowController::class, 'generateInvoice']);
});
