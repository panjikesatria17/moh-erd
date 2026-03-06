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
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($purchaseOrders as $po)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                <div>{{ $po->number }}</div>
                                @if(((int) ($po->ad_hoc_items_count ?? 0)) > 0)
                                    <div class="mt-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-800">NON KATALOG</div>
                                @endif
                                @if(str_contains((string) ($po->notes ?? ''), '[BARANG TAMBAHAN]'))
                                    <div class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800">ITEM TAMBAHAN</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $po->purchaseRequest?->number ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $po->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($po->order_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium">{{ $po->status?->value }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah($po->total_amount)</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('ui.purchase-orders.download', $po) }}" title="Cetak / Download PDF" aria-label="Cetak / Download PDF" class="inline-flex items-center justify-center rounded-md border border-blue-300 p-1.5 text-blue-700 hover:bg-blue-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                        <path d="M6 9V3h12v6h-2V5H8v4H6zm10 4h2a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2v6h8v-6zm-6 4v-4h4v4h-4z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data purchase order.</td>
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
