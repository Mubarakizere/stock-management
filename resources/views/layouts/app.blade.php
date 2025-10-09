<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Stock Manager') }}</title>

    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js'])
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-gray-50 font-sans antialiased"
      x-data="{ sidebarOpen: window.innerWidth >= 1024 }"
      @resize.window="sidebarOpen = window.innerWidth >= 1024">

<!-- 🔔 Offline Banner -->
<div id="connection-status"
     class="hidden fixed top-0 left-0 right-0 z-[9999] bg-red-600 text-white text-center py-2 text-sm font-semibold shadow-md transition-all duration-300">
    ⚠️ You’re offline. Some actions will be saved and synced later.
</div>

<div class="flex h-screen overflow-hidden">

    <!-- Overlay (mobile) -->
    <div class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden"
         x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 shadow-sm
                 transform lg:translate-x-0 lg:static lg:inset-0"
           x-show="sidebarOpen"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           @click.away="sidebarOpen = false">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-100">
            <h1 class="text-lg font-bold text-indigo-600 tracking-tight">Stock Manager</h1>
            <button class="lg:hidden text-gray-500 hover:text-gray-700" @click="sidebarOpen = false">✕</button>
        </div>

        <!-- Navigation -->
<nav class="mt-3 space-y-1 px-3">
    @php $user = Auth::user(); @endphp

    {{-- 🔹 Dashboard (visible to all roles) --}}
    <x-sidebar-link
        href="{{ route('dashboard') }}"
        :active="request()->routeIs('dashboard')"
        class="{{ request()->routeIs('dashboard') ? 'sidebar-link-active' : 'sidebar-link' }}">
        Dashboard
    </x-sidebar-link>

    {{-- 🔹 Admin Section --}}
    @if($user->hasRoleName('admin'))
        <x-sidebar-link href="{{ route('users.index') }}" :active="request()->routeIs('users.*')" class="{{ request()->routeIs('users.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Users
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('categories.index') }}" :active="request()->routeIs('categories.*')" class="{{ request()->routeIs('categories.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Categories
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('products.index') }}" :active="request()->routeIs('products.*')" class="{{ request()->routeIs('products.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Products
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('suppliers.index') }}" :active="request()->routeIs('suppliers.*')" class="{{ request()->routeIs('suppliers.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Suppliers
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('purchases.index') }}" :active="request()->routeIs('purchases.*')" class="{{ request()->routeIs('purchases.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Purchases
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('sales.index') }}" :active="request()->routeIs('sales.*')" class="{{ request()->routeIs('sales.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Sales
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('transactions.index') }}" :active="request()->routeIs('transactions.*')"
               class="{{ request()->routeIs('transactions.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Transactions
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('customers.index') }}" :active="request()->routeIs('customers.*')"
                       class="{{ request()->routeIs('customers.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Customers
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('loans.index') }}" :active="request()->routeIs('loans.*')" class="{{ request()->routeIs('loans.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Loans
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('debits-credits.index') }}"
    :active="request()->routeIs('debits-credits.*')"
    class="{{ request()->routeIs('debits-credits.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
    Debits & Credits
</x-sidebar-link>

        <x-sidebar-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')" class="{{ request()->routeIs('reports.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Reports
        </x-sidebar-link>
    @endif

    {{-- 🔹 Manager Section --}}
    @if($user->hasRoleName('manager'))
        <x-sidebar-link href="{{ route('sales.index') }}" :active="request()->routeIs('sales.*')" class="{{ request()->routeIs('sales.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Sales
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('products.index') }}" :active="request()->routeIs('products.*')" class="{{ request()->routeIs('products.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Products
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('suppliers.index') }}" :active="request()->routeIs('suppliers.*')" class="{{ request()->routeIs('suppliers.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Suppliers
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')" class="{{ request()->routeIs('reports.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Reports
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('transactions.index') }}" :active="request()->routeIs('transactions.*')"
  class="{{ request()->routeIs('transactions.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
  Transactions
</x-sidebar-link>
<x-sidebar-link href="{{ route('customers.index') }}" :active="request()->routeIs('customers.*')"
  class="{{ request()->routeIs('customers.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
  Customers
</x-sidebar-link>


    @endif

    {{-- 🔹 Cashier Section --}}
    @if($user->hasRoleName('cashier'))
        <x-sidebar-link href="{{ route('sales.index') }}" :active="request()->routeIs('sales.*')" class="{{ request()->routeIs('sales.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Sales
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('customers.index') }}" :active="request()->routeIs('customers.*')" class="{{ request()->routeIs('customers.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Customers
        </x-sidebar-link>
    @endif

    {{-- 🔹 Accountant Section --}}
    @if($user->hasRoleName('accountant'))
       <x-sidebar-link href="{{ route('debits-credits.index') }}"
    :active="request()->routeIs('debits-credits.*')"
    class="{{ request()->routeIs('debits-credits.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
    Debits & Credits
</x-sidebar-link>

        <x-sidebar-link href="{{ route('loans.index') }}" :active="request()->routeIs('loans.*')" class="{{ request()->routeIs('loans.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Loans
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')" class="{{ request()->routeIs('reports.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
            Reports
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('transactions.index') }}" :active="request()->routeIs('transactions.*')"
  class="{{ request()->routeIs('transactions.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
  Transactions
</x-sidebar-link>

    @endif

    {{-- Divider & Logout --}}
    <div class="border-t border-gray-100 my-4"></div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="w-full text-left flex items-center px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-md transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 text-gray-400" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
            </svg>
            Logout
        </button>
    </form>

    <p class="text-xs text-gray-400 mt-4 px-3 pb-4">
        Logged in as <span class="font-medium text-gray-700">{{ ucfirst($user->roleNames()[0] ?? 'User') }}</span>
    </p>
</nav>

    </aside>

    <!-- Main Section -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <header class="flex items-center justify-between bg-white border-b border-gray-200 shadow-sm px-4 py-3 lg:px-6">
            <div class="flex items-center space-x-3">
                <button class="text-gray-600 hover:text-indigo-600 lg:hidden" @click="sidebarOpen = true">☰</button>
                <h2 class="text-lg font-semibold text-gray-700 tracking-tight">@yield('title', 'Dashboard')</h2>
            </div>
            <div class="flex items-center space-x-3">
                <!-- 🌐 Connection Indicator -->
                <div id="connection-indicator"
                     class="flex items-center gap-1 text-sm font-medium text-gray-500 transition-all">
                    <svg id="wifi-icon" xmlns="http://www.w3.org/2000/svg"
                         class="w-4 h-4 text-green-500 transition-all"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M5 13a10 10 0 0114 0M8.5 16.5a5 5 0 017 0M12 20h.01" />
                    </svg>
                    <span id="connection-text" class="text-green-600">Online</span>
                </div>
                <span class="text-gray-600 text-sm font-medium">{{ Auth::user()->name ?? '' }}</span>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-6">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>

<!-- ✅ Lucide Icons -->
<script type="module">
    import { createIcons, icons } from "https://cdn.jsdelivr.net/npm/lucide@latest/+esm";
    document.addEventListener("DOMContentLoaded", () => createIcons({ icons }));
</script>

@auth
<script>window.App = { userId: {{ Auth::id() }} };</script>
@endauth

<script>
// ✅ Register Service Worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const reg = await navigator.serviceWorker.register('/sw.js');
      console.log('✅ Service Worker registered', reg);
    } catch (err) {
      console.error('❌ SW registration failed:', err);
    }
  });
}

