@extends('layouts.procurement')

@section('title', 'Purchase Orders')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Purchase Orders</h2>
        <p class="text-sm text-gray-500">Daftar PO yang diterbitkan oleh purchasing HO.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">PR</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Order Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($purchaseOrders as $po)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $po->number }}</td>
                            <td class="px-4 py-3">{{ $po->purchaseRequest?->number ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $po->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($po->order_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium">{{ $po->status?->value }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $po->total_amount, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada data purchase order.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $purchaseOrders->links() }}
    </div>
@endsection
