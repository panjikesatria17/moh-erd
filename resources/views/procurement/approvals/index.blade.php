@extends('layouts.procurement')

@section('title', 'Approval Queue')

@section('content')
    @php
        $formatMoneyInput = static fn ($value) => $value === null || $value === ''
            ? ''
            : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    @endphp

    <div class="mb-4">
        <h2 class="text-xl font-semibold">Approval Queue</h2>
        <p class="text-sm text-gray-500">Daftar antrian approval dokumen procurement lintas modul.</p>

        @if(in_array(auth()->user()?->role?->value, [\App\Enums\UserRole::SUPER_ADMIN->value, \App\Enums\UserRole::ADMIN->value, \App\Enums\UserRole::OWNER->value], true))
        <form method="POST" action="{{ route('ui.approvals.settings.po-threshold.update') }}" class="mt-3 flex flex-wrap items-end gap-2 rounded-lg border border-gray-200 bg-white p-3">
            @csrf
            <div class="w-full sm:w-auto">
                <label class="mb-1 block text-xs font-medium text-gray-600">Threshold Approval Owner untuk PO</label>
                <input
                    type="text"
                    inputmode="decimal"
                    name="po_owner_approval_threshold"
                    value="{{ $formatMoneyInput(old('po_owner_approval_threshold', (float) ($poOwnerApprovalThreshold ?? 5000000))) }}"
                    class="js-idr-input w-full sm:w-64 rounded-md border border-gray-300 px-3 py-2 text-sm"
                >
                <p class="mt-1 text-xs text-gray-500">Contoh: 5000000, berarti PO di atas 5 juta wajib approval owner.</p>
            </div>
            <button type="submit" class="w-full sm:w-auto rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">Simpan Threshold</button>
        </form>
        @endif
    </div>

    <script>
        (() => {
            const input = document.querySelector('.js-idr-input');
            if (!input) {
                return;
            }

            const parseNumber = (value) => {
                const raw = String(value ?? '').trim();
                if (raw === '') {
                    return null;
                }

                let normalized = raw.replace(/\s+/g, '');
                const hasDot = normalized.includes('.');
                const hasComma = normalized.includes(',');

                if (hasDot && hasComma) {
                    normalized = normalized.replace(/\./g, '').replace(/,/g, '.');
                } else if (hasDot && /^-?\d{1,3}(\.\d{3})+$/.test(normalized)) {
                    normalized = normalized.replace(/\./g, '');
                } else if (hasComma) {
                    normalized = normalized.replace(/,/g, '.');
                }

                normalized = normalized.replace(/[^0-9.\-]/g, '');
                if (normalized === '' || normalized === '-' || normalized === '.' || normalized === '-.') {
                    return null;
                }

                if ((normalized.match(/\./g) || []).length > 1) {
                    const parts = normalized.split('.');
                    const decimalPart = parts.pop();
                    normalized = `${parts.join('')}.${decimalPart}`;
                }

                const parsed = Number.parseFloat(normalized);
                return Number.isFinite(parsed) ? parsed : null;
            };

            const formatNumberId = (value) => value.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });

            input.addEventListener('blur', () => {
                const parsed = parseNumber(input.value);
                if (parsed === null) {
                    return;
                }

                input.value = formatNumberId(parsed);
            });

            const initial = parseNumber(input.value);
            if (initial !== null) {
                input.value = formatNumberId(initial);
            }
        })();
    </script>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Approver</th>
                        <th class="px-4 py-3">Dokumen</th>
                        <th class="px-4 py-3">Level</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Approved At</th>
                        <th class="px-4 py-3">Catatan</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($approvals as $approval)
                        <tr>
                            <td class="px-4 py-3">{{ $approval->approver?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ class_basename($approval->approvable_type) }} #{{ $approval->approvable_id }}</td>
                            <td class="px-4 py-3">{{ $approval->level }}</td>
                            <td class="px-4 py-3">{{ $approval->status?->value ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($approval->approved_at)->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $approval->note ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @if($approval->approved_at === null)
                                        <form method="POST" action="{{ route('ui.approvals.approve', $approval) }}">
                                            @csrf
                                            <button type="submit" title="Approve" aria-label="Approve" class="inline-flex items-center justify-center rounded-md bg-emerald-600 p-1.5 text-white hover:bg-emerald-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                    <path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                                </svg>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('ui.approvals.reject', $approval) }}" onsubmit="return confirm('Tolak approval ini?')">
                                            @csrf
                                            <button type="submit" title="Reject" aria-label="Reject" class="inline-flex items-center justify-center rounded-md bg-rose-600 p-1.5 text-white hover:bg-rose-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                    <path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.29 19.7 2.88 18.3 9.17 12 2.88 5.71 4.29 4.29l6.3 6.3 6.29-6.3z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">Selesai</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada antrian approval.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $approvals->links() }}</div>
@endsection
