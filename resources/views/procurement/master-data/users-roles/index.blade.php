@extends('layouts.procurement')

@section('title', 'Users & Roles')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Users & Roles</h2>
        <p class="text-sm text-gray-500">Daftar user, role, dan scope akses (SPPG/Vendor).</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">SPPG</th>
                        <th class="px-4 py-3">Vendor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->role?->value ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $user->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $user->vendor?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data user.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
