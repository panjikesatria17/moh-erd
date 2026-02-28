@extends('layouts.procurement')

@section('title', 'Audit Trail')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Audit Trail</h2>
        <p class="text-sm text-gray-500">Jejak aktivitas perubahan data untuk kebutuhan compliance.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Event</th>
                        <th class="px-4 py-3">Entity</th>
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($auditTrails as $trail)
                        <tr>
                            <td class="px-4 py-3">{{ optional($trail->created_at)->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3">{{ $trail->user?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $trail->event }}</td>
                            <td class="px-4 py-3">{{ class_basename($trail->auditable_type) }} #{{ $trail->auditable_id }}</td>
                            <td class="px-4 py-3">{{ $trail->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data audit trail.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $auditTrails->links() }}</div>
@endsection
