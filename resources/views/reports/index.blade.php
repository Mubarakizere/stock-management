@extends('layouts.app')
@section('title', 'Financial Intelligence & Reports')

@section('content')
@php
    use Carbon\Carbon;

    // Safe fallbacks
    $start  = $start ?? now()->subMonth()->toDateString();
    $end    = $end ?? now()->toDateString();
    $q      = $q ?? '';

    $startDateParsed = Carbon::parse($start);
    $endDateParsed   = Carbon::parse($end);

    $fmt = fn($n) => 'RWF ' . number_format((float) str_replace(',', '', (string) ($n ?? 0)), 2);
    $fmtRaw = fn($n) => number_format((float) str_replace(',', '', (string) ($n ?? 0)), 2);
    $num = fn($n) => (float) str_replace(',', '', (string) ($n ?? 0));

    $totalSalesNum       = $num($totalSales ?? 0);
    $totalProfitNum      = $num($totalProfit ?? 0);
    $pendingBalancesNum  = $num($pendingBalances ?? 0);
    $totalPurchasesNum   = $num($totalPurchases ?? 0);
    $creditsNum          = $num($credits ?? 0);
    $debitsNum           = $num($debits ?? 0);
    $netBalanceNum       = $num($netBalance ?? 0);
    $loansGivenNum       = $num($loansGiven ?? 0);
    $loansTakenNum       = $num($loansTaken ?? 0);

    $plRevenueNum        = (float) ($plRevenue ?? 0);
    $plCogsNum           = (float) ($plCogs ?? 0);
    $plGrossProfitNum    = (float) ($plGrossProfit ?? 0);
    $plOtherExpensesNum  = (float) ($plOtherExpenses ?? 0);
    $plNetProfitNum      = (float) ($plNetProfit ?? 0);

    $grossMarginPct      = $plRevenueNum > 0 ? round(($plGrossProfitNum / $plRevenueNum) * 100, 1) : 0;
    $netMarginPct        = $plRevenueNum > 0 ? round(($plNetProfitNum / $plRevenueNum) * 100, 1) : 0;

    // Presets dates
    $todayDate     = Carbon::today()->toDateString();
    $last7Date     = Carbon::today()->subDays(6)->toDateString();
    $last30Date    = Carbon::today()->subDays(29)->toDateString();
    $thisMonthDate = Carbon::today()->startOfMonth()->toDateString();
    $lastMonthStart = Carbon::today()->subMonth()->startOfMonth()->toDateString();
    $lastMonthEnd   = Carbon::today()->subMonth()->endOfMonth()->toDateString();
    $thisYearStart  = Carbon::today()->startOfYear()->toDateString();
@endphp

