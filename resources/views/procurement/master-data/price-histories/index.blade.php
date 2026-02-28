@extends('layouts.procurement')

@section('title', 'Price History')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Master Data - Price History</h2>
            <p class="text-sm text-gray-500">Kelola riwayat harga produk per vendor untuk referensi pembelian.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('ui.master-data.price-histories.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Tambah Riwayat Harga</h3>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Produk</label>
                <select name="product_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Vendor (Opsional)</label>
                <select name="vendor_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">-- Harga Umum / Default --</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Harga</label>
                <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Efektif Tanggal</label>
                <input type="date" name="effective_at" value="{{ old('effective_at', now()->toDateString()) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan Harga</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal Efektif</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3 text-right">Harga</th>
                        <th class="px-4 py-3">Input By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($priceHistories as $history)
                        <tr>
                            <td class="px-4 py-3">{{ optional($history->effective_at)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $history->product?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $history->product?->sku ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $history->vendor?->name ?? 'Default / Umum' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $history->price, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $history->creator?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data riwayat harga.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $priceHistories->links() }}
    </div>
@endsection