// ✅ Background Sync + Offline UI
window.addEventListener('online', async () => {
  updateConnectionStatus(true);
  const reg = await navigator.serviceWorker.ready;
  if ('SyncManager' in window) {
    await reg.sync.register('sync-sales');
    window.dispatchEvent(new CustomEvent('toast', {
      detail: { message: '📡 Sync scheduled in background...', type: 'success' }
    }));
  } else {
    fallbackSync();
  }
});

window.addEventListener('offline', () => updateConnectionStatus(false));

async function fallbackSync() {
  if (!('indexedDB' in window)) return;
  const db = await openDB('StockManagerDB', 1);
  const tx = db.transaction('offline_sales', 'readonly');
  const store = tx.objectStore('offline_sales');
  const allReq = store.getAll();

  allReq.onsuccess = async () => {
    const records = allReq.result || [];
    if (!records.length) return;
    try {
      await fetch('/api/sync/offline-sales', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sales: records })
      });
      const tx2 = db.transaction('offline_sales', 'readwrite');
      tx2.objectStore('offline_sales').clear();
      window.dispatchEvent(new CustomEvent('toast', {
        detail: { message: '✅ Offline sales synced successfully!', type: 'success' }
      }));
    } catch {
      window.dispatchEvent(new CustomEvent('toast', {
        detail: { message: '⚠️ Sync failed — will retry later.', type: 'error' }
      }));
    }
  };
}

function openDB(name, version) {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(name, version);
    req.onsuccess = () => resolve(req.result);
    req.onerror = reject;
    req.onupgradeneeded = (e) => {
      const db = e.target.result;
      if (!db.objectStoreNames.contains('offline_sales')) {
        db.createObjectStore('offline_sales', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

// 🌐 Connection Indicator + Banner
function updateConnectionStatus(isOnline) {
  const icon = document.getElementById('wifi-icon');
  const text = document.getElementById('connection-text');
  if (isOnline) {
    icon.classList.replace('text-red-500', 'text-green-500');
    text.textContent = 'Online';
    text.classList.replace('text-red-600', 'text-green-600');
  } else {
    icon.classList.replace('text-green-500', 'text-red-500');
    text.textContent = 'Offline';
    text.classList.replace('text-green-600', 'text-red-600');
  }
}
</script>

<!-- ✅ Toast Notifications -->
<div
    x-data="{ visible: false, message: '', type: 'success' }"
    x-show="visible"
    x-transition
    x-cloak
    x-bind:class="type === 'success' ? 'bg-green-600' : 'bg-red-600'"
    class="fixed top-4 right-4 z-[99999] text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-lg"
    x-text="message"
    @toast.window="
        message = $event.detail.message;
        type = $event.detail.type || 'success';
        visible = true;
        setTimeout(() => visible = false, 3000);
    ">
</div>

<style>
@keyframes pulseSlow { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .5; transform: scale(0.9); } }
.animate-pulse-slow { animation: pulseSlow 1.5s infinite ease-in-out; }
[x-cloak] { display: none !important; }
</style>

@stack('scripts')
@vite(['resources/js/idle-logout.js'])
</body>
</html>
