<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - SPPG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-200">
    <div class="mx-auto flex min-h-screen w-full max-w-[1600px] flex-col items-center justify-center px-3 py-3 sm:px-4 md:px-6 md:py-6 text-center">
        <h1 class="mb-4 text-[1.85rem] font-bold leading-tight tracking-wide text-black sm:text-2xl md:mb-8 md:text-5xl">SELAMAT DATANG DI PROGRAM</h1>

        <div class="mb-4 grid w-full grid-cols-[6.5rem_minmax(0,1fr)_6.5rem] items-center gap-0 sm:grid-cols-[7rem_minmax(0,1fr)_7rem] sm:gap-1 md:mb-8 md:grid-cols-[1fr_auto_1fr] md:gap-4">
            <div class="h-[6.5rem] w-[6.5rem] justify-self-end sm:h-[7rem] sm:w-[7rem] md:h-64 md:w-64 lg:h-72 lg:w-72">
                <img src="{{ asset('images/logo-bgn.png') }}" alt="Badan Gizi Nasional" class="h-full w-full object-contain">
            </div>

            <div class="px-1 md:justify-self-center">
                <p class="text-[clamp(1.15rem,5.2vw,1.9rem)] font-bold leading-tight tracking-wide text-black md:text-6xl">SATUAN PELAYANAN</p>
                <p class="mt-1 text-[clamp(1.15rem,5.2vw,1.9rem)] font-bold leading-tight tracking-wide text-black md:mt-2 md:text-6xl">PEMENUHAN GIZI</p>
            </div>

            <div class="h-[6.5rem] w-[6.5rem] justify-self-start sm:h-[7rem] sm:w-[7rem] md:h-64 md:w-64 lg:h-72 lg:w-72">
                <img src="{{ asset('images/logo-smp.png') }}" alt="Satria Merah Putih" class="h-full w-full object-contain">
            </div>
        </div>

        <a href="{{ route('login') }}"
           class="mb-4 inline-flex rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 px-7 py-2 text-base font-medium tracking-wide text-white shadow-lg transition hover:from-blue-600 hover:to-indigo-600 sm:px-8 sm:text-lg md:mb-8 md:px-12 md:py-3 md:text-3xl">
            SELANJUTNYA
        </a>

        <p class="text-sm text-black sm:text-base md:text-2xl">version.0.1.0.0</p>
    </div>
</body>
</html>
