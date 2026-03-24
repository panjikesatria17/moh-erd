@extends('layouts.procurement')

@section('title', 'Master Data Product')

@section('content')
    @php
        $authRole = auth()->user()?->role;
        $currentRoleRaw = is_object($authRole) ? ($authRole->value ?? null) : $authRole;
        $canManageMasterWrites = in_array($currentRoleRaw, [
            \App\Enums\UserRole::SUPER_ADMIN->value,
            \App\Enums\UserRole::PURCHASING->value,
            \App\Enums\UserRole::ADMIN->value,
            \App\Enums\UserRole::OWNER->value,
            'super_admin',
            'purchasing',
            'admin',
            'owner',
        ], true);
        $formatMoneyInput = static fn ($value) => $value === null || $value === ''
            ? ''
            : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    @endphp

    <x-ui.hero
        class="mb-4"
        eyebrow="Master Data"
        title="Master Data - Products"
        description="Kelola produk, satuan, kategori, dan batas harga pemerintah."
    >
        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
            <x-ui.action-link href="{{ route('ui.master-data.products.index') }}" title="Refresh Awal" aria-label="Refresh Awal" variant="outline" size="icon" class="p-2!">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                    <path d="M12 6a6 6 0 0 1 5.232 3.067 1 1 0 0 0 1.736-.992A8 8 0 1 0 20 12a1 1 0 1 0-2 0 6 6 0 1 1-6-6z"/>
                    <path d="M20.707 4.293a1 1 0 0 0-1.414 0L17 6.586V4a1 1 0 1 0-2 0v5a1 1 0 0 0 1 1h5a1 1 0 1 0 0-2h-2.586l2.293-2.293a1 1 0 0 0 0-1.414z"/>
                </svg>
            </x-ui.action-link>
            <x-ui.action-link href="{{ route('ui.master-data.products.export') }}" title="Export Excel" aria-label="Export Excel" variant="blue-outline" size="icon" class="p-2!">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                    <path d="M12 3a1 1 0 0 1 1 1v8.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4.004 4.004a1 1 0 0 1-1.414 0L7.285 11.71a1 1 0 0 1 1.414-1.414L11 12.586V4a1 1 0 0 1 1-1z"/>
                    <path d="M5 15a1 1 0 0 1 1 1v2h12v-2a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1z"/>
                </svg>
            </x-ui.action-link>
            <x-ui.action-link href="{{ route('ui.master-data.products.export-pdf', request()->only(['scope', 'q'])) }}" title="Download PDF" aria-label="Download PDF" variant="rose-outline" size="icon" class="p-2!">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                    <path d="M7 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9.414a2 2 0 0 0-.586-1.414l-4.414-4.414A2 2 0 0 0 12.586 3H7zm5 1.5V9h4.5L12 4.5z"/>
                    <path d="M8.5 12a1 1 0 0 1 1-1h2.25a2.25 2.25 0 1 1 0 4.5H10.5V17a1 1 0 1 1-2 0v-5zm2 2.5h1.25a.25.25 0 0 0 0-.5H10.5v.5zm5-2.5A1.5 1.5 0 0 1 17 13.5V17a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-.5a.5.5 0 0 0-1 0 1 1 0 1 1-2 0 2.5 2.5 0 0 1 2.5-2.5z"/>
                </svg>
            </x-ui.action-link>
            @if($canManageMasterWrites)
                <form method="POST" action="{{ route('ui.master-data.products.import') }}" enctype="multipart/form-data" class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:flex-nowrap">
                    @csrf
                    <input type="file" name="excel_file" accept=".xlsx,.xls" class="w-full sm:w-52 rounded-md border border-gray-300 px-2 py-1.5 text-xs" required>
                    <button type="submit" title="Import Excel" aria-label="Import Excel" class="inline-flex items-center justify-center rounded-md bg-indigo-600 p-2 text-white hover:bg-indigo-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                            <path d="M12 3a1 1 0 0 1 1 1v8.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4.004 4.004a1 1 0 0 1-1.414 0L7.285 11.71a1 1 0 0 1 1.414-1.414L11 12.586V4a1 1 0 0 1 1-1z"/>
                            <path d="M5 15a1 1 0 0 1 1 1v2h12v-2a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1z"/>
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </x-ui.hero>

    <x-ui.panel class="mb-4" title="Filter Produk">
    <form method="GET" action="{{ route('ui.master-data.products.index') }}" class="">
        <div class="flex flex-wrap items-center gap-2">
            <select name="scope" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="all" @selected(($scope ?? 'all') === 'all')>Semua Produk</option>
                <option value="catalog" @selected(($scope ?? 'all') === 'catalog')>Katalog</option>
                <option value="ad_hoc" @selected(($scope ?? 'all') === 'ad_hoc')>Non Katalog</option>
            </select>
            <input
                type="text"
                name="q"
                value="{{ $keyword ?? request('q') }}"
                placeholder="Cari SKU, nama produk, unit, atau vendor"
                class="w-full sm:max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm"
            >
            <button type="submit" class="w-full sm:w-auto rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">Filter</button>
            @if((($scope ?? 'all') !== 'all') || (($keyword ?? '') !== ''))
                <a href="{{ route('ui.master-data.products.index') }}" class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-2 text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            @endif
        </div>
    </form>
    </x-ui.panel>

    @if($canManageMasterWrites)
    <form method="POST" action="{{ $editProduct ? route('ui.master-data.products.update', $editProduct) : route('ui.master-data.products.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        @if($editProduct)
            @method('PUT')
        @endif
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-700">{{ $editProduct ? 'Edit Produk' : 'Tambah Produk' }}</h3>
            @if($editProduct)
                <a href="{{ route('ui.master-data.products.index') }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Batal Edit</a>
            @endif
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">SKU</label>
                <input type="text" name="sku" value="{{ old('sku', $editProduct?->sku) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama Produk</label>
                <input type="text" name="name" value="{{ old('name', $editProduct?->name) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Kategori</label>
                <select name="product_category_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('product_category_id', $editProduct?->product_category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Vendor</label>
                <select name="vendor_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">-- Pilih Vendor --</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $editProduct?->vendor_id) === (string) $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Unit</label>
                <input type="text" name="unit" value="{{ old('unit', $editProduct?->unit) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="kg / pcs / liter" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">PCS per BOX (Opsional)</label>
                <input type="text" inputmode="decimal" name="pcs_per_box" value="{{ $formatMoneyInput(old('pcs_per_box', $editProduct?->pcs_per_box)) }}" class="js-idr-input w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: 12">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">PCS per PACK (Opsional)</label>
                <input type="text" inputmode="decimal" name="pcs_per_pack" value="{{ $formatMoneyInput(old('pcs_per_pack', $editProduct?->pcs_per_pack)) }}" class="js-idr-input w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: 6">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Total Inventory (Qty)</label>
                <input type="number" step="0.01" min="0" name="total_inventory" value="{{ old('total_inventory', $editProduct?->total_inventory ?? 0) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: 100">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Harga Beli</label>
                <input type="text" inputmode="decimal" name="purchase_price" value="{{ $formatMoneyInput(old('purchase_price', $editProduct?->purchase_price)) }}" class="js-idr-input w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: 12.500,00">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Harga Jual (Dasar Margin)</label>
                <input id="selling_price" type="text" inputmode="decimal" name="selling_price" value="{{ $formatMoneyInput(old('selling_price', $editProduct?->selling_price)) }}" class="js-idr-input w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: 15.000,00">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Harga Maksimal Pemerintah</label>
                <input id="government_price_cap" type="text" inputmode="decimal" name="government_price_cap" value="{{ $formatMoneyInput(old('government_price_cap', $editProduct?->government_price_cap)) }}" class="js-idr-input w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: 16.000,00">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Selisih Harga (%)</label>
                <input id="price_variance_percent" type="number" step="0.01" name="price_variance_percent" value="{{ old('price_variance_percent', $editProduct?->price_variance_percent) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="contoh: 10 atau -5">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Selisih Harga (Rp)</label>
                <input id="price_variance_amount" type="text" inputmode="decimal" name="price_variance_amount" value="{{ $formatMoneyInput(old('price_variance_amount', $editProduct?->price_variance_amount)) }}" class="js-idr-input w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: 1.000,00 atau -500,00">
            </div>
            <div class="md:col-span-2 xl:col-span-3">
                <p class="text-xs text-gray-500">Harga acuan akhir dihitung sebagai: <strong>Harga Jual + Selisih Rp + (Harga Jual x Selisih %)</strong>. Jika harga jual kosong, sistem fallback ke Harga Maksimal Pemerintah.</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button id="btn-calc-amount" type="button" class="rounded-md border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50">Hitung Rp dari %</button>
                    <button id="btn-calc-percent" type="button" class="rounded-md border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Hitung % dari Rp</button>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Minimum Stock Level</label>
                <input type="number" step="0.01" min="0" name="minimum_stock_level" value="{{ old('minimum_stock_level', $editProduct?->minimum_stock_level ?? 0) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Reorder Stock Level</label>
                <input type="number" step="0.01" min="0" name="reorder_stock_level" value="{{ old('reorder_stock_level', $editProduct?->reorder_stock_level ?? 0) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $editProduct ? (int) $editProduct->is_active : 1) ? 'checked' : '' }}>
                    Aktif
                </label>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ $editProduct ? 'Update Produk' : 'Simpan Produk' }}</button>
        </div>
    </form>
    @endif

    <div class="mb-5 overflow-hidden rounded-xl border border-rose-200 bg-white">
        <div class="border-b border-rose-200 bg-rose-50 px-4 py-3">
            <h3 class="text-sm font-semibold text-rose-900">Review Produk Non Katalog</h3>
            <p class="text-xs text-rose-700">Produk ad-hoc dari vendor external/non tetap. Promosikan jika sudah jadi item katalog permanen.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-[11px]">
                <thead class="bg-gray-50 text-left text-[11px] uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Nama Produk</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Kategori Sekarang</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($adHocProducts ?? collect()) as $adHocProduct)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $adHocProduct->sku }}</td>
                            <td class="px-4 py-3">
                                <div>{{ $adHocProduct->name }}</div>
                                <div class="mt-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-800">NON KATALOG</div>
                            </td>
                            <td class="px-4 py-3">{{ $adHocProduct->unit }}</td>
                            <td class="px-4 py-3">{{ $adHocProduct->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $adHocProduct->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($canManageMasterWrites)
                                <form method="POST" action="{{ route('ui.master-data.products.promote-ad-hoc', $adHocProduct) }}" class="flex flex-wrap items-center justify-end gap-2">
                                    @csrf
                                    <select name="product_category_id" class="rounded-md border border-gray-300 px-2 py-1 text-xs" required>
                                        <option value="">Kategori Final</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected((int) $adHocProduct->product_category_id === (int) $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="vendor_id" class="rounded-md border border-gray-300 px-2 py-1 text-xs">
                                        <option value="">Tanpa Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" @selected((int) ($adHocProduct->vendor_id ?? 0) === (int) $vendor->id)>{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" inputmode="decimal" name="government_price_cap" value="{{ $formatMoneyInput(old('government_price_cap', $adHocProduct->government_price_cap)) }}" placeholder="Price Cap" class="js-idr-input w-full sm:w-28 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                    <button type="submit" class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">Promote</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">Tidak ada produk non katalog yang menunggu review.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManageMasterWrites)
    <form method="POST" action="{{ $editCategory ? route('ui.master-data.product-categories.update', $editCategory) : route('ui.master-data.product-categories.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        @if($editCategory)
            @method('PUT')
        @endif
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-700">{{ $editCategory ? 'Edit Kategori Produk' : 'Tambah Kategori Produk' }}</h3>
            @if($editCategory)
                <a href="{{ route('ui.master-data.products.index') }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Batal Edit</a>
            @endif
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama Kategori</label>
                <input type="text" name="name" value="{{ old('name', $editCategory?->name) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Deskripsi</label>
                <input type="text" name="description" value="{{ old('description', $editCategory?->description) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Opsional">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">{{ $editCategory ? 'Update Kategori' : 'Simpan Kategori' }}</button>
        </div>

        <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2">Kategori</th>
                        <th class="px-3 py-2">Deskripsi</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($categories as $category)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $category->name }}</td>
                            <td class="px-3 py-2">{{ $category->description ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('ui.master-data.product-categories.edit', $category) }}" class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-4 text-center text-gray-500">Belum ada kategori.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3 text-right">Harga Beli</th>
                        <th class="px-4 py-3 text-right">Harga Jual</th>
                        <th class="px-4 py-3 text-right">Price Cap</th>
                        <th class="px-4 py-3 text-right">Selisih Harga (Rp)</th>
                        <th class="px-4 py-3 text-right">Selisih (%)</th>
                        <th class="px-4 py-3 text-right">Harga Acuan Akhir</th>
                        <th class="px-4 py-3 text-right">Total Inventory</th>
                        <th class="px-4 py-3 text-right">Nilai Inventory</th>
                        <th class="px-4 py-3 text-right">Min/Reorder</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        @php
                            $inventoryQty = (float) ($inventoryQtyByProduct[$product->id] ?? 0);
                            $inventoryValue = (float) ($inventoryValueByProduct[$product->id] ?? 0);
                            $basePrice = $product->selling_price !== null
                                ? (float) $product->selling_price
                                : ($product->government_price_cap !== null ? (float) $product->government_price_cap : null);
                            $varianceAmount = (float) ($product->price_variance_amount ?? 0);
                            $variancePercent = (float) ($product->price_variance_percent ?? 0);
                            $percentAdjustment = $basePrice !== null ? ($basePrice * $variancePercent / 100) : null;
                            $finalReferencePrice = $basePrice !== null ? ($basePrice + $varianceAmount + (float) $percentAdjustment) : null;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $product->sku }}</td>
                            <td class="px-4 py-3">
                                <div>{{ $product->name }}</div>
                                @if($product->is_ad_hoc)
                                    <div class="mt-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-800">NON KATALOG</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $product->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $product->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $product->unit }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">@rupiah($product->purchase_price)</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">@rupiah($product->selling_price)</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">@rupiah($product->government_price_cap)</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($product->price_variance_amount === null)
                                    <span class="text-gray-400">-</span>
                                @else
                                    <span class="font-medium">{{ (float) $product->price_variance_amount > 0 ? '+' : '' }}@rupiah($product->price_variance_amount)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($product->price_variance_percent === null)
                                    <span class="text-gray-400">-</span>
                                @else
                                    <span class="font-medium">{{ (float) $product->price_variance_percent > 0 ? '+' : '' }}{{ number_format((float) $product->price_variance_percent, 2, ',', '.') }}%</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($finalReferencePrice === null)
                                    <span class="text-gray-400">-</span>
                                @else
                                    <span class="font-semibold">@rupiah($finalReferencePrice)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-medium">{{ number_format($inventoryQtyByProduct[$product->id] ?? 0, 2, ',', '.') }}</span>
                                <span class="text-[11px] text-gray-500">{{ $product->unit }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold whitespace-nowrap">@rupiah($inventoryValueByProduct[$product->id] ?? 0)</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $product->minimum_stock_level, 2, ',', '.') }} / {{ number_format((float) $product->reorder_stock_level, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-medium {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($canManageMasterWrites)
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('ui.master-data.products.edit', $product) }}" class="rounded-md border border-gray-300 px-2.5 py-1 text-[11px] font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                                    <form method="POST" action="{{ route('ui.master-data.products.destroy', $product) }}" onsubmit="return confirm('Hapus data produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-300 px-2.5 py-1 text-[11px] font-medium text-red-700 hover:bg-red-50">Hapus</button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-4 py-8 text-center text-gray-500">Belum ada data produk.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-amber-50">
                    <tr>
                        <td colspan="12" class="px-4 py-3 text-right text-[11px] font-semibold uppercase text-amber-800">Total Value Aset (sesuai filter)</td>
                        <td class="px-4 py-3 text-right text-[11px] font-bold text-amber-900 whitespace-nowrap">@rupiah($totalAssetValue ?? 0)</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>

    <script>
        (function () {
            const sellingPriceInput = document.getElementById('selling_price');
            const govCapInput = document.getElementById('government_price_cap');
            const percentInput = document.getElementById('price_variance_percent');
            const amountInput = document.getElementById('price_variance_amount');
            const calcAmountButton = document.getElementById('btn-calc-amount');
            const calcPercentButton = document.getElementById('btn-calc-percent');

            if (!percentInput || !amountInput || !calcAmountButton || !calcPercentButton) {
                return;
            }

            const parseNumber = (value) => {
                const raw = String(value ?? '').trim();
                if (raw === '') {
                    return null;
                }

                let normalized = raw.replace(/\s+/g, '');
                const hasDot = normalized.includes('.');
                const hasComma = normalized.includes(',');

                if (hasDot && hasComma) {
                    normalized = normalized.replace(/\./g, '').replace(/,/g, '.');
                } else if (hasDot && /^-?\d{1,3}(\.\d{3})+$/.test(normalized)) {
                    normalized = normalized.replace(/\./g, '');
                } else if (hasComma) {
                    normalized = normalized.replace(/,/g, '.');
                }

                normalized = normalized.replace(/[^0-9.\-]/g, '');
                if (normalized === '' || normalized === '-' || normalized === '.' || normalized === '-.') {
                    return null;
                }

                if ((normalized.match(/\./g) || []).length > 1) {
                    const parts = normalized.split('.');
                    const decimalPart = parts.pop();
                    normalized = `${parts.join('')}.${decimalPart}`;
                }

                const parsed = Number.parseFloat(normalized);
                return Number.isFinite(parsed) ? parsed : null;
            };

            const formatNumberId = (value) => value.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });

            document.querySelectorAll('.js-idr-input').forEach((input) => {
                input.addEventListener('blur', function () {
                    const parsed = parseNumber(input.value);
                    if (parsed === null) {
                        return;
                    }

                    input.value = formatNumberId(parsed);
                });

                const initial = parseNumber(input.value);
                if (initial !== null) {
                    input.value = formatNumberId(initial);
                }
            });

            const resolveBase = () => {
                const sellingPrice = sellingPriceInput ? parseNumber(sellingPriceInput.value) : null;
                if (sellingPrice !== null) {
                    return sellingPrice;
                }

                return govCapInput ? parseNumber(govCapInput.value) : null;
            };

            calcAmountButton.addEventListener('click', function () {
                const base = resolveBase();
                const percent = parseNumber(percentInput.value);

                if (base === null || percent === null) {
                    return;
                }

                amountInput.value = formatNumberId((base * percent) / 100);
            });

            calcPercentButton.addEventListener('click', function () {
                const base = resolveBase();
                const amount = parseNumber(amountInput.value);

                if (base === null || amount === null || base === 0) {
                    return;
                }

                percentInput.value = ((amount / base) * 100).toFixed(2);
            });
        })();
    </script>
@endsection
