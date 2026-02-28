<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()
            ->with(['billingCycle', 'delivery', 'sppg', 'vendor', 'payments'])
            ->latest('invoice_date');

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

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['billingCycle', 'delivery', 'sppg', 'vendor', 'payments']);

        return response()->json([
            'data' => $invoice,
        ]);
    }
}
