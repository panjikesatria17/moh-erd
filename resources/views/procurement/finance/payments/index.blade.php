@extends('layouts.procurement')

@section('title', 'Payments')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Payments</h2>
        <p class="text-sm text-gray-500">Monitoring pembayaran invoice vendor.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Paid By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $payment->number }}</td>
                            <td class="px-4 py-3">{{ $payment->invoice?->number ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($payment->payment_date)->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $payment->status?->value ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $payment->payer?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada data pembayaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
