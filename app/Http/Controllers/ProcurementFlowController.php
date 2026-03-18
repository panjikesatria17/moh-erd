<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Approval;
use App\Models\AuditTrail;
use App\Models\BillingCycle;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\ProductPriceHistory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ProcurementFlowController extends Controller
{
    public function createPurchaseRequest(Request $request): JsonResponse
    {
        Gate::authorize('create', PurchaseRequest::class);

        $validated = $request->validate([
            'sppg_id' => ['required', 'exists:sppgs,id'],
            'needed_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.requested_unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $role = $request->user()?->role;
        $isSppgUser = $role === UserRole::SPPG_USER || $role === UserRole::SPPG_USER->value;

        if ($isSppgUser && (int) $request->user()?->sppg_id !== (int) $validated['sppg_id']) {
            abort(403, 'SPPG user can only create PR for its own SPPG.');
        }

        $purchaseRequest = DB::transaction(function () use ($validated, $request) {
            $purchaseRequest = PurchaseRequest::create([
                'number' => $this->generateNumber('PR'),
                'sppg_id' => $validated['sppg_id'],
                'requested_by' => $request->user()?->id,
                'request_date' => now()->toDateString(),
                'needed_date' => $validated['needed_date'] ?? null,
                'status' => DocumentStatus::SUBMITTED,
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $subtotal = (float) $item['quantity'] * (float) $item['requested_unit_price'];

                $purchaseRequest->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'requested_unit_price' => $item['requested_unit_price'],
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $purchaseRequest->recalculateTotal();

            $this->writeAudit($request, 'purchase_request.submitted', $purchaseRequest, null, $purchaseRequest->toArray());

            return $purchaseRequest->load('items');
        });

        return response()->json([
            'message' => 'Purchase Request created and submitted.',
            'data' => $purchaseRequest,
        ], 201);
    }

    public function approvePurchaseRequest(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        Gate::authorize('approve', $purchaseRequest);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'level' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
        ]);

        $updatedPr = DB::transaction(function () use ($validated, $request, $purchaseRequest) {
            $old = $purchaseRequest->toArray();
            $status = $validated['status'] === 'approved'
                ? DocumentStatus::APPROVED
                : DocumentStatus::REJECTED;

            Approval::create([
                'approvable_type' => PurchaseRequest::class,
                'approvable_id' => $purchaseRequest->id,
                'level' => $validated['level'],
                'approver_id' => $request->user()?->id,
                'status' => $status,
                'note' => $validated['note'] ?? null,
                'approved_at' => now(),
            ]);

            $purchaseRequest->update([
                'status' => $status,
            ]);

            $this->writeAudit(
                $request,
                'purchase_request.'.$validated['status'],
                $purchaseRequest,
                $old,
                $purchaseRequest->fresh()->toArray()
            );

            return $purchaseRequest->fresh(['items', 'approvals']);
        });

        return response()->json([
            'message' => 'Purchase Request '.$validated['status'].'.',
            'data' => $updatedPr,
        ]);
    }

    public function generatePurchaseOrder(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        Gate::authorize('generatePo', $purchaseRequest);

        $validated = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'expected_date' => ['nullable', 'date'],
            'is_direct_purchase' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($purchaseRequest->status !== DocumentStatus::APPROVED) {
            return response()->json([
                'message' => 'Purchase Request must be approved before generating PO.',
            ], 422);
        }

        $purchaseOrder = DB::transaction(function () use ($validated, $request, $purchaseRequest) {
            $purchaseRequest->loadMissing(['items', 'sppg']);

            $vendorId = $validated['vendor_id']
                ?? $purchaseRequest->sppg?->default_vendor_id;

            if (! $vendorId) {
                abort(422, 'Vendor is required. SPPG default vendor is not set.');
            }

            $po = PurchaseOrder::create([
                'number' => $this->generateNumber('PO'),
                'purchase_request_id' => $purchaseRequest->id,
                'sppg_id' => $purchaseRequest->sppg_id,
                'vendor_id' => $vendorId,
                'ordered_by' => $request->user()?->id,
                'order_date' => now()->toDateString(),
                'expected_date' => $validated['expected_date'] ?? null,
                'status' => DocumentStatus::PROCESSED,
                'is_direct_purchase' => (bool) ($validated['is_direct_purchase'] ?? false),
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0,
            ]);

            foreach ($purchaseRequest->items as $item) {
                $latestPrice = ProductPriceHistory::query()
                    ->where('product_id', $item->product_id)
                    ->where(function ($query) use ($vendorId) {
                        $query->where('vendor_id', $vendorId)->orWhereNull('vendor_id');
                    })
                    ->orderByDesc('effective_at')
                    ->value('price');

                $unitPrice = $latestPrice ?? $item->requested_unit_price;
                $subtotal = (float) $item->quantity * (float) $unitPrice;

                $po->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'notes' => $item->notes,
                ]);
            }

            $po->recalculateTotal();

            $purchaseRequest->update([
                'status' => DocumentStatus::PROCESSED,
            ]);

            $this->writeAudit($request, 'purchase_order.generated', $po, null, $po->toArray());

            return $po->load(['items', 'vendor', 'sppg']);
        });

        return response()->json([
            'message' => 'Purchase Order generated successfully.',
            'data' => $purchaseOrder,
        ], 201);
    }

    public function generateInvoice(Request $request, Delivery $delivery): JsonResponse
    {
        Gate::authorize('generateInvoice', $delivery);

        $validated = $request->validate([
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]);

        $invoice = DB::transaction(function () use ($request, $validated, $delivery) {
            $delivery->loadMissing(['sppg', 'purchaseOrder']);

            if (! $delivery->vendor_id && ! $delivery->purchaseOrder?->vendor_id) {
                abort(422, 'Vendor cannot be determined from delivery or purchase order.');
            }

            $vendorId = $delivery->vendor_id ?? $delivery->purchaseOrder?->vendor_id;
            $date = Carbon::parse($delivery->delivery_date ?? now());
            $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $weekEnd = $date->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

            $billingCycle = BillingCycle::firstOrCreate(
                [
                    'sppg_id' => $delivery->sppg_id,
                    'week_start_date' => $weekStart,
                    'week_end_date' => $weekEnd,
                ],
                [
                    'status' => DocumentStatus::INVOICED,
                    'created_by' => $request->user()?->id,
                ]
            );

            $subtotal = (float) $delivery->total_amount;
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);

            $invoice = Invoice::create([
                'number' => $this->generateNumber('INV'),
                'billing_cycle_id' => $billingCycle->id,
                'delivery_id' => $delivery->id,
                'sppg_id' => $delivery->sppg_id,
                'vendor_id' => $vendorId,
                'invoice_date' => now()->toDateString(),
                'due_date' => $validated['due_date'] ?? now()->addWeek()->toDateString(),
                'status' => DocumentStatus::INVOICED,
                'subtotal_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ]);

            $delivery->update([
                'status' => DocumentStatus::INVOICED,
            ]);

            $this->writeAudit($request, 'invoice.generated', $invoice, null, $invoice->toArray());

            return $invoice->load(['billingCycle', 'delivery', 'vendor', 'sppg']);
        });

        return response()->json([
            'message' => 'Invoice generated successfully.',
            'data' => $invoice,
        ], 201);
    }

    private function generateNumber(string $prefix): string
    {
        $datePart = now()->format('Ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        return sprintf('%s-%s-%s', $prefix, $datePart, $random);
    }

    private function writeAudit(Request $request, string $event, object $model, ?array $oldValues, ?array $newValues): void
    {
        AuditTrail::create([
            'user_id' => $request->user()?->id,
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
