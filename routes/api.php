<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\PurchaseRequestController;
use App\Http\Controllers\ProcurementFlowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('purchase-requests', PurchaseRequestController::class)
            ->only(['index', 'show'])
            ->middleware('role:super_admin,owner,purchasing,sppg_user');

        Route::apiResource('purchase-orders', PurchaseOrderController::class)
            ->only(['index', 'show'])
            ->middleware('role:super_admin,owner,purchasing,vendor_admin');

        Route::apiResource('deliveries', DeliveryController::class)
            ->only(['index', 'show'])
            ->middleware('role:super_admin,owner,admin_gudang,vendor_admin');

        Route::apiResource('invoices', InvoiceController::class)
            ->only(['index', 'show'])
            ->middleware('role:super_admin,owner,finance,vendor_admin');

        Route::post('purchase-requests', [ProcurementFlowController::class, 'createPurchaseRequest'])
            ->middleware('role:super_admin,sppg_user,purchasing');

        Route::post('purchase-requests/{purchaseRequest}/approve', [ProcurementFlowController::class, 'approvePurchaseRequest'])
            ->middleware('role:super_admin,owner');

        Route::post('purchase-requests/{purchaseRequest}/purchase-orders', [ProcurementFlowController::class, 'generatePurchaseOrder'])
            ->middleware('role:super_admin,purchasing');

        Route::post('deliveries/{delivery}/invoices', [ProcurementFlowController::class, 'generateInvoice'])
            ->middleware('role:super_admin,finance,purchasing');
    });
});