@can('reports.view')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- ========================================================================= --}}
    {{-- 1. HEADER & SUBSYSTEM NAVIGATION                                          --}}
    {{-- ========================================================================= --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Executive Analytics & Auditing</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Financial Intelligence & Reports
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Statement of Profit & Loss, cash velocity, working capital, and predictive sales telemetry.
            </p>
        </div>

        {{-- Sub-Reports Hub Navigation & Export Hub --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('reports.manufacturing') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Manufacturing</span>
            </a>

            <a href="{{ route('reports.customers.statement') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Customer Statements</span>
            </a>

            <a href="{{ route('reports.suppliers.statement') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Supplier Statements</span>
            </a>

            {{-- Quick Export Trigger --}}
            <a href="{{ route('reports.export.pl.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Download P&L (PDF)</span>
            </a>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. INTERACTIVE DATE RANGE & KEYWORD FILTER TOOLBAR                        --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
        {{-- Fast Range Preset Buttons --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800/80">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-400 mr-1">Date Preset:</span>
                <a href="{{ route('reports.index', array_merge(request()->except(['start_date','end_date']), ['start_date' => $todayDate, 'end_date' => $todayDate])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $todayDate && $end === $todayDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Today
                </a>
                <a href="{{ route('reports.index', array_merge(request()->except(['start_date','end_date']), ['start_date' => $last7Date, 'end_date' => $todayDate])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $last7Date && $end === $todayDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Last 7 Days
                </a>
                <a href="{{ route('reports.index', array_merge(request()->except(['start_date','end_date']), ['start_date' => $last30Date, 'end_date' => $todayDate])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $last30Date && $end === $todayDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Last 30 Days
                </a>
                <a href="{{ route('reports.index', array_merge(request()->except(['start_date','end_date']), ['start_date' => $thisMonthDate, 'end_date' => $todayDate])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $thisMonthDate && $end === $todayDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Month
                </a>
                <a href="{{ route('reports.index', array_merge(request()->except(['start_date','end_date']), ['start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $lastMonthStart && $end === $lastMonthEnd ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Last Month
                </a>
                <a href="{{ route('reports.index', array_merge(request()->except(['start_date','end_date']), ['start_date' => $thisYearStart, 'end_date' => $todayDate])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $start === $thisYearStart && $end === $todayDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Year (YTD)
                </a>
            </div>

            {{-- Audit Scope Pill --}}
            <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-mono">
                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $startDateParsed->format('M d, Y') }} — {{ $endDateParsed->format('M d, Y') }}</span>
            </div>
        </div>

        {{-- Custom Filter Form --}}
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-3">
                <label for="start_date" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Audit Start Date</label>
                <input type="date" id="start_date" name="start_date" value="{{ $start }}"
                       class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>

            <div class="md:col-span-3">
                <label for="end_date" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Audit End Date</label>
                <input type="date" id="end_date" name="end_date" value="{{ $end }}"
                       class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>

            <div class="md:col-span-4">
                <label for="q" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Search Context (Customer, Product, Notes)</label>
                <div class="relative">
                    <input type="text" id="q" name="q" value="{{ $q }}" placeholder="Filter by name, voucher, or terms..."
                           class="w-full pl-9 pr-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit"
                        class="w-full py-2 px-4 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Apply Filter</span>
                </button>
                <a href="{{ route('reports.index') }}"
                   class="py-2 px-3 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700/60 hover:bg-slate-200 transition"
                   title="Reset All Filters">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </a>
            </div>
        </form>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3. STATEMENT OF PROFIT & LOSS (P&L EXECUTIVE COCKPIT)                     --}}
    {{-- ========================================================================= --}}
    <div class="bg-gradient-to-br from-white via-slate-50/50 to-indigo-50/20 dark:from-slate-800/90 dark:via-slate-800/60 dark:to-slate-900/40 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 sm:p-7 shadow-xs space-y-6">
        {{-- P&L Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-5 border-b border-slate-200/80 dark:border-slate-700/60">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Statement of Profit &amp; Loss</span>
                        <span class="text-[11px] font-semibold font-mono px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700/80 text-slate-600 dark:text-slate-300">
                            {{ $startDateParsed->format('M d') }} – {{ $endDateParsed->format('M d, Y') }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Accrual accounting statement reflecting gross margins, cost of goods, and net operational income.
                    </p>
                </div>
            </div>

            <a href="{{ route('reports.export.pl.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 transition">
                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Export P&amp;L Statement</span>
            </a>
        </div>

        {{-- 4-Stage Waterfall Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Stage 1: Operating Revenue --}}
            <div class="bg-white/80 dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-700/70 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>1. Sales Revenue</span>
                    <span class="text-[10px] font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 px-1.5 py-0.5 rounded">Gross Inflow</span>
                </div>
                <div class="text-xl font-black font-mono text-slate-900 dark:text-white mt-2">
                    {{ $fmt($plRevenueNum) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Cumulative item billings in period
                </div>
            </div>

            {{-- Stage 2: COGS --}}
            <div class="bg-white/80 dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-700/70 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>2. Cost of Goods (COGS)</span>
                    <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded">Direct Cost</span>
                </div>
                <div class="text-xl font-black font-mono text-amber-600 dark:text-amber-400 mt-2">
                    − {{ $fmt($plCogsNum) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Inventory acquisition base cost
                </div>
            </div>

            {{-- Stage 3: Gross Profit --}}
            <div class="bg-white/80 dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-700/70 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>3. Gross Operating Profit</span>
                    <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded">{{ $grossMarginPct }}% Margin</span>
                </div>
                <div class="text-xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-2">
                    {{ $fmt($plGrossProfitNum) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Gross trading margin spread
                </div>
            </div>

            {{-- Stage 4: Operating Expenses --}}
            <div class="bg-white/80 dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-700/70 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                    <span>4. Operating Expenses (OPEX)</span>
                    <span class="text-[10px] font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-1.5 py-0.5 rounded">Overhead</span>
                </div>
                <div class="text-xl font-black font-mono text-rose-600 dark:text-rose-400 mt-2">
                    − {{ $fmt($plOtherExpensesNum) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Direct ledger debits & operational costs
                </div>
            </div>
        </div>

        {{-- Net Profit Highlight Banner --}}
        <div class="rounded-2xl p-5 border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 {{ $plNetProfitNum >= 0 ? 'bg-emerald-500/10 border-emerald-500/30 dark:bg-emerald-950/20 dark:border-emerald-800/40' : 'bg-rose-500/10 border-rose-500/30 dark:bg-rose-950/20 dark:border-rose-800/40' }}">
            <div class="flex items-center gap-3.5">
                <div class="p-3 rounded-2xl {{ $plNetProfitNum >= 0 ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' }}">
                    @if($plNetProfitNum >= 0)
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    @else
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                    @endif
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider {{ $plNetProfitNum >= 0 ? 'text-emerald-800 dark:text-emerald-300' : 'text-rose-800 dark:text-rose-300' }}">
                        Net Operating Income (Bottom Line)
                    </div>
                    <div class="text-2xl sm:text-3xl font-black font-mono {{ $plNetProfitNum >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $fmt($plNetProfitNum) }}
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($plNetProfitNum >= 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Profitable Operation ({{ $netMarginPct }}% Net Margin)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200 border border-rose-300 dark:border-rose-700">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        Net Deficit in Period
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 4. LIVE BALANCE SHEET & INVENTORY VALUATION SNAPSHOT                      --}}
    {{-- ========================================================================= --}}
    <div>
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
            </svg>
            <span>Live Balance Sheet &amp; Physical Asset Inventory</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- AR --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Accounts Receivable (AR)</span>
                    <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-xl font-black font-mono {{ $arBalance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-200' }}">
                    {{ $fmt($arBalance) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Customer debts awaiting settlement
                </div>
            </div>

            {{-- AP --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Accounts Payable (AP)</span>
                    <div class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-xl font-black font-mono {{ $apBalance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-200' }}">
                    {{ $fmt($apBalance) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Supplier payables awaiting liquidation
                </div>
            </div>

            {{-- Stock Units --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Current Stock Count</span>
                    <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-xl font-black font-mono text-slate-900 dark:text-white">
                    {{ number_format($inventoryUnits, 0) }} <span class="text-xs font-normal text-slate-400">units</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total on-hand inventory units
                </div>
            </div>

            {{-- Inventory Value --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Inventory Valuation (@ Cost)</span>
                    <div class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800/50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-xl font-black font-mono text-purple-600 dark:text-purple-400">
                    {{ $fmt($inventoryValue) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Current tied-up asset capital
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 5. COMPREHENSIVE CASH & LEDGER METRICS DECK                                --}}
    {{-- ========================================================================= --}}
    <div>
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span>Period Operational &amp; Cash Velocity Metrics</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            {{-- Gross Sales --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Gross Sales</span>
                <div class="text-lg font-black font-mono text-slate-900 dark:text-white mt-1">
                    {{ $fmt($totalSalesNum) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                    <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $fmt($totalProfitNum) }}</span>
                    <span>net profit</span>
                </div>
            </div>

            {{-- Purchases Outflow --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Purchases &amp; Procurement</span>
                <div class="text-lg font-black font-mono text-indigo-600 dark:text-indigo-400 mt-1">
                    {{ $fmt($totalPurchasesNum) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Invoiced raw stock & goods
                </div>
            </div>

            {{-- Pending Balances --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Period Pending Sales</span>
                <div class="text-lg font-black font-mono text-amber-600 dark:text-amber-400 mt-1">
                    {{ $fmt($pendingBalancesNum) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Sales on credit in this window
                </div>
            </div>

            {{-- Net Cash Balance --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Ledger Net Cash Balance</span>
                    <span class="w-2 h-2 rounded-full {{ $netBalanceNum >= 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                </div>
                <div class="text-lg font-black font-mono {{ $netBalanceNum >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} mt-1">
                    {{ $fmt($netBalanceNum) }}
                </div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                    In: +{{ $fmt($creditsNum) }} • Out: −{{ $fmt($debitsNum) }}
                </div>
            </div>

            {{-- Loans Portfolio --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Capital Loans (Cash)</span>
                <div class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200 mt-2 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Lent (Given):</span>
                        <span class="text-blue-600 dark:text-blue-400">{{ $fmt($loansGivenNum) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Borrowed (Taken):</span>
                        <span class="text-purple-600 dark:text-purple-400">{{ $fmt($loansTakenNum) }}</span>
                    </div>
                </div>
            </div>

            {{-- MoM Growth --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">MoM Sales Growth</span>
                <div class="text-lg font-black font-mono mt-1 flex items-center gap-1.5 {{ $revenueGrowth >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        @if($revenueGrowth >= 0)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        @endif
                    </svg>
                    <span>{{ number_format($revenueGrowth, 1) }}%</span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Compared to previous calendar month
                </div>
            </div>

            {{-- Profit Margin --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Overall Profit Margin</span>
                <div class="text-lg font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ number_format($profitMargin, 1) }}%
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Item profit divided by revenue
                </div>
            </div>

            {{-- Expense Ratio --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Expense-to-Revenue Ratio</span>
                <div class="text-lg font-black font-mono text-indigo-600 dark:text-indigo-400 mt-1">
                    {{ number_format($expenseRatio, 1) }}%
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Purchases &amp; debits vs total sales
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 6. VISUAL ANALYTICS CHARTS                                                --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Chart 1: Monthly Sales Trend --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Monthly Sales Trajectory</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Revenue generation over the last 6 months</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500">Trailing 6M</span>
                </div>
                <div class="h-64 relative">
                    <canvas id="monthlySalesChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Chart 2: Revenue vs Expenses --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Revenue vs Procurement Expenses</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Cash generation vs stock expenditures comparison</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500">Comparative</span>
                </div>
                <div class="h-64 relative">
                    <canvas id="revenueExpenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 7. TOP PERFORMERS RANKINGS (TOP PRODUCTS & TOP CUSTOMERS)                 --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Products --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Top 5 Products by Sales Volume
                    </h3>
                </div>
                <span class="text-[10px] text-slate-400">Selected period</span>
            </div>

            @php
                $maxProdSales = $topProducts->max('total_sales') ?: 1;
            @endphp

            <div class="space-y-3">
                @forelse($topProducts as $idx => $tp)
                    @php
                        $tpSales = (float) $tp->total_sales;
                        $tpPct = min(100, round(($tpSales / $maxProdSales) * 100));
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2 truncate max-w-[240px]">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold font-mono {{ $idx === 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                    #{{ $idx + 1 }}
                                </span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 truncate" title="{{ optional($tp->product)->name }}">
                                    {{ optional($tp->product)->name ?? 'Unknown SKU' }}
                                </span>
                            </div>
                            <span class="font-mono font-bold text-slate-900 dark:text-white">
                                {{ $fmt($tpSales) }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700/60 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-500 transition-all duration-500" style="width: {{ $tpPct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        No product sales recorded in this timeframe.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Top Customers --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Top 5 Spending Customers
                    </h3>
                </div>
                <span class="text-[10px] text-slate-400">Selected period</span>
            </div>

            @php
                $maxCustSpent = $topCustomers->max('total_spent') ?: 1;
            @endphp

            <div class="space-y-3">
                @forelse($topCustomers as $idx => $tc)
                    @php
                        $tcSpent = (float) $tc->total_spent;
                        $tcPct = min(100, round(($tcSpent / $maxCustSpent) * 100));
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2 truncate max-w-[240px]">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold font-mono {{ $idx === 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                    #{{ $idx + 1 }}
                                </span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 truncate" title="{{ optional($tc->customer)->name }}">
                                    {{ optional($tc->customer)->name ?? 'Direct Counter Customer' }}
                                </span>
                            </div>
                            <span class="font-mono font-bold text-slate-900 dark:text-white">
                                {{ $fmt($tcSpent) }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700/60 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-500 transition-all duration-500" style="width: {{ $tcPct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        No customer transactions recorded in this timeframe.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 8. COMPREHENSIVE EXPORT HUB                                               --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Financial Statement &amp; Ledger Export Center</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Download formatted audits and structured datasets for accountants and stakeholders.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Export 1: Sales CSV --}}
            <a href="{{ route('reports.export.sales.csv', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               class="group p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 hover:border-emerald-500 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/20 transition flex flex-col justify-between">
                <div>
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-2 group-hover:scale-105 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Sales Ledger Export</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Raw transaction records, customers, and settlement statuses.</p>
                </div>
                <div class="mt-3 flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                    <span>Download CSV</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>
            </a>

            {{-- Export 2: Finance PDF --}}
            <a href="{{ route('reports.export.finance.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               class="group p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 hover:border-indigo-500 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition flex flex-col justify-between">
                <div>
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-2 group-hover:scale-105 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Executive Finance Report</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Formal A4 balance audit, credit/debit totals, and loans.</p>
                </div>
                <div class="mt-3 flex items-center gap-1 text-[11px] font-bold text-indigo-600 dark:text-indigo-400">
                    <span>Download PDF</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>
            </a>

            {{-- Export 3: Insights PDF --}}
            <a href="{{ route('reports.export.insights.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               class="group p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 hover:border-purple-500 hover:bg-purple-50/30 dark:hover:bg-purple-950/20 transition flex flex-col justify-between">
                <div>
                    <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-2 group-hover:scale-105 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Business Intelligence Brief</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Top performing products, clients, and profit ratios.</p>
                </div>
                <div class="mt-3 flex items-center gap-1 text-[11px] font-bold text-purple-600 dark:text-purple-400">
                    <span>Download PDF</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>
            </a>

            {{-- Export 4: P&L PDF --}}
            <a href="{{ route('reports.export.pl.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               class="group p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 hover:border-rose-500 hover:bg-rose-50/30 dark:hover:bg-rose-950/20 transition flex flex-col justify-between">
                <div>
                    <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-2 group-hover:scale-105 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Profit &amp; Loss Statement</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Audited breakdown of Revenue, COGS, Gross Profit &amp; OPEX.</p>
                </div>
                <div class="mt-3 flex items-center gap-1 text-[11px] font-bold text-rose-600 dark:text-rose-400">
                    <span>Download PDF</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>
            </a>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        // 1. Monthly Sales Trajectory Chart
        const monthlyLabels = @json($months ?? []);
        const monthlyData   = @json($monthlySales ?? []);
        const monthlyCanvas = document.getElementById('monthlySalesChart');

        if (monthlyCanvas && window.Chart && monthlyLabels.length) {
            const ctxMonthly = monthlyCanvas.getContext('2d');
            const gradientSales = ctxMonthly.createLinearGradient(0, 0, 0, 240);
            gradientSales.addColorStop(0, 'rgba(99, 102, 241, 0.25)');
            gradientSales.addColorStop(1, 'rgba(99, 102, 241, 0.00)');

            new Chart(ctxMonthly, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Gross Sales (RWF)',
                        data: monthlyData,
                        borderColor: '#6366f1',
                        backgroundColor: gradientSales,
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: isDark ? '#1e293b' : '#ffffff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' Sales: RWF ' + Number(ctx.raw || 0).toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: textColor, font: { size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                font: { size: 11 },
                                callback: val => Number(val).toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        // 2. Revenue vs Expenses Comparative Chart
        const reChartData = @json($revenueExpensesChart ?? []);
        const reMonths    = reChartData.map(item => item.month);
        const reSales     = reChartData.map(item => item.sales);
        const reExpenses  = reChartData.map(item => item.expenses);
        const reCanvas    = document.getElementById('revenueExpenseChart');

        if (reCanvas && window.Chart && reMonths.length) {
            const ctxRE = reCanvas.getContext('2d');
            new Chart(ctxRE, {
                type: 'line',
                data: {
                    labels: reMonths,
                    datasets: [
                        {
                            label: 'Sales Revenue',
                            data: reSales,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.08)',
                            borderWidth: 2.5,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 3.5,
                            pointBackgroundColor: '#10b981'
                        },
                        {
                            label: 'Purchasing Outflow',
                            data: reExpenses,
                            borderColor: '#f43f5e',
                            backgroundColor: 'rgba(244, 63, 94, 0.08)',
                            borderWidth: 2.5,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 3.5,
                            pointBackgroundColor: '#f43f5e'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor,
                                font: { size: 11, weight: '600' },
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.dataset.label + ': RWF ' + Number(ctx.raw || 0).toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: textColor, font: { size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                font: { size: 11 },
                                callback: val => Number(val).toLocaleString()
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush

@else
<div class="py-12 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="rounded-2xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 p-6 text-xs text-amber-800 dark:text-amber-200">
        <h3 class="text-sm font-bold mb-1">Access Restricted</h3>
        <p>You do not have administrative permission to view financial reports and ledger audits. Please contact your system administrator.</p>
    </div>
</div>
@endcan
@endsection
