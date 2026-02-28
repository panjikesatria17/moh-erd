<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HO Procurement</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
    <div class="mx-auto flex min-h-screen max-w-md items-center px-6 py-10">
        <div class="w-full rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="mb-1 text-xl font-semibold">Login</h1>
            <p class="mb-5 text-sm text-gray-500">Masuk untuk melanjutkan ke dashboard procurement.</p>

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300">
                    Remember me
                </label>

                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Masuk</button>
            </form>

            <a href="{{ url('/') }}" class="mt-4 block text-center text-sm text-gray-500 hover:text-gray-700">Kembali ke halaman awal</a>
        </div>
    </div>
</body>
</html>
