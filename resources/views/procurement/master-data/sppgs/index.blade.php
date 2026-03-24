@extends('layouts.procurement')

@section('title', 'Master Data SPPG')

@section('content')
    @php
        $authRole = auth()->user()?->role;
        $currentRoleRaw = is_object($authRole) ? ($authRole->value ?? null) : $authRole;
        $canManageMasterWrites = in_array($currentRoleRaw, [\App\Enums\UserRole::SUPER_ADMIN->value, \App\Enums\UserRole::ADMIN->value, \App\Enums\UserRole::OWNER->value, 'super_admin', 'admin', 'owner'], true);
    @endphp

    <x-ui.hero
        class="mb-4"
        eyebrow="Master Data"
        title="Master Data - SPPG"
        description="Kelola unit SPPG dan vendor default untuk proses procurement."
    />

    @if($canManageMasterWrites)
    <x-ui.panel class="mb-5" :title="$editSppg ? 'Edit SPPG' : 'Tambah SPPG'">
    <form method="POST" action="{{ $editSppg ? route('ui.master-data.sppgs.update', $editSppg) : route('ui.master-data.sppgs.store') }}" class="">
        @csrf
        @if($editSppg)
            @method('PUT')
        @endif
        <div class="mb-3 flex items-center justify-between gap-2">
            @if($editSppg)
                <a href="{{ route('ui.master-data.sppgs.index') }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Batal Edit</a>
            @endif
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Kode SPPG</label>
                <input type="text" name="code" value="{{ old('code', $editSppg?->code) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama SPPG</label>
                <input type="text" name="name" value="{{ old('name', $editSppg?->name) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama Ka. SPPG</label>
                <input type="text" name="ka_sppg_name" value="{{ old('ka_sppg_name', $editSppg?->ka_sppg_name) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: Budi Santoso">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama Akuntansi</label>
                <input type="text" name="accounting_name" value="{{ old('accounting_name', $editSppg?->accounting_name) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: Siti Aminah">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Vendor Default</label>
                <select name="default_vendor_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">-- Tanpa Vendor Default --</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('default_vendor_id', $editSppg?->default_vendor_id) === (string) $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $editSppg ? (int) $editSppg->is_active : 1) ? 'checked' : '' }}>
                    Aktif
                </label>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600">Alamat</label>
                <textarea name="address" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">{{ old('address', $editSppg?->address) }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ $editSppg ? 'Update SPPG' : 'Simpan SPPG' }}</button>
        </div>
    </form>
    </x-ui.panel>
    @endif

    <x-ui.panel title="Daftar SPPG" subtitle="Unit SPPG aktif untuk proses procurement" bodyClass="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Ka. SPPG</th>
                        <th class="px-4 py-3">Akuntansi</th>
                        <th class="px-4 py-3">Vendor Default</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Alamat</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sppgs as $sppg)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $sppg->code }}</td>
                            <td class="px-4 py-3">{{ $sppg->name }}</td>
                            <td class="px-4 py-3">{{ $sppg->ka_sppg_name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $sppg->accounting_name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $sppg->defaultVendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.status-pill
                                    :value="$sppg->is_active ? 'aktif' : 'nonaktif'"
                                    :classes="[
                                        'aktif' => 'bg-emerald-100 text-emerald-700',
                                        'nonaktif' => 'bg-slate-100 text-slate-600',
                                    ]"
                                />
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $sppg->address ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if($canManageMasterWrites)
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('ui.master-data.sppgs.edit', $sppg) }}" class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                                    <form method="POST" action="{{ route('ui.master-data.sppgs.destroy', $sppg) }}" onsubmit="return confirm('Hapus data SPPG ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-300 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Hapus</button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Belum ada data SPPG.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.panel>

    <div class="mt-4">
        {{ $sppgs->links() }}
    </div>
@endsection
