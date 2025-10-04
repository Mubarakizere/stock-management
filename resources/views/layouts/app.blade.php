<!DOCTYPE html>
<html lang="en" x-data="layout()" x-bind:class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Stock Manager') }}</title>
    @vite('resources/css/app.css')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100">

    <div class="flex h-screen overflow-hidden">
        <!-- Overlay -->
        <div x-show="sidebarOpen" class="fixed inset-0 bg-black bg-opacity-40 z-20 lg:hidden"
             @click="sidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside
            class="fixed z-30 inset-y-0 left-0 w-64 bg-white dark:bg-gray-800 shadow-lg transform transition-transform duration-200
                   lg:translate-x-0 lg:static lg:inset-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

            <div class="flex items-center justify-between px-4 py-4 border-b dark:border-gray-700">
                <h1 class="text-xl font-bold text-indigo-600 dark:text-indigo-400">Stock Manager</h1>
                <button class="lg:hidden text-gray-600 dark:text-gray-400" @click="sidebarOpen = false">&times;</button>
            </div>

            <nav class="px-4 py-4 space-y-2 text-sm">
                <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                    🏠 Dashboard
                </x-nav-link>

                @if(in_array(auth()->user()->role, ['admin','manager']))
                    <p class="mt-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Stock</p>
                    <x-nav-link href="{{ route('categories.index') }}" :active="request()->routeIs('categories.*')">📦 Categories</x-nav-link>
                    <x-nav-link href="{{ route('products.index') }}" :active="request()->routeIs('products.*')">🧺 Products</x-nav-link>
                    <x-nav-link href="{{ route('suppliers.index') }}" :active="request()->routeIs('suppliers.*')">🚚 Suppliers</x-nav-link>
                @endif

                @if(in_array(auth()->user()->role, ['admin','manager','cashier']))
                    <p class="mt-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sales</p>
                    <x-nav-link href="{{ route('customers.index') }}" :active="request()->routeIs('customers.*')">👥 Customers</x-nav-link>
                    <x-nav-link href="{{ route('sales.index') }}" :active="request()->routeIs('sales.*')">💰 Sales</x-nav-link>
                @endif

                @if(in_array(auth()->user()->role, ['admin','manager']))
                    <x-nav-link href="{{ route('purchases.index') }}" :active="request()->routeIs('purchases.*')">🛒 Purchases</x-nav-link>
                    <p class="mt-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Loans</p>
                    <x-nav-link href="{{ route('loans.index') }}" :active="request()->routeIs('loans.*')">🏦 Loans</x-nav-link>
                @endif

                @if(in_array(auth()->user()->role, ['admin','manager','cashier']))
                    <p class="mt-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Finance</p>
                    <x-nav-link href="{{ route('debits-credits.index') }}" :active="request()->routeIs('debits-credits.*')">💳 Debits & Credits</x-nav-link>
                @endif

                @if(in_array(auth()->user()->role, ['admin','manager']))
                    <x-nav-link href="{{ route('transactions.index') }}" :active="request()->routeIs('transactions.*')">📑 Transactions</x-nav-link>
                @endif
            </nav>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-h-screen lg:ml-64 transition-all duration-200">

            <!-- Topbar -->
            <header class="bg-white dark:bg-gray-800 shadow flex justify-between items-center px-6 py-3 sticky top-0 z-10">
                <div class="flex items-center space-x-3">
                    <button class="text-gray-600 dark:text-gray-300 lg:hidden" @click="sidebarOpen = true">☰</button>
                    <h2 class="font-semibold text-lg text-indigo-600 dark:text-indigo-400">
                        @yield('page-title', 'Dashboard')
                    </h2>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button @click="toggleDarkMode"
                            class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                        <template x-if="!darkMode">🌙</template>
                        <template x-if="darkMode">☀️</template>
                    </button>

                    <!-- User Info -->
                    <span class="text-sm text-gray-700 dark:text-gray-200">
                        👤 {{ auth()->user()->name }}
                        <span class="text-gray-400">({{ ucfirst(auth()->user()->role) }})</span>
                    </span>

                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm">
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <!-- Breadcrumb -->
            <nav class="bg-gray-50 dark:bg-gray-900 border-b dark:border-gray-700 px-6 py-2 text-sm text-gray-500 dark:text-gray-400">
                <ol class="flex space-x-2">
                    <li><a href="{{ route('dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Dashboard</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>

            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                @if(session('success'))
                    <div class="mb-4 p-4 rounded bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Alpine.js layout logic -->
    <script>
        function layout() {
            return {
                sidebarOpen: false,
                darkMode: localStorage.getItem('darkMode') === 'true',
                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('darkMode', this.darkMode);
                }
            }
        }
    </script>
</body>
</html>
