@extends('layouts.procurement')

@section('title', 'Purchase Requests')

@section('content')
    @php
        $currentRole = auth()->user()?->role?->value;
        $canCreatePurchaseRequest = in_array($currentRole, ['super_admin', 'purchasing', 'sppg_user'], true);
        $canApprovePurchaseRequest = in_array($currentRole, ['super_admin', 'owner'], true);
        $canGeneratePurchaseOrder = in_array($currentRole, ['super_admin', 'purchasing'], true);
        $canAssignRequester = in_array($currentRole, ['super_admin', 'purchasing'], true);
        $formatMoneyInput = static fn ($value) => $value === null || $value === ''
            ? ''
            : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    @endphp

    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Purchase Requests</h2>
            <p class="text-sm text-gray-500">Daftar permintaan pembelian dari seluruh SPPG.</p>
        </div>
    </div>

    @if($canCreatePurchaseRequest)
    <form method="POST" action="{{ route('ui.purchase-requests.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Create Purchase Request (Quick Form)</h3>
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
                    <button type="button" id="add-item-row" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">+ Add Product</button>
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
                                <select name="items[{{ $index }}][product_id]" class="js-product-select w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    <option value="">Pilih Product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-[11px] text-gray-500">Kosongkan jika item non katalog.</p>
                            </div>
                            <div class="sm:col-span-3 md:col-span-3">
                                <input type="text" name="items[{{ $index }}][ad_hoc_name]" value="{{ $item['ad_hoc_name'] ?? '' }}" placeholder="Nama item non katalog" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div class="sm:col-span-3 md:col-span-1">
                                <input type="text" name="items[{{ $index }}][ad_hoc_unit]" value="{{ $item['ad_hoc_unit'] ?? '' }}" placeholder="Satuan" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div class="sm:col-span-3 md:col-span-2">
                                <input type="number" step="0.01" min="0.01" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? '' }}" placeholder="Quantity" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                            </div>
                            <div class="sm:col-span-5 md:col-span-3">
                                <input type="text" inputmode="decimal" name="items[{{ $index }}][requested_unit_price]" value="{{ $formatMoneyInput($item['requested_unit_price'] ?? '') }}" placeholder="Requested Unit Price (auto dari master)" class="js-unit-price w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div class="sm:col-span-1 md:col-span-1">
                                <button type="button" class="remove-item-row w-full rounded-md border border-red-200 px-2 py-2 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <label class="mb-1 block text-xs font-medium text-gray-600">Notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Create PR</button>
        </div>
    </form>
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
                'default_unit_price' => $defaultUnitPrice,
            ];
        })->values();
    @endphp

    <script>
        (() => {
            const itemsWrapper = document.getElementById('pr-items-wrapper');
            const addItemButton = document.getElementById('add-item-row');
            const vendorSelect = document.getElementById('vendor_id');
            const additionalCheckbox = document.getElementById('is_additional');
            const additionalPoSelect = document.getElementById('additional_to_po_id');
            const products = {!! json_encode($productOptions) !!};
            const priceLookup = {!! json_encode($priceLookup ?? []) !!};

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

            const applyAutoPriceToRow = (row, force = false) => {
                const productSelect = row.querySelector('.js-product-select');
                const unitPriceInput = row.querySelector('.js-unit-price');
                if (!productSelect || !unitPriceInput) {
                    return;
                }

                if (!force && unitPriceInput.value !== '') {
                    return;
                }

                const vendorId = vendorSelect?.value || '';
                const resolved = resolveMasterPrice(productSelect.value, vendorId);
                if (resolved === null) {
                    return;
                }

                unitPriceInput.value = formatNumberId(resolved);
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
                    };
                });
            };

            const bindProductAutoFill = () => {
                itemsWrapper.querySelectorAll('.pr-item-row').forEach((row) => {
                    const productSelect = row.querySelector('.js-product-select');
                    if (!productSelect) {
                        return;
                    }

                    productSelect.onchange = () => applyAutoPriceToRow(row, true);
                });
            };

            addItemButton?.addEventListener('click', () => {
                const nextIndex = itemsWrapper.querySelectorAll('.pr-item-row').length;
                const row = document.createElement('div');
                row.className = 'grid grid-cols-1 gap-2 sm:grid-cols-6 md:grid-cols-12 pr-item-row';
                row.innerHTML = `
                    <div class="sm:col-span-6 md:col-span-6">
                        <select name="items[${nextIndex}][product_id]" class="js-product-select w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            ${createProductOptions()}
                        </select>
                        <p class="mt-1 text-[11px] text-gray-500">Kosongkan jika item non katalog.</p>
                    </div>
                    <div class="sm:col-span-3 md:col-span-3">
                        <input type="text" name="items[${nextIndex}][ad_hoc_name]" placeholder="Nama item non katalog" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-3 md:col-span-1">
                        <input type="text" name="items[${nextIndex}][ad_hoc_unit]" placeholder="Satuan" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-3 md:col-span-2">
                        <input type="number" step="0.01" min="0.01" name="items[${nextIndex}][quantity]" placeholder="Quantity" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    </div>
                    <div class="sm:col-span-5 md:col-span-3">
                        <input type="text" inputmode="decimal" name="items[${nextIndex}][requested_unit_price]" placeholder="Requested Unit Price (auto dari master)" class="js-unit-price w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-1 md:col-span-1">
                        <button type="button" class="remove-item-row w-full rounded-md border border-red-200 px-2 py-2 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                    </div>
                `;

                itemsWrapper.appendChild(row);
                bindRemoveButtons();
                bindProductAutoFill();
                bindUnitPriceFormatting();
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
            });

            bindRemoveButtons();
            bindProductAutoFill();
            bindUnitPriceFormatting();
            reindexRows();

            itemsWrapper.querySelectorAll('.pr-item-row').forEach((row) => {
                applyAutoPriceToRow(row, false);
            });
        })();
    </script>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
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
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium">{{ $pr->status?->value }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah($pr->total_amount)</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('ui.purchase-requests.download', $pr) }}" title="Cetak PR" aria-label="Cetak PR" class="inline-flex items-center justify-center rounded-md border border-blue-300 p-1.5 text-blue-700 hover:bg-blue-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                            <path d="M6 9V3h12v6h-2V5H8v4H6zm10 4h2a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2v6h8v-6zm-6 4v-4h4v4h-4z"/>
                                        </svg>
                                    </a>

                                    @if($canApprovePurchaseRequest && $pr->status?->value === 'submitted')
                                        <form method="POST" action="{{ route('ui.purchase-requests.approve', $pr) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Approve</button>
                                        </form>
                                    @endif

                                    @if($canGeneratePurchaseOrder && $pr->status?->value === 'approved')
                                        <form method="POST" action="{{ route('ui.purchase-requests.generate-po', $pr) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="date" name="expected_date" class="rounded-md border border-gray-300 px-2 py-1 text-xs">
                                            <select name="vendor_id" class="rounded-md border border-gray-300 px-2 py-1 text-xs">
                                                <option value="">Default vendor</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
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
    </div>

    <div class="mt-4">
        {{ $purchaseRequests->links() }}
    </div>
@endsection
