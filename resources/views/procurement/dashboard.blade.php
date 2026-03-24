@extends('layouts.procurement')

@section('title', 'Procurement Dashboard')

@section('content')
    @php
        $currentUser = auth()->user();
        $now = now('Asia/Jakarta');
        $hour = (int) $now->format('H');
        $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];
        $monthMap = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
        $dayName = $dayMap[$now->format('l')] ?? $now->format('l');
        $dateLabel = $dayName.', '.$now->format('d').' '.($monthMap[(int) $now->format('n')] ?? $now->format('F')).' '.$now->format('Y');
        $timeLabel = $now->format('H:i:s');
    @endphp

    <x-ui.hero class="mb-6" eyebrow="Admin Dashboard" :title="$greeting . ', ' . ($currentUser?->name ?? 'Pengguna')" description="Ringkasan aktivitas procurement, inventori, ekspedisi, dan billing dalam satu panel terintegrasi.">
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-100">
            <span class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1">{{ $dateLabel }}</span>
            <span class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1">WIB {{ $timeLabel }}</span>
        </div>
    </x-ui.hero>

    <div class="mb-4">
        <h3 class="text-lg font-semibold text-slate-900">Ringkasan Utama</h3>
        <p class="text-sm text-slate-500">Snapshot operasional harian untuk monitoring cepat.</p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat-card label="SPPG" :value="$stats['sppgs']" />
        <x-ui.stat-card label="Vendors" :value="$stats['vendors']" />
        <x-ui.stat-card label="Products" :value="$stats['products']" />
        <x-ui.stat-card label="Purchase Requests" :value="$stats['purchase_requests']" />
        <x-ui.stat-card label="Purchase Orders" :value="$stats['purchase_orders']" />
        <x-ui.stat-card label="Ekspedisi">
            <p class="font-medium text-slate-700">On Proses: <span class="font-semibold text-slate-900">{{ $stats['expedition_on_process'] ?? 0 }}</span></p>
            <p class="mt-1 font-medium text-slate-700">Delivered: <span class="font-semibold text-slate-900">{{ $stats['expedition_delivered'] ?? 0 }}</span></p>
        </x-ui.stat-card>
        @if(($showFundingSummaryMetrics ?? false) === true)
            <x-ui.stat-card label="Dana Pembelian">
                <p class="text-xs font-medium text-slate-700">Menunggu Owner: <span class="font-semibold text-slate-900">{{ $stats['funding_pending_owner'] ?? 0 }}</span></p>
                <p class="mt-1 text-xs font-medium text-slate-700">Dana Cair: <span class="font-semibold text-slate-900">@rupiah($stats['funding_disbursed_total'] ?? 0)</span></p>
                <p class="mt-1 text-xs font-medium text-slate-700">Sisa Dana: <span class="font-semibold text-slate-900">@rupiah($stats['funding_remaining_total'] ?? 0)</span></p>
            </x-ui.stat-card>
        @endif
        <x-ui.stat-card label="Open Stock Alerts" :value="$stats['open_stock_alerts']" :emphasis="true" />
    </div>

    @if(($showAssetAndChartMetrics ?? false) === true || ($showProfitMarginMetric ?? false) === true)
        <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
            @if(($showAssetAndChartMetrics ?? false) === true)
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-800">Total Aset</h3>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">@rupiah($totalAssetValue ?? 0)</p>
                    <p class="mt-1 text-xs text-slate-500">Estimasi nilai aset berbasis stok dan harga referensi produk.</p>
                </section>
            @endif

            @if(($showProfitMarginMetric ?? false) === true)
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-800">Margin Keuntungan</h3>
                    <p class="mt-1 text-2xl font-semibold {{ ($totalProfitMargin ?? 0) >= 0 ? 'text-slate-900' : 'text-rose-700' }}">@rupiah($totalProfitMargin ?? 0)</p>
                    <p class="mt-1 text-xs text-slate-500">Estimasi margin dari selisih harga jual dan harga beli pada stok aktif.</p>
                </section>
            @endif
        </div>
    @endif

    @if(($showAssetAndChartMetrics ?? false) === true)
        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-slate-800">Grafik Order Barang Berdasarkan SPPG</h3>
                <p class="text-xs text-slate-500">Urutan SPPG dengan total kuantitas order tertinggi.</p>
            </div>

            <div class="space-y-3">
                @forelse(($ordersBySppg ?? collect()) as $row)
                    @php
                        $barWidth = ($maxOrderQtyBySppg ?? 0) > 0
                            ? max(2, min(100, (($row['total_qty'] ?? 0) / $maxOrderQtyBySppg) * 100))
                            : 0;
                    @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-700">{{ $row['sppg_name'] }}</span>
                            <span class="text-gray-500">Qty: {{ number_format((float) ($row['total_qty'] ?? 0), 0, ',', '.') }} • @rupiah($row['total_amount'] ?? 0)</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-slate-800" style="width: {{ $barWidth }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada data order barang per SPPG.</p>
                @endforelse
            </div>
        </section>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-800">Recent Purchase Requests</h3>
            <div class="space-y-3">
                @forelse($recentPurchaseRequests as $pr)
                    <div class="rounded-xl border border-slate-100 p-3">
                        <p class="text-sm font-medium">{{ $pr->number }}</p>
                        <p class="text-xs text-slate-500">{{ $pr->sppg?->name }} • {{ $pr->status?->value }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ optional($pr->request_date)->format('d M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada data PR.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-800">Recent Purchase Orders</h3>
            <div class="space-y-3">
                @forelse($recentPurchaseOrders as $po)
                    <div class="rounded-xl border border-slate-100 p-3">
                        <p class="text-sm font-medium">{{ $po->number }}</p>
                        <p class="text-xs text-slate-500">{{ $po->vendor?->name }} • {{ $po->status?->value }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ optional($po->order_date)->format('d M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada data PO.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-800">Recent Invoices</h3>
            <div class="space-y-3">
                @forelse($recentInvoices as $invoice)
                    <div class="rounded-xl border border-slate-100 p-3">
                        <p class="text-sm font-medium">{{ $invoice->number }}</p>
                        <p class="text-xs text-slate-500">{{ $invoice->vendor?->name }} • {{ $invoice->status?->value }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ optional($invoice->invoice_date)->format('d M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada data invoice.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
