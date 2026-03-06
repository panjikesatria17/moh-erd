@extends('layouts.procurement')

@section('title', 'Stock Movements')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Stock Movements</h2>
        <p class="text-sm text-gray-500">Riwayat mutasi stok berdasarkan gudang, produk, dan referensi transaksi.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
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
                            <td class="px-4 py-3">{{ $movement->type?->value ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $movement->quantity, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $movement->balance_after, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $movement->creator?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Belum ada data stock movement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $stockMovements->links() }}</div>
@endsection
