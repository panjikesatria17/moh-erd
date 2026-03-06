@extends('layouts.procurement')

@section('title', 'Program ON/OFF')

@section('content')
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Kontrol Program Global</h2>
        <p class="text-sm text-gray-500">Jika program dinonaktifkan, semua role selain super admin tidak dapat menggunakan sistem.</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">Status Saat Ini</p>
                <p class="mt-1 text-lg font-semibold {{ $programEnabled ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $programEnabled ? 'PROGRAM AKTIF (ON)' : 'PROGRAM NONAKTIF (OFF)' }}
                </p>
                @if($programSetting)
                    <p class="mt-1 text-xs text-gray-500">Terakhir diubah: {{ optional($programSetting->updated_at)->format('d M Y H:i') }}</p>
                @endif
            </div>

            <div class="flex items-center gap-2">
                @if($programEnabled)
                    <form method="POST" action="{{ route('ui.program-control.update') }}">
                        @csrf
                        <input type="hidden" name="enabled" value="0">
                        <button
                            type="submit"
                            class="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
                            onclick="return confirm('Nonaktifkan program? Semua role selain super admin akan terkunci.')"
                        >
                            Nonaktifkan Program
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('ui.program-control.update') }}">
                        @csrf
                        <input type="hidden" name="enabled" value="1">
                        <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Aktifkan Program
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            Gunakan mode ini untuk kontrol aktivasi sistem lintas yayasan. Saat OFF, user non-super-admin akan melihat halaman terkunci.
        </div>
    </div>
@endsection
