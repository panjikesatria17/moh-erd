@extends('layouts.procurement')

@section('title', 'Users & Roles')

@section('content')
    @php
        $currentRoleValue = auth()->user()?->role?->value;
        $isAdminViewer = $currentRoleValue === \App\Enums\UserRole::ADMIN->value;
    @endphp

    <div class="mb-4">
        <h2 class="text-xl font-semibold">Users & Roles</h2>
        <p class="text-sm text-gray-500">Daftar user, role, dan scope akses (SPPG/Vendor).</p>
    </div>

    <form method="POST" action="{{ $editUser ? route('ui.users-roles.update', $editUser) : route('ui.users-roles.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        @if($editUser)
            @method('PUT')
        @endif
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-700">{{ $editUser ? 'Edit User' : 'Tambah User' }}</h3>
            @if($editUser)
                <a href="{{ route('ui.users-roles.index') }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Batal Edit</a>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama</label>
                <input type="text" name="name" value="{{ old('name', $editUser?->name) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Email</label>
                <input type="email" name="email" value="{{ old('email', $editUser?->email) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Role</label>
                <select id="role_select" name="role" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">-- Pilih Role --</option>
                    @foreach($roleOptions as $role)
                        <option value="{{ $role }}" @selected(old('role', $editUser?->role?->value ?? $editUser?->role) === $role)>{{ $roleLabels[$role] ?? $role }}</option>
                    @endforeach
                </select>
                @if($isAdminViewer)
                    <p class="mt-1 text-[11px] text-amber-700">Role super admin disembunyikan untuk akun admin.</p>
                @endif
            </div>
            <div>
                <label id="sppg_scope_label" class="mb-1 block text-xs font-medium text-gray-600">SPPG (Opsional)</label>
                <select id="sppg_scope_select" name="sppg_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">-- Tanpa Scope SPPG --</option>
                    @foreach($sppgs as $sppg)
                        <option value="{{ $sppg->id }}" @selected((string) old('sppg_id', $editUser?->sppg_id) === (string) $sppg->id)>{{ $sppg->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label id="vendor_scope_label" class="mb-1 block text-xs font-medium text-gray-600">Vendor (Opsional)</label>
                <select id="vendor_scope_select" name="vendor_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">-- Tanpa Scope Vendor --</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $editUser?->vendor_id) === (string) $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Password {{ $editUser ? '(opsional)' : '' }}</label>
                <input type="password" name="password" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" {{ $editUser ? '' : 'required' }}>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Konfirmasi Password {{ $editUser ? '(opsional)' : '' }}</label>
                <input type="password" name="password_confirmation" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" {{ $editUser ? '' : 'required' }}>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ $editUser ? 'Update User' : 'Simpan User' }}</button>
        </div>

        <p id="role_scope_hint" class="mt-2 text-xs text-gray-500">
            Scope SPPG wajib untuk role sppg_user, scope Vendor wajib untuk role vendor_admin dan ekspedisi.
        </p>
    </form>

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
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $roleLabels[$user->role?->value ?? $user->role] ?? ($user->role?->value ?? $user->role ?? '-') }}</td>
                            <td class="px-4 py-3">{{ $user->sppg?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $user->vendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $targetRoleValue = $user->role?->value ?? $user->role;
                                    $isProtectedSuperAdmin = $isAdminViewer && $targetRoleValue === \App\Enums\UserRole::SUPER_ADMIN->value;
                                @endphp
                                <div class="flex items-center justify-end gap-2">
                                    @if($isProtectedSuperAdmin)
                                        <span class="rounded-md border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Dilindungi</span>
                                    @else
                                        <a href="{{ route('ui.users-roles.edit', $user) }}" class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                                        <form method="POST" action="{{ route('ui.users-roles.destroy', $user) }}" onsubmit="return confirm('Hapus user ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md border border-red-300 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada data user.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>

    <script>
        (function () {
            const roleSelect = document.getElementById('role_select');
            const sppgSelect = document.getElementById('sppg_scope_select');
            const vendorSelect = document.getElementById('vendor_scope_select');
            const sppgLabel = document.getElementById('sppg_scope_label');
            const vendorLabel = document.getElementById('vendor_scope_label');
            const scopeHint = document.getElementById('role_scope_hint');

            if (!roleSelect || !sppgSelect || !vendorSelect || !sppgLabel || !vendorLabel || !scopeHint) {
                return;
            }

            function applyScopeRules() {
                const role = roleSelect.value;
                const isSppgUser = role === '{{ \App\Enums\UserRole::SPPG_USER->value }}';
                const isVendorAdmin = role === '{{ \App\Enums\UserRole::VENDOR_ADMIN->value }}';
                const isExpedition = role === '{{ \App\Enums\UserRole::EXPEDITION->value }}';
                const requiresVendorScope = isVendorAdmin || isExpedition;

                sppgSelect.disabled = !isSppgUser;
                sppgSelect.required = isSppgUser;
                if (!isSppgUser) {
                    sppgSelect.value = '';
                }

                vendorSelect.disabled = !requiresVendorScope;
                vendorSelect.required = requiresVendorScope;
                if (!requiresVendorScope) {
                    vendorSelect.value = '';
                }

                sppgLabel.textContent = isSppgUser ? 'SPPG (Wajib)' : 'SPPG (Opsional)';
                vendorLabel.textContent = requiresVendorScope ? 'Vendor (Wajib)' : 'Vendor (Opsional)';

                if (isSppgUser) {
                    scopeHint.textContent = 'Role sppg_user membutuhkan scope SPPG. Scope Vendor dinonaktifkan otomatis.';
                    return;
                }

                if (isVendorAdmin) {
                    scopeHint.textContent = 'Role vendor_admin membutuhkan scope Vendor. Scope SPPG dinonaktifkan otomatis.';
                    return;
                }

                if (isExpedition) {
                    scopeHint.textContent = 'Role ekspedisi membutuhkan scope Vendor. Scope SPPG dinonaktifkan otomatis.';
                    return;
                }

                scopeHint.textContent = 'Untuk role non-scope, field SPPG dan Vendor dinonaktifkan dan akan disimpan null.';
            }

            roleSelect.addEventListener('change', applyScopeRules);
            applyScopeRules();
        })();
    </script>
@endsection
