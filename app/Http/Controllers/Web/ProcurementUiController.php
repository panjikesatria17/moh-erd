<?php

namespace App\Http\Controllers\Web;

use App\Enums\DocumentStatus;
use App\Models\Approval;
use App\Models\AuditTrail;
use App\Models\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPriceHistory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Sppg;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPerformance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcurementUiController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'sppgs' => Sppg::query()->count(),
            'vendors' => Vendor::query()->count(),
            'products' => Product::query()->count(),
            'purchase_requests' => PurchaseRequest::query()->count(),
            'purchase_orders' => PurchaseOrder::query()->count(),
            'deliveries' => Delivery::query()->count(),
            'invoices' => Invoice::query()->count(),
            'payments' => Payment::query()->count(),
            'open_stock_alerts' => StockAlert::query()->where('is_resolved', false)->count(),
        ];

        $recentPurchaseRequests = PurchaseRequest::query()
            ->with(['sppg', 'requester'])
            ->latest('request_date')
            ->limit(5)
            ->get();

        $recentPurchaseOrders = PurchaseOrder::query()
            ->with(['sppg', 'vendor'])
            ->latest('order_date')
            ->limit(5)
            ->get();

        $recentInvoices = Invoice::query()
            ->with(['sppg', 'vendor'])
            ->latest('invoice_date')
            ->limit(5)
            ->get();

        return view('procurement.dashboard', compact(
            'stats',
            'recentPurchaseRequests',
            'recentPurchaseOrders',
            'recentInvoices'
        ));
    }

    public function purchaseRequests(): View
    {
        $purchaseRequests = PurchaseRequest::query()
            ->with(['sppg', 'requester'])
            ->latest('request_date')
            ->paginate(15);

        $sppgs = Sppg::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get();

        return view('procurement.purchase-requests.index', compact('purchaseRequests', 'sppgs', 'products', 'vendors'));
    }

    public function purchaseOrders(): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with(['sppg', 'vendor', 'purchaseRequest'])
            ->latest('order_date')
            ->paginate(15);

        return view('procurement.purchase-orders.index', compact('purchaseOrders'));
    }

    public function deliveries(): View
    {
        $deliveries = Delivery::query()
            ->with(['sppg', 'vendor', 'purchaseOrder'])
            ->latest('delivery_date')
            ->paginate(15);

        return view('procurement.deliveries.index', compact('deliveries'));
    }

    public function invoices(): View
    {
        $invoices = Invoice::query()
            ->with(['sppg', 'vendor', 'payments'])
            ->latest('invoice_date')
            ->paginate(15);

        return view('procurement.invoices.index', compact('invoices'));
    }

    public function masterSppgs(): View
    {
        $sppgs = Sppg::query()
            ->with('defaultVendor')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15);

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('procurement.master-data.sppgs.index', compact('sppgs', 'vendors'));
    }

    public function storeSppg(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:sppgs,code'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'default_vendor_id' => ['nullable', 'exists:vendors,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Sppg::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'default_vendor_id' => $validated['default_vendor_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('ui.master-data.sppgs.index')
            ->with('success', 'Data SPPG berhasil ditambahkan.');
    }

    public function masterVendors(): View
    {
        $vendors = Vendor::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15);

        return view('procurement.master-data.vendors.index', compact('vendors'));
    }

    public function storeVendor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:vendors,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_affiliate' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Vendor::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_affiliate' => $request->boolean('is_affiliate', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('ui.master-data.vendors.index')
            ->with('success', 'Data vendor berhasil ditambahkan.');
    }

    public function masterProducts(): View
    {
        $products = Product::query()
            ->with('category')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15);

        $categories = ProductCategory::query()
            ->orderBy('name')
            ->get();

        return view('procurement.master-data.products.index', compact('products', 'categories'));
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'unit' => ['required', 'string', 'max:30'],
            'government_price_cap' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_stock_level' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Product::query()->create([
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'product_category_id' => $validated['product_category_id'],
            'unit' => $validated['unit'],
            'government_price_cap' => $validated['government_price_cap'] ?? null,
            'minimum_stock_level' => $validated['minimum_stock_level'] ?? 0,
            'reorder_stock_level' => $validated['reorder_stock_level'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('ui.master-data.products.index')
            ->with('success', 'Data produk berhasil ditambahkan.');
    }

    public function priceHistories(): View
    {
        $priceHistories = ProductPriceHistory::query()
            ->with(['product', 'vendor', 'creator'])
            ->orderByDesc('effective_at')
            ->orderByDesc('id')
            ->paginate(15);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('procurement.master-data.price-histories.index', compact('priceHistories', 'products', 'vendors'));
    }

    public function storePriceHistory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'effective_at' => ['required', 'date'],
        ]);

        ProductPriceHistory::query()->create([
            'product_id' => $validated['product_id'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'price' => $validated['price'],
            'effective_at' => $validated['effective_at'],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('ui.master-data.price-histories.index')
            ->with('success', 'Riwayat harga berhasil ditambahkan.');
    }

    public function approvalQueue(): View
    {
        $approvals = Approval::query()
            ->with(['approver', 'approvable'])
            ->orderByRaw("CASE WHEN approved_at IS NULL THEN 0 ELSE 1 END")
            ->latest('created_at')
            ->paginate(20);

        return view('procurement.approvals.index', compact('approvals'));
    }

    public function stockMovements(): View
    {
        $stockMovements = StockMovement::query()
            ->with(['warehouse', 'product', 'creator'])
            ->latest('movement_date')
            ->paginate(20);

        return view('procurement.inventory.stock-movements.index', compact('stockMovements'));
    }

    public function stockAlerts(): View
    {
        $stockAlerts = StockAlert::query()
            ->with(['warehouse', 'product', 'resolver'])
            ->orderBy('is_resolved')
            ->latest('created_at')
            ->paginate(20);

        return view('procurement.inventory.stock-alerts.index', compact('stockAlerts'));
    }

    public function billingCycles(): View
    {
        $billingCycles = BillingCycle::query()
            ->with(['sppg', 'creator'])
            ->latest('week_start_date')
            ->paginate(20);

        return view('procurement.finance.billing-cycles.index', compact('billingCycles'));
    }

    public function payments(): View
    {
        $payments = Payment::query()
            ->with(['invoice', 'payer'])
            ->latest('payment_date')
            ->paginate(20);

        return view('procurement.finance.payments.index', compact('payments'));
    }

    public function usersRoles(): View
    {
        $users = User::query()
            ->with(['sppg', 'vendor'])
            ->latest('id')
            ->paginate(20);

        return view('procurement.master-data.users-roles.index', compact('users'));
    }

    public function vendorPerformances(): View
    {
        $performances = VendorPerformance::query()
            ->with('vendor')
            ->latest('period_end')
            ->paginate(20);

        return view('procurement.analytics.vendor-performances.index', compact('performances'));
    }

    public function priceTrends(): View
    {
        $trendRows = ProductPriceHistory::query()
            ->select('product_id')
            ->selectRaw('COUNT(*) as records_count')
            ->selectRaw('MIN(price) as min_price')
            ->selectRaw('MAX(price) as max_price')
            ->selectRaw('AVG(price) as avg_price')
            ->groupBy('product_id')
            ->with('product')
            ->paginate(20);

        return view('procurement.analytics.price-trends.index', compact('trendRows'));
    }

    public function auditTrails(): View
    {
        $auditTrails = AuditTrail::query()
            ->with('user')
            ->latest('created_at')
            ->paginate(20);

        return view('procurement.analytics.audit-trails.index', compact('auditTrails'));
    }

    public function storePurchaseRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sppg_id' => ['required', 'exists:sppgs,id'],
            'needed_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'requested_unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $requesterId = User::query()->where('role', 'sppg_user')->value('id');
            $subtotal = (float) $validated['quantity'] * (float) $validated['requested_unit_price'];

            $purchaseRequest = PurchaseRequest::query()->create([
                'number' => $this->generateNumber('PR'),
                'sppg_id' => $validated['sppg_id'],
                'requested_by' => $requesterId,
                'request_date' => now()->toDateString(),
                'needed_date' => $validated['needed_date'] ?? null,
                'status' => DocumentStatus::SUBMITTED,
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0,
            ]);

            $purchaseRequest->items()->create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'requested_unit_price' => $validated['requested_unit_price'],
                'subtotal' => $subtotal,
            ]);

            $purchaseRequest->recalculateTotal();
        });

        return redirect()
            ->route('ui.purchase-requests.index')
            ->with('success', 'Purchase Request berhasil dibuat.');
    }

    public function approvePurchaseRequest(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $approverId = User::query()->where('role', 'owner')->value('id')
            ?? User::query()->value('id');

        if (! $approverId) {
            return redirect()
                ->route('ui.purchase-requests.index')
                ->withErrors(['approval' => 'User approver tidak tersedia.']);
        }

        DB::transaction(function () use ($purchaseRequest, $approverId) {
            Approval::query()->create([
                'approvable_type' => PurchaseRequest::class,
                'approvable_id' => $purchaseRequest->id,
                'level' => 1,
                'approver_id' => $approverId,
                'status' => DocumentStatus::APPROVED,
                'note' => 'Approved from UI dashboard',
                'approved_at' => now(),
            ]);

            $purchaseRequest->update([
                'status' => DocumentStatus::APPROVED,
            ]);
        });

        return redirect()
            ->route('ui.purchase-requests.index')
            ->with('success', 'Purchase Request berhasil di-approve.');
    }

    public function generatePurchaseOrder(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'expected_date' => ['nullable', 'date'],
        ]);

        if ($purchaseRequest->status !== DocumentStatus::APPROVED) {
            return redirect()
                ->route('ui.purchase-requests.index')
                ->withErrors(['po' => 'PR harus berstatus approved sebelum generate PO.']);
        }

        DB::transaction(function () use ($purchaseRequest, $validated) {
            $purchaseRequest->loadMissing(['items', 'sppg']);

            $vendorId = $validated['vendor_id'] ?? $purchaseRequest->sppg?->default_vendor_id;

            if (! $vendorId) {
                throw new \RuntimeException('Vendor tidak tersedia untuk PR ini.');
            }

            $orderedBy = User::query()->where('role', 'purchasing')->value('id');

            $purchaseOrder = PurchaseOrder::query()->create([
                'number' => $this->generateNumber('PO'),
                'purchase_request_id' => $purchaseRequest->id,
                'sppg_id' => $purchaseRequest->sppg_id,
                'vendor_id' => $vendorId,
                'ordered_by' => $orderedBy,
                'order_date' => now()->toDateString(),
                'expected_date' => $validated['expected_date'] ?? null,
                'status' => DocumentStatus::PROCESSED,
                'is_direct_purchase' => false,
                'notes' => 'Generated from UI dashboard',
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

                $purchaseOrder->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $purchaseOrder->recalculateTotal();

            $purchaseRequest->update([
                'status' => DocumentStatus::PROCESSED,
            ]);
        });

        return redirect()
            ->route('ui.purchase-orders.index')
            ->with('success', 'Purchase Order berhasil digenerate.');
    }

    public function generateInvoice(Request $request, Delivery $delivery): RedirectResponse
    {
        $validated = $request->validate([
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($delivery, $validated) {
            $delivery->loadMissing(['purchaseOrder', 'sppg']);

            $vendorId = $delivery->vendor_id ?? $delivery->purchaseOrder?->vendor_id;

            if (! $vendorId) {
                throw new \RuntimeException('Vendor tidak tersedia untuk delivery ini.');
            }

            $date = Carbon::parse($delivery->delivery_date ?? now());
            $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $weekEnd = $date->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

            $financeId = User::query()->where('role', 'finance')->value('id');

            $billingCycle = BillingCycle::query()->firstOrCreate(
                [
                    'sppg_id' => $delivery->sppg_id,
                    'week_start_date' => $weekStart,
                    'week_end_date' => $weekEnd,
                ],
                [
                    'status' => DocumentStatus::INVOICED,
                    'created_by' => $financeId,
                ]
            );

            $subtotal = (float) $delivery->total_amount;
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);

            Invoice::query()->create([
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
        });

        return redirect()
            ->route('ui.invoices.index')
            ->with('success', 'Invoice berhasil digenerate dari delivery.');
    }

    private function generateNumber(string $prefix): string
    {
        $datePart = now()->format('Ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        return sprintf('%s-%s-%s', $prefix, $datePart, $random);
    }
}
