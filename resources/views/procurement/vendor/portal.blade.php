@extends('layouts.procurement')

@section('title', 'Portal Vendor')

@section('content')
    <x-ui.hero class="mb-5" eyebrow="Vendor Dashboard" title="Portal Vendor" description="Kelola stok barang vendor, pantau margin yayasan, dan catat pembayaran selisih harga ke yayasan dalam satu panel terintegrasi." />

    <form method="GET" action="{{ route('ui.vendor.portal') }}" class="mb-4 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4 md:grid-cols-4">
        <div>
            <label for="start_date" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Mulai</label>
            <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </div>
        <div>
            <label for="end_date" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Selesai</label>
            <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </div>
        <div class="md:col-span-2 flex flex-col items-stretch gap-2 pt-1 sm:flex-row sm:items-end">
            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 sm:w-auto">Terapkan Filter</button>
            <a href="{{ route('ui.vendor.portal') }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Reset</a>
        </div>
    </form>

    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
        <x-ui.stat-card class="xl:col-span-2" label="Pendapatan Vendor" value="@rupiah($vendorRevenue)" />
        <x-ui.stat-card class="xl:col-span-2" label="Margin Yayasan (Markup)" value="@rupiah($yayasanMarkup)" />
        <x-ui.stat-card label="Nilai Stok Vendor" value="@rupiah($stockValue)" />
        <x-ui.stat-card label="Sisa Bayar ke Yayasan" value="@rupiah($outstandingToFoundation)" :emphasis="$outstandingToFoundation > 0">
            @if($outstandingToFoundation <= 0)
                <span class="text-emerald-700">Lunas</span>
            @endif
        </x-ui.stat-card>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <x-ui.panel class="xl:col-span-2" title="Integrasi Stok Vendor ke Master Data Produk" subtitle="Perubahan stok/harga vendor di sini akan langsung memperbarui data produk master untuk vendor Anda." bodyClass="p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3">Harga Vendor</th>
                            <th class="px-4 py-3">Harga Yayasan</th>
                            <th class="px-4 py-3">Stok</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $product)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-800">{{ $product->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $product->sku }} • {{ $product->unit }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('ui.vendor.stock.update', $product) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', (float) ($product->purchase_price ?? 0)) }}" class="w-28 rounded-md border border-slate-300 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-700">@rupiah((float) ($product->selling_price ?? 0))</td>
                                <td class="px-4 py-3">
                                        <input type="number" step="0.01" min="0" name="total_inventory" value="{{ old('total_inventory', (float) ($product->total_inventory ?? 0)) }}" class="w-24 rounded-md border border-slate-300 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                        <button type="submit" class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada produk aktif untuk vendor ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.panel>

        <x-ui.panel title="Catat Pembayaran Selisih" subtitle="Pembayaran ini adalah transfer selisih markup dari vendor ke yayasan setelah invoice dibayar.">
            <div class="mt-3 space-y-1 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs">
                <p>Total kewajiban dari invoice paid: <span class="font-semibold">@rupiah($totalDueToFoundation)</span></p>
                <p>Total dibayar vendor (approved): <span class="font-semibold">@rupiah($paidToFoundation)</span></p>
                <p>Sisa bayar: <span class="font-semibold {{ $outstandingToFoundation > 0 ? 'text-rose-700' : 'text-emerald-700' }}">@rupiah($outstandingToFoundation)</span></p>
            </div>

            <form method="POST" action="{{ route('ui.vendor.margin-payments.store') }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Tanggal Bayar</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Nominal</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Referensi</label>
                    <input type="text" name="reference_no" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" placeholder="No transfer / bukti">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Catatan</label>
                    <textarea name="notes" rows="2" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" placeholder="Opsional"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Bukti Transfer (Opsional)</label>
                    <input type="file" name="proof_image" accept="image/png,image/jpeg,image/webp" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                </div>
                <button type="submit" class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Catat Pembayaran</button>
            </form>
        </x-ui.panel>
    </div>

    <x-ui.panel class="mb-4" title="Rekap Selisih per Invoice" bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Pendapatan Vendor</th>
                        <th class="px-4 py-3 text-right">Selisih untuk Yayasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoiceRows as $row)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $row->invoice_number }}</p>
                                <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($row->invoice_date)->format('d M Y') }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $row->sppg_name }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $row->invoice_status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah((float) $row->vendor_revenue)</td>
                            <td class="px-4 py-3 text-right">@rupiah((float) $row->yayasan_markup_due)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada invoice pada periode yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <x-ui.panel title="Riwayat Pembayaran Selisih ke Yayasan" bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Referensi</th>
                        <th class="px-4 py-3">Dicatat Oleh</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3">Bukti</th>
                        <th class="px-4 py-3">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($marginPayments as $payment)
                        <tr>
                            <td class="px-4 py-3">{{ optional($payment->payment_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusClass = match($payment->status) {
                                        'approved' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ strtoupper((string) $payment->status) }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $payment->reference_no ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $payment->creator?->name ?: '-' }}</td>
                            <td class="px-4 py-3 text-right">@rupiah((float) $payment->amount)</td>
                            <td class="px-4 py-3">
                                @if($payment->proof_image_path)
                                    <a href="{{ asset('storage/' . $payment->proof_image_path) }}" target="_blank" class="text-blue-600 hover:text-blue-700">Lihat Bukti</a>
                                @else
                                    <form method="POST" action="{{ route('ui.vendor.margin-payments.upload-proof', $payment) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                        @csrf
                                        <input type="file" name="proof_image" accept="image/png,image/jpeg,image/webp" class="w-32 rounded border border-slate-300 px-1 py-1 text-[10px]">
                                        <button type="submit" class="rounded bg-slate-900 px-2 py-1 text-[10px] font-medium text-white">Upload</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p>{{ $payment->notes ?: '-' }}</p>
                                @if($payment->rejection_note)
                                    <p class="mt-1 text-xs text-rose-700">Ditolak: {{ $payment->rejection_note }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada pembayaran selisih tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <x-ui.panel class="mt-4" title="Ledger Otomatis Vendor-Yayasan" subtitle="Akrual muncul saat invoice status paid. Pembayaran approved mengurangi saldo kewajiban." bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Deskripsi</th>
                        <th class="px-4 py-3 text-right">Debit</th>
                        <th class="px-4 py-3 text-right">Kredit</th>
                        <th class="px-4 py-3 text-right">Saldo Kewajiban</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($ledgerEntries as $entry)
                        <tr>
                            <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($entry['entry_date'])->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $entry['entry_type'] === 'invoice_accrual' ? 'Akrual' : 'Pembayaran' }}</td>
                            <td class="px-4 py-3">{{ $entry['description'] }}</td>
                            <td class="px-4 py-3 text-right">@rupiah($entry['debit'])</td>
                            <td class="px-4 py-3 text-right">@rupiah($entry['credit'])</td>
                            <td class="px-4 py-3 text-right font-semibold {{ $entry['running_balance'] > 0 ? 'text-rose-700' : 'text-emerald-700' }}">@rupiah($entry['running_balance'])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada data ledger otomatis pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>
@endsection
