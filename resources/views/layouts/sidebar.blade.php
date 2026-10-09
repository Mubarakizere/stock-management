<aside
    class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col
           bg-gray-900 border-r border-gray-800 shadow-xl
           lg:static lg:translate-x-0 transition-transform duration-200 ease-out
           overflow-hidden"
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    @click.away="if (window.innerWidth < 1024) sidebarOpen = false"
>
    {{-- Header --}}
    <div class="flex-shrink-0 flex items-center justify-between px-4 py-4 border-b border-gray-800">
        <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center justify-center h-8 w-8 rounded-xl bg-teal-500/10 border border-teal-500/20 text-teal-400">
                <i data-lucide="boxes" class="w-4 h-4"></i>
            </span>
            <h1 class="text-sm font-semibold text-white tracking-tight">
                {{ config('app.name', 'Stock Manager') }}
            </h1>
        </div>
        <button class="lg:hidden text-gray-400 hover:text-white p-1 rounded-lg hover:bg-gray-800 transition"
                @click="sidebarOpen = false"
                aria-label="Close sidebar">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    {{-- Scrollable Navigation --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar">
        <nav class="px-3 py-3 space-y-1">

            @php
                use Illuminate\Support\Facades\Auth;

                $user = Auth::user();
                $role = $user?->getRoleNames()->first() ?? 'user';

                // Auto-expand based on current route
                $salesActive = request()->routeIs('sales.*') || request()->routeIs('customers.*');
                $inventoryActive = request()->routeIs('products.*') || request()->routeIs('raw-materials.*') || request()->routeIs('stock.history*') || request()->routeIs('productions.*');
                $purchasesActive = request()->routeIs('purchases.*') || request()->routeIs('suppliers.*');
                $financeActive = request()->routeIs('transactions.*')
                    || request()->routeIs('debits-credits.*')
                    || request()->routeIs('expenses.*')
                    || request()->routeIs('loans.*')
                    || request()->routeIs('item-loans.*');
                $reportsActive = request()->routeIs('reports.*');
                $settingsActive = request()->routeIs('users.*')
                    || request()->routeIs('roles.*')
                    || request()->routeIs('categories.*')
                    || request()->routeIs('payment-channels.*');
            @endphp

            {{-- Main Navigation --}}
            <div class="px-3 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Main
            </div>

            {{-- Dashboard --}}
            @can('reports.view')
                <x-sidebar-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </x-sidebar-link>
            @endcan

            {{-- Operations Section --}}
            <div class="px-3 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Operations
            </div>

            {{-- Sales & Customers --}}
            @canany(['sales.view', 'customers.view'])
                <div x-data="{ open: {{ $salesActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-colors"
                            :class="open || {{ $salesActive ? 'true' : 'false' }} ? 'text-white bg-gray-800/80' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="shopping-cart" class="w-4 h-4 {{ $salesActive ? 'text-teal-400' : 'text-gray-400' }}"></i>
                            <span>Sales</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-gray-800 mt-1 space-y-1">
                        @can('sales.view')
                            <a href="{{ route('sales.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('sales.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                                <span>All Sales</span>
                            </a>
                        @endcan

                        @can('customers.view')
                            <a href="{{ route('customers.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('customers.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                <span>Customers</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- Inventory & Manufacturing --}}
            @canany(['products.view', 'stock.view'])
                <div x-data="{ open: {{ $inventoryActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-colors"
                            :class="open || {{ $inventoryActive ? 'true' : 'false' }} ? 'text-white bg-gray-800/80' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="package" class="w-4 h-4 {{ $inventoryActive ? 'text-teal-400' : 'text-gray-400' }}"></i>
                            <span>Inventory</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-gray-800 mt-1 space-y-1">
                        @can('products.view')
                            <a href="{{ route('products.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('products.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="box" class="w-3.5 h-3.5"></i>
                                <span>Products</span>
                            </a>
                        @endcan

                        @can('products.view')
                            <a href="{{ route('raw-materials.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('raw-materials.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="flask-conical" class="w-3.5 h-3.5"></i>
                                <span>Raw Materials</span>
                            </a>
                        @endcan

                        @can('products.view')
                            <a href="{{ route('productions.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('productions.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="factory" class="w-3.5 h-3.5"></i>
                                <span>Production</span>
                            </a>
                        @endcan

                        @can('stock.view')
                            <a href="{{ route('stock.history') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('stock.history*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="history" class="w-3.5 h-3.5"></i>
                                <span>Stock Movements</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- Purchases & Suppliers --}}
            @canany(['purchases.view', 'suppliers.view'])
                <div x-data="{ open: {{ $purchasesActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-colors"
                            :class="open || {{ $purchasesActive ? 'true' : 'false' }} ? 'text-white bg-gray-800/80' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="shopping-bag" class="w-4 h-4 {{ $purchasesActive ? 'text-teal-400' : 'text-gray-400' }}"></i>
                            <span>Purchases</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-gray-800 mt-1 space-y-1">
                        @can('purchases.view')
                            <a href="{{ route('purchases.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('purchases.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                <span>All Purchases</span>
                            </a>
                        @endcan

                        @can('suppliers.view')
                            <a href="{{ route('suppliers.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('suppliers.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                                <span>Suppliers</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- Finance Section --}}
            <div class="px-3 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Finance
            </div>

            {{-- Finance Dropdown --}}
            @canany(['transactions.view', 'debits-credits.view', 'loans.view'])
                <div x-data="{ open: {{ $financeActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-colors"
                            :class="open || {{ $financeActive ? 'true' : 'false' }} ? 'text-white bg-gray-800/80' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="dollar-sign" class="w-4 h-4 {{ $financeActive ? 'text-teal-400' : 'text-gray-400' }}"></i>
                            <span>Finance</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-gray-800 mt-1 space-y-1">
                        @can('transactions.view')
                            <a href="{{ route('expenses.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('expenses.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                                <span>Expenses</span>
                            </a>

                            <a href="{{ route('transactions.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('transactions.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                                <span>Transactions</span>
                            </a>
                        @endcan

                        @can('debits-credits.view')
                            <a href="{{ route('debits-credits.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('debits-credits.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="scale" class="w-3.5 h-3.5"></i>
                                <span>Debits & Credits</span>
                            </a>
                        @endcan

                        @can('loans.view')
                            <a href="{{ route('loans.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('loans.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="hand-coins" class="w-3.5 h-3.5"></i>
                                <span>Loans</span>
                            </a>

                            <a href="{{ route('item-loans.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('item-loans.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="handshake" class="w-3.5 h-3.5"></i>
                                <span>Inter-Company</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- Analytics & Administration --}}
            <div class="px-3 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Administration
            </div>

            {{-- Reports --}}
            @can('reports.view')
                <div x-data="{ open: {{ $reportsActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-colors"
                            :class="open || {{ $reportsActive ? 'true' : 'false' }} ? 'text-white bg-gray-800/80' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 {{ $reportsActive ? 'text-teal-400' : 'text-gray-400' }}"></i>
                            <span>Reports</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-gray-800 mt-1 space-y-1">
                        @if(Route::has('reports.index'))
                            <a href="{{ route('reports.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('reports.index') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="file-bar-chart" class="w-3.5 h-3.5"></i>
                                <span>Overview</span>
                            </a>
                        @endif

                        <a href="{{ route('reports.manufacturing') }}"
                           class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                  {{ request()->routeIs('reports.manufacturing') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                            <i data-lucide="factory" class="w-3.5 h-3.5"></i>
                            <span>Manufacturing</span>
                        </a>

                        <a href="{{ route('reports.suppliers.statement') }}"
                           class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                  {{ request()->routeIs('reports.suppliers.statement') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                            <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                            <span>Supplier Statement</span>
                        </a>

                        <a href="{{ route('reports.customers.statement') }}"
                           class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                  {{ request()->routeIs('reports.customers.statement') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                            <span>Customer Statement</span>
                        </a>
                    </div>
                </div>
            @endcan

            {{-- Settings --}}
            @canany(['users.view', 'roles.view', 'categories.view', 'payment-channels.view'])
                <div x-data="{ open: {{ $settingsActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-colors"
                            :class="open || {{ $settingsActive ? 'true' : 'false' }} ? 'text-white bg-gray-800/80' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="settings" class="w-4 h-4 {{ $settingsActive ? 'text-teal-400' : 'text-gray-400' }}"></i>
                            <span>Settings</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-gray-800 mt-1 space-y-1">
                        @can('users.view')
                            <a href="{{ route('users.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('users.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                <span>Users</span>
                            </a>
                        @endcan

                        @can('roles.view')
                            <a href="{{ route('roles.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('roles.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                                <span>Roles</span>
                            </a>
                        @endcan

                        @can('categories.view')
                            <a href="{{ route('categories.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('categories.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="folder" class="w-3.5 h-3.5"></i>
                                <span>Categories</span>
                            </a>
                        @endcan

                        @can('payment-channels.view')
                            <a href="{{ route('payment-channels.index') }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('payment-channels.*') ? 'bg-teal-500/10 text-teal-400 font-semibold' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50' }}">
                                <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                                <span>Payment Channels</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- Separator --}}
            <div class="border-t border-gray-800 my-4"></div>

            {{-- User Info Card --}}
            <div class="px-3 py-2.5 rounded-xl bg-gray-800/60 border border-gray-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-teal-500/10 border border-teal-500/20 text-teal-400 text-xs font-semibold flex items-center justify-center shrink-0">
                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-200 text-sm truncate leading-tight">
                            {{ $user->name }}
                        </p>
                        <p class="text-[11px] text-gray-400 capitalize mt-0.5">
                            {{ $role }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-gray-400 hover:bg-rose-500/10 hover:text-rose-400 rounded-xl transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span>Logout</span>
                </button>
            </form>

            {{-- Version/Footer --}}
            <p class="text-[11px] text-center text-gray-400 dark:text-gray-500 mt-3 pb-2">
                v1.0.0 &bull; Stock Management
            </p>
        </nav>
    </div>
</aside>

{{-- Custom Scrollbar Styles --}}
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.12);
        border-radius: 9999px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(255, 255, 255, 0.22);
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background-color: transparent;
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>
