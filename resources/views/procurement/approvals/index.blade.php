@extends('layouts.procurement')

@section('title', 'Approval Queue')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Approval Queue</h2>
        <p class="text-sm text-gray-500">Daftar antrian approval dokumen procurement lintas modul.</p>
    </div>

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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada antrian approval.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $approvals->links() }}</div>
@endsection
