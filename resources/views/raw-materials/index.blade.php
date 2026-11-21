@extends('layouts.app')
@section('title', 'Raw Materials Catalog')

@section('content')
@php
    use App\Models\Category;
    use Illuminate\Support\Str;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $threshold = (int)($threshold ?? config('inventory.low_stock', 5));

    $allCategories = $categories ?? Category::where('kind', 'raw_material')->orderBy('name')->get();
    $usableCategories = collect($allCategories)
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? '', ['raw_material', 'both']))
        ->values();

    $__coll = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products);

    $isPaginated = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $pageCount   = $isPaginated ? $products->count() : $__coll->count();
    $totalCount  = $isPaginated ? $products->total() : $pageCount;

    $pageUnits   = $__coll->sum(fn($p) => max(0, (float)($p->qty_in ?? 0) - (float)($p->qty_out ?? 0)));
    $pageValue   = $__coll->sum(function($p){
        $units = max(0, (float)($p->qty_in ?? 0) - (float)($p->qty_out ?? 0));
        return $units * (float)($p->cost_price ?: $p->price ?: 0);
    });

    $lowStockCount = $__coll->filter(function($p) use ($threshold) {
        $stk = max(0, (float)($p->qty_in ?? 0) - (float)($p->qty_out ?? 0));
        return $stk > 0 && $stk <= $threshold;
    })->count();
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        selectedIds: [],
        allSelected: false,
        searchQuery: '{{ request('search', request('q', '')) }}',
        categoryId: '{{ request('category_id', '') }}',
        stockStatus: '{{ request('stock_status', '') }}',
        perPage: '{{ request('per_page', 20) }}',
        isLoading: false,

        // Single Delete Modal
        deleteModalOpen: false,
        deleteActionUrl: '',
        deleteItemName: '',
        deleteRecipeCount: 0,

        // Bulk Delete Modal
        bulkDeleteModalOpen: false,

        // Quick Stock Adjust Modal
        quickAdjustModalOpen: false,
        adjustProductId: null,
        adjustProductName: '',
        adjustCurrentStock: 0,
        adjustUnit: '',
        adjustType: 'add',
        adjustQty: null,
        adjustNotes: '',
        adjustLoading: false,
        adjustError: '',

        toggleSelectAll(checked) {
            this.allSelected = checked;
            if (checked) {
                const checkboxes = document.querySelectorAll('#raw-materials-table-container input[type=checkbox][value]');
                this.selectedIds = Array.from(checkboxes).map(cb => cb.value);
            } else {
                this.selectedIds = [];
            }
        },

        confirmDelete(url, name, recipeCount = 0) {
            this.deleteActionUrl = url;
            this.deleteItemName = name;
            this.deleteRecipeCount = recipeCount;
            this.deleteModalOpen = true;
        },

        openQuickAdjust(id, name, currentStock, unit) {
            this.adjustProductId = id;
            this.adjustProductName = name;
            this.adjustCurrentStock = currentStock;
            this.adjustUnit = unit;
            this.adjustType = 'add';
            this.adjustQty = null;
            this.adjustNotes = '';
            this.adjustError = '';
            this.quickAdjustModalOpen = true;
        },

        async submitQuickAdjust() {
            if (!this.adjustQty || this.adjustQty <= 0) {
                this.adjustError = 'Please enter a valid adjustment quantity greater than 0.';
                return;
            }
            this.adjustLoading = true;
            this.adjustError = '';

            try {
                const tokenEl = document.querySelector('meta[name=csrf-token]');
                const resp = await fetch(`/products/${this.adjustProductId}/quick-adjust`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenEl ? tokenEl.content : '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        type: this.adjustType,
                        quantity: this.adjustQty,
                        notes: this.adjustNotes
                    })
                });

                const data = await resp.json();

                if (!resp.ok || !data.success) {
                    this.adjustError = data.message || 'Stock adjustment failed.';
                    this.adjustLoading = false;
                    return;
                }

                this.quickAdjustModalOpen = false;
                this.fetchData();
            } catch (err) {
                this.adjustError = 'Network error during adjustment. Please try again.';
            } finally {
                this.adjustLoading = false;
            }
        },

        async fetchData() {
            this.isLoading = true;
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('q', this.searchQuery);
            if (this.categoryId) params.set('category_id', this.categoryId);
            if (this.stockStatus) params.set('stock_status', this.stockStatus);
            if (this.perPage) params.set('per_page', this.perPage);

            try {
                const resp = await fetch(`{{ route('raw-materials.index') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    const container = document.getElementById('raw-materials-table-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
                    }
                    const paginator = document.getElementById('raw-materials-pagination-container');
                    if (paginator && data.pagination !== undefined) {
                        paginator.innerHTML = data.pagination;
                    }

                    if (data.stats) {
                        const elUnits = document.getElementById('stat-total-units');
                        if (elUnits) elUnits.textContent = data.stats.units;
                        const elVal = document.getElementById('stat-total-val');
                        if (elVal) elVal.textContent = data.stats.value;
                    }

                    this.selectedIds = [];
                    this.allSelected = false;
                } else {
                    window.location.href = `{{ route('raw-materials.index') }}?${params.toString()}`;
                }
            } catch (e) {
                window.location.href = `{{ route('raw-materials.index') }}?${params.toString()}`;
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
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Inventory Management</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-0.5">
                Raw Materials Catalog
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Ingredients, packaging, and raw stock consumed in manufacturing operations.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            @can('stock.view')
                <a href="{{ route('stock.history') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Stock History
                </a>
            @endcan

            @can('products.create')
                <a href="{{ route('raw-materials.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Raw Material
                </a>
            @endcan
        </div>
    </div>

    {{-- 4 Primary Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Varieties --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Material Varieties</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white" id="stat-total-count">
                    {{ $fmt0($totalCount) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Active catalog ingredients
                </div>
            </div>
        </div>

        {{-- Units in Stock --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Warehouse Volume</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white" id="stat-total-units">
                    {{ $fmt2($pageUnits) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Cumulative physical stock
                </div>
            </div>
        </div>

        {{-- Valuation at Cost --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Inventory Valuation</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    <span id="stat-total-val">{{ $fmt2($pageValue) }}</span> <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Valued strictly at WAC cost
                </div>
            </div>
        </div>

        {{-- Low Stock Alerts --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Reorder Warnings</span>
                <span class="w-8 h-8 rounded-xl {{ $lowStockCount > 0 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight {{ $lowStockCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }}">
                    {{ $fmt0($lowStockCount) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Materials at or below threshold (≤ {{ $threshold }})
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Filter Toolbar --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm space-y-3">
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            {{-- Search & Category Inputs --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1">
                {{-- Live Search --}}
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           x-model="searchQuery"
                           @input.debounce.350ms="fetchData()"
                           placeholder="Search by material name or #RM-ID..."
                           class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                    <button type="button"
                            x-show="searchQuery"
                            @click="searchQuery = ''; fetchData()"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Category Filter --}}
                <div class="sm:w-56">
                    <select x-model="categoryId"
                            @change="fetchData()"
                            class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <option value="">All Categories</option>
                        @foreach($usableCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Per Page Select --}}
            <div class="flex items-center gap-2 self-end md:self-auto text-xs text-slate-500 dark:text-slate-400">
                <span class="whitespace-nowrap">Show:</span>
                <select x-model="perPage"
                        @change="fetchData()"
                        class="py-1.5 px-2.5 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                    <option value="15">15 rows</option>
                    <option value="25">25 rows</option>
                    <option value="50">50 rows</option>
                    <option value="100">100 rows</option>
                </select>
            </div>
        </div>

        {{-- Stock Health Status Tabs --}}
        <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-xs">
            <span class="text-slate-400 text-[11px] font-medium mr-1 uppercase tracking-wider">Health Status:</span>
            <button type="button"
                    @click="stockStatus = ''; fetchData()"
                    :class="stockStatus === '' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                    class="px-3 py-1 rounded-lg transition">
                All
            </button>

            <button type="button"
                    @click="stockStatus = 'in'; fetchData()"
                    :class="stockStatus === 'in' ? 'bg-emerald-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                    class="px-3 py-1 rounded-lg transition">
                Healthy Stock
            </button>

            <button type="button"
                    @click="stockStatus = 'low'; fetchData()"
                    :class="stockStatus === 'low' ? 'bg-amber-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                    class="px-3 py-1 rounded-lg transition">
                Low Stock (≤ {{ $threshold }})
            </button>

            <button type="button"
                    @click="stockStatus = 'out'; fetchData()"
                    :class="stockStatus === 'out' ? 'bg-rose-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                    class="px-3 py-1 rounded-lg transition">
                Depleted / Out
            </button>

            <div class="ml-auto" x-show="isLoading" x-cloak>
                <div class="inline-flex items-center gap-1.5 text-xs text-teal-600 dark:text-teal-400 font-medium">
                    <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Filtering...
                </div>
            </div>
        </div>
    </div>

    {{-- Floating Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-3"
         class="fixed bottom-6 inset-x-0 z-40 max-w-xl mx-auto px-4">
        <div class="bg-slate-900 dark:bg-slate-800 text-white rounded-2xl shadow-2xl p-3 border border-slate-700/80 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-teal-500 text-slate-950 font-bold text-xs font-mono"
                      x-text="selectedIds.length">
                </span>
                <span class="text-xs font-medium">
                    Raw Material(s) selected
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        @click="selectedIds = []; allSelected = false"
                        class="px-3 py-1.5 text-xs text-slate-300 hover:text-white hover:bg-slate-800 dark:hover:bg-slate-700 rounded-xl transition">
                    Clear
                </button>

                @can('products.delete')
                    <button type="button"
                            @click="bulkDeleteModalOpen = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Raw Materials Catalog Table Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div id="raw-materials-table-container">
            @include('raw-materials._table', ['products' => $products, 'threshold' => $threshold])
        </div>

        {{-- Pagination Container --}}
        <div id="raw-materials-pagination-container" class="p-4 border-t border-slate-100 dark:border-slate-800/80">
            @if($isPaginated)
                {{ $products->links() }}
            @endif
        </div>
    </div>

    {{-- =========================================================================
     | MODALS
     |======================================================================== --}}

    {{-- 1. Single Delete Confirmation Modal --}}
    @can('products.delete')
    <div x-show="deleteModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.outside="deleteModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 space-y-5">

            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Delete Raw Material
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to delete <span class="font-semibold text-slate-900 dark:text-white" x-text="deleteItemName"></span>?
                    </p>
                </div>
            </div>

            <template x-if="deleteRecipeCount > 0">
                <div class="rounded-xl border border-amber-200/80 dark:border-amber-800/80 bg-amber-50/70 dark:bg-amber-950/30 p-3 text-xs text-amber-800 dark:text-amber-300">
                    <div class="font-semibold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Active BOM Recipe Warning</span>
                    </div>
                    <p class="mt-1">
                        This ingredient is used in <span class="font-bold" x-text="deleteRecipeCount"></span> product recipe(s). Deleting it will detach it from all manufacturing bills of materials.
                    </p>
                </div>
            </template>

            <form :action="deleteActionUrl" method="POST" class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                @csrf
                @method('DELETE')
                <button type="button"
                        @click="deleteModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 transition shadow-sm">
                    Confirm Delete
                </button>
            </form>
        </div>
    </div>
    @endcan

    {{-- 2. Bulk Delete Confirmation Modal --}}
    @can('products.delete')
    <div x-show="bulkDeleteModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.outside="bulkDeleteModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 space-y-5">

            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Bulk Delete Raw Materials
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to permanently delete the <span class="font-bold text-slate-900 dark:text-white font-mono" x-text="selectedIds.length"></span> selected raw material(s)? This will remove them from inventory and detach associated recipes.
                    </p>
                </div>
            </div>

            <form action="{{ route('raw-materials.bulk-delete') }}" method="POST" class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>

                <button type="button"
                        @click="bulkDeleteModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 transition shadow-sm">
                    Delete <span x-text="selectedIds.length"></span> Items
                </button>
            </form>
        </div>
    </div>
    @endcan

    {{-- 3. Quick Stock Adjust Modal --}}
    @can('products.edit')
    <div x-show="quickAdjustModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.outside="quickAdjustModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 space-y-4">

            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Quick Stock Adjustment</h3>
                        <p class="text-[11px] text-slate-400" x-text="adjustProductName"></p>
                    </div>
                </div>

                <button type="button" @click="quickAdjustModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="adjustError">
                <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-300 text-xs">
                    <span x-text="adjustError"></span>
                </div>
            </template>

            <div class="space-y-3.5 text-xs">
                {{-- Adjustment Direction Tabs --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Adjustment Action</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                                @click="adjustType = 'add'"
                                :class="adjustType === 'add' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-600 dark:text-emerald-300 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'"
                                class="py-2 px-3 rounded-xl border text-center transition">
                            + Add Intake Stock
                        </button>
                        <button type="button"
                                @click="adjustType = 'remove'"
                                :class="adjustType === 'remove' ? 'bg-rose-50 border-rose-500 text-rose-700 dark:bg-rose-950/40 dark:border-rose-600 dark:text-rose-300 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'"
                                class="py-2 px-3 rounded-xl border text-center transition">
                            - Deduct / Waste Stock
                        </button>
                    </div>
                </div>

                {{-- Current Stock Info --}}
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-[11px]">
                    <span class="text-slate-500">Current Warehouse Balance:</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="`${adjustCurrentStock} ${adjustUnit}`"></span>
                </div>

                {{-- Quantity --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">
                        Adjustment Quantity <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               x-model.number="adjustQty"
                               placeholder="e.g. 10.50"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 font-mono text-[11px]" x-text="adjustUnit"></span>
                    </div>
                </div>

                {{-- Audit Note --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">
                        Reason / Notes (Audit Log)
                    </label>
                    <input type="text"
                           x-model="adjustNotes"
                           placeholder="e.g. Supplier discrepancy, damaged packaging, audit count..."
                           class="w-full py-2 px-3 rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                <button type="button"
                        @click="quickAdjustModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 transition">
                    Cancel
                </button>
                <button type="button"
                        @click="submitQuickAdjust()"
                        :disabled="adjustLoading"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 transition shadow-sm disabled:opacity-50">
                    <span x-show="!adjustLoading">Save Adjustment</span>
                    <span x-show="adjustLoading" class="inline-flex items-center gap-1">
                        <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endcan

</div>
@endsection
