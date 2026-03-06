@extends('layouts.procurement')

@section('title', 'Deliveries')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Deliveries</h2>
        <p class="text-sm text-gray-500">Monitoring pengiriman barang dari gudang/vendor ke SPPG.</p>
        <form method="GET" action="{{ route('ui.deliveries.index') }}" class="mt-3 flex flex-wrap items-center gap-2">
            <select name="vendor" class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-1.5 text-sm">
                <option value="">Semua Vendor</option>
                @foreach(($vendors ?? collect()) as $vendor)
                    <option value="{{ $vendor->id }}" @selected(request('vendor') == $vendor->id)>{{ $vendor->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="w-full sm:w-auto rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Filter</button>
            @if(request()->filled('vendor'))
                <a href="{{ route('ui.deliveries.index') }}" class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-1.5 text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            @endif
        </form>
        @if(isset($selectedVendor) && $selectedVendor)
            <div class="mt-2 inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                Vendor aktif: {{ $selectedVendor->name }}
            </div>
        @endif
    </div>

    <div class="space-y-3 md:hidden" id="mobile-delivery-list">
        <div class="sticky top-0 z-10 -mx-1 flex items-center gap-2 overflow-x-auto px-1 py-1">
            <button type="button" class="mobile-delivery-filter whitespace-nowrap rounded-full border border-slate-300 bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white" data-mobile-filter="all">Semua</button>
            <button type="button" class="mobile-delivery-filter whitespace-nowrap rounded-full border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700" data-mobile-filter="processed">On Proses</button>
            <button type="button" class="mobile-delivery-filter whitespace-nowrap rounded-full border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-rose-700" data-mobile-filter="overdue">Overdue</button>
        </div>

        @forelse($deliveries as $delivery)
            @php
                $rawStatus = $delivery->status?->value;
                $statusLabel = $rawStatus === 'processed' ? 'on proses' : ($rawStatus ?? '-');
                $deliveryAgeHours = $delivery->delivery_date
                    ? \Illuminate\Support\Carbon::parse($delivery->delivery_date)->startOfDay()->diffInHours(now('Asia/Jakarta'))
                    : 0;
                $isSlaOverdue = $rawStatus === 'processed' && $deliveryAgeHours > 24;

                $statusBadgeClass = match ($rawStatus) {
                    'processed' => 'bg-amber-100 text-amber-800',
                    'delivered', 'invoiced', 'paid' => 'bg-emerald-100 text-emerald-800',
                    'rejected' => 'bg-rose-100 text-rose-800',
                    default => 'bg-gray-100 text-gray-700',
                };
            @endphp

            <article
                class="mobile-delivery-card rounded-xl border border-gray-200 bg-white p-3 shadow-sm"
                data-mobile-delivery-card="true"
                data-mobile-status="{{ $rawStatus ?? '' }}"
                data-mobile-overdue="{{ $isSlaOverdue ? '1' : '0' }}"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $delivery->number }}</p>
                        <p class="text-xs text-gray-500">PO {{ $delivery->purchaseOrder?->number ?? '-' }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-medium {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                </div>

                <div class="mt-2 space-y-1 text-xs text-gray-600">
                    <p><span class="font-medium text-gray-700">SPPG:</span> {{ $delivery->sppg?->name ?? '-' }}</p>
                    <p><span class="font-medium text-gray-700">Vendor:</span> {{ $delivery->vendor?->name ?? '-' }}</p>
                    <p><span class="font-medium text-gray-700">Tanggal:</span> {{ optional($delivery->delivery_date)->format('d M Y') }}</p>
                    <p><span class="font-medium text-gray-700">Total:</span> @rupiah($delivery->total_amount)</p>

                    @if($rawStatus === 'processed')
                        <p class="text-[11px] {{ $isSlaOverdue ? 'font-semibold text-rose-700' : 'text-amber-700' }}">
                            SLA: {{ $deliveryAgeHours }} jam {{ $isSlaOverdue ? '(overdue)' : '' }}
                        </p>
                    @endif
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('ui.deliveries.surat-jalan.preview', $delivery) }}" target="_blank" class="inline-flex min-h-9 items-center rounded-md border border-gray-300 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50">Preview Surat Jalan</a>

                    @if($delivery->delivery_proof_image_path)
                        <a href="{{ asset('storage/'.$delivery->delivery_proof_image_path) }}" target="_blank" class="inline-flex min-h-9 items-center rounded-md border border-emerald-300 px-3 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Foto</a>
                    @endif

                    @if($delivery->signed_delivery_note_path)
                        <a href="{{ asset('storage/'.$delivery->signed_delivery_note_path) }}" target="_blank" class="inline-flex min-h-9 items-center rounded-md border border-blue-300 px-3 text-xs font-medium text-blue-700 hover:bg-blue-50">Surat Jalan TTD</a>
                    @endif

                    @if(($canCompleteDelivery ?? false) && $delivery->status?->value === 'processed')
                        <button
                            type="button"
                            class="inline-flex min-h-9 items-center rounded-md bg-emerald-600 px-3 text-xs font-medium text-white hover:bg-emerald-700"
                            data-complete-delivery-open="true"
                            data-delivery-id="{{ $delivery->id }}"
                            data-delivery-number="{{ $delivery->number }}"
                        >
                            Upload Bukti
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">Belum ada data delivery.</div>
        @endforelse

        @if($deliveries->isNotEmpty())
            <div id="mobile-delivery-filter-empty" class="hidden rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">
                Tidak ada delivery yang cocok dengan filter ini.
            </div>
        @endif
    </div>

    @php
        $mobileOnProcessDeliveries = collect($deliveries->items() ?? [])
            ->filter(function ($delivery) {
                return $delivery->status?->value === 'processed';
            })
            ->sortBy(function ($delivery) {
                return sprintf('%s-%010d', (string) ($delivery->delivery_date ?? ''), (int) ($delivery->id ?? 0));
            })
            ->values();

        $mobilePriorityDeliveryId = (int) ($mobileOnProcessDeliveries->first()?->id ?? 0);
        $mobileOverdueCount = $mobileOnProcessDeliveries->filter(function ($delivery) {
            if (! $delivery->delivery_date) {
                return false;
            }

            $ageHours = \Illuminate\Support\Carbon::parse($delivery->delivery_date)
                ->startOfDay()
                ->diffInHours(now('Asia/Jakarta'));

            return $ageHours > 24;
        })->count();
    @endphp

    @if(($canCompleteDelivery ?? false) && $mobileOnProcessDeliveries->isNotEmpty())
        <div class="h-24 md:hidden"></div>
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 p-3 shadow-[0_-6px_20px_rgba(15,23,42,0.12)] backdrop-blur md:hidden">
            <div class="mx-auto max-w-screen-sm">
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Aksi Cepat Ekspedisi</p>
                <div class="flex items-center gap-2">
                    <select id="mobile-delivery-select" class="h-10 min-w-0 flex-1 rounded-md border border-gray-300 px-2 text-xs text-gray-700">
                        @foreach($mobileOnProcessDeliveries as $delivery)
                            <option value="{{ $delivery->id }}" data-delivery-number="{{ $delivery->number }}" @selected((int) $delivery->id === $mobilePriorityDeliveryId)>
                                {{ $delivery->number }} | {{ $delivery->vendor?->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" id="mobile-open-complete" class="inline-flex h-10 items-center rounded-md bg-emerald-600 px-3 text-xs font-semibold text-white hover:bg-emerald-700">
                        Upload Bukti
                    </button>
                </div>
                <p class="mt-1 text-[11px] text-slate-500">Default prioritas: delivery on proses paling lama.</p>
                @if($mobileOverdueCount > 0)
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">Perhatian: {{ $mobileOverdueCount }} delivery on proses sudah melewati SLA 24 jam.</p>
                @endif
            </div>
        </div>
    @endif

    <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white md:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">PO</th>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Delivery Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Bukti</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($deliveries as $delivery)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $delivery->number }}</td>
                            <td class="px-4 py-3">{{ $delivery->purchaseOrder?->number ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $delivery->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $delivery->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($delivery->delivery_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $rawStatus = $delivery->status?->value;
                                    $statusLabel = $rawStatus === 'processed' ? 'on proses' : ($rawStatus ?? '-');

                                    $statusBadgeClass = match ($rawStatus) {
                                        'processed' => 'bg-amber-100 text-amber-800',
                                        'delivered', 'invoiced', 'paid' => 'bg-emerald-100 text-emerald-800',
                                        'rejected' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    @if($delivery->delivery_proof_image_path)
                                        <a href="{{ asset('storage/'.$delivery->delivery_proof_image_path) }}" target="_blank" class="inline-flex items-center rounded-md border border-emerald-300 px-2 py-1 font-medium text-emerald-700 hover:bg-emerald-50">Foto</a>
                                    @endif
                                    @if($delivery->signed_delivery_note_path)
                                        <a href="{{ asset('storage/'.$delivery->signed_delivery_note_path) }}" target="_blank" class="inline-flex items-center rounded-md border border-blue-300 px-2 py-1 font-medium text-blue-700 hover:bg-blue-50">Surat Jalan</a>
                                    @endif
                                    @if(! $delivery->delivery_proof_image_path && ! $delivery->signed_delivery_note_path)
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah($delivery->total_amount)</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('ui.deliveries.surat-jalan.preview', $delivery) }}" target="_blank" title="Preview Surat Jalan" aria-label="Preview Surat Jalan" class="inline-flex items-center justify-center rounded-md border border-gray-300 p-1.5 text-gray-700 hover:bg-gray-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                            <path d="M7 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9.414a2 2 0 0 0-.586-1.414l-4.414-4.414A2 2 0 0 0 12.586 3H7zm5 1.5V9h4.5L12 4.5zM8 12a1 1 0 1 1 0-2h8a1 1 0 1 1 0 2H8zm0 4a1 1 0 1 1 0-2h8a1 1 0 1 1 0 2H8z"/>
                                        </svg>
                                    </a>

                                    @if(($canCompleteDelivery ?? false) && $delivery->status?->value === 'processed')
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-emerald-300 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                                            data-complete-delivery-open="true"
                                            data-delivery-id="{{ $delivery->id }}"
                                            data-delivery-number="{{ $delivery->number }}"
                                        >
                                            Upload Bukti
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">Belum ada data delivery.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $deliveries->links() }}
    </div>

    <div class="mt-4 space-y-3 md:hidden">
        @forelse(($pendingPurchaseOrders ?? collect()) as $po)
            <article class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $po->number }}</p>
                        <p class="text-xs text-gray-500">{{ $po->vendor?->name ?? '-' }}</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-700">{{ $po->status?->value ?? '-' }}</span>
                </div>

                <div class="mt-2 space-y-1 text-xs text-gray-600">
                    <p><span class="font-medium text-gray-700">SPPG:</span> {{ $po->sppg?->name ?? '-' }}</p>
                    <p><span class="font-medium text-gray-700">Tanggal PO:</span> {{ optional($po->order_date)->format('d M Y') ?? '-' }}</p>
                    <p><span class="font-medium text-gray-700">Total:</span> @rupiah($po->total_amount)</p>
                </div>

                <div class="mt-3">
                    @if($canStartDelivery ?? false)
                        <form method="POST" action="{{ route('ui.purchase-orders.create-delivery', $po) }}">
                            @csrf
                            <input type="hidden" name="vendor" value="{{ request('vendor') }}">
                            <button type="submit" class="inline-flex min-h-9 items-center rounded-md bg-indigo-600 px-3 text-xs font-medium text-white hover:bg-indigo-700">
                                Kirim (On Proses)
                            </button>
                        </form>
                    @else
                        <span class="text-xs text-gray-400">Anda tidak memiliki akses memulai pengiriman.</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-6 text-center text-sm text-gray-500">Tidak ada PO siap dikirim untuk filter vendor saat ini.</div>
        @endforelse
    </div>

    <div class="mt-4 hidden overflow-hidden rounded-xl border border-gray-200 bg-white md:block">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-700">PO Siap Dikirim (Per Vendor)</h3>
            <p class="text-xs text-gray-500">PO approved/processed yang belum punya dokumen delivery.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">PO Number</th>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Order Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 hidden md:table-cell">Keterangan</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($pendingPurchaseOrders ?? collect()) as $po)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $po->number }}</td>
                            <td class="px-4 py-3">{{ $po->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $po->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($po->order_date)->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium">{{ $po->status?->value ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah($po->total_amount)</td>
                            <td class="px-4 py-3 text-xs text-gray-500 hidden md:table-cell">Pembuatan delivery dilakukan dari proses PO.</td>
                            <td class="px-4 py-3 text-right">
                                @if($canStartDelivery ?? false)
                                    <form method="POST" action="{{ route('ui.purchase-orders.create-delivery', $po) }}">
                                        @csrf
                                        <input type="hidden" name="vendor" value="{{ request('vendor') }}">
                                        <button type="submit" class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-indigo-700">
                                            Kirim (On Proses)
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">Tidak ada PO siap dikirim untuk filter vendor saat ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (() => {
            const filterButtons = Array.from(document.querySelectorAll('.mobile-delivery-filter'));
            const cards = Array.from(document.querySelectorAll('[data-mobile-delivery-card="true"]'));
            const emptyState = document.getElementById('mobile-delivery-filter-empty');

            if (!filterButtons.length || !cards.length) {
                return;
            }

            const setActiveButton = (activeKey) => {
                filterButtons.forEach((button) => {
                    const isActive = button.getAttribute('data-mobile-filter') === activeKey;

                    button.classList.remove('text-white', 'text-slate-700', 'text-rose-700');

                    button.classList.toggle('bg-slate-900', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('bg-white', !isActive);

                    if (!isActive && button.getAttribute('data-mobile-filter') === 'overdue') {
                        button.classList.add('text-rose-700');
                    }

                    if (!isActive && button.getAttribute('data-mobile-filter') !== 'overdue') {
                        button.classList.add('text-slate-700');
                    }
                });
            };

            const applyFilter = (filterKey) => {
                let visibleCount = 0;

                cards.forEach((card) => {
                    const status = card.getAttribute('data-mobile-status') || '';
                    const isOverdue = card.getAttribute('data-mobile-overdue') === '1';

                    const shouldShow = filterKey === 'all'
                        ? true
                        : filterKey === 'processed'
                            ? status === 'processed'
                            : isOverdue;

                    card.classList.toggle('hidden', !shouldShow);
                    if (shouldShow) {
                        visibleCount += 1;
                    }
                });

                if (emptyState) {
                    emptyState.classList.toggle('hidden', visibleCount > 0);
                }

                setActiveButton(filterKey);
            };

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    applyFilter(button.getAttribute('data-mobile-filter') || 'all');
                });
            });
        })();
    </script>

    @if(($canCompleteDelivery ?? false) === true)
        <div id="complete-delivery-modal" class="fixed inset-0 z-50 hidden items-start overflow-y-auto bg-slate-900/50 p-4 sm:items-center">
            <div class="mx-auto mt-6 w-full max-w-xl rounded-xl border border-gray-200 bg-white p-4 shadow-xl sm:mt-0">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Upload Bukti Pengiriman</h3>
                        <p id="complete-delivery-label" class="text-xs text-gray-500">Delivery</p>
                    </div>
                    <button type="button" id="complete-delivery-close" class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50">Tutup</button>
                </div>

                <form id="complete-delivery-form" method="POST" action="#" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Foto Bukti Barang Sampai</label>
                        <input type="file" name="delivery_proof_image" accept="image/*" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Surat Jalan TTD SPPG (PDF/JPG/PNG)</label>
                        <input type="file" name="signed_delivery_note" accept=".pdf,image/*" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="min-h-10 rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Simpan & Tandai Delivered</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (() => {
                const modal = document.getElementById('complete-delivery-modal');
                const closeBtn = document.getElementById('complete-delivery-close');
                const form = document.getElementById('complete-delivery-form');
                const label = document.getElementById('complete-delivery-label');
                const mobileDeliverySelect = document.getElementById('mobile-delivery-select');
                const mobileOpenCompleteButton = document.getElementById('mobile-open-complete');

                if (!modal || !closeBtn || !form || !label) {
                    return;
                }

                const openButtons = document.querySelectorAll('[data-complete-delivery-open="true"]');

                const openModal = (deliveryId, deliveryNumber) => {
                    if (!deliveryId) {
                        return;
                    }

                    form.action = `{{ url('/ui/deliveries') }}/${deliveryId}/complete`;
                    label.textContent = `Delivery: ${deliveryNumber || '-'}`;
                    modal.classList.add('flex');
                    modal.classList.remove('hidden');
                };

                const closeModal = () => {
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                    form.reset();
                };

                openButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const deliveryId = button.getAttribute('data-delivery-id');
                        const deliveryNumber = button.getAttribute('data-delivery-number') || '-';

                        openModal(deliveryId, deliveryNumber);
                    });
                });

                if (mobileOpenCompleteButton && mobileDeliverySelect) {
                    mobileOpenCompleteButton.addEventListener('click', () => {
                        const selectedOption = mobileDeliverySelect.options[mobileDeliverySelect.selectedIndex];
                        const deliveryId = selectedOption?.value || '';
                        const deliveryNumber = selectedOption?.getAttribute('data-delivery-number') || '-';

                        openModal(deliveryId, deliveryNumber);
                    });
                }

                closeBtn.addEventListener('click', closeModal);
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });
            })();
        </script>
    @endif
@endsection
