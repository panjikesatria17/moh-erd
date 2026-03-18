@extends('layouts.procurement')

@section('title', 'Invoices')

@section('content')
    @php
        $currentRoleRaw = auth()->user()?->role?->value;
        $currentRole = in_array($currentRoleRaw, ['admin', 'owner'], true) ? 'super_admin' : $currentRoleRaw;
        $canManageFinanceWrites = in_array($currentRole, ['super_admin', 'finance'], true);
    @endphp

    <div class="mb-4">
        <h2 class="text-xl font-semibold">Invoices</h2>
        <p class="text-sm text-gray-500">Tagihan vendor untuk billing mingguan per SPPG.</p>
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
                <a href="{{ route('ui.invoices.summary.download', ['vendor' => $selectedVendor->id, 'week_start' => $weekStartDate, 'week_end' => $weekEndDate]) }}" title="Export Rekap PDF" aria-label="Export Rekap PDF" class="inline-flex items-center justify-center rounded-md border border-blue-300 p-1.5 text-blue-700 hover:bg-blue-50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M12 3a1 1 0 0 1 1 1v8.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4.004 4.004a1 1 0 0 1-1.414 0L7.285 11.71a1 1 0 0 1 1.414-1.414L11 12.586V4a1 1 0 0 1 1-1z"/>
                        <path d="M5 15a1 1 0 0 1 1 1v2h12v-2a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1z"/>
                    </svg>
                </a>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                    <p class="text-xs font-medium text-blue-700">Total Invoice Vendor Minggu Ini</p>
                    <p class="mt-1 text-lg font-semibold text-blue-900">@rupiah($weeklyVendorInvoiceTotal ?? 0)</p>
                    <p class="mt-0.5 text-xs text-blue-700">Periode {{ \Illuminate\Support\Carbon::parse($weekStartDate)->format('d M Y') }} - {{ \Illuminate\Support\Carbon::parse($weekEndDate)->format('d M Y') }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <p class="text-xs font-medium text-gray-500">Jumlah Invoice Vendor Minggu Ini</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format((int) ($weeklyVendorInvoiceCount ?? 0), 0, ',', '.') }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">Dokumen invoice terdaftar</p>
                </div>
            </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
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
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium">{{ $invoice->status?->value }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah($invoice->total_amount)</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('ui.invoices.download', $invoice) }}" title="Download PDF" aria-label="Download PDF" class="inline-flex items-center justify-center rounded-md border border-blue-300 p-1.5 text-blue-700 hover:bg-blue-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                            <path d="M6 9V3h12v6h-2V5H8v4H6zm10 4h2a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2v6h8v-6zm-6 4v-4h4v4h-4z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('ui.payments.index', ['invoice' => $invoice->id]) }}" title="Lihat Pembayaran" aria-label="Lihat Pembayaran" class="inline-flex items-center justify-center rounded-md border border-gray-300 p-1.5 text-gray-700 hover:bg-gray-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                            <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v1H2V6zm0 3h20v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9zm4 5a1 1 0 0 0 0 2h3a1 1 0 1 0 0-2H6z"/>
                                        </svg>
                                    </a>
                                    @if($invoice->status?->value !== 'paid' && $canManageFinanceWrites)
                                        <form method="POST" action="{{ route('ui.invoices.create-payment', $invoice) }}">
                                            @csrf
                                            @if(request()->filled('vendor'))
                                                <input type="hidden" name="vendor" value="{{ request('vendor') }}">
                                            @endif
                                            <button type="submit" title="Buat Payment" aria-label="Buat Payment" class="inline-flex items-center justify-center rounded-md bg-emerald-600 p-1.5 text-white hover:bg-emerald-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                    <path d="M12 2a1 1 0 0 1 1 1v1.062a7.002 7.002 0 0 1 5.938 5.938H20a1 1 0 1 1 0 2h-1.062a7.002 7.002 0 0 1-5.938 5.938V19a1 1 0 1 1-2 0v-1.062a7.002 7.002 0 0 1-5.938-5.938H4a1 1 0 1 1 0-2h1.062a7.002 7.002 0 0 1 5.938-5.938V3a1 1 0 0 1 1-1zm0 4a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm-1 5V9a1 1 0 1 1 2 0v2h2a1 1 0 1 1 0 2h-2v2a1 1 0 1 1-2 0v-2H9a1 1 0 1 1 0-2h2z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @elseif($invoice->status?->value === 'paid')
                                        <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700">Paid</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Belum ada data invoice.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-700">PO Siap Ditagihkan</h3>
            <p class="text-xs text-gray-500">PO approved/processed yang belum masuk invoice.</p>
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
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium">{{ $po->status?->value ?? '-' }}</span>
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
                                            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Generate Invoice</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">Tidak ada PO siap ditagihkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
@endsection
