@extends('layouts.app')
@section('title', 'Bills of Materials (BOM) & Dynamic Margin Guard')

@section('content')
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $isPaginated = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        searchQuery: '{{ request('q', '') }}',
        categoryId: '{{ request('category_id', '') }}',
        recipeStatus: '{{ request('status', '') }}',
        marginStatus: '{{ request('margin_status', '') }}',
        basis: '{{ request('basis', 'wac') }}',
        perPage: '{{ request('per_page', 20) }}',
        isLoading: false,

        // Clear Recipe Modal
        clearModalOpen: false,
        clearActionUrl: '',
        clearProductName: '',
        clearIngredientCount: 0,

        // Sync Modal
        syncModalOpen: false,
        syncActionUrl: '',
        syncProductName: '',
        syncCurrentCost: 0,
        syncNewCost: 0,
        syncTargetMargin: 25,
        syncSuggestedPrice: 0,
        adjustPrice: false,

        confirmClear(url, productName, count) {
            this.clearActionUrl = url;
            this.clearProductName = productName;
            this.clearIngredientCount = count;
            this.clearModalOpen = true;
        },

        confirmSync(url, productName, currentCost, newCost, targetMargin, suggestedPrice) {
            this.syncActionUrl = url;
            this.syncProductName = productName;
            this.syncCurrentCost = currentCost;
            this.syncNewCost = newCost;
            this.syncTargetMargin = targetMargin || 25;
            this.syncSuggestedPrice = suggestedPrice;
            this.adjustPrice = false;
            this.syncModalOpen = true;
        },

        async fetchData() {
            this.isLoading = true;
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('q', this.searchQuery);
            if (this.categoryId) params.set('category_id', this.categoryId);
            if (this.recipeStatus) params.set('status', this.recipeStatus);
            if (this.marginStatus) params.set('margin_status', this.marginStatus);
            if (this.basis) params.set('basis', this.basis);
            if (this.perPage) params.set('per_page', this.perPage);

            try {
                const resp = await fetch(`{{ route('recipes.index') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    const container = document.getElementById('recipes-table-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
                    }
                    const paginator = document.getElementById('recipes-pagination-container');
                    if (paginator && data.pagination !== undefined) {
                        paginator.innerHTML = data.pagination;
                    }

                    if (data.stats) {
                        const elTotal = document.getElementById('stat-total-products');
                        if (elTotal) elTotal.textContent = data.stats.total_products;
                        const elConfig = document.getElementById('stat-configured');
                        if (elConfig) elConfig.textContent = data.stats.configured_count;
                        const elHealthy = document.getElementById('stat-healthy');
                        if (elHealthy) elHealthy.textContent = data.stats.healthy_count;
                        const elAlerts = document.getElementById('stat-alerts');
                        if (elAlerts) elAlerts.textContent = data.stats.margin_alerts;
                    }
                } else {
                    window.location.href = `{{ route('recipes.index') }}?${params.toString()}`;
                }
            } catch (e) {
                window.location.href = `{{ route('recipes.index') }}?${params.toString()}`;
            } finally {
                this.isLoading = false;
            }
        }
     }">

    {{-- ========================================================================= --}}
    {{-- 1. HEADER SECTION & TOP ACTIONS                                           --}}
    {{-- ========================================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Manufacturing Cost Accounting</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-0.5 flex items-center gap-2.5">
                <span>Bills of Materials &amp; Margin Guard</span>
                <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/60">
                    Dynamic BOM
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Real-time manufacturing unit costs calculated from Weighted Average Raw Material costs with automated Profit Margin Guard alerts.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Basis Switcher --}}
            <div class="flex items-center bg-white dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700 rounded-xl p-1 text-xs">
                <button type="button"
                        @click="basis = 'wac'; fetchData();"
                        :class="basis === 'wac' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                        class="px-2.5 py-1 rounded-lg transition"
                        title="Weighted Average Cost from stock-in history">
                    WAC Costing
                </button>
                <button type="button"
                        @click="basis = 'latest_po'; fetchData();"
                        :class="basis === 'latest_po' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                        class="px-2.5 py-1 rounded-lg transition"
                        title="Latest PO Purchase Price">
                    Latest PO
                </button>
            </div>

            <form action="{{ route('recipes.recalculate-all') }}" method="POST">
                @csrf
                <input type="hidden" name="basis" :value="basis">
                <button type="submit"
                        onclick="return confirm('Recalculate dynamic BOM costs across all finished products using current raw material costs?');"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Recalculate All BOMs</span>
                </button>
            </form>

            <a href="{{ route('productions.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>Production Batches</span>
            </a>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. 4-KPI METRIC DECK                                                      --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Products --}}
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Products Catalog</p>
                <div class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-0.5" id="stat-total-products">
                    {{ $stats['total_products'] }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Finished goods</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>

        {{-- Card 2: Recipes Configured --}}
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">BOM Configured</p>
                <div class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-0.5" id="stat-configured">
                    {{ $stats['configured_count'] }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $stats['coverage_pct'] }}% recipes active
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
        </div>

        {{-- Card 3: Healthy Margins --}}
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Healthy Margins</p>
                <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5" id="stat-healthy">
                    {{ $stats['healthy_count'] }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    &ge; target margin (Avg: {{ $stats['avg_margin_pct'] }}%)
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        {{-- Card 4: Margin Alerts --}}
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Margin Guard Alerts</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-2xl font-bold font-mono text-rose-600 dark:text-rose-400" id="stat-alerts">
                        {{ $stats['margin_alerts'] }}
                    </span>
                    @if($stats['critical_count'] > 0)
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300">
                            {{ $stats['critical_count'] }} Critical
                        </span>
                    @endif
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Under-margin products</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3. FILTER TOOLBAR                                                         --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        {{-- Margin Guard Status Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs">
            <button type="button"
                    @click="marginStatus = ''; fetchData();"
                    :class="!marginStatus ? 'bg-white dark:bg-slate-900 text-indigo-600 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-medium'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer">
                All Products ({{ $stats['total_products'] }})
            </button>
            <button type="button"
                    @click="marginStatus = 'healthy'; fetchData();"
                    :class="marginStatus === 'healthy' ? 'bg-white dark:bg-slate-900 text-emerald-600 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-medium'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Healthy ({{ $stats['healthy_count'] }})</span>
            </button>
            <button type="button"
                    @click="marginStatus = 'alerts'; fetchData();"
                    :class="marginStatus === 'alerts' ? 'bg-white dark:bg-slate-900 text-rose-600 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-medium'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                <span>Margin Alerts ({{ $stats['margin_alerts'] }})</span>
            </button>
        </div>

        {{-- Search & Category Filter --}}
        <div class="flex flex-wrap items-center gap-2">
            <select x-model="categoryId"
                    @change="fetchData()"
                    class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
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
                       placeholder="Search product or ID..."
                       class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 4. TABLE CONTAINER                                                        --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden relative">
        <div x-show="isLoading"
             x-cloak
             class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-10">
            <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white dark:bg-slate-800 shadow-md text-xs font-semibold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                <svg class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Evaluating BOM Margins...</span>
            </div>
        </div>

        <div id="recipes-table-container">
            @include('recipes._table', ['products' => $products, 'basis' => $basis])
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 5. 1-CLICK SYNC BOM COST & MARGIN RE-ALIGN MODAL                          --}}
    {{-- ========================================================================= --}}
    <div x-show="syncModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="syncModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Sync BOM Unit Cost</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" x-text="syncProductName"></p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Current Cost Price:</span>
                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="syncCurrentCost.toFixed(2) + ' RWF'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Calculated BOM Cost (WAC):</span>
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="syncNewCost.toFixed(2) + ' RWF'"></span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-slate-200 dark:border-slate-700">
                        <span class="text-slate-500">Target Gross Margin:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="syncTargetMargin + '%'"></span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 flex items-start gap-2.5">
                    <input type="checkbox" id="modalAdjustPrice" x-model="adjustPrice" class="mt-0.5 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="modalAdjustPrice" class="text-xs text-indigo-900 dark:text-indigo-300 leading-snug">
                        Also re-align selling price to <strong class="font-mono" x-text="syncSuggestedPrice.toFixed(2) + ' RWF'"></strong> to restore the <span x-text="syncTargetMargin"></span>% target margin.
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            @click="syncModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <form :action="syncActionUrl" method="POST">
                        @csrf
                        <input type="hidden" name="adjust_price" :value="adjustPrice ? 1 : 0">
                        <input type="hidden" name="basis" :value="basis">
                        <button type="submit"
                                class="px-4 py-2 text-xs font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition">
                            Apply Synchronization
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
