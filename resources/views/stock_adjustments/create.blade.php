@extends('layouts.app')
@section('title', 'Adjust Stock — ' . $product->name)

@section('content')
@php
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);

    $currentStock = (float) $product->currentStock();
    $avgCost      = (float) $product->weightedAverageCost();
    $unit         = $product->unit ?? 'pcs';
    $isRawMat     = ($product->type ?? '') === 'raw_material';
    $backRoute    = $isRawMat ? route('raw-materials.show', $product) : route('products.show', $product);
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        systemStock: {{ $currentStock }},
        actualStock: '{{ old('actual_stock', $currentStock) }}',
        unitCost: {{ $avgCost }},
        notes: '{{ addslashes(old('notes', '')) }}',

        get delta() {
            const act = parseFloat(this.actualStock);
            if (isNaN(act)) return 0;
            return act - this.systemStock;
        },

        get valuationDelta() {
            return this.delta * this.unitCost;
        },

        setQuickNote(reason) {
            this.notes = reason;
        },

        formatCurrency(n) {
            return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                <a href="{{ route('stock.history') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Stock Movements</a>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-800 dark:text-slate-200 font-medium">Stock Adjustment</span>
            </nav>

            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Stock Audit & Adjustment
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    {{ $isRawMat ? 'Raw Material' : 'Finished Good' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                    ID #{{ $product->id }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Reconcile physical inventory counts against system records for <strong class="text-slate-700 dark:text-slate-300">{{ $product->name }}</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ $backRoute }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Item</span>
            </a>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-4 text-sm text-rose-800 dark:text-rose-200 shadow-sm">
            <div class="flex items-center gap-2 font-semibold">
                <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Please fix the following issues:</span>
            </div>
            <ul class="list-disc pl-7 mt-2 space-y-1 text-xs text-rose-700 dark:text-rose-300">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Item Summary & Live Variance Card --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- System Stock --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Current System Balance</span>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                    {{ $fmt2($currentStock) }}
                </span>
                <span class="text-xs text-slate-400 font-mono">{{ $unit }}</span>
            </div>
            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                Unit Valuation: RWF {{ $fmt2($avgCost) }}
            </p>
        </div>

        {{-- Count Variance (Real-time) --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Audit Variance Delta</span>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-2xl font-bold tracking-tight tabular-nums"
                      :class="delta > 0 ? 'text-emerald-600 dark:text-emerald-400' : (delta < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400')"
                      x-text="(delta > 0 ? '+' : '') + formatCurrency(delta)"></span>
                <span class="text-xs text-slate-400 font-mono">{{ $unit }}</span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-[11px]">
                <template x-if="delta > 0">
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                        <span>↑ Inventory Surplus (+IN)</span>
                    </span>
                </template>
                <template x-if="delta < 0">
                    <span class="text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1">
                        <span>↓ Shrinkage / Write-off (-OUT)</span>
                    </span>
                </template>
                <template x-if="delta === 0">
                    <span class="text-slate-400">No count variance</span>
                </template>
            </div>
        </div>

        {{-- Valuation Impact (Real-time) --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Financial Valuation Impact</span>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-xs text-slate-400 font-normal">RWF</span>
                <span class="text-2xl font-bold tracking-tight tabular-nums"
                      :class="valuationDelta >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600 dark:text-rose-400'"
                      x-text="(valuationDelta > 0 ? '+' : '') + formatCurrency(valuationDelta)"></span>
            </div>
            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                P&L inventory adjustment value
            </p>
        </div>
    </div>

    {{-- Adjustment Form --}}
    <form action="{{ route('products.adjust.store', $product) }}" method="POST"
          class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            {{-- Current Stock Display --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">
                    Current Recorded Stock
                </label>
                <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300">
                    <span class="font-mono font-bold text-sm">{{ $fmt2($currentStock) }}</span>
                    <span class="text-xs text-slate-400 font-mono">{{ $unit }}</span>
                </div>
                <p class="mt-1 text-[11px] text-slate-400">Current system balance before reconciliation.</p>
            </div>

            {{-- Actual Physical Count Input --}}
            <div>
                <label for="actual_stock" class="block text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1.5">
                    Physical Stock Count <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="number"
                           id="actual_stock"
                           name="actual_stock"
                           x-model="actualStock"
                           step="any"
                           min="0"
                           required
                           autofocus
                           placeholder="Enter counted stock quantity"
                           class="w-full text-sm font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                    <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-mono pointer-events-none">
                        {{ $unit }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-slate-400">The audited quantity physically counted in the warehouse.</p>
            </div>
        </div>

        {{-- Audit Reason / Notes --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="notes" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">
                    Adjustment Reason & Audit Notes
                </label>
                <span class="text-[11px] text-slate-400">Select quick reason below or write custom notes</span>
            </div>

            {{-- Quick Reason Chips --}}
            <div class="flex flex-wrap gap-1.5 mb-2.5">
                @foreach([
                    'Annual Physical Inventory Audit',
                    'Damaged Goods / Spillage',
                    'Expired Batch Write-off',
                    'Supplier Delivery Count Discrepancy',
                    'Sample / Internal Quality Testing',
                    'Found Unrecorded Inventory'
                ] as $reason)
                    <button type="button"
                            @click="setQuickNote('{{ $reason }}')"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                        + {{ $reason }}
                    </button>
                @endforeach
            </div>

            <textarea id="notes"
                      name="notes"
                      rows="3"
                      x-model="notes"
                      placeholder="e.g. Physical inventory cycle count completed by Warehouse Lead on Friday..."
                      class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"></textarea>
        </div>

        {{-- Form Actions Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <span class="text-xs text-slate-500 dark:text-slate-400">
                A permanent stock movement entry will be logged under your user identity.
            </span>

            <div class="flex items-center gap-2.5 self-end sm:self-center">
                <a href="{{ $backRoute }}"
                   class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 text-xs font-semibold rounded-xl bg-teal-600 text-white hover:bg-teal-500 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Confirm & Record Adjustment</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
