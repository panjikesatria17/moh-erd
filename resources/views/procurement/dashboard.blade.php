@extends('layouts.procurement')

@section('title', 'Procurement Dashboard')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold">Dashboard</h2>
        <p class="text-sm text-gray-500">Ringkasan aktivitas procurement, stok, dan billing mingguan.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">SPPG</p>
            <p class="mt-1 text-2xl font-semibold">{{ $stats['sppgs'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Vendors</p>
            <p class="mt-1 text-2xl font-semibold">{{ $stats['vendors'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Products</p>
            <p class="mt-1 text-2xl font-semibold">{{ $stats['products'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Purchase Requests</p>
            <p class="mt-1 text-2xl font-semibold">{{ $stats['purchase_requests'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Purchase Orders</p>
            <p class="mt-1 text-2xl font-semibold">{{ $stats['purchase_orders'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Open Stock Alerts</p>
            <p class="mt-1 text-2xl font-semibold text-red-600">{{ $stats['open_stock_alerts'] }}</p>
        </div>
    </div>

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
