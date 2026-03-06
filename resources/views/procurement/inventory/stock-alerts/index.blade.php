@extends('layouts.procurement')

@section('title', 'Stock Alerts')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Stock Alerts</h2>
        <p class="text-sm text-gray-500">Notifikasi stok menipis dan status penyelesaiannya.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Gudang</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3 text-right">Current</th>
                        <th class="px-4 py-3 text-right">Minimum</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Resolved By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($stockAlerts as $alert)
                        <tr>
                            <td class="px-4 py-3">{{ $alert->warehouse?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $alert->product?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $alert->product?->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $alert->current_balance, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $alert->minimum_stock_level, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $alert->is_resolved ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $alert->is_resolved ? 'Resolved' : 'Open' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $alert->resolver?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada stock alert.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $stockAlerts->links() }}</div>
@endsection
