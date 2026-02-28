<?php

use App\Http\Controllers\Web\ProcurementUiController;
use App\Http\Controllers\ProcurementFlowController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('ui.dashboard');
});

Route::prefix('ui')->name('ui.')->group(function () {
    Route::get('/dashboard', [ProcurementUiController::class, 'dashboard'])->name('dashboard');
    Route::get('/purchase-requests', [ProcurementUiController::class, 'purchaseRequests'])->name('purchase-requests.index');
    Route::post('/purchase-requests', [ProcurementUiController::class, 'storePurchaseRequest'])->name('purchase-requests.store');
    Route::post('/purchase-requests/{purchaseRequest}/approve', [ProcurementUiController::class, 'approvePurchaseRequest'])->name('purchase-requests.approve');
    Route::post('/purchase-requests/{purchaseRequest}/generate-po', [ProcurementUiController::class, 'generatePurchaseOrder'])->name('purchase-requests.generate-po');
    Route::get('/purchase-orders', [ProcurementUiController::class, 'purchaseOrders'])->name('purchase-orders.index');
    Route::get('/deliveries', [ProcurementUiController::class, 'deliveries'])->name('deliveries.index');
    Route::post('/deliveries/{delivery}/generate-invoice', [ProcurementUiController::class, 'generateInvoice'])->name('deliveries.generate-invoice');
    Route::get('/invoices', [ProcurementUiController::class, 'invoices'])->name('invoices.index');
});

Route::prefix('procurement')->group(function () {
    Route::post('/purchase-requests', [ProcurementFlowController::class, 'createPurchaseRequest']);
    Route::post('/purchase-requests/{purchaseRequest}/approve', [ProcurementFlowController::class, 'approvePurchaseRequest']);
    Route::post('/purchase-requests/{purchaseRequest}/purchase-orders', [ProcurementFlowController::class, 'generatePurchaseOrder']);
    Route::post('/deliveries/{delivery}/invoices', [ProcurementFlowController::class, 'generateInvoice']);
});
