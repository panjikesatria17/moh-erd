@extends('layouts.procurement')

@section('title', 'Barang Reject')

@section('content')
    @php
        $selectedDeliveryId = $selectedDeliveryId ?? null;
        $deliveryItemsMap = $deliveries
            ->mapWithKeys(function ($delivery) {
                $items = collect($delivery->purchaseOrder?->items ?? [])->map(function ($item) {
                    return [
                        'id' => (int) $item->id,
                        'product_name' => $item->product?->name ?? '-',
                        'product_sku' => $item->product?->sku ?? '-',
                        'ordered_quantity' => (float) $item->quantity,
                        'unit' => $item->product?->unit ?? '-',
                    ];
                })->values();

                return [(string) $delivery->id => $items];
            })
            ->toArray();
    @endphp

    <div class="mb-4">
        <h2 class="text-xl font-semibold">Barang Reject</h2>
        <p class="text-sm text-gray-500">Catat item reject saat barang tiba di SPPG dan lampirkan foto bukti.</p>
    </div>

    <form method="GET" action="{{ route('ui.rejected-items.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-3">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-4">
            <div class="md:col-span-3">
                <label class="mb-1 block text-xs font-medium text-gray-600">Pilih Delivery / PO</label>
                <select name="delivery_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">-- Pilih Delivery --</option>
                    @foreach($deliveries as $delivery)
                        <option value="{{ $delivery->id }}" @selected((int) $selectedDeliveryId === (int) $delivery->id)>
                            {{ $delivery->number }} | PO {{ $delivery->purchaseOrder?->number ?? '-' }} | {{ $delivery->sppg?->name ?? '-' }} | {{ $delivery->vendor?->name ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <button type="submit" class="w-full sm:w-auto rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">Tampilkan</button>
                @if(request()->filled('delivery_id'))
                    <a href="{{ route('ui.rejected-items.index') }}" class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-2 text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                @endif
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('ui.rejected-items.store') }}" enctype="multipart/form-data" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Input Barang Reject</h3>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Delivery</label>
                <select id="delivery_id" name="delivery_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">-- Pilih Delivery --</option>
                    @foreach($deliveries as $delivery)
                        <option value="{{ $delivery->id }}" @selected((string) old('delivery_id', $selectedDeliveryId) === (string) $delivery->id)>
                            {{ $delivery->number }} | PO {{ $delivery->purchaseOrder?->number ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Item PO yang Reject</label>
                <select id="purchase_order_item_id" name="purchase_order_item_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">-- Pilih Item dari PO Delivery --</option>
                    @foreach($availableItems as $item)
                        <option value="{{ $item['id'] }}" @selected((string) old('purchase_order_item_id') === (string) $item['id'])>
                            {{ $item['product_name'] }} ({{ $item['product_sku'] }}) | Pesan: {{ rtrim(rtrim(number_format((float) $item['ordered_quantity'], 2, ',', '.'), '0'), ',') }} {{ $item['unit'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Item harus sesuai dengan item yang dipesan di PO delivery terpilih.</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Jumlah Reject</label>
                <input type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Tanggal Laporan</label>
                <input type="date" name="reported_at" value="{{ old('reported_at', now()->toDateString()) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Foto Bukti (Opsional)</label>
                <input id="evidence_image" type="file" name="evidence_image" accept="image/*" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">Format: jpg/png/webp, maksimal 5 MB.</p>
                <div id="image_preview_wrap" class="mt-2 hidden">
                    <img id="image_preview" src="" alt="Preview foto bukti" class="h-24 w-24 rounded-md border border-gray-300 object-cover">
                </div>
            </div>

            <div class="sm:col-span-2 xl:col-span-3">
                <label class="mb-1 block text-xs font-medium text-gray-600">Alasan Reject</label>
                <textarea name="reason" rows="3" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: kemasan rusak, produk basi, jumlah tidak sesuai." required>{{ old('reason') }}</textarea>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Simpan Laporan Reject</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Delivery / PO</th>
                        <th class="px-4 py-3">SPPG / Vendor</th>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3 text-right">Qty Reject</th>
                        <th class="px-4 py-3">Alasan</th>
                        <th class="px-4 py-3">Foto</th>
                        <th class="px-4 py-3">Pelapor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rejectedItems as $row)
                        <tr>
                            <td class="px-4 py-3">{{ optional($row->reported_at)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $row->delivery?->number ?? '-' }}</div>
                                <div class="text-xs text-gray-500">PO {{ $row->delivery?->purchaseOrder?->number ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ $row->delivery?->sppg?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->delivery?->vendor?->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ $row->product?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->product?->sku ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format((float) $row->quantity, 2, ',', '.'), '0'), ',') }} {{ $row->product?->unit ?? '' }}</td>
                            <td class="px-4 py-3">{{ $row->reason }}</td>
                            <td class="px-4 py-3">
                                @if($row->evidence_image_path)
                                    <a href="{{ asset('storage/'.$row->evidence_image_path) }}" target="_blank" class="inline-flex items-center rounded-md border border-blue-300 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50">Lihat</a>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $row->reporter?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Belum ada laporan barang reject.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $rejectedItems->links() }}</div>

    <script>
        (() => {
            const deliverySelect = document.getElementById('delivery_id');
            const poItemSelect = document.getElementById('purchase_order_item_id');
            const imageInput = document.getElementById('evidence_image');
            const imagePreviewWrap = document.getElementById('image_preview_wrap');
            const imagePreview = document.getElementById('image_preview');

            const deliveryItemsMap = @json($deliveryItemsMap);
            const previousPoItemId = '{{ old('purchase_order_item_id') }}';

            const formatQty = (value) => {
                const number = Number.parseFloat(value ?? 0);
                if (!Number.isFinite(number)) {
                    return '0';
                }

                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                }).format(number);
            };

            const renderPoItems = (deliveryId, selectedId = '') => {
                if (!poItemSelect) {
                    return;
                }

                const options = ['<option value="">-- Pilih Item dari PO Delivery --</option>'];
                const items = deliveryItemsMap[String(deliveryId)] ?? [];

                items.forEach((item) => {
                    const itemId = String(item.id);
                    const selected = String(selectedId) === itemId ? ' selected' : '';
                    const label = `${item.product_name} (${item.product_sku}) | Pesan: ${formatQty(item.ordered_quantity)} ${item.unit}`;
                    options.push(`<option value="${itemId}"${selected}>${label}</option>`);
                });

                poItemSelect.innerHTML = options.join('');
            };

            if (deliverySelect && poItemSelect) {
                renderPoItems(deliverySelect.value, previousPoItemId);
                deliverySelect.addEventListener('change', () => renderPoItems(deliverySelect.value));
            }

            if (imageInput && imagePreviewWrap && imagePreview) {
                imageInput.addEventListener('change', () => {
                    const [file] = imageInput.files || [];
                    if (!file) {
                        imagePreview.src = '';
                        imagePreviewWrap.classList.add('hidden');
                        return;
                    }

                    imagePreview.src = URL.createObjectURL(file);
                    imagePreviewWrap.classList.remove('hidden');
                });
            }
        })();
    </script>
@endsection
