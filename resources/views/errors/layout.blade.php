<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          theme: localStorage.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
          copied: false,
          showDiagnostics: false,
          copyIncident(text) {
              navigator.clipboard.writeText(text).then(() => {
                  this.copied = true;
                  setTimeout(() => this.copied = false, 2500);
              });
          }
      }"
      x-init="
          if (theme === 'dark') document.documentElement.classList.add('dark');
          else document.documentElement.classList.remove('dark');
          $watch('theme', val => {
              localStorage.theme = val;
              document.documentElement.classList.toggle('dark', val === 'dark');
          });
      ">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Service Incident') · {{ config('app.name', 'Nezerwa Plus') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        code, kbd, samp, pre, .font-mono { font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        html.dark body { background-color: #0b0f19; color: #f1f5f9; }
    </style>
</head>

<body class="min-h-screen bg-slate-50 dark:bg-[#0b0f19] text-slate-800 dark:text-slate-100 flex flex-col antialiased transition-colors duration-200">

    {{-- Background subtle architectural grid --}}
    <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] opacity-70"></div>

    {{-- Top App Bar --}}
    <header class="relative z-10 w-full border-b border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            {{-- Brand Mark --}}
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                <span class="w-9 h-9 rounded-xl bg-teal-600 dark:bg-teal-500 text-white flex items-center justify-center font-bold text-base shadow-sm shadow-teal-500/20 group-hover:scale-105 transition-transform">
                    <i data-lucide="boxes" class="w-5 h-5"></i>
                </span>
                <div class="flex flex-col">
                    <span class="font-bold text-sm tracking-tight text-slate-900 dark:text-slate-100 leading-none">
                        {{ config('app.name', 'Nezerwa Plus') }}
                    </span>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium tracking-wide uppercase mt-0.5">
                        Inventory & ERP
                    </span>
                </div>
            </a>

            {{-- Right Controls --}}
            <div class="flex items-center gap-3">
                {{-- System Status Pill --}}
                <div class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/60">
                    <span class="w-2 h-2 rounded-full @yield('status-dot', 'bg-amber-500') animate-pulse"></span>
                    <span>@yield('status-text', 'Incident Reported')</span>
                </div>

                {{-- Dark Mode Toggle --}}
                <button type="button"
                        @click="theme = theme === 'dark' ? 'light' : 'dark'"
                        class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 flex items-center justify-center transition shadow-sm"
                        title="Toggle theme">
                    <template x-if="theme === 'dark'">
                        <i data-lucide="sun" class="w-4 h-4"></i>
                    </template>
                    <template x-if="theme !== 'dark'">
                        <i data-lucide="moon" class="w-4 h-4"></i>
                    </template>
                </button>

                {{-- User indicator or Sign in --}}
                @if (auth()->check())
                    <div class="hidden md:flex items-center gap-2 pl-2 border-l border-slate-200 dark:border-slate-800">
                        <span class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xs font-semibold">
                            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                        </span>
                        <div class="text-left text-xs leading-tight">
                            <span class="font-medium text-slate-700 dark:text-slate-200 block truncate max-w-[120px]">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] text-slate-400 capitalize">{{ auth()->user()->roles->first()?->name ?? 'User' }}</span>
                        </div>
                    </div>
                @else
                    <a href="{{ url('/login') }}" class="btn btn-outline text-xs px-3 py-1.5 rounded-lg hidden sm:inline-flex">
                        Sign In
                    </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="relative z-10 flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="max-w-2xl w-full">

            {{-- Primary Error Card --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/40 overflow-hidden transition-all">

                {{-- Status Bar Header --}}
                <div class="px-6 py-3.5 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/70 dark:bg-slate-900/50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-mono font-semibold @yield('badge-class', 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700')">
                            @yield('code-badge', 'HTTP ERROR')
                        </span>
                    </div>

                    @php
                        $incidentId = 'INC-' . strtoupper(substr(hash('sha256', request()->fullUrl() . now()->timestamp), 0, 8));
                    @endphp
                    <span class="text-[11px] font-mono text-slate-400 dark:text-slate-500">
                        Ref: {{ $incidentId }}
                    </span>
                </div>

                {{-- Body Content --}}
                <div class="p-6 sm:p-8 space-y-6">

                    {{-- Icon & Titles --}}
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                        <div class="w-16 h-16 rounded-2xl shrink-0 flex items-center justify-center @yield('icon-container-class', 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700')">
                            @yield('icon')
                        </div>

                        <div class="space-y-1">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight leading-snug">
                                @yield('headline', 'An unexpected error occurred')
                            </h1>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                                @yield('subhead', 'The server was unable to complete your request.')
                            </p>
                        </div>
                    </div>

                    {{-- Error Message Alert (if present) --}}
                    @hasSection('alert-box')
                        @yield('alert-box')
                    @endif

                    {{-- Actions Row --}}
                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        @yield('primary-actions')

                        <button type="button"
                                onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ url('/dashboard') }}'"
                                class="btn btn-outline text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Go Back</span>
                        </button>
                    </div>

                    {{-- Contextual Quick Navigation (Optional) --}}
                    @hasSection('quick-nav')
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 block">
                                Quick Navigation
                            </span>
                            @yield('quick-nav')
                        </div>
                    @endif

                    {{-- Collapsible Diagnostics Drawer --}}
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800" x-data="{ open: false }">
                        <button type="button"
                                @click="open = !open"
                                class="w-full flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 py-1 transition">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                                <span>Diagnostic Information</span>
                            </span>
                            <span class="flex items-center gap-1 text-[11px] font-mono text-slate-400">
                                <span x-text="open ? 'Hide details' : 'Show details'"></span>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                            </span>
                        </button>

                        <div x-show="open" x-cloak class="mt-3 rounded-xl bg-slate-900 text-slate-300 p-4 font-mono text-xs space-y-2 border border-slate-800">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-800 text-[11px]">
                                <span class="text-slate-400 uppercase tracking-wider">System Snapshot</span>
                                <button type="button"
                                        @click="copyIncident(`Incident: {{ $incidentId }}\nPath: {{ request()->method() }} {{ request()->path() }}\nStatus: @yield('code', 'Error')\nTimestamp: {{ now()->toIso8601String() }}`)"
                                        class="text-teal-400 hover:text-teal-300 transition flex items-center gap-1">
                                    <template x-if="!copied">
                                        <span class="flex items-center gap-1"><i data-lucide="copy" class="w-3 h-3"></i> Copy Incident</span>
                                    </template>
                                    <template x-if="copied">
                                        <span class="text-emerald-400 flex items-center gap-1"><i data-lucide="check" class="w-3 h-3"></i> Copied to Clipboard</span>
                                    </template>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1">
                                <div><span class="text-slate-500">Method:</span> <span class="text-slate-200">{{ request()->method() }}</span></div>
                                <div><span class="text-slate-500">Status:</span> <span class="text-slate-200">@yield('code', 'Unknown')</span></div>
                                <div class="sm:col-span-2 truncate"><span class="text-slate-500">Path:</span> <span class="text-teal-400">{{ request()->fullUrl() }}</span></div>
                                <div><span class="text-slate-500">Timestamp:</span> <span class="text-slate-200">{{ now()->format('Y-m-d H:i:s T') }}</span></div>
                                <div><span class="text-slate-500">Session:</span> <span class="text-slate-200">{{ auth()->check() ? 'Authenticated' : 'Guest' }}</span></div>
                                @hasSection('extra-diagnostics')
                                    <div class="sm:col-span-2 pt-1 border-t border-slate-800 text-slate-400">
                                        @yield('extra-diagnostics')
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Footer Bar --}}
                <div class="px-6 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400 dark:text-slate-500">
                    <span>
                        If you believe this is a system malfunction, please contact your internal administrator.
                    </span>
                    <span class="font-mono text-[11px]">
                        v{{ config('app.version', '2.4.0') }}
                    </span>
                </div>

            </div>

        </div>
    </main>

    {{-- Fallback Lucide Icon Activator --}}
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
