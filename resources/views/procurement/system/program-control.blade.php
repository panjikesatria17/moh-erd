@extends('layouts.procurement')

@section('title', 'Program ON/OFF')

@section('content')
    @php
        $programEnabled = (bool) ($programControlState['enabled'] ?? true);
        $effectiveEnabled = (bool) ($programControlState['effective_enabled'] ?? true);
        $lockMode = (string) ($programControlState['lock_mode'] ?? 'hard_lock');
        $effectiveReason = (string) ($programControlState['effective_reason'] ?? 'enabled');
        $licenseExpiresAt = $programControlState['license_expires_at'] ?? null;
        $licenseGraceDays = (int) ($programControlState['license_grace_days'] ?? 0);
        $effectiveDeadline = $programControlState['effective_deadline'] ?? null;
    @endphp

    <x-ui.hero
        class="mb-4"
        eyebrow="System Control"
        title="Kontrol Program Global"
        description="Jika program dinonaktifkan, semua role selain super admin tidak dapat menggunakan sistem."
    />

    <x-ui.panel title="Pengaturan Program" subtitle="Kontrol status global ON/OFF sistem">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">Status Efektif Saat Ini</p>
                <div class="mt-1">
                    <x-ui.status-pill
                        :value="$effectiveEnabled ? 'program aktif (on)' : 'program nonaktif (off)'"
                        :classes="[
                            'program aktif (on)' => 'bg-emerald-100 text-emerald-700',
                            'program nonaktif (off)' => 'bg-rose-100 text-rose-700',
                        ]"
                    />
                </div>
                <p class="mt-1 text-xs text-gray-500">Alasan status: {{ str($effectiveReason)->replace('_', ' ')->title() }}</p>
                @if($effectiveDeadline)
                    <p class="mt-1 text-xs text-gray-500">Deadline lisensi + grace: {{ $effectiveDeadline->format('d M Y H:i') }}</p>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('ui.program-control.update') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @csrf

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Program Switch</label>
                <select name="enabled" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="1" @selected($programEnabled)>ON (Aktif)</option>
                    <option value="0" @selected(! $programEnabled)>OFF (Nonaktif)</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Mode Saat OFF</label>
                <select name="lock_mode" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="hard_lock" @selected($lockMode === 'hard_lock')>Hard Lock (semua akses non-super-admin diblok)</option>
                    <option value="read_only" @selected($lockMode === 'read_only')>Read Only (hanya baca, aksi write diblok)</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Tanggal Expiry Lisensi (opsional)</label>
                <input
                    type="date"
                    name="license_expires_at"
                    value="{{ old('license_expires_at', optional($licenseExpiresAt)->toDateString()) }}"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                >
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Grace Period (hari)</label>
                <input
                    type="number"
                    min="0"
                    max="3650"
                    step="1"
                    name="license_grace_days"
                    value="{{ old('license_grace_days', $licenseGraceDays) }}"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                >
            </div>

            <div class="mt-1 md:col-span-2">
                <button
                    type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    onclick="return confirm('Simpan pengaturan kontrol program global?')"
                >
                    Simpan Pengaturan
                </button>
            </div>
        </form>

        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            Gunakan mode ini untuk kontrol aktivasi sistem lintas yayasan. Untuk demo/marketing biasanya gunakan mode <strong>read-only</strong>, untuk lock total gunakan <strong>hard lock</strong>.
        </div>
    </x-ui.panel>
@endsection
