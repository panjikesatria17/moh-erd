<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Procurement UI')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/smp-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class([
    'text-gray-900',
    'bg-gray-50' => ! request()->routeIs('ui.dashboard'),
    'relative bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100 bg-fixed' => request()->routeIs('ui.dashboard'),
])
>
    @php
        $currentRoleValue = auth()->user()?->role?->value;
        $currentUser = auth()->user();
        $roleLabels = \App\Enums\UserRole::labels();
        $currentRoleLabel = $roleLabels[$currentRoleValue] ?? 'Pengguna';
        $roleDescriptions = [
            \App\Enums\UserRole::SUPER_ADMIN->value => 'Kontrol penuh seluruh modul procurement HO.',
            \App\Enums\UserRole::ADMIN->value => 'Kontrol operasional hampir setara super admin (tanpa kontrol program ON/OFF).',
            \App\Enums\UserRole::OWNER->value => 'Akses persetujuan strategis dan monitoring menyeluruh.',
            \App\Enums\UserRole::FINANCE->value => 'Fokus pada invoice, billing cycle, dan pembayaran.',
            \App\Enums\UserRole::PURCHASING->value => 'Fokus pada PR, PO, vendor, dan master pengadaan.',
            \App\Enums\UserRole::ADMIN_GUDANG->value => 'Fokus pada delivery, pergerakan stok, dan alert stok.',
            \App\Enums\UserRole::EXPEDITION->value => 'Fokus pada proses pengiriman, bukti kirim, dan surat jalan ttd.',
            \App\Enums\UserRole::SPPG_USER->value => 'Fokus pengajuan kebutuhan SPPG dan pemantauan PR.',
            \App\Enums\UserRole::VENDOR_ADMIN->value => 'Fokus koordinasi vendor dan pemenuhan pengadaan.',
        ];
        $currentRoleDescription = $roleDescriptions[$currentRoleValue] ?? 'Akses procurement sesuai peran pengguna.';

        $hasNotificationsTable = false;
        $unreadNotificationCount = 0;
        $recentNotifications = collect();
        if ($currentUser) {
            $hasNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
            if ($hasNotificationsTable) {
                $unreadNotificationCount = $currentUser->unreadNotifications()->count();
                $recentNotifications = $currentUser->notifications()->latest()->limit(6)->get();
            }
        }
    @endphp

    <div @class([
        'min-h-screen',
        'flex flex-col',
        'relative z-10' => request()->routeIs('ui.dashboard'),
    ])>
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-6 py-4">
                <div>
                    <h1 class="text-lg font-semibold">C-Procurement Platform</h1>
                    <p class="text-sm text-gray-500">Enterprise dashboard for PR, PO, delivery, and billing.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($hasNotificationsTable)
                        <details class="relative">
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                Notifikasi
                                @if($unreadNotificationCount > 0)
                                    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $unreadNotificationCount }}</span>
                                @endif
                            </summary>
                            <div class="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                <div class="mb-2 flex items-center justify-between">
                                    <p class="text-xs font-semibold text-slate-700">Notifikasi Terbaru</p>
                                    @if($unreadNotificationCount > 0)
                                        <form method="POST" action="{{ route('ui.notifications.read-all') }}">
                                            @csrf
                                            <button type="submit" class="text-[11px] font-medium text-blue-700 hover:text-blue-800">Tandai semua dibaca</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="max-h-72 space-y-2 overflow-y-auto">
                                    @forelse($recentNotifications as $notification)
                                        @php
                                            $payload = is_array($notification->data ?? null) ? $notification->data : [];
                                            $title = $payload['title'] ?? 'Notifikasi';
                                            $message = $payload['message'] ?? '-';
                                            $url = $payload['url'] ?? route('ui.dashboard');
                                        @endphp
                                        <a href="{{ $url }}" class="block rounded-md border px-2.5 py-2 text-xs {{ $notification->read_at ? 'border-slate-200 bg-white text-slate-600' : 'border-blue-200 bg-blue-50 text-slate-800' }}">
                                            <p class="font-semibold">{{ $title }}</p>
                                            <p class="mt-0.5">{{ $message }}</p>
                                        </a>
                                    @empty
                                        <p class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-500">Belum ada notifikasi.</p>
                                    @endforelse
                                </div>
                            </div>
                        </details>
                    @endif
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">{{ $currentRoleLabel }}</span>
                </div>
            </div>
        </header>

        <div class="mx-auto grid w-full max-w-screen-2xl flex-1 grid-cols-1 gap-6 px-6 py-6 lg:grid-cols-12">
            <aside class="lg:col-span-3">
                <nav class="rounded-xl border border-gray-200 bg-white p-3">
                    @php
                        $effectiveRole = in_array($currentRoleValue, [\App\Enums\UserRole::ADMIN->value, \App\Enums\UserRole::OWNER->value], true)
                            ? \App\Enums\UserRole::SUPER_ADMIN->value
                            : $currentRoleValue;

                        $role = $effectiveRole;
                        $hasRole = static fn (array $allowedRoles): bool => in_array($role, $allowedRoles, true);

                        $canApproval = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                        ]);
                        $canPurchaseRequest = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::PURCHASING->value,
                            \App\Enums\UserRole::SPPG_USER->value,
                        ]);
                        $canPurchaseOrder = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::PURCHASING->value,
                            \App\Enums\UserRole::VENDOR_ADMIN->value,
                        ]);
                        $canDeliveries = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::ADMIN_GUDANG->value,
                            \App\Enums\UserRole::EXPEDITION->value,
                            \App\Enums\UserRole::VENDOR_ADMIN->value,
                        ]);
                        $canRejectedItems = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::ADMIN_GUDANG->value,
                            \App\Enums\UserRole::SPPG_USER->value,
                            \App\Enums\UserRole::PURCHASING->value,
                        ]);
                        $canStockMovements = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::ADMIN_GUDANG->value,
                        ]);
                        $canStockAlerts = $canStockMovements;
                        $canInvoices = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::FINANCE->value,
                            \App\Enums\UserRole::VENDOR_ADMIN->value,
                        ]);
                        $canKwitansi = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::FINANCE->value,
                        ]);
                        $canBillingCycles = $canKwitansi;
                        $canPurchaseFunding = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::FINANCE->value,
                        ]);
                        $canPayments = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::FINANCE->value,
                            \App\Enums\UserRole::SPPG_USER->value,
                        ]);
                        $canMasterData = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::PURCHASING->value,
                        ]);
                        $canUsersRoles = $currentRoleValue === \App\Enums\UserRole::SUPER_ADMIN->value;
                        $canProgramControl = $currentRoleValue === \App\Enums\UserRole::SUPER_ADMIN->value;
                        $canAuditTrail = $canApproval;
                        $canAnalytics = $hasRole([
                            \App\Enums\UserRole::SUPER_ADMIN->value,
                            \App\Enums\UserRole::OWNER->value,
                            \App\Enums\UserRole::FINANCE->value,
                            \App\Enums\UserRole::PURCHASING->value,
                        ]);
                        $sidebarSectionLabelClass = 'mb-2 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-500';
                    @endphp

                    <div class="mb-4 rounded-2xl border border-[#c8ccd1] bg-[#f3f4f6] px-4 py-4">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('images/logo-smp.png') }}" alt="Logo Satria Merah Putih" class="h-24 w-24 shrink-0 object-contain md:h-28 md:w-28">
                            <div class="min-w-0">
                                <p class="text-xl font-black uppercase leading-tight tracking-[0.01em] text-black">Panel Navigasi</p>
                                <p class="mt-1 text-[11px] font-semibold uppercase leading-tight tracking-[0.06em] text-slate-600">Yayasan Satria Merah Putih</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="{{ $sidebarSectionLabelClass }}">Overview</p>
                        <a href="{{ route('ui.dashboard') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.dashboard') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Dashboard</a>
                    </div>

                    @if($canPurchaseRequest || $canPurchaseOrder || $canApproval)
                        <div class="mb-4">
                            <p class="{{ $sidebarSectionLabelClass }}">Procurement Workflow</p>
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

                    @if($canDeliveries || $canRejectedItems || $canStockMovements || $canStockAlerts)
                        <div class="mb-4">
                            <p class="{{ $sidebarSectionLabelClass }}">Inventory & Distribution</p>
                            @if($canDeliveries)
                                <a href="{{ route('ui.deliveries.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.deliveries.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Deliveries / Goods Receipt</a>
                            @endif
                            @if($canRejectedItems)
                                <a href="{{ route('ui.rejected-items.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.rejected-items.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Barang Reject</a>
                            @endif
                            @if($canStockMovements)
                                <a href="{{ route('ui.stock-movements.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.stock-movements.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Stock Movements</a>
                            @endif
                            @if($canStockAlerts)
                                <a href="{{ route('ui.stock-alerts.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.stock-alerts.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Stock Alerts</a>
                            @endif
                        </div>
                    @endif

                    @if($canInvoices || $canKwitansi || $canBillingCycles || $canPurchaseFunding || $canPayments)
                        <div class="mb-4">
                            <p class="{{ $sidebarSectionLabelClass }}">Finance & Billing</p>
                            @if($canInvoices)
                                <a href="{{ route('ui.invoices.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.invoices.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Invoices</a>
                            @endif
                            @if($canKwitansi)
                                <a href="{{ route('ui.kwitansi.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.kwitansi.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Kwitansi</a>
                            @endif
                            @if($canBillingCycles)
                                <a href="{{ route('ui.billing-cycles.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.billing-cycles.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Billing Cycles (Weekly)</a>
                            @endif
                            @if($canPurchaseFunding)
                                <a href="{{ route('ui.purchase-funding-requests.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.purchase-funding-requests.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Pengajuan Dana Pembelian</a>
                            @endif
                            @if($canPayments)
                                <a href="{{ route('ui.payments.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.payments.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Payments</a>
                            @endif
                        </div>
                    @endif

                    @if($canMasterData || $canUsersRoles)
                        <div class="mb-4">
                            <p class="{{ $sidebarSectionLabelClass }}">Master Data</p>
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

                    @if($canAnalytics || $canAuditTrail)
                        <div class="mb-2">
                            <p class="{{ $sidebarSectionLabelClass }}">Analytics & Compliance</p>
                            @if($canAnalytics)
                                <a href="{{ route('ui.vendor-performances.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.vendor-performances.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Vendor Performance</a>
                                <a href="{{ route('ui.price-trends.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.price-trends.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Price Trend Analysis</a>
                            @endif
                            @if($canAuditTrail)
                                <a href="{{ route('ui.audit-trails.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.audit-trails.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Audit Trail</a>
                            @endif
                        </div>
                    @endif

                    @if($canProgramControl)
                        <div class="mb-2">
                            <p class="{{ $sidebarSectionLabelClass }}">System Control</p>
                            <a href="{{ route('ui.program-control.index') }}" class="mb-1 block rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('ui.program-control.*') ? 'bg-gray-100 text-blue-700' : 'text-gray-700' }}">Program ON/OFF</a>
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

        <footer class="sticky bottom-0 z-30 mt-auto border-t border-slate-200/80 bg-white/95 shadow-[0_-6px_18px_rgba(15,23,42,0.05)] backdrop-blur">
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-6 py-2.5 text-xs text-slate-600">
                <p>© 2026 Yayasan Satria Merah Putih</p>
                <a href="https://c-projection.com">By C-projection</a>
            </div>
        </footer>
    </div>

    <script>
        (() => {
            const parseNumber = (value) => {
                const raw = String(value ?? '').trim();
                if (raw === '') {
                    return null;
                }

                let normalized = raw.replace(/\s+/g, '');
                const hasDot = normalized.includes('.');
                const hasComma = normalized.includes(',');

                if (hasDot && hasComma) {
                    normalized = normalized.replace(/\./g, '').replace(/,/g, '.');
                } else if (hasDot && /^-?\d{1,3}(\.\d{3})+$/.test(normalized)) {
                    normalized = normalized.replace(/\./g, '');
                } else if (hasComma) {
                    normalized = normalized.replace(/,/g, '.');
                }

                normalized = normalized.replace(/[^0-9.\-]/g, '');
                if (normalized === '' || normalized === '-' || normalized === '.' || normalized === '-.') {
                    return null;
                }

                if ((normalized.match(/\./g) || []).length > 1) {
                    const parts = normalized.split('.');
                    const decimalPart = parts.pop();
                    normalized = `${parts.join('')}.${decimalPart}`;
                }

                const parsed = Number.parseFloat(normalized);
                return Number.isFinite(parsed) ? parsed : null;
            };

            const formatNumber = (value, minimumFractionDigits = 0, maximumFractionDigits = 2) => {
                return value.toLocaleString('id-ID', { minimumFractionDigits, maximumFractionDigits });
            };

            const formatRupiah = (value, minimumFractionDigits = 0, maximumFractionDigits = 2) => {
                return `Rp ${formatNumber(value, minimumFractionDigits, maximumFractionDigits)}`;
            };

            const autoFormatInputs = () => {
                document.querySelectorAll('.js-idr-input').forEach((input) => {
                    input.addEventListener('blur', () => {
                        const parsed = parseNumber(input.value);
                        if (parsed === null) {
                            return;
                        }

                        input.value = formatNumber(parsed);
                    });

                    const initial = parseNumber(input.value);
                    if (initial !== null) {
                        input.value = formatNumber(initial);
                    }
                });
            };

            window.__idr = {
                parseNumber,
                formatNumber,
                formatRupiah,
                autoFormatInputs,
            };

            autoFormatInputs();
        })();
    </script>
</body>
</html>
