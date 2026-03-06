<?php

namespace App\Http\Controllers\Web;

use App\Enums\DocumentStatus;
use App\Enums\FundingRequestStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Approval;
use App\Models\AppSetting;
use App\Models\AuditTrail;
use App\Models\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Kwitansi;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPriceHistory;
use App\Models\PurchaseFundingRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\RejectedItem;
use App\Models\Sppg;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\PurchaseFundingNeedsOwnerApproval;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use App\Enums\UserRole;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class ProcurementUiController extends Controller
{
    public function dashboard(): View
    {
        [
            'sppg_scope_id' => $sppgScopeId,
            'vendor_scope_id' => $vendorScopeId,
            'sppg_scope_enabled' => $sppgScopeEnabled,
            'vendor_scope_enabled' => $vendorScopeEnabled,
        ] = $this->resolveDashboardRoleScopeContext();

        $sppgQuery = Sppg::query();
        if ($sppgScopeEnabled) {
            $sppgQuery->whereKey($this->scopedId($sppgScopeId));
        } elseif ($vendorScopeEnabled) {
            $sppgIds = PurchaseOrder::query()
                ->where('vendor_id', $this->scopedId($vendorScopeId))
                ->select('sppg_id');
            $sppgQuery->whereIn('id', $sppgIds);
        }

        $vendorQuery = Vendor::query();
        if ($vendorScopeEnabled) {
            $vendorQuery->whereKey($this->scopedId($vendorScopeId));
        } elseif ($sppgScopeEnabled) {
            $vendorIds = PurchaseOrder::query()
                ->where('sppg_id', $this->scopedId($sppgScopeId))
                ->select('vendor_id');
            $vendorQuery->whereIn('id', $vendorIds);
        }

        $productQuery = Product::query();
        if ($vendorScopeEnabled) {
            $productQuery->where('vendor_id', $this->scopedId($vendorScopeId));
        } elseif ($sppgScopeEnabled) {
            $productQuery->whereHas('purchaseRequestItems.purchaseRequest', function ($query) use ($sppgScopeId) {
                $query->where('sppg_id', $this->scopedId($sppgScopeId));
            });
        }

        $purchaseRequestCountQuery = PurchaseRequest::query();
        if ($sppgScopeEnabled) {
            $purchaseRequestCountQuery->where('sppg_id', $this->scopedId($sppgScopeId));
        } elseif ($vendorScopeEnabled) {
            $purchaseRequestCountQuery->whereHas('purchaseOrders', function ($query) use ($vendorScopeId) {
                $query->where('vendor_id', $this->scopedId($vendorScopeId));
            });
        }

        $purchaseOrderCountQuery = PurchaseOrder::query();
        if ($sppgScopeEnabled) {
            $purchaseOrderCountQuery->where('sppg_id', $this->scopedId($sppgScopeId));
        }
        if ($vendorScopeEnabled) {
            $purchaseOrderCountQuery->where('vendor_id', $this->scopedId($vendorScopeId));
        }

        $deliveryCountQuery = Delivery::query();
        if ($sppgScopeEnabled) {
            $deliveryCountQuery->where('sppg_id', $this->scopedId($sppgScopeId));
        }
        if ($vendorScopeEnabled) {
            $deliveryCountQuery->where('vendor_id', $this->scopedId($vendorScopeId));
        }

        $invoiceCountQuery = Invoice::query();
        if ($sppgScopeEnabled) {
            $invoiceCountQuery->where('sppg_id', $this->scopedId($sppgScopeId));
        }
        if ($vendorScopeEnabled) {
            $invoiceCountQuery->where('vendor_id', $this->scopedId($vendorScopeId));
        }

        $paymentCountQuery = Payment::query();
        if ($sppgScopeEnabled) {
            $paymentCountQuery->whereHas('invoice', function ($query) use ($sppgScopeId) {
                $query->where('sppg_id', $this->scopedId($sppgScopeId));
            });
        }
        if ($vendorScopeEnabled) {
            $paymentCountQuery->whereHas('invoice', function ($query) use ($vendorScopeId) {
                $query->where('vendor_id', $this->scopedId($vendorScopeId));
            });
        }

        $stockAlertCountQuery = StockAlert::query()->where('is_resolved', false);
        if ($vendorScopeEnabled) {
            $stockAlertCountQuery->whereHas('product', function ($query) use ($vendorScopeId) {
                $query->where('vendor_id', $this->scopedId($vendorScopeId));
            });
        } elseif ($sppgScopeEnabled) {
            $stockAlertCountQuery->whereHas('product.purchaseRequestItems.purchaseRequest', function ($query) use ($sppgScopeId) {
                $query->where('sppg_id', $this->scopedId($sppgScopeId));
            });
        }

        $fundingRequestCountQuery = PurchaseFundingRequest::query();
        if ($sppgScopeEnabled) {
            $fundingRequestCountQuery->where('sppg_id', $this->scopedId($sppgScopeId));
        }
        if ($vendorScopeEnabled) {
            $fundingRequestCountQuery->where('vendor_id', $this->scopedId($vendorScopeId));
        }

        $stats = [
            'sppgs' => $sppgQuery->count(),
            'vendors' => $vendorQuery->count(),
            'products' => $productQuery->count(),
            'purchase_requests' => $purchaseRequestCountQuery->count(),
            'purchase_orders' => $purchaseOrderCountQuery->count(),
            'deliveries' => $deliveryCountQuery->count(),
            'invoices' => $invoiceCountQuery->count(),
            'payments' => $paymentCountQuery->count(),
            'open_stock_alerts' => $stockAlertCountQuery->count(),
            'expedition_on_process' => (clone $deliveryCountQuery)->where('status', DocumentStatus::PROCESSED->value)->count(),
            'expedition_delivered' => (clone $deliveryCountQuery)
                ->whereIn('status', [
                    DocumentStatus::DELIVERED->value,
                    DocumentStatus::INVOICED->value,
                    DocumentStatus::PAID->value,
                ])->count(),
            'funding_pending_owner' => (clone $fundingRequestCountQuery)
                ->where('status', FundingRequestStatus::REVIEWED->value)
                ->count(),
            'funding_disbursed_total' => (float) (clone $fundingRequestCountQuery)->sum('disbursed_amount'),
            'funding_remaining_total' => max(
                (float) (clone $fundingRequestCountQuery)->sum('disbursed_amount')
                - (float) (clone $fundingRequestCountQuery)->sum('spent_amount'),
                0
            ),
        ];

        $recentPurchaseRequestsQuery = PurchaseRequest::query()
            ->select(['id', 'number', 'sppg_id', 'status', 'request_date'])
            ->with(['sppg:id,name']);
        if ($sppgScopeEnabled) {
            $recentPurchaseRequestsQuery->where('sppg_id', $this->scopedId($sppgScopeId));
        } elseif ($vendorScopeEnabled) {
            $recentPurchaseRequestsQuery->whereHas('purchaseOrders', function ($query) use ($vendorScopeId) {
                $query->where('vendor_id', $this->scopedId($vendorScopeId));
            });
        }
        $recentPurchaseRequests = $recentPurchaseRequestsQuery->latest('request_date')->limit(5)->get();

        $recentPurchaseOrdersQuery = PurchaseOrder::query()
            ->select(['id', 'number', 'vendor_id', 'status', 'order_date'])
            ->with(['vendor:id,name']);
        if ($sppgScopeEnabled) {
            $recentPurchaseOrdersQuery->where('sppg_id', $this->scopedId($sppgScopeId));
        }
        if ($vendorScopeEnabled) {
            $recentPurchaseOrdersQuery->where('vendor_id', $this->scopedId($vendorScopeId));
        }
        $recentPurchaseOrders = $recentPurchaseOrdersQuery->latest('order_date')->limit(5)->get();

        $recentInvoicesQuery = Invoice::query()
            ->select(['id', 'number', 'vendor_id', 'status', 'invoice_date'])
            ->with(['vendor:id,name']);
        if ($sppgScopeEnabled) {
            $recentInvoicesQuery->where('sppg_id', $this->scopedId($sppgScopeId));
        }
        if ($vendorScopeEnabled) {
            $recentInvoicesQuery->where('vendor_id', $this->scopedId($vendorScopeId));
        }
        $recentInvoices = $recentInvoicesQuery->latest('invoice_date')->limit(5)->get();

        $currentRole = Auth::user()?->role?->value;
        $showProfitMarginMetric = in_array($currentRole, [
            UserRole::SUPER_ADMIN->value,
            UserRole::OWNER->value,
        ], true);

        $showAssetAndChartMetrics = in_array($currentRole, [
            UserRole::SUPER_ADMIN->value,
            UserRole::OWNER->value,
            UserRole::FINANCE->value,
            UserRole::ADMIN_GUDANG->value,
            UserRole::PURCHASING->value,
        ], true);

        $showFundingSummaryMetrics = in_array($currentRole, [
            UserRole::SUPER_ADMIN->value,
            UserRole::OWNER->value,
            UserRole::FINANCE->value,
        ], true);

        $totalAssetValue = 0.0;
        $totalProfitMargin = 0.0;
        $ordersBySppg = collect();
        $maxOrderQtyBySppg = 0.0;

        if ($showAssetAndChartMetrics || $showProfitMarginMetric) {
            $productsForMetricsQuery = Product::query()->select([
                'id',
                'purchase_price',
                'selling_price',
                'government_price_cap',
                'price_variance_percent',
                'price_variance_amount',
                'minimum_stock_level',
            ]);

            if ($vendorScopeEnabled) {
                $productsForMetricsQuery->where('vendor_id', $this->scopedId($vendorScopeId));
            } elseif ($sppgScopeEnabled) {
                $productsForMetricsQuery->whereHas('purchaseRequestItems.purchaseRequest', function ($query) use ($sppgScopeId) {
                    $query->where('sppg_id', $this->scopedId($sppgScopeId));
                });
            }

            $productsForMetrics = $productsForMetricsQuery->get();
            $inventoryQtyByProduct = $this->getInventoryQuantityByProductIds($productsForMetrics->pluck('id')->all());

            foreach ($productsForMetrics as $productMetric) {
                $productId = (int) $productMetric->id;
                $qty = $this->resolveProductAssetQuantity(
                    $productMetric,
                    (float) ($inventoryQtyByProduct[$productId] ?? 0)
                );

                if ($showAssetAndChartMetrics) {
                    $referencePrice = $this->resolveProductReferencePrice($productMetric);
                    $totalAssetValue += $qty * $referencePrice;
                }

                if ($showProfitMarginMetric && $productMetric->purchase_price !== null && $productMetric->selling_price !== null) {
                    $totalProfitMargin += $qty * ((float) $productMetric->selling_price - (float) $productMetric->purchase_price);
                }
            }

            if ($showAssetAndChartMetrics) {
                $ordersBySppgQuery = PurchaseOrderItem::query()
                    ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                    ->join('sppgs', 'purchase_orders.sppg_id', '=', 'sppgs.id')
                    ->whereNull('purchase_order_items.deleted_at')
                    ->whereNull('purchase_orders.deleted_at')
                    ->selectRaw('sppgs.id as sppg_id, sppgs.name as sppg_name, SUM(purchase_order_items.quantity) as total_qty, SUM(purchase_order_items.subtotal) as total_amount')
                    ->when($sppgScopeEnabled, fn ($query) => $query->where('purchase_orders.sppg_id', $this->scopedId($sppgScopeId)))
                    ->when($vendorScopeEnabled, fn ($query) => $query->where('purchase_orders.vendor_id', $this->scopedId($vendorScopeId)))
                    ->groupBy('sppgs.id', 'sppgs.name')
                    ->orderByDesc('total_qty')
                    ->limit(8);

                $ordersBySppg = $ordersBySppgQuery->get()->map(function ($row) {
                    return [
                        'sppg_id' => (int) $row->sppg_id,
                        'sppg_name' => (string) $row->sppg_name,
                        'total_qty' => (float) $row->total_qty,
                        'total_amount' => (float) $row->total_amount,
                    ];
                });

                $maxOrderQtyBySppg = (float) ($ordersBySppg->max('total_qty') ?? 0.0);
            }
        }

        return view('procurement.dashboard', compact(
            'stats',
            'recentPurchaseRequests',
            'recentPurchaseOrders',
            'recentInvoices',
            'showAssetAndChartMetrics',
            'showProfitMarginMetric',
            'totalAssetValue',
            'totalProfitMargin',
            'ordersBySppg',
            'maxOrderQtyBySppg'
        ));
    }

    public function purchaseRequests(): View
    {
        $currentUser = Auth::user();
        $isSppgUser = $currentUser?->role?->value === UserRole::SPPG_USER->value;
        $currentUserSppgId = $isSppgUser ? (int) ($currentUser?->sppg_id ?? 0) : null;

        $purchaseRequests = PurchaseRequest::query()
            ->with(['sppg', 'requester', 'additionalToPurchaseOrder'])
            ->when($isSppgUser, fn ($query) => $query->where('sppg_id', $currentUserSppgId))
            ->withCount([
                'items as ad_hoc_items_count' => function ($query) {
                    $query->whereHas('product', function ($productQuery) {
                        $productQuery->where('is_ad_hoc', true);
                    });
                },
            ])
            ->latest('request_date')
            ->paginate(15);

        $sppgs = Sppg::query()
            ->where('is_active', true)
            ->when($isSppgUser, fn ($query) => $query->whereKey($currentUserSppgId))
            ->orderBy('name')
            ->get();
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get();
        $referencePurchaseOrders = PurchaseOrder::query()
            ->with(['vendor', 'sppg'])
            ->whereIn('status', [DocumentStatus::APPROVED->value, DocumentStatus::PROCESSED->value])
            ->when($isSppgUser, fn ($query) => $query->where('sppg_id', $currentUserSppgId))
            ->latest('order_date')
            ->limit(200)
            ->get();

        $priceHistories = ProductPriceHistory::query()
            ->whereIn('product_id', $products->pluck('id'))
            ->orderByDesc('effective_at')
            ->orderByDesc('id')
            ->get(['product_id', 'vendor_id', 'price']);

        $priceLookup = [];
        foreach ($priceHistories as $history) {
            $key = $history->product_id.':'.($history->vendor_id ?? 0);
            if (! array_key_exists($key, $priceLookup)) {
                $priceLookup[$key] = (float) $history->price;
            }
        }

        $requesterFallbackMap = [];
        $purchaseRequestIds = collect($purchaseRequests->items())->pluck('id')->all();
        if ($purchaseRequestIds !== []) {
            $fallbackTrails = AuditTrail::query()
                ->with('user:id,name')
                ->where('event', 'purchase_request.created')
                ->where('auditable_type', PurchaseRequest::class)
                ->whereIn('auditable_id', $purchaseRequestIds)
                ->latest('created_at')
                ->get();

            foreach ($fallbackTrails as $trail) {
                $auditableId = (int) $trail->auditable_id;
                if (! array_key_exists($auditableId, $requesterFallbackMap)) {
                    $requesterFallbackMap[$auditableId] = $trail->user?->name;
                }
            }
        }

        $sppgUserOptionsBySppg = [];
        $sppgIds = collect($purchaseRequests->items())->pluck('sppg_id')->filter()->unique()->values();
        if ($sppgIds->isNotEmpty()) {
            $sppgUsers = User::query()
                ->where('role', UserRole::SPPG_USER->value)
                ->whereIn('sppg_id', $sppgIds)
                ->orderBy('name')
                ->get(['id', 'name', 'sppg_id']);

            foreach ($sppgUsers as $sppgUser) {
                $sppgKey = (int) ($sppgUser->sppg_id ?? 0);
                if ($sppgKey <= 0) {
                    continue;
                }

                if (! array_key_exists($sppgKey, $sppgUserOptionsBySppg)) {
                    $sppgUserOptionsBySppg[$sppgKey] = [];
                }

                $sppgUserOptionsBySppg[$sppgKey][] = [
                    'id' => (int) $sppgUser->id,
                    'name' => $sppgUser->name,
                ];
            }
        }

        return view('procurement.purchase-requests.index', compact('purchaseRequests', 'sppgs', 'products', 'vendors', 'referencePurchaseOrders', 'priceLookup', 'requesterFallbackMap', 'sppgUserOptionsBySppg'));
    }

    public function assignPurchaseRequestRequester(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $validated = $request->validate([
            'requested_by' => ['required', 'exists:users,id'],
        ]);

        $requester = User::query()->findOrFail((int) $validated['requested_by']);

        if ($requester->role?->value !== UserRole::SPPG_USER->value) {
            return redirect()
                ->route('ui.purchase-requests.index')
                ->withErrors(['requested_by' => 'Requester harus user dengan role SPPG User.']);
        }

        if ((int) ($requester->sppg_id ?? 0) !== (int) $purchaseRequest->sppg_id) {
            return redirect()
                ->route('ui.purchase-requests.index')
                ->withErrors(['requested_by' => 'Requester harus berasal dari SPPG yang sama dengan PR.']);
        }

        $oldValues = $purchaseRequest->toArray();

        $purchaseRequest->update([
            'requested_by' => $requester->id,
        ]);

        $this->writeAudit(
            $request,
            'purchase_request.requester_assigned',
            $purchaseRequest,
            $oldValues,
            $purchaseRequest->fresh()?->toArray()
        );

        return redirect()
            ->route('ui.purchase-requests.index')
            ->with('success', 'Requester PR berhasil diperbarui.');
    }

    public function purchaseOrders(): View
    {
        $vendorScopeId = $this->currentVendorScopeId();

        $purchaseOrders = PurchaseOrder::query()
            ->with(['sppg', 'vendor', 'purchaseRequest'])
            ->when($vendorScopeId !== null, fn ($query) => $query->where('vendor_id', $vendorScopeId > 0 ? $vendorScopeId : -1))
            ->withCount([
                'items as ad_hoc_items_count' => function ($query) {
                    $query->whereHas('product', function ($productQuery) {
                        $productQuery->where('is_ad_hoc', true);
                    });
                },
            ])
            ->latest('order_date')
            ->paginate(15);

        return view('procurement.purchase-orders.index', compact('purchaseOrders'));
    }

    public function downloadPurchaseOrderPdf(PurchaseOrder $purchaseOrder): Response
    {
        $vendorScopeId = $this->currentVendorScopeId();
        if ($vendorScopeId !== null && (int) $purchaseOrder->vendor_id !== $vendorScopeId) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak purchase order vendor lain.');
        }

        $purchaseOrder->loadMissing([
            'sppg',
            'vendor',
            'orderedBy',
            'purchaseRequest.requester',
            'items.product',
        ]);

        $rows = $purchaseOrder->items->map(function ($item, $index) {
            $isAdHoc = (bool) ($item->product?->is_ad_hoc ?? false);

            return [
                'no' => $index + 1,
                'name' => ($item->product?->name ?? '-').($isAdHoc ? ' (NON KATALOG)' : ''),
                'qty' => (float) $item->quantity,
                'unit' => $item->product?->unit ?? '-',
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->subtotal,
                'notes' => $isAdHoc ? 'Item non katalog / ad-hoc' : '-',
                'arrival_time' => '-',
            ];
        });

        return $this->renderProcurementDocumentPdf([
            'documentTypeLabel' => 'PURCHASE ORDER',
            'documentNumber' => $purchaseOrder->number,
            'referenceNumber' => $purchaseOrder->purchaseRequest?->number,
            'documentDate' => $purchaseOrder->order_date,
            'neededDate' => $purchaseOrder->purchaseRequest?->needed_date,
            'recipientName' => $purchaseOrder->vendor?->name,
            'senderName' => $purchaseOrder->sppg?->name,
            'senderAddress' => $purchaseOrder->sppg?->address,
            'creatorName' => $purchaseOrder->sppg?->accounting_name ?: $purchaseOrder->orderedBy?->name,
            'approverName' => $purchaseOrder->sppg?->ka_sppg_name ?: $purchaseOrder->purchaseRequest?->requester?->name,
            'itemsRows' => $rows,
            'totalAmount' => (float) $purchaseOrder->total_amount,
            'notes' => $purchaseOrder->notes,
        ], $purchaseOrder->number.'.pdf');
    }

    public function downloadPurchaseRequestPdf(PurchaseRequest $purchaseRequest): Response
    {
        $user = Auth::user();
        if (
            $user?->role?->value === UserRole::SPPG_USER->value
            && (int) ($user->sppg_id ?? 0) !== (int) $purchaseRequest->sppg_id
        ) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak purchase request ini.');
        }

        $purchaseRequest->loadMissing([
            'sppg',
            'requester',
            'items.product',
            'purchaseOrders.vendor',
            'purchaseOrders.orderedBy',
        ]);

        $primaryPo = $purchaseRequest->purchaseOrders->first();

        $rows = $purchaseRequest->items->map(function ($item, $index) {
            $isAdHoc = (bool) ($item->product?->is_ad_hoc ?? false);

            return [
                'no' => $index + 1,
                'name' => ($item->product?->name ?? '-').($isAdHoc ? ' (NON KATALOG)' : ''),
                'qty' => (float) $item->quantity,
                'unit' => $item->product?->unit ?? '-',
                'unit_price' => (float) $item->requested_unit_price,
                'total_price' => (float) $item->subtotal,
                'notes' => $isAdHoc ? 'Item non katalog / ad-hoc' : ($item->notes ?: '-'),
                'arrival_time' => '-',
            ];
        });

        return $this->renderProcurementDocumentPdf([
            'documentTypeLabel' => 'PURCHASE REQUEST',
            'documentNumber' => $purchaseRequest->number,
            'referenceNumber' => $primaryPo?->number,
            'documentDate' => $purchaseRequest->request_date,
            'neededDate' => $purchaseRequest->needed_date,
            'recipientName' => $primaryPo?->vendor?->name,
            'senderName' => $purchaseRequest->sppg?->name,
            'senderAddress' => $purchaseRequest->sppg?->address,
            'creatorName' => $purchaseRequest->requester?->name,
            'approverName' => $primaryPo?->orderedBy?->name,
            'itemsRows' => $rows,
            'totalAmount' => (float) $purchaseRequest->total_amount,
            'notes' => $purchaseRequest->notes,
        ], $purchaseRequest->number.'.pdf');
    }

    private function renderProcurementDocumentPdf(array $payload, string $filename): Response
    {
        $pdf = Pdf::loadView('procurement.purchase-orders.pdf', [
            'documentTypeLabel' => $payload['documentTypeLabel'] ?? '-',
            'documentNumber' => $payload['documentNumber'] ?? '-',
            'referenceNumber' => $payload['referenceNumber'] ?? '-',
            'documentDate' => $payload['documentDate'] ?? null,
            'neededDate' => $payload['neededDate'] ?? null,
            'recipientName' => $payload['recipientName'] ?? null,
            'senderName' => $payload['senderName'] ?? '-',
            'senderAddress' => $payload['senderAddress'] ?? null,
            'creatorName' => $payload['creatorName'] ?? null,
            'approverName' => $payload['approverName'] ?? null,
            'itemsRows' => $payload['itemsRows'] ?? collect(),
            'totalAmount' => $payload['totalAmount'] ?? 0,
            'documentNotes' => $payload['notes'] ?? null,
            'generatedAt' => now(),
            'logoBgn' => $this->imageDataUri(public_path('images/logo-bgn.png'), true),
            'logoSmp' => $this->imageDataUri(public_path('images/smp-logo.png'), true),
            'logoSppg' => $this->imageDataUri(public_path('images/logo-smp.png'), true),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function deliveries(Request $request): View
    {
        ['selectedVendorId' => $selectedVendorId, 'selectedVendor' => $selectedVendor, 'vendors' => $vendors] = $this->resolveScopedVendorSelection($request);
        $currentRole = Auth::user()?->role?->value;
        $canStartDelivery = in_array($currentRole, [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN_GUDANG->value,
            UserRole::EXPEDITION->value,
        ], true);
        $canCompleteDelivery = in_array($currentRole, [
            UserRole::SUPER_ADMIN->value,
            UserRole::EXPEDITION->value,
        ], true);

        $deliveries = Delivery::query()
            ->with(['sppg', 'vendor', 'purchaseOrder'])
            ->when($selectedVendorId, fn ($query) => $query->where('vendor_id', $selectedVendorId))
            ->latest('delivery_date')
            ->paginate(15)
            ->withQueryString();

        $pendingPurchaseOrders = PurchaseOrder::query()
            ->with(['sppg', 'vendor'])
            ->when($selectedVendorId, fn ($query) => $query->where('vendor_id', $selectedVendorId))
            ->whereIn('status', [DocumentStatus::APPROVED->value, DocumentStatus::PROCESSED->value])
            ->whereDoesntHave('deliveries')
            ->latest('order_date')
            ->get();

        return view('procurement.deliveries.index', compact(
            'deliveries',
            'vendors',
            'selectedVendor',
            'pendingPurchaseOrders',
            'canStartDelivery',
            'canCompleteDelivery'
        ));
    }

    public function createDeliveryFromPurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! in_array($purchaseOrder->status?->value, [
            DocumentStatus::APPROVED->value,
            DocumentStatus::PROCESSED->value,
            DocumentStatus::DELIVERED->value,
            DocumentStatus::INVOICED->value,
        ], true)) {
            return redirect()
                ->route('ui.deliveries.index', $request->only('vendor'))
                ->withErrors(['delivery' => 'Status PO tidak valid untuk membuat pengiriman baru.']);
        }

        $existingActiveDelivery = $purchaseOrder->deliveries()
            ->whereIn('status', [
                DocumentStatus::PROCESSED->value,
                DocumentStatus::DELIVERED->value,
                DocumentStatus::INVOICED->value,
                DocumentStatus::PAID->value,
            ])
            ->first();

        if ($existingActiveDelivery) {
            return redirect()
                ->route('ui.deliveries.index', $request->only('vendor'))
                ->withErrors(['delivery' => 'PO ini sudah memiliki delivery aktif.']);
        }

        $delivery = Delivery::query()->create([
            'number' => $this->generateNumber('DLV'),
            'purchase_order_id' => $purchaseOrder->id,
            'goods_receipt_id' => null,
            'sppg_id' => $purchaseOrder->sppg_id,
            'vendor_id' => $purchaseOrder->vendor_id,
            'delivered_by' => Auth::id(),
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'total_amount' => (float) $purchaseOrder->total_amount,
            'invoiced_po_amount' => 0,
            'notes' => 'Pengiriman sedang on proses oleh ekspedisi.',
        ]);

        $purchaseOrder->update([
            'status' => DocumentStatus::PROCESSED,
        ]);

        $this->writeAudit(
            $request,
            'delivery.created_from_po',
            $delivery,
            null,
            [
                'delivery_number' => $delivery->number,
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_order_number' => $purchaseOrder->number,
                'vendor_id' => $purchaseOrder->vendor_id,
                'status' => $delivery->status?->value,
            ]
        );

        return redirect()
            ->route('ui.deliveries.index', $request->only('vendor'))
            ->with('success', 'Pengiriman barang dimulai. Status delivery saat ini: on proses.');
    }

    public function completeDeliveryByExpedition(Request $request, Delivery $delivery): RedirectResponse
    {
        $vendorScopeId = $this->currentVendorScopeId();
        if ($vendorScopeId !== null && (int) $delivery->vendor_id !== (int) $vendorScopeId) {
            abort(403, 'Anda tidak memiliki akses untuk memproses delivery vendor lain.');
        }

        if ($delivery->status?->value !== DocumentStatus::PROCESSED->value) {
            return redirect()
                ->route('ui.deliveries.index', $request->only('vendor'))
                ->withErrors(['delivery' => 'Hanya delivery berstatus on proses yang dapat diselesaikan.']);
        }

        $validated = $request->validate([
            'delivery_proof_image' => ['required', 'image', 'max:5120'],
            'signed_delivery_note' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $proofImageError = $this->ensureSafeUploadedFile(
            $request,
            $request->file('delivery_proof_image'),
            ['jpg', 'jpeg', 'png', 'webp'],
            ['image/jpeg', 'image/png', 'image/webp'],
            5120,
            [
                UserRole::EXPEDITION->value => 6144,
                UserRole::SUPER_ADMIN->value => 8192,
            ],
            'delivery_proof_image',
            'Bukti pengiriman'
        );
        if ($proofImageError !== null) {
            return $proofImageError;
        }

        $signedNoteError = $this->ensureSafeUploadedFile(
            $request,
            $request->file('signed_delivery_note'),
            ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
            ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            8192,
            [
                UserRole::EXPEDITION->value => 8192,
                UserRole::SUPER_ADMIN->value => 10240,
            ],
            'signed_delivery_note',
            'Surat jalan signed'
        );
        if ($signedNoteError !== null) {
            return $signedNoteError;
        }

        $proofImagePath = $request->file('delivery_proof_image')->store('deliveries/proofs', 'public');
        $signedDeliveryNotePath = $request->file('signed_delivery_note')->store('deliveries/surat-jalan-signed', 'public');

        $oldValues = $delivery->toArray();

        $delivery->update([
            'delivery_proof_image_path' => $proofImagePath,
            'signed_delivery_note_path' => $signedDeliveryNotePath,
            'proof_uploaded_at' => now(),
            'delivered_at' => now(),
            'delivery_date' => $delivery->delivery_date ?? now()->toDateString(),
            'delivered_by' => Auth::id(),
            'status' => DocumentStatus::DELIVERED,
        ]);

        $purchaseOrder = $delivery->purchaseOrder;
        if ($purchaseOrder && $purchaseOrder->status?->value !== DocumentStatus::PAID->value) {
            $purchaseOrder->update([
                'status' => DocumentStatus::DELIVERED,
            ]);
        }

        $delivery->refresh();

        $this->writeAudit(
            $request,
            'delivery.completed_by_expedition',
            $delivery,
            $oldValues,
            $delivery->toArray()
        );

        return redirect()
            ->route('ui.deliveries.index', $request->only('vendor'))
            ->with('success', 'Bukti pengiriman dan surat jalan berhasil diupload. Status delivery berubah menjadi delivered.');
    }

    public function rejectedItems(Request $request): View
    {
        $vendorScopeId = $this->currentVendorScopeId();
        $sppgScopeId = $this->currentSppgScopeId();

        $deliveries = Delivery::query()
            ->with(['purchaseOrder.items.product', 'sppg', 'vendor'])
            ->when($vendorScopeId !== null, fn ($query) => $query->where('vendor_id', $this->scopedId($vendorScopeId)))
            ->when($sppgScopeId !== null, fn ($query) => $query->where('sppg_id', $this->scopedId($sppgScopeId)))
            ->latest('delivery_date')
            ->limit(250)
            ->get();

        $selectedDeliveryId = $request->filled('delivery_id') ? (int) $request->integer('delivery_id') : null;
        if ($selectedDeliveryId !== null && ! $deliveries->contains('id', $selectedDeliveryId)) {
            $selectedDeliveryId = null;
        }

        $selectedDelivery = $selectedDeliveryId !== null
            ? $deliveries->firstWhere('id', $selectedDeliveryId)
            : $deliveries->first();

        $availableItems = collect($selectedDelivery?->purchaseOrder?->items ?? [])
            ->map(function (PurchaseOrderItem $item) {
                return [
                    'id' => (int) $item->id,
                    'product_id' => (int) ($item->product_id ?? 0),
                    'product_name' => $item->product?->name ?? '-',
                    'product_sku' => $item->product?->sku ?? '-',
                    'unit' => $item->product?->unit ?? '-',
                    'ordered_quantity' => (float) $item->quantity,
                ];
            })
            ->values();

        $rejectedItems = RejectedItem::query()
            ->with([
                'delivery:id,number,purchase_order_id,sppg_id,vendor_id,delivery_date',
                'delivery.sppg:id,name',
                'delivery.vendor:id,name',
                'delivery.purchaseOrder:id,number',
                'product:id,name,sku,unit',
                'reporter:id,name',
            ])
            ->when($vendorScopeId !== null, function ($query) use ($vendorScopeId) {
                $query->whereHas('delivery', fn ($deliveryQuery) => $deliveryQuery->where('vendor_id', $this->scopedId($vendorScopeId)));
            })
            ->when($sppgScopeId !== null, function ($query) use ($sppgScopeId) {
                $query->whereHas('delivery', fn ($deliveryQuery) => $deliveryQuery->where('sppg_id', $this->scopedId($sppgScopeId)));
            })
            ->when($selectedDelivery?->id, fn ($query) => $query->where('delivery_id', (int) $selectedDelivery->id))
            ->orderByDesc('reported_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('procurement.rejected-items.index', [
            'deliveries' => $deliveries,
            'selectedDeliveryId' => $selectedDelivery?->id,
            'availableItems' => $availableItems,
            'rejectedItems' => $rejectedItems,
        ]);
    }

    public function storeRejectedItem(Request $request): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, ['quantity']);

        $validated = $request->validate([
            'delivery_id' => ['required', 'exists:deliveries,id'],
            'purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:2000'],
            'reported_at' => ['nullable', 'date'],
            'evidence_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $evidenceImageError = $this->ensureSafeUploadedFile(
            $request,
            $request->file('evidence_image'),
            ['jpg', 'jpeg', 'png', 'webp'],
            ['image/jpeg', 'image/png', 'image/webp'],
            5120,
            [
                UserRole::ADMIN_GUDANG->value => 5120,
                UserRole::SUPER_ADMIN->value => 8192,
            ],
            'evidence_image',
            'Foto bukti reject'
        );
        if ($evidenceImageError !== null) {
            return $evidenceImageError;
        }

        $delivery = Delivery::query()
            ->with(['purchaseOrder.items.product'])
            ->findOrFail((int) $validated['delivery_id']);

        $vendorScopeId = $this->currentVendorScopeId();
        if ($vendorScopeId !== null && (int) $delivery->vendor_id !== $vendorScopeId) {
            abort(403, 'Anda tidak memiliki akses untuk mencatat reject pada delivery vendor lain.');
        }

        $sppgScopeId = $this->currentSppgScopeId();
        if ($sppgScopeId !== null && (int) $delivery->sppg_id !== $sppgScopeId) {
            abort(403, 'Anda tidak memiliki akses untuk mencatat reject pada delivery SPPG lain.');
        }

        $selectedPoItem = $delivery->purchaseOrder?->items
            ?->firstWhere('id', (int) $validated['purchase_order_item_id']);

        if (! $selectedPoItem) {
            return redirect()
                ->route('ui.rejected-items.index', ['delivery_id' => $delivery->id])
                ->withErrors(['purchase_order_item_id' => 'Barang reject harus sesuai item yang dipesan di PO delivery ini.'])
                ->withInput();
        }

        if ((float) $validated['quantity'] > (float) $selectedPoItem->quantity) {
            return redirect()
                ->route('ui.rejected-items.index', ['delivery_id' => $delivery->id])
                ->withErrors(['quantity' => 'Jumlah reject tidak boleh melebihi kuantitas item pada PO.'])
                ->withInput();
        }

        $imagePath = null;
        if ($request->hasFile('evidence_image')) {
            $imagePath = $request->file('evidence_image')->store('rejected-items', 'public');
        }

        $rejectedItem = RejectedItem::query()->create([
            'delivery_id' => $delivery->id,
            'purchase_order_item_id' => $selectedPoItem->id,
            'product_id' => $selectedPoItem->product_id,
            'reported_by' => Auth::id(),
            'quantity' => (float) $validated['quantity'],
            'reason' => trim((string) $validated['reason']),
            'evidence_image_path' => $imagePath,
            'reported_at' => $validated['reported_at'] ?? now()->toDateString(),
        ]);

        $this->writeAudit(
            $request,
            'delivery.rejected_item_reported',
            $rejectedItem,
            null,
            $rejectedItem->toArray()
        );

        return redirect()
            ->route('ui.rejected-items.index', ['delivery_id' => $delivery->id])
            ->with('success', 'Barang reject berhasil dicatat.');
    }

    public function previewDeliveryNotePdf(Delivery $delivery): Response
    {
        $vendorScopeId = $this->currentVendorScopeId();
        if ($vendorScopeId !== null && (int) $delivery->vendor_id !== $vendorScopeId) {
            abort(403, 'Anda tidak memiliki akses untuk surat jalan vendor lain.');
        }

        $delivery->loadMissing([
            'vendor',
            'sppg',
            'purchaseOrder.items.product',
        ]);

        $rows = collect();
        $po = $delivery->purchaseOrder;

        if ($po) {
            foreach ($po->items as $index => $item) {
                $rows->push([
                    'no' => $index + 1,
                    'name' => $item->product?->name ?? '-',
                    'qty' => (float) $item->quantity,
                    'unit' => $item->product?->unit ?? '-',
                    'notes' => $item->notes ?? '-',
                ]);
            }
        }

        [$logoVendor, $hasCustomVendorLogo] = $this->resolveVendorLogoDataUri($delivery->vendor, true);

        $filename = 'SURAT-JALAN-'.($delivery->number ?? 'DELIVERY').'.pdf';

        $pdf = Pdf::loadView('procurement.deliveries.surat-jalan-pdf', [
            'delivery' => $delivery,
            'vendor' => $delivery->vendor,
            'sppg' => $delivery->sppg,
            'purchaseOrder' => $po,
            'itemsRows' => $rows,
            'logoBgn' => $this->imageDataUri(public_path('images/logo-bgn.png'), true),
            'logoVendor' => $logoVendor,
            'hasCustomVendorLogo' => $hasCustomVendorLogo,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    public function invoices(Request $request): View
    {
        ['selectedVendorId' => $selectedVendorId, 'selectedVendor' => $selectedVendor, 'vendors' => $vendors] = $this->resolveScopedVendorSelection($request);
        $weeklyVendorInvoiceTotal = 0;
        $weeklyVendorInvoiceCount = 0;
        $weekStartDate = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEndDate = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        if ($selectedVendorId) {
            $weeklyVendorInvoiceSummary = Invoice::query()
                ->where('vendor_id', $selectedVendorId)
                ->whereBetween('invoice_date', [$weekStartDate, $weekEndDate])
                ->selectRaw('COUNT(*) as invoice_count, COALESCE(SUM(total_amount), 0) as total_amount')
                ->first();

            $weeklyVendorInvoiceTotal = (float) ($weeklyVendorInvoiceSummary?->total_amount ?? 0);
            $weeklyVendorInvoiceCount = (int) ($weeklyVendorInvoiceSummary?->invoice_count ?? 0);
        }

        $invoices = Invoice::query()
            ->with(['sppg', 'vendor', 'payments'])
            ->when($selectedVendorId, fn ($query) => $query->where('vendor_id', $selectedVendorId))
            ->latest('invoice_date')
            ->paginate(15)
            ->withQueryString();

        $additionalInvoiceIds = [];
        $additionalInvoiceRefs = [];
        foreach ($invoices->items() as $invoice) {
            $isAdditional = false;
            $additionalPoNumbers = collect();

            if ($invoice->billingCycle) {
                $additionalDeliveries = Delivery::query()
                    ->with(['purchaseOrder:id,number,notes'])
                    ->where('sppg_id', $invoice->sppg_id)
                    ->where('vendor_id', $invoice->vendor_id)
                    ->whereBetween('delivery_date', [
                        $invoice->billingCycle->week_start_date,
                        $invoice->billingCycle->week_end_date,
                    ])
                    ->where(function ($query) {
                        $query
                            ->where('notes', 'like', '%[BARANG TAMBAHAN]%')
                            ->orWhereHas('purchaseOrder', function ($poQuery) {
                                $poQuery->where('notes', 'like', '%[BARANG TAMBAHAN]%');
                            });
                    })
                    ->get();

                $isAdditional = $additionalDeliveries->isNotEmpty();
                $additionalPoNumbers = $additionalDeliveries
                    ->map(fn (Delivery $delivery) => $delivery->purchaseOrder?->number)
                    ->filter()
                    ->unique()
                    ->values();
            } elseif ($invoice->delivery_id) {
                $additionalDelivery = Delivery::query()
                    ->with(['purchaseOrder:id,number,notes'])
                    ->whereKey($invoice->delivery_id)
                    ->where(function ($query) {
                        $query
                            ->where('notes', 'like', '%[BARANG TAMBAHAN]%')
                            ->orWhereHas('purchaseOrder', function ($poQuery) {
                                $poQuery->where('notes', 'like', '%[BARANG TAMBAHAN]%');
                            });
                    })
                    ->first();

                $isAdditional = $additionalDelivery !== null;
                $additionalPoNumbers = collect([$additionalDelivery?->purchaseOrder?->number])
                    ->filter()
                    ->unique()
                    ->values();
            }

            if ($isAdditional) {
                $invoiceId = (int) $invoice->id;
                $additionalInvoiceIds[] = $invoiceId;
                $additionalInvoiceRefs[$invoiceId] = $additionalPoNumbers->isNotEmpty()
                    ? $additionalPoNumbers->implode(', ')
                    : '-';
            }
        }

        $pendingPurchaseOrders = PurchaseOrder::query()
            ->with([
                'sppg',
                'vendor',
                'deliveries' => fn ($query) => $query->latest('id')->limit(1),
            ])
            ->when($selectedVendorId, fn ($query) => $query->where('vendor_id', $selectedVendorId))
            ->whereIn('status', [DocumentStatus::APPROVED->value, DocumentStatus::PROCESSED->value])
            ->latest('order_date')
            ->get()
            ->filter(function (PurchaseOrder $purchaseOrder) {
                $latestDelivery = $purchaseOrder->deliveries->first();
                if (! $latestDelivery) {
                    return true;
                }

                return (float) $purchaseOrder->total_amount > (float) ($latestDelivery->invoiced_po_amount ?? 0);
            })
            ->values();

        return view('procurement.invoices.index', compact(
            'invoices',
            'additionalInvoiceIds',
            'additionalInvoiceRefs',
            'pendingPurchaseOrders',
            'vendors',
            'selectedVendor',
            'weeklyVendorInvoiceTotal',
            'weeklyVendorInvoiceCount',
            'weekStartDate',
            'weekEndDate'
        ));
    }

    public function downloadInvoicePdf(Invoice $invoice): Response
    {
        $vendorScopeId = $this->currentVendorScopeId();
        if ($vendorScopeId !== null && (int) $invoice->vendor_id !== $vendorScopeId) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak invoice vendor lain.');
        }

        $invoice->loadMissing([
            'sppg',
            'vendor',
            'payments',
            'billingCycle',
            'delivery.purchaseOrder.items.product',
            'delivery.purchaseOrder.orderedBy',
            'delivery.purchaseOrder.purchaseRequest.requester',
        ]);

        $invoiceDate = $invoice->invoice_date ? Carbon::parse($invoice->invoice_date) : now();
        $payment = $invoice->payments->sortByDesc('payment_date')->first();
        [$vendorLogo, $hasCustomVendorLogo] = $this->resolveVendorLogoDataUri($invoice->vendor, true);

        $deliveriesForInvoice = collect();
        if ($invoice->billingCycle) {
            $deliveriesForInvoice = Delivery::query()
                ->with(['purchaseOrder.items.product'])
                ->where('sppg_id', $invoice->sppg_id)
                ->where('vendor_id', $invoice->vendor_id)
                ->whereBetween('delivery_date', [
                    $invoice->billingCycle->week_start_date,
                    $invoice->billingCycle->week_end_date,
                ])
                ->whereIn('status', [DocumentStatus::INVOICED->value, DocumentStatus::PAID->value])
                ->orderBy('delivery_date')
                ->orderBy('number')
                ->get();
        }

        if ($deliveriesForInvoice->isEmpty() && $invoice->delivery) {
            $deliveriesForInvoice = collect([$invoice->delivery]);
        }

        $rows = collect();
        foreach ($deliveriesForInvoice as $delivery) {
            $po = $delivery->purchaseOrder;
            if (! $po) {
                continue;
            }

            foreach ($po->items as $item) {
                $isAdditionalPo = str_contains((string) ($po->notes ?? ''), '[BARANG TAMBAHAN]');
                $isAdHoc = (bool) ($item->product?->is_ad_hoc ?? false);
                $rows->push([
                    'name' => ($item->product?->name ?? '-').($isAdditionalPo ? ' (Tambahan)' : '').($isAdHoc ? ' (NON KATALOG)' : ''),
                    'qty' => (float) $item->quantity,
                    'unit' => $item->product?->unit ?? '-',
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->subtotal,
                    'notes' => 'PO: '.($po->number ?? '-').($isAdditionalPo ? ' [BARANG TAMBAHAN]' : '').($isAdHoc ? ' [NON KATALOG]' : ''),
                    'arrival_time' => $delivery->delivery_date ? Carbon::parse($delivery->delivery_date)->format('d/m/Y') : '-',
                ]);
            }
        }

        $rows = $rows->values()->map(function ($row, $index) {
            $row['no'] = $index + 1;

            return $row;
        });

        if ($rows->isEmpty()) {
            $rows = collect([
                [
                    'no' => 1,
                    'name' => 'Tagihan vendor mingguan',
                    'qty' => 1,
                    'unit' => 'Lot',
                    'unit_price' => (float) $invoice->subtotal_amount,
                    'total_price' => (float) $invoice->subtotal_amount,
                    'notes' => '-',
                    'arrival_time' => '-',
                ],
            ]);
        }

        $pdf = Pdf::loadView('procurement.invoices.invoice-pdf', [
            'invoice' => $invoice,
            'vendor' => $invoice->vendor,
            'sppg' => $invoice->sppg,
            'invoiceDate' => $invoiceDate,
            'itemsRows' => $rows,
            'subtotalAmount' => (float) $invoice->subtotal_amount,
            'taxAmount' => (float) $invoice->tax_amount,
            'totalAmount' => (float) $invoice->total_amount,
            'paymentMethod' => $payment?->payment_method,
            'paymentReference' => $payment?->reference_no,
            'signatureName' => $invoice->vendor?->name,
            'logoVendor' => $vendorLogo,
            'hasCustomVendorLogo' => $hasCustomVendorLogo,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($invoice->number.'.pdf');
    }

    public function downloadVendorInvoiceSummaryPdf(Request $request): Response
    {
        $vendorScopeId = $this->currentVendorScopeId();
        $vendorId = $vendorScopeId ?? (int) $request->integer('vendor');
        if ($vendorId <= 0) {
            abort(404);
        }

        $vendor = Vendor::query()->findOrFail($vendorId);

        $weekStartDate = $request->filled('week_start')
            ? Carbon::parse((string) $request->input('week_start'))->startOfDay()->toDateString()
            : now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $weekEndDate = $request->filled('week_end')
            ? Carbon::parse((string) $request->input('week_end'))->endOfDay()->toDateString()
            : now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $summaryInvoices = Invoice::query()
            ->with(['sppg'])
            ->where('vendor_id', $vendorId)
            ->whereBetween('invoice_date', [$weekStartDate, $weekEndDate])
            ->orderBy('invoice_date')
            ->orderBy('number')
            ->get();

        $summaryTotal = (float) $summaryInvoices->sum(fn ($invoice) => (float) $invoice->total_amount);

        $pdf = Pdf::loadView('procurement.invoices.summary-pdf', [
            'vendor' => $vendor,
            'weekStartDate' => $weekStartDate,
            'weekEndDate' => $weekEndDate,
            'summaryInvoices' => $summaryInvoices,
            'summaryTotal' => $summaryTotal,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeVendorName = preg_replace('/[^A-Za-z0-9\-]/', '-', strtoupper((string) $vendor->name)) ?: 'VENDOR';
        $filename = sprintf('REKAP-INVOICE-%s-%s-%s.pdf', $safeVendorName, $weekStartDate, $weekEndDate);

        return $pdf->download($filename);
    }

    public function createInvoicePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->loadMissing('payments');

        if ($invoice->status === DocumentStatus::PAID) {
            return redirect()
                ->route('ui.invoices.index', $request->only('vendor'))
                ->withErrors(['payment' => 'Invoice ini sudah berstatus paid.']);
        }

        $activePayment = $invoice->payments
            ->first(fn (Payment $payment) => in_array($payment->status?->value, [
                PaymentStatus::DRAFT->value,
                PaymentStatus::SUBMITTED->value,
                PaymentStatus::APPROVED->value,
                PaymentStatus::PAID->value,
            ], true));

        if ($activePayment) {
            return redirect()
                ->route('ui.invoices.index', $request->only('vendor'))
                ->withErrors(['payment' => 'Invoice ini sudah memiliki proses pembayaran aktif.']);
        }

        $oldInvoice = $invoice->toArray();
        $createdPayment = null;

        DB::transaction(function () use ($invoice, &$createdPayment) {
            $createdPayment = Payment::query()->create([
                'number' => $this->generateNumber('PAY'),
                'invoice_id' => $invoice->id,
                'payment_date' => null,
                'amount' => (float) $invoice->total_amount,
                'status' => PaymentStatus::DRAFT,
                'payment_method' => null,
                'reference_no' => null,
                'proof_image_path' => null,
                'proof_uploaded_by' => null,
                'proof_uploaded_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'paid_by' => null,
                'notes' => 'Menunggu upload bukti pembayaran oleh SPPG.',
            ]);
        });

        if ($createdPayment) {
            $this->writeAudit(
                $request,
                'payment.created_from_invoice',
                $createdPayment,
                null,
                $createdPayment->toArray()
            );
        }

        $invoice->refresh();

        return redirect()
            ->route('ui.invoices.index', $request->only('vendor'))
            ->with('success', 'Draft pembayaran berhasil dibuat. Menunggu upload bukti oleh user SPPG.');
    }

    public function uploadPaymentProof(Request $request, Payment $payment): RedirectResponse
    {
        $sppgScopeId = $this->currentSppgScopeId();
        if ($sppgScopeId === null) {
            abort(403, 'Hanya user SPPG yang dapat upload bukti pembayaran.');
        }

        $payment->loadMissing('invoice');
        if ((int) ($payment->invoice?->sppg_id ?? 0) !== $sppgScopeId) {
            abort(403, 'Anda tidak memiliki akses untuk payment SPPG lain.');
        }

        if ($payment->status === PaymentStatus::PAID) {
            return redirect()
                ->route('ui.payments.index', $request->only('invoice'))
                ->withErrors(['payment' => 'Payment ini sudah berstatus paid.']);
        }

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'proof_image' => ['required', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $paymentProofError = $this->ensureSafeUploadedFile(
            $request,
            $request->file('proof_image'),
            ['jpg', 'jpeg', 'png', 'webp'],
            ['image/jpeg', 'image/png', 'image/webp'],
            5120,
            [
                UserRole::SPPG_USER->value => 5120,
                UserRole::SUPER_ADMIN->value => 8192,
            ],
            'proof_image',
            'Bukti pembayaran'
        );
        if ($paymentProofError !== null) {
            return $paymentProofError;
        }

        $proofPath = $request->file('proof_image')->store('payment-proofs', 'public');

        $oldValues = $payment->toArray();
        $payment->update([
            'payment_date' => $validated['payment_date'],
            'payment_method' => trim((string) ($validated['payment_method'] ?? 'Transfer')),
            'reference_no' => trim((string) ($validated['reference_no'] ?? '')) ?: null,
            'proof_image_path' => $proofPath,
            'proof_uploaded_by' => Auth::id(),
            'proof_uploaded_at' => now(),
            'status' => PaymentStatus::SUBMITTED,
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: $payment->notes,
        ]);

        $this->writeAudit(
            $request,
            'payment.proof_uploaded',
            $payment,
            $oldValues,
            $payment->toArray()
        );

        return redirect()
            ->route('ui.payments.index', $request->only('invoice'))
            ->with('success', 'Bukti pembayaran berhasil diupload dan menunggu approval finance.');
    }

    public function approvePaymentProof(Request $request, Payment $payment): RedirectResponse
    {
        $payment->loadMissing('invoice');

        if ($payment->status !== PaymentStatus::SUBMITTED) {
            return redirect()
                ->route('ui.payments.index', $request->only('invoice'))
                ->withErrors(['payment' => 'Payment harus berstatus submitted sebelum di-approve.']);
        }

        $oldPayment = $payment->toArray();
        $invoice = $payment->invoice;
        $oldInvoice = $invoice?->toArray();

        DB::transaction(function () use ($payment, $invoice) {
            $payment->update([
                'status' => PaymentStatus::PAID,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'paid_by' => Auth::id(),
            ]);

            if ($invoice) {
                $invoice->update([
                    'status' => DocumentStatus::PAID,
                ]);
            }
        });

        $this->writeAudit(
            $request,
            'payment.approved_by_finance',
            $payment,
            $oldPayment,
            $payment->toArray()
        );

        if ($invoice) {
            $invoice->refresh();
            $this->writeAudit(
                $request,
                'invoice.marked_paid',
                $invoice,
                $oldInvoice,
                $invoice->toArray()
            );
        }

        return redirect()
            ->route('ui.payments.index', $request->only('invoice'))
            ->with('success', 'Payment berhasil di-approve. Status invoice menjadi paid.');
    }

    public function generateInvoiceFromPurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, [
            'tax_amount',
        ]);

        if (! in_array($purchaseOrder->status?->value, [DocumentStatus::APPROVED->value, DocumentStatus::PROCESSED->value], true)) {
            return redirect()
                ->route('ui.invoices.index', $request->only('vendor'))
                ->withErrors(['invoice' => 'PO harus berstatus approved/processed sebelum dibuatkan invoice.']);
        }

        $validated = $request->validate([
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]);

        $result = DB::transaction(function () use ($purchaseOrder, $validated) {
            $purchaseOrder->loadMissing(['deliveries']);

            $activeDelivery = $purchaseOrder->deliveries()
                ->latest('id')
                ->first();

            if (! $activeDelivery) {
                $activeDelivery = Delivery::query()->create([
                    'number' => $this->generateNumber('DLV'),
                    'purchase_order_id' => $purchaseOrder->id,
                    'goods_receipt_id' => null,
                    'sppg_id' => $purchaseOrder->sppg_id,
                    'vendor_id' => $purchaseOrder->vendor_id,
                    'delivered_by' => Auth::id(),
                    'delivery_date' => $purchaseOrder->order_date ?? now()->toDateString(),
                    'status' => DocumentStatus::DELIVERED,
                    'total_amount' => 0,
                    'invoiced_po_amount' => 0,
                    'notes' => 'Auto-generated from PO for invoicing.',
                ]);
            }

            if ($activeDelivery->status === DocumentStatus::PAID) {
                return ['status' => 'paid'];
            }

            $currentPoTotal = (float) $purchaseOrder->total_amount;
            $alreadyInvoicedAmount = (float) ($activeDelivery->invoiced_po_amount ?? 0);
            $incrementalAmount = $currentPoTotal - $alreadyInvoicedAmount;

            if ($incrementalAmount <= 0) {
                return ['status' => 'no_delta'];
            }

            $activeDelivery->update([
                'status' => DocumentStatus::DELIVERED,
                'total_amount' => $incrementalAmount,
                'delivery_date' => $activeDelivery->delivery_date ?? ($purchaseOrder->order_date ?? now()->toDateString()),
                'notes' => str_contains((string) ($purchaseOrder->notes ?? ''), '[BARANG TAMBAHAN]')
                    ? trim((string) ($activeDelivery->notes ?? '').' [BARANG TAMBAHAN]')
                    : $activeDelivery->notes,
            ]);

            $this->upsertInvoiceFromDelivery(
                $activeDelivery,
                (float) ($validated['tax_amount'] ?? 0),
                $validated['due_date'] ?? null,
            );

            $activeDelivery->update([
                'invoiced_po_amount' => $currentPoTotal,
            ]);

            return ['status' => 'ok', 'incremental' => $incrementalAmount];
        });

        if (($result['status'] ?? null) === 'paid') {
            return redirect()
                ->route('ui.invoices.index', $request->only('vendor'))
                ->withErrors(['invoice' => 'PO ini sudah tertagih dan berstatus paid. Buat dokumen lanjutan terpisah jika perlu.']);
        }

        if (($result['status'] ?? null) === 'no_delta') {
            return redirect()
                ->route('ui.invoices.index', $request->only('vendor'))
                ->with('success', 'Tidak ada nilai tambahan yang perlu ditagihkan pada PO ini.');
        }

        return redirect()
            ->route('ui.invoices.index', $request->only('vendor'))
            ->with('success', 'Invoice PO berhasil diperbarui. Jika ada barang tambahan, tetap memakai nomor invoice yang sama untuk periode berjalan.');
    }

    public function masterSppgs(Request $request): View
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

        $editSppg = null;
        if ($request->filled('edit')) {
            $editSppg = Sppg::query()->find($request->integer('edit'));
        }

        return view('procurement.master-data.sppgs.index', compact('sppgs', 'vendors', 'editSppg'));
    }

    public function storeSppg(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:sppgs,code'],
            'name' => ['required', 'string', 'max:255'],
            'ka_sppg_name' => ['nullable', 'string', 'max:255'],
            'accounting_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'default_vendor_id' => ['nullable', 'exists:vendors,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Sppg::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'ka_sppg_name' => $validated['ka_sppg_name'] ?? null,
            'accounting_name' => $validated['accounting_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'default_vendor_id' => $validated['default_vendor_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('ui.master-data.sppgs.index')
            ->with('success', 'Data SPPG berhasil ditambahkan.');
    }

    public function editSppg(Sppg $sppg): RedirectResponse
    {
        return redirect()->route('ui.master-data.sppgs.index', ['edit' => $sppg->id]);
    }

    public function updateSppg(Request $request, Sppg $sppg): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('sppgs', 'code')->ignore($sppg->id)],
            'name' => ['required', 'string', 'max:255'],
            'ka_sppg_name' => ['nullable', 'string', 'max:255'],
            'accounting_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'default_vendor_id' => ['nullable', 'exists:vendors,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sppg->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'ka_sppg_name' => $validated['ka_sppg_name'] ?? null,
            'accounting_name' => $validated['accounting_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'default_vendor_id' => $validated['default_vendor_id'] ?? null,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()
            ->route('ui.master-data.sppgs.index')
            ->with('success', 'Data SPPG berhasil diperbarui.');
    }

    public function destroySppg(Sppg $sppg): RedirectResponse
    {
        try {
            $sppg->delete();
        } catch (QueryException) {
            return redirect()
                ->route('ui.master-data.sppgs.index')
                ->withErrors(['delete_sppg' => 'SPPG tidak dapat dihapus karena masih dipakai pada transaksi.']);
        }

        return redirect()
            ->route('ui.master-data.sppgs.index')
            ->with('success', 'Data SPPG berhasil dihapus.');
    }

    public function masterVendors(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $vendors = Vendor::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('owner_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $editVendor = null;
        if ($request->filled('edit')) {
            $editVendor = Vendor::query()->find($request->integer('edit'));
        }

        return view('procurement.master-data.vendors.index', compact('vendors', 'editVendor', 'search'));
    }

    public function storeVendor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:vendors,code'],
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_affiliate' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Vendor::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'owner_name' => $validated['owner_name'] ?? null,
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

    public function editVendor(Vendor $vendor): RedirectResponse
    {
        return redirect()->route('ui.master-data.vendors.index', ['edit' => $vendor->id]);
    }

    public function updateVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('vendors', 'code')->ignore($vendor->id)],
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_affiliate' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $vendor->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'owner_name' => $validated['owner_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_affiliate' => $request->boolean('is_affiliate', false),
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()
            ->route('ui.master-data.vendors.index')
            ->with('success', 'Data vendor berhasil diperbarui.');
    }

    public function destroyVendor(Vendor $vendor): RedirectResponse
    {
        try {
            $vendor->delete();
        } catch (QueryException) {
            return redirect()
                ->route('ui.master-data.vendors.index')
                ->withErrors(['delete_vendor' => 'Vendor tidak dapat dihapus karena masih dipakai pada transaksi.']);
        }

        return redirect()
            ->route('ui.master-data.vendors.index')
            ->with('success', 'Data vendor berhasil dihapus.');
    }

    public function masterProducts(Request $request): View
    {
        $scope = (string) $request->input('scope', 'all');
        if (! in_array($scope, ['all', 'catalog', 'ad_hoc'], true)) {
            $scope = 'all';
        }

        $keyword = trim((string) $request->input('q', ''));

        $productsBaseQuery = $this->buildMasterProductsQuery($scope, $keyword);

        $products = (clone $productsBaseQuery)
            ->orderByDesc('is_active')
            ->orderByDesc('is_ad_hoc')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $allFilteredProducts = (clone $productsBaseQuery)->get([
            'id',
            'purchase_price',
            'selling_price',
            'government_price_cap',
            'price_variance_percent',
            'price_variance_amount',
            'minimum_stock_level',
        ]);

        $inventoryQtyByProduct = $this->getInventoryQuantityByProductIds($allFilteredProducts->pluck('id')->all());
        $inventoryValueByProduct = [];
        $totalAssetValue = 0.0;

        foreach ($allFilteredProducts as $filteredProduct) {
            $productId = (int) $filteredProduct->id;
            $qty = $this->resolveProductAssetQuantity(
                $filteredProduct,
                (float) ($inventoryQtyByProduct[$productId] ?? 0)
            );
            $unitPrice = $this->resolveProductReferencePrice($filteredProduct);
            $value = $qty * $unitPrice;

            $inventoryValueByProduct[$productId] = $value;
            $totalAssetValue += $value;
        }

        $adHocProducts = Product::query()
            ->with(['category', 'vendor'])
            ->where('is_ad_hoc', true)
            ->latest('id')
            ->limit(100)
            ->get();

        $categories = ProductCategory::query()
            ->orderBy('name')
            ->get();

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $editProduct = null;
        if ($request->filled('edit')) {
            $editProduct = Product::query()->find($request->integer('edit'));
        }

        $editCategory = null;
        if ($request->filled('category_edit')) {
            $editCategory = ProductCategory::query()->find($request->integer('category_edit'));
        }

        return view('procurement.master-data.products.index', compact('products', 'categories', 'vendors', 'adHocProducts', 'editProduct', 'editCategory', 'scope', 'keyword', 'inventoryQtyByProduct', 'inventoryValueByProduct', 'totalAssetValue'));
    }

    private function buildMasterProductsQuery(string $scope, string $keyword)
    {
        return Product::query()
            ->with(['category', 'vendor'])
            ->when($scope === 'catalog', fn ($query) => $query->where('is_ad_hoc', false))
            ->when($scope === 'ad_hoc', fn ($query) => $query->where('is_ad_hoc', true))
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('sku', 'like', '%'.$keyword.'%')
                        ->orWhere('name', 'like', '%'.$keyword.'%')
                        ->orWhere('unit', 'like', '%'.$keyword.'%')
                        ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('name', 'like', '%'.$keyword.'%'));
                });
            });
    }

    private function getInventoryQuantityByProductIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return StockMovement::query()
            ->selectRaw(
                "product_id, SUM(CASE WHEN type = ? THEN quantity WHEN type = ? THEN -quantity ELSE quantity END) as total_quantity",
                [StockMovementType::IN->value, StockMovementType::OUT->value]
            )
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->pluck('total_quantity', 'product_id')
            ->map(fn ($qty) => (float) $qty)
            ->all();
    }

    private function resolveProductReferencePrice(Product $product): float
    {
        $basePrice = $this->resolveProductVarianceBasePrice($product) ?? 0.0;
        $varianceAmount = (float) ($product->price_variance_amount ?? 0);
        $variancePercent = (float) ($product->price_variance_percent ?? 0);

        return $basePrice + $varianceAmount + ($basePrice * $variancePercent / 100);
    }

    private function resolveProductAssetQuantity(Product $product, float $inventoryQty): float
    {
        if ($inventoryQty > 0) {
            return $inventoryQty;
        }

        // Fallback to minimum stock planning so asset value remains representative
        // even before stock movements are recorded.
        return max(0.0, (float) ($product->minimum_stock_level ?? 0));
    }

    private function normalizeLocalizedNumericInputs(Request $request, array $fields): void
    {
        $normalized = [];
        foreach ($fields as $field) {
            if (! $request->exists($field)) {
                continue;
            }

            $normalized[$field] = $this->parseLocalizedNumberValue($request->input($field));
        }

        if ($normalized !== []) {
            $request->merge($normalized);
        }
    }

    private function parseLocalizedNumberValue(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        $hasDot = str_contains($normalized, '.');
        $hasComma = str_contains($normalized, ',');

        if ($hasDot && $hasComma) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif ($hasDot && preg_match('/^-?\d{1,3}(\.\d{3})+$/', $normalized) === 1) {
            $normalized = str_replace('.', '', $normalized);
        } elseif ($hasComma) {
            $normalized = str_replace(',', '.', $normalized);
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '';
        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === '-.') {
            return null;
        }

        if (substr_count($normalized, '.') > 1) {
            $parts = explode('.', $normalized);
            $decimalPart = array_pop($parts);
            $normalized = implode('', $parts).'.'.$decimalPart;
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function resolveProductVarianceBasePrice(Product $product): ?float
    {
        if ($product->selling_price !== null) {
            return (float) $product->selling_price;
        }

        if ($product->government_price_cap !== null) {
            return (float) $product->government_price_cap;
        }

        return null;
    }

    public function promoteAdHocProduct(Request $request, Product $product): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, [
            'purchase_price',
            'selling_price',
            'government_price_cap',
        ]);

        if (! $product->is_ad_hoc) {
            return redirect()
                ->route('ui.master-data.products.index')
                ->withErrors(['promote_product' => 'Produk ini bukan item non katalog.']);
        }

        $validated = $request->validate([
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'government_price_cap' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $product->update([
            'product_category_id' => (int) $validated['product_category_id'],
            'vendor_id' => isset($validated['vendor_id']) && $validated['vendor_id'] !== '' ? (int) $validated['vendor_id'] : null,
            'purchase_price' => $validated['purchase_price'] ?? $product->purchase_price,
            'selling_price' => $validated['selling_price'] ?? $product->selling_price,
            'government_price_cap' => $validated['government_price_cap'] ?? $product->government_price_cap,
            'is_ad_hoc' => false,
            'is_active' => true,
        ]);

        return redirect()
            ->route('ui.master-data.products.index')
            ->with('success', 'Produk non katalog berhasil dipromosikan ke katalog permanen.');
    }

    public function storeProductCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:product_categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        ProductCategory::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('ui.master-data.products.index')
            ->with('success', 'Kategori produk berhasil ditambahkan.');
    }

    public function editProductCategory(ProductCategory $productCategory): RedirectResponse
    {
        return redirect()->route('ui.master-data.products.index', ['category_edit' => $productCategory->id]);
    }

    public function updateProductCategory(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_categories', 'name')->ignore($productCategory->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $productCategory->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('ui.master-data.products.index')
            ->with('success', 'Kategori produk berhasil diperbarui.');
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, [
            'purchase_price',
            'selling_price',
            'government_price_cap',
            'price_variance_amount',
        ]);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'unit' => ['required', 'string', 'max:30'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'government_price_cap' => ['nullable', 'numeric', 'min:0'],
            'price_variance_percent' => ['nullable', 'numeric'],
            'price_variance_amount' => ['nullable', 'numeric'],
            'minimum_stock_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_stock_level' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Product::query()->create([
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'product_category_id' => $validated['product_category_id'],
            'vendor_id' => $validated['vendor_id'],
            'unit' => $validated['unit'],
            'purchase_price' => $validated['purchase_price'] ?? null,
            'selling_price' => $validated['selling_price'] ?? null,
            'government_price_cap' => $validated['government_price_cap'] ?? null,
            'price_variance_percent' => $validated['price_variance_percent'] ?? null,
            'price_variance_amount' => $validated['price_variance_amount'] ?? null,
            'minimum_stock_level' => $validated['minimum_stock_level'] ?? 0,
            'reorder_stock_level' => $validated['reorder_stock_level'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('ui.master-data.products.index')
            ->with('success', 'Data produk berhasil ditambahkan.');
    }

    public function editProduct(Product $product): RedirectResponse
    {
        return redirect()->route('ui.master-data.products.index', ['edit' => $product->id]);
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, [
            'purchase_price',
            'selling_price',
            'government_price_cap',
            'price_variance_amount',
        ]);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'unit' => ['required', 'string', 'max:30'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'government_price_cap' => ['nullable', 'numeric', 'min:0'],
            'price_variance_percent' => ['nullable', 'numeric'],
            'price_variance_amount' => ['nullable', 'numeric'],
            'minimum_stock_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_stock_level' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'product_category_id' => $validated['product_category_id'],
            'vendor_id' => $validated['vendor_id'],
            'unit' => $validated['unit'],
            'purchase_price' => $validated['purchase_price'] ?? null,
            'selling_price' => $validated['selling_price'] ?? null,
            'government_price_cap' => $validated['government_price_cap'] ?? null,
            'price_variance_percent' => $validated['price_variance_percent'] ?? null,
            'price_variance_amount' => $validated['price_variance_amount'] ?? null,
            'minimum_stock_level' => $validated['minimum_stock_level'] ?? 0,
            'reorder_stock_level' => $validated['reorder_stock_level'] ?? 0,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()
            ->route('ui.master-data.products.index')
            ->with('success', 'Data produk berhasil diperbarui.');
    }

    public function destroyProduct(Product $product): RedirectResponse
    {
        try {
            $product->delete();
        } catch (QueryException) {
            return redirect()
                ->route('ui.master-data.products.index')
                ->withErrors(['delete_product' => 'Produk tidak dapat dihapus karena masih dipakai pada transaksi.']);
        }

        return redirect()
            ->route('ui.master-data.products.index')
            ->with('success', 'Data produk berhasil dihapus.');
    }

    public function exportProductsExcel(): Response
    {
        $products = Product::query()
            ->with(['category', 'vendor'])
            ->orderBy('sku')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'sku',
            'name',
            'category',
            'vendor_code',
            'vendor',
            'unit',
            'purchase_price',
            'selling_price',
            'government_price_cap',
            'price_variance_percent',
            'price_variance_amount',
            'minimum_stock_level',
            'reorder_stock_level',
            'is_active',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            $sheet->fromArray([
                $product->sku,
                $product->name,
                $product->category?->name,
                $product->vendor?->code,
                $product->vendor?->name,
                $product->unit,
                (float) ($product->purchase_price ?? 0),
                (float) ($product->selling_price ?? 0),
                (float) ($product->government_price_cap ?? 0),
                (float) ($product->price_variance_percent ?? 0),
                (float) ($product->price_variance_amount ?? 0),
                (float) ($product->minimum_stock_level ?? 0),
                (float) ($product->reorder_stock_level ?? 0),
                $product->is_active ? 1 : 0,
            ], null, 'A'.$row);

            $row++;
        }

        $filename = 'master-products-'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportProductsPdf(Request $request): Response
    {
        $scope = (string) $request->input('scope', 'all');
        if (! in_array($scope, ['all', 'catalog', 'ad_hoc'], true)) {
            $scope = 'all';
        }

        $keyword = trim((string) $request->input('q', ''));

        $products = $this->buildMasterProductsQuery($scope, $keyword)
            ->orderBy('name')
            ->get();

        $inventoryQtyByProduct = $this->getInventoryQuantityByProductIds($products->pluck('id')->all());
        $inventoryValueByProduct = [];
        $totalAssetValue = 0.0;

        foreach ($products as $product) {
            $productId = (int) $product->id;
            $qty = $this->resolveProductAssetQuantity(
                $product,
                (float) ($inventoryQtyByProduct[$productId] ?? 0)
            );
            $value = $qty * $this->resolveProductReferencePrice($product);
            $inventoryValueByProduct[$productId] = $value;
            $totalAssetValue += $value;
        }

        $pdf = Pdf::loadView('procurement.master-data.products.pdf', [
            'products' => $products,
            'inventoryQtyByProduct' => $inventoryQtyByProduct,
            'inventoryValueByProduct' => $inventoryValueByProduct,
            'totalAssetValue' => $totalAssetValue,
            'scope' => $scope,
            'keyword' => $keyword,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $filename = 'master-products-'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }

    public function importProductsExcel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $file = $validated['excel_file'];
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Throwable) {
            return redirect()
                ->route('ui.master-data.products.index')
                ->withErrors(['excel_file' => 'File Excel tidak dapat dibaca.']);
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        if (! is_array($rows) || count($rows) < 1) {
            return redirect()
                ->route('ui.master-data.products.index')
                ->withErrors(['excel_file' => 'File Excel kosong atau tidak valid.']);
        }

        $header = $rows[0] ?? null;
        if (! is_array($header) || $header === []) {
            return redirect()
                ->route('ui.master-data.products.index')
                ->withErrors(['excel_file' => 'Header Excel tidak valid atau kosong.']);
        }

        $normalizedHeaders = array_map(static fn ($item) => strtolower(trim((string) $item)), $header);

        $requiredHeaders = ['sku', 'name', 'unit'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (! in_array($requiredHeader, $normalizedHeaders, true)) {
                return redirect()
                    ->route('ui.master-data.products.index')
                    ->withErrors(['excel_file' => 'Header `'.$requiredHeader.'` wajib ada di Excel.']);
            }
        }

        if (! in_array('category', $normalizedHeaders, true) && ! in_array('category_name', $normalizedHeaders, true)) {
            return redirect()
                ->route('ui.master-data.products.index')
                ->withErrors(['excel_file' => 'Header `category` atau `category_name` wajib ada di Excel.']);
        }

        if (! in_array('vendor', $normalizedHeaders, true) && ! in_array('vendor_code', $normalizedHeaders, true)) {
            return redirect()
                ->route('ui.master-data.products.index')
                ->withErrors(['excel_file' => 'Header `vendor` atau `vendor_code` wajib ada di Excel.']);
        }

        $toNullableNumber = fn ($value): ?float => $this->parseLocalizedNumberValue($value);

        $toBoolean = static function ($value, bool $default = true): bool {
            $normalized = strtolower(trim((string) $value));
            if ($normalized === '') {
                return $default;
            }

            return in_array($normalized, ['1', 'true', 'yes', 'aktif', 'active'], true);
        };

        $created = 0;
        $updated = 0;
        $skipped = 0;

        for ($r = 1; $r < count($rows); $r++) {
            $row = $rows[$r] ?? [];
            if (! is_array($row) || $row === []) {
                $skipped++;
                continue;
            }

            $data = [];
            foreach ($normalizedHeaders as $index => $column) {
                $data[$column] = trim((string) ($row[$index] ?? ''));
            }

            $sku = $data['sku'] ?? '';
            $name = $data['name'] ?? '';
            $unit = $data['unit'] ?? '';
            $categoryName = $data['category'] ?? ($data['category_name'] ?? '');
            $vendorCode = $data['vendor_code'] ?? '';
            $vendorName = $data['vendor'] ?? '';

            if ($sku === '' || $name === '' || $unit === '' || $categoryName === '') {
                $skipped++;
                continue;
            }

            $category = ProductCategory::query()->firstOrCreate(
                ['name' => $categoryName],
                ['description' => null]
            );

            $vendor = null;
            if ($vendorCode !== '') {
                $vendor = Vendor::query()->where('code', $vendorCode)->first();
            }

            if (! $vendor && $vendorName !== '') {
                $vendor = Vendor::query()->where('name', $vendorName)->first();
            }

            if (! $vendor) {
                $skipped++;
                continue;
            }

            $payload = [
                'name' => $name,
                'product_category_id' => $category->id,
                'vendor_id' => $vendor->id,
                'unit' => $unit,
                'purchase_price' => $toNullableNumber($data['purchase_price'] ?? ($data['harga_beli'] ?? null)),
                'selling_price' => $toNullableNumber($data['selling_price'] ?? ($data['harga_jual'] ?? null)),
                'government_price_cap' => $toNullableNumber($data['government_price_cap'] ?? null),
                'price_variance_percent' => $toNullableNumber($data['price_variance_percent'] ?? null),
                'price_variance_amount' => $toNullableNumber($data['price_variance_amount'] ?? null),
                'minimum_stock_level' => $toNullableNumber($data['minimum_stock_level'] ?? null) ?? 0,
                'reorder_stock_level' => $toNullableNumber($data['reorder_stock_level'] ?? null) ?? 0,
                'is_active' => $toBoolean($data['is_active'] ?? null, true),
            ];

            $exists = Product::query()->where('sku', $sku)->exists();
            Product::query()->updateOrCreate(['sku' => $sku], $payload);

            if ($exists) {
                $updated++;
            } else {
                $created++;
            }
        }

        return redirect()
            ->route('ui.master-data.products.index')
            ->with('success', "Import Excel produk selesai. Create: {$created}, Update: {$updated}, Skip: {$skipped}.");
    }

    public function priceHistories(Request $request): View
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

        $editPriceHistory = null;
        if ($request->filled('edit')) {
            $editPriceHistory = ProductPriceHistory::query()->find($request->integer('edit'));
        }

        return view('procurement.master-data.price-histories.index', compact('priceHistories', 'products', 'vendors', 'editPriceHistory'));
    }

    public function storePriceHistory(Request $request): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, [
            'price',
        ]);

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'price' => ['nullable', 'numeric', 'min:0', 'required_without:margin_percent'],
            'margin_percent' => ['nullable', 'numeric', 'required_without:price'],
            'effective_at' => ['required', 'date'],
        ]);

        $product = Product::query()->findOrFail((int) $validated['product_id']);
        $marginPercent = $request->filled('margin_percent') ? (float) $validated['margin_percent'] : null;
        $basePriceForMargin = $this->resolveProductVarianceBasePrice($product);

        if ($marginPercent !== null && $basePriceForMargin === null) {
            return redirect()
                ->route('ui.master-data.price-histories.index')
                ->withErrors(['margin_percent' => 'Produk ini belum memiliki Harga Jual, jadi margin persentase belum bisa dihitung.'])
                ->withInput();
        }

        $resolvedPrice = $marginPercent !== null
            ? (float) $basePriceForMargin * (1 + ($marginPercent / 100))
            : (float) $validated['price'];

        if ($resolvedPrice < 0) {
            return redirect()
                ->route('ui.master-data.price-histories.index')
                ->withErrors(['price' => 'Harga hasil perhitungan margin tidak boleh negatif.'])
                ->withInput();
        }

        ProductPriceHistory::query()->create([
            'product_id' => $validated['product_id'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'price' => $resolvedPrice,
            'effective_at' => $validated['effective_at'],
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('ui.master-data.price-histories.index')
            ->with('success', 'Riwayat harga berhasil ditambahkan.');
    }

    public function editPriceHistory(ProductPriceHistory $productPriceHistory): RedirectResponse
    {
        return redirect()->route('ui.master-data.price-histories.index', ['edit' => $productPriceHistory->id]);
    }

    public function updatePriceHistory(Request $request, ProductPriceHistory $productPriceHistory): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, [
            'price',
        ]);

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'price' => ['nullable', 'numeric', 'min:0', 'required_without:margin_percent'],
            'margin_percent' => ['nullable', 'numeric', 'required_without:price'],
            'effective_at' => ['required', 'date'],
        ]);

        $product = Product::query()->findOrFail((int) $validated['product_id']);
        $marginPercent = $request->filled('margin_percent') ? (float) $validated['margin_percent'] : null;
        $basePriceForMargin = $this->resolveProductVarianceBasePrice($product);

        if ($marginPercent !== null && $basePriceForMargin === null) {
            return redirect()
                ->route('ui.master-data.price-histories.index', ['edit' => $productPriceHistory->id])
                ->withErrors(['margin_percent' => 'Produk ini belum memiliki Harga Jual, jadi margin persentase belum bisa dihitung.'])
                ->withInput();
        }

        $resolvedPrice = $marginPercent !== null
            ? (float) $basePriceForMargin * (1 + ($marginPercent / 100))
            : (float) $validated['price'];

        if ($resolvedPrice < 0) {
            return redirect()
                ->route('ui.master-data.price-histories.index', ['edit' => $productPriceHistory->id])
                ->withErrors(['price' => 'Harga hasil perhitungan margin tidak boleh negatif.'])
                ->withInput();
        }

        $productPriceHistory->update([
            'product_id' => $validated['product_id'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'price' => $resolvedPrice,
            'effective_at' => $validated['effective_at'],
        ]);

        return redirect()
            ->route('ui.master-data.price-histories.index')
            ->with('success', 'Riwayat harga berhasil diperbarui.');
    }

    public function destroyPriceHistory(ProductPriceHistory $productPriceHistory): RedirectResponse
    {
        $productPriceHistory->delete();

        return redirect()
            ->route('ui.master-data.price-histories.index')
            ->with('success', 'Riwayat harga berhasil dihapus.');
    }

    public function approvalQueue(): View
    {
        $approvals = Approval::query()
            ->with(['approver', 'approvable'])
            ->orderByRaw("CASE WHEN approved_at IS NULL THEN 0 ELSE 1 END")
            ->latest('created_at')
            ->paginate(20);

        $poOwnerApprovalThreshold = $this->getPoOwnerApprovalThreshold();

        return view('procurement.approvals.index', compact('approvals', 'poOwnerApprovalThreshold'));
    }

    public function updatePoOwnerApprovalThreshold(Request $request): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, [
            'po_owner_approval_threshold',
        ]);

        $validated = $request->validate([
            'po_owner_approval_threshold' => ['required', 'numeric', 'min:0'],
        ]);

        AppSetting::query()->updateOrCreate(
            ['key' => 'po_owner_approval_threshold'],
            ['value' => (string) (float) $validated['po_owner_approval_threshold']]
        );

        return redirect()
            ->route('ui.approvals.index')
            ->with('success', 'Threshold approval PO berhasil diperbarui.');
    }

    public function approveQueueItem(Request $request, Approval $approval): RedirectResponse
    {
        if ($approval->approved_at !== null) {
            return redirect()
                ->route('ui.approvals.index')
                ->withErrors(['approval' => 'Approval ini sudah diproses sebelumnya.']);
        }

        $approval->loadMissing('approvable');
        $oldApproval = $approval->toArray();
        $oldApprovable = $approval->approvable?->toArray();

        DB::transaction(function () use ($request, $approval) {
            $approval->update([
                'status' => DocumentStatus::APPROVED,
                'approved_at' => now(),
                'approver_id' => Auth::id() ?? $approval->approver_id,
                'note' => $approval->note ?: 'Approved from approval queue',
            ]);

            $approvable = $approval->approvable;
            if ($approvable && $this->hasStatusAttribute($approvable)) {
                $approvable->update([
                    'status' => DocumentStatus::APPROVED,
                ]);
            }

            $this->writeAudit(
                $request,
                'approval.queue_approved',
                $approval,
                null,
                $approval->toArray()
            );
        });

        $approval->refresh();
        if ($approval->approvable && $this->hasStatusAttribute($approval->approvable)) {
            $approval->approvable->refresh();
            $this->writeAudit(
                $request,
                'approval.document_approved',
                $approval->approvable,
                $oldApprovable,
                $approval->approvable->toArray()
            );
        }

        $this->writeAudit(
            $request,
            'approval.queue_approved_meta',
            $approval,
            $oldApproval,
            $approval->toArray()
        );

        return redirect()
            ->route('ui.approvals.index')
            ->with('success', 'Approval berhasil diproses (approved).');
    }

    public function rejectQueueItem(Request $request, Approval $approval): RedirectResponse
    {
        if ($approval->approved_at !== null) {
            return redirect()
                ->route('ui.approvals.index')
                ->withErrors(['approval' => 'Approval ini sudah diproses sebelumnya.']);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $approval->loadMissing('approvable');
        $oldApproval = $approval->toArray();
        $oldApprovable = $approval->approvable?->toArray();

        DB::transaction(function () use ($request, $approval, $validated) {
            $approval->update([
                'status' => DocumentStatus::REJECTED,
                'approved_at' => now(),
                'approver_id' => Auth::id() ?? $approval->approver_id,
                'note' => $validated['note'] ?? ($approval->note ?: 'Rejected from approval queue'),
            ]);

            $approvable = $approval->approvable;
            if ($approvable && $this->hasStatusAttribute($approvable)) {
                $approvable->update([
                    'status' => DocumentStatus::REJECTED,
                ]);
            }

            $this->writeAudit(
                $request,
                'approval.queue_rejected',
                $approval,
                null,
                $approval->toArray()
            );
        });

        $approval->refresh();
        if ($approval->approvable && $this->hasStatusAttribute($approval->approvable)) {
            $approval->approvable->refresh();
            $this->writeAudit(
                $request,
                'approval.document_rejected',
                $approval->approvable,
                $oldApprovable,
                $approval->approvable->toArray()
            );
        }

        $this->writeAudit(
            $request,
            'approval.queue_rejected_meta',
            $approval,
            $oldApproval,
            $approval->toArray()
        );

        return redirect()
            ->route('ui.approvals.index')
            ->with('success', 'Approval berhasil diproses (rejected).');
    }

    public function stockMovements(): View
    {
        $stockMovements = StockMovement::query()
            ->with(['warehouse', 'product.vendor', 'creator'])
            ->latest('movement_date')
            ->paginate(20);

        return view('procurement.inventory.stock-movements.index', compact('stockMovements'));
    }

    public function stockAlerts(): View
    {
        $stockAlerts = StockAlert::query()
            ->with(['warehouse', 'product.vendor', 'resolver'])
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

        $poOwnerApprovalThreshold = $this->getPoOwnerApprovalThreshold();
        $pendingPoOwnerApprovals = Approval::query()
            ->where('approvable_type', PurchaseOrder::class)
            ->whereNull('approved_at')
            ->count();

        return view('procurement.finance.billing-cycles.index', compact(
            'billingCycles',
            'poOwnerApprovalThreshold',
            'pendingPoOwnerApprovals'
        ));
    }

    public function kwitansi(Request $request): View
    {
        $selectedVendorId = $request->filled('vendor') ? (int) $request->integer('vendor') : null;

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $kwitansis = Kwitansi::query()
            ->with(['vendor', 'creator', 'invoices'])
            ->when($selectedVendorId, fn ($query) => $query->where('vendor_id', $selectedVendorId))
            ->latest('receipt_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $availableInvoices = Invoice::query()
            ->with(['sppg', 'vendor'])
            ->where('status', '!=', DocumentStatus::PAID->value)
            ->when($selectedVendorId, fn ($query) => $query->where('vendor_id', $selectedVendorId))
            ->whereDoesntHave('kwitansis')
            ->orderBy('invoice_date')
            ->orderBy('number')
            ->get();

        $selectedVendor = $selectedVendorId
            ? $vendors->firstWhere('id', $selectedVendorId)
            : null;

        return view('procurement.finance.kwitansi.index', compact(
            'kwitansis',
            'vendors',
            'availableInvoices',
            'selectedVendor'
        ));
    }

    public function storeKwitansi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'receipt_date' => ['required', 'date'],
            'billed_to' => ['required', 'string', 'max:255'],
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer', 'distinct', 'exists:invoices,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoiceIds = collect($validated['invoice_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $invoices = Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->where('status', '!=', DocumentStatus::PAID->value)
            ->whereDoesntHave('kwitansis')
            ->get();

        if ($invoices->count() !== $invoiceIds->count()) {
            return redirect()
                ->route('ui.kwitansi.index', $request->only('vendor'))
                ->withErrors(['invoice_ids' => 'Sebagian invoice tidak valid, sudah dibayar, atau sudah masuk kwitansi lain.'])
                ->withInput();
        }

        if ($invoices->pluck('vendor_id')->unique()->count() !== 1 || (int) $invoices->first()->vendor_id !== (int) $validated['vendor_id']) {
            return redirect()
                ->route('ui.kwitansi.index', ['vendor' => $validated['vendor_id']])
                ->withErrors(['invoice_ids' => 'Semua invoice pada satu kwitansi harus dari vendor yang sama.'])
                ->withInput();
        }

        $totalAmount = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->total_amount);

        $kwitansi = DB::transaction(function () use ($validated, $invoices, $totalAmount) {
            $kwitansi = Kwitansi::query()->create([
                'number' => $this->generateNumber('KWT'),
                'vendor_id' => (int) $validated['vendor_id'],
                'billed_to' => $validated['billed_to'],
                'receipt_date' => $validated['receipt_date'],
                'total_amount' => $totalAmount,
                'created_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $attachPayload = [];
            foreach ($invoices as $invoice) {
                $attachPayload[(int) $invoice->id] = [
                    'billed_amount' => (float) $invoice->total_amount,
                ];
            }

            $kwitansi->invoices()->attach($attachPayload);

            return $kwitansi;
        });

        $kwitansi->loadMissing('invoices');
        $this->writeAudit(
            $request,
            'kwitansi.created',
            $kwitansi,
            null,
            [
                'number' => $kwitansi->number,
                'vendor_id' => $kwitansi->vendor_id,
                'receipt_date' => (string) $kwitansi->receipt_date,
                'invoice_ids' => $kwitansi->invoices->pluck('id')->all(),
                'total_amount' => (float) $kwitansi->total_amount,
            ]
        );

        return redirect()
            ->route('ui.kwitansi.index', ['vendor' => $validated['vendor_id']])
            ->with('success', 'Kwitansi berhasil dibuat dari '.count($validated['invoice_ids']).' invoice.');
    }

    public function downloadKwitansiPdf(Kwitansi $kwitansi): Response
    {
        $kwitansi->loadMissing(['vendor', 'creator', 'invoices.sppg']);
        [$vendorLogo, $hasCustomVendorLogo] = $this->resolveVendorLogoDataUri($kwitansi->vendor, true);
        $ownerName = $kwitansi->vendor?->owner_name;
        if (! $ownerName) {
            $ownerName = User::query()
                ->where('role', UserRole::OWNER->value)
                ->value('name');
        }

        $rows = $kwitansi->invoices
            ->sortBy('invoice_date')
            ->values()
            ->map(function (Invoice $invoice, int $index) {
                return [
                    'no' => $index + 1,
                    'invoice_number' => $invoice->number,
                    'sppg_name' => $invoice->sppg?->name ?? '-',
                    'invoice_date' => $invoice->invoice_date,
                    'amount' => (float) ($invoice->pivot->billed_amount ?? $invoice->total_amount),
                ];
            });

        $firstSppgName = $kwitansi->invoices->first()?->sppg?->name;

        $pdf = Pdf::loadView('procurement.finance.kwitansi.pdf', [
            'kwitansi' => $kwitansi,
            'vendor' => $kwitansi->vendor,
            'rows' => $rows,
            'receivedFrom' => $kwitansi->billed_to,
            'firstSppgName' => $firstSppgName,
            'terbilang' => $this->terbilangRupiah((float) $kwitansi->total_amount),
            'logoVendor' => $vendorLogo,
            'hasCustomVendorLogo' => $hasCustomVendorLogo,
            'ownerName' => $ownerName,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($kwitansi->number.'.pdf');
    }

    public function payments(Request $request): View
    {
        $sppgScopeId = $this->currentSppgScopeId();
        $currentRole = Auth::user()?->role?->value;

        $selectedInvoice = null;
        if ($request->filled('invoice')) {
            $selectedInvoice = Invoice::query()
                ->select(['id', 'number'])
                ->when($sppgScopeId !== null, fn ($query) => $query->where('sppg_id', $this->scopedId($sppgScopeId)))
                ->find((int) $request->integer('invoice'));
        }

        $payments = Payment::query()
            ->with(['invoice:id,number,sppg_id', 'invoice.sppg:id,name', 'payer:id,name', 'proofUploader:id,name', 'approver:id,name'])
            ->when($request->filled('invoice') && $selectedInvoice, fn ($query) => $query->where('invoice_id', (int) $selectedInvoice->id))
            ->when($sppgScopeId !== null, function ($query) use ($sppgScopeId) {
                $query->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('sppg_id', $this->scopedId($sppgScopeId)));
            })
            ->latest('payment_date')
            ->paginate(20)
            ->withQueryString();

        $canUploadProof = $currentRole === UserRole::SPPG_USER->value;
        $canApproveProof = in_array($currentRole, [UserRole::SUPER_ADMIN->value, UserRole::FINANCE->value], true);

        return view('procurement.finance.payments.index', compact('payments', 'selectedInvoice', 'canUploadProof', 'canApproveProof'));
    }

    public function purchaseFundingRequests(Request $request): View
    {
        $allowedStatuses = FundingRequestStatus::values();
        $allowedFundSources = ['petty_cash', 'bank_transfer', 'budget_operasional'];

        $selectedStatus = $request->filled('status') && in_array((string) $request->string('status'), $allowedStatuses, true)
            ? (string) $request->string('status')
            : null;

        $selectedFundSource = $request->filled('fund_source') && in_array((string) $request->string('fund_source'), $allowedFundSources, true)
            ? (string) $request->string('fund_source')
            : null;

        $fundingRequests = PurchaseFundingRequest::query()
            ->with([
                'purchaseOrder:id,number,status',
                'vendor:id,name',
                'sppg:id,name',
                'submitter:id,name',
                'reviewer:id,name',
                'approver:id,name',
            ])
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->when($selectedFundSource, fn ($query) => $query->where('fund_source', $selectedFundSource))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $statsQuery = PurchaseFundingRequest::query()
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->when($selectedFundSource, fn ($query) => $query->where('fund_source', $selectedFundSource));

        $totalRequested = (float) (clone $statsQuery)->sum('requested_amount');
        $totalApproved = (float) (clone $statsQuery)->sum('approved_amount');
        $totalDisbursed = (float) (clone $statsQuery)->sum('disbursed_amount');
        $totalSpent = (float) (clone $statsQuery)->sum('spent_amount');

        $fundingStats = [
            'requested' => $totalRequested,
            'approved' => $totalApproved,
            'disbursed' => $totalDisbursed,
            'spent' => $totalSpent,
            'remaining' => max($totalDisbursed - $totalSpent, 0),
        ];

        $purchaseOrderOptions = PurchaseOrder::query()
            ->select(['id', 'number', 'vendor_id', 'sppg_id', 'status', 'total_amount', 'order_date'])
            ->with(['vendor:id,name', 'sppg:id,name'])
            ->whereIn('status', [
                DocumentStatus::APPROVED->value,
                DocumentStatus::PROCESSED->value,
                DocumentStatus::DELIVERED->value,
                DocumentStatus::INVOICED->value,
            ])
            ->latest('order_date')
            ->limit(100)
            ->get();

        $currentRole = Auth::user()?->role?->value;
        $canManageFunding = in_array($currentRole, [UserRole::SUPER_ADMIN->value, UserRole::FINANCE->value], true);
        $canOwnerApproval = in_array($currentRole, [UserRole::SUPER_ADMIN->value, UserRole::OWNER->value], true);
        $canConfigureFundingThreshold = in_array($currentRole, [UserRole::SUPER_ADMIN->value, UserRole::FINANCE->value], true);
        $fundingOwnerApprovalThreshold = $this->getPurchaseFundingOwnerApprovalThreshold();
        $pendingOwnerFundingApprovals = PurchaseFundingRequest::query()
            ->where('status', FundingRequestStatus::REVIEWED->value)
            ->count();
        $fundSourceLabels = [
            'petty_cash' => 'Petty Cash',
            'bank_transfer' => 'Transfer Bank',
            'budget_operasional' => 'Budget Operasional',
        ];

        return view('procurement.finance.purchase-funding-requests.index', compact(
            'fundingRequests',
            'fundingStats',
            'selectedStatus',
            'selectedFundSource',
            'purchaseOrderOptions',
            'canManageFunding',
            'canOwnerApproval',
            'canConfigureFundingThreshold',
            'fundingOwnerApprovalThreshold',
            'pendingOwnerFundingApprovals',
            'fundSourceLabels'
        ));
    }

    public function exportPurchaseFundingRequestsExcel(Request $request): Response
    {
        $allowedStatuses = FundingRequestStatus::values();
        $allowedFundSources = ['petty_cash', 'bank_transfer', 'budget_operasional'];

        $selectedStatus = $request->filled('status') && in_array((string) $request->string('status'), $allowedStatuses, true)
            ? (string) $request->string('status')
            : null;

        $selectedFundSource = $request->filled('fund_source') && in_array((string) $request->string('fund_source'), $allowedFundSources, true)
            ? (string) $request->string('fund_source')
            : null;

        $fundingRequests = PurchaseFundingRequest::query()
            ->with(['purchaseOrder:id,number', 'vendor:id,name', 'sppg:id,name', 'submitter:id,name', 'reviewer:id,name', 'approver:id,name'])
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->when($selectedFundSource, fn ($query) => $query->where('fund_source', $selectedFundSource))
            ->orderByDesc('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'number',
            'purchase_order',
            'vendor',
            'sppg',
            'fund_source',
            'status',
            'requested_amount',
            'reviewed_amount',
            'approved_amount',
            'disbursed_amount',
            'spent_amount',
            'remaining_amount',
            'submitted_by',
            'reviewed_by',
            'approved_by',
            'created_at',
            'settled_at',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($fundingRequests as $fundingRequest) {
            $remainingAmount = max((float) ($fundingRequest->disbursed_amount ?? 0) - (float) ($fundingRequest->spent_amount ?? 0), 0);

            $sheet->fromArray([
                $fundingRequest->number,
                $fundingRequest->purchaseOrder?->number,
                $fundingRequest->vendor?->name,
                $fundingRequest->sppg?->name,
                $fundingRequest->fund_source,
                $fundingRequest->status?->value,
                (float) ($fundingRequest->requested_amount ?? 0),
                (float) ($fundingRequest->reviewed_amount ?? 0),
                (float) ($fundingRequest->approved_amount ?? 0),
                (float) ($fundingRequest->disbursed_amount ?? 0),
                (float) ($fundingRequest->spent_amount ?? 0),
                $remainingAmount,
                $fundingRequest->submitter?->name,
                $fundingRequest->reviewer?->name,
                $fundingRequest->approver?->name,
                optional($fundingRequest->created_at)->format('Y-m-d H:i:s'),
                optional($fundingRequest->settled_at)->format('Y-m-d H:i:s'),
            ], null, 'A'.$row);

            $row++;
        }

        $filename = 'purchase-funding-requests-'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadPurchaseFundingRequestsPdf(Request $request): Response
    {
        $allowedStatuses = FundingRequestStatus::values();
        $allowedFundSources = ['petty_cash', 'bank_transfer', 'budget_operasional'];

        $selectedStatus = $request->filled('status') && in_array((string) $request->string('status'), $allowedStatuses, true)
            ? (string) $request->string('status')
            : null;

        $selectedFundSource = $request->filled('fund_source') && in_array((string) $request->string('fund_source'), $allowedFundSources, true)
            ? (string) $request->string('fund_source')
            : null;

        $fundingRequests = PurchaseFundingRequest::query()
            ->with(['purchaseOrder:id,number', 'vendor:id,name', 'sppg:id,name'])
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->when($selectedFundSource, fn ($query) => $query->where('fund_source', $selectedFundSource))
            ->orderByDesc('created_at')
            ->get();

        $statsQuery = PurchaseFundingRequest::query()
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->when($selectedFundSource, fn ($query) => $query->where('fund_source', $selectedFundSource));

        $totalRequested = (float) (clone $statsQuery)->sum('requested_amount');
        $totalApproved = (float) (clone $statsQuery)->sum('approved_amount');
        $totalDisbursed = (float) (clone $statsQuery)->sum('disbursed_amount');
        $totalSpent = (float) (clone $statsQuery)->sum('spent_amount');

        $pdf = Pdf::loadView('procurement.finance.purchase-funding-requests.report-pdf', [
            'fundingRequests' => $fundingRequests,
            'selectedStatus' => $selectedStatus,
            'selectedFundSource' => $selectedFundSource,
            'totals' => [
                'requested' => $totalRequested,
                'approved' => $totalApproved,
                'disbursed' => $totalDisbursed,
                'spent' => $totalSpent,
                'remaining' => max($totalDisbursed - $totalSpent, 0),
            ],
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('purchase-funding-requests-'.now()->format('Ymd_His').'.pdf');
    }

    public function markAllNotificationsAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        return redirect()->back()->with('success', 'Semua notifikasi sudah ditandai sebagai dibaca.');
    }

    public function updatePurchaseFundingOwnerApprovalThreshold(Request $request): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, [
            'purchase_funding_owner_approval_threshold',
        ]);

        $validated = $request->validate([
            'purchase_funding_owner_approval_threshold' => ['required', 'numeric', 'min:0'],
        ]);

        AppSetting::query()->updateOrCreate(
            ['key' => 'purchase_funding_owner_approval_threshold'],
            ['value' => (string) (float) $validated['purchase_funding_owner_approval_threshold']]
        );

        return redirect()
            ->route('ui.purchase-funding-requests.index')
            ->with('success', 'Threshold approval owner untuk pengajuan dana berhasil diperbarui.');
    }

    public function storePurchaseFundingRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'fund_source' => ['required', Rule::in(['petty_cash', 'bank_transfer', 'budget_operasional'])],
            'requested_amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchaseOrder = PurchaseOrder::query()
            ->with(['vendor:id,name', 'sppg:id,name'])
            ->findOrFail((int) $validated['purchase_order_id']);

        $fundingRequest = PurchaseFundingRequest::query()->create([
            'number' => $this->generateNumber('FND'),
            'purchase_order_id' => $purchaseOrder->id,
            'title' => trim((string) ($validated['title'] ?? '')) !== ''
                ? $validated['title']
                : 'Pengajuan dana untuk '.$purchaseOrder->number,
            'vendor_id' => $purchaseOrder->vendor_id,
            'sppg_id' => $purchaseOrder->sppg_id,
            'fund_source' => $validated['fund_source'],
            'requested_amount' => (float) $validated['requested_amount'],
            'status' => FundingRequestStatus::SUBMITTED,
            'submitted_by' => Auth::id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->writeAudit($request, 'purchase-funding-request.created', $fundingRequest, null, [
            'number' => $fundingRequest->number,
            'purchase_order_id' => $fundingRequest->purchase_order_id,
            'fund_source' => $fundingRequest->fund_source,
            'requested_amount' => (float) $fundingRequest->requested_amount,
            'status' => $fundingRequest->status?->value,
        ]);

        return redirect()
            ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
            ->with('success', 'Pengajuan dana pembelian berhasil dibuat.');
    }

    public function reviewPurchaseFundingRequest(Request $request, PurchaseFundingRequest $purchaseFundingRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reviewed_amount' => ['required', 'numeric', 'min:1'],
            'finance_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($purchaseFundingRequest->status?->value, [
            FundingRequestStatus::APPROVED->value,
            FundingRequestStatus::REJECTED->value,
            FundingRequestStatus::DISBURSED->value,
            FundingRequestStatus::SETTLED->value,
        ], true)) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Pengajuan dana ini tidak dapat direview lagi.']);
        }

        $reviewedAmount = (float) $validated['reviewed_amount'];
        $ownerThreshold = $this->getPurchaseFundingOwnerApprovalThreshold();
        $requiresOwnerApproval = $reviewedAmount > $ownerThreshold;

        $oldValues = $purchaseFundingRequest->only([
            'status',
            'reviewed_amount',
            'finance_notes',
            'reviewed_by',
            'reviewed_at',
            'approved_amount',
            'approved_by',
            'approved_at',
        ]);

        $updatePayload = [
            'reviewed_amount' => $reviewedAmount,
            'finance_notes' => $validated['finance_notes'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'status' => $requiresOwnerApproval ? FundingRequestStatus::REVIEWED : FundingRequestStatus::APPROVED,
        ];

        if (! $requiresOwnerApproval) {
            $updatePayload['approved_amount'] = $reviewedAmount;
            $updatePayload['approved_by'] = Auth::id();
            $updatePayload['approved_at'] = now();
        }

        $purchaseFundingRequest->update($updatePayload);

        if ($requiresOwnerApproval) {
            $purchaseFundingRequest->loadMissing(['purchaseOrder:id,number', 'vendor:id,name', 'sppg:id,name']);

            $ownerRecipients = User::query()
                ->whereIn('role', [UserRole::OWNER->value, UserRole::SUPER_ADMIN->value])
                ->get();

            if ($ownerRecipients->isNotEmpty()) {
                Notification::send(
                    $ownerRecipients,
                    new PurchaseFundingNeedsOwnerApproval($purchaseFundingRequest, Auth::user())
                );
            }

            $this->writeAudit($request, 'purchase-funding-request.owner_notified', $purchaseFundingRequest, null, [
                'status' => $purchaseFundingRequest->status?->value,
                'recipient_count' => $ownerRecipients->count(),
                'reviewed_amount' => $reviewedAmount,
            ]);
        }

        $this->writeAudit($request, 'purchase-funding-request.reviewed', $purchaseFundingRequest, $oldValues, [
            'status' => $purchaseFundingRequest->status?->value,
            'reviewed_amount' => (float) $purchaseFundingRequest->reviewed_amount,
            'finance_notes' => $purchaseFundingRequest->finance_notes,
            'reviewed_by' => $purchaseFundingRequest->reviewed_by,
            'reviewed_at' => optional($purchaseFundingRequest->reviewed_at)->toDateTimeString(),
            'approved_amount' => (float) ($purchaseFundingRequest->approved_amount ?? 0),
            'approved_by' => $purchaseFundingRequest->approved_by,
            'approved_at' => optional($purchaseFundingRequest->approved_at)->toDateTimeString(),
            'owner_threshold' => $ownerThreshold,
        ]);

        return redirect()
            ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
            ->with('success', $requiresOwnerApproval
                ? 'Review pengajuan dana berhasil disimpan dan menunggu approval owner.'
                : 'Review pengajuan dana berhasil disimpan dan otomatis approved (di bawah threshold owner).');
    }

    public function approvePurchaseFundingRequest(Request $request, PurchaseFundingRequest $purchaseFundingRequest): RedirectResponse
    {
        $validated = $request->validate([
            'approved_amount' => ['nullable', 'numeric', 'min:1'],
            'owner_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($purchaseFundingRequest->status?->value, [
            FundingRequestStatus::APPROVED->value,
            FundingRequestStatus::DISBURSED->value,
            FundingRequestStatus::SETTLED->value,
        ], true)) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Pengajuan dana ini sudah disetujui.']);
        }

        if ($purchaseFundingRequest->status?->value === FundingRequestStatus::REJECTED->value) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Pengajuan dana yang sudah ditolak tidak dapat di-approve.']);
        }

        $approvedAmount = (float) ($validated['approved_amount']
            ?? $purchaseFundingRequest->reviewed_amount
            ?? $purchaseFundingRequest->requested_amount);

        $oldValues = $purchaseFundingRequest->only(['status', 'approved_amount', 'approved_by', 'approved_at', 'owner_notes']);

        $purchaseFundingRequest->update([
            'approved_amount' => $approvedAmount,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'owner_notes' => $validated['owner_notes'] ?? null,
            'status' => FundingRequestStatus::APPROVED,
        ]);

        $this->writeAudit($request, 'purchase-funding-request.approved', $purchaseFundingRequest, $oldValues, [
            'status' => $purchaseFundingRequest->status?->value,
            'approved_amount' => (float) $purchaseFundingRequest->approved_amount,
            'approved_by' => $purchaseFundingRequest->approved_by,
            'approved_at' => optional($purchaseFundingRequest->approved_at)->toDateTimeString(),
            'owner_notes' => $purchaseFundingRequest->owner_notes,
        ]);

        return redirect()
            ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
            ->with('success', 'Pengajuan dana berhasil di-approve owner.');
    }

    public function rejectPurchaseFundingRequest(Request $request, PurchaseFundingRequest $purchaseFundingRequest): RedirectResponse
    {
        $validated = $request->validate([
            'owner_notes' => ['required', 'string', 'max:1000'],
        ]);

        if (in_array($purchaseFundingRequest->status?->value, [
            FundingRequestStatus::DISBURSED->value,
            FundingRequestStatus::SETTLED->value,
        ], true)) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Pengajuan dana yang sudah dicairkan tidak dapat ditolak.']);
        }

        $oldValues = $purchaseFundingRequest->only(['status', 'rejected_by', 'rejected_at', 'owner_notes']);

        $purchaseFundingRequest->update([
            'status' => FundingRequestStatus::REJECTED,
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'owner_notes' => $validated['owner_notes'],
        ]);

        $this->writeAudit($request, 'purchase-funding-request.rejected', $purchaseFundingRequest, $oldValues, [
            'status' => $purchaseFundingRequest->status?->value,
            'rejected_by' => $purchaseFundingRequest->rejected_by,
            'rejected_at' => optional($purchaseFundingRequest->rejected_at)->toDateTimeString(),
            'owner_notes' => $purchaseFundingRequest->owner_notes,
        ]);

        return redirect()
            ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
            ->with('success', 'Pengajuan dana ditolak.');
    }

    public function disbursePurchaseFundingRequest(Request $request, PurchaseFundingRequest $purchaseFundingRequest): RedirectResponse
    {
        $validated = $request->validate([
            'disbursed_amount' => ['nullable', 'numeric', 'min:1'],
            'finance_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($purchaseFundingRequest->status?->value, [
            FundingRequestStatus::APPROVED->value,
            FundingRequestStatus::DISBURSED->value,
        ], true)) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Pengajuan dana belum siap untuk proses pencairan.']);
        }

        $maxAllowed = (float) ($purchaseFundingRequest->approved_amount
            ?? $purchaseFundingRequest->reviewed_amount
            ?? $purchaseFundingRequest->requested_amount);

        $disbursedAmount = (float) ($validated['disbursed_amount'] ?? $maxAllowed);

        if ($disbursedAmount > $maxAllowed) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Dana cair tidak boleh melebihi nominal approval.']);
        }

        $oldValues = $purchaseFundingRequest->only(['status', 'disbursed_amount', 'disbursed_by', 'disbursed_at', 'finance_notes']);

        $purchaseFundingRequest->update([
            'disbursed_amount' => $disbursedAmount,
            'disbursed_by' => Auth::id(),
            'disbursed_at' => now(),
            'finance_notes' => $validated['finance_notes'] ?? $purchaseFundingRequest->finance_notes,
            'status' => FundingRequestStatus::DISBURSED,
        ]);

        $this->writeAudit($request, 'purchase-funding-request.disbursed', $purchaseFundingRequest, $oldValues, [
            'status' => $purchaseFundingRequest->status?->value,
            'disbursed_amount' => (float) $purchaseFundingRequest->disbursed_amount,
            'disbursed_by' => $purchaseFundingRequest->disbursed_by,
            'disbursed_at' => optional($purchaseFundingRequest->disbursed_at)->toDateTimeString(),
            'finance_notes' => $purchaseFundingRequest->finance_notes,
        ]);

        return redirect()
            ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
            ->with('success', 'Dana pembelian berhasil dicairkan.');
    }

    public function settlePurchaseFundingRequest(Request $request, PurchaseFundingRequest $purchaseFundingRequest): RedirectResponse
    {
        $validated = $request->validate([
            'spent_amount' => ['required', 'numeric', 'min:0'],
            'finance_notes' => ['nullable', 'string', 'max:1000'],
            'settlement_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:6144'],
        ]);

        $settlementProofError = $this->ensureSafeUploadedFile(
            $request,
            $request->file('settlement_proof'),
            ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
            ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            4096,
            [
                UserRole::FINANCE->value => 4096,
                UserRole::SUPER_ADMIN->value => 6144,
            ],
            'settlement_proof',
            'Bukti settlement'
        );
        if ($settlementProofError !== null) {
            return $settlementProofError;
        }

        if (! in_array($purchaseFundingRequest->status?->value, [
            FundingRequestStatus::DISBURSED->value,
            FundingRequestStatus::SETTLED->value,
        ], true)) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Settlement hanya bisa dilakukan setelah dana dicairkan.']);
        }

        $disbursedAmount = (float) ($purchaseFundingRequest->disbursed_amount ?? 0);
        $spentAmount = (float) $validated['spent_amount'];

        if ($disbursedAmount <= 0) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Belum ada nominal pencairan untuk disettle.']);
        }

        if ($spentAmount > $disbursedAmount) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Realisasi dana tidak boleh melebihi dana cair.']);
        }

        if (! $request->hasFile('settlement_proof') && ! $purchaseFundingRequest->settlement_proof_path) {
            return redirect()
                ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
                ->withErrors(['funding' => 'Lampiran bukti settlement (nota/foto) wajib diunggah.']);
        }

        $isSettled = abs($spentAmount - $disbursedAmount) < 0.01;

        $oldValues = $purchaseFundingRequest->only([
            'status',
            'spent_amount',
            'settled_by',
            'settled_at',
            'finance_notes',
            'settlement_proof_path',
            'settlement_proof_uploaded_at',
        ]);

        $newSettlementProofPath = $purchaseFundingRequest->settlement_proof_path;
        $newSettlementProofUploadedAt = $purchaseFundingRequest->settlement_proof_uploaded_at;

        if ($request->hasFile('settlement_proof')) {
            $newSettlementProofPath = $request->file('settlement_proof')->store('funding-settlement-proofs', 'public');
            $newSettlementProofUploadedAt = now();
        }

        $purchaseFundingRequest->update([
            'spent_amount' => $spentAmount,
            'finance_notes' => $validated['finance_notes'] ?? $purchaseFundingRequest->finance_notes,
            'status' => $isSettled ? FundingRequestStatus::SETTLED : FundingRequestStatus::DISBURSED,
            'settled_by' => $isSettled ? Auth::id() : null,
            'settled_at' => $isSettled ? now() : null,
            'settlement_proof_path' => $newSettlementProofPath,
            'settlement_proof_uploaded_at' => $newSettlementProofUploadedAt,
        ]);

        $this->writeAudit($request, 'purchase-funding-request.settled', $purchaseFundingRequest, $oldValues, [
            'status' => $purchaseFundingRequest->status?->value,
            'spent_amount' => (float) $purchaseFundingRequest->spent_amount,
            'settled_by' => $purchaseFundingRequest->settled_by,
            'settled_at' => optional($purchaseFundingRequest->settled_at)->toDateTimeString(),
            'finance_notes' => $purchaseFundingRequest->finance_notes,
            'settlement_proof_path' => $purchaseFundingRequest->settlement_proof_path,
            'settlement_proof_uploaded_at' => optional($purchaseFundingRequest->settlement_proof_uploaded_at)->toDateTimeString(),
        ]);

        return redirect()
            ->route('ui.purchase-funding-requests.index', $request->only(['status', 'fund_source']))
            ->with('success', $isSettled
                ? 'Settlement selesai. Dana sudah cocok dengan realisasi.'
                : 'Settlement parsial tersimpan. Masih ada sisa dana berjalan.');
    }

    public function usersRoles(Request $request): View
    {
        $users = User::query()
            ->with(['sppg', 'vendor'])
            ->latest('id')
            ->paginate(20);

        $sppgs = Sppg::query()->where('is_active', true)->orderBy('name')->get();
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get();
        $roleOptions = UserRole::values();
        $roleLabels = UserRole::labels();

        $editUser = null;
        if ($request->filled('edit')) {
            $editUser = User::query()->find($request->integer('edit'));
        }

        return view('procurement.master-data.users-roles.index', compact('users', 'sppgs', 'vendors', 'roleOptions', 'roleLabels', 'editUser'));
    }

    public function storeUserRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(UserRole::values())],
            'sppg_id' => [
                Rule::requiredIf(fn () => $request->input('role') === UserRole::SPPG_USER->value),
                'nullable',
                'exists:sppgs,id',
            ],
            'vendor_id' => [
                Rule::requiredIf(fn () => in_array($request->input('role'), [UserRole::VENDOR_ADMIN->value, UserRole::EXPEDITION->value], true)),
                'nullable',
                'exists:vendors,id',
            ],
        ]);

        $scopedEntityIds = $this->normalizeEntityScopeByRole(
            $validated['role'],
            $validated['sppg_id'] ?? null,
            $validated['vendor_id'] ?? null,
        );

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'sppg_id' => $scopedEntityIds['sppg_id'],
            'vendor_id' => $scopedEntityIds['vendor_id'],
        ]);

        return redirect()
            ->route('ui.users-roles.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function editUserRole(User $user): RedirectResponse
    {
        return redirect()->route('ui.users-roles.index', ['edit' => $user->id]);
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(UserRole::values())],
            'sppg_id' => [
                Rule::requiredIf(fn () => $request->input('role') === UserRole::SPPG_USER->value),
                'nullable',
                'exists:sppgs,id',
            ],
            'vendor_id' => [
                Rule::requiredIf(fn () => in_array($request->input('role'), [UserRole::VENDOR_ADMIN->value, UserRole::EXPEDITION->value], true)),
                'nullable',
                'exists:vendors,id',
            ],
        ]);

        $scopedEntityIds = $this->normalizeEntityScopeByRole(
            $validated['role'],
            $validated['sppg_id'] ?? null,
            $validated['vendor_id'] ?? null,
        );

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'sppg_id' => $scopedEntityIds['sppg_id'],
            'vendor_id' => $scopedEntityIds['vendor_id'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('ui.users-roles.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroyUserRole(User $user): RedirectResponse
    {
        if ((int) (Auth::id() ?? 0) === (int) $user->id) {
            return redirect()
                ->route('ui.users-roles.index')
                ->withErrors(['delete_user' => 'Akun yang sedang login tidak dapat dihapus.']);
        }

        try {
            $user->delete();
        } catch (QueryException) {
            return redirect()
                ->route('ui.users-roles.index')
                ->withErrors(['delete_user' => 'User tidak dapat dihapus karena masih terikat pada data transaksi tertentu.']);
        }

        return redirect()
            ->route('ui.users-roles.index')
            ->with('success', 'User berhasil dihapus.');
    }

    public function vendorPerformances(): View
    {
        $selectedVendorId = request()->filled('vendor_id') ? (int) request()->integer('vendor_id') : null;
        $dateFrom = request()->filled('date_from') ? (string) request()->input('date_from') : now()->subDays(30)->toDateString();
        $dateTo = request()->filled('date_to') ? (string) request()->input('date_to') : now()->toDateString();

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $deliveries = Delivery::query()
            ->with(['vendor:id,name', 'purchaseOrder:id,order_date,expected_date'])
            ->whereNotNull('vendor_id')
            ->when($selectedVendorId, fn ($query) => $query->where('vendor_id', $selectedVendorId))
            ->when($dateFrom, fn ($query) => $query->whereDate('delivery_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('delivery_date', '<=', $dateTo))
            ->orderBy('delivery_date')
            ->get();

        $rows = $deliveries
            ->groupBy('vendor_id')
            ->map(function (Collection $vendorDeliveries) use ($dateFrom, $dateTo) {
                $firstDelivery = $vendorDeliveries->first();
                $vendor = $firstDelivery?->vendor;

                $onTimeCount = 0;
                $lateCount = 0;
                $qualityIssueCount = 0;
                $leadTimes = [];

                foreach ($vendorDeliveries as $delivery) {
                    $expectedDate = $delivery->purchaseOrder?->expected_date ?? $delivery->purchaseOrder?->order_date;
                    if ($expectedDate && $delivery->delivery_date) {
                        if ($delivery->delivery_date->lte($expectedDate)) {
                            $onTimeCount++;
                        } else {
                            $lateCount++;
                        }
                    }

                    $notes = strtolower((string) ($delivery->notes ?? ''));
                    $isRejected = (string) ($delivery->status?->value ?? '') === DocumentStatus::REJECTED->value;
                    $hasQualityKeyword = str_contains($notes, 'retur')
                        || str_contains($notes, 'rusak')
                        || str_contains($notes, 'komplain')
                        || str_contains($notes, 'reject');

                    if ($isRejected || $hasQualityKeyword) {
                        $qualityIssueCount++;
                    }

                    if ($delivery->purchaseOrder?->order_date && $delivery->delivery_date) {
                        $leadTimes[] = (float) $delivery->purchaseOrder->order_date->diffInDays($delivery->delivery_date);
                    }
                }

                $totalDeliveries = (int) $vendorDeliveries->count();
                $avgLeadTime = $leadTimes === [] ? 0.0 : array_sum($leadTimes) / count($leadTimes);
                $onTimeRate = $totalDeliveries > 0 ? ($onTimeCount / $totalDeliveries) * 100 : 0.0;
                $lateRate = $totalDeliveries > 0 ? ($lateCount / $totalDeliveries) * 100 : 0.0;
                $qualityIssueRate = $totalDeliveries > 0 ? ($qualityIssueCount / $totalDeliveries) * 100 : 0.0;

                // Weighted score: timeliness + quality + lead-time efficiency.
                $leadTimeScore = max(0.0, 100.0 - min($avgLeadTime, 30.0) / 30.0 * 100.0);
                $score = ($onTimeRate * 0.5) + ((100.0 - $qualityIssueRate) * 0.3) + ($leadTimeScore * 0.2);

                return (object) [
                    'vendor_id' => (int) ($vendor?->id ?? 0),
                    'vendor_name' => $vendor?->name ?? '-',
                    'period_start' => $dateFrom,
                    'period_end' => $dateTo,
                    'total_deliveries' => $totalDeliveries,
                    'on_time_delivery_count' => $onTimeCount,
                    'late_delivery_count' => $lateCount,
                    'quality_issue_count' => $qualityIssueCount,
                    'average_lead_time_days' => $avgLeadTime,
                    'on_time_rate' => $onTimeRate,
                    'late_rate' => $lateRate,
                    'quality_issue_rate' => $qualityIssueRate,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->values();

        $performances = $this->paginateCollection($rows, 20);

        $summary = [
            'total_vendors' => $rows->count(),
            'total_deliveries' => (int) $rows->sum('total_deliveries'),
            'avg_on_time_rate' => (float) ($rows->avg('on_time_rate') ?? 0),
            'avg_score' => (float) ($rows->avg('score') ?? 0),
        ];

        return view('procurement.analytics.vendor-performances.index', compact(
            'performances',
            'vendors',
            'selectedVendorId',
            'dateFrom',
            'dateTo',
            'summary'
        ));
    }

    public function priceTrends(): View
    {
        $selectedProductId = request()->filled('product_id') ? (int) request()->integer('product_id') : null;
        $selectedVendorId = request()->filled('vendor_id') ? (int) request()->integer('vendor_id') : null;
        $dateFrom = request()->filled('date_from') ? (string) request()->input('date_from') : now()->subMonths(6)->toDateString();
        $dateTo = request()->filled('date_to') ? (string) request()->input('date_to') : now()->toDateString();

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'sku', 'name']);

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $histories = ProductPriceHistory::query()
            ->with(['product:id,sku,name', 'vendor:id,name'])
            ->when($selectedProductId, fn ($query) => $query->where('product_id', $selectedProductId))
            ->when($selectedVendorId, fn ($query) => $query->where('vendor_id', $selectedVendorId))
            ->when($dateFrom, fn ($query) => $query->whereDate('effective_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('effective_at', '<=', $dateTo))
            ->orderBy('effective_at')
            ->orderBy('id')
            ->get();

        $rows = $histories
            ->groupBy('product_id')
            ->map(function (Collection $productHistories) {
                $first = $productHistories->first();
                $last = $productHistories->last();

                $prices = $productHistories->pluck('price')->map(fn ($price) => (float) $price)->all();
                $recordsCount = count($prices);
                $min = $recordsCount > 0 ? min($prices) : 0.0;
                $max = $recordsCount > 0 ? max($prices) : 0.0;
                $avg = $recordsCount > 0 ? array_sum($prices) / $recordsCount : 0.0;
                $firstPrice = $recordsCount > 0 ? (float) $prices[0] : 0.0;
                $lastPrice = $recordsCount > 0 ? (float) $prices[$recordsCount - 1] : 0.0;
                $trendPercent = $firstPrice > 0 ? (($lastPrice - $firstPrice) / $firstPrice) * 100 : 0.0;
                $volatilityPercent = $avg > 0 ? (($max - $min) / $avg) * 100 : 0.0;

                return (object) [
                    'product_id' => (int) ($first?->product_id ?? 0),
                    'product' => $first?->product,
                    'records_count' => $recordsCount,
                    'vendors_count' => $productHistories->pluck('vendor_id')->filter()->unique()->count(),
                    'min_price' => $min,
                    'max_price' => $max,
                    'avg_price' => $avg,
                    'first_price' => $firstPrice,
                    'last_price' => $lastPrice,
                    'trend_percent' => $trendPercent,
                    'volatility_percent' => $volatilityPercent,
                    'last_effective_at' => $last?->effective_at,
                ];
            })
            ->sortByDesc(fn ($row) => abs((float) $row->trend_percent))
            ->values();

        $trendRows = $this->paginateCollection($rows, 20);

        $summary = [
            'total_products' => $rows->count(),
            'total_records' => (int) $rows->sum('records_count'),
            'avg_trend_percent' => (float) ($rows->avg('trend_percent') ?? 0),
            'products_up' => (int) $rows->filter(fn ($row) => (float) $row->trend_percent > 0)->count(),
            'products_down' => (int) $rows->filter(fn ($row) => (float) $row->trend_percent < 0)->count(),
        ];

        return view('procurement.analytics.price-trends.index', compact(
            'trendRows',
            'products',
            'vendors',
            'selectedProductId',
            'selectedVendorId',
            'dateFrom',
            'dateTo',
            'summary'
        ));
    }

    private function paginateCollection(Collection $rows, int $perPage = 20): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $rows->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function auditTrails(Request $request): View
    {
        $selectedEvent = $request->filled('event') ? (string) $request->input('event') : null;
        $selectedUserId = $request->filled('user_id') ? (int) $request->integer('user_id') : null;
        $dateFrom = $request->filled('date_from') ? (string) $request->input('date_from') : null;
        $dateTo = $request->filled('date_to') ? (string) $request->input('date_to') : null;

        $eventOptions = AuditTrail::query()
            ->whereNotNull('event')
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $userOptions = User::query()
            ->whereIn('id', AuditTrail::query()->whereNotNull('user_id')->select('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        $auditTrails = AuditTrail::query()
            ->with('user')
            ->when($selectedEvent, fn ($query) => $query->where('event', $selectedEvent))
            ->when($selectedUserId, fn ($query) => $query->where('user_id', $selectedUserId))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('procurement.analytics.audit-trails.index', compact(
            'auditTrails',
            'eventOptions',
            'userOptions',
            'selectedEvent',
            'selectedUserId',
            'dateFrom',
            'dateTo'
        ));
    }

    public function storePurchaseRequest(Request $request): RedirectResponse
    {
        $itemsInput = $request->input('items', []);
        if (is_array($itemsInput)) {
            foreach ($itemsInput as $itemIndex => $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (array_key_exists('quantity', $item)) {
                    $itemsInput[$itemIndex]['quantity'] = $this->parseLocalizedNumberValue($item['quantity']);
                }

                if (array_key_exists('requested_unit_price', $item)) {
                    $itemsInput[$itemIndex]['requested_unit_price'] = $this->parseLocalizedNumberValue($item['requested_unit_price']);
                }
            }

            $request->merge(['items' => $itemsInput]);
        }

        $currentUser = Auth::user();
        $isSppgUser = $currentUser?->role?->value === UserRole::SPPG_USER->value;

        $validated = $request->validate([
            'sppg_id' => ['required', 'exists:sppgs,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'needed_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'is_additional' => ['nullable', 'boolean'],
            'additional_to_po_id' => [
                Rule::requiredIf(fn () => $request->boolean('is_additional')),
                'nullable',
                'exists:purchase_orders,id',
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.ad_hoc_name' => ['nullable', 'string', 'max:255'],
            'items.*.ad_hoc_unit' => ['nullable', 'string', 'max:30'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.requested_unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($validated['items'] as $index => $item) {
            $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
            $adHocName = trim((string) ($item['ad_hoc_name'] ?? ''));
            $adHocUnit = trim((string) ($item['ad_hoc_unit'] ?? ''));

            if ($productId <= 0 && $adHocName === '') {
                return redirect()
                    ->route('ui.purchase-requests.index')
                    ->withErrors(['items.'.$index.'.product_id' => 'Pilih produk katalog atau isi nama produk non katalog.'])
                    ->withInput();
            }

            if ($adHocName !== '' && $adHocUnit === '') {
                return redirect()
                    ->route('ui.purchase-requests.index')
                    ->withErrors(['items.'.$index.'.ad_hoc_unit' => 'Satuan wajib diisi untuk produk non katalog.'])
                    ->withInput();
            }
        }

        if ($isSppgUser && (int) $validated['sppg_id'] !== (int) ($currentUser?->sppg_id ?? 0)) {
            return redirect()
                ->route('ui.purchase-requests.index')
                ->withErrors(['sppg_id' => 'User SPPG hanya dapat membuat PR untuk SPPG miliknya sendiri.'])
                ->withInput();
        }

        $createdPurchaseRequest = null;

        try {
            DB::transaction(function () use ($validated, $request, &$createdPurchaseRequest) {
                $authUser = $request->user();
                $requesterId = $authUser?->id ?? User::query()->where('role', 'sppg_user')->value('id');

                $isAdditional = $request->boolean('is_additional');
                $additionalToPoId = $validated['additional_to_po_id'] ?? null;
                $hasAdHocItems = collect($validated['items'])->contains(function (array $item) {
                    return trim((string) ($item['ad_hoc_name'] ?? '')) !== '';
                });

                $notes = trim((string) ($validated['notes'] ?? ''));
                if ($isAdditional) {
                    $additionalMarker = '[BARANG TAMBAHAN]';
                    $targetPoNumber = PurchaseOrder::query()->whereKey($additionalToPoId)->value('number');
                    $referenceText = $targetPoNumber ? ' Referensi PO: '.$targetPoNumber.'.' : '';
                    $notes = trim($additionalMarker.' '.$referenceText.' '.($notes !== '' ? $notes : ''));
                }
                if ($hasAdHocItems && ! str_contains($notes, '[NON KATALOG]')) {
                    $notes = trim('[NON KATALOG] '.($notes !== '' ? $notes : ''));
                }

                $purchaseRequest = PurchaseRequest::query()->create([
                    'number' => $this->generateNumber('PR'),
                    'sppg_id' => $validated['sppg_id'],
                    'requested_by' => $requesterId,
                    'request_date' => now()->toDateString(),
                    'needed_date' => $validated['needed_date'] ?? null,
                    'status' => DocumentStatus::SUBMITTED,
                    'notes' => $notes !== '' ? $notes : null,
                    'is_additional' => $isAdditional,
                    'additional_to_po_id' => $additionalToPoId,
                    'total_amount' => 0,
                ]);

                foreach ($validated['items'] as $item) {
                    $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
                    $adHocName = trim((string) ($item['ad_hoc_name'] ?? ''));
                    $adHocUnit = trim((string) ($item['ad_hoc_unit'] ?? ''));

                    if ($productId <= 0 && $adHocName !== '') {
                        $productId = $this->createAdHocProduct(
                            $adHocName,
                            $adHocUnit,
                            isset($validated['vendor_id']) ? (int) $validated['vendor_id'] : null
                        )->id;
                    }

                    $requestedUnitPrice = $item['requested_unit_price'] ?? null;
                    if ($requestedUnitPrice === null || $requestedUnitPrice === '') {
                        $requestedUnitPrice = $this->resolveRequestedUnitPrice(
                            $productId,
                            isset($validated['vendor_id']) ? (int) $validated['vendor_id'] : null
                        );
                    }

                    if ($requestedUnitPrice === null) {
                        if ($adHocName !== '') {
                            throw new \RuntimeException('Harga wajib diisi untuk produk non katalog.');
                        }

                        throw new \RuntimeException('Harga master untuk produk yang dipilih belum tersedia.');
                    }

                    $subtotal = (float) $item['quantity'] * (float) $requestedUnitPrice;

                    $purchaseRequest->items()->create([
                        'product_id' => $productId,
                        'quantity' => $item['quantity'],
                        'requested_unit_price' => $requestedUnitPrice,
                        'subtotal' => $subtotal,
                    ]);
                }

                $purchaseRequest->recalculateTotal();
                $purchaseRequest->refresh();
                $createdPurchaseRequest = $purchaseRequest;
            });
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('ui.purchase-requests.index')
                ->withErrors(['purchase_request' => $exception->getMessage()])
                ->withInput();
        }

        if ($createdPurchaseRequest) {
            $this->writeAudit(
                $request,
                'purchase_request.created',
                $createdPurchaseRequest,
                null,
                $createdPurchaseRequest->toArray()
            );
        }

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

        $result = DB::transaction(function () use ($purchaseRequest, $validated) {
            $purchaseRequest->loadMissing(['items.product', 'sppg', 'additionalToPurchaseOrder']);

            $additionalTargetPo = null;
            if ($purchaseRequest->is_additional && $purchaseRequest->additional_to_po_id) {
                $additionalTargetPo = PurchaseOrder::query()
                    ->with('vendor')
                    ->find($purchaseRequest->additional_to_po_id);

                if (! $additionalTargetPo) {
                    throw new \RuntimeException('PO referensi untuk barang tambahan tidak ditemukan.');
                }

                if ((int) $additionalTargetPo->sppg_id !== (int) $purchaseRequest->sppg_id) {
                    throw new \RuntimeException('PO referensi harus dalam SPPG yang sama.');
                }
            }

            $vendorId = $additionalTargetPo?->vendor_id
                ?? $validated['vendor_id']
                ?? $purchaseRequest->sppg?->default_vendor_id;

            if (! $vendorId) {
                throw new \RuntimeException('Vendor tidak tersedia untuk PR ini.');
            }

            $orderedBy = User::query()->where('role', 'purchasing')->value('id');

            if ($additionalTargetPo) {
                $purchaseOrder = $additionalTargetPo;
            } else {
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
            }

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

            $poTotalAmount = (float) $purchaseOrder->total_amount;
            $poOwnerApprovalThreshold = $this->getPoOwnerApprovalThreshold();
            $hasAdHocItems = $purchaseRequest->items->contains(function ($item) {
                return (bool) ($item->product?->is_ad_hoc ?? false);
            });
            $requiresOwnerApproval = $poTotalAmount > $poOwnerApprovalThreshold || $hasAdHocItems;

            if ($requiresOwnerApproval) {
                $ownerApproverId = User::query()->where('role', UserRole::OWNER->value)->value('id')
                    ?? User::query()->where('role', 'owner')->value('id');

                $approvalReasons = [];
                if ($poTotalAmount > $poOwnerApprovalThreshold) {
                    $approvalReasons[] = 'nominal PO di atas threshold';
                }
                if ($hasAdHocItems) {
                    $approvalReasons[] = 'mengandung item non katalog';
                }
                $approvalReasonText = implode(' + ', $approvalReasons);

                $purchaseOrder->update([
                    'status' => DocumentStatus::SUBMITTED,
                    'notes' => trim((string) ($purchaseOrder->notes ?? '').' [MENUNGGU APPROVAL OWNER: '.$approvalReasonText.']'),
                ]);

                Approval::query()->create([
                    'approvable_type' => PurchaseOrder::class,
                    'approvable_id' => $purchaseOrder->id,
                    'level' => 1,
                    'approver_id' => $ownerApproverId,
                    'status' => DocumentStatus::SUBMITTED,
                    'note' => 'Menunggu approval owner ('.$approvalReasonText.'). Total PO: '.$poTotalAmount.', threshold: '.$poOwnerApprovalThreshold,
                    'approved_at' => null,
                ]);
            }

            if ($additionalTargetPo) {
                $existingNotes = trim((string) ($purchaseOrder->notes ?? ''));
                $additionalNote = '[BARANG TAMBAHAN] dari PR '.$purchaseRequest->number;
                if (! str_contains($existingNotes, $additionalNote)) {
                    $purchaseOrder->update([
                        'notes' => trim(($existingNotes !== '' ? $existingNotes.PHP_EOL : '').$additionalNote),
                    ]);
                }
            }

            $purchaseRequest->update([
                'status' => DocumentStatus::PROCESSED,
            ]);

            return [
                'is_additional' => (bool) $additionalTargetPo,
                'po_number' => $purchaseOrder->number,
                'requires_owner_approval' => $requiresOwnerApproval,
                'owner_approval_reason' => $approvalReasonText ?? null,
            ];
        });

        if (! empty($result['requires_owner_approval'])) {
            $message = 'Purchase Order '.$result['po_number'].' berhasil dibuat dan menunggu approval owner'.(! empty($result['owner_approval_reason']) ? ' ('.$result['owner_approval_reason'].')' : '').'.';
        } else {
            $message = ! empty($result['is_additional'])
                ? 'Barang tambahan berhasil ditambahkan ke PO '.$result['po_number'].' agar tetap satu alur invoice.'
                : 'Purchase Order berhasil digenerate.';
        }

        return redirect()
            ->route('ui.purchase-orders.index')
            ->with('success', $message);
    }

    private function resolveRequestedUnitPrice(int $productId, ?int $vendorId = null): ?float
    {
        $priceQuery = ProductPriceHistory::query()
            ->where('product_id', $productId)
            ->orderByDesc('effective_at')
            ->orderByDesc('id');

        if ($vendorId) {
            $price = (clone $priceQuery)
                ->where(function ($query) use ($vendorId) {
                    $query->where('vendor_id', $vendorId)->orWhereNull('vendor_id');
                })
                ->value('price');

            if ($price !== null) {
                return (float) $price;
            }
        }

        $defaultPrice = (clone $priceQuery)
            ->whereNull('vendor_id')
            ->value('price');

        if ($defaultPrice !== null) {
            return (float) $defaultPrice;
        }

        $product = Product::query()->find($productId);
        if (! $product || $product->government_price_cap === null) {
            return null;
        }

        $basePrice = (float) $product->government_price_cap;
        $varianceAmount = (float) ($product->price_variance_amount ?? 0);
        $variancePercent = (float) ($product->price_variance_percent ?? 0);

        return $basePrice + $varianceAmount + ($basePrice * $variancePercent / 100);
    }

    private function createAdHocProduct(string $name, string $unit, ?int $vendorId = null): Product
    {
        $normalizedName = trim($name);
        $normalizedUnit = trim($unit);

        if ($normalizedName === '' || $normalizedUnit === '') {
            throw new \RuntimeException('Produk non katalog membutuhkan nama dan satuan.');
        }

        $adHocCategory = ProductCategory::query()->firstOrCreate(
            ['name' => 'Non Katalog'],
            ['description' => 'Produk ad-hoc/non katalog untuk kebutuhan insidental vendor external']
        );

        return Product::query()->create([
            'sku' => $this->generateNumber('ADH'),
            'name' => $normalizedName,
            'product_category_id' => $adHocCategory->id,
            'vendor_id' => $vendorId,
            'unit' => $normalizedUnit,
            'government_price_cap' => null,
            'price_variance_percent' => null,
            'price_variance_amount' => null,
            'minimum_stock_level' => 0,
            'reorder_stock_level' => 0,
            'is_active' => false,
            'is_ad_hoc' => true,
        ]);
    }

    public function generateInvoice(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->normalizeLocalizedNumericInputs($request, [
            'tax_amount',
        ]);

        $validated = $request->validate([
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]);

        $oldDelivery = $delivery->toArray();

        if (in_array($delivery->status?->value, [DocumentStatus::INVOICED->value, DocumentStatus::PAID->value], true)) {
            return redirect()
                ->route('ui.deliveries.index')
                ->withErrors(['invoice' => 'Delivery ini sudah pernah dibuatkan invoice.']);
        }

        DB::transaction(function () use ($delivery, $validated) {
            $this->upsertInvoiceFromDelivery(
                $delivery,
                (float) ($validated['tax_amount'] ?? 0),
                $validated['due_date'] ?? null,
            );
        });

        $delivery->refresh();
        $this->writeAudit(
            $request,
            'invoice.generated_from_delivery',
            $delivery,
            $oldDelivery,
            $delivery->toArray()
        );

        return redirect()
            ->route('ui.invoices.index')
            ->with('success', 'Invoice vendor berhasil dibuat/diperbarui dari delivery.');
    }

    private function upsertInvoiceFromDelivery(Delivery $delivery, float $taxAmount = 0, ?string $dueDate = null): void
    {
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

        $existingInvoice = Invoice::query()
            ->where('billing_cycle_id', $billingCycle->id)
            ->where('sppg_id', $delivery->sppg_id)
            ->where('vendor_id', $vendorId)
            ->where('status', '!=', DocumentStatus::PAID->value)
            ->latest('id')
            ->first();

        if ($existingInvoice) {
            $existingInvoice->update([
                'subtotal_amount' => (float) $existingInvoice->subtotal_amount + $subtotal,
                'tax_amount' => (float) $existingInvoice->tax_amount + $taxAmount,
                'total_amount' => (float) $existingInvoice->total_amount + $subtotal + $taxAmount,
                'due_date' => $dueDate ?? $existingInvoice->due_date,
                'status' => DocumentStatus::INVOICED,
            ]);
        } else {
            Invoice::query()->create([
                'number' => $this->generateNumber('INV'),
                'billing_cycle_id' => $billingCycle->id,
                'delivery_id' => $delivery->id,
                'sppg_id' => $delivery->sppg_id,
                'vendor_id' => $vendorId,
                'invoice_date' => now()->toDateString(),
                'due_date' => $dueDate ?? now()->addWeek()->toDateString(),
                'status' => DocumentStatus::INVOICED,
                'subtotal_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ]);
        }

        $delivery->update([
            'status' => DocumentStatus::INVOICED,
        ]);
    }

    private function generateNumber(string $prefix): string
    {
        $datePart = now()->format('Ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        return sprintf('%s-%s-%s', $prefix, $datePart, $random);
    }

    private function writeAudit(Request $request, string $event, object $model, ?array $oldValues = null, ?array $newValues = null): void
    {
        if (! isset($model->id)) {
            return;
        }

        AuditTrail::query()->create([
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

    private function hasStatusAttribute(object $model): bool
    {
        if (! method_exists($model, 'getAttributes')) {
            return false;
        }

        $attributes = $model->getAttributes();
        if (array_key_exists('status', $attributes)) {
            return true;
        }

        if (method_exists($model, 'getFillable')) {
            return in_array('status', $model->getFillable(), true);
        }

        return false;
    }

    private function getPoOwnerApprovalThreshold(): float
    {
        $storedValue = AppSetting::query()
            ->where('key', 'po_owner_approval_threshold')
            ->value('value');

        if ($storedValue === null || $storedValue === '') {
            return 5000000;
        }

        return (float) $storedValue;
    }

    private function getPurchaseFundingOwnerApprovalThreshold(): float
    {
        $storedValue = AppSetting::query()
            ->where('key', 'purchase_funding_owner_approval_threshold')
            ->value('value');

        if ($storedValue === null || $storedValue === '') {
            return 1000000;
        }

        return (float) $storedValue;
    }

    private function ensureSafeUploadedFile(
        Request $request,
        ?UploadedFile $file,
        array $allowedExtensions,
        array $allowedMimeTypes,
        int $defaultMaxKb,
        array $roleBasedMaxKb,
        string $fieldName,
        string $label
    ): ?RedirectResponse {
        if (! $file) {
            return null;
        }

        if (! $file->isValid()) {
            return redirect()->back()->withErrors([
                $fieldName => $label.' gagal diunggah. Silakan pilih file lain.',
            ])->withInput();
        }

        $currentRole = Auth::user()?->role?->value;
        $allowedMaxKb = $roleBasedMaxKb[$currentRole] ?? $defaultMaxKb;
        if ((int) ceil($file->getSize() / 1024) > $allowedMaxKb) {
            return redirect()->back()->withErrors([
                $fieldName => $label.' melebihi batas ukuran role Anda (maks '.number_format($allowedMaxKb / 1024, 2, ',', '.').' MB).',
            ])->withInput();
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions, true)) {
            return redirect()->back()->withErrors([
                $fieldName => $label.' memiliki ekstensi file yang tidak diizinkan.',
            ])->withInput();
        }

        $detectedMime = strtolower((string) ($file->getMimeType() ?? ''));
        if (! in_array($detectedMime, $allowedMimeTypes, true)) {
            return redirect()->back()->withErrors([
                $fieldName => $label.' memiliki tipe file tidak valid (deteksi MIME ditolak).',
            ])->withInput();
        }

        $originalName = strtolower((string) $file->getClientOriginalName());
        if (str_contains($originalName, '..') || preg_match('/\.(php\d*|phtml|phar|cgi|pl|py|sh|exe|bat|cmd|com|js|html?)$/', $originalName)) {
            return redirect()->back()->withErrors([
                $fieldName => $label.' ditolak karena nama file berisiko.',
            ])->withInput();
        }

        return null;
    }

    private function terbilangRupiah(float $amount): string
    {
        $value = (int) round($amount);
        if ($value === 0) {
            return 'Nol Rupiah';
        }

        return trim($this->terbilangInteger($value)).' Rupiah';
    }

    private function terbilangInteger(int $value): string
    {
        $words = [
            0 => '',
            1 => 'Satu',
            2 => 'Dua',
            3 => 'Tiga',
            4 => 'Empat',
            5 => 'Lima',
            6 => 'Enam',
            7 => 'Tujuh',
            8 => 'Delapan',
            9 => 'Sembilan',
            10 => 'Sepuluh',
            11 => 'Sebelas',
        ];

        if ($value < 12) {
            return $words[$value];
        }

        if ($value < 20) {
            return trim($this->terbilangInteger($value - 10).' Belas');
        }

        if ($value < 100) {
            return trim($this->terbilangInteger((int) floor($value / 10)).' Puluh '.$this->terbilangInteger($value % 10));
        }

        if ($value < 200) {
            return trim('Seratus '.$this->terbilangInteger($value - 100));
        }

        if ($value < 1000) {
            return trim($this->terbilangInteger((int) floor($value / 100)).' Ratus '.$this->terbilangInteger($value % 100));
        }

        if ($value < 2000) {
            return trim('Seribu '.$this->terbilangInteger($value - 1000));
        }

        if ($value < 1000000) {
            return trim($this->terbilangInteger((int) floor($value / 1000)).' Ribu '.$this->terbilangInteger($value % 1000));
        }

        if ($value < 1000000000) {
            return trim($this->terbilangInteger((int) floor($value / 1000000)).' Juta '.$this->terbilangInteger($value % 1000000));
        }

        if ($value < 1000000000000) {
            return trim($this->terbilangInteger((int) floor($value / 1000000000)).' Miliar '.$this->terbilangInteger($value % 1000000000));
        }

        return trim($this->terbilangInteger((int) floor($value / 1000000000000)).' Triliun '.$this->terbilangInteger($value % 1000000000000));
    }

    private function imageDataUri(string $absolutePath, bool $circular = false): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $extension = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        if (
            $circular
            && function_exists('imagecreatefromstring')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled')
            && function_exists('imagepng')
            && function_exists('imagesavealpha')
            && function_exists('imagecolorallocatealpha')
            && function_exists('imagesetpixel')
            && function_exists('imagedestroy')
        ) {
            $sourceBinary = @file_get_contents($absolutePath);
            if ($sourceBinary !== false) {
                $sourceImage = @imagecreatefromstring($sourceBinary);
                if ($sourceImage !== false) {
                    $sourceWidth = imagesx($sourceImage);
                    $sourceHeight = imagesy($sourceImage);
                    $sourceSize = min($sourceWidth, $sourceHeight);

                    if ($sourceSize > 0) {
                        $srcX = (int) floor(($sourceWidth - $sourceSize) / 2);
                        $srcY = (int) floor(($sourceHeight - $sourceSize) / 2);
                        $targetSize = 256;

                        $output = imagecreatetruecolor($targetSize, $targetSize);
                        imagesavealpha($output, true);
                        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
                        imagefill($output, 0, 0, $transparent);

                        imagecopyresampled(
                            $output,
                            $sourceImage,
                            0,
                            0,
                            $srcX,
                            $srcY,
                            $targetSize,
                            $targetSize,
                            $sourceSize,
                            $sourceSize
                        );

                        $center = ($targetSize - 1) / 2;
                        $radius = $targetSize / 2;
                        $radiusSquared = $radius * $radius;

                        for ($y = 0; $y < $targetSize; $y++) {
                            for ($x = 0; $x < $targetSize; $x++) {
                                $dx = $x - $center;
                                $dy = $y - $center;

                                if (($dx * $dx) + ($dy * $dy) > $radiusSquared) {
                                    imagesetpixel($output, $x, $y, $transparent);
                                }
                            }
                        }

                        ob_start();
                        imagepng($output);
                        $circleBinary = (string) ob_get_clean();

                        imagedestroy($output);
                        imagedestroy($sourceImage);

                        if ($circleBinary !== '') {
                            return 'data:image/png;base64,'.base64_encode($circleBinary);
                        }
                    }

                    imagedestroy($sourceImage);
                }
            }
        }

        $binary = @file_get_contents($absolutePath);
        if ($binary === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function resolveVendorLogoDataUri(?Vendor $vendor, bool $circular = false): array
    {
        $candidatePaths = [];
        $logoBaseDirs = ['images/vendor', 'images/vendors'];

        if ($vendor) {
            $codeSlug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $vendor->code), '-'));
            $nameSlug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $vendor->name), '-'));

            foreach ([$codeSlug, $nameSlug] as $slug) {
                if ($slug === '') {
                    continue;
                }

                foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                    foreach ($logoBaseDirs as $baseDir) {
                        $candidatePaths[] = public_path($baseDir.'/'.$slug.'.'.$ext);
                    }
                }
            }
        }

        foreach ($candidatePaths as $path) {
            $uri = $this->imageDataUri($path, $circular);
            if ($uri !== null) {
                return [$uri, true];
            }
        }

        foreach ([
            public_path('images/smp-logo.png'),
            public_path('images/logo-smp.png'),
            public_path('images/logo-bgn.png'),
        ] as $fallbackPath) {
            $uri = $this->imageDataUri($fallbackPath, $circular);
            if ($uri !== null) {
                return [$uri, false];
            }
        }

        return [null, false];
    }

    private function resolveVendorLogoDataUriByName(string $vendorName): array
    {
        $normalizedName = trim((string) preg_replace('/\s+/', ' ', $vendorName));
        if ($normalizedName === '') {
            return [null, false];
        }

        $lowerName = strtolower($normalizedName);

        $vendor = Vendor::query()
            ->whereRaw('LOWER(name) = ?', [$lowerName])
            ->first();

        if (! $vendor) {
            $vendor = Vendor::query()
                ->whereRaw('LOWER(name) LIKE ?', ['%'.$lowerName.'%'])
                ->first();
        }

        if (! $vendor) {
            return [null, false];
        }

        [$logoUri, $isCustom] = $this->resolveVendorLogoDataUri($vendor);

        return $isCustom ? [$logoUri, true] : [null, false];
    }

    private function currentVendorScopeId(): ?int
    {
        $user = Auth::user();
        if (! in_array($user?->role?->value, [UserRole::VENDOR_ADMIN->value, UserRole::EXPEDITION->value], true)) {
            return null;
        }

        $vendorId = (int) ($user->vendor_id ?? 0);
        if ($vendorId <= 0) {
            abort(403, 'Akun vendor tidak memiliki scope vendor yang valid.');
        }

        return $vendorId;
    }

    private function currentSppgScopeId(): ?int
    {
        $user = Auth::user();
        if ($user?->role?->value !== UserRole::SPPG_USER->value) {
            return null;
        }

        $sppgId = (int) ($user->sppg_id ?? 0);
        if ($sppgId <= 0) {
            abort(403, 'Akun SPPG tidak memiliki scope SPPG yang valid.');
        }

        return $sppgId;
    }

    private function resolveScopedVendorSelection(Request $request): array
    {
        $vendorScopeId = $this->currentVendorScopeId();
        $requestedVendorId = $request->filled('vendor') ? (int) $request->integer('vendor') : null;
        $selectedVendorId = $vendorScopeId ?? $requestedVendorId;

        $selectedVendor = null;
        if ($selectedVendorId) {
            $selectedVendor = Vendor::query()
                ->select(['id', 'name'])
                ->find($selectedVendorId);
        }

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->when($vendorScopeId !== null, fn ($query) => $query->whereKey($vendorScopeId > 0 ? $vendorScopeId : -1))
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'selectedVendorId' => $selectedVendorId,
            'selectedVendor' => $selectedVendor,
            'vendors' => $vendors,
        ];
    }

    private function resolveDashboardRoleScopeContext(): array
    {
        $currentUser = Auth::user();
        $currentRole = $currentUser?->role?->value;
        $isSuperAdmin = $currentRole === UserRole::SUPER_ADMIN->value;
        $sppgScopeId = $currentRole === UserRole::SPPG_USER->value ? (int) ($currentUser?->sppg_id ?? 0) : null;
        $vendorScopeId = in_array($currentRole, [UserRole::VENDOR_ADMIN->value, UserRole::EXPEDITION->value], true)
            ? (int) ($currentUser?->vendor_id ?? 0)
            : null;

        return [
            'sppg_scope_id' => $sppgScopeId,
            'vendor_scope_id' => $vendorScopeId,
            'sppg_scope_enabled' => ! $isSuperAdmin && $sppgScopeId !== null,
            'vendor_scope_enabled' => ! $isSuperAdmin && $vendorScopeId !== null,
        ];
    }

    private function scopedId(?int $scopeId): int
    {
        return $scopeId !== null && $scopeId > 0 ? $scopeId : -1;
    }

    private function normalizeEntityScopeByRole(string $role, ?int $sppgId, ?int $vendorId): array
    {
        if ($role === UserRole::SPPG_USER->value) {
            return [
                'sppg_id' => $sppgId,
                'vendor_id' => null,
            ];
        }

        if (in_array($role, [UserRole::VENDOR_ADMIN->value, UserRole::EXPEDITION->value], true)) {
            return [
                'sppg_id' => null,
                'vendor_id' => $vendorId,
            ];
        }

        return [
            'sppg_id' => null,
            'vendor_id' => null,
        ];
    }
}
