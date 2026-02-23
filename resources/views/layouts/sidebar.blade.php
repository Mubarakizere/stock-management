<aside
    class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col
           border-r border-emerald-800 shadow-lg
           lg:static lg:translate-x-0 transition-transform duration-200 ease-out
           overflow-hidden"
    style="background: linear-gradient(to bottom, #059669, #064e3b);"
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
    <div class="flex-shrink-0 flex items-center justify-between px-4 py-4 border-b border-white/20">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center h-8 w-8 rounded-xl bg-white/20">
                <i data-lucide="boxes" class="w-4 h-4 text-white"></i>
            </span>
            <h1 class="text-sm font-semibold text-white tracking-tight">
                {{ config('app.name', 'Stock Manager') }}
            </h1>
        </div>
        <button class="lg:hidden text-white/70 hover:text-white" @click="sidebarOpen = false">✕</button>
    </div>

    {{-- Scrollable Navigation --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar">
        <nav class="px-3 py-3 space-y-1 text-white/90">

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
                    || request()->routeIs('categories.*');
            @endphp

            {{-- 🏠 Dashboard --}}
            @can('reports.view')
                <x-sidebar-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </x-sidebar-link>
            @endcan

            {{-- 💰 Sales & Customers --}}
            @canany(['sales.view', 'customers.view'])
                <div x-data="{ open: {{ $salesActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-white/10 transition"
                            :class="open ? 'bg-white/10' : ''">
                        <span class="flex items-center gap-2">
                            <i data-lucide="shopping-cart" class="w-4 h-4 text-white"></i>
                            <span class="text-sm font-medium text-white">Sales</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-white/60 transition" :class="open ? 'rotate-180' : ''"></i></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-6 mt-1 space-y-1">
                        @can('sales.view')
                            <a href="{{ route('sales.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('sales.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="receipt" class="w-3.5 h-3.5 inline mr-2"></i>
                                All Sales
                            </a>
                        @endcan

                        @can('customers.view')
                            <a href="{{ route('customers.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('customers.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="users" class="w-3.5 h-3.5 inline mr-2"></i>
                                Customers
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- 📦 Inventory & Manufacturing --}}
            @canany(['products.view', 'stock.view'])
                <div x-data="{ open: {{ $inventoryActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-white/10 transition"
                            :class="open ? 'bg-white/10' : ''">
                        <span class="flex items-center gap-2">
                            <i data-lucide="package" class="w-4 h-4 text-white"></i>
                            <span class="text-sm font-medium text-white">Inventory</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-white/60 transition" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-6 mt-1 space-y-1">
                        @can('products.view')
                            <a href="{{ route('products.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('products.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="box" class="w-3.5 h-3.5 inline mr-2"></i>
                                Products
                            </a>
                        @endcan

                        @can('products.view')
                            <a href="{{ route('raw-materials.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('raw-materials.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="flask-conical" class="w-3.5 h-3.5 inline mr-2"></i>
                                Raw Materials
                            </a>
                        @endcan

                        @can('products.view')
                            <a href="{{ route('productions.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('productions.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="factory" class="w-3.5 h-3.5 inline mr-2"></i>
                                Production
                            </a>
                        @endcan

                        @can('stock.view')
                            <a href="{{ route('stock.history') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('stock.history*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="history" class="w-3.5 h-3.5 inline mr-2"></i>
                                Stock Movements
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- 🛒 Purchases & Suppliers --}}
            @canany(['purchases.view', 'suppliers.view'])
                <div x-data="{ open: {{ $purchasesActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-white/10 transition"
                            :class="open ? 'bg-white/10' : ''">
                        <span class="flex items-center gap-2">
                            <i data-lucide="shopping-bag" class="w-4 h-4 text-white"></i>
                            <span class="text-sm font-medium text-white">Purchases</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-white/60 transition" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-6 mt-1 space-y-1">
                        @can('purchases.view')
                            <a href="{{ route('purchases.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('purchases.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 inline mr-2"></i>
                                All Purchases
                            </a>
                        @endcan

                        @can('suppliers.view')
                            <a href="{{ route('suppliers.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('suppliers.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="truck" class="w-3.5 h-3.5 inline mr-2"></i>
                                Suppliers
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- 💵 Finance --}}
            @canany(['transactions.view', 'debits-credits.view', 'loans.view'])
                <div x-data="{ open: {{ $financeActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-white/10 transition"
                            :class="open ? 'bg-white/10' : ''">
                        <span class="flex items-center gap-2">
                            <i data-lucide="dollar-sign" class="w-4 h-4 text-white"></i>
                            <span class="text-sm font-medium text-white">Finance</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-white/60 transition" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-6 mt-1 space-y-1">
                        @can('transactions.view')
                            <a href="{{ route('expenses.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('expenses.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="wallet" class="w-3.5 h-3.5 inline mr-2"></i>
                                Expenses
                            </a>

                            <a href="{{ route('transactions.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('transactions.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="activity" class="w-3.5 h-3.5 inline mr-2"></i>
                                Transactions
                            </a>
                        @endcan

                        @can('debits-credits.view')
                            <a href="{{ route('debits-credits.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('debits-credits.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="scale" class="w-3.5 h-3.5 inline mr-2"></i>
                                Debits & Credits
                            </a>
                        @endcan

                        @can('loans.view')
                            <a href="{{ route('loans.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('loans.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="hand-coins" class="w-3.5 h-3.5 inline mr-2"></i>
                                Loans
                            </a>

                            <a href="{{ route('item-loans.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('item-loans.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="handshake" class="w-3.5 h-3.5 inline mr-2"></i>
                                Inter-Company
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- 📊 Reports --}}
            @can('reports.view')
                <div x-data="{ open: {{ $reportsActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-white/10 transition"
                            :class="open ? 'bg-white/10' : ''">
                        <span class="flex items-center gap-2">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 text-white"></i>
                            <span class="text-sm font-medium text-white">Reports</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-white/60 transition" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-6 mt-1 space-y-1">
                        @if(Route::has('reports.index'))
                            <a href="{{ route('reports.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('reports.index') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="file-bar-chart" class="w-3.5 h-3.5 inline mr-2"></i>
                                Overview
                            </a>
                        @endif

                        <a href="{{ route('reports.manufacturing') }}"
                           class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                  {{ request()->routeIs('reports.manufacturing') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                            <i data-lucide="factory" class="w-3.5 h-3.5 inline mr-2"></i>
                            Manufacturing
                        </a>

                        <a href="{{ route('reports.suppliers.statement') }}"
                           class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                  {{ request()->routeIs('reports.suppliers.statement') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                            <i data-lucide="truck" class="w-3.5 h-3.5 inline mr-2"></i>
                            Supplier Statement
                        </a>

                        <a href="{{ route('reports.customers.statement') }}"
                           class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                  {{ request()->routeIs('reports.customers.statement') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                            <i data-lucide="users" class="w-3.5 h-3.5 inline mr-2"></i>
                            Customer Statement
                        </a>
                    </div>
                </div>
            @endcan

            {{-- ⚙️ Settings --}}
            @canany(['users.view', 'roles.view', 'categories.view'])
                <div x-data="{ open: {{ $settingsActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-white/10 transition"
                            :class="open ? 'bg-white/10' : ''">
                        <span class="flex items-center gap-2">
                            <i data-lucide="settings" class="w-4 h-4 text-white"></i>
                            <span class="text-sm font-medium text-white">Settings</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-white/60 transition" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-6 mt-1 space-y-1">
                        @can('users.view')
                            <a href="{{ route('users.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('users.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="user" class="w-3.5 h-3.5 inline mr-2"></i>
                                Users
                            </a>
                        @endcan

                        @can('roles.view')
                            <a href="{{ route('roles.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('roles.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="shield" class="w-3.5 h-3.5 inline mr-2"></i>
                                Roles
                            </a>
                        @endcan

                        @can('categories.view')
                            <a href="{{ route('categories.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('categories.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="folder" class="w-3.5 h-3.5 inline mr-2"></i>
                                Categories
                            </a>
                        @endcan

                        {{-- Payment Channels --}}
                        @can('payment-channels.view')
                            <a href="{{ route('payment-channels.index') }}"
                               class="block px-3 py-2 text-sm rounded-md hover:bg-white/10
                                      {{ request()->routeIs('payment-channels.*') ? 'bg-white/20 font-medium text-white' : 'text-white/80' }}">
                                <i data-lucide="credit-card" class="w-3.5 h-3.5 inline mr-2"></i>
                                Payment Channels
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- Separator --}}
            <div class="border-t border-white/20 my-4"></div>

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full text-left flex items-center px-3 py-2 text-sm text-white/80 hover:bg-red-500/20 hover:text-red-200 rounded-md transition-all">
                    <i data-lucide="log-out" class="w-4 h-4 mr-2"></i>
                    Logout
                </button>
            </form>

            {{-- User Info --}}
            <div class="px-3 py-3 mt-3 bg-white/10 rounded-lg border border-white/20">
                <p class="text-xs text-white/60">Logged in as</p>
                <p class="font-medium text-white text-sm mt-0.5">
                    {{ $user->name }}
                </p>
                <span class="inline-flex items-center px-2 py-0.5 mt-1.5 rounded text-xs font-medium bg-white/20 text-white">
                    {{ ucfirst($role) }}
                </span>
            </div>

            {{-- Version/Footer --}}
            <p class="text-xs text-center text-white/40 mt-4 pb-4">
                v1.0.0 &bull; Powered by BarakSoftwares
            </p>
        </nav>
    </div>
</aside>

{{-- Custom Scrollbar Styles --}}
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(255, 255, 255, 0.5);
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
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
