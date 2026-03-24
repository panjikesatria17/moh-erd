<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Belum Login</title>
    <link rel="icon" type="image/png" href="{{ asset('images/smp-logo.png') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen bg-slate-950 text-slate-900" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
    <div class="pointer-events-none absolute inset-0 bg-linear-to-br from-slate-950 via-slate-900 to-sky-900"></div>
    <div class="pointer-events-none absolute -top-24 -right-20 h-72 w-72 rounded-full bg-cyan-300/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-24 h-72 w-72 rounded-full bg-blue-500/20 blur-3xl"></div>

    <main class="relative z-10 mx-auto flex min-h-screen w-full max-w-3xl items-center px-4 py-6 sm:px-6 sm:py-10">
        <section class="w-full rounded-3xl border border-white/15 bg-white/95 p-5 text-center shadow-2xl sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">401 Unauthorized</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Sesi Belum Terautentikasi</h1>
            <p class="mx-auto mt-3 max-w-xl text-sm text-slate-600 sm:text-base">
                Anda perlu login terlebih dahulu untuk mengakses modul procurement dan melanjutkan aktivitas operasional.
            </p>

            <div class="mt-6 flex flex-col gap-2.5 sm:flex-row sm:justify-center">
                <a href="{{ route('login') }}" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                    Ke Halaman Login
                </a>
                <a href="{{ url('/') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Kembali ke Beranda
                </a>
            </div>
        </section>
    </main>
</body>
</html>
