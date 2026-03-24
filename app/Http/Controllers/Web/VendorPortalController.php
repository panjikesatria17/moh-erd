<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorMarginPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VendorPortalController extends Controller
{
    public function dashboard(Request $request): View|RedirectResponse
    {
        $vendorId = (int) (Auth::user()?->vendor_id ?? 0);
        if ($vendorId <= 0) {
            return redirect()->route('ui.dashboard')->withErrors([
                'vendor' => 'Akun Anda belum terhubung ke vendor. Hubungi admin.',
            ]);
        }

        $startDate = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfDay()->toDateString()
            : now()->startOfMonth()->toDateString();

        $endDate = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->endOfDay()->toDateString()
            : now()->endOfMonth()->toDateString();

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $products = Product::query()
            ->where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'sku',
                'name',
                'unit',
                'purchase_price',
                'selling_price',
                'total_inventory',
            ]);

        $report = $this->buildVendorMarginReport($vendorId, $startDate, $endDate);

        $vendorRevenue = $report['vendorRevenue'];
        $yayasanMarkup = $report['yayasanMarkup'];
        $totalDueToFoundation = $report['totalDueToFoundation'];

        $paidToFoundation = (float) VendorMarginPayment::query()
            ->where('vendor_id', $vendorId)
            ->where('status', VendorMarginPayment::STATUS_APPROVED)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $outstandingToFoundation = max($totalDueToFoundation - $paidToFoundation, 0);

        $invoiceRows = $report['invoiceRows'];

        $marginPayments = VendorMarginPayment::query()
            ->with(['creator:id,name', 'approver:id,name'])
            ->where('vendor_id', $vendorId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $ledgerEntries = $this->buildVendorLedgerEntries(
            collect($report['paidAccrualRows']),
            $marginPayments->where('status', VendorMarginPayment::STATUS_APPROVED)
        );

        $stockValue = (float) $products->sum(function (Product $product) {
            return (float) ($product->total_inventory ?? 0) * (float) ($product->purchase_price ?? 0);
        });

        return view('procurement.vendor.portal', [
            'products' => $products,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'vendorRevenue' => $vendorRevenue,
            'yayasanMarkup' => $yayasanMarkup,
            'totalDueToFoundation' => $totalDueToFoundation,
            'paidToFoundation' => $paidToFoundation,
            'outstandingToFoundation' => $outstandingToFoundation,
            'invoiceRows' => $invoiceRows,
            'marginPayments' => $marginPayments,
            'ledgerEntries' => $ledgerEntries,
            'stockValue' => $stockValue,
        ]);
    }

    public function financeIndex(Request $request): View
    {
        $selectedVendorId = $request->filled('vendor') ? (int) $request->integer('vendor') : null;
        $selectedStatus = $request->filled('status') ? (string) $request->string('status') : null;

        $startDate = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfDay()->toDateString()
            : now()->startOfMonth()->toDateString();

        $endDate = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->endOfDay()->toDateString()
            : now()->endOfMonth()->toDateString();

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $payments = VendorMarginPayment::query()
            ->with(['vendor:id,name', 'creator:id,name', 'approver:id,name'])
            ->when($selectedVendorId, fn($query) => $query->where('vendor_id', $selectedVendorId))
            ->when($selectedStatus, fn($query) => $query->where('status', $selectedStatus))
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $ledgerEntries = collect();
        if ($selectedVendorId) {
            $report = $this->buildVendorMarginReport($selectedVendorId, $startDate, $endDate);

            $approvedPayments = VendorMarginPayment::query()
                ->where('vendor_id', $selectedVendorId)
                ->where('status', VendorMarginPayment::STATUS_APPROVED)
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get();

            $ledgerEntries = $this->buildVendorLedgerEntries(collect($report['paidAccrualRows']), $approvedPayments);
        }

        return view('procurement.finance.vendor-margin-payments.index', [
            'vendors' => $vendors,
            'payments' => $payments,
            'selectedVendorId' => $selectedVendorId,
            'selectedStatus' => $selectedStatus,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'ledgerEntries' => $ledgerEntries,
        ]);
    }

    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        $vendorId = (int) (Auth::user()?->vendor_id ?? 0);
        if ($vendorId <= 0 || (int) $product->vendor_id !== $vendorId) {
            abort(403);
        }

        $validated = $request->validate([
            'total_inventory' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $product->update([
            'total_inventory' => (float) $validated['total_inventory'],
            'purchase_price' => array_key_exists('purchase_price', $validated) && $validated['purchase_price'] !== null
                ? (float) $validated['purchase_price']
                : null,
        ]);

        return redirect()->back()->with('success', 'Stok produk vendor berhasil diperbarui dan terintegrasi ke master data produk.');
    }

    public function storeMarginPayment(Request $request): RedirectResponse
    {
        $vendorId = (int) (Auth::user()?->vendor_id ?? 0);
        if ($vendorId <= 0) {
            abort(403);
        }

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $proofPath = $this->storeProofFile($request->file('proof_image'));

        VendorMarginPayment::query()->create([
            'vendor_id' => $vendorId,
            'payment_date' => $validated['payment_date'],
            'amount' => (float) $validated['amount'],
            'reference_no' => $validated['reference_no'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => VendorMarginPayment::STATUS_SUBMITTED,
            'proof_image_path' => $proofPath,
            'proof_uploaded_at' => $proofPath ? now() : null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Pembayaran selisih harga ke yayasan berhasil diajukan untuk verifikasi finance.');
    }

    public function uploadProof(Request $request, VendorMarginPayment $payment): RedirectResponse
    {
        $vendorId = (int) (Auth::user()?->vendor_id ?? 0);
        if ($vendorId <= 0 || (int) $payment->vendor_id !== $vendorId) {
            abort(403);
        }

        $validated = $request->validate([
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $path = $this->storeProofFile($validated['proof_image']);
        if ($path === null) {
            return redirect()->back()->withErrors(['proof_image' => 'Bukti transfer tidak dapat disimpan.']);
        }

        if (! empty($payment->proof_image_path)) {
            Storage::disk('public')->delete($payment->proof_image_path);
        }

        $payment->update([
            'proof_image_path' => $path,
            'proof_uploaded_at' => now(),
            'status' => VendorMarginPayment::STATUS_SUBMITTED,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_note' => null,
        ]);

        return redirect()->back()->with('success', 'Bukti transfer berhasil diunggah dan menunggu verifikasi finance.');
    }

    public function approveMarginPayment(VendorMarginPayment $payment): RedirectResponse
    {
        if ($payment->status === VendorMarginPayment::STATUS_APPROVED) {
            return redirect()->back()->withErrors(['margin_payment' => 'Pembayaran ini sudah di-approve.']);
        }

        $payment->update([
            'status' => VendorMarginPayment::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_note' => null,
        ]);

        return redirect()->back()->with('success', 'Pembayaran selisih berhasil di-approve.');
    }

    public function rejectMarginPayment(Request $request, VendorMarginPayment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_note' => ['required', 'string', 'max:1000'],
        ]);

        $payment->update([
            'status' => VendorMarginPayment::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_note' => $validated['rejection_note'],
        ]);

        return redirect()->back()->with('success', 'Pembayaran selisih berhasil ditolak dengan catatan.');
    }

    private function storeProofFile(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store('vendor-margin-proofs', 'public');
    }

    private function buildVendorMarginReport(int $vendorId, string $startDate, string $endDate): array
    {
        $invoiceMarginBase = DB::table('invoices')
            ->join('deliveries', 'deliveries.id', '=', 'invoices.delivery_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'deliveries.purchase_order_id')
            ->join('purchase_order_items', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->leftJoin('products', 'products.id', '=', 'purchase_order_items.product_id')
            ->leftJoin('sppgs', 'sppgs.id', '=', 'invoices.sppg_id')
            ->whereNull('invoices.deleted_at')
            ->whereNull('deliveries.deleted_at')
            ->whereNull('purchase_orders.deleted_at')
            ->whereNull('purchase_order_items.deleted_at')
            ->where('purchase_orders.vendor_id', $vendorId)
            ->whereBetween('invoices.invoice_date', [$startDate, $endDate]);

        $summary = (clone $invoiceMarginBase)
            ->selectRaw('COALESCE(SUM(purchase_order_items.subtotal), 0) as vendor_revenue')
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity * GREATEST(COALESCE(products.selling_price, purchase_order_items.unit_price) - purchase_order_items.unit_price, 0)), 0) as yayasan_markup')
            ->first();

        $invoiceRows = (clone $invoiceMarginBase)
            ->selectRaw('invoices.id as invoice_id')
            ->selectRaw('invoices.number as invoice_number')
            ->selectRaw('invoices.invoice_date as invoice_date')
            ->selectRaw('invoices.status as invoice_status')
            ->selectRaw('COALESCE(sppgs.name, "-") as sppg_name')
            ->selectRaw('COALESCE(SUM(purchase_order_items.subtotal), 0) as vendor_revenue')
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity * GREATEST(COALESCE(products.selling_price, purchase_order_items.unit_price) - purchase_order_items.unit_price, 0)), 0) as yayasan_markup_due')
            ->groupBy('invoices.id', 'invoices.number', 'invoices.invoice_date', 'invoices.status', 'sppgs.name')
            ->orderByDesc('invoices.invoice_date')
            ->orderByDesc('invoices.id')
            ->get();

        $paidAccrualRows = (clone $invoiceMarginBase)
            ->where('invoices.status', 'paid')
            ->selectRaw('invoices.id as invoice_id')
            ->selectRaw('invoices.number as invoice_number')
            ->selectRaw('invoices.invoice_date as entry_date')
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity * GREATEST(COALESCE(products.selling_price, purchase_order_items.unit_price) - purchase_order_items.unit_price, 0)), 0) as amount')
            ->groupBy('invoices.id', 'invoices.number', 'invoices.invoice_date')
            ->orderBy('invoices.invoice_date')
            ->orderBy('invoices.id')
            ->get();

        $totalDueToFoundation = (float) $paidAccrualRows->sum(fn($row) => (float) ($row->amount ?? 0));

        return [
            'vendorRevenue' => (float) ($summary->vendor_revenue ?? 0),
            'yayasanMarkup' => (float) ($summary->yayasan_markup ?? 0),
            'totalDueToFoundation' => $totalDueToFoundation,
            'invoiceRows' => $invoiceRows,
            'paidAccrualRows' => $paidAccrualRows,
        ];
    }

    private function buildVendorLedgerEntries(Collection $paidAccrualRows, Collection $approvedPayments): Collection
    {
        $entries = collect();

        foreach ($paidAccrualRows as $row) {
            $amount = (float) ($row->amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $entries->push([
                'entry_date' => (string) $row->entry_date,
                'entry_type' => 'invoice_accrual',
                'description' => 'Akrual selisih invoice ' . ($row->invoice_number ?? '-'),
                'debit' => $amount,
                'credit' => 0.0,
                'delta' => $amount,
            ]);
        }

        foreach ($approvedPayments as $payment) {
            $amount = (float) ($payment->amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $entries->push([
                'entry_date' => optional($payment->payment_date)->toDateString() ?? optional($payment->created_at)->toDateString(),
                'entry_type' => 'vendor_payment',
                'description' => 'Pembayaran vendor ' . ($payment->reference_no ?: ('#' . $payment->id)),
                'debit' => 0.0,
                'credit' => $amount,
                'delta' => -$amount,
            ]);
        }

        $runningBalance = 0.0;

        return $entries
            ->sortBy([
                ['entry_date', 'asc'],
                ['entry_type', 'asc'],
            ])
            ->values()
            ->map(function (array $entry) use (&$runningBalance) {
                $runningBalance += (float) $entry['delta'];
                $entry['running_balance'] = $runningBalance;

                return $entry;
            });
    }
}
