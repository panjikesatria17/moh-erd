<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\Approval;
use App\Models\AuditTrail;
use App\Models\BillingCycle;
use App\Models\Delivery;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Sppg;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\VendorPerformance;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcurementDemoTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $sppg = Sppg::query()->where('code', 'SPPG-JKT-01')->firstOrFail();
            $warehouse = Warehouse::query()->where('code', 'WH-HO-01')->firstOrFail();
            $vendor = $sppg->defaultVendor;

            $sppgUser = User::query()->where('email', 'sppg.jkt01@ho.local')->firstOrFail();
            $owner = User::query()->where('email', 'owner@ho.local')->firstOrFail();
            $purchasing = User::query()->where('email', 'purchasing@ho.local')->firstOrFail();
            $gudang = User::query()->where('email', 'gudang@ho.local')->firstOrFail();
            $finance = User::query()->where('email', 'finance@ho.local')->firstOrFail();

            $chicken = Product::query()->where('sku', 'PRD-001')->firstOrFail();
            $rice = Product::query()->where('sku', 'PRD-003')->firstOrFail();

            $purchaseRequest = PurchaseRequest::query()->updateOrCreate(
                ['number' => 'PR-20260301-0001'],
                [
                    'sppg_id' => $sppg->id,
                    'requested_by' => $sppgUser->id,
                    'request_date' => Carbon::now()->subDays(6)->toDateString(),
                    'needed_date' => Carbon::now()->subDays(3)->toDateString(),
                    'status' => DocumentStatus::PROCESSED,
                    'notes' => 'Kebutuhan bahan menu mingguan SPPG Jakarta 01',
                    'total_amount' => 0,
                ]
            );

            PurchaseRequestItem::query()->updateOrCreate(
                [
                    'purchase_request_id' => $purchaseRequest->id,
                    'product_id' => $chicken->id,
                ],
                [
                    'quantity' => 120,
                    'requested_unit_price' => 40000,
                    'subtotal' => 4800000,
                    'notes' => 'Untuk menu ayam minggu berjalan',
                ]
            );

            PurchaseRequestItem::query()->updateOrCreate(
                [
                    'purchase_request_id' => $purchaseRequest->id,
                    'product_id' => $rice->id,
                ],
                [
                    'quantity' => 200,
                    'requested_unit_price' => 14500,
                    'subtotal' => 2900000,
                    'notes' => 'Cadangan beras 1 minggu',
                ]
            );

            $purchaseRequest->recalculateTotal();

            Approval::query()->updateOrCreate(
                [
                    'approvable_type' => PurchaseRequest::class,
                    'approvable_id' => $purchaseRequest->id,
                    'level' => 1,
                    'approver_id' => $owner->id,
                ],
                [
                    'status' => DocumentStatus::APPROVED,
                    'note' => 'Disetujui owner',
                    'approved_at' => Carbon::now()->subDays(5),
                ]
            );

            $purchaseOrder = PurchaseOrder::query()->updateOrCreate(
                ['number' => 'PO-20260301-0001'],
                [
                    'purchase_request_id' => $purchaseRequest->id,
                    'sppg_id' => $sppg->id,
                    'vendor_id' => $vendor?->id,
                    'ordered_by' => $purchasing->id,
                    'order_date' => Carbon::now()->subDays(5)->toDateString(),
                    'expected_date' => Carbon::now()->subDays(3)->toDateString(),
                    'status' => DocumentStatus::PROCESSED,
                    'is_direct_purchase' => false,
                    'notes' => 'PO sesuai PR mingguan',
                    'total_amount' => 0,
                ]
            );

            PurchaseOrderItem::query()->updateOrCreate(
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $chicken->id,
                ],
                [
                    'quantity' => 120,
                    'unit_price' => 40000,
                    'subtotal' => 4800000,
                ]
            );

            PurchaseOrderItem::query()->updateOrCreate(
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $rice->id,
                ],
                [
                    'quantity' => 200,
                    'unit_price' => 14500,
                    'subtotal' => 2900000,
                ]
            );

            $purchaseOrder->recalculateTotal();

            $goodsReceipt = GoodsReceipt::query()->updateOrCreate(
                ['number' => 'GR-20260301-0001'],
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'warehouse_id' => $warehouse->id,
                    'received_by' => $gudang->id,
                    'received_date' => Carbon::now()->subDays(4)->toDateString(),
                    'status' => DocumentStatus::PROCESSED,
                    'inspection_notes' => 'Barang sesuai spesifikasi',
                ]
            );

            StockMovement::query()->updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $chicken->id,
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $goodsReceipt->id,
                    'type' => StockMovementType::IN,
                ],
                [
                    'quantity' => 120,
                    'balance_after' => 120,
                    'movement_date' => Carbon::now()->subDays(4)->toDateString(),
                    'created_by' => $gudang->id,
                    'notes' => 'Penerimaan PO-20260301-0001',
                ]
            );

            StockMovement::query()->updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $rice->id,
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $goodsReceipt->id,
                    'type' => StockMovementType::IN,
                ],
                [
                    'quantity' => 200,
                    'balance_after' => 200,
                    'movement_date' => Carbon::now()->subDays(4)->toDateString(),
                    'created_by' => $gudang->id,
                    'notes' => 'Penerimaan PO-20260301-0001',
                ]
            );

            $delivery = Delivery::query()->updateOrCreate(
                ['number' => 'SJ-20260301-0001'],
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'goods_receipt_id' => $goodsReceipt->id,
                    'sppg_id' => $sppg->id,
                    'vendor_id' => $vendor?->id,
                    'delivered_by' => $gudang->id,
                    'delivery_date' => Carbon::now()->subDays(2)->toDateString(),
                    'status' => DocumentStatus::INVOICED,
                    'total_amount' => (float) $purchaseOrder->total_amount,
                    'notes' => 'Pengiriman mingguan ke SPPG',
                ]
            );

            $weekStart = Carbon::parse($delivery->delivery_date)->startOfWeek(Carbon::MONDAY)->toDateString();
            $weekEnd = Carbon::parse($delivery->delivery_date)->endOfWeek(Carbon::SUNDAY)->toDateString();

            $billingCycle = BillingCycle::query()->updateOrCreate(
                [
                    'sppg_id' => $sppg->id,
                    'week_start_date' => $weekStart,
                    'week_end_date' => $weekEnd,
                ],
                [
                    'status' => DocumentStatus::PAID,
                    'created_by' => $finance->id,
                ]
            );

            $invoice = Invoice::query()->updateOrCreate(
                ['number' => 'INV-20260301-0001'],
                [
                    'billing_cycle_id' => $billingCycle->id,
                    'delivery_id' => $delivery->id,
                    'sppg_id' => $sppg->id,
                    'vendor_id' => $vendor?->id,
                    'invoice_date' => Carbon::now()->subDay()->toDateString(),
                    'due_date' => Carbon::now()->addDays(6)->toDateString(),
                    'status' => DocumentStatus::PAID,
                    'subtotal_amount' => (float) $delivery->total_amount,
                    'tax_amount' => 0,
                    'total_amount' => (float) $delivery->total_amount,
                ]
            );

            Payment::query()->updateOrCreate(
                ['number' => 'PAY-20260301-0001'],
                [
                    'invoice_id' => $invoice->id,
                    'payment_date' => Carbon::now()->toDateString(),
                    'amount' => (float) $invoice->total_amount,
                    'status' => PaymentStatus::PAID,
                    'payment_method' => 'bank_transfer',
                    'reference_no' => 'TRX-HO-0001',
                    'paid_by' => $finance->id,
                    'notes' => 'Pembayaran invoice mingguan',
                ]
            );

            StockMovement::query()->updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $chicken->id,
                    'reference_type' => Delivery::class,
                    'reference_id' => $delivery->id,
                    'type' => StockMovementType::OUT,
                ],
                [
                    'quantity' => 110,
                    'balance_after' => 10,
                    'movement_date' => Carbon::parse($delivery->delivery_date)->toDateString(),
                    'created_by' => $gudang->id,
                    'notes' => 'Distribusi ke SPPG-JKT-01',
                ]
            );

            StockMovement::query()->updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $rice->id,
                    'reference_type' => Delivery::class,
                    'reference_id' => $delivery->id,
                    'type' => StockMovementType::OUT,
                ],
                [
                    'quantity' => 180,
                    'balance_after' => 20,
                    'movement_date' => Carbon::parse($delivery->delivery_date)->toDateString(),
                    'created_by' => $gudang->id,
                    'notes' => 'Distribusi ke SPPG-JKT-01',
                ]
            );

            StockAlert::query()->updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $chicken->id,
                ],
                [
                    'current_balance' => 10,
                    'minimum_stock_level' => 50,
                    'is_resolved' => false,
                    'resolved_at' => null,
                    'resolved_by' => null,
                ]
            );

            VendorPerformance::query()->updateOrCreate(
                [
                    'vendor_id' => $vendor?->id,
                    'period_start' => Carbon::now()->startOfMonth()->toDateString(),
                    'period_end' => Carbon::now()->endOfMonth()->toDateString(),
                ],
                [
                    'on_time_delivery_count' => 8,
                    'late_delivery_count' => 1,
                    'quality_issue_count' => 0,
                    'average_lead_time_days' => 2.5,
                    'score' => 92,
                ]
            );

            AuditTrail::query()->updateOrCreate(
                [
                    'event' => 'seed.purchase_request',
                    'auditable_type' => PurchaseRequest::class,
                    'auditable_id' => $purchaseRequest->id,
                ],
                [
                    'user_id' => $sppgUser->id,
                    'old_values' => null,
                    'new_values' => [
                        'number' => $purchaseRequest->number,
                        'status' => $purchaseRequest->status?->value,
                    ],
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'database-seeder',
                ]
            );

            AuditTrail::query()->updateOrCreate(
                [
                    'event' => 'seed.purchase_order',
                    'auditable_type' => PurchaseOrder::class,
                    'auditable_id' => $purchaseOrder->id,
                ],
                [
                    'user_id' => $purchasing->id,
                    'old_values' => null,
                    'new_values' => [
                        'number' => $purchaseOrder->number,
                        'status' => $purchaseOrder->status?->value,
                    ],
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'database-seeder',
                ]
            );

            AuditTrail::query()->updateOrCreate(
                [
                    'event' => 'seed.invoice',
                    'auditable_type' => Invoice::class,
                    'auditable_id' => $invoice->id,
                ],
                [
                    'user_id' => $finance->id,
                    'old_values' => null,
                    'new_values' => [
                        'number' => $invoice->number,
                        'status' => $invoice->status?->value,
                        'total_amount' => $invoice->total_amount,
                    ],
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'database-seeder',
                ]
            );
        });
    }
}
