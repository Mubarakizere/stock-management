@extends('layouts.app')
@section('title', 'Manufacturing & Production Reports')

@section('content')
@php
    use Carbon\Carbon;

    $startDateParsed = Carbon::parse($start);
    $endDateParsed   = Carbon::parse($end);

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $fmtMoney = fn($n) => 'RWF ' . number_format((float)($n ?? 0), 2);

    // Presets dates
    $todayDate      = Carbon::today()->toDateString();
    $last7Date      = Carbon::today()->subDays(6)->toDateString();
    $last30Date     = Carbon::today()->subDays(29)->toDateString();
    $thisMonthDate  = Carbon::today()->startOfMonth()->toDateString();
    $lastMonthStart = Carbon::today()->subMonth()->startOfMonth()->toDateString();
    $lastMonthEnd   = Carbon::today()->subMonth()->endOfMonth()->toDateString();
    $thisYearStart  = Carbon::today()->startOfYear()->toDateString();
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- ========================================================================= --}}
    {{-- 1. HEADER & SUBSYSTEM NAVIGATION                                          --}}
    {{-- ========================================================================= --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-violet-600 dark:text-violet-400 uppercase tracking-wider mb-1">
                <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Manufacturing &amp; Production Telemetry</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-violet-600 text-white shadow-md shadow-violet-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <span>Manufacturing &amp; Production Reports</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Batch yield analytics, material consumption rates, unit production costs, and stock valuations.
            </p>
        </div>

        {{-- Quick Navigation Actions --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('reports.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Financial Overview (P&amp;L)</span>
            </a>

            <a href="{{ route('productions.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Production Orders</span>
            </a>

            <a href="{{ route('raw-materials.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span>Raw Materials</span>
            </a>

            <a href="{{ route('recipes.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-violet-600 hover:bg-violet-700 shadow-md shadow-violet-500/20 active:scale-95 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span>Recipe Master</span>
            </a>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. INTERACTIVE DATE RANGE & PRESET FILTER TOOLBAR                         --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
        {{-- Fast Presets --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800/80">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-400 mr-1">Timeframe:</span>
                <a href="{{ route('reports.manufacturing', ['start_date' => $todayDate, 'end_date' => $todayDate]) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $todayDate && $end === $todayDate ? 'bg-violet-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Today
                </a>
                <a href="{{ route('reports.manufacturing', ['start_date' => $last7Date, 'end_date' => $todayDate]) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $last7Date && $end === $todayDate ? 'bg-violet-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Last 7 Days
                </a>
                <a href="{{ route('reports.manufacturing', ['start_date' => $last30Date, 'end_date' => $todayDate]) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $last30Date && $end === $todayDate ? 'bg-violet-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Last 30 Days
                </a>
                <a href="{{ route('reports.manufacturing', ['start_date' => $thisMonthDate, 'end_date' => $todayDate]) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $thisMonthDate && $end === $todayDate ? 'bg-violet-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Month
                </a>
                <a href="{{ route('reports.manufacturing', ['start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd]) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $lastMonthStart && $end === $lastMonthEnd ? 'bg-violet-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Last Month
                </a>
                <a href="{{ route('reports.manufacturing', ['start_date' => $thisYearStart, 'end_date' => $todayDate]) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $thisYearStart && $end === $todayDate ? 'bg-violet-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Year (YTD)
                </a>
            </div>

            <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-mono">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $startDateParsed->format('M d, Y') }} — {{ $endDateParsed->format('M d, Y') }}</span>
            </div>
        </div>

        {{-- Custom Filter Form --}}
        <form method="GET" action="{{ route('reports.manufacturing') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-5">
                <label for="start_date" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Production Start Date</label>
                <input type="date" id="start_date" name="start_date" value="{{ $start }}"
                       class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-violet-500/50">
            </div>

            <div class="md:col-span-5">
                <label for="end_date" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Production End Date</label>
                <input type="date" id="end_date" name="end_date" value="{{ $end }}"
                       class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-violet-500/50">
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit"
                        class="w-full py-2 px-4 rounded-xl text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 active:scale-95 shadow-md shadow-violet-500/20 transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('reports.manufacturing') }}"
                   class="py-2 px-3 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700/60 hover:bg-slate-200 transition"
                   title="Reset Period">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </a>
            </div>
        </form>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3. EXECUTIVE 4-KPI DECK                                                   --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- KPI 1: Production Runs --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group hover:border-violet-400 dark:hover:border-violet-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Production Runs</span>
                <div class="p-2.5 rounded-xl bg-violet-50 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black font-mono text-slate-900 dark:text-white">
                    {{ $fmt0($productionRuns) }}
                </span>
                <span class="text-xs text-slate-400 font-medium">batches completed</span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                <span>Active manufacturing batches</span>
            </div>
        </div>

        {{-- KPI 2: Units Produced --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group hover:border-emerald-400 dark:hover:border-emerald-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Units Produced</span>
                <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                    {{ $fmt2($totalUnitsProduced) }}
                </span>
                <span class="text-xs text-slate-400 font-medium">finished units</span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Total product yield</span>
            </div>
        </div>

        {{-- KPI 3: Material Cost --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group hover:border-amber-400 dark:hover:border-amber-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Material Cost Incurred</span>
                <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400">
                    {{ $fmtMoney($totalMaterialCost) }}
                </span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Consumed raw inventory value</span>
            </div>
        </div>

        {{-- KPI 4: Stock Health Alerts --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group hover:border-rose-400 dark:hover:border-rose-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Raw Material Health</span>
                <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black font-mono {{ $rmOutCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                    {{ $rmOutCount }} <span class="text-xs font-normal text-slate-400">out of stock</span>
                </span>
            </div>
            <div class="mt-1 flex items-center gap-2 text-xs font-medium">
                @if($rmLowCount > 0)
                    <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        {{ $rmLowCount }} low stock items
                    </span>
                @else
                    <span class="text-slate-400">All other raw materials healthy</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 4. PRODUCTION YIELD & RAW MATERIAL CONSUMPTION (DUAL SECTION)             --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- 1. Top Finished Products Produced --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Top Finished Products Produced
                        </h2>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">{{ $topProduced->count() }} SKUs</span>
                </div>

                @php
                    $maxProduced = $topProduced->max('total_qty') ?: 1;
                @endphp

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/60 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                <th class="py-2.5 px-4">Product Name</th>
                                <th class="py-2.5 px-4 text-right">Yield Quantity</th>
                                <th class="py-2.5 px-4 text-right">Estimated Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @forelse($topProduced as $tp)
                                @php
                                    $qty = (float) $tp->total_qty;
                                    $price = (float) ($tp->product->price ?? 0);
                                    $val = $qty * $price;
                                    $pct = min(100, round(($qty / $maxProduced) * 100));
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-slate-900 dark:text-white truncate max-w-[200px]" title="{{ $tp->product->name ?? '—' }}">
                                            {{ $tp->product->name ?? '—' }}
                                        </div>
                                        <div class="w-32 bg-slate-100 dark:bg-slate-700 rounded-full h-1 mt-1 overflow-hidden">
                                            <div class="h-1 rounded-full bg-violet-600 dark:bg-violet-400" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-violet-700 dark:text-violet-300">
                                        {{ $fmt2($qty) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono text-slate-600 dark:text-slate-400">
                                        {{ $fmtMoney($val) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-slate-400 italic">
                                        No manufacturing production runs recorded in this window.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 2. Raw Material Usage Ledger --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Raw Material Usage &amp; Consumption Cost
                        </h2>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">{{ $rawMaterialUsage->count() }} items</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/60 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                <th class="py-2.5 px-4">Material</th>
                                <th class="py-2.5 px-4 text-right">Quantity Consumed</th>
                                <th class="py-2.5 px-4 text-right">Total Cost</th>
                                <th class="py-2.5 px-4 text-right">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @forelse($rawMaterialUsage as $mu)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-medium text-slate-900 dark:text-white truncate max-w-[160px]" title="{{ $mu['name'] }}">
                                        {{ $mu['name'] }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                        {{ $fmt2($mu['total_used']) }} <span class="text-[10px] text-slate-400 font-normal">{{ $mu['unit'] }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono text-amber-600 dark:text-amber-400 font-bold">
                                        {{ $fmtMoney($mu['total_cost']) }}
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <span class="font-mono text-xs {{ $mu['in_stock'] <= 0 ? 'text-rose-600 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                            {{ $fmt2($mu['in_stock']) }} <span class="text-[10px] text-slate-400 font-normal">{{ $mu['unit'] }}</span>
                                        </span>
                                        @if($mu['in_stock'] <= 0)
                                            <span class="ml-1 px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Out</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400 italic">
                                        No raw material consumption recorded for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($rawMaterialUsage->isNotEmpty())
                            <tfoot>
                                <tr class="bg-slate-50 dark:bg-slate-900/60 font-bold border-t border-slate-200 dark:border-slate-700">
                                    <td class="py-2.5 px-4 text-slate-800 dark:text-slate-200 uppercase text-[10px] tracking-wider" colspan="2">Aggregate Material Cost</td>
                                    <td class="py-2.5 px-4 text-right font-mono text-amber-600 dark:text-amber-400 text-sm">{{ $fmtMoney($totalMaterialCost) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 5. STOCK LEVELS & VALUATION (RAW MATERIALS VS FINISHED GOODS)              --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Raw Materials Stock Registry --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <span>Raw Materials Inventory</span>
                            <span class="px-2 py-0.5 rounded-full bg-teal-100 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 text-[10px]">{{ $rawMaterialsStock->count() }} items</span>
                        </h2>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400">Valuation:</span>
                        <span class="font-mono font-bold text-xs text-teal-600 dark:text-teal-400 ml-1">{{ $fmtMoney($totalRmValue) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="py-2.5 px-4">Material Name</th>
                                <th class="py-2.5 px-4 text-right">On Hand</th>
                                <th class="py-2.5 px-4 text-right">Asset Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @forelse($rawMaterialsStock as $rm)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition {{ $rm['out'] ? 'bg-rose-50/40 dark:bg-rose-950/10' : ($rm['low'] ? 'bg-amber-50/30 dark:bg-amber-950/10' : '') }}">
                                    <td class="py-2.5 px-4">
                                        <div class="font-medium text-slate-900 dark:text-white">{{ $rm['name'] }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $rm['category'] }}</div>
                                    </td>
                                    <td class="py-2.5 px-4 text-right whitespace-nowrap">
                                        <span class="font-mono font-bold {{ $rm['out'] ? 'text-rose-600 dark:text-rose-400' : ($rm['low'] ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-200') }}">
                                            {{ $fmt2($rm['stock']) }} <span class="text-[10px] text-slate-400 font-normal">{{ $rm['unit'] }}</span>
                                        </span>
                                        @if($rm['out'])
                                            <span class="ml-1 px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Out</span>
                                        @elseif($rm['low'])
                                            <span class="ml-1 px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Low</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-mono font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $fmtMoney($rm['value']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-slate-400 italic">
                                        No raw materials registered in catalogue.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Finished Products Stock (Recipe-tracked) --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <span>Finished Goods Inventory</span>
                            <span class="px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-[10px]">{{ $finishedProductsStock->count() }} items</span>
                        </h2>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400">Valuation:</span>
                        <span class="font-mono font-bold text-xs text-blue-600 dark:text-blue-400 ml-1">{{ $fmtMoney($totalFpValue) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="py-2.5 px-4">Finished Product</th>
                                <th class="py-2.5 px-4 text-right">On Hand</th>
                                <th class="py-2.5 px-4 text-right">Asset Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @forelse($finishedProductsStock as $fp)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition {{ $fp['out'] ? 'bg-rose-50/40 dark:bg-rose-950/10' : ($fp['low'] ? 'bg-amber-50/30 dark:bg-amber-950/10' : '') }}">
                                    <td class="py-2.5 px-4">
                                        <div class="font-medium text-slate-900 dark:text-white">{{ $fp['name'] }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $fp['category'] }}</div>
                                    </td>
                                    <td class="py-2.5 px-4 text-right whitespace-nowrap">
                                        <span class="font-mono font-bold {{ $fp['out'] ? 'text-rose-600 dark:text-rose-400' : ($fp['low'] ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-200') }}">
                                            {{ $fmt2($fp['stock']) }} <span class="text-[10px] text-slate-400 font-normal">{{ $fp['unit'] }}</span>
                                        </span>
                                        @if($fp['out'])
                                            <span class="ml-1 px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Out</span>
                                        @elseif($fp['low'])
                                            <span class="ml-1 px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Low</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-mono font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $fmtMoney($fp['value']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-slate-400 italic">
                                        No recipe-linked finished products found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
