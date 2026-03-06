@extends('layouts.procurement')

@section('title', 'Price History')

@section('content')
    @php
        $canManageMasterWrites = in_array(auth()->user()?->role?->value, [
            \App\Enums\UserRole::SUPER_ADMIN->value,
            \App\Enums\UserRole::PURCHASING->value,
        ], true);
        $formatMoneyInput = static fn ($value) => $value === null || $value === ''
            ? ''
            : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="text-xl font-semibold">Master Data - Price History</h2>
            <p class="text-sm text-gray-500">Kelola riwayat harga produk per vendor untuk referensi pembelian.</p>
        </div>
    </div>

    @if($canManageMasterWrites)
    <form method="POST" action="{{ $editPriceHistory ? route('ui.master-data.price-histories.update', $editPriceHistory) : route('ui.master-data.price-histories.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        @if($editPriceHistory)
            @method('PUT')
        @endif
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-700">{{ $editPriceHistory ? 'Edit Riwayat Harga' : 'Tambah Riwayat Harga' }}</h3>
            @if($editPriceHistory)
                <a href="{{ route('ui.master-data.price-histories.index') }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Batal Edit</a>
            @endif
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Produk</label>
                <select id="product_id" name="product_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $product)
                        <option
                            value="{{ $product->id }}"
                            data-base-price="{{ $product->selling_price ?? $product->government_price_cap ?? '' }}"
                            @selected((string) old('product_id', $editPriceHistory?->product_id) === (string) $product->id)
                        >
                            {{ $product->name }} ({{ $product->sku }})
                        </option>
                    @endforeach
                </select>
                <p id="price-cap-info" class="mt-1 text-xs text-gray-500">Harga Jual Dasar: -</p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Vendor (Opsional)</label>
                <select name="vendor_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">-- Harga Umum / Default --</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $editPriceHistory?->vendor_id) === (string) $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Harga</label>
                <input id="price" type="text" inputmode="decimal" name="price" value="{{ $formatMoneyInput(old('price', $editPriceHistory?->price)) }}" class="js-idr-input w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: 12.500,00 atau hitung dari margin %">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Margin Harga Satuan (%)</label>
                @php
                    $editBaseCap = $editPriceHistory?->product?->selling_price !== null
                        ? (float) $editPriceHistory->product->selling_price
                        : ($editPriceHistory?->product?->government_price_cap !== null ? (float) $editPriceHistory->product->government_price_cap : null);
                    $defaultEditMargin = ($editPriceHistory && $editBaseCap !== null && $editBaseCap != 0.0)
                        ? ((((float) $editPriceHistory->price - $editBaseCap) / $editBaseCap) * 100)
                        : null;
                @endphp
                <div class="flex items-center gap-2">
                    <input id="margin_percent" type="number" step="0.01" name="margin_percent" value="{{ old('margin_percent', $defaultEditMargin) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="contoh: 10 atau -5">
                    <button id="btn-calc-price" type="button" class="whitespace-nowrap rounded-md border border-emerald-300 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Hitung</button>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Efektif Tanggal</label>
                <input type="date" name="effective_at" value="{{ old('effective_at', optional($editPriceHistory?->effective_at)->toDateString() ?? now()->toDateString()) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ $editPriceHistory ? 'Update Harga' : 'Simpan Harga' }}</button>
        </div>
    </form>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal Efektif</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3 text-right">Harga</th>
                        <th class="px-4 py-3 text-right">Margin (%)</th>
                        <th class="px-4 py-3">Input By</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
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
                            <td class="px-4 py-3 text-right">@rupiah($history->price)</td>
                            <td class="px-4 py-3 text-right">
                                @php
                                    $baseCap = $history->product?->selling_price !== null
                                        ? (float) $history->product->selling_price
                                        : ($history->product?->government_price_cap !== null ? (float) $history->product->government_price_cap : null);
                                    $computedMargin = ($baseCap !== null && $baseCap != 0.0)
                                        ? ((((float) $history->price - $baseCap) / $baseCap) * 100)
                                        : null;
                                @endphp
                                @if($computedMargin === null)
                                    <span class="text-gray-400">-</span>
                                @else
                                    {{ $computedMargin > 0 ? '+' : '' }}{{ number_format((float) $computedMargin, 2, ',', '.') }}%
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $history->creator?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($canManageMasterWrites)
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('ui.master-data.price-histories.edit', $history) }}" class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                                    <form method="POST" action="{{ route('ui.master-data.price-histories.destroy', $history) }}" onsubmit="return confirm('Hapus riwayat harga ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-300 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Hapus</button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data riwayat harga.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $priceHistories->links() }}
    </div>

    <script>
        (function () {
            const productSelect = document.getElementById('product_id');
            const priceInput = document.getElementById('price');
            const marginInput = document.getElementById('margin_percent');
            const calcButton = document.getElementById('btn-calc-price');
            const info = document.getElementById('price-cap-info');

            if (!productSelect || !priceInput || !marginInput || !calcButton || !info) {
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

            const getSelectedPriceCap = () => {
                const selected = productSelect.options[productSelect.selectedIndex];
                if (!selected) {
                    return null;
                }

                return parseNumber(selected.getAttribute('data-base-price'));
            };

            const renderCapInfo = () => {
                const cap = getSelectedPriceCap();
                if (cap === null) {
                    info.textContent = 'Harga Jual Dasar: -';
                    return;
                }

                info.textContent = `Harga Jual Dasar: ${cap.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
            };

            calcButton.addEventListener('click', function () {
                const cap = getSelectedPriceCap();
                const margin = parseNumber(marginInput.value);

                if (cap === null || margin === null) {
                    return;
                }

                const calculated = cap * (1 + (margin / 100));
                priceInput.value = formatNumberId(calculated);
            });

            priceInput.addEventListener('blur', function () {
                const parsed = parseNumber(priceInput.value);
                if (parsed === null) {
                    return;
                }

                priceInput.value = formatNumberId(parsed);
            });

            const initialPrice = parseNumber(priceInput.value);
            if (initialPrice !== null) {
                priceInput.value = formatNumberId(initialPrice);
            }

            productSelect.addEventListener('change', renderCapInfo);
            renderCapInfo();
        })();
    </script>
@endsection
