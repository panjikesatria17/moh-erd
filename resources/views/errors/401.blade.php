<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Belum Login</title>
    <link rel="icon" type="image/png" href="{{ asset('images/smp-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <main class="mx-auto flex min-h-screen max-w-2xl items-center px-6 py-10">
        <section class="w-full rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">401 Unauthorized</p>
            <h1 class="mt-2 text-2xl font-bold md:text-3xl">Sesi belum terautentikasi</h1>
            <p class="mx-auto mt-3 max-w-xl text-sm text-gray-600 md:text-base">
                Anda perlu login terlebih dahulu untuk mengakses halaman ini.
            </p>

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('login') }}" class="rounded-md bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    Ke Halaman Login
                </a>
                <a href="{{ url('/') }}" class="rounded-md border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Kembali ke Beranda
                </a>
            </div>
        </section>
    </main>
</body>
</html>
