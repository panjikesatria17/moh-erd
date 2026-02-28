<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Delivery::class);

        $query = Delivery::query()
            ->with(['purchaseOrder', 'goodsReceipt', 'sppg', 'vendor'])
            ->latest('delivery_date');

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

    public function show(Delivery $delivery): JsonResponse
    {
        $this->authorize('view', $delivery);

        $delivery->load(['purchaseOrder', 'goodsReceipt', 'sppg', 'vendor', 'invoice']);

        return response()->json([
            'data' => $delivery,
        ]);
    }
}
