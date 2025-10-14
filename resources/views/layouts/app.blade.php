<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      x-init="
          // ✅ Initialize dark mode based on localStorage
          if (localStorage.theme === 'dark') {
              document.documentElement.classList.add('dark');
          } else {
              document.documentElement.classList.remove('dark');
          }

          // ✅ Watch for changes and reapply
          $watch(() => localStorage.theme, (value) => {
              if (value === 'dark') document.documentElement.classList.add('dark');
              else document.documentElement.classList.remove('dark');
          });
      ">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Stock Manager') }}</title>

    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js'])

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- 🧠 Prevent white flash when dark mode is on -->
    <style>
        html.dark body {
            background-color: #0f172a;
            color: #f1f5f9;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 font-sans antialiased transition-colors duration-300"
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
    @include('layouts.sidebar')

    <!-- Main Section -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <header class="flex items-center justify-between bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm px-4 py-3 lg:px-6 transition-colors duration-300">
            <div class="flex items-center space-x-3">
                <button class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 lg:hidden" @click="sidebarOpen = true">☰</button>
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200 tracking-tight">@yield('title', 'Dashboard')</h2>
            </div>

            <div class="flex items-center space-x-4">

                <!-- 🌙 / ☀️ Dark Mode Toggle -->
                <button
                    @click="
                        if (document.documentElement.classList.contains('dark')) {
                            document.documentElement.classList.remove('dark');
                            localStorage.theme = 'light';
                        } else {
                            document.documentElement.classList.add('dark');
                            localStorage.theme = 'dark';
                        }
                    "
                    class="w-8 h-8 rounded-full border border-gray-300 dark:border-gray-600 flex items-center justify-center
                           hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    title="Toggle dark mode">

                    <template x-if="!document.documentElement.classList.contains('dark')">
                        <i data-lucide='moon' class='w-4 h-4 text-gray-600'></i>
                    </template>
                    <template x-if="document.documentElement.classList.contains('dark')">
                        <i data-lucide='sun' class='w-4 h-4 text-yellow-400'></i>
                    </template>
                </button>

                <!-- 🌐 Connection Indicator -->
                <div id="connection-indicator"
                     class="flex items-center gap-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                    <svg id="wifi-icon" xmlns="http://www.w3.org/2000/svg"
                         class="w-4 h-4 text-green-500" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M5 13a10 10 0 0114 0M8.5 16.5a5 5 0 017 0M12 20h.01" />
                    </svg>
                    <span id="connection-text" class="text-green-600">Online</span>
                </div>

                <span class="text-gray-600 dark:text-gray-300 text-sm font-medium">
                    {{ Auth::user()->name ?? '' }}
                </span>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-6">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>
