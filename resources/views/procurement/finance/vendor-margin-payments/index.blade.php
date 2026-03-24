@extends('layouts.procurement')

@section('title', 'Settlement Vendor-Yayasan')

@section('content')
    @php
        $submittedCount = $payments->where('status', 'submitted')->count();
        $approvedCount = $payments->where('status', 'approved')->count();
        $pendingAmount = (float) $payments->where('status', 'submitted')->sum('amount');
    @endphp

    <x-ui.hero class="mb-4 sm:mb-5 px-3! py-4! sm:px-6! sm:py-6!" eyebrow="Finance Operations" title="Settlement Vendor-Yayasan" description="Kelola verifikasi pembayaran selisih vendor dengan alur approval yang cepat, rapi, dan mudah dipantau dari perangkat mobile.">
        <div class="grid grid-cols-2 gap-2 sm:mt-1 sm:grid-cols-3 sm:gap-3">
            <div class="rounded-xl border border-white/15 bg-white/10 p-2.5 backdrop-blur-sm sm:p-3">
                <p class="text-[11px] uppercase tracking-wide text-slate-200">Total Data</p>
                <p class="mt-1 text-base font-semibold sm:text-lg">{{ $payments->total() }}</p>
            </div>
            <div class="rounded-xl border border-white/15 bg-white/10 p-2.5 backdrop-blur-sm sm:p-3">
                <p class="text-[11px] uppercase tracking-wide text-slate-200">Butuh Approval</p>
                <p class="mt-1 text-base font-semibold sm:text-lg">{{ $submittedCount }}</p>
            </div>
            <div class="col-span-2 rounded-xl border border-white/15 bg-white/10 p-2.5 backdrop-blur-sm sm:col-span-1 sm:p-3">
                <p class="text-[11px] uppercase tracking-wide text-slate-200">Nominal Pending</p>
                <p class="mt-1 text-sm font-semibold leading-tight sm:text-lg">@rupiah($pendingAmount)</p>
            </div>
        </div>
    </x-ui.hero>

    <form method="GET" action="{{ route('ui.finance.vendor-margin-payments.index') }}" class="mb-4 grid grid-cols-1 gap-2.5 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:gap-3 sm:p-4 md:grid-cols-5">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Vendor</label>
            <select name="vendor" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm leading-tight">
                <option value="">Semua Vendor</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ (int) $selectedVendorId === (int) $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm leading-tight">
                <option value="">Semua Status</option>
                <option value="submitted" {{ $selectedStatus === 'submitted' ? 'selected' : '' }}>SUBMITTED</option>
                <option value="approved" {{ $selectedStatus === 'approved' ? 'selected' : '' }}>APPROVED</option>
                <option value="rejected" {{ $selectedStatus === 'rejected' ? 'selected' : '' }}>REJECTED</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Mulai</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm leading-tight">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Selesai</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm leading-tight">
        </div>
        <div class="flex flex-col items-stretch gap-2 pt-1 sm:flex-row sm:items-end">
            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 md:w-auto">Filter</button>
            <a href="{{ route('ui.finance.vendor-margin-payments.index') }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50 md:w-auto">Reset</a>
        </div>
    </form>

    <div class="space-y-2.5 md:hidden">
        @forelse($payments as $payment)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vendor</p>
                        <h3 class="mt-1 wrap-break-word text-sm font-semibold text-slate-900">{{ $payment->vendor?->name ?: '-' }}</h3>
                    </div>
                    <x-ui.status-pill
                        class="shrink-0"
                        :value="$payment->status"
                        :classes="[
                            'approved' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-rose-100 text-rose-700',
                            'submitted' => 'bg-amber-100 text-amber-700',
                        ]"
                        :uppercase="true"
                    />
                </div>
                <div class="mt-2.5 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-2.5">
                        <p class="text-slate-500">Tanggal</p>
                        <p class="mt-0.5 font-medium text-slate-800">{{ optional($payment->payment_date)->format('d M Y') }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-2.5">
                        <p class="text-slate-500">Nominal</p>
                        <p class="mt-0.5 font-semibold text-slate-900">@rupiah((float) $payment->amount)</p>
                    </div>
                </div>
                <div class="mt-2.5 text-xs">
                    <p class="text-slate-500">Referensi</p>
                    <p class="mt-0.5 wrap-break-word font-medium text-slate-800">{{ $payment->reference_no ?: '-' }}</p>
                </div>
                <div class="mt-2.5 text-xs">
                    @if($payment->proof_image_path)
                        <a href="{{ asset('storage/' . $payment->proof_image_path) }}" target="_blank" class="font-semibold text-sky-700">Lihat Bukti Pembayaran</a>
                    @else
                        <span class="text-slate-500">Belum ada bukti pembayaran</span>
                    @endif
                </div>
                <div class="mt-2.5 border-t border-slate-100 pt-2.5">
                    @if($payment->status !== 'approved')
                        <div class="space-y-2">
                            <form method="POST" action="{{ route('ui.finance.vendor-margin-payments.approve', $payment) }}">
                                @csrf
                                <x-ui.action-button type="submit" variant="success" size="sm" :full-width="true" class="rounded-lg">Approve</x-ui.action-button>
                            </form>
                            <form method="POST" action="{{ route('ui.finance.vendor-margin-payments.reject', $payment) }}" class="space-y-2">
                                @csrf
                                <input type="text" name="rejection_note" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" placeholder="Alasan reject" required>
                                <x-ui.action-button type="submit" variant="danger" size="sm" :full-width="true" class="rounded-lg">Reject</x-ui.action-button>
                            </form>
                        </div>
                    @else
                        <p class="text-xs text-emerald-700">Approved oleh {{ $payment->approver?->name ?: '-' }}</p>
                    @endif
                </div>
            </article>
        @empty
            <x-ui.empty-state message="Belum ada pembayaran selisih pada filter ini." class="rounded-2xl" />
        @endforelse
    </div>

    <x-ui.panel class="hidden md:block" bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Referensi</th>
                        <th class="px-4 py-3">Bukti</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3">Aksi Finance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $payment)
                        <tr>
                            <td class="px-4 py-3">{{ $payment->vendor?->name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ optional($payment->payment_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <x-ui.status-pill
                                    :value="$payment->status"
                                    :classes="[
                                        'approved' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                        'submitted' => 'bg-amber-100 text-amber-700',
                                    ]"
                                    :uppercase="true"
                                />
                            </td>
                            <td class="px-4 py-3">{{ $payment->reference_no ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if($payment->proof_image_path)
                                    <a href="{{ asset('storage/' . $payment->proof_image_path) }}" target="_blank" class="font-medium text-sky-700 hover:text-sky-800">Lihat Bukti</a>
                                @else
                                    <span class="text-xs text-slate-500">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">@rupiah((float) $payment->amount)</td>
                            <td class="px-4 py-3">
                                @if($payment->status !== 'approved')
                                    <div class="space-y-2">
                                        <form method="POST" action="{{ route('ui.finance.vendor-margin-payments.approve', $payment) }}">
                                            @csrf
                                            <x-ui.action-button type="submit" variant="success" size="sm">Approve</x-ui.action-button>
                                        </form>
                                        <form method="POST" action="{{ route('ui.finance.vendor-margin-payments.reject', $payment) }}" class="space-y-1">
                                            @csrf
                                            <input type="text" name="rejection_note" class="w-full rounded border border-slate-300 px-2 py-1 text-xs" placeholder="Alasan reject" required>
                                            <x-ui.action-button type="submit" variant="danger" size="sm">Reject</x-ui.action-button>
                                        </form>
                                    </div>
                                @else
                                    <p class="text-xs text-emerald-700">Approved oleh {{ $payment->approver?->name ?: '-' }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty-row :colspan="7" message="Belum ada pembayaran selisih pada filter ini." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <div class="mt-4">{{ $payments->links() }}</div>

    @if((int) $selectedVendorId > 0)
        <x-ui.panel class="mt-4" title="Ledger Otomatis Vendor Terpilih" subtitle="Akrual berasal dari invoice paid. Pembayaran approved mengurangi saldo kewajiban vendor ke yayasan." bodyClass="p-0">
            <div class="space-y-2 p-3 md:hidden">
                @forelse($ledgerEntries as $entry)
                    <article class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-700">{{ \Illuminate\Support\Carbon::parse($entry['entry_date'])->format('d M Y') }}</p>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $entry['entry_type'] === 'invoice_accrual' ? 'Akrual' : 'Pembayaran' }}</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-600">{{ $entry['description'] }}</p>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-[11px]">
                            <div class="rounded-lg bg-slate-50 p-2">
                                <p class="text-slate-500">Debit</p>
                                <p class="mt-0.5 font-semibold text-slate-800">@rupiah($entry['debit'])</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-2">
                                <p class="text-slate-500">Kredit</p>
                                <p class="mt-0.5 font-semibold text-slate-800">@rupiah($entry['credit'])</p>
                            </div>
                            <div class="col-span-2 rounded-lg bg-slate-50 p-2">
                                <p class="text-slate-500">Saldo</p>
                                <p class="mt-0.5 font-semibold {{ $entry['running_balance'] > 0 ? 'text-rose-700' : 'text-emerald-700' }}">@rupiah($entry['running_balance'])</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state message="Belum ada ledger otomatis pada filter ini." />
                @endforelse
            </div>

            <div class="hidden overflow-x-auto md:block">
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
                            <x-ui.table-empty-row :colspan="6" message="Belum ada ledger otomatis pada filter ini." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.panel>
    @endif
@endsection
