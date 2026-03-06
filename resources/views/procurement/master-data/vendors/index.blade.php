@extends('layouts.procurement')

@section('title', 'Master Data Vendor')

@section('content')
    @php
        $canManageMasterWrites = in_array(auth()->user()?->role?->value, [\App\Enums\UserRole::SUPER_ADMIN->value, \App\Enums\UserRole::ADMIN->value], true);
    @endphp

    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Master Data - Vendors</h2>
            <p class="text-sm text-gray-500">Kelola data vendor beserta informasi kontak dan afiliasi.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('ui.master-data.vendors.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-3">
        <div class="flex flex-wrap items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ $search ?? request('q') }}"
                placeholder="Cari kode, nama vendor, owner, email, atau telepon"
                class="w-full max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm"
            >
            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">Cari</button>
            @if(($search ?? '') !== '')
                <a href="{{ route('ui.master-data.vendors.index') }}" class="rounded-md border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            @endif
        </div>
    </form>

    @if($canManageMasterWrites)
    <form method="POST" action="{{ $editVendor ? route('ui.master-data.vendors.update', $editVendor) : route('ui.master-data.vendors.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        @if($editVendor)
            @method('PUT')
        @endif
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-700">{{ $editVendor ? 'Edit Vendor' : 'Tambah Vendor' }}</h3>
            @if($editVendor)
                <a href="{{ route('ui.master-data.vendors.index') }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Batal Edit</a>
            @endif
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Kode Vendor</label>
                <input type="text" name="code" value="{{ old('code', $editVendor?->code) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama Vendor</label>
                <input type="text" name="name" value="{{ old('name', $editVendor?->name) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama Owner Vendor</label>
                <input type="text" name="owner_name" value="{{ old('owner_name', $editVendor?->owner_name) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Contoh: Budi Santoso">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Email</label>
                <input type="email" name="email" value="{{ old('email', $editVendor?->email) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $editVendor?->phone) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="xl:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600">Alamat</label>
                <input type="text" name="address" value="{{ old('address', $editVendor?->address) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Afiliasi</label>
                <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <input type="checkbox" name="is_affiliate" value="1" {{ old('is_affiliate', $editVendor ? (int) $editVendor->is_affiliate : 0) ? 'checked' : '' }}>
                    Vendor Afiliasi
                </label>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $editVendor ? (int) $editVendor->is_active : 1) ? 'checked' : '' }}>
                    Aktif
                </label>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ $editVendor ? 'Update Vendor' : 'Simpan Vendor' }}</button>
        </div>
    </form>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Owner Vendor</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3">Afiliasi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($vendors as $vendor)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $vendor->code }}</td>
                            <td class="px-4 py-3">{{ $vendor->name }}</td>
                            <td class="px-4 py-3">{{ $vendor->owner_name ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                <div>{{ $vendor->email ?: '-' }}</div>
                                <div class="text-xs">{{ $vendor->phone ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $vendor->is_affiliate ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $vendor->is_affiliate ? 'Afiliasi' : 'Non Afiliasi' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $vendor->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $vendor->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($canManageMasterWrites)
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('ui.master-data.vendors.edit', $vendor) }}" class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                                    <form method="POST" action="{{ route('ui.master-data.vendors.destroy', $vendor) }}" onsubmit="return confirm('Hapus data vendor ini?')">
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
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data vendor.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $vendors->links() }}
    </div>
@endsection
