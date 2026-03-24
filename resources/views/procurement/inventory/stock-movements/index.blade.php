@extends('layouts.procurement')

@section('title', 'Stock Movements')

@section('content')
    <x-ui.hero
        class="mb-4"
        eyebrow="Inventory & Distribution"
        title="Stock Movements"
        description="Riwayat mutasi stok berdasarkan gudang, produk, dan referensi transaksi."
    />

    <x-ui.panel title="Riwayat Mutasi Stok" subtitle="Perubahan stok per transaksi" bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Gudang</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3">Created By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($stockMovements as $movement)
                        <tr>
                            <td class="px-4 py-3">{{ optional($movement->movement_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $movement->warehouse?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $movement->product?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $movement->product?->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.status-pill
                                    :value="$movement->type?->value ?? '-'"
                                    :classes="[
                                        'in' => 'bg-emerald-100 text-emerald-700',
                                        'out' => 'bg-rose-100 text-rose-700',
                                        'adjustment' => 'bg-amber-100 text-amber-700',
                                    ]"
                                />
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $movement->quantity, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $movement->balance_after, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $movement->creator?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <x-ui.table-empty-row :colspan="8" message="Belum ada data stock movement." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <div class="mt-4">{{ $stockMovements->links() }}</div>
@endsection
