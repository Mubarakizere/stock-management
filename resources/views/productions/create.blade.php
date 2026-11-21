@extends('layouts.app')
@section('title', 'Record Production Run & Batch Lot')

@section('content')
@php
    $productRecipes = [];
    foreach ($products as $p) {
        $productRecipes[$p->id] = [
            'name'       => $p->name,
            'unit'       => $p->unit ?? 'pcs',
            'has_recipe' => ($p->recipeItems && $p->recipeItems->isNotEmpty()),
            'recipe'     => ($p->recipeItems ?? collect())->map(fn($ri) => [
                'raw_material_name' => $ri->rawMaterial->name ?? 'Unknown',
                'quantity'          => (float) $ri->quantity,
                'recipe_unit'       => $ri->unit ?: ($ri->rawMaterial->unit ?? 'pcs'),
                'base_unit'         => $ri->rawMaterial->unit ?? 'pcs',
                'conversion_factor' => (float) ($ri->conversion_factor ?: 1.0),
                'unit_cost'         => (float) ($ri->rawMaterial->weightedAverageCost() ?: $ri->rawMaterial->cost_price ?: $ri->rawMaterial->price ?: 0),
                'available'         => (float) ($ri->rawMaterial->currentStock()),
            ])->values()->toArray(),
        ];
    }
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="productionWorkstation({{ json_encode($productRecipes) }}, {{ (int)($selectedProductId ?? 0) }}, '{{ $defaultBatchNumber }}', '{{ $defaultMfgDate }}', '{{ $defaultExpiryDate }}')">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('productions.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Production Runs</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white font-semibold">New Batch Run</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1 flex items-center gap-2.5">
                <span>Record Production Batch</span>
                <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 border border-teal-200/60 dark:border-teal-800/60">
                    Batch &amp; Yield Tracking
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Execute manufacturing batches with automated Bill of Materials consumption, Lot traceability, and Yield/Scrap variance monitoring.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <template x-if="selectedProductId && hasRecipe()">
                <a :href="'/products/' + selectedProductId + '/recipe'"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    Edit Product Recipe
                </a>
            </template>

            <a href="{{ route('productions.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Cancel
            </a>
        </div>
    </div>

    {{-- Error Banner --}}
    @if (($errors ?? null)?->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            <div class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Workstation Grid --}}
    <form action="{{ route('productions.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- Left Column: Batch Configuration & Yield Variance (7 cols) --}}
            <div class="lg:col-span-7 space-y-5">

                {{-- Card 1: Product & Planned vs Actual Yield --}}
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                                1
                            </span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Product &amp; Yield Variance</h2>
                        </div>
                        <span class="text-[11px] font-mono text-slate-400 uppercase tracking-wider">Step 1 of 3</span>
                    </div>

                    {{-- Product Selection --}}
                    <div>
                        <label for="product_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Target Finished Product <span class="text-rose-500">*</span>
                        </label>
                        <select id="product_id"
                                name="product_id"
                                x-model="selectedProductId"
                                @change="onProductChange()"
                                required
                                class="w-full py-2.5 px-3 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                            <option value="">Select product to manufacture…</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ (string)old('product_id', $selectedProductId) === (string)$p->id ? 'selected' : '' }}>
                                    {{ $p->name }} (Unit: {{ $p->unit ?? 'pcs' }})
                                </option>
                            @endforeach
                        </select>

                        {{-- Recipe status feedback --}}
                        <div class="mt-2.5" x-show="selectedProductId">
                            <template x-if="hasRecipe()">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Active BOM Recipe &bull; <span class="font-bold font-mono" x-text="recipe.length"></span> ingredient(s) mapped</span>
                                </div>
                            </template>

                            <template x-if="!hasRecipe()">
                                <div class="rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 p-3 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <div>
                                        <p class="font-semibold">This product does not have a recipe configured.</p>
                                        <p class="mt-0.5 text-amber-700 dark:text-amber-300">
                                            A Bill of Materials is required before running batches.
                                            <a :href="'/products/' + selectedProductId + '/recipe'" class="font-bold underline ml-1 hover:text-amber-900 dark:hover:text-amber-100">Configure Recipe &rarr;</a>
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Yield Variance Inputs: Planned Yield vs Actual Output vs Scrap --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        {{-- Planned / Expected Output --}}
                        <div>
                            <label for="expected_quantity" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Expected Recipe Yield <span class="text-slate-400 font-normal">(Planned Batch)</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       id="expected_quantity"
                                       name="expected_quantity"
                                       min="0.01"
                                       step="any"
                                       x-model.number="expectedQuantity"
                                       @input="recalculateYield()"
                                       value="{{ old('expected_quantity', 1) }}"
                                       class="w-full py-2.5 pl-3.5 pr-16 text-sm font-bold font-mono rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"
                                       placeholder="100.00">
                                <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-semibold text-slate-400 pointer-events-none"
                                      x-text="currentProductUnit()"></span>
                            </div>
                        </div>

                        {{-- Actual Good Output --}}
                        <div>
                            <label for="quantity" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Actual Good Output <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       id="quantity"
                                       name="quantity"
                                       min="0.01"
                                       step="any"
                                       x-model.number="quantity"
                                       @input="recalculateYield()"
                                       value="{{ old('quantity', 1) }}"
                                       required
                                       class="w-full py-2.5 pl-3.5 pr-16 text-sm font-bold font-mono rounded-xl border border-teal-300 dark:border-teal-700 bg-white dark:bg-slate-800 text-teal-700 dark:text-teal-300 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"
                                       placeholder="95.00">
                                <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-semibold text-teal-600 pointer-events-none"
                                      x-text="currentProductUnit()"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Yield & Scrap Indicator Card --}}
                    <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Production Efficiency &amp; Scrap</span>
                            <span :class="yieldPercentage >= 95 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : (yieldPercentage >= 85 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300')"
                                  class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold"
                                  x-text="yieldPercentage.toFixed(1) + '% Yield Efficiency'"></span>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden flex">
                            <div class="h-full bg-emerald-500 transition-all duration-300" :style="'width: ' + Math.min(100, yieldPercentage) + '%'"></div>
                            <div class="h-full bg-rose-500 transition-all duration-300" :style="'width: ' + Math.max(0, 100 - yieldPercentage) + '%'"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-xs pt-1">
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Scrap / Lost Units:</span>
                                <span class="font-mono font-bold ml-1" :class="wasteQuantity > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300'" x-text="wasteQuantity.toFixed(2) + ' ' + currentProductUnit()"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-500 dark:text-slate-400">Shrinkage / Scrap Rate:</span>
                                <span class="font-mono font-bold ml-1" :class="scrapPercentage > 5 ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'" x-text="scrapPercentage.toFixed(1) + '%'"></span>
                            </div>
                        </div>

                        {{-- Scrap Reason (Visible if scrap exists) --}}
                        <div x-show="wasteQuantity > 0" class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
                            <label for="waste_reason" class="block text-[11px] font-semibold text-rose-700 dark:text-rose-300 mb-1">
                                Scrap / Waste Root Cause
                            </label>
                            <select id="waste_reason"
                                    name="waste_reason"
                                    x-model="wasteReason"
                                    class="w-full py-2 px-3 text-xs rounded-xl border border-rose-200 dark:border-rose-800/60 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500">
                                <option value="">Select cause of loss / scrap...</option>
                                <option value="Normal trimming & evaporation">Normal trimming &amp; evaporation</option>
                                <option value="Oven over-bake / burnt batch">Oven over-bake / burnt batch</option>
                                <option value="Spillage on packaging line">Spillage on packaging line</option>
                                <option value="Defective container / packaging seal fail">Defective container / packaging seal fail</option>
                                <option value="Quality control sample rejected">Quality control sample rejected</option>
                                <option value="Equipment calibration loss">Equipment calibration loss</option>
                                <option value="Other operator error">Other operator error</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Batch / Lot Numbering & Expiration Tracking --}}
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                                2
                            </span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Batch Lot &amp; Expiration Tracking</h2>
                        </div>
                        <span class="text-[11px] font-mono text-slate-400 uppercase tracking-wider">Step 2 of 3</span>
                    </div>

                    {{-- Batch Number --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="batch_number" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Batch / Lot Number <span class="text-rose-500">*</span>
                            </label>
                            <button type="button"
                                    @click="generateNewBatch()"
                                    class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Re-generate</span>
                            </button>
                        </div>
                        <div class="relative">
                            <input type="text"
                                   id="batch_number"
                                   name="batch_number"
                                   x-model="batchNumber"
                                   required
                                   class="w-full py-2.5 px-3.5 text-xs font-mono font-bold rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-indigo-600 dark:text-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                                   placeholder="BATCH-YYYYMMDD-XXXX">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Unique batch code stamped on finished products for recall traceability.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        {{-- Manufacturing Date --}}
                        <div>
                            <label for="mfg_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Manufacturing Date (MFG)
                            </label>
                            <input type="date"
                                   id="mfg_date"
                                   name="mfg_date"
                                   x-model="mfgDate"
                                   class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        {{-- Expiration Date --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="expiry_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Expiration Date (EXP)
                                </label>
                                <div class="flex items-center gap-1 text-[10px]">
                                    <button type="button" @click="addMonthsToExpiry(3)" class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300">+3M</button>
                                    <button type="button" @click="addMonthsToExpiry(6)" class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300">+6M</button>
                                    <button type="button" @click="addMonthsToExpiry(12)" class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300">+1Y</button>
                                </div>
                            </div>
                            <input type="date"
                                   id="expiry_date"
                                   name="expiry_date"
                                   x-model="expiryDate"
                                   class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                {{-- Card 3: Execution Time & Notes --}}
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                                3
                            </span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Operator Log &amp; Notes</h2>
                        </div>
                        <span class="text-[11px] font-mono text-slate-400 uppercase tracking-wider">Step 3 of 3</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Production Timestamp --}}
                        <div>
                            <label for="produced_at" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Production Timestamp
                            </label>
                            <input type="datetime-local"
                                   id="produced_at"
                                   name="produced_at"
                                   value="{{ old('produced_at', now()->format('Y-m-d\TH:i')) }}"
                                   class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>

                        {{-- Batch Notes --}}
                        <div>
                            <label for="notes" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Shift Notes / Machine Line <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input type="text"
                                   id="notes"
                                   name="notes"
                                   value="{{ old('notes') }}"
                                   placeholder="e.g. Line 2, Morning Shift, Oven #4"
                                   class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Live Bill of Materials & Stock Check Terminal (5 cols) --}}
            <div class="lg:col-span-5 lg:sticky lg:top-6 space-y-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
                    {{-- Terminal Header --}}
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                                <span>BOM Stock Consumption</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Deduction based on planned batch input</p>
                        </div>

                        <template x-if="recipe.length > 0">
                            <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/60">
                                <span x-text="recipe.length"></span> items
                            </span>
                        </template>
                    </div>

                    {{-- Empty State (No product selected yet) --}}
                    <div x-show="!selectedProductId" class="p-8 text-center text-slate-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="text-xs">Select a finished product to inspect required raw materials and verify warehouse balances.</p>
                    </div>

                    {{-- Active Recipe Material Rows --}}
                    <div x-show="selectedProductId && recipe.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800/80 max-h-96 overflow-y-auto">
                        <template x-for="(m, i) in recipe" :key="i">
                            <div class="p-4 transition hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                                 :class="m.totalBaseNeeded > m.available ? 'bg-rose-50/40 dark:bg-rose-950/20' : ''">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-semibold text-slate-900 dark:text-white truncate" x-text="m.raw_material_name"></div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                            Ratio: <span class="font-mono" x-text="m.quantity + ' ' + m.recipe_unit"></span> per unit
                                        </div>
                                    </div>

                                    <div class="text-right shrink-0">
                                        <div class="text-xs font-bold font-mono"
                                             :class="m.totalBaseNeeded > m.available ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'"
                                             x-text="formatTotalConsumption(m)">
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            In stock: <span :class="m.totalBaseNeeded > m.available ? 'text-rose-600 font-bold' : ''" x-text="m.available.toFixed(2) + ' ' + m.base_unit"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status bar --}}
                                <div class="mt-2 flex items-center justify-between text-[11px]">
                                    <template x-if="m.totalBaseNeeded <= m.available">
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Stock Sufficient
                                        </span>
                                    </template>
                                    <template x-if="m.totalBaseNeeded > m.available">
                                        <span class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400 font-semibold">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            Shortage: <span class="font-mono font-bold" x-text="(m.totalBaseNeeded - m.available).toFixed(2) + ' ' + m.base_unit"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Cost & Unit Valuation Summary --}}
                    <div x-show="selectedProductId && recipe.length > 0"
                         class="p-4 bg-slate-50/70 dark:bg-slate-900/60 border-t border-slate-100 dark:border-slate-800 space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Total Batch Input Cost:</span>
                            <span class="font-mono font-medium text-slate-700 dark:text-slate-300">
                                RWF <span x-text="totalCost()"></span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <span>Cost Per Good Unit Produced:</span>
                            <span class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                                RWF <span x-text="costPerGoodUnit()"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Shortage Alert Banner --}}
                    <div x-show="hasShortages()"
                         class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border-t border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Cannot execute batch: insufficient stock for one or more ingredients.</span>
                    </div>

                    {{-- Submit Button --}}
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="submit"
                                :disabled="!canSubmit()"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-xs font-semibold text-white
                                       bg-teal-600 hover:bg-teal-700 active:bg-teal-800 disabled:bg-slate-200 dark:disabled:bg-slate-800
                                       disabled:text-slate-400 dark:disabled:text-slate-600 disabled:cursor-not-allowed transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Execute Batch Run &amp; Deduct BOM</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function productionWorkstation(recipes, preselected, defaultBatch, defaultMfg, defaultExpiry) {
        return {
            selectedProductId: preselected || '',
            expectedQuantity: 100,
            quantity: 100,
            wasteQuantity: 0,
            wasteReason: '',
            yieldPercentage: 100,
            scrapPercentage: 0,
            batchNumber: defaultBatch || '',
            mfgDate: defaultMfg || '',
            expiryDate: defaultExpiry || '',
            recipe: [],

            init() {
                if (this.selectedProductId) this.onProductChange();
                this.recalculateYield();
            },

            generateNewBatch() {
                const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
                const rand = Math.random().toString(36).substring(2, 6).toUpperCase();
                this.batchNumber = 'BATCH-' + dateStr + '-' + rand;
            },

            addMonthsToExpiry(months) {
                const base = this.mfgDate ? new Date(this.mfgDate) : new Date();
                base.setMonth(base.getMonth() + months);
                this.expiryDate = base.toISOString().slice(0, 10);
            },

            currentProductUnit() {
                if (!this.selectedProductId || !recipes[this.selectedProductId]) return 'units';
                return recipes[this.selectedProductId].unit || 'units';
            },

            hasRecipe() {
                if (!this.selectedProductId || !recipes[this.selectedProductId]) return false;
                return Boolean(recipes[this.selectedProductId].has_recipe);
            },

            recalculateYield() {
                const expected = Number(this.expectedQuantity) || 0;
                const actual = Number(this.quantity) || 0;

                this.wasteQuantity = Math.max(0, expected - actual);

                if (expected > 0) {
                    this.yieldPercentage = (actual / expected) * 100;
                    this.scrapPercentage = (this.wasteQuantity / expected) * 100;
                } else {
                    this.yieldPercentage = 100;
                    this.scrapPercentage = 0;
                }

                this.updatePreview();
            },

            onProductChange() {
                const data = recipes[this.selectedProductId];
                const inputBatchSize = Math.max(Number(this.expectedQuantity) || 0, Number(this.quantity) || 1);

                this.recipe = (data && data.recipe)
                    ? data.recipe.map(r => {
                        const factor = r.conversion_factor || 1.0;
                        return {
                            ...r,
                            totalRecipeNeeded: r.quantity * inputBatchSize,
                            totalBaseNeeded: r.quantity * factor * inputBatchSize
                        };
                    })
                    : [];
            },

            updatePreview() {
                const inputBatchSize = Math.max(Number(this.expectedQuantity) || 0, Number(this.quantity) || 1);

                this.recipe = this.recipe.map(r => {
                    const factor = r.conversion_factor || 1.0;
                    return {
                        ...r,
                        totalRecipeNeeded: r.quantity * inputBatchSize,
                        totalBaseNeeded: r.quantity * factor * inputBatchSize
                    };
                });
            },

            formatTotalConsumption(m) {
                if (m.recipe_unit !== m.base_unit) {
                    return m.totalRecipeNeeded.toFixed(2) + ' ' + m.recipe_unit + ' (' + m.totalBaseNeeded.toFixed(2) + ' ' + m.base_unit + ')';
                }
                return m.totalBaseNeeded.toFixed(2) + ' ' + m.base_unit;
            },

            hasShortages() {
                if (!this.selectedProductId || this.recipe.length === 0) return false;
                return this.recipe.some(r => r.totalBaseNeeded > r.available);
            },

            canSubmit() {
                return Boolean(
                    this.selectedProductId &&
                    this.quantity > 0 &&
                    this.hasRecipe() &&
                    this.recipe.length > 0 &&
                    !this.hasShortages()
                );
            },

            totalCost() {
                const total = this.recipe.reduce((s, r) => s + (r.unit_cost * r.totalBaseNeeded), 0);
                return total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            costPerGoodUnit() {
                const actual = Number(this.quantity) || 1;
                const total = this.recipe.reduce((s, r) => s + (r.unit_cost * r.totalBaseNeeded), 0);
                const unit = actual > 0 ? (total / actual) : 0;
                return unit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>
@endpush
@endsection
