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

    <div class="mb-6 rounded-xl border border-slate-200/70 bg-white/80 p-4 shadow-sm backdrop-blur">
        <p class="text-sm font-medium text-slate-800">{{ $greeting }}, {{ $currentUser?->name ?? 'Pengguna' }}</p>
        <p class="mt-1 text-sm text-slate-600">Selamat datang di dashboard procurement. Semoga aktivitas hari ini lancar.</p>
        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
            <span>Hari/Tanggal: <strong class="text-slate-700">{{ $dateLabel }}</strong></span>
            <span>Jam (WIB): <strong class="text-slate-700">{{ $timeLabel }}</strong></span>
        </div>
    </div>

    <div class="mb-6">
        <h2 class="text-xl font-semibold">Dashboard</h2>
        <p class="text-sm text-gray-500">Ringkasan aktivitas procurement, stok, dan billing mingguan.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4">
            <p class="text-sm text-blue-700/80">SPPG</p>
            <p class="mt-1 text-2xl font-semibold text-blue-900">{{ $stats['sppgs'] }}</p>
        </div>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-4">
            <p class="text-sm text-emerald-700/80">Vendors</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $stats['vendors'] }}</p>
        </div>
        <div class="rounded-xl border border-violet-100 bg-violet-50/70 p-4">
            <p class="text-sm text-violet-700/80">Products</p>
            <p class="mt-1 text-2xl font-semibold text-violet-900">{{ $stats['products'] }}</p>
        </div>
        <div class="rounded-xl border border-amber-100 bg-amber-50/70 p-4">
            <p class="text-sm text-amber-700/80">Purchase Requests</p>
            <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $stats['purchase_requests'] }}</p>
        </div>
        <div class="rounded-xl border border-cyan-100 bg-cyan-50/70 p-4">
            <p class="text-sm text-cyan-700/80">Purchase Orders</p>
            <p class="mt-1 text-2xl font-semibold text-cyan-900">{{ $stats['purchase_orders'] }}</p>
        </div>
        <div class="rounded-xl border border-sky-100 bg-sky-50/70 p-4">
            <p class="text-sm text-sky-700/80">Ekspedisi</p>
            <p class="mt-1 text-sm font-medium text-sky-900">On Proses: <span class="font-semibold">{{ $stats['expedition_on_process'] ?? 0 }}</span></p>
            <p class="mt-1 text-sm font-medium text-sky-900">Delivered: <span class="font-semibold">{{ $stats['expedition_delivered'] ?? 0 }}</span></p>
        </div>
        @if(($showFundingSummaryMetrics ?? false) === true)
            <div class="rounded-xl border border-cyan-100 bg-cyan-50/70 p-4">
                <p class="text-sm text-cyan-700/80">Dana Pembelian</p>
                <p class="mt-1 text-xs font-medium text-cyan-900">Menunggu Owner: <span class="font-semibold">{{ $stats['funding_pending_owner'] ?? 0 }}</span></p>
                <p class="mt-1 text-xs font-medium text-cyan-900">Dana Cair: <span class="font-semibold">@rupiah($stats['funding_disbursed_total'] ?? 0)</span></p>
                <p class="mt-1 text-xs font-medium text-cyan-900">Sisa Dana: <span class="font-semibold">@rupiah($stats['funding_remaining_total'] ?? 0)</span></p>
            </div>
        @endif
        <div class="rounded-xl border border-rose-100 bg-rose-50/70 p-4">
            <p class="text-sm text-rose-700/80">Open Stock Alerts</p>
            <p class="mt-1 text-2xl font-semibold text-red-600">{{ $stats['open_stock_alerts'] }}</p>
        </div>
    </div>

    @if(($showAssetAndChartMetrics ?? false) === true || ($showProfitMarginMetric ?? false) === true)
        <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
            @if(($showAssetAndChartMetrics ?? false) === true)
                <section class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-4">
                    <h3 class="text-sm font-semibold text-emerald-800">Total Aset</h3>
                    <p class="mt-1 text-2xl font-semibold text-emerald-900">@rupiah($totalAssetValue ?? 0)</p>
                    <p class="mt-1 text-xs text-emerald-700/80">Estimasi nilai aset berbasis stok dan harga referensi produk.</p>
                </section>
            @endif

            @if(($showProfitMarginMetric ?? false) === true)
                <section class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4">
                    <h3 class="text-sm font-semibold text-indigo-800">Margin Keuntungan</h3>
                    <p class="mt-1 text-2xl font-semibold {{ ($totalProfitMargin ?? 0) >= 0 ? 'text-indigo-900' : 'text-red-700' }}">@rupiah($totalProfitMargin ?? 0)</p>
                    <p class="mt-1 text-xs text-indigo-700/80">Estimasi margin dari selisih harga jual dan harga beli pada stok aktif.</p>
                </section>
            @endif
        </div>
    @endif

    @if(($showAssetAndChartMetrics ?? false) === true)
        <section class="mt-4 rounded-xl border border-gray-200 bg-white p-4">
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700">Grafik Order Barang Berdasarkan SPPG</h3>
                <p class="text-xs text-gray-500">Urutan SPPG dengan total kuantitas order tertinggi.</p>
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
                        <div class="h-2 rounded-full bg-gray-100">
                            <div class="h-2 rounded-full bg-blue-500" style="width: {{ $barWidth }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada data order barang per SPPG.</p>
                @endforelse
            </div>
        </section>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <section class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">Recent Purchase Requests</h3>
            <div class="space-y-3">
                @forelse($recentPurchaseRequests as $pr)
                    <div class="rounded-lg border border-gray-100 p-3">
                        <p class="text-sm font-medium">{{ $pr->number }}</p>
                        <p class="text-xs text-gray-500">{{ $pr->sppg?->name }} • {{ $pr->status?->value }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ optional($pr->request_date)->format('d M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada data PR.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">Recent Purchase Orders</h3>
            <div class="space-y-3">
                @forelse($recentPurchaseOrders as $po)
                    <div class="rounded-lg border border-gray-100 p-3">
                        <p class="text-sm font-medium">{{ $po->number }}</p>
                        <p class="text-xs text-gray-500">{{ $po->vendor?->name }} • {{ $po->status?->value }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ optional($po->order_date)->format('d M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada data PO.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">Recent Invoices</h3>
            <div class="space-y-3">
                @forelse($recentInvoices as $invoice)
                    <div class="rounded-lg border border-gray-100 p-3">
                        <p class="text-sm font-medium">{{ $invoice->number }}</p>
                        <p class="text-xs text-gray-500">{{ $invoice->vendor?->name }} • {{ $invoice->status?->value }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ optional($invoice->invoice_date)->format('d M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada data invoice.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
