@extends('layouts.app')
@section('title', 'Supplier Statement (Accounts Payable)')

@section('content')
@php
    use Carbon\Carbon;

    $fmt = fn($n) => number_format((float)($n ?? 0), 2);
    $fmtMoney = fn($n) => 'RWF ' . number_format((float)($n ?? 0), 2);

    // Preset dates
    $todayDate      = Carbon::today()->toDateString();
    $last7Date      = Carbon::today()->subDays(6)->toDateString();
    $last30Date     = Carbon::today()->subDays(29)->toDateString();
    $thisMonthDate  = Carbon::today()->startOfMonth()->toDateString();
    $lastMonthStart = Carbon::today()->subMonth()->startOfMonth()->toDateString();
    $lastMonthEnd   = Carbon::today()->subMonth()->endOfMonth()->toDateString();
    $thisYearStart  = Carbon::today()->startOfYear()->toDateString();

    $activeFrom = $from ?? '';
    $activeTo   = $to ?? '';
    $hasSupplier = !empty($supplierId) && !empty($supplier);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- ========================================================================= --}}
    {{-- 1. HEADER & NAVIGATION                                                    --}}
    {{-- ========================================================================= --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Statements</span>
                <span>/</span>
                <span>Supplier Ledger</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span>Supplier Statement (Accounts Payable)</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Audit trail of purchase invoices, disbursement payments, goods returns, and progressive running liabilities.
            </p>
        </div>

        {{-- Top Actions --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('reports.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Financial Overview (P&amp;L)</span>
            </a>

            <a href="{{ route('reports.customers.statement') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Customer Statements (AR)</span>
            </a>

            <a href="{{ route('suppliers.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Supplier Registry</span>
            </a>

            @if($hasSupplier)
                <a href="{{ route('reports.suppliers.statement.pdf', request()->all()) }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Download PDF Statement</span>
                </a>
            @endif
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. INTERACTIVE STATEMENT FILTER TOOLBAR                                   --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
        {{-- Fast Presets (if supplier selected) --}}
        @if($hasSupplier)
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800/80">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 mr-1">Timeframe:</span>
                    <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplierId]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ empty($from) && empty($to) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        All Time
                    </a>
                    <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplierId, 'from' => $todayDate, 'to' => $todayDate]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $todayDate && $to === $todayDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Today
                    </a>
                    <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplierId, 'from' => $last7Date, 'to' => $todayDate]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $last7Date && $to === $todayDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Last 7 Days
                    </a>
                    <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplierId, 'from' => $thisMonthDate, 'to' => $todayDate]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $thisMonthDate && $to === $todayDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        This Month
                    </a>
                    <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplierId, 'from' => $lastMonthStart, 'to' => $lastMonthEnd]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $lastMonthStart && $to === $lastMonthEnd ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Last Month
                    </a>
                    <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplierId, 'from' => $thisYearStart, 'to' => $todayDate]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $thisYearStart && $to === $todayDate ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        This Year (YTD)
                    </a>
                </div>

                <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-mono">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Period: {{ $from ? Carbon::parse($from)->format('M d, Y') : 'Inception' }} — {{ $to ? Carbon::parse($to)->format('M d, Y') : 'Present' }}</span>
                </div>
            </div>
        @endif

        {{-- Filter Form --}}
        <form method="GET" action="{{ route('reports.suppliers.statement') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-5">
                <label for="supplier_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                    Select Supplier <span class="text-rose-500">*</span>
                </label>
                <select id="supplier_id" name="supplier_id" required
                        class="w-full py-2.5 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                    <option value="">-- Choose Supplier Entity --</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected((int)$supplierId === (int)$s->id)>
                            {{ $s->name }} @if($s->phone) ({{ $s->phone }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label for="from" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Statement Date From</label>
                <input type="date" id="from" name="from" value="{{ $from }}"
                       class="w-full py-2.5 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>

            <div class="md:col-span-3">
                <label for="to" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Statement Date To</label>
                <input type="date" id="to" name="to" value="{{ $to }}"
                       class="w-full py-2.5 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>

            <div class="md:col-span-1 flex items-center gap-1.5">
                <button type="submit"
                        class="w-full py-2.5 px-3 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition flex items-center justify-center cursor-pointer"
                        title="Generate Statement">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
                <a href="{{ route('reports.suppliers.statement') }}"
                   class="py-2.5 px-3 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700/60 hover:bg-slate-200 transition"
                   title="Reset">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </a>
            </div>
        </form>
    </div>

    @if($hasSupplier)
        {{-- ========================================================================= --}}
        {{-- 3. SUPPLIER DOSSIER CARD                                                  --}}
        {{-- ========================================================================= --}}
        <div class="bg-gradient-to-br from-white via-slate-50/50 to-indigo-50/20 dark:from-slate-800/90 dark:via-slate-800/60 dark:to-slate-900/40 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/60">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                                {{ $supplier->name }}
                            </h2>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Active Vendor
                            </span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                            @if($supplier->contact_person)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ $supplier->contact_person }}
                                </span>
                            @endif
                            @if($supplier->phone)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    {{ $supplier->phone }}
                                </span>
                            @endif
                            @if($supplier->email)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    {{ $supplier->email }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('suppliers.show', $supplier) }}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 transition">
                        <span>Vendor Dossier</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 4. 4-KPI DECK                                                             --}}
        {{-- ========================================================================= --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Purchases Total --}}
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Invoiced (Net)</span>
                    <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/50">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-2xl font-black font-mono text-slate-900 dark:text-white">
                    {{ $fmtMoney($kpis['purchases_total']) }}
                </div>
                <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span>Cumulative purchases billed</span>
                </div>
            </div>

            {{-- Paid Total --}}
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Paid to Supplier</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                    {{ $fmtMoney($kpis['paid_total']) }}
                </div>
                <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Settled disbursement payments</span>
                </div>
            </div>

            {{-- Supplier Refunds --}}
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Supplier Refunds</span>
                    <div class="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-800/50">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-2xl font-black font-mono text-sky-600 dark:text-sky-400">
                    {{ $fmtMoney($kpis['refunds_total']) }}
                </div>
                <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                    <span>Inbound refunds from vendor</span>
                </div>
            </div>

            {{-- Balance Outstanding --}}
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Closing Balance (AP)</span>
                    <div class="p-2.5 rounded-xl {{ $kpis['balance'] > 0 ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50' }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-2xl font-black font-mono {{ $kpis['balance'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ $fmtMoney($kpis['balance']) }}
                </div>
                <div class="mt-1 flex items-center gap-1.5 text-xs font-medium">
                    @if($kpis['balance'] > 0)
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span class="text-amber-700 dark:text-amber-300">Outstanding Payable (We Owe)</span>
                    @elseif($kpis['balance'] < 0)
                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                        <span class="text-sky-700 dark:text-sky-300">Supplier Credit (Advance)</span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-emerald-700 dark:text-emerald-300">Account Zeroed / Settled</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 5. STATEMENT TIMELINE LEDGER TABLE                                        --}}
        {{-- ========================================================================= --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Accounts Payable Transaction Ledger</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Chronological purchases, payments, and running balance calculation.</p>
                    </div>
                </div>
                <span class="text-xs font-mono text-slate-400">{{ $events->count() }} line items</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 dark:bg-slate-900/60 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                            <th scope="col" class="py-3.5 pl-4 sm:pl-6 pr-3">Date</th>
                            <th scope="col" class="px-3 py-3.5">Event Type</th>
                            <th scope="col" class="px-3 py-3.5">Reference &amp; Notes</th>
                            <th scope="col" class="px-3 py-3.5">Payment Method</th>
                            <th scope="col" class="px-3 py-3.5 text-right">Debit / Credit Impact</th>
                            <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($events as $e)
                            @php
                                $isPurchase = $e['type'] === 'Purchase';
                                $isPayment  = $e['type'] === 'Payment';
                                $isRefund   = $e['type'] === 'Supplier Refund';
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                {{-- Date --}}
                                <td class="py-3.5 pl-4 sm:pl-6 pr-3 whitespace-nowrap font-mono text-slate-600 dark:text-slate-300">
                                    {{ $e['date']->format('M d, Y') }}
                                </td>

                                {{-- Event Type Badge --}}
                                <td class="px-3 py-3.5 whitespace-nowrap">
                                    @if($isPurchase)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/60">
                                            <svg class="w-3 h-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                            Purchase
                                        </span>
                                    @elseif($isPayment)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60">
                                            <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Payment
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60">
                                            <svg class="w-3 h-3 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                            Supplier Refund
                                        </span>
                                    @endif
                                </td>

                                {{-- Reference & Notes --}}
                                <td class="px-3 py-3.5">
                                    <div class="flex flex-col">
                                        @if($e['link'])
                                            <a href="{{ $e['link'] }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                                <span>{{ $e['ref'] }}</span>
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        @else
                                            <span class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ $e['ref'] }}</span>
                                        @endif
                                        @if(!empty($e['note']))
                                            <span class="text-[11px] text-slate-400 mt-0.5 line-clamp-1" title="{{ $e['note'] }}">
                                                {{ $e['note'] }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Payment Channel --}}
                                <td class="px-3 py-3.5 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 text-[10px] font-mono">
                                        {{ $e['method'] }}
                                    </span>
                                </td>

                                {{-- Amount Impact --}}
                                <td class="px-3 py-3.5 text-right whitespace-nowrap font-mono font-bold">
                                    @if($e['amount'] >= 0)
                                        <span class="text-slate-900 dark:text-white">
                                            +{{ $fmtMoney($e['amount']) }}
                                        </span>
                                    @else
                                        <span class="text-emerald-600 dark:text-emerald-400">
                                            −{{ $fmtMoney(abs($e['amount'])) }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Running Balance --}}
                                <td class="py-3.5 pl-3 pr-4 sm:pr-6 text-right whitespace-nowrap font-mono font-bold text-sm {{ $e['balance'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $fmtMoney($e['balance']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 italic">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <div class="p-3 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <span class="font-medium">No ledger activity recorded for this supplier in the selected period.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($events->isNotEmpty())
                        <tfoot>
                            <tr class="bg-slate-50 dark:bg-slate-900/60 font-bold border-t-2 border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-200">
                                <td colspan="4" class="py-3 pl-4 sm:pl-6 pr-3 uppercase tracking-wider text-[11px] text-slate-500 dark:text-slate-400">
                                    Closing AP Position as of {{ $to ? Carbon::parse($to)->format('M d, Y') : now()->format('M d, Y') }}
                                </td>
                                <td class="px-3 py-3 text-right text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                    Net Liability
                                </td>
                                <td class="py-3 pl-3 pr-4 sm:pr-6 text-right font-mono font-black text-sm {{ $kpis['balance'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $fmtMoney($kpis['balance']) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 6. RETURNS AUDIT (INFORMATIONAL DISCLOSURE)                                --}}
        {{-- ========================================================================= --}}
        @if($returns->isNotEmpty())
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Goods Returns to Supplier (Reconciliation Audit)</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Informational log of physical goods returned. Values are already credited to the purchase totals.
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-mono text-slate-400">{{ $returns->count() }} return records</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/60 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                <th class="py-2.5 px-5">Return Date</th>
                                <th class="py-2.5 px-5">Linked Purchase</th>
                                <th class="py-2.5 px-5">Payment Channel</th>
                                <th class="py-2.5 px-5 text-right">Returned Value</th>
                                <th class="py-2.5 px-5 text-right">Cash Refunded</th>
                                <th class="py-2.5 px-5 text-right">Items Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @foreach($returns as $r)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-5 font-mono text-slate-600 dark:text-slate-300">
                                        {{ Carbon::parse($r->return_date)->format('M d, Y') }}
                                    </td>
                                    <td class="py-3 px-5">
                                        <a href="{{ route('purchases.show', $r->purchase_id) }}"
                                           class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                            Purchase #{{ $r->purchase_id }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-5 uppercase font-mono text-slate-500 text-[11px]">
                                        {{ $r->payment_channel ?: 'Standard' }}
                                    </td>
                                    <td class="py-3 px-5 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                        {{ $fmtMoney($r->total_amount) }}
                                    </td>
                                    <td class="py-3 px-5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ $fmtMoney($r->refund_amount) }}
                                    </td>
                                    <td class="py-3 px-5 text-right font-mono text-slate-500">
                                        {{ $r->items_count }} items
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    @else
        {{-- ========================================================================= --}}
        {{-- 7. PROMPT / EMPTY STATE WHEN NO SUPPLIER IS SELECTED                      --}}
        {{-- ========================================================================= --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center shadow-xs space-y-4">
            <div class="p-4 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-3xl w-16 h-16 flex items-center justify-center mx-auto">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div class="max-w-md mx-auto">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Select a Supplier Entity</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Please choose a supplier from the dropdown above to load their chronological Accounts Payable statement, payment records, and net running balance.
                </p>
            </div>

            {{-- Quick Supplier Selection Pills --}}
            @if($suppliers->isNotEmpty())
                <div class="pt-4 max-w-xl mx-auto">
                    <span class="text-xs font-semibold text-slate-400 block mb-2 uppercase tracking-wider text-[10px]">
                        Quick Select Registered Suppliers:
                    </span>
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        @foreach($suppliers->take(8) as $s)
                            <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $s->id]) }}"
                               class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-700/60 border border-slate-200 dark:border-slate-700 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-slate-700 hover:border-indigo-300 transition">
                                {{ $s->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
