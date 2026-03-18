@extends('layouts.procurement')

@section('title', 'Billing Cycles')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Billing Cycles (Weekly)</h2>
        <p class="text-sm text-gray-500">Siklus penagihan mingguan per SPPG untuk proses invoice/payment.</p>
    </div>

    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-amber-900">Aturan Approval PO Aktif</p>
                <p class="text-sm text-amber-800">
                    PO di atas <span class="font-semibold">Rp {{ number_format((float) ($poOwnerApprovalThreshold ?? 5000000), 0, ',', '.') }}</span>
                    wajib approval owner.
                </p>
                <p class="text-xs text-amber-700">PO menunggu approval owner saat ini: {{ (int) ($pendingPoOwnerApprovals ?? 0) }}</p>
            </div>
            @if(in_array(auth()->user()?->role?->value, [\App\Enums\UserRole::SUPER_ADMIN->value, \App\Enums\UserRole::ADMIN->value, \App\Enums\UserRole::OWNER->value], true))
                <a href="{{ route('ui.approvals.index') }}" class="inline-flex items-center rounded-md bg-amber-600 px-3 py-2 text-xs font-medium text-white hover:bg-amber-700">
                    Buka Approval Queue
                </a>
            @endif
        </div>
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
