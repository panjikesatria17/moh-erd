<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesUserRoleScope;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    use AppliesUserRoleScope;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::query()
            ->with(['purchaseRequest', 'sppg', 'vendor', 'items.product'])
            ->latest('order_date');

        $this->applySppgScopeForSppgUser($request, $query, 'sppg_id');
        $this->applyVendorScopeForVendorAdmin($request, $query, 'vendor_id');

        $data = $query->paginate((int) $request->integer('per_page', 15));

        return response()->json($data);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['purchaseRequest', 'sppg', 'vendor', 'items.product']);

        return response()->json([
            'data' => $purchaseOrder,
        ]);
    }
}
