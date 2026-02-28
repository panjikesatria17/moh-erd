<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $query = PurchaseRequest::query()
            ->with(['sppg', 'requester', 'items.product'])
            ->latest('request_date');

        $role = $request->user()?->role;

        if ($role === UserRole::SPPG_USER || $role === UserRole::SPPG_USER->value) {
            $query->where('sppg_id', $request->user()?->sppg_id);
        }

        $data = $query->paginate((int) $request->integer('per_page', 15));

        return response()->json($data);
    }

    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorize('view', $purchaseRequest);

        $purchaseRequest->load(['sppg', 'requester', 'items.product', 'approvals.approver']);

        return response()->json([
            'data' => $purchaseRequest,
        ]);
    }
}
