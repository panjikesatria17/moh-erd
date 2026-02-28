<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Procurement UI')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div>
                    <h1 class="text-lg font-semibold">HO Procurement Platform</h1>
                    <p class="text-sm text-gray-500">Enterprise dashboard for PR, PO, delivery, and billing.</p>
                </div>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">Laravel UI</span>
            </div>
        </header>

        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-6 py-6 lg:grid-cols-12">
            <aside class="lg:col-span-3">
                <nav class="rounded-xl border border-gray-200 bg-white p-3">
                    <a href="{{ route('ui.dashboard') }}" class="mb-2 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.dashboard') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Dashboard</a>
                    <a href="{{ route('ui.purchase-requests.index') }}" class="mb-2 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.purchase-requests.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Purchase Requests</a>
                    <a href="{{ route('ui.purchase-orders.index') }}" class="mb-2 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.purchase-orders.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Purchase Orders</a>
                    <a href="{{ route('ui.deliveries.index') }}" class="mb-2 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.deliveries.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Deliveries</a>
                    <a href="{{ route('ui.invoices.index') }}" class="block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.invoices.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Invoices</a>
                </nav>
            </aside>

            <main class="lg:col-span-9">
                @if(session('success'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p class="mb-1 font-medium">Terjadi kesalahan:</p>
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
