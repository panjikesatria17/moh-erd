@extends('layouts.procurement')

@section('title', 'Purchase Requests')

@section('content')
    @php
        $authRole = auth()->user()?->role;
        $currentRoleRaw = is_object($authRole) ? ($authRole->value ?? null) : $authRole;
        $currentRole = in_array($currentRoleRaw, ['admin', 'owner'], true) ? 'super_admin' : $currentRoleRaw;
        $canCreatePurchaseRequest = in_array($currentRole, ['super_admin', 'sppg_user'], true);
        $canApprovePurchaseRequest = in_array($currentRole, ['super_admin', 'owner'], true);
        $canGeneratePurchaseOrder = in_array($currentRole, ['super_admin', 'purchasing'], true);
        $canAssignRequester = in_array($currentRole, ['super_admin', 'purchasing'], true);
        $formatMoneyInput = static fn ($value) => $value === null || $value === ''
            ? ''
            : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
        $unitOptions = collect(['PCS', 'KG', 'GR', 'L', 'ML', 'PACK', 'BOX'])
            ->merge($products->pluck('unit')->filter()->map(fn ($unit) => strtoupper((string) $unit)))
            ->unique()
            ->values();
    @endphp

    <x-ui.hero
        class="mb-4"
        eyebrow="Procurement Workflow"
        title="Purchase Requests"
        description="Daftar permintaan pembelian dari seluruh SPPG."
    />

    @if($canCreatePurchaseRequest)
    <x-ui.panel class="mb-5" title="Create Purchase Request (Quick Form)">
    <form id="purchase-request-form" method="POST" action="{{ route('ui.purchase-requests.store') }}" class="">
        @csrf
        <input type="hidden" id="is_product_review_confirmed" name="is_product_review_confirmed" value="0">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">SPPG</label>
                <select name="sppg_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">Pilih SPPG</option>
                    @foreach($sppgs as $sppg)
                        <option value="{{ $sppg->id }}" @selected((string) old('sppg_id') === (string) $sppg->id)>{{ $sppg->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Needed Date</label>
                <input type="date" name="needed_date" value="{{ old('needed_date') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">PO Vendor (Optional)</label>
                <select id="vendor_id" name="vendor_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Use SPPG default vendor saat generate PO</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2 xl:col-span-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-amber-900">
                    <input id="is_additional" type="checkbox" name="is_additional" value="1" {{ old('is_additional') ? 'checked' : '' }}>
                    Barang tambahan / susulan dari PO yang sudah dicetak
                </label>
                <p class="mt-1 text-xs text-amber-800">Jika dicentang, item PR ini akan ditambahkan ke PO referensi agar tetap satu alur invoice.</p>
                <div class="mt-2">
                    <select id="additional_to_po_id" name="additional_to_po_id" class="w-full rounded-md border border-amber-300 px-3 py-2 text-sm" {{ old('is_additional') ? '' : 'disabled' }}>
                        <option value="">Pilih PO Referensi (wajib jika barang tambahan)</option>
                        @foreach(($referencePurchaseOrders ?? collect()) as $poRef)
                            <option value="{{ $poRef->id }}" @selected((string) old('additional_to_po_id') === (string) $poRef->id)>
                                {{ $poRef->number }} - {{ $poRef->sppg?->name ?? '-' }} - {{ $poRef->vendor?->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <div class="mb-2 flex items-center justify-between">
                    <label class="block text-xs font-medium text-gray-600">Products</label>
                    <div class="flex items-center gap-2">
                        <button type="button" id="add-item-row" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">+ Add Product</button>
                    </div>
                </div>

                @php
                    $oldItems = old('items', [[
                        'product_id' => '',
                        'ad_hoc_name' => '',
                        'ad_hoc_unit' => '',
                        'quantity' => '',
                        'requested_unit_price' => '',
                    ]]);
                @endphp

                <div id="pr-items-wrapper" class="space-y-2">
                    @foreach($oldItems as $index => $item)
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-6 md:grid-cols-12 pr-item-row">
                            <div class="sm:col-span-6 md:col-span-6">
                                <button type="button" class="open-product-selector-inline w-full rounded-md border border-indigo-300 px-3 py-2 text-left text-sm font-medium text-indigo-700 hover:bg-indigo-50">Pilih Produk</button>
                                <select name="items[{{ $index }}][product_id]" class="js-product-select hidden w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    <option value="">Pilih Product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-[11px] text-gray-500">Kosongkan jika item non katalog.</p>
                            </div>
                            <div class="sm:col-span-3 md:col-span-2">
                                <input type="text" name="items[{{ $index }}][ad_hoc_name]" value="{{ $item['ad_hoc_name'] ?? '' }}" placeholder="Nama item non katalog" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div class="sm:col-span-3 md:col-span-1">
                                <select name="items[{{ $index }}][ad_hoc_unit]" class="js-unit-select w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    <option value="">Satuan</option>
                                    @foreach($unitOptions as $unitOption)
                                        <option value="{{ $unitOption }}" @selected(strtoupper((string) ($item['ad_hoc_unit'] ?? '')) === strtoupper((string) $unitOption))>{{ $unitOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2 md:col-span-1">
                                <input type="number" step="0.01" min="0.01" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? '' }}" placeholder="Qty" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                            </div>
                            <div class="sm:col-span-4 md:col-span-2">
                                <input type="text" inputmode="decimal" name="items[{{ $index }}][requested_unit_price]" value="{{ $formatMoneyInput($item['requested_unit_price'] ?? '') }}" placeholder="Harga/satuan" class="js-unit-price w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                <p class="js-unit-price-hint mt-1 text-[11px] text-gray-500">Auto</p>
                            </div>
                            <div class="sm:col-span-4 md:col-span-2">
                                <input type="text" name="items[{{ $index }}][notes]" value="{{ $item['notes'] ?? '' }}" placeholder="Keterangan (misal: dipotong 8pt)" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div class="sm:col-span-1 md:col-span-1">
                                <button type="button" class="remove-item-row w-full rounded-md border border-red-200 px-2 py-2 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="mt-2 text-[11px] text-gray-500">Pilih produk dari tombol "Pilih Produk" di setiap baris, lalu review pada modal sebelum konfirmasi.</p>
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <label class="mb-1 block text-xs font-medium text-gray-600">Notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <div id="review-confirmed-badge" class="mb-2 hidden items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                Produk sudah direview
            </div>
            <div id="review-lock-notice" class="mb-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                Mohon review produk terlebih dahulu. Klik tombol <strong>Pilih Produk</strong> di baris item, lalu konfirmasi dengan tombol <strong>Sudah Sesuai</strong>.
            </div>
            <button id="create-pr-submit" type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400" disabled>Create PR</button>
        </div>
    </form>
    </x-ui.panel>
    @endif

    @php
        $productOptions = $products->map(function ($product) {
            $basePrice = $product->government_price_cap !== null ? (float) $product->government_price_cap : null;
            $varianceAmount = (float) ($product->price_variance_amount ?? 0);
            $variancePercent = (float) ($product->price_variance_percent ?? 0);
            $defaultUnitPrice = $basePrice !== null
                ? $basePrice + $varianceAmount + ($basePrice * $variancePercent / 100)
                : null;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => strtoupper((string) ($product->unit ?? 'PCS')),
                'pcs_per_box' => $product->pcs_per_box !== null ? (float) $product->pcs_per_box : null,
                'pcs_per_pack' => $product->pcs_per_pack !== null ? (float) $product->pcs_per_pack : null,
                'default_unit_price' => $defaultUnitPrice,
            ];
        })->values();
    @endphp

    <div id="product-selector-modal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="product-selector-title">
        <div class="max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <h4 id="product-selector-title" class="text-sm font-semibold text-gray-800">Laman Produk - Pilih Produk</h4>
                <button type="button" id="close-product-selector" class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50">Tutup</button>
            </div>
            <div class="max-h-[70vh] overflow-auto p-4">
                <div class="mb-3">
                    <input id="product-selector-search" type="text" placeholder="Cari nama produk atau SKU..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-gray-500">Tips: gunakan pencarian lalu pilih semua hasil yang tampil.</p>
                    <div class="flex items-center gap-2">
                        <span id="selector-selected-count" class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700">0 produk dipilih</span>
                        <button type="button" id="selector-select-visible" class="rounded-md border border-indigo-300 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">Pilih Semua Terlihat</button>
                        <button type="button" id="selector-clear-selection" class="rounded-md border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Hapus Pilihan</button>
                    </div>
                </div>
                <div class="mb-3 grid grid-cols-12 gap-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                    <div class="col-span-1">Pilih</div>
                    <div class="col-span-4">Produk</div>
                    <div class="col-span-2">SKU</div>
                    <div class="col-span-2">Satuan</div>
                    <div class="col-span-2">Qty</div>
                    <div class="col-span-1">Harga</div>
                </div>
                <div id="product-selector-list" class="space-y-2">
                    @foreach($products as $product)
                        <div class="selector-product-row grid grid-cols-12 items-center gap-2 rounded-lg border border-gray-200 p-2"
                            data-product-name="{{ strtolower($product->name) }}"
                            data-product-sku="{{ strtolower($product->sku) }}">
                            <div class="col-span-1">
                                <button type="button"
                                    class="selector-toggle-button w-full rounded-md border border-gray-300 px-2 py-1.5 text-[11px] font-semibold text-gray-700 hover:bg-gray-50"
                                    data-selected="0"
                                    data-product-id="{{ $product->id }}"
                                    data-product-name="{{ $product->name }}"
                                    data-product-sku="{{ $product->sku }}"
                                    aria-pressed="false">
                                    Pilih
                                </button>
                            </div>
                            <div class="col-span-4">
                                <p class="text-sm font-medium text-gray-800">{{ $product->name }}</p>
                            </div>
                            <div class="col-span-2 text-xs text-gray-600">{{ $product->sku }}</div>
                            <div class="col-span-2">
                                <select class="selector-unit w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs" data-base-unit="{{ strtoupper((string) ($product->unit ?? 'PCS')) }}">
                                    @foreach($unitOptions as $unitOption)
                                        <option value="{{ $unitOption }}" @selected(strtoupper((string) $product->unit) === strtoupper((string) $unitOption))>{{ $unitOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2">
                                <input type="number" min="0.01" step="0.01" class="selector-qty w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs" placeholder="0">
                            </div>
                            <div class="col-span-1">
                                <input type="text" inputmode="decimal" class="selector-unit-price w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs" placeholder="Auto / satuan">
                                <p class="selector-unit-price-hint mt-1 text-[11px] text-gray-500">Harga per satuan terpilih.</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-4 py-3">
                <button type="button" id="review-selected-products" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">Review Pilihan</button>
            </div>
        </div>
    </div>

    <div id="selected-products-review-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="selected-products-review-title">
        <div class="w-full max-w-2xl rounded-xl bg-white shadow-2xl">
            <div class="border-b border-gray-200 px-4 py-3">
                <h4 id="selected-products-review-title" class="text-sm font-semibold text-gray-800">Konfirmasi Produk Terpilih</h4>
                <p class="mt-1 text-xs text-gray-500">Periksa kembali produk yang dipilih sebelum dimasukkan ke Purchase Request.</p>
            </div>
            <div class="max-h-[55vh] overflow-auto px-4 py-3">
                <div id="selected-products-review-total" class="mb-2 rounded-md bg-gray-50 px-3 py-2 text-xs font-medium text-gray-700"></div>
                <div id="selected-products-review-list" class="space-y-2 text-sm text-gray-700"></div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-4 py-3">
                <button type="button" id="review-back-to-selector" class="rounded-md border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">Kembali</button>
                <button type="button" id="confirm-selected-products" class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700">Sudah Sesuai</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const itemsWrapper = document.getElementById('pr-items-wrapper');
            const purchaseRequestForm = document.getElementById('purchase-request-form');
            const createPrSubmitButton = document.getElementById('create-pr-submit');
            const reviewLockNotice = document.getElementById('review-lock-notice');
            const reviewConfirmedBadge = document.getElementById('review-confirmed-badge');
            const reviewConfirmedInput = document.getElementById('is_product_review_confirmed');
            const addItemButton = document.getElementById('add-item-row');
            const productSelectorModal = document.getElementById('product-selector-modal');
            const productSelectorSearchInput = document.getElementById('product-selector-search');
            const selectorSelectedCount = document.getElementById('selector-selected-count');
            const selectorSelectVisibleButton = document.getElementById('selector-select-visible');
            const selectorClearSelectionButton = document.getElementById('selector-clear-selection');
            const closeProductSelectorButton = document.getElementById('close-product-selector');
            const reviewSelectedProductsButton = document.getElementById('review-selected-products');
            const selectedProductsReviewModal = document.getElementById('selected-products-review-modal');
            const selectedProductsReviewTotal = document.getElementById('selected-products-review-total');
            const selectedProductsReviewList = document.getElementById('selected-products-review-list');
            const reviewBackToSelectorButton = document.getElementById('review-back-to-selector');
            const confirmSelectedProductsButton = document.getElementById('confirm-selected-products');
            const vendorSelect = document.getElementById('vendor_id');
            const additionalCheckbox = document.getElementById('is_additional');
            const additionalPoSelect = document.getElementById('additional_to_po_id');
            const products = {!! json_encode($productOptions) !!};
            const unitOptions = {!! json_encode($unitOptions->values()) !!};
            const priceLookup = {!! json_encode($priceLookup ?? []) !!};
            let reviewedSelections = [];

            const productsById = products.reduce((acc, product) => {
                acc[String(product.id)] = product;
                return acc;
            }, {});

            const resolveMasterPrice = (productId, vendorId) => {
                if (!productId) {
                    return null;
                }

                const vendorKey = vendorId ? `${productId}:${vendorId}` : null;
                const defaultKey = `${productId}:0`;

                if (vendorKey && Object.prototype.hasOwnProperty.call(priceLookup, vendorKey)) {
                    return Number.parseFloat(priceLookup[vendorKey]);
                }

                if (Object.prototype.hasOwnProperty.call(priceLookup, defaultKey)) {
                    return Number.parseFloat(priceLookup[defaultKey]);
                }

                const product = productsById[String(productId)];
                if (!product) {
                    return null;
                }

                const fallback = Number.parseFloat(product.default_unit_price);
                return Number.isFinite(fallback) ? fallback : null;
            };

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

            const setReviewConfirmedState = (isConfirmed) => {
                if (!reviewConfirmedInput) {
                    return;
                }

                reviewConfirmedInput.value = isConfirmed ? '1' : '0';

                if (createPrSubmitButton) {
                    createPrSubmitButton.disabled = !isConfirmed;
                }

                if (reviewLockNotice) {
                    reviewLockNotice.classList.toggle('hidden', isConfirmed);
                }

                if (reviewConfirmedBadge) {
                    reviewConfirmedBadge.classList.toggle('hidden', !isConfirmed);
                    reviewConfirmedBadge.classList.toggle('inline-flex', isConfirmed);
                }
            };

            const syncInlineProductButtons = () => {
                itemsWrapper.querySelectorAll('.pr-item-row').forEach((row) => {
                    const productButton = row.querySelector('.open-product-selector-inline');
                    const productSelect = row.querySelector('.js-product-select');
                    if (!(productButton instanceof HTMLButtonElement) || !(productSelect instanceof HTMLSelectElement)) {
                        return;
                    }

                    const selectedOption = productSelect.options[productSelect.selectedIndex];
                    if (selectedOption && selectedOption.value !== '') {
                        productButton.textContent = selectedOption.text;
                    } else {
                        productButton.textContent = 'Pilih Produk';
                    }
                });
            };

            const normalizeUnit = (unit) => String(unit ?? '').trim().toUpperCase();

            const updateFormRowUnitPriceHint = (row) => {
                const hint = row.querySelector('.js-unit-price-hint');
                if (!(hint instanceof HTMLElement)) {
                    return;
                }

                const productSelect = row.querySelector('.js-product-select');
                const unitSelect = row.querySelector('.js-unit-select');
                const selectedProduct = productSelect instanceof HTMLSelectElement
                    ? productsById[String(productSelect.value)] || null
                    : null;
                const unit = normalizeUnit(unitSelect instanceof HTMLSelectElement ? unitSelect.value : '')
                    || normalizeUnit(selectedProduct?.unit || '')
                    || 'SATUAN';

                hint.textContent = `Harga akan dipakai per ${unit}.`;
            };

            const updateSelectorRowUnitPriceHint = (row) => {
                const hint = row.querySelector('.selector-unit-price-hint');
                if (!(hint instanceof HTMLElement)) {
                    return;
                }

                const unitSelect = row.querySelector('.selector-unit');
                const unit = normalizeUnit(unitSelect instanceof HTMLSelectElement ? unitSelect.value : '') || 'SATUAN';
                hint.textContent = `Harga akan dipakai per ${unit}.`;
            };

            const convertPriceByUnit = (basePrice, baseUnit, targetUnit, product = null) => {
                const fromUnit = normalizeUnit(baseUnit);
                const toUnit = normalizeUnit(targetUnit);
                if (!Number.isFinite(basePrice) || fromUnit === '' || toUnit === '' || fromUnit === toUnit) {
                    return basePrice;
                }

                const pcsPerBox = Number.parseFloat(product?.pcs_per_box ?? '');
                const pcsPerPack = Number.parseFloat(product?.pcs_per_pack ?? '');

                if (fromUnit === 'BOX' && toUnit === 'PCS' && Number.isFinite(pcsPerBox) && pcsPerBox > 0) {
                    return basePrice / pcsPerBox;
                }

                if (fromUnit === 'PCS' && toUnit === 'BOX' && Number.isFinite(pcsPerBox) && pcsPerBox > 0) {
                    return basePrice * pcsPerBox;
                }

                if (fromUnit === 'PACK' && toUnit === 'PCS' && Number.isFinite(pcsPerPack) && pcsPerPack > 0) {
                    return basePrice / pcsPerPack;
                }

                if (fromUnit === 'PCS' && toUnit === 'PACK' && Number.isFinite(pcsPerPack) && pcsPerPack > 0) {
                    return basePrice * pcsPerPack;
                }

                if (fromUnit === 'BOX' && toUnit === 'PACK' && Number.isFinite(pcsPerBox) && Number.isFinite(pcsPerPack) && pcsPerBox > 0 && pcsPerPack > 0) {
                    return basePrice * (pcsPerPack / pcsPerBox);
                }

                if (fromUnit === 'PACK' && toUnit === 'BOX' && Number.isFinite(pcsPerBox) && Number.isFinite(pcsPerPack) && pcsPerBox > 0 && pcsPerPack > 0) {
                    return basePrice * (pcsPerBox / pcsPerPack);
                }

                const key = `${fromUnit}->${toUnit}`;
                const factorMap = {
                    'KG->GR': 1 / 1000,
                    'GR->KG': 1000,
                    'L->ML': 1 / 1000,
                    'ML->L': 1000,
                };

                if (!Object.prototype.hasOwnProperty.call(factorMap, key)) {
                    return basePrice;
                }

                return basePrice * factorMap[key];
            };

            const createUnitOptionsHtml = (selectedUnit = '') => {
                const selected = normalizeUnit(selectedUnit);
                return ['<option value="">Satuan</option>']
                    .concat(unitOptions.map((unit) => {
                        const current = normalizeUnit(unit);
                        const isSelected = current === selected ? 'selected' : '';
                        return `<option value="${unit}" ${isSelected}>${unit}</option>`;
                    }))
                    .join('');
            };

            const createItemRowMarkup = (index, selected = null) => {
                const selectedProductId = selected?.product_id ? String(selected.product_id) : '';
                const selectedQty = selected?.quantity ?? '';
                const selectedUnit = selected?.selected_unit ?? selected?.ad_hoc_unit ?? '';
                const selectedPrice = selected?.requested_unit_price !== null && selected?.requested_unit_price !== undefined && selected?.requested_unit_price !== ''
                    ? formatNumberId(Number(selected.requested_unit_price))
                    : '';

                const optionsHtml = ['<option value="">Pilih Product</option>']
                    .concat(products.map((product) => {
                        const isSelected = String(product.id) === selectedProductId ? 'selected' : '';
                        return `<option value="${product.id}" ${isSelected}>${product.name} (${product.sku})</option>`;
                    }))
                    .join('');

                return `
                    <div class="sm:col-span-6 md:col-span-6">
                        <button type="button" class="open-product-selector-inline w-full rounded-md border border-indigo-300 px-3 py-2 text-left text-sm font-medium text-indigo-700 hover:bg-indigo-50">Pilih Produk</button>
                        <select name="items[${index}][product_id]" class="js-product-select hidden w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            ${optionsHtml}
                        </select>
                        <p class="mt-1 text-[11px] text-gray-500">Kosongkan jika item non katalog.</p>
                    </div>
                    <div class="sm:col-span-3 md:col-span-2">
                        <input type="text" name="items[${index}][ad_hoc_name]" placeholder="Nama item non katalog" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-3 md:col-span-1">
                        <select name="items[${index}][ad_hoc_unit]" class="js-unit-select w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            ${createUnitOptionsHtml(selectedUnit)}
                        </select>
                    </div>
                    <div class="sm:col-span-2 md:col-span-1">
                        <input type="number" step="0.01" min="0.01" name="items[${index}][quantity]" value="${selectedQty}" placeholder="Qty" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    </div>
                    <div class="sm:col-span-4 md:col-span-2">
                        <input type="text" inputmode="decimal" name="items[${index}][requested_unit_price]" value="${selectedPrice}" placeholder="Harga/satuan" class="js-unit-price w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        <p class="js-unit-price-hint mt-1 text-[11px] text-gray-500">Auto</p>
                    </div>
                    <div class="sm:col-span-4 md:col-span-2">
                        <input type="text" name="items[${index}][notes]" placeholder="Keterangan (misal: dipotong 8pt)" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-1 md:col-span-1">
                        <button type="button" class="remove-item-row w-full rounded-md border border-red-200 px-2 py-2 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                    </div>
                `;
            };

            const applyAutoPriceToRow = (row, force = false) => {
                const productSelect = row.querySelector('.js-product-select');
                const unitSelect = row.querySelector('.js-unit-select');
                const unitPriceInput = row.querySelector('.js-unit-price');
                if (!productSelect || !unitPriceInput) {
                    return;
                }

                const selectedProduct = productsById[String(productSelect.value)] || null;
                const baseUnit = selectedProduct?.unit || '';
                if (unitSelect instanceof HTMLSelectElement && normalizeUnit(unitSelect.value) === '' && baseUnit !== '') {
                    unitSelect.value = baseUnit;
                }

                if (!force && unitPriceInput.value !== '') {
                    return;
                }

                const vendorId = vendorSelect?.value || '';
                const resolved = resolveMasterPrice(productSelect.value, vendorId);
                if (resolved === null) {
                    return;
                }

                const targetUnit = unitSelect instanceof HTMLSelectElement ? unitSelect.value : baseUnit;
                const convertedPrice = convertPriceByUnit(Number(resolved), baseUnit, targetUnit, selectedProduct);
                unitPriceInput.value = formatNumberId(convertedPrice);
                updateFormRowUnitPriceHint(row);
            };

            const bindUnitPriceFormatting = () => {
                itemsWrapper.querySelectorAll('.js-unit-price').forEach((input) => {
                    input.onblur = () => {
                        const parsed = parseNumber(input.value);
                        if (parsed === null) {
                            return;
                        }

                        input.value = formatNumberId(parsed);
                    };

                    const initial = parseNumber(input.value);
                    if (initial !== null) {
                        input.value = formatNumberId(initial);
                    }
                });
            };

            const createProductOptions = () => {
                return ['<option value="">Pilih Product</option>']
                    .concat(products.map((product) => `<option value="${product.id}">${product.name} (${product.sku})</option>`))
                    .join('');
            };

            const openModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const filterSelectorProducts = () => {
                const keyword = String(productSelectorSearchInput?.value ?? '').trim().toLowerCase();

                document.querySelectorAll('.selector-product-row').forEach((row) => {
                    const name = row.dataset.productName || '';
                    const sku = row.dataset.productSku || '';
                    const isVisible = keyword === '' || name.includes(keyword) || sku.includes(keyword);
                    row.classList.toggle('hidden', !isVisible);
                });
            };

            const selectVisibleProducts = () => {
                document.querySelectorAll('.selector-product-row').forEach((row) => {
                    if (row.classList.contains('hidden')) {
                        return;
                    }

                    setRowSelectedState(row, true);
                });

                updateSelectorSelectedCount();
            };

            const clearAllSelectedProducts = () => {
                document.querySelectorAll('#product-selector-list .selector-product-row').forEach((rowContainer) => {
                    setRowSelectedState(rowContainer, false);
                    const qtyInput = rowContainer?.querySelector('.selector-qty');
                    const priceInput = rowContainer?.querySelector('.selector-unit-price');

                    if (qtyInput instanceof HTMLInputElement) {
                        qtyInput.value = '';
                    }

                    if (priceInput instanceof HTMLInputElement) {
                        priceInput.value = '';
                    }
                });

                updateSelectorSelectedCount();
            };

            const closeModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const updateSelectorSelectedCount = () => {
                if (!selectorSelectedCount) {
                    return;
                }

                const selectedCount = document.querySelectorAll('#product-selector-list .selector-product-row[data-selected="1"]').length;
                selectorSelectedCount.textContent = `${selectedCount} produk dipilih`;
            };

            const setRowSelectedState = (row, isSelected) => {
                if (!row) {
                    return;
                }

                const button = row.querySelector('.selector-toggle-button');
                if (!(button instanceof HTMLButtonElement)) {
                    return;
                }

                row.dataset.selected = isSelected ? '1' : '0';
                button.dataset.selected = isSelected ? '1' : '0';
                button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                button.textContent = isSelected ? 'Dipilih' : 'Pilih';
                button.classList.toggle('border-indigo-500', isSelected);
                button.classList.toggle('bg-indigo-50', isSelected);
                button.classList.toggle('text-indigo-700', isSelected);
                button.classList.toggle('border-gray-300', !isSelected);
                button.classList.toggle('text-gray-700', !isSelected);
            };

            const applyAutoPriceToSelectorRow = (row, force = false) => {
                const button = row.querySelector('.selector-toggle-button');
                const unitSelect = row.querySelector('.selector-unit');
                const priceInput = row.querySelector('.selector-unit-price');
                if (!(button instanceof HTMLButtonElement) || !(priceInput instanceof HTMLInputElement)) {
                    return;
                }

                if (!force && String(priceInput.value).trim() !== '') {
                    return;
                }

                const productId = button.dataset.productId;
                if (!productId) {
                    return;
                }

                const basePrice = resolveMasterPrice(productId, vendorSelect?.value || '');
                if (basePrice === null) {
                    return;
                }

                const product = productsById[String(productId)] || null;
                const baseUnit = product?.unit || '';
                const targetUnit = unitSelect instanceof HTMLSelectElement ? unitSelect.value : baseUnit;
                const convertedPrice = convertPriceByUnit(Number(basePrice), baseUnit, targetUnit, product);
                priceInput.value = formatNumberId(convertedPrice);
                updateSelectorRowUnitPriceHint(row);
            };

            const hydrateSelectorFromCurrentItems = () => {
                const selectedByProduct = {};

                itemsWrapper.querySelectorAll('.pr-item-row').forEach((row) => {
                    const productSelect = row.querySelector('.js-product-select');
                    const unitInput = row.querySelector('.js-unit-select');
                    const qtyInput = row.querySelector('input[name$="[quantity]"]');
                    const priceInput = row.querySelector('.js-unit-price');
                    if (!productSelect || !qtyInput || !priceInput) {
                        return;
                    }

                    const productId = productSelect.value;
                    if (!productId) {
                        return;
                    }

                    const qty = parseNumber(qtyInput.value);
                    const unitPrice = parseNumber(priceInput.value);
                    selectedByProduct[String(productId)] = {
                        selected_unit: unitInput instanceof HTMLSelectElement ? normalizeUnit(unitInput.value) : '',
                        quantity: qty,
                        requested_unit_price: unitPrice,
                    };
                });

                document.querySelectorAll('#product-selector-list .selector-product-row').forEach((rowContainer) => {
                    const button = rowContainer.querySelector('.selector-toggle-button');
                    const productId = button?.dataset.productId;
                    const unitSelect = rowContainer?.querySelector('.selector-unit');
                    const qtyInput = rowContainer?.querySelector('.selector-qty');
                    const priceInput = rowContainer?.querySelector('.selector-unit-price');
                    const selected = selectedByProduct[String(productId)];

                    if (!productId || !qtyInput || !priceInput) {
                        return;
                    }

                    if (selected) {
                        setRowSelectedState(rowContainer, true);
                        if (unitSelect instanceof HTMLSelectElement) {
                            unitSelect.value = selected.selected_unit || (productsById[String(productId)]?.unit || unitSelect.value);
                        }
                        qtyInput.value = selected.quantity !== null && selected.quantity !== undefined ? selected.quantity : '';
                        priceInput.value = selected.requested_unit_price !== null && selected.requested_unit_price !== undefined
                            ? formatNumberId(Number(selected.requested_unit_price))
                            : '';
                    } else {
                        setRowSelectedState(rowContainer, false);
                        if (unitSelect instanceof HTMLSelectElement) {
                            unitSelect.value = productsById[String(productId)]?.unit || unitSelect.value;
                        }
                        qtyInput.value = '';
                        priceInput.value = '';
                        applyAutoPriceToSelectorRow(rowContainer, true);
                    }

                    updateSelectorRowUnitPriceHint(rowContainer);
                });

                updateSelectorSelectedCount();
            };

            const bindSelectorRowInteractions = () => {
                document.querySelectorAll('#product-selector-list .selector-product-row').forEach((row) => {
                    const toggleButton = row.querySelector('.selector-toggle-button');
                    const qtyInput = row.querySelector('.selector-qty');
                    const unitSelect = row.querySelector('.selector-unit');
                    const priceInput = row.querySelector('.selector-unit-price');

                    if (toggleButton instanceof HTMLButtonElement) {
                        toggleButton.addEventListener('click', () => {
                            const isSelected = row.dataset.selected === '1';
                            setRowSelectedState(row, !isSelected);
                            updateSelectorSelectedCount();
                        });
                    }

                    const autoCheckFromInput = () => {
                        if (!(toggleButton instanceof HTMLButtonElement)) {
                            return;
                        }

                        const qtyValue = qtyInput instanceof HTMLInputElement ? String(qtyInput.value).trim() : '';
                        const priceValue = priceInput instanceof HTMLInputElement ? String(priceInput.value).trim() : '';
                        if (qtyValue !== '' || priceValue !== '') {
                            setRowSelectedState(row, true);
                        }

                        updateSelectorSelectedCount();
                    };

                    if (qtyInput instanceof HTMLInputElement) {
                        qtyInput.addEventListener('input', autoCheckFromInput);
                    }

                    if (priceInput instanceof HTMLInputElement) {
                        priceInput.addEventListener('input', autoCheckFromInput);
                    }

                    if (unitSelect instanceof HTMLSelectElement) {
                        unitSelect.addEventListener('change', () => {
                            applyAutoPriceToSelectorRow(row, true);
                            autoCheckFromInput();
                            updateSelectorRowUnitPriceHint(row);
                        });
                    }

                    applyAutoPriceToSelectorRow(row, false);
                    updateSelectorRowUnitPriceHint(row);
                });
            };

            const collectSelectionsFromSelector = () => {
                const selections = [];

                document.querySelectorAll('#product-selector-list .selector-product-row[data-selected="1"]').forEach((rowContainer) => {
                    const button = rowContainer.querySelector('.selector-toggle-button');
                    const productId = button?.dataset.productId;
                    const productName = button?.dataset.productName || '-';
                    const productSku = button?.dataset.productSku || '-';
                    const qtyInput = rowContainer?.querySelector('.selector-qty');
                    const unitSelect = rowContainer?.querySelector('.selector-unit');
                    const priceInput = rowContainer?.querySelector('.selector-unit-price');

                    if (!productId) {
                        return;
                    }

                    const qty = parseNumber(qtyInput?.value ?? '');
                    if (qty === null || qty <= 0) {
                        qtyInput?.focus();
                        throw new Error(`Quantity untuk produk ${productName} wajib diisi dan lebih dari 0.`);
                    }

                    let unitPrice = parseNumber(priceInput?.value ?? '');
                    if (unitPrice === null) {
                        const resolved = resolveMasterPrice(productId, vendorSelect?.value || '');
                        if (resolved !== null) {
                            const product = productsById[String(productId)] || null;
                            const baseUnit = product?.unit || '';
                            const targetUnit = unitSelect instanceof HTMLSelectElement ? unitSelect.value : baseUnit;
                            unitPrice = convertPriceByUnit(Number(resolved), baseUnit, targetUnit, product);
                        } else {
                            unitPrice = null;
                        }
                    }

                    if (unitPrice === null) {
                        priceInput?.focus();
                        throw new Error(`Harga untuk produk ${productName} belum tersedia. Isi harga secara manual.`);
                    }

                    selections.push({
                        product_id: Number(productId),
                        product_name: productName,
                        product_sku: productSku,
                        selected_unit: unitSelect instanceof HTMLSelectElement ? normalizeUnit(unitSelect.value) : '',
                        quantity: qty,
                        requested_unit_price: unitPrice,
                    });
                });

                return selections;
            };

            const renderSelectionReview = (selections) => {
                if (!selectedProductsReviewList) {
                    return;
                }

                if (selections.length === 0) {
                    if (selectedProductsReviewTotal) {
                        selectedProductsReviewTotal.innerHTML = '';
                    }
                    selectedProductsReviewList.innerHTML = '<p class="text-xs text-red-600">Belum ada produk yang dipilih.</p>';
                    return;
                }

                const grandTotal = selections.reduce((carry, item) => {
                    return carry + (Number(item.quantity) * Number(item.requested_unit_price));
                }, 0);

                if (selectedProductsReviewTotal) {
                    selectedProductsReviewTotal.innerHTML = `Total Nominal Pilihan: <span class="font-semibold">Rp ${formatNumberId(grandTotal)}</span>`;
                }

                selectedProductsReviewList.innerHTML = selections.map((item) => {
                    const subtotal = Number(item.quantity) * Number(item.requested_unit_price);
                    const unitLabel = normalizeUnit(item.selected_unit || 'PCS');
                    return `
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-sm font-semibold text-gray-800">${item.product_name}</p>
                            <p class="text-xs text-gray-500">SKU: ${item.product_sku}</p>
                            <p class="mt-1 text-xs text-gray-700">Qty: ${formatNumberId(Number(item.quantity))} ${unitLabel} | Harga: ${formatNumberId(Number(item.requested_unit_price))} / ${unitLabel} | Subtotal: ${formatNumberId(subtotal)}</p>
                        </div>
                    `;
                }).join('');
            };

            const applySelectionsToFormRows = (selections) => {
                if (selections.length === 0) {
                    return;
                }

                itemsWrapper.innerHTML = '';

                selections.forEach((selection, index) => {
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-1 gap-2 sm:grid-cols-6 md:grid-cols-12 pr-item-row';
                    row.innerHTML = createItemRowMarkup(index, selection);
                    itemsWrapper.appendChild(row);
                });

                bindRemoveButtons();
                bindProductAutoFill();
                bindUnitPriceFormatting();
                reindexRows();
                syncInlineProductButtons();
                setReviewConfirmedState(false);
            };

            const reindexRows = () => {
                [...itemsWrapper.querySelectorAll('.pr-item-row')].forEach((row, index) => {
                    row.querySelectorAll('select, input').forEach((field) => {
                        field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`);
                    });
                });
            };

            const bindRemoveButtons = () => {
                itemsWrapper.querySelectorAll('.remove-item-row').forEach((button) => {
                    button.onclick = () => {
                        const rows = itemsWrapper.querySelectorAll('.pr-item-row');
                        if (rows.length === 1) {
                            return;
                        }

                        button.closest('.pr-item-row')?.remove();
                        reindexRows();
                        setReviewConfirmedState(false);
                    };
                });
            };

            const bindProductAutoFill = () => {
                itemsWrapper.querySelectorAll('.pr-item-row').forEach((row) => {
                    const productButton = row.querySelector('.open-product-selector-inline');
                    const productSelect = row.querySelector('.js-product-select');
                    const unitSelect = row.querySelector('.js-unit-select');
                    if (productButton instanceof HTMLButtonElement) {
                        productButton.onclick = () => {
                            hydrateSelectorFromCurrentItems();
                            openModal(productSelectorModal);
                        };
                    }

                    if (!productSelect) {
                        return;
                    }

                    productSelect.onchange = () => {
                        const selectedProduct = productsById[String(productSelect.value)] || null;
                        if (unitSelect instanceof HTMLSelectElement && selectedProduct?.unit) {
                            unitSelect.value = selectedProduct.unit;
                        }
                        applyAutoPriceToRow(row, true);
                        updateFormRowUnitPriceHint(row);
                        syncInlineProductButtons();
                        setReviewConfirmedState(false);
                    };

                    if (unitSelect instanceof HTMLSelectElement) {
                        unitSelect.onchange = () => {
                            applyAutoPriceToRow(row, true);
                            updateFormRowUnitPriceHint(row);
                            setReviewConfirmedState(false);
                        };
                    }

                    updateFormRowUnitPriceHint(row);
                });
            };

            addItemButton?.addEventListener('click', () => {
                const nextIndex = itemsWrapper.querySelectorAll('.pr-item-row').length;
                const row = document.createElement('div');
                row.className = 'grid grid-cols-1 gap-2 sm:grid-cols-6 md:grid-cols-12 pr-item-row';
                row.innerHTML = createItemRowMarkup(nextIndex);

                itemsWrapper.appendChild(row);
                bindRemoveButtons();
                bindProductAutoFill();
                bindUnitPriceFormatting();
                syncInlineProductButtons();
                setReviewConfirmedState(false);
            });

            productSelectorSearchInput?.addEventListener('input', filterSelectorProducts);
            selectorSelectVisibleButton?.addEventListener('click', selectVisibleProducts);
            selectorClearSelectionButton?.addEventListener('click', clearAllSelectedProducts);

            closeProductSelectorButton?.addEventListener('click', () => {
                closeModal(productSelectorModal);
            });

            reviewSelectedProductsButton?.addEventListener('click', () => {
                try {
                    const selections = collectSelectionsFromSelector();
                    if (selections.length === 0) {
                        window.alert('Pilih minimal satu produk.');
                        return;
                    }

                    reviewedSelections = selections;
                    renderSelectionReview(selections);
                    closeModal(productSelectorModal);
                    openModal(selectedProductsReviewModal);
                } catch (error) {
                    window.alert(error.message || 'Data produk belum lengkap.');
                }
            });

            reviewBackToSelectorButton?.addEventListener('click', () => {
                closeModal(selectedProductsReviewModal);
                openModal(productSelectorModal);
            });

            confirmSelectedProductsButton?.addEventListener('click', () => {
                applySelectionsToFormRows(reviewedSelections);
                closeModal(selectedProductsReviewModal);
                setReviewConfirmedState(true);
            });

            additionalCheckbox?.addEventListener('change', () => {
                if (!additionalPoSelect) {
                    return;
                }

                additionalPoSelect.disabled = !additionalCheckbox.checked;
                if (!additionalCheckbox.checked) {
                    additionalPoSelect.value = '';
                }
            });

            vendorSelect?.addEventListener('change', () => {
                itemsWrapper.querySelectorAll('.pr-item-row').forEach((row) => {
                    const unitPriceInput = row.querySelector('.js-unit-price');
                    if (unitPriceInput && unitPriceInput.value === '') {
                        applyAutoPriceToRow(row, true);
                    }
                });

                setReviewConfirmedState(false);
            });

            itemsWrapper.addEventListener('input', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement || target instanceof HTMLTextAreaElement)) {
                    return;
                }

                if (target.classList.contains('js-unit-price') || target.name.includes('[quantity]') || target.name.includes('[ad_hoc_name]') || target.name.includes('[ad_hoc_unit]')) {
                    setReviewConfirmedState(false);
                }
            });

            purchaseRequestForm?.addEventListener('submit', (event) => {
                if (!reviewConfirmedInput || reviewConfirmedInput.value === '1') {
                    return;
                }

                event.preventDefault();
                window.alert('Silakan review produk dulu melalui tombol "Pilih Produk" di baris item, lalu klik "Sudah Sesuai".');
            });

            bindRemoveButtons();
            bindProductAutoFill();
            bindUnitPriceFormatting();
            bindSelectorRowInteractions();
            reindexRows();
            syncInlineProductButtons();
            setReviewConfirmedState(false);
            updateSelectorSelectedCount();

            itemsWrapper.querySelectorAll('.pr-item-row').forEach((row) => {
                applyAutoPriceToRow(row, false);
            });
        })();
    </script>

    <x-ui.panel title="Daftar Purchase Request" subtitle="Monitoring status PR dan aksi lanjutan" bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3">Requester</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($purchaseRequests as $pr)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                <div>{{ $pr->number }}</div>
                                @if(!($pr->requester?->name ?? ($requesterFallbackMap[$pr->id] ?? null)))
                                    <div class="mt-1 inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-700">LEGACY</div>
                                @endif
                                @if(((int) ($pr->ad_hoc_items_count ?? 0)) > 0)
                                    <div class="mt-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-800">NON KATALOG</div>
                                @endif
                                @if($pr->is_additional)
                                    <div class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800">BARANG TAMBAHAN</div>
                                    @if($pr->additionalToPurchaseOrder)
                                        <div class="text-xs text-amber-700">Ref PO: {{ $pr->additionalToPurchaseOrder->number }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $pr->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $resolvedRequesterName = $pr->requester?->name ?? ($requesterFallbackMap[$pr->id] ?? null);
                                @endphp

                                @if($resolvedRequesterName)
                                    <span>{{ $resolvedRequesterName }}</span>
                                @else
                                    @php
                                        $assignableUsers = $sppgUserOptionsBySppg[(int) $pr->sppg_id] ?? [];
                                    @endphp

                                    @if($canAssignRequester && !empty($assignableUsers))
                                        <form method="POST" action="{{ route('ui.purchase-requests.assign-requester', $pr) }}" class="flex items-center gap-1">
                                            @csrf
                                            <select name="requested_by" class="rounded-md border border-gray-300 px-2 py-1 text-xs" required>
                                                <option value="">Set Requester</option>
                                                @foreach($assignableUsers as $assignableUser)
                                                    <option value="{{ $assignableUser['id'] }}">{{ $assignableUser['name'] }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-md border border-emerald-300 px-2 py-1 text-[10px] font-medium text-emerald-700 hover:bg-emerald-50">Simpan</button>
                                        </form>
                                    @else
                                        <span>-</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ optional($pr->request_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <x-ui.status-pill
                                    :value="$pr->status?->value ?? '-'"
                                    :classes="[
                                        'submitted' => 'bg-amber-100 text-amber-700',
                                        'approved' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                    ]"
                                />
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah($pr->total_amount)</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <x-ui.action-link href="{{ route('ui.purchase-requests.download', $pr) }}" title="Cetak PR" aria-label="Cetak PR" variant="blue-outline" size="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                            <path d="M6 9V3h12v6h-2V5H8v4H6zm10 4h2a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2v6h8v-6zm-6 4v-4h4v4h-4z"/>
                                        </svg>
                                    </x-ui.action-link>

                                    @if($canApprovePurchaseRequest && $pr->status?->value === 'submitted')
                                        <form method="POST" action="{{ route('ui.purchase-requests.approve', $pr) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Approve</button>
                                        </form>
                                    @endif

                                    @if($canGeneratePurchaseOrder && $pr->status?->value === 'approved')
                                        <form method="POST" action="{{ route('ui.purchase-requests.generate-po', $pr) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">Generate PO</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data purchase request.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <div class="mt-4">
        {{ $purchaseRequests->links() }}
    </div>
@endsection
