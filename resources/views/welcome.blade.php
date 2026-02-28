<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - SPPG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-200">
    <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col items-center justify-center px-6 py-10 text-center">
        <h1 class="mb-12 text-3xl font-bold tracking-wide text-black md:text-5xl">SELAMAT DATANG DI PROGRAM</h1>

        <div class="mb-12 flex w-full items-center justify-center gap-8 md:gap-12">
            <div class="flex h-32 w-32 items-center justify-center rounded-full border-4 border-yellow-700 bg-blue-950 text-center text-xs font-semibold text-yellow-300 md:h-52 md:w-52 md:text-lg">
                BADAN GIZI NASIONAL
            </div>

            <div>
                <p class="text-2xl font-bold tracking-wide text-black md:text-6xl">SATUAN PELAYANAN</p>
                <p class="mt-2 text-2xl font-bold tracking-wide text-black md:text-6xl">PEMENUHAN GIZI</p>
            </div>

            <div class="flex h-32 w-32 items-center justify-center rounded-full border-4 border-red-600 bg-white text-center text-xs font-bold text-black md:h-52 md:w-52 md:text-lg">
                SATRIA MERAH PUTIH
            </div>
        </div>

        <a href="{{ route('login') }}"
           class="mb-14 inline-flex rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 px-12 py-3 text-2xl font-medium tracking-wide text-white shadow-lg transition hover:from-blue-600 hover:to-indigo-600 md:px-20 md:py-5 md:text-5xl">
            SELANJUTNYA
        </a>

        <p class="text-xl text-black md:text-4xl">version.0.1.0.0</p>
    </div>
</body>
</html>
