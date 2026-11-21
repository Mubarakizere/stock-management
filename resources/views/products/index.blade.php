@extends('layouts.app')
@section('title', 'Products Catalog')

@section('content')
@php
    use App\Models\Category;
    use Illuminate\Support\Str;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $threshold = (int)($threshold ?? 5);

    $allCategories = $categories ?? Category::orderBy('name')->get();
    $usableCategories = collect($allCategories)
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'product', ['product','both']))
        ->values();

    $__coll = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products);

    $isPaginated = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $pageCount   = $isPaginated ? $products->count() : $__coll->count();
    $totalCount  = $isPaginated ? $products->total() : $pageCount;

    $pageUnits   = $__coll->sum(fn($p) => max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0)));
    $pageValue   = $__coll->sum(function($p){
        $units = max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0));
        return $units * (float)($p->cost_price ?? 0);
    });
    $pageRevenue = $__coll->sum(function($p){
        $units = max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0));
        return $units * (float)($p->price ?? 0);
    });
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        ...bulkSelect(),
        singleDeleteModalOpen: false,
        singleDeleteUrl: '',
        singleDeleteName: '',
        singleDeleteSku: '',
        singleDeleteStock: 0,
        openSingleDeleteModal(url, name, sku, stock) {
            this.singleDeleteUrl = url;
            this.singleDeleteName = name;
            this.singleDeleteSku = sku;
            this.singleDeleteStock = stock;
            this.singleDeleteModalOpen = true;
        }
     }">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span>Inventory</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Finished Goods</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Products Catalog</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Track warehouse inventory, unit margins, weighted average costs (WAC), and reorder thresholds.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Export Report Dropdown --}}
            @can('products.view')
                <div x-data="{ exportOpen: false }" class="relative">
                    <button @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                            class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                        <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Stock PDF</span>
                        <svg class="w-3 h-3 text-slate-400 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="exportOpen" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-52 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl py-1 z-50 text-xs">
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'all']) }}" class="flex items-center gap-2 px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            All Products Catalog
                        </a>
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'low']) }}" class="flex items-center gap-2 px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-amber-700 dark:text-amber-400">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Low Stock (≤ {{ $threshold }})
                        </a>
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'out']) }}" class="flex items-center gap-2 px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-rose-700 dark:text-rose-400">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Out of Stock (Zero Units)
                        </a>
                    </div>
                </div>
            @endcan

            {{-- Stock Movements Link --}}
            @can('stock.view')
                <a href="{{ route('stock.history') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Movements
                </a>
            @endcan

            {{-- Add Product Primary --}}
            @can('products.create')
                <a href="{{ route('products.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Product
                </a>
            @endcan
        </div>
    </div>

    @cannot('products.view')
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Permission Required</p>
            <p class="text-xs text-slate-400 mt-1">You do not have administrative access to view the product inventory.</p>
        </div>
    @else

        {{-- 4 Executive Metric Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Catalog Total --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Products Catalog</span>
                    <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2">
                    <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                        {{ $fmt0($totalCount) }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Active finished goods items
                    </div>
                </div>
            </div>

            {{-- Total Units in Stock --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Units in Stock</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2">
                    <div class="text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                        {{ $fmt0($pageUnits) }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Available warehouse quantity
                    </div>
                </div>
            </div>

            {{-- Inventory Valuation at Cost --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Stock Valuation (at Cost)</span>
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2">
                    <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                        {{ $fmt2($pageValue) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Weighted average holding cost
                    </div>
                </div>
            </div>

            {{-- Potential Gross Revenue --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Potential Gross Sales</span>
                    <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2">
                    <div class="text-2xl font-bold font-mono tracking-tight text-amber-600 dark:text-amber-400">
                        {{ $fmt2($pageRevenue) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Value at retail catalog pricing
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter & Search Toolbar --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
            <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                {{-- Search --}}
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Search Products</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search by name, SKU, or ID..."
                            class="w-full pl-9 pr-8 text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-teal-500 focus:ring-teal-500"
                        >
                        @if(request('search'))
                            <a href="{{ route('products.index', request()->except('search')) }}" class="absolute inset-y-0 right-2.5 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Category --}}
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Category</label>
                    <select name="category_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">All Categories</option>
                        @foreach($usableCategories as $c)
                            <option value="{{ $c->id }}" @selected((string)request('category_id') === (string)$c->id)>
                                {{ $c->name }} {{ $c->code ? "({$c->code})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Stock Status --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Stock Health</label>
                    <select name="stock_status" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">All Levels</option>
                        <option value="in"  @selected(request('stock_status') === 'in')>In Stock (> {{ $threshold }})</option>
                        <option value="low" @selected(request('stock_status') === 'low')>Low Stock (≤ {{ $threshold }})</option>
                        <option value="out" @selected(request('stock_status') === 'out')>Out of Stock (0)</option>
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter
                    </button>

                    @if(request()->anyFilled(['search', 'category_id', 'stock_status']))
                        <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition" title="Clear Filters">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Floating Bulk Selection Action Toolbar --}}
        <div x-show="selectedIds.length > 0"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="fixed bottom-6 inset-x-0 mx-auto max-w-xl z-40 bg-slate-900 dark:bg-slate-800 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2.5 text-xs">
                <span class="w-6 h-6 rounded-lg bg-teal-500/20 text-teal-400 flex items-center justify-center font-bold font-mono text-xs">
                    <span x-text="selectedIds.length"></span>
                </span>
                <span class="font-medium">Products selected for batch operations</span>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="selectedIds = []; allSelected = false"
                        class="px-3 py-1.5 text-xs font-semibold rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition">
                    Clear
                </button>
                @can('products.delete')
                    <button type="button" @click="$store.bulkConfirm.open = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-rose-600 hover:bg-rose-500 text-white shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Batch
                    </button>
                @endcan
            </div>
        </div>

        {{-- Hidden Form for Bulk Delete --}}
        <form id="bulk-delete-form" action="{{ route('products.bulk-delete') }}" method="POST" class="hidden">
            @csrf
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
        </form>

        {{-- Products Table Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                @include('products._table')
            </div>

            {{-- Pagination --}}
            @if($isPaginated && $products->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

    @endcannot

    {{-- ============================================================ --}}
    {{-- MODAL 1: SINGLE DELETE CONFIRMATION MODAL                    --}}
    {{-- ============================================================ --}}
    @can('products.delete')
    <div x-show="singleDeleteModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="singleDeleteModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 ring-1 ring-rose-200 dark:ring-rose-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Delete Product Item
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to permanently delete <strong class="text-slate-800 dark:text-slate-200" x-text="singleDeleteName"></strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Stock & Recipe Cascade Notice
                </div>
                <p class="text-amber-700 dark:text-amber-400 pl-5">
                    Deleting this product will remove its catalog record, stock movements, and manufacturing recipes. Current recorded stock is <strong x-text="singleDeleteStock + ' units'"></strong>.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="singleDeleteModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Keep Product
                </button>
                <form :action="singleDeleteUrl" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-rose-600 hover:bg-rose-500 shadow-sm transition">
                        Confirm Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- MODAL 2: BULK DELETE CONFIRMATION MODAL                     --}}
    {{-- ============================================================ --}}
    @can('products.delete')
    <div x-show="$store.bulkConfirm.open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="$store.bulkConfirm.open = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 ring-1 ring-rose-200 dark:ring-rose-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Delete <span x-text="selectedIds.length"></span> Selected Products
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to delete these products in bulk?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-rose-50/80 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-800 dark:text-rose-300">
                This action will delete all selected products along with their stock movement ledgers, recipes, and production allocations.
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="$store.bulkConfirm.open = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Cancel
                </button>
                <button type="button"
                        @click="$store.bulkConfirm.open = false; document.getElementById('bulk-delete-form').submit()"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-rose-600 hover:bg-rose-500 shadow-sm transition">
                    Confirm Bulk Delete
                </button>
            </div>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- MODAL 3: QUICK STOCK ADJUSTMENT MODAL                       --}}
    {{-- ============================================================ --}}
    @can('products.edit')
    <div x-show="$store.quickAdjust.show"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="$store.quickAdjust.close()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Quick Stock Adjustment</h3>
                        <p class="text-xs text-slate-400" x-text="$store.quickAdjust.productName"></p>
                    </div>
                </div>
                <button type="button" @click="$store.quickAdjust.close()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-4 space-y-4">
                {{-- Current Stock display --}}
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Recorded Warehouse Stock:</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white text-sm" x-text="$store.quickAdjust.currentStock"></span>
                </div>

                {{-- Action Type Switcher --}}
                <div class="grid grid-cols-2 gap-2">
                    <button type="button"
                            @click="$store.quickAdjust.type = 'add'"
                            class="py-2 px-3 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition"
                            :class="$store.quickAdjust.type === 'add'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        + Add Stock
                    </button>
                    <button type="button"
                            @click="$store.quickAdjust.type = 'remove'"
                            class="py-2 px-3 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition"
                            :class="$store.quickAdjust.type === 'remove'
                                ? 'bg-rose-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                        − Remove Stock
                    </button>
                </div>

                {{-- Adjustment Quantity --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Adjustment Quantity <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="1" min="1"
                           x-model.number="$store.quickAdjust.quantity"
                           placeholder="Enter quantity to adjust..."
                           @keydown.enter.prevent="$store.quickAdjust.submit()"
                           class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500">
                </div>

                {{-- Live Projected Balance Preview --}}
                <div class="p-3 rounded-xl text-xs font-medium flex items-center justify-between"
                     :class="$store.quickAdjust.type === 'add' ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300'">
                    <span>Projected Resulting Balance:</span>
                    <span class="font-mono font-bold text-sm"
                          x-text="$store.quickAdjust.type === 'add'
                            ? ($store.quickAdjust.currentStock + ($store.quickAdjust.quantity || 0))
                            : Math.max(0, $store.quickAdjust.currentStock - ($store.quickAdjust.quantity || 0))"></span>
                </div>

                {{-- Reason / Audit Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reason / Notes (Optional)</label>
                    <textarea rows="2"
                              x-model="$store.quickAdjust.notes"
                              placeholder="e.g. Physical inventory reconciliation, broken bottle, expiry..."
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"></textarea>
                </div>

                {{-- Error Banner --}}
                <template x-if="$store.quickAdjust.error">
                    <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs" x-text="$store.quickAdjust.error"></div>
                </template>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="$store.quickAdjust.close()" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="button"
                            @click="$store.quickAdjust.submit()"
                            :disabled="$store.quickAdjust.loading || !$store.quickAdjust.quantity || $store.quickAdjust.quantity <= 0"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition disabled:opacity-50">
                        <span x-show="!$store.quickAdjust.loading">Commit Adjustment</span>
                        <span x-show="$store.quickAdjust.loading">Updating Stock...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endcan

</div>

@push('scripts')
<script>
    function bulkSelect() {
        return {
            selectedIds: [],
            allSelected: false,
            pageIds: [{{ $products->pluck('id')->join(',') }}],

            toggleAll() {
                this.selectedIds = this.allSelected ? [...this.pageIds] : [];
            },

            updateAllSelected() {
                this.allSelected = this.pageIds.length > 0 && this.pageIds.every(id => this.selectedIds.includes(id));
            },
        };
    }

    document.addEventListener('alpine:init', () => {
        Alpine.store('bulkConfirm', { open: false });

        Alpine.store('quickAdjust', {
            show: false,
            productId: null,
            productName: '',
            currentStock: 0,
            type: 'add',
            quantity: null,
            notes: '',
            error: '',
            loading: false,

            open(id, name, stock) {
                this.productId = id;
                this.productName = name;
                this.currentStock = Number(stock || 0);
                this.type = 'add';
                this.quantity = null;
                this.notes = '';
                this.error = '';
                this.loading = false;
                this.show = true;
            },

            close() {
                this.show = false;
            },

            async submit() {
                if (!this.quantity || this.quantity <= 0) return;
                this.loading = true;
                this.error = '';

                try {
                    const tokenEl = document.querySelector('meta[name="csrf-token"]');
                    const resp = await fetch(`/products/${this.productId}/quick-adjust`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': tokenEl ? tokenEl.content : '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            type: this.type,
                            quantity: this.quantity,
                            notes: this.notes || null,
                        }),
                    });

                    const data = await resp.json();

                    if (!resp.ok || !data.success) {
                        this.error = data.message || 'Stock adjustment failed.';
                        this.loading = false;
                        return;
                    }

                    // Update DOM stock cell
                    const row = document.querySelector(`[data-product-id="${this.productId}"]`);
                    if (row) {
                        const cell = row.querySelector('[data-stock]');
                        if (cell) {
                            cell.textContent = Math.round(data.new_stock).toLocaleString();
                            cell.classList.add('text-teal-600', 'scale-110');
                            setTimeout(() => cell.classList.remove('text-teal-600', 'scale-110'), 2500);
                        }
                    }

                    this.close();
                } catch (e) {
                    this.error = 'Network error. Please try again.';
                    this.loading = false;
                }
            }
        });
    });
</script>
@endpush
@endsection
