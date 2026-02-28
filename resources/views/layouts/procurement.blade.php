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
                    @php
                        $role = auth()->user()?->role?->value;
                        $isSuperAdmin = $role === \App\Enums\UserRole::SUPER_ADMIN->value;
                        $isOwner = $role === \App\Enums\UserRole::OWNER->value;
                        $isFinance = $role === \App\Enums\UserRole::FINANCE->value;
                        $isPurchasing = $role === \App\Enums\UserRole::PURCHASING->value;
                        $isAdminGudang = $role === \App\Enums\UserRole::ADMIN_GUDANG->value;
                        $isSppgUser = $role === \App\Enums\UserRole::SPPG_USER->value;

                        $canApproval = $isSuperAdmin || $isOwner;
                        $canPurchaseRequest = $isSuperAdmin || $isOwner || $isPurchasing || $isSppgUser;
                        $canPurchaseOrder = $isSuperAdmin || $isOwner || $isPurchasing;
                        $canInventory = $isSuperAdmin || $isOwner || $isAdminGudang;
                        $canFinance = $isSuperAdmin || $isOwner || $isFinance;
                        $canMasterData = $isSuperAdmin || $isOwner || $isPurchasing;
                        $canUsersRoles = $isSuperAdmin || $isOwner;
                        $canAnalytics = $isSuperAdmin || $isOwner || $isFinance || $isPurchasing;
                    @endphp

                    <div class="mb-4 border-b border-gray-100 pb-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Administrator</p>
                        <p class="mt-1 text-xs text-gray-500">Kontrol penuh procurement HO multi-SPPG</p>
                    </div>

                    <div class="mb-4">
                        <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Overview</p>
                        <a href="{{ route('ui.dashboard') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.dashboard') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Dashboard</a>
                    </div>

                    @if($canPurchaseRequest || $canPurchaseOrder || $canApproval)
                        <div class="mb-4">
                            <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Procurement Workflow</p>
                            @if($canPurchaseRequest)
                                <a href="{{ route('ui.purchase-requests.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.purchase-requests.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Purchase Requests</a>
                            @endif
                            @if($canPurchaseOrder)
                                <a href="{{ route('ui.purchase-orders.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.purchase-orders.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Purchase Orders</a>
                            @endif
                            @if($canApproval)
                                <a href="{{ route('ui.approvals.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.approvals.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Approval Queue</a>
                            @endif
                        </div>
                    @endif

                    @if($canInventory)
                        <div class="mb-4">
                            <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Inventory & Distribution</p>
                            <a href="{{ route('ui.deliveries.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.deliveries.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Deliveries / Goods Receipt</a>
                            <a href="{{ route('ui.stock-movements.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.stock-movements.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Stock Movements</a>
                            <a href="{{ route('ui.stock-alerts.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.stock-alerts.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Stock Alerts</a>
                        </div>
                    @endif

                    @if($canFinance)
                        <div class="mb-4">
                            <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Finance & Billing</p>
                            <a href="{{ route('ui.invoices.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.invoices.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Invoices</a>
                            <a href="{{ route('ui.billing-cycles.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.billing-cycles.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Billing Cycles (Weekly)</a>
                            <a href="{{ route('ui.payments.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.payments.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Payments</a>
                        </div>
                    @endif

                    @if($canMasterData || $canUsersRoles)
                        <div class="mb-4">
                            <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Master Data</p>
                            @if($canMasterData)
                                <a href="{{ route('ui.master-data.sppgs.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.master-data.sppgs.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">SPPG</a>
                                <a href="{{ route('ui.master-data.vendors.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.master-data.vendors.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Vendors</a>
                                <a href="{{ route('ui.master-data.products.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.master-data.products.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Products</a>
                                <a href="{{ route('ui.master-data.price-histories.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.master-data.price-histories.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Price History</a>
                            @endif
                            @if($canUsersRoles)
                                <a href="{{ route('ui.users-roles.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.users-roles.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Users & Roles</a>
                            @endif
                        </div>
                    @endif

                    @if($canAnalytics || $canUsersRoles)
                        <div class="mb-2">
                            <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Analytics & Compliance</p>
                            @if($canAnalytics)
                                <a href="{{ route('ui.vendor-performances.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.vendor-performances.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Vendor Performance</a>
                                <a href="{{ route('ui.price-trends.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.price-trends.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Price Trend Analysis</a>
                            @endif
                            @if($canUsersRoles)
                                <a href="{{ route('ui.audit-trails.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.audit-trails.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Audit Trail</a>
                            @endif
                        </div>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t border-gray-100 pt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                            Logout
                        </button>
                    </form>
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
