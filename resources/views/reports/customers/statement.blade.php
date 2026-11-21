@extends('layouts.app')
@section('title', 'Customer Statement (Accounts Receivable)')

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
    $hasCustomer = !empty($customerId) && !empty($customer);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- ========================================================================= --}}
    {{-- 1. HEADER & NAVIGATION                                                    --}}
    {{-- ========================================================================= --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">
                <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Statements</span>
                <span>/</span>
                <span>Customer Ledger</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <span>Customer Statement (Accounts Receivable)</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Audit trail of sales invoices, inbound customer payments, credit adjustments, and progressive receivable balances.
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

            <a href="{{ route('reports.suppliers.statement') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Supplier Statements (AP)</span>
            </a>

            <a href="{{ route('customers.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>Customer Registry</span>
            </a>

            @if($hasCustomer)
                <a href="{{ route('reports.customers.statement.pdf', request()->all()) }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:scale-95 shadow-md shadow-emerald-500/20 transition cursor-pointer">
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
        {{-- Fast Presets (if customer selected) --}}
        @if($hasCustomer)
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800/80">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 mr-1">Timeframe:</span>
                    <a href="{{ route('reports.customers.statement', ['customer_id' => $customerId]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ empty($from) && empty($to) ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        All Time
                    </a>
                    <a href="{{ route('reports.customers.statement', ['customer_id' => $customerId, 'from' => $todayDate, 'to' => $todayDate]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $todayDate && $to === $todayDate ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Today
                    </a>
                    <a href="{{ route('reports.customers.statement', ['customer_id' => $customerId, 'from' => $last7Date, 'to' => $todayDate]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $last7Date && $to === $todayDate ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Last 7 Days
                    </a>
                    <a href="{{ route('reports.customers.statement', ['customer_id' => $customerId, 'from' => $thisMonthDate, 'to' => $todayDate]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $thisMonthDate && $to === $todayDate ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        This Month
                    </a>
                    <a href="{{ route('reports.customers.statement', ['customer_id' => $customerId, 'from' => $lastMonthStart, 'to' => $lastMonthEnd]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $lastMonthStart && $to === $lastMonthEnd ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Last Month
                    </a>
                    <a href="{{ route('reports.customers.statement', ['customer_id' => $customerId, 'from' => $thisYearStart, 'to' => $todayDate]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ $from === $thisYearStart && $to === $todayDate ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        This Year (YTD)
                    </a>
                </div>

                <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-mono">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Period: {{ $from ? Carbon::parse($from)->format('M d, Y') : 'Inception' }} — {{ $to ? Carbon::parse($to)->format('M d, Y') : 'Present' }}</span>
                </div>
            </div>
        @endif

        {{-- Form Controls --}}
        <form method="GET" action="{{ route('reports.customers.statement') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3.5 items-end">
            {{-- Customer Selector --}}
            <div class="md:col-span-5">
                <label for="customer_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Select Customer <span class="text-emerald-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <select name="customer_id" id="customer_id" required
                            class="w-full pl-9 pr-8 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                        <option value="">-- Choose Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected((int)$customerId === (int)$c->id)>
                                {{ $c->name }} {{ $c->phone ? '('.$c->phone.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- From Date --}}
            <div class="md:col-span-3">
                <label for="from" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    From Date
                </label>
                <input type="date" name="from" id="from" value="{{ $activeFrom }}"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
            </div>

            {{-- To Date --}}
            <div class="md:col-span-3">
                <label for="to" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    To Date
                </label>
                <input type="date" name="to" id="to" value="{{ $activeTo }}"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
            </div>

            {{-- Action Buttons --}}
            <div class="md:col-span-1 flex items-center gap-2">
                <button type="submit"
                        class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-sm font-semibold flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/20 transition cursor-pointer"
                        title="Generate Statement">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="md:hidden lg:inline">Filter</span>
                </button>

                @if($hasCustomer || $activeFrom || $activeTo)
                    <a href="{{ route('reports.customers.statement') }}"
                       class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold flex items-center justify-center transition"
                       title="Reset Filters">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if($hasCustomer)
        {{-- ========================================================================= --}}
        {{-- 3. CUSTOMER DOSSIER CARD                                                  --}}
        {{-- ========================================================================= --}}
        <div class="bg-gradient-to-r from-emerald-900/10 via-slate-900/5 to-transparent dark:from-emerald-950/40 dark:via-slate-900/40 rounded-2xl border border-emerald-200/80 dark:border-emerald-800/40 p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center text-white text-xl font-extrabold shadow-md shadow-emerald-500/20">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $customer->name }}</h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                Verified Client #{{ $customer->id }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-y-1 gap-x-4 mt-1 text-xs text-slate-500 dark:text-slate-400">
                            @if($customer->phone)
                                <div class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <span>{{ $customer->phone }}</span>
                                </div>
                            @endif
                            @if($customer->email)
                                <div class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ $customer->email }}</span>
                                </div>
                            @endif
                            @if($customer->address)
                                <div class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>{{ $customer->address }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('customers.show', $customer->id) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Customer Profile</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 4. EXECUTIVE 4-KPI DECK                                                   --}}
        {{-- ========================================================================= --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- KPI 1: Sales Invoiced (Net) --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Invoiced Sales</span>
                    <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $fmtMoney($kpis['sales_total']) }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                        <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $events->where('type', 'Sale')->count() }} sales</span>
                        <span>in selected timeframe</span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-600"></div>
            </div>

            {{-- KPI 2: Paid by Customer --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Payments Received</span>
                    <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                        {{ $fmtMoney($kpis['paid_total']) }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $events->where('type', 'Payment')->count() }} payments</span>
                        <span>collected via cash/bank/MoMo</span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
            </div>

            {{-- KPI 3: Refunds Issued --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Customer Refunds</span>
                    <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 tracking-tight">
                        {{ $fmtMoney($kpis['refunds_total']) }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                        <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $events->where('type', 'Customer Refund')->count() }} refunds</span>
                        <span>reimbursed to client</span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
            </div>

            {{-- KPI 4: Net AR Balance --}}
            @php
                $isOwed = $kpis['balance'] > 0;
                $isZero = abs($kpis['balance']) < 0.01;
            @endphp
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border {{ $isOwed ? 'border-rose-300 dark:border-rose-800' : 'border-slate-200/80 dark:border-slate-800' }} p-5 shadow-xs relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider {{ $isOwed ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' }}">
                        Closing AR Balance
                    </span>
                    <div class="p-2 rounded-xl {{ $isOwed ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' }} group-hover:scale-110 transition-transform">
                        @if($isOwed)
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @endif
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black {{ $isOwed ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} tracking-tight">
                        {{ $fmtMoney($kpis['balance']) }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs">
                        @if($isOwed)
                            <span class="inline-flex items-center gap-1 font-bold text-rose-600 dark:text-rose-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <span>Outstanding Customer Debt</span>
                            </span>
                        @elseif($isZero)
                            <span class="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Account Fully Settled</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 font-bold text-blue-600 dark:text-blue-400">
                                <span>Prepaid / Customer Credit Balance</span>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1 {{ $isOwed ? 'bg-gradient-to-r from-rose-500 to-red-600' : 'bg-gradient-to-r from-emerald-500 to-teal-500' }}"></div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 5. CHRONOLOGICAL AR STATEMENT LEDGER TABLE                                --}}
        {{-- ========================================================================= --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Chronological Accounts Receivable Ledger</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Invoiced billings, client settlements & progressive balance progression</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2.5 py-1 rounded-full font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                        {{ $events->count() }} Ledger Records
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-bold">
                            <th scope="col" class="py-3 px-4 text-left">Date</th>
                            <th scope="col" class="py-3 px-4 text-left">Event Type</th>
                            <th scope="col" class="py-3 px-4 text-left">Reference / Document</th>
                            <th scope="col" class="py-3 px-4 text-left">Payment Channel</th>
                            <th scope="col" class="py-3 px-4 text-right">Debit / Credit Impact</th>
                            <th scope="col" class="py-3 px-4 text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                        @forelse($events as $row)
                            @php
                                $isSale = $row['type'] === 'Sale';
                                $isPayment = $row['type'] === 'Payment';
                                $isRefund = $row['type'] === 'Customer Refund';

                                $badgeClass = match($row['type']) {
                                    'Sale'            => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/80 dark:border-blue-800',
                                    'Payment'         => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800',
                                    'Customer Refund' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800',
                                    default           => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                {{-- Date --}}
                                <td class="py-3.5 px-4 whitespace-nowrap font-medium text-slate-700 dark:text-slate-300">
                                    {{ $row['date']->format('M d, Y') }}
                                    <span class="block text-[10px] text-slate-400 font-normal">{{ $row['date']->format('H:i') !== '00:00' ? $row['date']->format('h:i A') : '' }}</span>
                                </td>

                                {{-- Type --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-semibold {{ $badgeClass }}">
                                        @if($isSale)
                                            <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                        @elseif($isPayment)
                                            <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            <svg class="w-3 h-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                        @endif
                                        <span>{{ $row['type'] }}</span>
                                    </span>
                                </td>

                                {{-- Reference & Notes --}}
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        @if(!empty($row['link']))
                                            <a href="{{ $row['link'] }}" class="text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1">
                                                <span>{{ $row['ref'] }}</span>
                                                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        @else
                                            <span>{{ $row['ref'] }}</span>
                                        @endif
                                    </div>
                                    @if(!empty($row['note']))
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1 italic">
                                            {{ $row['note'] }}
                                        </p>
                                    @endif
                                </td>

                                {{-- Method --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $row['method'] }}
                                    </span>
                                </td>

                                {{-- Amount Impact --}}
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    @if($row['amount'] > 0)
                                        <span class="font-bold text-slate-900 dark:text-white">
                                            + {{ $fmtMoney($row['amount']) }}
                                        </span>
                                    @else
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                            - {{ $fmtMoney(abs($row['amount'])) }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Running Balance --}}
                                <td class="py-3.5 px-4 text-right whitespace-nowrap font-mono font-bold">
                                    @if($row['balance'] > 0)
                                        <span class="text-rose-600 dark:text-rose-400">
                                            {{ $fmtMoney($row['balance']) }}
                                        </span>
                                    @elseif(abs($row['balance']) < 0.01)
                                        <span class="text-slate-400 dark:text-slate-500">
                                            0.00
                                        </span>
                                    @else
                                        <span class="text-emerald-600 dark:text-emerald-400">
                                            {{ $fmtMoney($row['balance']) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">No transactions recorded</span>
                                        <span class="text-xs text-slate-400 max-w-sm">No sales or customer payments found for {{ $customer->name }} during this period. Try clearing date filters.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($events->count())
                <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <div>
                        Statement Period: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $from ? Carbon::parse($from)->format('M d, Y') : 'Start' }} to {{ $to ? Carbon::parse($to)->format('M d, Y') : 'Today' }}</span>
                    </div>
                    <div class="font-semibold">
                        Final Balance: <span class="{{ $kpis['balance'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $fmtMoney($kpis['balance']) }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- 6. RETURNS AUDIT (INFO ONLY)                                              --}}
        {{-- ========================================================================= --}}
        @if($returns->count())
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Customer Returns &amp; Restocking Audit</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Informational record of items returned from customer</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                        {{ $returns->count() }} Return Documents
                    </span>
                </div>

                <div class="overflow-x-auto border border-slate-200 dark:border-slate-700/80 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase font-semibold">
                            <tr>
                                <th class="py-2.5 px-4 text-left">Date</th>
                                <th class="py-2.5 px-4 text-left">Sale Invoice Ref</th>
                                <th class="py-2.5 px-4 text-left">Channel</th>
                                <th class="py-2.5 px-4 text-right">Returned Goods Value</th>
                                <th class="py-2.5 px-4 text-right">Cash Refund Disbursed</th>
                                <th class="py-2.5 px-4 text-right">Items Restocked</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($returns as $ret)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="py-2.5 px-4 text-slate-700 dark:text-slate-300">
                                        {{ $ret->return_date ? Carbon::parse($ret->return_date)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="py-2.5 px-4 font-semibold text-emerald-600 dark:text-emerald-400">
                                        <a href="{{ route('sales.show', $ret->sale_id) }}" class="hover:underline flex items-center gap-1">
                                            <span>Sale #{{ $ret->sale_id }}</span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </td>
                                    <td class="py-2.5 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            {{ strtoupper($ret->payment_channel ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-medium">
                                        {{ $fmtMoney($ret->total_amount ?? 0) }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-bold text-amber-600 dark:text-amber-400">
                                        {{ $fmtMoney($ret->refund_amount ?? 0) }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-semibold">
                                        {{ $ret->items_count ?? (isset($ret->items) ? $ret->items->count() : 0) }} units
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Note: Cash refunds paid to customer are already reconciled directly via the main Accounts Receivable ledger.</span>
                </div>
            </div>
        @endif

    @else
        {{-- ========================================================================= --}}
        {{-- 7. EMPTY STATE: NO CUSTOMER SELECTED                                     --}}
        {{-- ========================================================================= --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-8 sm:p-12 shadow-xs text-center space-y-6">
            <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-100 dark:border-emerald-800/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-inner">
                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>

            <div class="max-w-md mx-auto space-y-2">
                <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Select a Customer to Review Statement</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Choose a client from the dropdown above or pick from the registered customers below to inspect their real-time billing history and outstanding receivables.
                </p>
            </div>

            {{-- Quick customer selection pills --}}
            @if($customers->count())
                <div class="max-w-2xl mx-auto pt-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-3">Quick Pick Active Customers:</span>
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        @foreach($customers->take(8) as $cust)
                            <a href="{{ route('reports.customers.statement', ['customer_id' => $cust->id]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 dark:bg-slate-700/60 dark:hover:bg-slate-700 dark:hover:text-emerald-400 border border-slate-200/60 dark:border-slate-700 transition">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>{{ $cust->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Value Highlights --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto pt-6 border-t border-slate-100 dark:border-slate-800 text-left">
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                    <div class="flex items-center gap-2 font-bold text-xs text-slate-800 dark:text-slate-200 mb-1">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Real-Time AR Balance</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        Automatic recalculation of customer debt, advance deposits, and settlement status.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                    <div class="flex items-center gap-2 font-bold text-xs text-slate-800 dark:text-slate-200 mb-1">
                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Audit Timeline</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        Step-by-step transaction logs linking to original sales receipts and payment methods.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                    <div class="flex items-center gap-2 font-bold text-xs text-slate-800 dark:text-slate-200 mb-1">
                        <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>DomPDF Export</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        Generate branded, audit-compliant PDF statements with one click to send directly to clients.
                    </p>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
