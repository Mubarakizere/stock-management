<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      x-init="
          if (localStorage.theme === 'dark') document.documentElement.classList.add('dark');
          else document.documentElement.classList.remove('dark');
          $watch(() => localStorage.theme, v => document.documentElement.classList.toggle('dark', v === 'dark'));
      ">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Stock Manager'))</title>

    @vite(['resources/css/app.css','resources/css/theme.css','resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4F46E5">

    {{-- ✅ PWA Essentials --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/icons/icon-192x192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Stock Management">
    <link rel="apple-touch-startup-image" href="{{ asset('images/icons/icon-512x512.png') }}">

    {{-- Prevent white flash + hide Google banner --}}
    <style>
        [x-cloak]{display:none!important}
        html.dark body{background:#0f172a;color:#f8fafc}

        /* ===== Google Translate cleanup ===== */
        .goog-te-banner-frame,
        .goog-te-banner-frame.skiptranslate,
        #goog-gt-tt,
        .goog-te-balloon-frame,
        .VIpgJd-ZVi9od-ORHb-OEVmcd { display: none !important; }

        /* Google injects top offset on html/body; force reset */
        html, body { top: 0 !important; }

        /* Hide default gadget chrome (we use our own dropdown) */
        .goog-te-gadget { color: transparent !important; }
        .goog-te-gadget .goog-te-combo { margin:0 !important; }
        .goog-logo-link, .goog-te-gadget span { display:none !important; }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans antialiased transition-colors duration-300"
      x-data="{ sidebarOpen: window.innerWidth >= 1024 }"
      @resize.window="sidebarOpen = window.innerWidth >= 1024">
    <x-loader />

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
        <header class="flex items-center justify-between bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-200/80 dark:border-gray-800 px-4 lg:px-7 py-2.5 sticky top-0 z-30 transition-colors">
            {{-- Left: Breadcrumb / Mobile Toggle --}}
            <div class="flex items-center gap-3">
                <button class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 rounded-lg lg:hidden transition"
                        @click="sidebarOpen = true"
                        aria-label="Open sidebar">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>

                <nav class="flex items-center gap-2 text-sm font-medium">
                    <span class="text-gray-400 dark:text-gray-500 font-normal">Nezerwa Plus</span>
                    <span class="text-gray-300 dark:text-gray-700">/</span>
                    <span class="font-semibold text-gray-900 dark:text-white">@yield('title', 'Dashboard')</span>
                </nav>
            </div>

            {{-- Center: Quick Search Bar --}}
            <div class="hidden md:flex items-center">
                @can('products.view')
                    <a href="{{ route('products.index') }}"
                       class="flex items-center gap-2.5 px-3 py-1.5 text-xs text-gray-400 dark:text-gray-500 bg-gray-100/80 dark:bg-gray-800/60 hover:bg-gray-200/70 dark:hover:bg-gray-700/50 rounded-xl border border-gray-200/70 dark:border-gray-700/60 transition-all w-64 shadow-xs group">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors"></i>
                        <span class="truncate">Search products or stock...</span>
                        <kbd class="ml-auto text-[10px] font-mono px-1.5 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-400">Ctrl+K</kbd>
                    </a>
                @endcan
            </div>

            {{-- Right: Actions & Profile --}}
            <div class="flex items-center gap-2.5 sm:gap-3">
                {{-- 🌐 Live Connection Status --}}
                <div id="connection-indicator"
                     class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span id="connection-text" class="text-[11px] font-semibold tracking-wide uppercase">Live</span>
                </div>

                {{-- 🌙 Dark Mode Toggle --}}
                <button
                    @click="
                        const isDark = document.documentElement.classList.toggle('dark');
                        localStorage.theme = isDark ? 'dark' : 'light';
                    "
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 transition"
                    title="Toggle theme">
                    <i data-lucide="moon" class="w-4 h-4 dark:hidden"></i>
                    <i data-lucide="sun" class="w-4 h-4 hidden dark:block text-amber-400"></i>
                </button>

                {{-- 🌍 Language Switcher --}}
                <div class="relative" x-data="{ open:false }" @keydown.escape="open=false" @click.away="open=false">
                    <button @click="open=!open"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition border border-gray-200/70 dark:border-gray-700/70"
                            title="Change language">
                        <i data-lucide="globe" class="w-3.5 h-3.5 text-gray-400"></i>
                        <span id="lang-badge" class="font-semibold uppercase tracking-wider text-[11px]">EN</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-gray-400"></i>
                    </button>

                    <div x-show="open" x-cloak x-transition
                         class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg py-1 z-50 divide-y divide-gray-100 dark:divide-gray-700/60">
                        <div class="py-1">
                            <button type="button" class="w-full text-left px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between"
                                    @click="window.__setLanguage('en'); open=false">
                                <span>English</span>
                                <span class="text-[10px] text-gray-400 uppercase">EN</span>
                            </button>
                            <button type="button" class="w-full text-left px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between"
                                    @click="window.__setLanguage('fr'); open=false">
                                <span>Français</span>
                                <span class="text-[10px] text-gray-400 uppercase">FR</span>
                            </button>
                            <button type="button" class="w-full text-left px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between"
                                    @click="window.__setLanguage('rw'); open=false">
                                <span>Kinyarwanda</span>
                                <span class="text-[10px] text-gray-400 uppercase">RW</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="h-5 w-px bg-gray-200 dark:bg-gray-700/80"></div>

                {{-- 👤 User Dropdown --}}
                <div class="relative" x-data="{ userMenu: false }" @click.away="userMenu = false">
                    <button @click="userMenu = !userMenu"
                            class="flex items-center gap-2.5 p-1 sm:px-2.5 sm:py-1 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition text-left">
                        @php
                            $user = Auth::user();
                            $photo = $user?->photo ? asset('storage/' . $user->photo) : null;
                            $userRole = $user?->getRoleNames()->first() ?? 'User';
                        @endphp

                        @if($photo)
                            <img src="{{ $photo }}" alt="{{ $user->name }}"
                                 class="w-7 h-7 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                        @else
                            <div class="w-7 h-7 rounded-full bg-teal-500/10 dark:bg-teal-500/20 text-teal-600 dark:text-teal-400 ring-1 ring-teal-500/30 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif

                        <div class="hidden sm:block">
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 leading-tight truncate max-w-[110px]">
                                {{ $user->name ?? 'User' }}
                            </p>
                            <span class="inline-flex items-center text-[10px] font-medium text-teal-600 dark:text-teal-400 uppercase tracking-wider">
                                {{ ucfirst($userRole) }}
                            </span>
                        </div>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400 hidden sm:block"></i>
                    </button>

                    <div x-show="userMenu" x-cloak x-transition
                         class="absolute right-0 mt-2 w-52 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg py-1 z-50">
                        <div class="px-3.5 py-2.5 border-b border-gray-100 dark:border-gray-700/60">
                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $user->name ?? 'User' }}</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $user->email ?? '' }}</p>
                            <span class="mt-1.5 inline-block text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">
                                {{ ucfirst($userRole) }} Role
                            </span>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}"
                               class="flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i>
                                <span>Account Settings</span>
                            </a>
                        </div>

                        <div class="border-t border-gray-100 dark:border-gray-700/60 py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
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

