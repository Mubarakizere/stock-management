@extends('layouts.app')
@section('title', "Raw Material: {$product->name}")

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

    $unit = $product->unit ?? 'kg';
    $wac  = $product->weightedAverageCost();
    $val  = $current * $wac;

    $isZero = $current <= 0;
    $isLow  = $current > 0 && $current <= $lowThreshold;

    $usedRecipes = $product->usedInRecipes ?? collect();
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
                const tokenEl = document.querySelector('meta[name=csrf-token]');
                const resp = await fetch('{{ route('products.quick-adjust', $product) }}', {
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
                    this.adjustError = data.message || 'Failed to adjust stock.';
                    this.adjustLoading = false;
                    return;
                }

                window.location.reload();
            } catch (e) {
                this.adjustError = 'Network error during adjustment.';
                this.adjustLoading = false;
            }
        }
     }">

    {{-- Executive Header & Action Hub --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            {{-- Title & Meta Pills --}}
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400">
                    <a href="{{ route('raw-materials.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Raw Materials</a>
                    <span>/</span>
                    <span class="font-mono text-slate-500">#RM-{{ str_pad($product->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ $product->name }}
                    </h1>

                    {{-- Stock Health Status Pill --}}
                    @if($isZero)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60">
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                            Depleted (0.00 {{ $unit }})
                        </span>
                    @elseif($isLow)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200/80 dark:border-amber-900/60">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Low Stock Alert
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-900/60">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Healthy Stock
                        </span>
                    @endif

                    {{-- Category Pill --}}
                    @if($category)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $catColor }}"></span>
                            <span>{{ $category->name }}</span>
                        </span>
                    @endif

                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300 font-mono">
                        Base: {{ $unit }}
                    </span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('raw-materials.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Catalog
                </a>

                @can('products.edit')
                    {{-- Quick Adjust Trigger --}}
                    <button type="button"
                            @click="showQuickAdjustModal = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-amber-600 hover:bg-amber-700 active:bg-amber-800 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        ± Quick Adjust
                    </button>

                    <a href="{{ route('raw-materials.edit', $product) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Material
                    </a>
                @endcan

                @can('stock.view')
                    <a href="{{ route('stock.history', ['product_id' => $product->id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Movements
                    </a>
                @endcan

                @can('products.delete')
                    <button type="button"
                            @click="showDeleteModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-rose-600 hover:text-rose-700 hover:bg-rose-50/60 dark:text-rose-400 dark:hover:bg-rose-950/30 transition">
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
        {{-- Unit Cost --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Baseline Unit Cost</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ $fmt2($product->cost_price ?: $product->price) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Standard catalog intake price per {{ $unit }}
                </div>
            </div>
        </div>

        {{-- Weighted Average Cost (WAC) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Intake Cost (WAC)</span>
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
                    Weighted average inflow cost per {{ $unit }}
                </div>
            </div>
        </div>

        {{-- Current Stock Level --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Current Warehouse Stock</span>
                <span class="w-8 h-8 rounded-xl {{ $isZero ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400' : ($isLow ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400') }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ $fmt2($current) }} <span class="text-xs font-medium text-slate-400 font-sans">{{ $unit }}</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Reorder warning threshold: ≤ {{ $lowThreshold }} {{ $unit }}
                </div>
            </div>
        </div>

        {{-- Inventory Valuation --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Inventory Asset Valuation</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ $fmt2($val) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total on-hand value (units × WAC)
                </div>
            </div>
        </div>
    </div>

    {{-- 2 Secondary Turn Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Lifetime Cumulative Inflow</span>
                <div class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                    +{{ $fmt2($totalIn) }} <span class="text-xs font-sans text-slate-400 font-normal">{{ $unit }}</span>
                </div>
                <div class="text-[11px] text-slate-400">Total historical intake purchases & adjustments</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Lifetime Cumulative Outflow</span>
                <div class="text-xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">
                    -{{ $fmt2($totalOut) }} <span class="text-xs font-sans text-slate-400 font-normal">{{ $unit }}</span>
                </div>
                <div class="text-[11px] text-slate-400">Consumed in production runs & adjustments</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Finished Products Consuming This Material (BOM Usage) --}}
    <div id="recipes" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Finished Products Consuming This Ingredient</h2>
                    <p class="text-xs text-slate-400">Downstream Bill of Materials (BOM) dependencies across catalog products</p>
                </div>
            </div>

            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-mono">
                {{ $usedRecipes->count() }} {{ Str::plural('Product', $usedRecipes->count()) }}
            </span>
        </div>

        @if($usedRecipes->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/30 dark:bg-slate-900/30">
                            <th class="py-3 px-4">Finished Product</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4 text-right">Required Intake Qty</th>
                            <th class="py-3 px-4 text-right">Unit Ingredient Cost</th>
                            <th class="py-3 px-4 text-right">Finished Stock On Hand</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($usedRecipes as $rec)
                            @php
                                $fp = $rec->product;
                                $fpCat = $fp?->category;
                                $reqQty = (float)$rec->quantity;
                                $unitCostContrib = $reqQty * $wac;
                                $fpStock = $fp ? $fp->currentStock() : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                    @if($fp)
                                        <a href="{{ route('products.show', $fp) }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">
                                            {{ $fp->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    {{ $fpCat->name ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-medium text-slate-900 dark:text-white">
                                    {{ $fmt2($reqQty) }} {{ $rec->unit ?? $unit }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                    {{ $fmt2($unitCostContrib) }} RWF
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $fmt2($fpStock) }} {{ $fp->unit ?? 'pcs' }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @if($fp)
                                        <a href="{{ route('recipes.edit', $fp) }}" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                                            Edit Recipe →
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-8 px-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    This raw material is not currently bound to any finished product BOM recipes.
                </p>
            </div>
        @endif
    </div>

    {{-- Purchase Price History Ledger --}}
    @if(isset($purchaseHistory) && $purchaseHistory->isNotEmpty())
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Purchase Price History</h2>
                        <p class="text-xs text-slate-400">Historical supplier purchases and price variance trajectory</p>
                    </div>
                </div>

                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 font-mono">
                    {{ $purchaseHistory->count() }} records
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/30 dark:bg-slate-900/30">
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Supplier</th>
                            <th class="py-3 px-4 text-right">Intake Qty</th>
                            <th class="py-3 px-4 text-right">Unit Purchase Cost</th>
                            <th class="py-3 px-4 text-right">Total Line Cost</th>
                            <th class="py-3 px-4 text-center">Cost Shift</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($purchaseHistory as $idx => $ph)
                            @php
                                $purchaseDate = $ph->purchase->purchase_date ?? $ph->created_at;
                                $supplier = $ph->purchase->supplier->name ?? '—';
                                $unitCost = (float)($ph->unit_cost ?? 0);
                                $prevCost = isset($purchaseHistory[$idx + 1]) ? (float)($purchaseHistory[$idx + 1]->unit_cost ?? 0) : null;
                                $diff = $prevCost !== null ? $unitCost - $prevCost : null;
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">
                                    {{ \Carbon\Carbon::parse($purchaseDate)->format('d M Y') }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                    {{ $supplier }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-medium text-slate-700 dark:text-slate-300">
                                    {{ $fmt2($ph->quantity) }} {{ $unit }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $fmt2($unitCost) }} RWF
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                    {{ $fmt2($ph->total_cost ?? ($unitCost * ($ph->quantity ?? 0))) }} RWF
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($diff !== null && $diff != 0)
                                        @if($diff > 0)
                                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 font-mono">
                                                ▲ +{{ $fmt2($diff) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 font-mono">
                                                ▼ {{ $fmt2($diff) }}
                                            </span>
                                        @endif
                                    @elseif($diff !== null)
                                        <span class="text-slate-400 text-xs">—</span>
                                    @else
                                        <span class="text-slate-400 text-[10px] font-mono">First Record</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
                                    @if($move->type === 'in')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                            INFLOW
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                                            OUTFLOW
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold {{ $move->type === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $move->type === 'in' ? '+' : '-' }}{{ $fmt2($move->quantity) }} {{ $unit }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-600 dark:text-slate-400">
                                    {{ $fmt2($move->unit_cost ?? 0) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-semibold text-slate-900 dark:text-white">
                                    {{ $fmt2($move->total_cost ?? 0) }} RWF
                                </td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300">
                                    {{ $move->user->name ?? 'System' }}
                                </td>
                                <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                    @if($move->source_type === Purchase::class)
                                        <a href="{{ route('purchases.show', $move->source_id) }}" class="text-teal-600 hover:underline">
                                            Purchase #{{ $move->source_id }}
                                        </a>
                                    @elseif($move->source_type === ProductModel::class)
                                        Manual Adjustment
                                    @else
                                        {{ class_basename($move->source_type ?? 'Adjustment') }} #{{ $move->source_id }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                    No stock movements recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endcan

    {{-- =========================================================================
     | MODALS
     |======================================================================== --}}

    {{-- 1. Quick Stock Adjust Modal --}}
    @can('products.edit')
    <div x-show="showQuickAdjustModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.outside="showQuickAdjustModal = false"
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
                        <p class="text-[11px] text-slate-400">{{ $product->name }}</p>
                    </div>
                </div>

                <button type="button" @click="showQuickAdjustModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
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

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-[11px]">
                    <span class="text-slate-500">Current Warehouse Stock:</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">{{ number_format($current, 2) }} {{ $unit }}</span>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">
                        Adjustment Quantity <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               x-model.number="adjustQty"
                               placeholder="e.g. 50.00"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 font-mono text-[11px]">{{ $unit }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">
                        Reason / Notes (Audit Log)
                    </label>
                    <input type="text"
                           x-model="adjustNotes"
                           placeholder="e.g. Spillage, inventory recount, supplier adjustment..."
                           class="w-full py-2 px-3 rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                <button type="button"
                        @click="showQuickAdjustModal = false"
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

    {{-- 2. Delete Confirmation Modal --}}
    @can('products.delete')
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.outside="showDeleteModal = false"
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
                        Are you sure you want to permanently delete <span class="font-semibold text-slate-900 dark:text-white">{{ $product->name }}</span>?
                    </p>
                </div>
            </div>

            @if($usedRecipes->isNotEmpty())
                <div class="rounded-xl border border-amber-200/80 dark:border-amber-800/80 bg-amber-50/70 dark:bg-amber-950/30 p-3 text-xs text-amber-800 dark:text-amber-300">
                    <div class="font-semibold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Active BOM Recipe Warning</span>
                    </div>
                    <p class="mt-1">
                        This ingredient is consumed in <span class="font-bold">{{ $usedRecipes->count() }} product recipe(s)</span>. Deletion will detach it from all finished goods manufacturing recipes.
                    </p>
                </div>
            @endif

            <form action="{{ route('raw-materials.destroy', $product) }}" method="POST" class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                @csrf
                @method('DELETE')
                <button type="button"
                        @click="showDeleteModal = false"
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

</div>
@endsection
