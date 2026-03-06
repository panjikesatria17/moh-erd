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
        Route::get('/purchase-requests/{purchaseRequest}/download', [ProcurementUiController::class, 'downloadPurchaseRequestPdf'])->name('purchase-requests.download');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::PURCHASING->value.','.UserRole::SPPG_USER->value)->group(function () {
        Route::post('/purchase-requests', [ProcurementUiController::class, 'storePurchaseRequest'])->name('purchase-requests.store');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value)->group(function () {
        Route::get('/approvals', [ProcurementUiController::class, 'approvalQueue'])->name('approvals.index');
        Route::post('/approvals/{approval}/approve', [ProcurementUiController::class, 'approveQueueItem'])->name('approvals.approve');
        Route::post('/approvals/{approval}/reject', [ProcurementUiController::class, 'rejectQueueItem'])->name('approvals.reject');
        Route::post('/purchase-requests/{purchaseRequest}/approve', [ProcurementUiController::class, 'approvePurchaseRequest'])->name('purchase-requests.approve');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value)->group(function () {
        Route::post('/approvals/settings/po-threshold', [ProcurementUiController::class, 'updatePoOwnerApprovalThreshold'])->name('approvals.settings.po-threshold.update');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::PURCHASING->value.','.UserRole::VENDOR_ADMIN->value)->group(function () {
        Route::get('/purchase-orders', [ProcurementUiController::class, 'purchaseOrders'])->name('purchase-orders.index');
        Route::get('/purchase-orders/{purchaseOrder}/download', [ProcurementUiController::class, 'downloadPurchaseOrderPdf'])->name('purchase-orders.download');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::PURCHASING->value)->group(function () {
        Route::post('/purchase-requests/{purchaseRequest}/assign-requester', [ProcurementUiController::class, 'assignPurchaseRequestRequester'])->name('purchase-requests.assign-requester');
        Route::post('/purchase-requests/{purchaseRequest}/generate-po', [ProcurementUiController::class, 'generatePurchaseOrder'])->name('purchase-requests.generate-po');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::ADMIN_GUDANG->value.','.UserRole::EXPEDITION->value.','.UserRole::VENDOR_ADMIN->value)->group(function () {
        Route::get('/deliveries', [ProcurementUiController::class, 'deliveries'])->name('deliveries.index');
        Route::get('/deliveries/{delivery}/surat-jalan', [ProcurementUiController::class, 'previewDeliveryNotePdf'])->name('deliveries.surat-jalan.preview');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::ADMIN_GUDANG->value.','.UserRole::SPPG_USER->value)->group(function () {
        Route::get('/rejected-items', [ProcurementUiController::class, 'rejectedItems'])->name('rejected-items.index');
        Route::post('/rejected-items', [ProcurementUiController::class, 'storeRejectedItem'])->name('rejected-items.store');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::ADMIN_GUDANG->value)->group(function () {
        Route::get('/stock-movements', [ProcurementUiController::class, 'stockMovements'])->name('stock-movements.index');
        Route::get('/stock-alerts', [ProcurementUiController::class, 'stockAlerts'])->name('stock-alerts.index');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::ADMIN_GUDANG->value.','.UserRole::EXPEDITION->value)->group(function () {
        Route::post('/purchase-orders/{purchaseOrder}/create-delivery', [ProcurementUiController::class, 'createDeliveryFromPurchaseOrder'])->name('purchase-orders.create-delivery');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::EXPEDITION->value)->group(function () {
        Route::post('/deliveries/{delivery}/complete', [ProcurementUiController::class, 'completeDeliveryByExpedition'])->name('deliveries.complete');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::FINANCE->value.','.UserRole::VENDOR_ADMIN->value)->group(function () {
        Route::get('/invoices', [ProcurementUiController::class, 'invoices'])->name('invoices.index');
        Route::get('/invoices/{invoice}/download', [ProcurementUiController::class, 'downloadInvoicePdf'])->name('invoices.download');
        Route::get('/invoices-summary/download', [ProcurementUiController::class, 'downloadVendorInvoiceSummaryPdf'])->name('invoices.summary.download');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::FINANCE->value)->group(function () {
        Route::get('/kwitansi', [ProcurementUiController::class, 'kwitansi'])->name('kwitansi.index');
        Route::get('/kwitansi/{kwitansi}/download', [ProcurementUiController::class, 'downloadKwitansiPdf'])->name('kwitansi.download');
        Route::get('/billing-cycles', [ProcurementUiController::class, 'billingCycles'])->name('billing-cycles.index');
        Route::get('/purchase-funding-requests', [ProcurementUiController::class, 'purchaseFundingRequests'])->name('purchase-funding-requests.index');
        Route::get('/purchase-funding-requests/export', [ProcurementUiController::class, 'exportPurchaseFundingRequestsExcel'])->name('purchase-funding-requests.export');
        Route::get('/purchase-funding-requests/export-pdf', [ProcurementUiController::class, 'downloadPurchaseFundingRequestsPdf'])->name('purchase-funding-requests.export-pdf');
        Route::post('/purchase-funding-requests', [ProcurementUiController::class, 'storePurchaseFundingRequest'])->name('purchase-funding-requests.store');
        Route::post('/purchase-funding-requests/{purchaseFundingRequest}/approve', [ProcurementUiController::class, 'approvePurchaseFundingRequest'])->name('purchase-funding-requests.approve');
        Route::post('/purchase-funding-requests/{purchaseFundingRequest}/reject', [ProcurementUiController::class, 'rejectPurchaseFundingRequest'])->name('purchase-funding-requests.reject');
    });

    Route::post('/notifications/read-all', [ProcurementUiController::class, 'markAllNotificationsAsRead'])->name('notifications.read-all');

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::FINANCE->value)->group(function () {
        Route::post('/purchase-funding-requests/settings/owner-threshold', [ProcurementUiController::class, 'updatePurchaseFundingOwnerApprovalThreshold'])->name('purchase-funding-requests.settings.owner-threshold.update');
        Route::post('/purchase-funding-requests/{purchaseFundingRequest}/review', [ProcurementUiController::class, 'reviewPurchaseFundingRequest'])->name('purchase-funding-requests.review');
        Route::post('/purchase-funding-requests/{purchaseFundingRequest}/disburse', [ProcurementUiController::class, 'disbursePurchaseFundingRequest'])->name('purchase-funding-requests.disburse');
        Route::post('/purchase-funding-requests/{purchaseFundingRequest}/settle', [ProcurementUiController::class, 'settlePurchaseFundingRequest'])->name('purchase-funding-requests.settle');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::FINANCE->value.','.UserRole::SPPG_USER->value)->group(function () {
        Route::get('/payments', [ProcurementUiController::class, 'payments'])->name('payments.index');
    });

    Route::middleware('role:'.UserRole::SPPG_USER->value)->group(function () {
        Route::post('/payments/{payment}/upload-proof', [ProcurementUiController::class, 'uploadPaymentProof'])->name('payments.upload-proof');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::FINANCE->value)->group(function () {
        Route::post('/payments/{payment}/approve', [ProcurementUiController::class, 'approvePaymentProof'])->name('payments.approve');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::FINANCE->value)->group(function () {
        Route::post('/invoices/{invoice}/create-payment', [ProcurementUiController::class, 'createInvoicePayment'])->name('invoices.create-payment');
        Route::post('/kwitansi', [ProcurementUiController::class, 'storeKwitansi'])->name('kwitansi.store');
        Route::post('/purchase-orders/{purchaseOrder}/generate-invoice', [ProcurementUiController::class, 'generateInvoiceFromPurchaseOrder'])->name('purchase-orders.generate-invoice');
        Route::post('/deliveries/{delivery}/generate-invoice', [ProcurementUiController::class, 'generateInvoice'])->name('deliveries.generate-invoice');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value.','.UserRole::PURCHASING->value)->prefix('master-data')->name('master-data.')->group(function () {
        Route::get('/sppgs', [ProcurementUiController::class, 'masterSppgs'])->name('sppgs.index');
        Route::get('/vendors', [ProcurementUiController::class, 'masterVendors'])->name('vendors.index');
        Route::get('/products', [ProcurementUiController::class, 'masterProducts'])->name('products.index');
        Route::get('/products/export', [ProcurementUiController::class, 'exportProductsExcel'])->name('products.export');
        Route::get('/products/export-pdf', [ProcurementUiController::class, 'exportProductsPdf'])->name('products.export-pdf');
        Route::get('/price-histories', [ProcurementUiController::class, 'priceHistories'])->name('price-histories.index');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value)->prefix('master-data')->name('master-data.')->group(function () {
        Route::post('/sppgs', [ProcurementUiController::class, 'storeSppg'])->name('sppgs.store');
        Route::get('/sppgs/{sppg}/edit', [ProcurementUiController::class, 'editSppg'])->name('sppgs.edit');
        Route::put('/sppgs/{sppg}', [ProcurementUiController::class, 'updateSppg'])->name('sppgs.update');
        Route::delete('/sppgs/{sppg}', [ProcurementUiController::class, 'destroySppg'])->name('sppgs.destroy');
        Route::post('/vendors', [ProcurementUiController::class, 'storeVendor'])->name('vendors.store');
        Route::get('/vendors/{vendor}/edit', [ProcurementUiController::class, 'editVendor'])->name('vendors.edit');
        Route::put('/vendors/{vendor}', [ProcurementUiController::class, 'updateVendor'])->name('vendors.update');
        Route::delete('/vendors/{vendor}', [ProcurementUiController::class, 'destroyVendor'])->name('vendors.destroy');
        Route::post('/product-categories', [ProcurementUiController::class, 'storeProductCategory'])->name('product-categories.store');
        Route::get('/product-categories/{productCategory}/edit', [ProcurementUiController::class, 'editProductCategory'])->name('product-categories.edit');
        Route::put('/product-categories/{productCategory}', [ProcurementUiController::class, 'updateProductCategory'])->name('product-categories.update');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::PURCHASING->value)->prefix('master-data')->name('master-data.')->group(function () {
        Route::post('/products/import', [ProcurementUiController::class, 'importProductsExcel'])->name('products.import');
        Route::post('/products', [ProcurementUiController::class, 'storeProduct'])->name('products.store');
        Route::get('/products/{product}/edit', [ProcurementUiController::class, 'editProduct'])->name('products.edit');
        Route::put('/products/{product}', [ProcurementUiController::class, 'updateProduct'])->name('products.update');
        Route::post('/products/{product}/promote-ad-hoc', [ProcurementUiController::class, 'promoteAdHocProduct'])->name('products.promote-ad-hoc');
        Route::delete('/products/{product}', [ProcurementUiController::class, 'destroyProduct'])->name('products.destroy');
        Route::post('/price-histories', [ProcurementUiController::class, 'storePriceHistory'])->name('price-histories.store');
        Route::get('/price-histories/{productPriceHistory}/edit', [ProcurementUiController::class, 'editPriceHistory'])->name('price-histories.edit');
        Route::put('/price-histories/{productPriceHistory}', [ProcurementUiController::class, 'updatePriceHistory'])->name('price-histories.update');
        Route::delete('/price-histories/{productPriceHistory}', [ProcurementUiController::class, 'destroyPriceHistory'])->name('price-histories.destroy');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value)->group(function () {
        Route::get('/users-roles', [ProcurementUiController::class, 'usersRoles'])->name('users-roles.index');
        Route::post('/users-roles', [ProcurementUiController::class, 'storeUserRole'])->name('users-roles.store');
        Route::get('/users-roles/{user}/edit', [ProcurementUiController::class, 'editUserRole'])->name('users-roles.edit');
        Route::put('/users-roles/{user}', [ProcurementUiController::class, 'updateUserRole'])->name('users-roles.update');
        Route::delete('/users-roles/{user}', [ProcurementUiController::class, 'destroyUserRole'])->name('users-roles.destroy');
    });

    Route::middleware('role:'.UserRole::SUPER_ADMIN->value.','.UserRole::OWNER->value)->group(function () {
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
