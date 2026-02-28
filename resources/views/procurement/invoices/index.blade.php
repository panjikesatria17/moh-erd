@extends('layouts.procurement')

@section('title', 'Invoices')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Invoices</h2>
        <p class="text-sm text-gray-500">Tagihan vendor untuk billing mingguan per SPPG.</p>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $invoice)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $invoice->number }}</td>
                            <td class="px-4 py-3">{{ $invoice->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($invoice->invoice_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ optional($invoice->due_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium">{{ $invoice->status?->value }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data invoice.</td>
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
