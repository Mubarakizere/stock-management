@extends('layouts.app')
@section('title', "Product: {$product->name}")

@section('content')
@php
    use App\Models\Purchase;
    use App\Models\Sale;
    use App\Models\PurchaseReturn;
    use App\Models\Product as ProductModel;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $category     = $product->category;
    $catColor     = $category->color ?? '#0d9488';
    $lowThreshold = (int) config('inventory.low_stock', 5);

    $wac = $product->weightedAverageCost();
    $margin = ($product->price ?? 0) > 0
        ? (($product->price - $wac) / max(0.0001, $product->price)) * 100
        : 0;

    $isZero = $current <= 0;
    $isLow  = $current > 0 && $current <= $lowThreshold;

    $recipeItems = $product->recipeItems()->with('rawMaterial')->get();
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        showDeleteModal: false,
        showQuickAdjustModal: false,
        adjustType: 'add',
        adjustQty: null,
        adjustNotes: '',
        adjustLoading: false,
        adjustError: '',

        async submitQuickAdjust() {
            if (!this.adjustQty || this.adjustQty <= 0) return;
            this.adjustLoading = true;
            this.adjustError = '';

            try {
                const tokenEl = document.querySelector('meta[name="csrf-token"]');
                const resp = await fetch('{{ route('products.quick-adjust', $product) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenEl ? tokenEl.content : '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        type: this.adjustType,
                        quantity: this.adjustQty,
                        notes: this.adjustNotes || null,
                    }),
                });

                const data = await resp.json();
                if (!resp.ok || !data.success) {
                    this.adjustError = data.message || 'Adjustment failed.';
                    this.adjustLoading = false;
                    return;
                }

                window.location.reload();
            } catch (e) {
                this.adjustError = 'Network error. Please try again.';
                this.adjustLoading = false;
            }
        }
     }">

    {{-- Breadcrumb Navigation --}}
    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <a href="{{ route('products.index') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Products</a>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $product->name }}</span>
    </div>

    {{-- Header Banner --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-xl shrink-0 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm">
                    {{ strtoupper(substr($product->name, 0, 2)) }}
                </div>
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ $product->name }}
                        </h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                            #{{ $product->sku ?: ('PRD-' . $product->id) }}
                        </span>

                        {{-- Stock Status Pill --}}
                        @if($isZero)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Out of Stock
                            </span>
                        @elseif($isLow)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Low Inventory (≤ {{ $lowThreshold }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Healthy Stock
                            </span>
                        @endif

                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            Unit: {{ $product->unit ?: 'pcs' }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                        @if($category)
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $catColor }}"></span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $category->name }}</span>
                            </span>
                        @endif
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <span>Catalog item #{{ $product->id }}</span>
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <span>Created {{ $product->created_at ? $product->created_at->format('d M Y') : '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Actions Suite --}}
            <div class="flex flex-wrap items-center gap-2 pt-2 lg:pt-0">
                @can('products.edit')
                    <button type="button" @click="showQuickAdjustModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        ± Quick Adjust
                    </button>

                    <a href="{{ route('recipes.edit', $product) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                        <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        Recipe (BOM)
                    </a>

                    <a href="{{ route('products.adjust.create', $product) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                        Full Adjust
                    </a>

                    <a href="{{ route('products.edit', $product) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                @endcan

                @can('stock.view')
                    <a href="{{ route('stock.history', ['product_id' => $product->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Movements
                    </a>
                @endcan

                @can('products.delete')
                    <button type="button" @click="showDeleteModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-rose-600 hover:text-rose-700 hover:bg-rose-50/60 dark:text-rose-400 dark:hover:bg-rose-950/30 transition" title="Delete Product">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- 4 Primary Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Retail Price --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Retail Selling Price</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ $fmt2($product->price) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Standard catalog sales price
                </div>
            </div>
        </div>

        {{-- Cost (WAC) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Unit Cost (WAC)</span>
                <span class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ $fmt2($wac) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Weighted average inflow cost
                </div>
            </div>
        </div>

        {{-- Profit Margin --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Profit Margin</span>
                <span class="w-8 h-8 rounded-xl {{ $margin >= 0 ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400' }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight {{ $margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $fmt2($margin) }}%
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Gross contribution per unit
                </div>
            </div>
        </div>

        {{-- Current Stock --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Warehouse Stock</span>
                <span class="w-8 h-8 rounded-xl {{ $isZero ? 'bg-rose-50 text-rose-600' : ($isLow ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600') }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight {{ $isZero ? 'text-rose-600 dark:text-rose-400' : ($isLow ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white') }}">
                    {{ $fmt0($current) }} <span class="text-xs font-medium text-slate-400 font-sans">{{ $product->unit ?: 'pcs' }}</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Live physical inventory on hand
                </div>
            </div>
        </div>
    </div>

    {{-- 3 Volume & Valuation Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500">Cumulative Inflow</span>
                <div class="text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">+{{ $fmt0($totalIn) }} {{ $product->unit ?: 'pcs' }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                </svg>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500">Cumulative Outflow</span>
                <div class="text-lg font-bold font-mono text-slate-700 dark:text-slate-300 mt-0.5">-{{ $fmt0($totalOut) }} {{ $product->unit ?: 'pcs' }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                </svg>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500">Total Stock Value (@ WAC)</span>
                <div class="text-lg font-bold font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $fmt2($product->stockValue()) }} RWF</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Recipe / Bill of Materials (BOM) Section --}}
    @if($recipeItems->isNotEmpty())
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Recipe (Bill of Materials)</h2>
                        <p class="text-xs text-slate-400">Raw materials consumed per 1 {{ $product->unit ?? 'unit' }} of finished output</p>
                    </div>
                </div>

                @can('products.edit')
                    <a href="{{ route('recipes.edit', $product) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                        <span>Edit Recipe Formula</span>
                        <span>→</span>
                    </a>
                @endcan
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/30 dark:bg-slate-900/30">
                            <th class="py-3 px-4">Raw Material Ingredient</th>
                            <th class="py-3 px-4 text-right">Recipe Amount</th>
                            <th class="py-3 px-4 text-right">Base Deduction</th>
                            <th class="py-3 px-4 text-right">Raw Unit Cost</th>
                            <th class="py-3 px-4 text-right">Line Cost</th>
                            <th class="py-3 px-4 text-right">Warehouse Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @php $recipeTotalCost = 0; @endphp
                        @foreach($recipeItems as $ri)
                            @php
                                $rm = $ri->rawMaterial;
                                $rmCost = (float)($rm->cost_price ?: $rm->price ?: 0);
                                $baseQty = $ri->baseQuantityNeeded(1.0);
                                $lineCost = $rmCost * $baseQty;
                                $recipeTotalCost += $lineCost;
                                $rmStock = $rm ? $rm->currentStock() : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                    {{ $rm->name ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                    {{ $ri->formattedQuantity() }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-500">
                                    {{ $baseQty }} {{ $rm->unit ?? 'pcs' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-500">
                                    {{ $fmt2($rmCost) }} RWF
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $fmt2($lineCost) }} RWF
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="font-mono font-semibold {{ $rmStock <= 0 ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300' }}">
                                        {{ $fmt0($rmStock) }} {{ $rm->unit ?? 'pcs' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 text-xs">
                        <tr>
                            <td colspan="4" class="py-3 px-4 font-bold text-slate-700 dark:text-slate-300 uppercase text-[11px]">Total Estimated Production Material Cost</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-teal-600 dark:text-teal-400">{{ $fmt2($recipeTotalCost) }} RWF</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm p-6 text-center">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Recipe Configured</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                Define raw material ingredient ratios to enable production run material deductions and accurate cost tracking.
            </p>
            @can('products.edit')
                <div class="mt-4">
                    <a href="{{ route('recipes.edit', $product) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-amber-600 text-white hover:bg-amber-500 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Configure Recipe (BOM)</span>
                    </a>
                </div>
            @endcan
        </div>
    @endif

    {{-- Recent Stock Movements Ledger --}}
    @can('stock.view')
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Recent Stock Movements</h2>
                </div>

                <a href="{{ route('stock.history', ['product_id' => $product->id]) }}" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                    View Complete History →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/30 dark:bg-slate-900/30">
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-center">Type</th>
                            <th class="py-3 px-4 text-right">Quantity</th>
                            <th class="py-3 px-4 text-right">Unit Cost</th>
                            <th class="py-3 px-4 text-right">Total Cost</th>
                            <th class="py-3 px-4">Recorded By</th>
                            <th class="py-3 px-4">Source Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($product->stockMovements as $move)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4 font-mono text-slate-700 dark:text-slate-300">
                                    {{ $move->created_at ? $move->created_at->format('d M Y, H:i') : '—' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $move->type === 'in' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $move->type }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold {{ $move->type === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-200' }}">
                                    {{ $move->type === 'in' ? '+' : '-' }}{{ $fmt0($move->quantity) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-500">
                                    {{ $fmt2($move->unit_cost) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-semibold text-slate-900 dark:text-white">
                                    {{ $fmt2($move->total_cost) }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                    {{ $move->user->name ?? 'System' }}
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    {{ class_basename($move->source_type ?? '') }} #{{ $move->source_id ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-slate-400">
                                    No stock movements recorded for this product yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- MODAL 1: DEDICATED DELETE PRODUCT MODAL                     --}}
    {{-- ============================================================ --}}
    @can('products.delete')
    <div x-show="showDeleteModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="showDeleteModal = false"
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
                        Are you sure you want to permanently delete <strong class="text-slate-800 dark:text-slate-200">{{ $product->name }}</strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Audit & Ledger Integrity Safeguard
                </div>
                <p class="text-amber-700 dark:text-amber-400 pl-5">
                    This catalog entry has <strong>{{ $fmt0($current) }} units</strong> in stock. Deletion will purge all movement records, bill of materials, and production allocations.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showDeleteModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Keep Product
                </button>
                <form action="{{ route('products.destroy', $product) }}" method="POST">
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
    {{-- MODAL 2: QUICK STOCK ADJUSTMENT MODAL                       --}}
    {{-- ============================================================ --}}
    @can('products.edit')
    <div x-show="showQuickAdjustModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="showQuickAdjustModal = false"
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
                        <p class="text-xs text-slate-400">{{ $product->name }}</p>
                    </div>
                </div>
                <button type="button" @click="showQuickAdjustModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-4 space-y-4">
                {{-- Current Stock display --}}
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Recorded Warehouse Stock:</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white text-sm">{{ $fmt0($current) }} {{ $product->unit ?: 'pcs' }}</span>
                </div>

                {{-- Action Type Switcher --}}
                <div class="grid grid-cols-2 gap-2">
                    <button type="button"
                            @click="adjustType = 'add'"
                            class="py-2 px-3 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition"
                            :class="adjustType === 'add'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        + Add Stock
                    </button>
                    <button type="button"
                            @click="adjustType = 'remove'"
                            class="py-2 px-3 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition"
                            :class="adjustType === 'remove'
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
                           x-model.number="adjustQty"
                           placeholder="Enter quantity to adjust..."
                           class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500">
                </div>

                {{-- Reason / Audit Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reason / Notes (Optional)</label>
                    <textarea rows="2"
                              x-model="adjustNotes"
                              placeholder="e.g. Physical stock count reconciliation, damaged items..."
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"></textarea>
                </div>

                {{-- Error Banner --}}
                <template x-if="adjustError">
                    <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs" x-text="adjustError"></div>
                </template>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showQuickAdjustModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="button"
                            @click="submitQuickAdjust()"
                            :disabled="adjustLoading || !adjustQty || adjustQty <= 0"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition disabled:opacity-50">
                        <span x-show="!adjustLoading">Commit Adjustment</span>
                        <span x-show="adjustLoading">Updating Stock...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endcan

</div>
@endsection
