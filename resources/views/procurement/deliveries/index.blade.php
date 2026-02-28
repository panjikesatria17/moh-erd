@extends('layouts.procurement')

@section('title', 'Deliveries')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Deliveries</h2>
        <p class="text-sm text-gray-500">Monitoring pengiriman barang dari gudang/vendor ke SPPG.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
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
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium">{{ $delivery->status?->value }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $delivery->total_amount, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    @if(in_array($delivery->status?->value, ['processed', 'delivered'], true))
                                        <form method="POST" action="{{ route('ui.deliveries.generate-invoice', $delivery) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="date" name="due_date" class="rounded-md border border-gray-300 px-2 py-1 text-xs">
                                            <input type="number" step="0.01" min="0" name="tax_amount" placeholder="Tax" class="w-20 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Generate Invoice</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Belum ada data delivery.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $deliveries->links() }}
    </div>
@endsection
