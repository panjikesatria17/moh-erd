@extends('layouts.procurement')

@section('title', 'Kwitansi')

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
        title="Kwitansi Penagihan"
        description="Gabungkan beberapa invoice vendor (misal 2-3 invoice) menjadi 1 dokumen kwitansi."
    >
        <form method="GET" action="{{ route('ui.kwitansi.index') }}" class="mt-3 flex flex-wrap items-center gap-2">
            <select name="vendor" class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-1.5 text-sm">
                <option value="">Pilih Vendor</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((int) request('vendor') === (int) $vendor->id)>{{ $vendor->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="w-full sm:w-auto rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Muat Invoice</button>
            @if(request()->filled('vendor'))
                <a href="{{ route('ui.kwitansi.index') }}" class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-1.5 text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            @endif
        </form>
    </x-ui.hero>

    @if($canManageFinanceWrites)
    <x-ui.panel class="mb-4" title="Buat Kwitansi Baru" subtitle="Pilih vendor dan centang invoice yang ingin digabungkan ke dalam 1 kwitansi.">
        <form method="POST" action="{{ route('ui.kwitansi.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Vendor</label>
                    <select name="vendor_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                        <option value="">Pilih Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((int) old('vendor_id', request('vendor')) === (int) $vendor->id)>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Tanggal Kwitansi</label>
                    <input type="date" name="receipt_date" value="{{ old('receipt_date', now()->toDateString()) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Telah Diterima Dari</label>
                    <input type="text" name="billed_to" value="{{ old('billed_to', $selectedVendor?->name) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: SPPG RANGGAMEKAR 3" required>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Catatan (opsional)</label>
                <textarea name="notes" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: Tagihan gabungan minggu ke-1">{{ old('notes') }}</textarea>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200">
                <div class="border-b border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-600">Invoice Tersedia (belum paid & belum masuk kwitansi)</div>
                <div class="max-h-72 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-3 py-2">Pilih</th>
                                <th class="px-3 py-2">Invoice</th>
                                <th class="px-3 py-2">SPPG</th>
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($availableInvoices as $invoice)
                                <tr>
                                    <td class="px-3 py-2">
                                        <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" @checked(in_array((int) $invoice->id, collect(old('invoice_ids', []))->map(fn($id) => (int) $id)->all(), true)) class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                    </td>
                                    <td class="px-3 py-2 font-medium">{{ $invoice->number }}</td>
                                    <td class="px-3 py-2">{{ $invoice->sppg?->name ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ optional($invoice->invoice_date)->format('d M Y') }}</td>
                                    <td class="px-3 py-2">{{ $invoice->status?->value ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">@rupiah($invoice->total_amount)</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">Tidak ada invoice tersedia untuk vendor/filter saat ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Buat Kwitansi</button>
            </div>
        </form>
    </x-ui.panel>
    @endif

    <x-ui.panel title="Daftar Kwitansi" subtitle="Rekap dokumen kwitansi yang sudah dibuat" bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Nomor</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Ditagihkan Ke</th>
                        <th class="px-4 py-3 text-right">Jumlah Invoice</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($kwitansis as $kwitansi)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $kwitansi->number }}</td>
                            <td class="px-4 py-3">{{ $kwitansi->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($kwitansi->receipt_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $kwitansi->billed_to }}</td>
                            <td class="px-4 py-3 text-right">{{ $kwitansi->invoices->count() }}</td>
                            <td class="px-4 py-3 text-right">@rupiah($kwitansi->total_amount)</td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.action-link href="{{ route('ui.kwitansi.download', $kwitansi) }}" title="Download Kwitansi" aria-label="Download Kwitansi" variant="blue-outline" size="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                        <path d="M6 9V3h12v6h-2V5H8v4H6zm10 4h2a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2v6h8v-6zm-6 4v-4h4v4h-4z"/>
                                    </svg>
                                </x-ui.action-link>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada kwitansi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <div class="mt-4">{{ $kwitansis->links() }}</div>
@endsection
