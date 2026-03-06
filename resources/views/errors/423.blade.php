<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>423 - Program Dinonaktifkan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/smp-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <main class="mx-auto flex min-h-screen w-full max-w-2xl items-center justify-center px-6 py-10">
        <div class="w-full rounded-xl border border-amber-200 bg-white p-8 shadow-sm">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-600">Error 423</p>
            <h1 class="text-2xl font-bold text-gray-900">Program Sedang Dinonaktifkan</h1>
            <p class="mt-3 text-sm text-gray-600">{{ $message ?? 'Akses sementara dikunci oleh super admin. Silakan hubungi super admin untuk aktivasi kembali.' }}</p>

            @if(!empty($readOnlyMode))
                <div class="mt-4 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
                    Mode saat ini: <strong>Read Only</strong>. Anda dapat melihat data, namun tidak bisa melakukan aksi perubahan.
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                    Logout
                </a>
            </div>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </main>
</body>
</html>
