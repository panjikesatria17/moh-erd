@extends('layouts.procurement')

@section('title', 'Stock Alerts')

@section('content')
    <x-ui.hero
        class="mb-4"
        eyebrow="Inventory & Distribution"
        title="Stock Alerts"
        description="Notifikasi stok menipis dan status penyelesaiannya."
    />

    <x-ui.panel title="Daftar Alert Stok" subtitle="Monitoring kondisi stok minimum" bodyClass="p-0">
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
                                <x-ui.status-pill
                                    :value="$alert->is_resolved ? 'resolved' : 'open'"
                                    :classes="[
                                        'resolved' => 'bg-emerald-100 text-emerald-700',
                                        'open' => 'bg-rose-100 text-rose-700',
                                    ]"
                                />
                            </td>
                            <td class="px-4 py-3">{{ $alert->resolver?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <x-ui.table-empty-row :colspan="7" message="Belum ada stock alert." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <div class="mt-4">{{ $stockAlerts->links() }}</div>
@endsection
