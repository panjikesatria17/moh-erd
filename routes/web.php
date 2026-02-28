<?php

use App\Http\Controllers\ProcurementFlowController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::prefix('procurement')->group(function () {
    Route::post('/purchase-requests', [ProcurementFlowController::class, 'createPurchaseRequest']);
    Route::post('/purchase-requests/{purchaseRequest}/approve', [ProcurementFlowController::class, 'approvePurchaseRequest']);
    Route::post('/purchase-requests/{purchaseRequest}/purchase-orders', [ProcurementFlowController::class, 'generatePurchaseOrder']);
    Route::post('/deliveries/{delivery}/invoices', [ProcurementFlowController::class, 'generateInvoice']);
});
