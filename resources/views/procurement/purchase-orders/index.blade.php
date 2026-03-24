@extends('layouts.procurement')

@section('title', 'Purchase Orders')

@section('content')
    <x-ui.hero
        class="mb-4"
        eyebrow="Procurement Workflow"
        title="Purchase Orders"
        description="Daftar PO yang diterbitkan oleh purchasing HO."
    />

    <x-ui.panel title="Daftar Purchase Order" subtitle="Monitoring PO lintas vendor" bodyClass="p-0">
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
                                <x-ui.status-pill
                                    :value="$po->status?->value ?? '-'"
                                    :classes="[
                                        'draft' => 'bg-slate-100 text-slate-700',
                                        'submitted' => 'bg-amber-100 text-amber-700',
                                        'approved' => 'bg-cyan-100 text-cyan-700',
                                        'processed' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                    ]"
                                />
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah($po->total_amount)</td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.action-link href="{{ route('ui.purchase-orders.download', $po) }}" title="Cetak / Download PDF" aria-label="Cetak / Download PDF" variant="blue-outline" size="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                        <path d="M6 9V3h12v6h-2V5H8v4H6zm10 4h2a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2v6h8v-6zm-6 4v-4h4v4h-4z"/>
                                    </svg>
                                </x-ui.action-link>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty-row :colspan="7" message="Belum ada data purchase order." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <div class="mt-4">
        {{ $purchaseOrders->links() }}
    </div>
@endsection