{{-- ✅ Toast Alerts --}}
@if(session('success') || session('error') || session('warning') || session('info'))
    <x-alert-toast />
@endif

{{-- ✅ Lucide Icons --}}
<script type="module">
import { createIcons, icons } from "https://cdn.jsdelivr.net/npm/lucide@0.454.0/+esm";
document.addEventListener("DOMContentLoaded", () => createIcons({ icons }));
</script>

<x-confirm-delete />
@stack('scripts')

{{-- ===== Hidden Google Translate mount point ===== --}}
<div id="google_translate_element" class="absolute -z-50 opacity-0 pointer-events-none"></div>

{{-- ✅ Google Translate: init + helpers --}}
<script>
/**
 * Initialize Google Translate with the three languages we want.
 * pageLanguage uses the current Laravel locale to hint the source.
 */
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: "{{ str_replace('_','-', app()->getLocale()) ?: 'en' }}",
        includedLanguages: 'en,fr,rw',
        autoDisplay: false
    }, 'google_translate_element');

    // Restore saved language after widget injects the <select>
    const saved = localStorage.getItem('gt_lang') || 'en';
    setTimeout(() => window.__setLanguage(saved, /*silent*/ true), 400);
}

/**
 * Programmatically change Google Translate language and persist to localStorage.
 * Also update the top-nav badge.
 */
window.__setLanguage = function(lang, silent = false) {
    try {
        const combo = document.querySelector('#google_translate_element select.goog-te-combo');
        if (!combo) {
            return setTimeout(() => window.__setLanguage(lang, silent), 200);
        }
        if (combo.value !== lang) {
            combo.value = lang;
            combo.dispatchEvent(new Event('change'));
        }
        localStorage.setItem('gt_lang', lang);
        const badge = document.getElementById('lang-badge');
        if (badge) badge.textContent = (lang || 'en').toUpperCase();
        if (!silent && window.createIcons) { /* no-op */ }
    } catch(e) { console.error('Translate switch failed', e); }
};
</script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

{{-- 🚫 Remove/guard the blue top banner reliably --}}
<script>
(function () {
  function killBar() {
    const banner = document.querySelector('.goog-te-banner-frame');
    if (banner) banner.style.display = 'none';
    const balloon = document.querySelector('.VIpgJd-ZVi9od-ORHb-OEVmcd');
    if (balloon) balloon.style.display = 'none';
    document.documentElement.style.top = '0px';
    document.body.style.top = '0px';
  }

  // Run now, on load, on resize and whenever DOM mutates (Google injects later)
  killBar();
  window.addEventListener('load', killBar, { passive: true });
  window.addEventListener('resize', killBar, { passive: true });
  new MutationObserver(killBar).observe(document.documentElement, { childList: true, subtree: true });

  // Also run right after we switch languages via the custom dropdown
  const prev = window.__setLanguage;
  window.__setLanguage = function(lang, silent) {
    if (typeof prev === 'function') prev(lang, silent);
    setTimeout(killBar, 120);
  };
})();
</script>

{{-- ✅ PWA Service Worker --}}
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(reg => console.log('✅ Service Worker registered:', reg.scope))
            .catch(err => console.error('❌ Service Worker registration failed:', err));
    });
}
</script>

{{-- 🍎 iPhone Install Tip (shows only once) --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    const hasSeenTip = localStorage.getItem('pwa_tip_seen');

    if (isIOS && !isStandalone && !hasSeenTip) {
        const banner = document.createElement('div');
        banner.innerHTML = `
            <div class="fixed bottom-5 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2">
                <i data-lucide='download' class='w-4 h-4'></i>
                <span>Install this app: Tap <strong>Share</strong> → <strong>Add to Home Screen</strong></span>
                <button id='closeTip' class='ml-3 text-white/70 hover:text-white text-xs'>×</button>
            </div>
        `;
        document.body.appendChild(banner);
        document.getElementById('closeTip').addEventListener('click', () => {
            banner.remove();
            localStorage.setItem('pwa_tip_seen', 'true');
        });
        if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
    }
});
</script>

<x-command-palette />
</body>
</html>
