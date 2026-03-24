@extends('layouts.procurement')

@section('title', 'Price Trends')

@section('content')
    <x-ui.hero
        class="mb-4"
        eyebrow="Analytics & Compliance"
        title="Price Trend Analysis"
        description="Analisis pergerakan harga produk berdasarkan histori harga pada periode tertentu."
    />

    <x-ui.panel class="mb-4" title="Filter Price Trend">
    <form method="GET" action="{{ route('ui.price-trends.index') }}" class="">
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
    </x-ui.panel>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5">
        <x-ui.stat-card label="Produk" value="{{ $summary['total_products'] ?? 0 }}" />
        <x-ui.stat-card label="Total Record" value="{{ $summary['total_records'] ?? 0 }}" />
        <x-ui.stat-card label="Avg Trend" value="{{ number_format((float) ($summary['avg_trend_percent'] ?? 0), 2, ',', '.') }}%" class="border-indigo-200 bg-indigo-50" />
        <x-ui.stat-card label="Produk Naik" value="{{ $summary['products_up'] ?? 0 }}" class="border-emerald-200 bg-emerald-50" />
        <x-ui.stat-card label="Produk Turun" value="{{ $summary['products_down'] ?? 0 }}" class="border-rose-200 bg-rose-50" />
    </div>

    <x-ui.panel title="Trend Harga Produk" subtitle="Ringkasan min/max/avg dan volatilitas harga" bodyClass="p-0">
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
    </x-ui.panel>

    <div class="mt-4">{{ $trendRows->links() }}</div>
@endsection
