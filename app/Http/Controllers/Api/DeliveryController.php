<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesUserRoleScope;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    use AppliesUserRoleScope;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Delivery::class);

        $query = Delivery::query()
            ->with(['purchaseOrder', 'goodsReceipt', 'sppg', 'vendor'])
            ->latest('delivery_date');

        $this->applySppgScopeForSppgUser($request, $query, 'sppg_id');
        $this->applyVendorScopeForVendorAdmin($request, $query, 'vendor_id');

        $data = $query->paginate((int) $request->integer('per_page', 15));

        return response()->json($data);
    }

    public function show(Delivery $delivery): JsonResponse
    {
        $this->authorize('view', $delivery);

        $delivery->load(['purchaseOrder', 'goodsReceipt', 'sppg', 'vendor', 'invoice']);

        return response()->json([
            'data' => $delivery,
        ]);
    }
}
