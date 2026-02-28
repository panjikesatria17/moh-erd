@extends('layouts.procurement')

@section('title', 'Price Trends')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Price Trend Analysis</h2>
        <p class="text-sm text-gray-500">Ringkasan statistik harga per produk berdasarkan histori harga.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3 text-right">Records</th>
                        <th class="px-4 py-3 text-right">Min Price</th>
                        <th class="px-4 py-3 text-right">Max Price</th>
                        <th class="px-4 py-3 text-right">Avg Price</th>
                        <th class="px-4 py-3 text-right">Range</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($trendRows as $row)
                        @php
                            $min = (float) $row->min_price;
                            $max = (float) $row->max_price;
                            $avg = (float) $row->avg_price;
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $row->product?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->product?->sku ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $row->records_count }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($min, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($max, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($avg, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($max - $min, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada data trend harga.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $trendRows->links() }}</div>
@endsection
