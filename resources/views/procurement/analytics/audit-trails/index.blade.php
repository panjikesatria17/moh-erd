@extends('layouts.procurement')

@section('title', 'Audit Trail')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Audit Trail</h2>
        <p class="text-sm text-gray-500">Jejak aktivitas perubahan data untuk kebutuhan compliance.</p>
        <form method="GET" action="{{ route('ui.audit-trails.index') }}" class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-5">
            <select name="event" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Semua Event</option>
                @foreach(($eventOptions ?? collect()) as $event)
                    <option value="{{ $event }}" @selected(($selectedEvent ?? null) === $event)>{{ $event }}</option>
                @endforeach
            </select>

            <select name="user_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Semua User</option>
                @foreach(($userOptions ?? collect()) as $userOption)
                    <option value="{{ $userOption->id }}" @selected((int) ($selectedUserId ?? 0) === (int) $userOption->id)>{{ $userOption->name }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm" title="Tanggal Mulai">
            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm" title="Tanggal Akhir">

            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('ui.audit-trails.index') }}" class="rounded-md border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>
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
