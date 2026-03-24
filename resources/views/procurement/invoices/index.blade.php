@extends('layouts.procurement')

@section('title', 'Invoices')

@section('content')
    @php
        $authRole = auth()->user()?->role;
        $currentRoleRaw = is_object($authRole) ? ($authRole->value ?? null) : $authRole;
        $currentRole = in_array($currentRoleRaw, ['admin', 'owner'], true) ? 'super_admin' : $currentRoleRaw;
        $canManageFinanceWrites = in_array($currentRole, ['super_admin', 'finance'], true);
    @endphp

    <x-ui.hero
        class="mb-4"
        eyebrow="Finance"
        title="Invoices"
        description="Tagihan vendor untuk billing mingguan per SPPG."
    >
        <form method="GET" action="{{ route('ui.invoices.index') }}" class="mt-3 flex flex-wrap items-center gap-2">
            <select name="vendor" class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-1.5 text-sm">
                <option value="">Semua Vendor</option>
                @foreach(($vendors ?? collect()) as $vendor)
                    <option value="{{ $vendor->id }}" @selected(request('vendor') == $vendor->id)>{{ $vendor->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="w-full sm:w-auto rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Filter</button>
            @if(request()->filled('vendor'))
                <a href="{{ route('ui.invoices.index') }}" class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-1.5 text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            @endif
        </form>
        @if(isset($selectedVendor) && $selectedVendor)
            <div class="mt-2 flex items-center gap-2">
                <div class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                    Vendor aktif: {{ $selectedVendor->name }}
                </div>
                <x-ui.action-link href="{{ route('ui.invoices.summary.download', ['vendor' => $selectedVendor->id, 'week_start' => $weekStartDate, 'week_end' => $weekEndDate]) }}" title="Export Rekap PDF" aria-label="Export Rekap PDF" variant="blue-outline" size="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M12 3a1 1 0 0 1 1 1v8.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4.004 4.004a1 1 0 0 1-1.414 0L7.285 11.71a1 1 0 0 1 1.414-1.414L11 12.586V4a1 1 0 0 1 1-1z"/>
                        <path d="M5 15a1 1 0 0 1 1 1v2h12v-2a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1z"/>
                    </svg>
                </x-ui.action-link>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-ui.stat-card
                    label="Total Invoice Vendor Minggu Ini"
                    value="@rupiah($weeklyVendorInvoiceTotal ?? 0)"
                    class="border-blue-100 bg-blue-50"
                >
                    <span class="text-xs text-blue-700">Periode {{ \Illuminate\Support\Carbon::parse($weekStartDate)->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEndDate)->format('d M Y') }}</span>
                </x-ui.stat-card>
                <x-ui.stat-card
                    label="Jumlah Invoice Vendor Minggu Ini"
                    value="{{ number_format((int) ($weeklyVendorInvoiceCount ?? 0), 0, ',', '.') }}"
                >
                    <span class="text-xs text-gray-500">Dokumen invoice terdaftar</span>
                </x-ui.stat-card>
            </div>
        @endif
    </x-ui.hero>

    <x-ui.panel class="mb-4" title="Daftar Invoice" subtitle="Monitoring invoice vendor dan proses pembayaran" bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Invoice Date</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $invoice)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                <div>{{ $invoice->number }}</div>
                                @if(in_array((int) $invoice->id, $additionalInvoiceIds ?? [], true))
                                    <div
                                        class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800"
                                        title="Referensi PO tambahan: {{ $additionalInvoiceRefs[(int) $invoice->id] ?? '-' }}"
                                    >
                                        BARANG TAMBAHAN
                                    </div>
                                    <div class="mt-1 text-[10px] text-amber-700">PO: {{ $additionalInvoiceRefs[(int) $invoice->id] ?? '-' }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $invoice->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($invoice->invoice_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ optional($invoice->due_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <x-ui.status-pill
                                    :value="$invoice->status?->value ?? '-'"
                                    :classes="[
                                        'draft' => 'bg-slate-100 text-slate-700',
                                        'submitted' => 'bg-amber-100 text-amber-700',
                                        'approved' => 'bg-cyan-100 text-cyan-700',
                                        'paid' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                    ]"
                                />
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah($invoice->total_amount)</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <x-ui.action-link href="{{ route('ui.invoices.download', $invoice) }}" title="Download PDF" aria-label="Download PDF" variant="blue-outline" size="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                            <path d="M6 9V3h12v6h-2V5H8v4H6zm10 4h2a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2v6h8v-6zm-6 4v-4h4v4h-4z"/>
                                        </svg>
                                    </x-ui.action-link>
                                    <x-ui.action-link href="{{ route('ui.payments.index', ['invoice' => $invoice->id]) }}" title="Lihat Pembayaran" aria-label="Lihat Pembayaran" variant="outline" size="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                            <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v1H2V6zm0 3h20v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9zm4 5a1 1 0 0 0 0 2h3a1 1 0 1 0 0-2H6z"/>
                                        </svg>
                                    </x-ui.action-link>
                                    @if($invoice->status?->value !== 'paid' && $canManageFinanceWrites)
                                        <form method="POST" action="{{ route('ui.invoices.create-payment', $invoice) }}">
                                            @csrf
                                            @if(request()->filled('vendor'))
                                                <input type="hidden" name="vendor" value="{{ request('vendor') }}">
                                            @endif
                                            <x-ui.action-button type="submit" title="Buat Payment" aria-label="Buat Payment" variant="success" size="icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                    <path d="M12 2a1 1 0 0 1 1 1v1.062a7.002 7.002 0 0 1 5.938 5.938H20a1 1 0 1 1 0 2h-1.062a7.002 7.002 0 0 1-5.938 5.938V19a1 1 0 1 1-2 0v-1.062a7.002 7.002 0 0 1-5.938-5.938H4a1 1 0 1 1 0-2h1.062a7.002 7.002 0 0 1 5.938-5.938V3a1 1 0 0 1 1-1zm0 4a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm-1 5V9a1 1 0 1 1 2 0v2h2a1 1 0 1 1 0 2h-2v2a1 1 0 1 1-2 0v-2H9a1 1 0 1 1 0-2h2z"/>
                                                </svg>
                                            </x-ui.action-button>
                                        </form>
                                    @elseif($invoice->status?->value === 'paid')
                                        <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700">Paid</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty-row :colspan="8" message="Belum ada data invoice." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <x-ui.panel class="mt-4" title="PO Siap Ditagihkan" subtitle="PO approved/processed yang belum masuk invoice." bodyClass="p-0">
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
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($pendingPurchaseOrders ?? collect()) as $po)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                <div>{{ $po->number }}</div>
                                @if(str_contains((string) ($po->notes ?? ''), '[BARANG TAMBAHAN]'))
                                    <div class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800">ADA ITEM TAMBAHAN</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $po->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $po->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($po->order_date)->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.status-pill
                                    :value="$po->status?->value ?? '-'"
                                    :classes="[
                                        'draft' => 'bg-slate-100 text-slate-700',
                                        'submitted' => 'bg-amber-100 text-amber-700',
                                        'approved' => 'bg-cyan-100 text-cyan-700',
                                        'processed' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                    ]"
                                />
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah($po->total_amount)</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    @if($canManageFinanceWrites)
                                        <form method="POST" action="{{ route('ui.purchase-orders.generate-invoice', $po) }}">
                                            @csrf
                                            @if(request()->filled('vendor'))
                                                <input type="hidden" name="vendor" value="{{ request('vendor') }}">
                                            @endif
                                            <x-ui.action-button type="submit" variant="primary" size="sm">Generate Invoice</x-ui.action-button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty-row :colspan="7" message="Tidak ada PO siap ditagihkan." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
@endsection
