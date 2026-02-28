@extends('layouts.procurement')

@section('title', 'Purchase Requests')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Purchase Requests</h2>
            <p class="text-sm text-gray-500">Daftar permintaan pembelian dari seluruh SPPG.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('ui.purchase-requests.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Create Purchase Request (Quick Form)</h3>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">SPPG</label>
                <select name="sppg_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">Pilih SPPG</option>
                    @foreach($sppgs as $sppg)
                        <option value="{{ $sppg->id }}">{{ $sppg->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Needed Date</label>
                <input type="date" name="needed_date" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Product</label>
                <select name="product_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">Pilih Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Quantity</label>
                <input type="number" step="0.01" min="0.01" name="quantity" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Requested Unit Price</label>
                <input type="number" step="0.01" min="0" name="requested_unit_price" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">PO Vendor (Optional)</label>
                <select name="vendor_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Use SPPG default vendor saat generate PO</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 xl:col-span-3">
                <label class="mb-1 block text-xs font-medium text-gray-600">Notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Create PR</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3">Requester</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($purchaseRequests as $pr)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $pr->number }}</td>
                            <td class="px-4 py-3">{{ $pr->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $pr->requester?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($pr->request_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium">{{ $pr->status?->value }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $pr->total_amount, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @if($pr->status?->value === 'submitted')
                                        <form method="POST" action="{{ route('ui.purchase-requests.approve', $pr) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Approve</button>
                                        </form>
                                    @endif

                                    @if($pr->status?->value === 'approved')
                                        <form method="POST" action="{{ route('ui.purchase-requests.generate-po', $pr) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="date" name="expected_date" class="rounded-md border border-gray-300 px-2 py-1 text-xs">
                                            <select name="vendor_id" class="rounded-md border border-gray-300 px-2 py-1 text-xs">
                                                <option value="">Default vendor</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">Generate PO</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data purchase request.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $purchaseRequests->links() }}
    </div>
@endsection
