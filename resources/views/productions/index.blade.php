@extends('layouts.app')
@section('title', 'Production Runs & Batch Lots')

@section('content')
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $isPaginated = $productions instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        selectedIds: [],
        allSelected: false,
        searchQuery: '{{ request('q', '') }}',
        productId: '{{ request('product_id', '') }}',
        expiryStatus: '{{ request('expiry_status', '') }}',
        yieldStatus: '{{ request('yield_status', '') }}',
        perPage: '{{ request('per_page', 20) }}',
        isLoading: false,

        // Single Reverse/Delete Modal
        reverseModalOpen: false,
        reverseActionUrl: '',
        reverseRunId: '',
        reverseProductName: '',
        reverseQuantity: 0,
        reverseUnit: '',

        // Bulk Reverse/Delete Modal
        bulkReverseModalOpen: false,

        toggleSelectAll(checked) {
            this.allSelected = checked;
            if (checked) {
                const checkboxes = document.querySelectorAll('#productions-table-container input[type=checkbox][value]');
                this.selectedIds = Array.from(checkboxes).map(cb => cb.value);
            } else {
                this.selectedIds = [];
            }
        },

        confirmReverse(url, runId, productName, quantity, unit) {
            this.reverseActionUrl = url;
            this.reverseRunId = runId;
            this.reverseProductName = productName;
            this.reverseQuantity = quantity;
            this.reverseUnit = unit;
            this.reverseModalOpen = true;
        },

        async fetchData() {
            this.isLoading = true;
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('q', this.searchQuery);
            if (this.productId) params.set('product_id', this.productId);
            if (this.expiryStatus) params.set('expiry_status', this.expiryStatus);
            if (this.yieldStatus) params.set('yield_status', this.yieldStatus);
            if (this.perPage) params.set('per_page', this.perPage);

            try {
                const resp = await fetch(`{{ route('productions.index') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    const container = document.getElementById('productions-table-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
                    }
                    const paginator = document.getElementById('productions-pagination-container');
                    if (paginator && data.pagination !== undefined) {
                        paginator.innerHTML = data.pagination;
                    }

                    if (data.stats) {
                        const elRuns = document.getElementById('stat-total-runs');
                        if (elRuns) elRuns.textContent = data.stats.total_runs;
                        const elProd = document.getElementById('stat-total-produced');
                        if (elProd) elProd.textContent = data.stats.total_produced;
                    }

                    this.selectedIds = [];
                    this.allSelected = false;
                } else {
                    window.location.href = `{{ route('productions.index') }}?${params.toString()}`;
                }
            } catch (e) {
                window.location.href = `{{ route('productions.index') }}?${params.toString()}`;
            } finally {
                this.isLoading = false;
            }
        }
     }">

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Manufacturing Operations</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-0.5 flex items-center gap-2.5">
                <span>Production Runs &amp; Batch Lots</span>
                <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 border border-teal-200/60 dark:border-teal-800/60">
                    Traceability &amp; Yield
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Monitor finished goods manufacturing batches, lot expiration dates, and Yield vs Scrap variance.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('recipes.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <span>BOM Recipes</span>
            </a>

            <a href="{{ route('reports.manufacturing') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span>Manufacturing Analytics</span>
            </a>

            @can('products.edit')
                <a href="{{ route('productions.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>+ New Production Batch</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- 4 Executive Manufacturing KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Runs & Output --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Output</span>
                <span class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white" id="stat-total-produced">
                    {{ $fmt2($stats['total_produced'] ?? 0) }}
                </div>
                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ $fmt0($stats['total_runs'] ?? 0) }} completed batches
                </div>
            </div>
        </div>

        {{-- Average Yield Efficiency --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Yield Efficiency</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    {{ $stats['avg_yield'] ?? 100 }}%
                </div>
                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Average recipe yield rate
                </div>
            </div>
        </div>

        {{-- Total Scrap Loss --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Scrap &amp; Shrinkage</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-rose-600 dark:text-rose-400">
                    {{ $fmt2($stats['total_scrap_qty'] ?? 0) }}
                </div>
                <div class="mt-0.5 text-xs font-mono text-slate-500 dark:text-slate-400">
                    {{ $fmt2($stats['total_scrap_cost'] ?? 0) }} RWF loss
                </div>
            </div>
        </div>

        {{-- Batch Shelf-Life Watch --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Shelf-Life Watch</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold font-mono tracking-tight text-amber-600 dark:text-amber-400">
                        {{ $stats['expiring_soon_count'] ?? 0 }}
                    </span>
                    @if(($stats['expired_count'] ?? 0) > 0)
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300">
                            {{ $stats['expired_count'] }} Expired
                        </span>
                    @endif
                </div>
                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Batches expiring &le; 30 days
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        {{-- Segmented Yield / Expiry Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs">
            <button type="button"
                    @click="yieldStatus = ''; expiryStatus = ''; fetchData();"
                    :class="!yieldStatus && !expiryStatus ? 'bg-white dark:bg-slate-900 text-teal-600 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-medium'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer">
                All Batches
            </button>
            <button type="button"
                    @click="yieldStatus = 'optimal'; expiryStatus = ''; fetchData();"
                    :class="yieldStatus === 'optimal' ? 'bg-white dark:bg-slate-900 text-emerald-600 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-medium'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Optimal Yield (&ge;95%)</span>
            </button>
            <button type="button"
                    @click="yieldStatus = 'high_waste'; expiryStatus = ''; fetchData();"
                    :class="yieldStatus === 'high_waste' ? 'bg-white dark:bg-slate-900 text-rose-600 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-medium'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span>High Scrap (&lt;85%)</span>
            </button>
            <button type="button"
                    @click="expiryStatus = 'expiring_soon'; yieldStatus = ''; fetchData();"
                    :class="expiryStatus === 'expiring_soon' ? 'bg-white dark:bg-slate-900 text-amber-600 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-medium'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Expiring Soon (&le;30d)</span>
            </button>
        </div>

        {{-- Product Filter & Search Input --}}
        <div class="flex flex-wrap items-center gap-2">
            <select x-model="productId"
                    @change="fetchData()"
                    class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-teal-500 focus:outline-none">
                <option value="">All Products</option>
                @foreach ($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>

            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.350ms="fetchData()"
                       placeholder="Search batch #, product..."
                       class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0"
         x-cloak
         class="p-3 bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 rounded-xl flex items-center justify-between text-xs">
        <span class="font-semibold text-teal-800 dark:text-teal-300">
            <strong x-text="selectedIds.length"></strong> production run(s) selected
        </span>
        <button type="button"
                @click="bulkReverseModalOpen = true"
                class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition">
            Reverse Selected Batches
        </button>
    </div>

    {{-- Table Container --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden relative">
        <div x-show="isLoading"
             x-cloak
             class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-10">
            <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white dark:bg-slate-800 shadow-md text-xs font-semibold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                <svg class="animate-spin w-4 h-4 text-teal-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Loading production batches...</span>
            </div>
        </div>

        <div id="productions-table-container">
            @include('productions._table', ['productions' => $productions])
        </div>

        <div id="productions-pagination-container" class="p-4 border-t border-slate-100 dark:border-slate-800">
            @if ($isPaginated && $productions->hasPages())
                {{ $productions->links() }}
            @endif
        </div>
    </div>

    {{-- Reverse Run Modal --}}
    <div x-show="reverseModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="reverseModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6 space-y-4">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Reverse Production Batch</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">This will restore consumed raw materials.</p>
                    </div>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-300">
                    Are you sure you want to reverse batch run for <strong x-text="reverseProductName"></strong>? This will deduct <strong x-text="reverseQuantity + ' ' + reverseUnit"></strong> from finished goods and restore the raw materials consumed.
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            @click="reverseModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <form :action="reverseActionUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-xs font-bold rounded-xl text-white bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-500/20 transition">
                            Confirm Reversal
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
