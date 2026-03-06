<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesUserRoleScope;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use AppliesUserRoleScope;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()
            ->with(['billingCycle', 'delivery', 'sppg', 'vendor', 'payments'])
            ->latest('invoice_date');

        $this->applySppgScopeForSppgUser($request, $query, 'sppg_id');
        $this->applyVendorScopeForVendorAdmin($request, $query, 'vendor_id');

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
