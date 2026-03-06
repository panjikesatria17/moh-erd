@extends('layouts.procurement')

@section('title', 'Pengajuan Dana Pembelian')

@section('content')
    @php
        $formatMoneyInput = static fn ($value) => $value === null || $value === ''
            ? ''
            : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    @endphp

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold">Pengajuan Dana Pembelian</h2>
            <p class="text-sm text-gray-500">Modul khusus finance-owner untuk kontrol petty cash, approval owner, dan settlement dana.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('ui.purchase-funding-requests.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="status" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs">
                    <option value="">Semua Status</option>
                    @foreach(\App\Enums\FundingRequestStatus::values() as $statusOption)
                        <option value="{{ $statusOption }}" @selected(($selectedStatus ?? null) === $statusOption)>{{ str($statusOption)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
                <select name="fund_source" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs">
                    <option value="">Semua Sumber Dana</option>
                    @foreach(($fundSourceLabels ?? []) as $fundSourceKey => $fundSourceLabel)
                        <option value="{{ $fundSourceKey }}" @selected(($selectedFundSource ?? null) === $fundSourceKey)>{{ $fundSourceLabel }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Filter</button>
                @if(request()->filled('status') || request()->filled('fund_source'))
                    <a href="{{ route('ui.purchase-funding-requests.index') }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                @endif
            </form>

            <a href="{{ route('ui.purchase-funding-requests.export', request()->only(['status', 'fund_source'])) }}" class="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Export Excel</a>
            <a href="{{ route('ui.purchase-funding-requests.export-pdf', request()->only(['status', 'fund_source'])) }}" class="rounded-md border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Export PDF</a>
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-blue-700">Dana Diajukan</p>
            <p class="mt-1 text-lg font-semibold text-blue-900">@rupiah($fundingStats['requested'] ?? 0)</p>
        </div>
        <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-cyan-700">Dana Approved</p>
            <p class="mt-1 text-lg font-semibold text-cyan-900">@rupiah($fundingStats['approved'] ?? 0)</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-emerald-700">Dana Cair</p>
            <p class="mt-1 text-lg font-semibold text-emerald-900">@rupiah($fundingStats['disbursed'] ?? 0)</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-amber-700">Dana Terpakai</p>
            <p class="mt-1 text-lg font-semibold text-amber-900">@rupiah($fundingStats['spent'] ?? 0)</p>
        </div>
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-purple-700">Sisa Dana</p>
            <p class="mt-1 text-lg font-semibold text-purple-900">@rupiah($fundingStats['remaining'] ?? 0)</p>
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 xl:grid-cols-2">
        <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-cyan-700">Threshold Wajib Approval Owner</p>
            <p class="mt-1 text-lg font-semibold text-cyan-900">@rupiah($fundingOwnerApprovalThreshold ?? 1000000)</p>
            <p class="mt-1 text-xs text-cyan-700/80">Di bawah threshold: cukup review finance. Di atas threshold: wajib approval owner.</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-amber-700">Menunggu Approval Owner</p>
            <p class="mt-1 text-lg font-semibold text-amber-900">{{ $pendingOwnerFundingApprovals ?? 0 }}</p>
            <p class="mt-1 text-xs text-amber-700/80">Jumlah pengajuan berstatus reviewed yang perlu keputusan owner.</p>
        </div>
    </div>

    @if(($canConfigureFundingThreshold ?? false) === true)
        <form method="POST" action="{{ route('ui.purchase-funding-requests.settings.owner-threshold.update') }}" class="mb-4 flex flex-wrap items-end gap-2 rounded-xl border border-gray-200 bg-white p-3">
            @csrf
            <div class="w-full sm:w-auto">
                <label class="mb-1 block text-xs font-medium text-gray-600">Atur Threshold Approval Owner (Pengajuan Dana)</label>
                <input
                    type="text"
                    inputmode="decimal"
                    name="purchase_funding_owner_approval_threshold"
                    value="{{ $formatMoneyInput(old('purchase_funding_owner_approval_threshold', (float) ($fundingOwnerApprovalThreshold ?? 1000000))) }}"
                    class="js-idr-input w-full sm:w-64 rounded-md border border-gray-300 px-3 py-2 text-sm"
                >
            </div>
            <button type="submit" class="w-full sm:w-auto rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">Simpan Threshold</button>
        </form>
    @endif

    <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="text-sm font-semibold text-gray-800">Buat Pengajuan Dana Baru</h3>
        <p class="mt-1 text-xs text-gray-500">Gunakan PO yang sudah approved/processed untuk diajukan ke finance lalu approval owner.</p>

        <form method="POST" action="{{ route('ui.purchase-funding-requests.store') }}" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
            @csrf
            <div class="xl:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600">Purchase Order</label>
                <select name="purchase_order_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-xs" required>
                    <option value="">Pilih PO</option>
                    @foreach(($purchaseOrderOptions ?? collect()) as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}" @selected((int) old('purchase_order_id') === (int) $purchaseOrder->id)>
                            {{ $purchaseOrder->number }} | {{ $purchaseOrder->vendor?->name ?? '-' }} | {{ optional($purchaseOrder->order_date)->format('d M Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Sumber Dana</label>
                <select name="fund_source" class="w-full rounded-md border border-gray-300 px-3 py-2 text-xs" required>
                    @foreach(($fundSourceLabels ?? []) as $fundSourceKey => $fundSourceLabel)
                        <option value="{{ $fundSourceKey }}" @selected(old('fund_source') === $fundSourceKey)>{{ $fundSourceLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nominal Diajukan</label>
                <input type="number" name="requested_amount" value="{{ old('requested_amount') }}" min="1" step="0.01" class="w-full rounded-md border border-gray-300 px-3 py-2 text-xs" placeholder="0" required>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Judul Pengajuan</label>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="255" class="w-full rounded-md border border-gray-300 px-3 py-2 text-xs" placeholder="Opsional">
            </div>

            <div class="xl:col-span-6">
                <label class="mb-1 block text-xs font-medium text-gray-600">Rincian Dana / Catatan</label>
                <textarea name="notes" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-xs" placeholder="Contoh: pembelian beras 50kg, minyak, telur, biaya kirim">{{ old('notes') }}</textarea>
            </div>

            <div class="xl:col-span-6 flex justify-end">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Ajukan ke Finance</button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Nomor</th>
                        <th class="px-4 py-3">PO / Vendor</th>
                        <th class="px-4 py-3">Sumber</th>
                        <th class="px-4 py-3 text-right">Diajukan</th>
                        <th class="px-4 py-3 text-right">Approved</th>
                        <th class="px-4 py-3 text-right">Cair</th>
                        <th class="px-4 py-3 text-right">Terpakai</th>
                        <th class="px-4 py-3 text-right">Sisa</th>
                        <th class="px-4 py-3">Lampiran</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($fundingRequests as $fundingRequest)
                        @php
                            $statusValue = $fundingRequest->status?->value ?? '-';
                            $statusBadgeClass = match ($statusValue) {
                                'submitted' => 'bg-slate-100 text-slate-700',
                                'reviewed' => 'bg-blue-100 text-blue-700',
                                'approved' => 'bg-cyan-100 text-cyan-700',
                                'disbursed' => 'bg-emerald-100 text-emerald-700',
                                'settled' => 'bg-purple-100 text-purple-700',
                                'rejected' => 'bg-rose-100 text-rose-700',
                                default => 'bg-gray-100 text-gray-700',
                            };

                            $remainingAmount = max((float) ($fundingRequest->disbursed_amount ?? 0) - (float) ($fundingRequest->spent_amount ?? 0), 0);
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ $fundingRequest->number }}</p>
                                <p class="text-xs text-gray-500">{{ optional($fundingRequest->created_at)->format('d M Y H:i') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ $fundingRequest->purchaseOrder?->number ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $fundingRequest->vendor?->name ?? '-' }} • {{ $fundingRequest->sppg?->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $fundingRequest->title }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $fundSourceLabels[$fundingRequest->fund_source] ?? $fundingRequest->fund_source }}</td>
                            <td class="px-4 py-3 text-right">@rupiah($fundingRequest->requested_amount)</td>
                            <td class="px-4 py-3 text-right">@rupiah($fundingRequest->approved_amount ?? 0)</td>
                            <td class="px-4 py-3 text-right">@rupiah($fundingRequest->disbursed_amount ?? 0)</td>
                            <td class="px-4 py-3 text-right">@rupiah($fundingRequest->spent_amount ?? 0)</td>
                            <td class="px-4 py-3 text-right font-medium">@rupiah($remainingAmount)</td>
                            <td class="px-4 py-3">
                                @if($fundingRequest->settlement_proof_path)
                                    <a href="{{ asset('storage/'.$fundingRequest->settlement_proof_path) }}" target="_blank" class="inline-flex items-center rounded-md border border-blue-300 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50">Bukti Settlement</a>
                                @else
                                    <span class="text-xs text-gray-400">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-medium {{ $statusBadgeClass }}">{{ str($statusValue)->replace('_', ' ')->title() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="min-w-[290px] space-y-2">
                                    @if(($canManageFunding ?? false) && in_array($statusValue, ['submitted', 'reviewed'], true))
                                        <form method="POST" action="{{ route('ui.purchase-funding-requests.review', $fundingRequest) }}" class="grid grid-cols-1 gap-1.5 rounded-md border border-blue-200 bg-blue-50 p-2">
                                            @csrf
                                            @if(request()->filled('status'))
                                                <input type="hidden" name="status" value="{{ request('status') }}">
                                            @endif
                                            @if(request()->filled('fund_source'))
                                                <input type="hidden" name="fund_source" value="{{ request('fund_source') }}">
                                            @endif
                                            <input type="number" name="reviewed_amount" min="1" step="0.01" value="{{ old('reviewed_amount', $fundingRequest->reviewed_amount ?? $fundingRequest->requested_amount) }}" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" placeholder="Nominal review" required>
                                            <input type="text" name="finance_notes" value="{{ old('finance_notes', $fundingRequest->finance_notes) }}" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" placeholder="Catatan finance (opsional)">
                                            <button type="submit" class="rounded-md bg-blue-600 px-2 py-1 text-xs font-medium text-white hover:bg-blue-700">Review Finance</button>
                                        </form>
                                    @endif

                                    @if(($canOwnerApproval ?? false) && in_array($statusValue, ['submitted', 'reviewed'], true))
                                        <form method="POST" action="{{ route('ui.purchase-funding-requests.approve', $fundingRequest) }}" class="grid grid-cols-1 gap-1.5 rounded-md border border-cyan-200 bg-cyan-50 p-2">
                                            @csrf
                                            @if(request()->filled('status'))
                                                <input type="hidden" name="status" value="{{ request('status') }}">
                                            @endif
                                            @if(request()->filled('fund_source'))
                                                <input type="hidden" name="fund_source" value="{{ request('fund_source') }}">
                                            @endif
                                            <input type="number" name="approved_amount" min="1" step="0.01" value="{{ old('approved_amount', $fundingRequest->approved_amount ?? $fundingRequest->reviewed_amount ?? $fundingRequest->requested_amount) }}" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" placeholder="Nominal approval">
                                            <input type="text" name="owner_notes" value="{{ old('owner_notes') }}" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" placeholder="Catatan owner (opsional)">
                                            <div class="flex items-center gap-1.5">
                                                <button type="submit" class="rounded-md bg-cyan-600 px-2 py-1 text-xs font-medium text-white hover:bg-cyan-700">Approve Owner</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('ui.purchase-funding-requests.reject', $fundingRequest) }}" class="grid grid-cols-1 gap-1.5 rounded-md border border-rose-200 bg-rose-50 p-2">
                                            @csrf
                                            @if(request()->filled('status'))
                                                <input type="hidden" name="status" value="{{ request('status') }}">
                                            @endif
                                            @if(request()->filled('fund_source'))
                                                <input type="hidden" name="fund_source" value="{{ request('fund_source') }}">
                                            @endif
                                            <input type="text" name="owner_notes" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" placeholder="Alasan penolakan" required>
                                            <button type="submit" class="rounded-md bg-rose-600 px-2 py-1 text-xs font-medium text-white hover:bg-rose-700">Reject</button>
                                        </form>
                                    @endif

                                    @if(($canManageFunding ?? false) && in_array($statusValue, ['approved', 'disbursed'], true))
                                        <form method="POST" action="{{ route('ui.purchase-funding-requests.disburse', $fundingRequest) }}" class="grid grid-cols-1 gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 p-2">
                                            @csrf
                                            @if(request()->filled('status'))
                                                <input type="hidden" name="status" value="{{ request('status') }}">
                                            @endif
                                            @if(request()->filled('fund_source'))
                                                <input type="hidden" name="fund_source" value="{{ request('fund_source') }}">
                                            @endif
                                            <input type="number" name="disbursed_amount" min="1" step="0.01" value="{{ old('disbursed_amount', $fundingRequest->disbursed_amount ?? $fundingRequest->approved_amount ?? $fundingRequest->reviewed_amount ?? $fundingRequest->requested_amount) }}" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" placeholder="Nominal cair">
                                            <button type="submit" class="rounded-md bg-emerald-600 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-700">Proses Pencairan</button>
                                        </form>

                                        <form method="POST" action="{{ route('ui.purchase-funding-requests.settle', $fundingRequest) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-1.5 rounded-md border border-amber-200 bg-amber-50 p-2">
                                            @csrf
                                            @if(request()->filled('status'))
                                                <input type="hidden" name="status" value="{{ request('status') }}">
                                            @endif
                                            @if(request()->filled('fund_source'))
                                                <input type="hidden" name="fund_source" value="{{ request('fund_source') }}">
                                            @endif
                                            <input type="number" name="spent_amount" min="0" step="0.01" value="{{ old('spent_amount', $fundingRequest->spent_amount ?? 0) }}" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" placeholder="Realisasi terpakai" required>
                                            <input type="file" name="settlement_proof" accept=".pdf,image/*" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" @required(! $fundingRequest->settlement_proof_path)>
                                            <input type="text" name="finance_notes" value="{{ old('finance_notes') }}" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" placeholder="Catatan settlement (opsional)">
                                            <button type="submit" class="rounded-md bg-amber-600 px-2 py-1 text-xs font-medium text-white hover:bg-amber-700">Update Settlement</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-8 text-center text-gray-500">Belum ada pengajuan dana pembelian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $fundingRequests->links() }}</div>
@endsection
