@extends('layouts.procurement')

@section('title', 'Price Trends')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Price Trend Analysis</h2>
        <p class="text-sm text-gray-500">Analisis pergerakan harga produk berdasarkan histori harga pada periode tertentu.</p>
    </div>

    <form method="GET" action="{{ route('ui.price-trends.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-3">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-5">
            <select name="product_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Semua Produk</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected((int) ($selectedProductId ?? 0) === (int) $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
            <select name="vendor_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Semua Vendor</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((int) ($selectedVendorId ?? 0) === (int) $vendor->id)>{{ $vendor->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">Terapkan</button>
                <a href="{{ route('ui.price-trends.index') }}" class="rounded-md border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </div>
    </form>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Produk</p>
            <p class="mt-1 text-xl font-semibold text-gray-900">{{ $summary['total_products'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Total Record</p>
            <p class="mt-1 text-xl font-semibold text-gray-900">{{ $summary['total_records'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Avg Trend</p>
            <p class="mt-1 text-xl font-semibold text-indigo-700">{{ number_format((float) ($summary['avg_trend_percent'] ?? 0), 2, ',', '.') }}%</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Produk Naik</p>
            <p class="mt-1 text-xl font-semibold text-emerald-700">{{ $summary['products_up'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Produk Turun</p>
            <p class="mt-1 text-xl font-semibold text-rose-700">{{ $summary['products_down'] ?? 0 }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3 text-right">Records</th>
                        <th class="px-4 py-3 text-right">Vendors</th>
                        <th class="px-4 py-3 text-right">Min Price</th>
                        <th class="px-4 py-3 text-right">Max Price</th>
                        <th class="px-4 py-3 text-right">Avg Price</th>
                        <th class="px-4 py-3 text-right">Range</th>
                        <th class="px-4 py-3 text-right">Trend</th>
                        <th class="px-4 py-3 text-right">Volatility</th>
                        <th class="px-4 py-3">Last Update</th>
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
                            <td class="px-4 py-3 text-right">{{ $row->vendors_count }}</td>
                            <td class="px-4 py-3 text-right">@rupiah($min)</td>
                            <td class="px-4 py-3 text-right">@rupiah($max)</td>
                            <td class="px-4 py-3 text-right">@rupiah($avg)</td>
                            <td class="px-4 py-3 text-right">@rupiah($max - $min)</td>
                            <td class="px-4 py-3 text-right font-medium {{ (float) $row->trend_percent > 0 ? 'text-emerald-700' : ((float) $row->trend_percent < 0 ? 'text-rose-700' : 'text-gray-700') }}">
                                {{ number_format((float) $row->trend_percent, 2, ',', '.') }}%
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $row->volatility_percent, 2, ',', '.') }}%</td>
                            <td class="px-4 py-3">{{ optional($row->last_effective_at)->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">Belum ada data trend harga pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $trendRows->links() }}</div>
@endsection
