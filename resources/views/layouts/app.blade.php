<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      x-init="
          // ✅ Dark mode initialization
          if (localStorage.theme === 'dark') {
              document.documentElement.classList.add('dark');
          } else {
              document.documentElement.classList.remove('dark');
          }
          $watch(() => localStorage.theme, (v) => {
              document.documentElement.classList.toggle('dark', v === 'dark');
          });
      ">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Stock Manager'))</title>

    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">

    <!-- Prevent white flash -->
    <style>[x-cloak]{display:none!important}html.dark body{background:#0f172a;color:#f8fafc}</style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans antialiased transition-colors duration-300"
      x-data="{ sidebarOpen: window.innerWidth >= 1024, userMenu:false }"
      @resize.window="sidebarOpen = window.innerWidth >= 1024">

{{-- 🔔 Offline Banner --}}
<div id="connection-status"
     class="hidden fixed top-0 inset-x-0 z-[9999] bg-red-600 text-white text-center py-2 text-sm font-semibold shadow-md">
    ⚠️ You’re offline. Some actions will be synced later.
</div>

<div class="flex h-screen overflow-hidden">

    {{-- Overlay (mobile) --}}
    <div class="fixed inset-0 z-30 bg-black/50 lg:hidden"
         x-show="sidebarOpen" x-transition.opacity
         @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- ====== MAIN SECTION ====== --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- ====== HEADER ====== --}}
        <header class="flex items-center justify-between bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm px-4 lg:px-6 py-3">
            {{-- Left: page title + sidebar toggle --}}
            <div class="flex items-center gap-3">
                <button class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 lg:hidden" @click="sidebarOpen = true">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100 tracking-tight">
                    @yield('title', 'Dashboard')
                </h1>
            </div>

            {{-- Right: actions --}}
            <div class="flex items-center gap-4">

                {{-- 🌙 Dark Mode Toggle --}}
                <button
                    @click="
                        if (document.documentElement.classList.contains('dark')) {
                            document.documentElement.classList.remove('dark');
                            localStorage.theme = 'light';
                        } else {
                            document.documentElement.classList.add('dark');
                            localStorage.theme = 'dark';
                        }"
                    class="w-9 h-9 flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    title="Toggle dark mode">
                    <i x-show="!document.documentElement.classList.contains('dark')" data-lucide="moon"
                       class="w-4 h-4 text-gray-600"></i>
                    <i x-show="document.documentElement.classList.contains('dark')" data-lucide="sun"
                       class="w-4 h-4 text-yellow-400"></i>
                </button>

                {{-- 🌐 Connection Indicator --}}
                <div id="connection-indicator" class="hidden sm:flex items-center gap-1 text-sm font-medium">
                    <svg id="wifi-icon" xmlns="http://www.w3.org/2000/svg"
                         class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M5 13a10 10 0 0114 0M8.5 16.5a5 5 0 017 0M12 20h.01" />
                    </svg>
                    <span id="connection-text" class="text-green-600 dark:text-green-400">Online</span>
                </div>

                {{-- 👤 User Dropdown --}}
                <div class="relative" @click.away="userMenu=false">
                    <button @click="userMenu = !userMenu"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                            <span class="text-indigo-700 dark:text-indigo-300 text-sm font-semibold">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </span>
                        </div>
                        <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ Auth::user()->name ?? 'User' }}
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                    </button>

                    {{-- Dropdown menu --}}
                    <div x-show="userMenu" x-cloak x-transition
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg py-1 z-50">
                        <a href="{{ route('profile.edit') }}"
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i data-lucide="user" class="w-4 h-4 inline mr-2"></i> Profile
                        </a>
                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700">
                                <i data-lucide="log-out" class="w-4 h-4 inline mr-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- ====== MAIN CONTENT ====== --}}
        <main class="flex-1 overflow-y-auto p-4 lg:p-6 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>

{{-- 🔹 Lucide Icons --}}
<script type="module">
import { createIcons, icons } from "https://cdn.jsdelivr.net/npm/lucide@0.454.0/+esm";
document.addEventListener("DOMContentLoaded", () => createIcons({ icons }));
</script>

{{-- 🔹 Alpine Confirm Modal + Extra Scripts --}}
<x-confirm-delete />
@stack('scripts')
</body>
</html>
