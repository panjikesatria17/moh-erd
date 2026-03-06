@extends('layouts.procurement')

@section('title', 'Payments')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Payments</h2>
        <p class="text-sm text-gray-500">Monitoring pembayaran invoice vendor.</p>
        @if(isset($selectedInvoice) && $selectedInvoice)
            <div class="mt-2 inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                <span>Filter invoice: {{ $selectedInvoice->number }}</span>
                <a href="{{ route('ui.payments.index') }}" class="rounded-full border border-blue-200 bg-white px-2 py-0.5 text-blue-700 hover:bg-blue-100">Reset</a>
            </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Paid By</th>
                        <th class="px-4 py-3">Bukti</th>
                        <th class="px-4 py-3">Approved By</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $payment->number }}</td>
                            <td class="px-4 py-3">{{ $payment->invoice?->number ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $payment->invoice?->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ optional($payment->payment_date)->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">@rupiah($payment->amount)</td>
                            <td class="px-4 py-3">{{ $payment->status?->value ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $payment->payer?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($payment->proof_image_path)
                                    <a href="{{ asset('storage/'.$payment->proof_image_path) }}" target="_blank" class="inline-flex items-center rounded-md border border-blue-300 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50">Lihat Bukti</a>
                                    <div class="mt-1 text-xs text-gray-500">by {{ $payment->proofUploader?->name ?? '-' }}</div>
                                @elseif($payment->status?->value === 'paid')
                                    <span class="text-xs text-gray-500">Legacy paid (tanpa bukti)</span>
                                @else
                                    <span class="text-xs text-gray-400">Belum upload</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($payment->approved_at)
                                    <div>{{ $payment->approver?->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ optional($payment->approved_at)->format('d M Y H:i') }}</div>
                                @elseif($payment->status?->value === 'paid' && $payment->payer)
                                    <div>{{ $payment->payer?->name }}</div>
                                    <div class="text-xs text-gray-500">Legacy approver fallback</div>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @if(($canUploadProof ?? false) && in_array($payment->status?->value, ['draft', 'rejected'], true))
                                        <button
                                            type="button"
                                            onclick="openPaymentProofModal({{ $payment->id }})"
                                            class="rounded-md bg-indigo-600 px-2 py-1 text-xs font-medium text-white hover:bg-indigo-700"
                                        >
                                            Upload Bukti
                                        </button>
                                    @elseif(($canApproveProof ?? false) && $payment->status?->value === 'submitted')
                                        <form method="POST" action="{{ route('ui.payments.approve', $payment) }}">
                                            @csrf
                                            @if(request()->filled('invoice'))
                                                <input type="hidden" name="invoice" value="{{ request('invoice') }}">
                                            @endif
                                            <button type="submit" class="rounded-md bg-emerald-600 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-700">Approve</button>
                                        </form>
                                    @elseif(($canApproveProof ?? false) && in_array($payment->status?->value, ['draft', 'rejected'], true))
                                        <span class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">Menunggu upload SPPG</span>
                                    @elseif($payment->status?->value === 'paid')
                                        <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">Paid</span>
                                    @else
                                        @if(($canUploadProof ?? false) && $payment->status?->value === 'submitted')
                                            <span class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">Menunggu Approval Finance</span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">Belum ada data pembayaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($payments as $payment)
        @if(($canUploadProof ?? false) && in_array($payment->status?->value, ['draft', 'rejected'], true))
            <div id="payment-proof-modal-{{ $payment->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
                <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Upload Bukti Pembayaran</h3>
                            <p class="text-xs text-gray-500">{{ $payment->number }} | Invoice {{ $payment->invoice?->number ?? '-' }}</p>
                        </div>
                        <button type="button" onclick="closePaymentProofModal({{ $payment->id }})" class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50">Tutup</button>
                    </div>

                    <form method="POST" action="{{ route('ui.payments.upload-proof', $payment) }}" enctype="multipart/form-data" class="space-y-3 px-4 py-4">
                        @csrf
                        @if(request()->filled('invoice'))
                            <input type="hidden" name="invoice" value="{{ request('invoice') }}">
                        @endif

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Tanggal Bayar</label>
                                <input type="date" name="payment_date" value="{{ now()->toDateString() }}" class="w-full rounded-md border border-gray-300 px-2 py-2 text-xs" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Metode</label>
                                <input type="text" name="payment_method" value="Transfer" placeholder="Metode pembayaran" class="w-full rounded-md border border-gray-300 px-2 py-2 text-xs">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Reference No</label>
                                <input type="text" name="reference_no" placeholder="No referensi transfer" class="w-full rounded-md border border-gray-300 px-2 py-2 text-xs">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">File Bukti</label>
                                <input type="file" name="proof_image" accept="image/*" class="w-full rounded-md border border-gray-300 px-2 py-2 text-xs" required>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Catatan (Opsional)</label>
                            <textarea name="notes" rows="2" class="w-full rounded-md border border-gray-300 px-2 py-2 text-xs" placeholder="Catatan pembayaran"></textarea>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="closePaymentProofModal({{ $payment->id }})" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Upload & Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    <div class="mt-4">{{ $payments->links() }}</div>

    <script>
        const openPaymentProofModal = (paymentId) => {
            const modal = document.getElementById(`payment-proof-modal-${paymentId}`);
            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        const closePaymentProofModal = (paymentId) => {
            const modal = document.getElementById(`payment-proof-modal-${paymentId}`);
            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };
    </script>
@endsection
