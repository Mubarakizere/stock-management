@extends('layouts.app')
@section('title', 'Stock Movements')

@section('content')
@php
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);

    $isPaginated = $movements instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        // Delete Confirmation Modals
        softDeleteOpen: false,
        softDeleteUrl: '',
        softDeleteId: '',
        softDeleteProduct: '',
        softDeleteDirection: '',
        softDeleteQty: '',

        forceDeleteOpen: false,
        forceDeleteUrl: '',
        forceDeleteId: '',
        forceDeleteProduct: '',

        confirmSoftDelete(url, id, product, direction, qty) {
            this.softDeleteUrl = url;
            this.softDeleteId = id;
            this.softDeleteProduct = product;
            this.softDeleteDirection = direction;
            this.softDeleteQty = qty;
            this.softDeleteOpen = true;
        },

        confirmForceDelete(url, id, product) {
            this.forceDeleteUrl = url;
            this.forceDeleteId = id;
            this.forceDeleteProduct = product;
            this.forceDeleteOpen = true;
        }
     }">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Dashboard</a>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-800 dark:text-slate-200 font-medium">Stock Movements Ledger</span>
            </nav>

            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Stock Movements
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    Audit Ledger
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                    {{ number_format($movements->total()) }} Records
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Real-time inventory audit trail tracking multi-channel purchases, sales, manufacturing runs, returns, and manual adjustments.
            </p>
        </div>

        {{-- Action Buttons --}}
        @can('stock.view')
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('stock.history.export.csv', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Export CSV</span>
                </a>

                <a href="{{ route('stock.history.export.pdf', request()->query()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span>Export PDF</span>
                </a>

                @can('products.edit')
                    <a href="{{ route('products.index') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-teal-600 text-white hover:bg-teal-500 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        <span>Inventory Catalog</span>
                    </a>
                @endcan
            </div>
        @endcan
    </div>

    {{-- Session Notifications --}}
    @if(session('success'))
        <div class="rounded-xl bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 p-4 text-sm text-teal-800 dark:text-teal-200 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-teal-500/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-teal-600 dark:text-teal-400 hover:opacity-75">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    @cannot('stock.view')
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/80 bg-amber-50/50 dark:bg-amber-950/30 p-6 text-center">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-amber-900 dark:text-amber-200">Access Restricted</h3>
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-1 max-w-sm mx-auto">
                You do not possess the required permissions to inspect stock movements and audit records.
            </p>
        </div>
    @else

        {{-- 4-KPI Executive Deck --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: Total Inflow --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Inbound</span>
                    <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums">
                        +{{ $fmt2($totals['in'] ?? 0) }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">units received</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Valuation:</span>
                    <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">
                        RWF {{ $fmt2($totals['in_cost'] ?? 0) }}
                    </span>
                </div>
            </div>

            {{-- Card 2: Total Outflow --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Outbound</span>
                    <span class="p-2 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 tabular-nums">
                        -{{ $fmt2($totals['out'] ?? 0) }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">units depleted</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Valuation:</span>
                    <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">
                        RWF {{ $fmt2($totals['out_cost'] ?? 0) }}
                    </span>
                </div>
            </div>

            {{-- Card 3: Net Delta --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Net Movement Delta</span>
                    <span class="p-2 rounded-xl {{ ($totals['net'] ?? 0) >= 0 ? 'bg-teal-500/10 text-teal-600 dark:text-teal-400' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight tabular-nums {{ ($totals['net'] ?? 0) >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ ($totals['net'] ?? 0) >= 0 ? '+' : '' }}{{ $fmt2($totals['net'] ?? 0) }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">net inventory units</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Valuation Delta:</span>
                    <span class="font-mono font-semibold {{ ($totals['net_cost'] ?? 0) >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600 dark:text-rose-400' }}">
                        RWF {{ $fmt2($totals['net_cost'] ?? 0) }}
                    </span>
                </div>
            </div>

            {{-- Card 4: Operation Count --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Filtered Operations</span>
                    <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                        {{ number_format($movements->total()) }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">transactions</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Active Filters:</span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-semibold">
                        {{ request()->hasAny(['product_id', 'type', 'origin', 'from_date', 'to_date', 'q']) ? 'Applied' : 'All-time' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Inventory Source Flow Strip --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>Transaction Origin Flow Distribution</span>
                </span>
                <span class="text-[11px] text-slate-400">Quantities for current filter set</span>
            </div>

            <div class="flex flex-wrap gap-2 pt-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Purchases (IN):</span>
                    <strong class="font-mono font-bold">{{ $fmt2($breakdown['in_purchases'] ?? 0) }}</strong>
                </span>

                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-rose-50 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    <span>Sales (OUT):</span>
                    <strong class="font-mono font-bold">{{ $fmt2($breakdown['out_sales'] ?? 0) }}</strong>
                </span>

                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Production (IN):</span>
                    <strong class="font-mono font-bold">{{ $fmt2($breakdown['in_production'] ?? 0) }}</strong>
                </span>

                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Production (OUT):</span>
                    <strong class="font-mono font-bold">{{ $fmt2($breakdown['out_production'] ?? 0) }}</strong>
                </span>

                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-blue-50 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    <span>Adjustments (±):</span>
                    <strong class="font-mono font-bold">{{ $fmt2($breakdown['adjustments'] ?? 0) }}</strong>
                </span>

                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-sky-50 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                    <span>Customer Returns:</span>
                    <strong class="font-mono font-bold">{{ $fmt2($breakdown['in_sale_returns'] ?? 0) }}</strong>
                </span>

                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Supplier Returns:</span>
                    <strong class="font-mono font-bold">{{ $fmt2($breakdown['out_returns'] ?? 0) }}</strong>
                </span>

                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-purple-50 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                    <span>Loans & Returns:</span>
                    <strong class="font-mono font-bold">{{ $fmt2(($breakdown['out_loans'] ?? 0) + ($breakdown['in_loan_returns'] ?? 0)) }}</strong>
                </span>
            </div>
        </div>

        {{-- Interactive Filter Toolbar --}}
        <form method="GET" action="{{ route('stock.history') }}"
              class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-4">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                {{-- Product Search / Select --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Inventory Item
                    </label>
                    <select name="product_id"
                            class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <option value="">All Products & Materials</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" @selected(request('product_id') == $prod->id)>
                                {{ $prod->name }} ({{ ($prod->type ?? '') === 'raw_material' ? 'Raw Mat' : 'Finished' }} · {{ $prod->unit ?? 'pcs' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Movement Type (In / Out) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Flow Direction
                    </label>
                    <select name="type"
                            class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <option value="">All Directions</option>
                        <option value="in" @selected(request('type') == 'in')>Inbound (+ IN)</option>
                        <option value="out" @selected(request('type') == 'out')>Outbound (- OUT)</option>
                    </select>
                </div>

                {{-- Origin Type --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Origin Source
                    </label>
                    <select name="origin"
                            class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <option value="">All Origins</option>
                        <option value="purchase" @selected(request('origin') === 'purchase')>Purchase (IN)</option>
                        <option value="sale" @selected(request('origin') === 'sale')>Sale (OUT)</option>
                        <option value="production" @selected(request('origin') === 'production')>Production Run</option>
                        <option value="adjustment" @selected(request('origin') === 'adjustment')>Stock Adjustment (±)</option>
                        <option value="sale_return" @selected(request('origin') === 'sale_return')>Customer Return (IN)</option>
                        <option value="purchase_return" @selected(request('origin') === 'purchase_return')>Supplier Return (OUT)</option>
                        <option value="item_loan" @selected(request('origin') === 'item_loan')>Item Loan (OUT)</option>
                        <option value="item_loan_return" @selected(request('origin') === 'item_loan_return')>Loan Return (IN)</option>
                    </select>
                </div>

                {{-- From Date --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Date From
                    </label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                           class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                </div>

                {{-- To Date --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Date To
                    </label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                           class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-4">
                    @can('stock.delete')
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700 dark:text-slate-300">
                            <input type="checkbox" name="show_deleted" value="1" @checked(request('show_deleted'))
                                   class="rounded border-slate-300 dark:border-slate-600 text-teal-600 focus:ring-teal-500">
                            <span>Show Trashed / Deleted Records</span>
                        </label>
                    @endcan

                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                        <span>Per page:</span>
                        <select name="per_page" onchange="this.form.submit()"
                                class="text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2 py-1">
                            <option value="15" @selected(request('per_page') == 15)>15</option>
                            <option value="25" @selected(request('per_page', 25) == 25)>25</option>
                            <option value="50" @selected(request('per_page') == 50)>50</option>
                            <option value="100" @selected(request('per_page') == 100)>100</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center">
                    @if(request()->hasAny(['product_id', 'type', 'origin', 'from_date', 'to_date', 'show_deleted', 'q']))
                        <a href="{{ route('stock.history') }}"
                           class="px-3.5 py-1.5 text-xs font-semibold rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition">
                            Clear Filters
                        </a>
                    @endif

                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 transition shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span>Apply Filters</span>
                    </button>
                </div>
            </div>
        </form>

        {{-- Ledger Table Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            @include('stock_movements._table', ['movements' => $movements])

            {{-- Pagination Footer --}}
            @if($isPaginated)
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $movements->withQueryString()->links() }}
                </div>
            @endif
        </div>

    @endcannot

    {{-- Soft Delete Modal --}}
    @can('stock.delete')
    <div x-show="softDeleteOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="softDeleteOpen = false">
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xl p-6"
             @click.outside="softDeleteOpen = false">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Trash Stock Movement?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        Are you sure you want to move stock movement record <strong class="text-slate-800 dark:text-slate-200" x-text="'#MV-' + softDeleteId"></strong> for <strong class="text-slate-800 dark:text-slate-200" x-text="softDeleteProduct"></strong> (<span class="font-mono font-bold" x-text="softDeleteQty"></span>) to trash?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-400">
                This record can be reviewed or restored later using the <em>Show Trashed Records</em> filter.
            </div>

            <div class="mt-6 flex items-center justify-end gap-2.5">
                <button type="button"
                        @click="softDeleteOpen = false"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Cancel
                </button>
                <form :action="softDeleteUrl" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-rose-600 text-white hover:bg-rose-500 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Move to Trash</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Force Delete Modal --}}
    <div x-show="forceDeleteOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
         @keydown.escape.window="forceDeleteOpen = false">
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-rose-200/80 dark:border-rose-900 shadow-2xl p-6"
             @click.outside="forceDeleteOpen = false">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-rose-600 dark:text-rose-400">Permanently Purge Movement?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        Are you sure you want to permanently erase stock movement <strong class="text-slate-800 dark:text-slate-200" x-text="'#MV-' + forceDeleteId"></strong> for <strong class="text-slate-800 dark:text-slate-200" x-text="forceDeleteProduct"></strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300 font-medium">
                ⚠️ WARNING: This action cannot be reversed. This audit entry will be permanently eliminated from the database.
            </div>

            <div class="mt-6 flex items-center justify-end gap-2.5">
                <button type="button"
                        @click="forceDeleteOpen = false"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Keep in Trash
                </button>
                <form :action="forceDeleteUrl" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-rose-700 text-white hover:bg-rose-600 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Purge Permanently</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection
