@extends('layouts.procurement')

@section('title', 'Master Data SPPG')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Master Data - SPPG</h2>
            <p class="text-sm text-gray-500">Kelola unit SPPG dan vendor default untuk proses procurement.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('ui.master-data.sppgs.store') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        @csrf
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Tambah SPPG</h3>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Kode SPPG</label>
                <input type="text" name="code" value="{{ old('code') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Nama SPPG</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Vendor Default</label>
                <select name="default_vendor_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">-- Tanpa Vendor Default --</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected(old('default_vendor_id') == $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    Aktif
                </label>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600">Alamat</label>
                <textarea name="address" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">{{ old('address') }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan SPPG</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Vendor Default</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Alamat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sppgs as $sppg)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $sppg->code }}</td>
                            <td class="px-4 py-3">{{ $sppg->name }}</td>
                            <td class="px-4 py-3">{{ $sppg->defaultVendor?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $sppg->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $sppg->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $sppg->address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data SPPG.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $sppgs->links() }}
    </div>
@endsection
