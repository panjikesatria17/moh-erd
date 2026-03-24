<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Yayasan Satria Merah Putih</title>
    <link rel="icon" type="image/png" href="{{ asset('images/smp-logo.png') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $welcomeBackgroundImage = config('ui.welcome_background_image', '/images/bg-login.png');
    $welcomeBackgroundPosition = config('ui.welcome_background_position', 'center center');
    $welcomeBackgroundSize = config('ui.welcome_background_size', 'cover');

    if ($welcomeBackgroundImage && ! str_starts_with($welcomeBackgroundImage, 'http://') && ! str_starts_with($welcomeBackgroundImage, 'https://') && ! str_starts_with($welcomeBackgroundImage, 'data:') && ! str_starts_with($welcomeBackgroundImage, '/')) {
        $welcomeBackgroundImage = asset(ltrim($welcomeBackgroundImage, '/'));
    }
@endphp
<body class="relative min-h-screen overflow-x-hidden bg-slate-950 bg-no-repeat text-white" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; background-image: url('{{ $welcomeBackgroundImage }}'); background-position: {{ $welcomeBackgroundPosition }}; background-size: {{ $welcomeBackgroundSize }};">
    <div class="fixed inset-0 bg-slate-950/86"></div>
    <div class="pointer-events-none fixed inset-0 bg-linear-to-b from-cyan-300/8 via-transparent to-blue-500/8"></div>
    <div class="pointer-events-none fixed -top-28 -right-24 h-72 w-72 rounded-full bg-cyan-300/20 blur-3xl"></div>
    <div class="pointer-events-none fixed -bottom-28 -left-20 h-72 w-72 rounded-full bg-blue-500/20 blur-3xl"></div>

    <main class="relative z-10 mx-auto flex min-h-dvh w-full max-w-5xl items-center px-4 py-8 sm:px-6 sm:py-10">
        <section class="w-full rounded-3xl border border-white/20 bg-slate-900/55 p-5 shadow-2xl backdrop-blur-md sm:p-7 lg:p-9">
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <div class="rounded-2xl border border-white/25 bg-white p-3 shadow-md sm:p-4">
                    <img src="{{ asset('images/logo-bgn.png') }}" alt="Logo BGN" class="mx-auto h-24 w-auto object-contain sm:h-28 lg:h-32">
                </div>
                <div class="rounded-2xl border border-white/25 bg-white p-3 shadow-md sm:p-4">
                    <img src="{{ asset('images/logo-smp.png') }}" alt="Logo Satria Merah Putih" class="mx-auto h-24 w-auto object-contain sm:h-28 lg:h-32">
                </div>
            </div>

            <p class="mt-3 text-center text-[10px] font-semibold uppercase tracking-[0.16em] text-cyan-100 sm:text-[11px]">
                Sinergi Program BGN dan Yayasan Satria Merah Putih
            </p>

            <div class="mx-auto mt-7 max-w-3xl text-center">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-200">Enterprise Procurement Platform</p>
                <h1 class="mt-2 text-3xl font-extrabold leading-tight text-white sm:text-4xl lg:text-5xl">
                    C-Procurement
                    <span class="block text-cyan-200">Yayasan Satria Merah Putih</span>
                </h1>
                <p class="mx-auto mt-4 max-w-2xl text-sm text-slate-200 sm:text-base">
                    Platform pengadaan terintegrasi untuk mengelola purchase request, purchase order, distribusi barang, invoice, hingga settlement vendor secara akurat dan terstruktur.
                </p>
            </div>

            <div class="mx-auto mt-6 grid max-w-3xl grid-cols-1 gap-2.5 sm:grid-cols-3">
                <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 text-center text-xs text-slate-100">Workflow PR sampai Payment</div>
                <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 text-center text-xs text-slate-100">Approval Berbasis Role</div>
                <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 text-center text-xs text-slate-100">Settlement dan Ledger Transparan</div>
            </div>

            <div class="mx-auto mt-6 max-w-md">
                <a href="{{ route('login') }}" class="block rounded-xl bg-white px-5 py-3 text-center text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Masuk ke Aplikasi</a>
            </div>

            <p class="mt-6 text-center text-[11px] text-slate-300">Version 0.1.0.1</p>
        </section>
    </main>
</body>
</html>
