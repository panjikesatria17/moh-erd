<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HO Procurement</title>
    <link rel="icon" type="image/png" href="{{ asset('images/smp-logo.png') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $loginBackgroundImage = config('ui.login_background_image', '/images/bg-login.png');
    $loginBackgroundPosition = config('ui.login_background_position', 'center center');
    $loginBackgroundSize = config('ui.login_background_size', 'cover');

    if ($loginBackgroundImage && ! str_starts_with($loginBackgroundImage, 'http://') && ! str_starts_with($loginBackgroundImage, 'https://') && ! str_starts_with($loginBackgroundImage, 'data:') && ! str_starts_with($loginBackgroundImage, '/')) {
        $loginBackgroundImage = asset(ltrim($loginBackgroundImage, '/'));
    }
@endphp
<body class="relative min-h-screen bg-slate-950 bg-no-repeat text-slate-900" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; background-image: url('{{ $loginBackgroundImage }}'); background-position: {{ $loginBackgroundPosition }}; background-size: {{ $loginBackgroundSize }};">
    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-[2px]"></div>
    <div class="pointer-events-none absolute inset-0 bg-linear-to-br from-cyan-400/10 via-transparent to-blue-500/15"></div>

    <div class="relative z-10 mx-auto grid min-h-screen w-full max-w-6xl grid-cols-1 items-center gap-6 px-4 py-6 sm:px-6 sm:py-10 lg:grid-cols-2">
        <section class="order-2 rounded-3xl border border-white/10 bg-white/5 p-5 text-white shadow-2xl backdrop-blur-md lg:order-1 lg:p-8">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-200">Enterprise Procurement</p>
            <h1 class="mt-2 text-2xl font-extrabold leading-tight sm:text-3xl">C-Procurement Platform</h1>
            <p class="mt-3 text-sm text-slate-200">
                Alur pengadaan terintegrasi dari purchase request hingga settlement vendor, dengan kontrol approval yang akurat dan monitoring operasional real-time.
            </p>

            <div class="mt-5 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                <div class="rounded-xl border border-white/15 bg-white/10 p-3">
                    <p class="text-[11px] uppercase tracking-wide text-slate-200">Workflow</p>
                    <p class="mt-1 text-sm font-semibold">PR, PO, Delivery, Invoice</p>
                </div>
                <div class="rounded-xl border border-white/15 bg-white/10 p-3">
                    <p class="text-[11px] uppercase tracking-wide text-slate-200">Finance Control</p>
                    <p class="mt-1 text-sm font-semibold">Settlement & Approval</p>
                </div>
            </div>
        </section>

        <section class="order-1 lg:order-2">
            <div class="mx-auto w-full max-w-md rounded-3xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="relative h-14 w-14 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <img
                                src="{{ asset('images/logo-smp.png') }}"
                                alt="Logo Satria Merah Putih"
                                class="absolute left-1/2 top-1/2 h-full w-full max-h-none max-w-none object-contain"
                                style="transform: translate(-50%, -46.8%) scale(1.98); transform-origin: center;"
                            >
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Yayasan Satria Merah Putih</p>
                            <p class="mt-0.5 text-base font-bold text-slate-900">Masuk ke Dashboard</p>
                        </div>
                    </div>
                </div>

                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200" placeholder="nama@perusahaan.com">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Password</label>
                        <input type="password" name="password" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200" placeholder="Masukkan password">
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
                        Ingat saya
                    </label>

                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">Masuk</button>
                </form>

                <a href="{{ url('/') }}" class="mt-4 block text-center text-sm text-slate-500 hover:text-slate-700">Kembali ke halaman awal</a>
            </div>
        </section>
    </div>
</body>
</html>
