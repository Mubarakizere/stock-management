@extends('layouts.app')
@section('title', 'Recipe — ' . $product->name)

@section('content')
@php
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);

    $existingIngredients = $product->recipeItems->map(fn($r) => [
        'raw_material_id'   => (int) $r->raw_material_id,
        'quantity'          => (float) $r->quantity,
        'unit'              => $r->unit ?: ($r->rawMaterial->unit ?? 'pcs'),
        'conversion_factor' => (float) ($r->conversion_factor ?: 1.0),
    ])->values()->toArray();

    $materialsData = $rawMaterials->map(fn($r) => [
        'id'        => (int) $r->id,
        'name'      => $r->name,
        'price'     => (float) ($r->cost_price ?: $r->price ?: 0),
        'wac_price' => (float) ($r->weightedAverageCost() ?: $r->cost_price ?: $r->price ?: 0),
        'po_price'  => (float) ($r->last_purchase_price ?: $r->cost_price ?: $r->price ?: 0),
        'unit'      => strtolower($r->unit ?: 'pcs'),
        'stock'     => (float) ($r->stock ?? 0),
    ])->values()->toArray();

    $sellingPrice = (float) ($product->price ?? 0);
    $initialCost = (float) ($product->estimatedRecipeCost() ?? $product->cost_price ?? 0);
    $targetMargin = (float) ($product->target_margin ?? 25.0);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="recipeEditor({{ json_encode($existingIngredients) }}, {{ json_encode($materialsData) }}, {{ $sellingPrice }}, {{ $initialCost }}, {{ $targetMargin }})">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Dashboard</a>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ route('recipes.index') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Recipes (BOM)</a>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-800 dark:text-slate-200 font-medium truncate max-w-xs">{{ $product->name }}</span>
            </nav>

            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Recipe / Bill of Materials
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    {{ $product->category->name ?? 'Finished Good' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                    ID #{{ $product->id }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-medium bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                    Output: 1 {{ $product->unit ?? 'pcs' }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Configure raw material ingredient quantities, unit conversion factors, and automated production cost calculation for <strong class="text-slate-700 dark:text-slate-300">{{ $product->name }}</strong>.
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('recipes.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Recipe Hub
            </a>

            <a href="{{ route('products.show', $product) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Product Profile
            </a>

            @if($product->recipeItems->isNotEmpty())
                <button type="button"
                        @click="showClearModal = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-300 dark:border-rose-800 dark:hover:bg-rose-900/40 shadow-sm transition">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Clear BOM
                </button>
            @endif
        </div>
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

    @if (isset($errors) && $errors->any())
        <div class="rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-4 text-sm text-rose-800 dark:text-rose-200 shadow-sm">
            <div class="flex items-center gap-2 font-semibold">
                <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Please address the following validation errors:</span>
            </div>
            <ul class="list-disc pl-7 mt-2 space-y-1 text-xs text-rose-700 dark:text-rose-300">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Executive 4-KPI Deck (Real-time Dynamic via Alpine) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Ingredients Count --}}
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Formula Ingredients</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-500 dark:text-amber-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums" x-text="ingredients.length"></span>
                <span class="text-xs text-slate-500 dark:text-slate-400">items in BOM</span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                <template x-if="ingredients.length > 0">
                    <span class="text-teal-600 dark:text-teal-400 font-medium">Active Bill of Materials</span>
                </template>
                <template x-if="ingredients.length === 0">
                    <span class="text-amber-600 dark:text-amber-400 font-medium">Unconfigured (Missing BOM)</span>
                </template>
            </div>
        </div>

        {{-- Card 2: Calculated BOM Unit Cost --}}
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dynamic BOM Cost</span>
                {{-- Basis Pill Switcher --}}
                <div class="inline-flex rounded-lg bg-slate-100 dark:bg-slate-800 p-0.5 border border-slate-200 dark:border-slate-700 text-[10px]">
                    <button type="button"
                            @click="basis = 'wac'"
                            :class="basis === 'wac' ? 'bg-teal-600 text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white font-medium'"
                            class="px-2 py-0.5 rounded-md transition"
                            title="Weighted Average Cost from stock movements">
                        WAC
                    </button>
                    <button type="button"
                            @click="basis = 'po'"
                            :class="basis === 'po' ? 'bg-indigo-600 text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white font-medium'"
                            class="px-2 py-0.5 rounded-md transition"
                            title="Latest Purchase Order invoice price">
                        Latest PO
                    </button>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xs font-medium text-slate-400">RWF</span>
                <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums"
                      x-text="formatCurrency(totalBOMCost())"></span>
            </div>
            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                Costing basis: <span class="font-semibold text-slate-700 dark:text-slate-300 uppercase" x-text="basis === 'wac' ? 'Weighted Avg Cost (WAC)' : 'Latest PO Invoice'"></span>
            </p>
        </div>

        {{-- Card 3: Retail Selling Price --}}
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Catalog Price</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xs font-medium text-slate-400">RWF</span>
                <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                    {{ $fmt2($sellingPrice) }}
                </span>
            </div>
            <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Target: <strong class="text-slate-700 dark:text-slate-300 font-mono" x-text="targetMargin + '%'"></strong></span>
                <span class="text-[10px] text-slate-400 font-mono">Retail POS</span>
            </div>
        </div>

        {{-- Card 4: Projected Unit Margin --}}
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gross Margin</span>
                {{-- Margin Guard Status Badge --}}
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider"
                      :class="marginStatus() === 'healthy' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : (marginStatus() === 'warning' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800')">
                    <span class="w-1.5 h-1.5 rounded-full"
                          :class="marginStatus() === 'healthy' ? 'bg-emerald-500' : (marginStatus() === 'warning' ? 'bg-amber-500' : 'bg-rose-500')"></span>
                    <span x-text="marginStatus() === 'healthy' ? 'Safe' : (marginStatus() === 'warning' ? 'Low Margin' : 'Critical')"></span>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xs font-medium text-slate-400">RWF</span>
                <span class="text-2xl font-bold tracking-tight tabular-nums"
                      :class="marginAmount() >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600 dark:text-rose-400'"
                      x-text="formatCurrency(marginAmount())"></span>
            </div>
            <div class="mt-1 flex items-center justify-between text-[11px]">
                <span class="text-slate-500 dark:text-slate-400">Gross Margin %:</span>
                <span class="font-bold font-mono"
                      :class="marginPct() >= targetMargin ? 'text-teal-600 dark:text-teal-400' : (marginPct() > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400')"
                      x-text="marginPct().toFixed(1) + '%'"></span>
            </div>
        </div>
    </div>

    {{-- Guidance Intelligence Banner --}}
    <div class="rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-slate-200 p-4 border border-slate-700/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h4 class="text-xs font-bold text-white tracking-wide uppercase">Multi-Unit Recipe Conversion Engine</h4>
                <p class="text-xs text-slate-300 mt-0.5 leading-relaxed">
                    You can input recipe quantities in operational culinary/batch units (e.g. <span class="font-mono text-amber-300">250 g</span>, <span class="font-mono text-amber-300">15 ml</span>, <span class="font-mono text-amber-300">1 cap</span>).
                    The system automatically computes base conversions (<span class="font-mono text-teal-300">0.25 kg</span>, <span class="font-mono text-teal-300">0.015 L</span>) to accurately deduct warehouse stock during production batches.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-end md:self-center shrink-0">
            <button type="button"
                    @click="addIngredient()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-amber-500 text-slate-950 hover:bg-amber-400 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Ingredient
            </button>
        </div>
    </div>

    {{-- Recipe Form Workstation --}}
    <form action="{{ route('recipes.update', $product) }}" method="POST" id="recipe-form">
        @csrf
        @method('PUT')

        {{-- Margin Guard & Dynamic Costing Control Center --}}
        <div class="mb-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Dynamic BOM Margin Guard</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                  :class="marginStatus() === 'healthy' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200' : (marginStatus() === 'warning' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200')">
                                <span class="w-1.5 h-1.5 rounded-full mr-1"
                                      :class="marginStatus() === 'healthy' ? 'bg-emerald-500' : (marginStatus() === 'warning' ? 'bg-amber-500' : 'bg-rose-500')"></span>
                                <span x-text="marginStatus() === 'healthy' ? 'Healthy Margin' : (marginStatus() === 'warning' ? 'Margin Warning' : 'Margin Deficit')"></span>
                            </span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Configure minimum target profit margin and choose real-time raw material valuation basis.
                        </p>
                    </div>
                </div>

                {{-- Costing Basis Switcher --}}
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Costing Basis:</span>
                    <button type="button"
                            @click="basis = 'wac'"
                            :class="basis === 'wac' ? 'bg-teal-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'"
                            class="px-2.5 py-1 rounded-lg text-xs transition">
                        Weighted Avg (WAC)
                    </button>
                    <button type="button"
                            @click="basis = 'po'"
                            :class="basis === 'po' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'"
                            class="px-2.5 py-1 rounded-lg text-xs transition">
                        Latest PO Invoice
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                {{-- Target Margin % Configuration --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Target Gross Margin (%)
                    </label>
                    <div class="flex items-center gap-2">
                        <div class="relative w-36">
                            <input type="number"
                                   name="target_margin"
                                   x-model.number="targetMargin"
                                   min="0" max="99.9" step="0.5"
                                   class="w-full text-xs font-mono font-bold text-right pr-7 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                            <span class="absolute right-2.5 top-2 text-xs font-bold text-slate-400">%</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <template x-for="p in [20, 25, 30, 35, 40]" :key="p">
                                <button type="button"
                                        @click="targetMargin = p"
                                        :class="targetMargin == p ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                                        class="px-2 py-1 rounded-lg text-xs transition"
                                        x-text="p + '%'"></button>
                            </template>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        Margin Guard flags an alert whenever actual gross margin dips below this percentage.
                    </p>
                </div>

                {{-- Recommended Price & Auto-adjust Checkbox --}}
                <div class="bg-slate-50 dark:bg-slate-800/40 rounded-xl p-3 border border-slate-200/80 dark:border-slate-800">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-600 dark:text-slate-300 font-medium">Recommended Selling Price:</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white tabular-nums">
                            RWF <span x-text="formatCurrency(recommendedSellingPrice())"></span>
                        </span>
                    </div>
                    <label class="mt-2.5 flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox"
                               name="auto_adjust_price"
                               value="1"
                               x-model="autoAdjustPrice"
                               class="rounded border-slate-300 dark:border-slate-700 text-teal-600 focus:ring-teal-500">
                        <span class="text-xs text-slate-700 dark:text-slate-300">
                            Auto-adjust catalog price to <strong class="text-slate-900 dark:text-white" x-text="'RWF ' + formatCurrency(recommendedSellingPrice())"></strong> on save
                        </span>
                    </label>
                </div>
            </div>

            {{-- Dynamic Margin Alert Banner --}}
            <template x-if="marginStatus() === 'warning'">
                <div class="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-3.5 text-xs text-amber-800 dark:text-amber-200 flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <strong class="font-bold">Margin Guard Alert:</strong>
                        Projected gross margin is <span class="font-bold font-mono" x-text="marginPct().toFixed(1) + '%'"></span>, which is below your target of <span class="font-bold font-mono" x-text="targetMargin + '%'"></span>.
                        Due to raw material purchase cost changes, consider raising catalog retail price to at least <strong class="underline decoration-amber-400 font-mono" x-text="'RWF ' + formatCurrency(recommendedSellingPrice())"></strong>.
                    </div>
                </div>
            </template>

            <template x-if="marginStatus() === 'critical'">
                <div class="rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-3.5 text-xs text-rose-800 dark:text-rose-200 flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <strong class="font-bold">Critical Deficit Alert:</strong>
                        Calculated BOM raw material unit cost (<span class="font-mono font-bold" x-text="'RWF ' + formatCurrency(totalBOMCost())"></span>) exceeds current catalog selling price (<span class="font-mono font-bold">{{ $fmt2($sellingPrice) }} RWF</span>), yielding a negative gross margin of <span class="font-bold font-mono" x-text="marginPct().toFixed(1) + '%'"></span>. Immediate price adjustment required!
                    </div>
                </div>
            </template>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            {{-- Workstation Header --}}
            <div class="px-5 py-4 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50 dark:bg-slate-800/40">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                            Recipe Composition Table
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Define exact ingredients needed to manufacture 1 {{ $product->unit ?? 'unit' }} of this finished product.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="addIngredient()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl text-teal-700 bg-teal-50 hover:bg-teal-100 dark:bg-teal-950/40 dark:text-teal-300 dark:hover:bg-teal-900/50 border border-teal-200 dark:border-teal-800 transition">
                        <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Add Ingredient Row</span>
                    </button>
                </div>
            </div>

            {{-- Table Column Headers (Desktop) --}}
            <div class="hidden lg:grid grid-cols-12 gap-3 px-5 py-3 bg-slate-100/70 dark:bg-slate-800/80 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                <div class="col-span-1 text-center">#</div>
                <div class="col-span-4">Raw Material Ingredient</div>
                <div class="col-span-2 text-right">Recipe Amount</div>
                <div class="col-span-2">Unit</div>
                <div class="col-span-1 text-right">Deduction</div>
                <div class="col-span-1 text-right">Line Cost</div>
                <div class="col-span-1 text-center">Action</div>
            </div>

            {{-- Ingredient Rows --}}
            <div class="divide-y divide-slate-100 dark:divide-slate-800/70">
                <template x-for="(ing, index) in ingredients" :key="index">
                    <div class="px-5 py-3.5 grid grid-cols-1 lg:grid-cols-12 gap-3 items-center hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition">

                        {{-- Index --}}
                        <div class="hidden lg:flex lg:col-span-1 items-center justify-center">
                            <span class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-mono font-semibold flex items-center justify-center"
                                  x-text="index + 1"></span>
                        </div>

                        {{-- Raw Material Selector --}}
                        <div class="lg:col-span-4">
                            <label class="lg:hidden block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Raw Material Ingredient <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select :name="'ingredients[' + index + '][raw_material_id]'"
                                        x-model="ing.raw_material_id"
                                        @change="onMaterialChange(ing)"
                                        class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"
                                        required>
                                    <option value="">Select Raw Material…</option>
                                    <template x-for="rm in availableMaterials" :key="rm.id">
                                        <option :value="rm.id"
                                                :selected="ing.raw_material_id == rm.id"
                                                x-text="rm.name + ' (' + (basis === 'po' ? 'PO: ' : 'WAC: ') + formatCurrency(materialPrice(rm)) + ' RWF / ' + rm.unit + ' | Stock: ' + rm.stock + ' ' + rm.unit + ')'">
                                        </option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        {{-- Recipe Quantity --}}
                        <div class="lg:col-span-2">
                            <label class="lg:hidden block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Quantity <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       :name="'ingredients[' + index + '][quantity]'"
                                       x-model.number="ing.quantity"
                                       @input="recalculateRow(ing)"
                                       step="any" min="0.0001"
                                       placeholder="0.00"
                                       class="w-full text-xs font-mono font-medium text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"
                                       required>
                            </div>
                        </div>

                        {{-- Unit of Measurement --}}
                        <div class="lg:col-span-2">
                            <label class="lg:hidden block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Recipe Unit
                            </label>
                            <select :name="'ingredients[' + index + '][unit]'"
                                    x-model="ing.unit"
                                    @change="onUnitChange(ing)"
                                    class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                                <optgroup label="Mass / Weight">
                                    <option value="g">Gram (g)</option>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="mg">Milligram (mg)</option>
                                </optgroup>
                                <optgroup label="Liquid / Volume">
                                    <option value="ml">Milliliter (ml)</option>
                                    <option value="cl">Centiliter (cl)</option>
                                    <option value="l">Liter (L)</option>
                                </optgroup>
                                <optgroup label="Packaging / Discrete Units">
                                    <option value="pcs">Pieces (pcs)</option>
                                    <option value="unit">Unit</option>
                                    <option value="bottle">Bottle</option>
                                    <option value="can">Can</option>
                                    <option value="cap">Cap / Lid</option>
                                    <option value="box">Box</option>
                                    <option value="pack">Pack</option>
                                    <option value="bag">Bag</option>
                                    <option value="carton">Carton</option>
                                    <option value="crate">Crate</option>
                                    <option value="roll">Roll</option>
                                </optgroup>
                            </select>

                            {{-- Hidden Conversion Factor Input --}}
                            <input type="hidden"
                                   :name="'ingredients[' + index + '][conversion_factor]'"
                                   :value="ing.conversion_factor">
                        </div>

                        {{-- Base Deduction Equivalent --}}
                        <div class="lg:col-span-1 text-right">
                            <label class="lg:hidden block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Base Deduction
                            </label>
                            <div class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                                 title="Converted quantity to deduct from warehouse stock">
                                <span x-text="formatBaseEquiv(ing)"></span>
                            </div>
                        </div>

                        {{-- Line Cost --}}
                        <div class="lg:col-span-1 text-right">
                            <label class="lg:hidden block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                                Line Cost
                            </label>
                            <div class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200">
                                <span x-text="formatCurrency(lineCost(ing))"></span>
                            </div>
                            <span class="text-[10px] text-slate-400 font-mono">RWF</span>
                        </div>

                        {{-- Remove Row Action --}}
                        <div class="lg:col-span-1 text-center flex lg:justify-center justify-end">
                            <button type="button"
                                    @click="removeIngredient(index)"
                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition"
                                    title="Remove this ingredient">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty State --}}
            <div x-show="ingredients.length === 0"
                 class="px-6 py-16 text-center text-slate-500 dark:text-slate-400">
                <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No Ingredients in Recipe</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                    This finished product currently has no Bill of Materials configured. Add raw material ingredients to automate cost estimation and batch tracking.
                </p>
                <button type="button"
                        @click="addIngredient()"
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-teal-600 text-white hover:bg-teal-500 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add First Ingredient</span>
                </button>
            </div>

            {{-- Table Summary Footer --}}
            <div x-show="ingredients.length > 0"
                 class="px-5 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200/80 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600 dark:text-slate-300">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                        <strong class="font-bold text-slate-900 dark:text-white" x-text="ingredients.length"></strong>
                        <span>Ingredients Configured</span>
                    </span>
                    <span class="text-slate-400">•</span>
                    <span>
                        Unit Output: <strong class="text-slate-800 dark:text-slate-200">1 {{ $product->unit ?? 'pcs' }}</strong>
                    </span>
                    <span class="text-slate-400">•</span>
                    <span>
                        Target Margin: <strong class="text-slate-800 dark:text-slate-200">{{ $fmt0($product->target_margin ?? 0) }}%</strong>
                    </span>
                </div>

                <div class="flex items-center gap-6 self-end md:self-center">
                    <div class="text-right">
                        <div class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">Total Material Cost / Unit</div>
                        <div class="text-lg font-bold text-slate-900 dark:text-white tabular-nums">
                            <span class="text-xs text-slate-400 font-normal">RWF</span>
                            <span x-text="formatCurrency(totalBOMCost())"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interactive Batch Production Simulator --}}
        <div class="mt-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs"
             x-show="ingredients.length > 0">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Manufacturing Batch Scaling Simulator</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Test and simulate projected material expenditure, revenue, and gross profit for various batch production runs.
                    </p>
                </div>

                {{-- Batch Size Controls --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Batch Target:</span>
                    <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700">
                        <template x-for="preset in [1, 10, 50, 100, 500]" :key="preset">
                            <button type="button"
                                    @click="batchQty = preset"
                                    :class="batchQty == preset ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium hover:text-slate-900'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition tabular-nums"
                                    x-text="preset + 'x'">
                            </button>
                        </template>
                    </div>
                    <div class="w-20">
                        <input type="number"
                               min="1"
                               x-model.number="batchQty"
                               placeholder="Custom"
                               class="w-full text-xs font-mono font-medium text-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-2 py-1.5 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 p-3">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estimated Batch Material Cost</span>
                    <div class="mt-1 flex items-baseline gap-1 text-slate-900 dark:text-white">
                        <span class="text-xs text-slate-400">RWF</span>
                        <span class="text-lg font-bold tabular-nums" x-text="formatCurrency(batchCost())"></span>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Required raw inventory valuation
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 p-3">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Projected Batch Sales Revenue</span>
                    <div class="mt-1 flex items-baseline gap-1 text-slate-900 dark:text-white">
                        <span class="text-xs text-slate-400">RWF</span>
                        <span class="text-lg font-bold tabular-nums" x-text="formatCurrency(batchRevenue())"></span>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Total sales value @ retail price
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 p-3">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Projected Gross Profit</span>
                    <div class="mt-1 flex items-baseline gap-1"
                         :class="batchProfit() >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600 dark:text-rose-400'">
                        <span class="text-xs text-slate-400">RWF</span>
                        <span class="text-lg font-bold tabular-nums" x-text="formatCurrency(batchProfit())"></span>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Gross margin contribution
                    </p>
                </div>
            </div>
        </div>

        {{-- Form Actions Bar --}}
        <div class="mt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <svg class="w-4 h-4 text-teal-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Saving this recipe will automatically recalculate the product's estimated manufacturing cost price and margins.</span>
            </div>

            <div class="flex items-center gap-3 self-end sm:self-center">
                <a href="{{ route('recipes.index') }}"
                   class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 text-xs font-semibold rounded-xl bg-teal-600 text-white hover:bg-teal-500 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Save Recipe Formula</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Danger Modal: Clear Recipe Confirmation --}}
    <div x-show="showClearModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="showClearModal = false">
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xl overflow-hidden p-6"
             @click.outside="showClearModal = false">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Clear Bill of Materials?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Are you sure you want to clear the entire recipe for <strong class="text-slate-800 dark:text-slate-200">{{ $product->name }}</strong>?
                        All ingredient associations will be detached. Future production runs will require recipe re-configuration.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button"
                        @click="showClearModal = false"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Keep Recipe
                </button>

                <form action="{{ route('recipes.destroy', $product) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-rose-600 text-white hover:bg-rose-500 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Yes, Clear Recipe</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function recipeEditor(existing, rawMaterials, sellingPrice, initialCost, targetMargin) {
        // Standard metric conversion factors normalized to base:
        // Weight: kg is 1.0 (g = 0.001, mg = 0.000001)
        // Volume: l is 1.0 (ml = 0.001, cl = 0.01)
        const weightFactors = { kg: 1.0, g: 0.001, mg: 0.000001 };
        const volumeFactors = { l: 1.0, ml: 0.001, cl: 0.01 };

        return {
            ingredients: Array.isArray(existing) && existing.length > 0 ? existing : [],
            availableMaterials: Array.isArray(rawMaterials) ? rawMaterials : [],
            sellingPrice: parseFloat(sellingPrice) || 0,
            targetMargin: parseFloat(targetMargin) || 25.0,
            basis: 'wac',
            batchQty: 1,
            showClearModal: false,
            autoAdjustPrice: false,

            materialPrice(rm) {
                if (!rm) return 0;
                return this.basis === 'po' ? (rm.po_price || rm.price) : (rm.wac_price || rm.price);
            },

            computeFactor(recipeUnit, baseUnit) {
                if (!recipeUnit || !baseUnit) return 1.0;
                const ru = recipeUnit.toLowerCase().trim();
                const bu = baseUnit.toLowerCase().trim();
                if (ru === bu) return 1.0;

                // Weight family
                if (weightFactors[ru] && weightFactors[bu]) {
                    return weightFactors[ru] / weightFactors[bu];
                }
                // Volume family
                if (volumeFactors[ru] && volumeFactors[bu]) {
                    return volumeFactors[ru] / volumeFactors[bu];
                }

                // Default
                return 1.0;
            },

            addIngredient() {
                this.ingredients.push({
                    raw_material_id: '',
                    quantity: 1,
                    unit: 'pcs',
                    conversion_factor: 1.0
                });
            },

            removeIngredient(index) {
                this.ingredients.splice(index, 1);
            },

            onMaterialChange(ing) {
                const rm = this.availableMaterials.find(r => r.id == ing.raw_material_id);
                if (rm) {
                    const baseUnit = rm.unit || 'pcs';
                    if (baseUnit === 'kg' && (!ing.unit || ing.unit === 'pcs')) {
                        ing.unit = 'g';
                    } else if (baseUnit === 'l' && (!ing.unit || ing.unit === 'pcs')) {
                        ing.unit = 'ml';
                    } else if (!ing.unit) {
                        ing.unit = baseUnit;
                    }
                    ing.conversion_factor = this.computeFactor(ing.unit, baseUnit);
                }
            },

            onUnitChange(ing) {
                const rm = this.availableMaterials.find(r => r.id == ing.raw_material_id);
                const baseUnit = rm ? (rm.unit || 'pcs') : 'pcs';
                ing.conversion_factor = this.computeFactor(ing.unit, baseUnit);
            },

            recalculateRow(ing) {
                const rm = this.availableMaterials.find(r => r.id == ing.raw_material_id);
                const baseUnit = rm ? (rm.unit || 'pcs') : 'pcs';
                ing.conversion_factor = this.computeFactor(ing.unit, baseUnit);
            },

            formatBaseEquiv(ing) {
                if (!ing.raw_material_id) return '—';
                const rm = this.availableMaterials.find(r => r.id == ing.raw_material_id);
                const baseUnit = rm ? (rm.unit || 'pcs') : 'pcs';
                const factor = ing.conversion_factor || this.computeFactor(ing.unit, baseUnit);
                const baseQty = (parseFloat(ing.quantity) || 0) * factor;

                if (baseQty < 0.001) return baseQty.toFixed(4) + ' ' + baseUnit;
                if (baseQty < 0.01) return baseQty.toFixed(3) + ' ' + baseUnit;
                return baseQty.toFixed(2) + ' ' + baseUnit;
            },

            lineCost(ing) {
                if (!ing.raw_material_id || !ing.quantity || ing.quantity <= 0) return 0;
                const rm = this.availableMaterials.find(r => r.id == ing.raw_material_id);
                if (!rm) return 0;
                const baseUnit = rm.unit || 'pcs';
                const factor = ing.conversion_factor || this.computeFactor(ing.unit, baseUnit);
                const baseQty = (parseFloat(ing.quantity) || 0) * factor;
                return baseQty * this.materialPrice(rm);
            },

            totalBOMCost() {
                return this.ingredients.reduce((acc, ing) => acc + this.lineCost(ing), 0);
            },

            marginAmount() {
                return this.sellingPrice - this.totalBOMCost();
            },

            marginPct() {
                if (this.sellingPrice <= 0) return 0;
                return ((this.sellingPrice - this.totalBOMCost()) / this.sellingPrice) * 100;
            },

            recommendedSellingPrice() {
                const marginDec = Math.min(0.95, Math.max(0.01, (this.targetMargin || 25) / 100));
                const cost = this.totalBOMCost();
                if (cost <= 0) return this.sellingPrice;
                return marginDec < 1 ? cost / (1 - marginDec) : cost;
            },

            marginStatus() {
                if (this.totalBOMCost() <= 0) return 'healthy';
                const margin = this.marginPct();
                if (margin < 0) return 'critical';
                if (margin < this.targetMargin) return 'warning';
                return 'healthy';
            },

            batchCost() {
                return this.totalBOMCost() * (parseFloat(this.batchQty) || 1);
            },

            batchRevenue() {
                return this.sellingPrice * (parseFloat(this.batchQty) || 1);
            },

            batchProfit() {
                return this.batchRevenue() - this.batchCost();
            },

            formatCurrency(val) {
                return Number(val || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        };
    }
</script>
@endpush
@endsection
