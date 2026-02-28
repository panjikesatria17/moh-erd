<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::query()
            ->with(['purchaseRequest', 'sppg', 'vendor', 'items.product'])
            ->latest('order_date');

        $role = $request->user()?->role;

        if ($role === UserRole::SPPG_USER || $role === UserRole::SPPG_USER->value) {
            $query->where('sppg_id', $request->user()?->sppg_id);
        }

        if ($role === UserRole::VENDOR_ADMIN || $role === UserRole::VENDOR_ADMIN->value) {
            $query->where('vendor_id', $request->user()?->vendor_id);
        }

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
