@extends('layouts.procurement')

@section('title', 'Billing Cycles')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Billing Cycles (Weekly)</h2>
        <p class="text-sm text-gray-500">Siklus penagihan mingguan per SPPG untuk proses invoice/payment.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3">Week Start</th>
                        <th class="px-4 py-3">Week End</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($billingCycles as $cycle)
                        <tr>
                            <td class="px-4 py-3">{{ $cycle->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($cycle->week_start_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ optional($cycle->week_end_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $cycle->status?->value ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $cycle->creator?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada billing cycle.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $billingCycles->links() }}</div>
@endsection
