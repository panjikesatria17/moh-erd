@extends('layouts.procurement')

@section('title', 'Master Data Product')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Master Data - Products</h2>
            <p class="text-sm text-gray-500">Kelola produk, satuan, kategori, dan batas harga pemerintah.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('ui.master-data.products.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Tambah Produk</h3>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">SKU</label>
                <input type="text" name="sku" value="{{ old('sku') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama Produk</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Kategori</label>
                <select name="product_category_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('product_category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Unit</label>
                <input type="text" name="unit" value="{{ old('unit') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="kg / pcs / liter" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Harga Maksimal Pemerintah</label>
                <input type="number" step="0.01" min="0" name="government_price_cap" value="{{ old('government_price_cap') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Minimum Stock Level</label>
                <input type="number" step="0.01" min="0" name="minimum_stock_level" value="{{ old('minimum_stock_level', 0) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Reorder Stock Level</label>
                <input type="number" step="0.01" min="0" name="reorder_stock_level" value="{{ old('reorder_stock_level', 0) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    Aktif
                </label>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan Produk</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3 text-right">Price Cap</th>
                        <th class="px-4 py-3 text-right">Min/Reorder</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $product->sku }}</td>
                            <td class="px-4 py-3">{{ $product->name }}</td>
                            <td class="px-4 py-3">{{ $product->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $product->unit }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $product->government_price_cap, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $product->minimum_stock_level, 2, ',', '.') }} / {{ number_format((float) $product->reorder_stock_level, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data produk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
@endsection
