@extends('layouts.app')
@section('title', 'New Production Run')

@section('content')
@php
    $productRecipes = [];
    foreach ($products as $p) {
        $productRecipes[$p->id] = [
            'name'       => $p->name,
            'unit'       => $p->unit ?? 'pcs',
            'has_recipe' => ($p->recipeItems && $p->recipeItems->isNotEmpty()),
            'recipe'     => ($p->recipeItems ?? collect())->map(fn($ri) => [
                'raw_material_name' => $ri->rawMaterial->name ?? '???',
                'quantity'          => (float) $ri->quantity,
                'recipe_unit'       => $ri->unit ?: ($ri->rawMaterial->unit ?? 'pcs'),
                'base_unit'         => $ri->rawMaterial->unit ?? 'pcs',
                'conversion_factor' => (float) ($ri->conversion_factor ?: 1.0),
                'unit_cost'         => (float) ($ri->rawMaterial->price ?? 0),
                'available'         => (float) ($ri->rawMaterial->currentStock()),
            ])->values()->toArray(),
        ];
    }
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="productionWorkstation({{ json_encode($productRecipes) }}, {{ $selectedProductId }})">

    {{-- Page Header --}}
    <div class="flex items-center justify-between pb-1">
        <div class="flex items-center gap-3">
            <a href="{{ route('productions.index') }}"
               class="w-9 h-9 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 hover:text-gray-900 dark:hover:text-gray-100 flex items-center justify-center transition shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                    Record Production Run
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Execute a manufacturing batch and deduct ingredient inventory.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <template x-if="selectedProductId && hasRecipe()">
                <a :href="'/products/' + selectedProductId + '/recipe'"
                   class="btn btn-outline text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-xl">
                    <i data-lucide="chef-hat" class="w-3.5 h-3.5 text-orange-500"></i>
                    <span>Edit Recipe</span>
                </a>
            </template>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-4 shadow-sm">
            <div class="flex items-center gap-2 text-sm font-semibold text-rose-700 dark:text-rose-300 mb-1">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600"></i>
                <span>Please resolve the following:</span>
            </div>
            <ul class="text-xs text-rose-600 dark:text-rose-400 list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main 2-Column Workstation Layout --}}
    <form action="{{ route('productions.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- Left Column: Batch Configuration (7 cols) --}}
            <div class="lg:col-span-7 space-y-5">

                {{-- Product & Quantity Card --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl shadow-sm p-6 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/80">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <i data-lucide="box" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i>
                            <span>Output Product & Batch Size</span>
                        </h2>
                        <span class="text-[11px] font-mono text-gray-400 uppercase tracking-wider">Step 1 of 2</span>
                    </div>

                    {{-- Product Selection --}}
                    <div>
                        <label for="product_id" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Target Finished Product <span class="text-rose-500">*</span>
                        </label>
                        <select id="product_id" name="product_id"
                                class="form-select w-full text-sm rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50"
                                x-model="selectedProductId"
                                @change="onProductChange()"
                                required>
                            <option value="">Select product to produce…</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ old('product_id', $selectedProductId) == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} (Unit: {{ $p->unit ?? 'pcs' }})
                                </option>
                            @endforeach
                        </select>

                        {{-- Recipe availability indicator --}}
                        <div class="mt-2" x-show="selectedProductId">
                            <template x-if="hasRecipe()">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                    <span>Recipe active · <span x-text="recipe.length"></span> ingredient(s) configured</span>
                                </div>
                            </template>

                            <template x-if="!hasRecipe()">
                                <div class="rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 p-3 text-xs text-amber-800 dark:text-amber-200 flex items-start gap-2">
                                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 mt-0.5 shrink-0"></i>
                                    <div>
                                        <p class="font-semibold">This product does not have a recipe yet.</p>
                                        <p class="mt-0.5 text-amber-700 dark:text-amber-300">
                                            A Bill of Materials is required to auto-deduct raw materials.
                                            <a :href="'/products/' + selectedProductId + '/recipe'" class="font-bold underline ml-1">Configure Recipe →</a>
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Quantity Input --}}
                    <div>
                        <label for="quantity" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Batch Quantity to Manufacture <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm flex items-center">
                            <input type="number" id="quantity" name="quantity" min="0.01" step="any"
                                   x-model.number="quantity"
                                   @input="updatePreview()"
                                   value="{{ old('quantity', 1) }}"
                                   required
                                   class="block w-full py-2.5 px-3.5 text-base font-bold font-mono rounded-l-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:ring-teal-500 focus:border-teal-500"
                                   placeholder="1">
                            <span class="inline-flex items-center px-4 py-2.5 rounded-r-xl border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-semibold uppercase tracking-wider select-none"
                                  x-text="currentProductUnit()">
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Batch Schedule & Audit Details Card --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/80">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i>
                            <span>Schedule & Batch Information</span>
                        </h2>
                        <span class="text-[11px] font-mono text-gray-400 uppercase tracking-wider">Step 2 of 2</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Production Date --}}
                        <div>
                            <label for="produced_at" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Production Timestamp
                            </label>
                            <input type="datetime-local" id="produced_at" name="produced_at"
                                   value="{{ old('produced_at', now()->format('Y-m-d\TH:i')) }}"
                                   class="form-input w-full text-xs sm:text-sm rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50">
                        </div>

                        {{-- Batch Notes --}}
                        <div>
                            <label for="notes" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Batch Reference / Notes <span class="text-gray-400 font-normal">(Optional)</span>
                            </label>
                            <input type="text" id="notes" name="notes"
                                   value="{{ old('notes') }}"
                                   placeholder="e.g. Batch #2026-A, Shift 1"
                                   class="form-input w-full text-xs sm:text-sm rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Live Bill of Materials & Stock Check Terminal (5 cols) --}}
            <div class="lg:col-span-5 lg:sticky lg:top-6 space-y-4">

                <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl shadow-sm overflow-hidden">

                    {{-- Terminal Header --}}
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/80 bg-gray-50/50 dark:bg-gray-900/30 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <i data-lucide="flask-conical" class="w-4 h-4 text-orange-500"></i>
                                <span>Material Consumption</span>
                            </h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">Live stock verification</p>
                        </div>

                        <template x-if="recipe.length > 0">
                            <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/60">
                                <span x-text="recipe.length"></span> items
                            </span>
                        </template>
                    </div>

                    {{-- Empty State (No product selected yet) --}}
                    <div x-show="!selectedProductId" class="p-8 text-center text-gray-400">
                        <i data-lucide="layers" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                        <p class="text-xs">Select a product to inspect the Bill of Materials and verify stock availability.</p>
                    </div>

                    {{-- Active Recipe Material Rows --}}
                    <div x-show="selectedProductId && recipe.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700/60 max-h-96 overflow-y-auto">
                        <template x-for="(m, i) in recipe" :key="i">
                            <div class="p-4 transition hover:bg-gray-50/50 dark:hover:bg-gray-700/20"
                                 :class="m.totalBaseNeeded > m.available ? 'bg-rose-50/40 dark:bg-rose-950/20' : ''">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="m.raw_material_name"></div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                            Recipe: <span x-text="m.quantity + ' ' + m.recipe_unit"></span> / unit
                                        </div>
                                    </div>

                                    <div class="text-right shrink-0">
                                        <div class="text-xs font-bold font-mono"
                                             :class="m.totalBaseNeeded > m.available ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100'"
                                             x-text="formatTotalConsumption(m)">
                                        </div>
                                        <div class="text-[10px] text-gray-400 mt-0.5">
                                            In stock: <span :class="m.totalBaseNeeded > m.available ? 'text-rose-600 font-bold' : ''" x-text="m.available.toFixed(2) + ' ' + m.base_unit"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status bar --}}
                                <div class="mt-2 flex items-center justify-between text-[11px]">
                                    <template x-if="m.totalBaseNeeded <= m.available">
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                            <i data-lucide="check" class="w-3 h-3"></i> Stock Sufficient
                                        </span>
                                    </template>
                                    <template x-if="m.totalBaseNeeded > m.available">
                                        <span class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400 font-semibold">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i> Shortage: <span x-text="(m.totalBaseNeeded - m.available).toFixed(2) + ' ' + m.base_unit"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Cost Summary --}}
                    <div x-show="selectedProductId && recipe.length > 0"
                         class="p-4 bg-gray-50/70 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700/80 space-y-2">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Cost per unit</span>
                            <span class="font-mono font-medium text-gray-700 dark:text-gray-300">
                                RWF <span x-text="costPerUnit()"></span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm font-bold text-gray-900 dark:text-gray-100 pt-1 border-t border-gray-200/60 dark:border-gray-700/60">
                            <span>Total Batch Material Cost</span>
                            <span class="font-mono text-teal-600 dark:text-teal-400">
                                RWF <span x-text="totalCost()"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Shortage Alert Banner --}}
                    <div x-show="hasShortages()"
                         class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border-t border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300 flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 text-rose-500"></i>
                        <span>Cannot execute batch: insufficient stock for one or more ingredients.</span>
                    </div>

                    {{-- Submit Button --}}
                    <div class="p-4 border-t border-gray-100 dark:border-gray-700/80">
                        <button type="submit"
                                :disabled="!canSubmit()"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold text-white
                                       bg-teal-600 hover:bg-teal-700 active:bg-teal-800 disabled:bg-gray-200 dark:disabled:bg-gray-700
                                       disabled:text-gray-400 dark:disabled:text-gray-500 disabled:cursor-not-allowed transition shadow-sm hover:shadow">
                            <i data-lucide="play" class="w-4 h-4"></i>
                            <span>Execute Production Run</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });

    function productionWorkstation(recipes, preselected) {
        return {
            selectedProductId: preselected || '',
            quantity: 1,
            recipe: [],

            init() {
                if (this.selectedProductId) this.onProductChange();
            },

            currentProductUnit() {
                if (!this.selectedProductId || !recipes[this.selectedProductId]) return 'units';
                return recipes[this.selectedProductId].unit || 'units';
            },

            hasRecipe() {
                if (!this.selectedProductId || !recipes[this.selectedProductId]) return false;
                return Boolean(recipes[this.selectedProductId].has_recipe);
            },

            onProductChange() {
                const data = recipes[this.selectedProductId];
                const q = this.quantity || 1;
                this.recipe = (data && data.recipe)
                    ? data.recipe.map(r => {
                        const factor = r.conversion_factor || 1.0;
                        return {
                            ...r,
                            totalRecipeNeeded: r.quantity * q,
                            totalBaseNeeded: r.quantity * factor * q
                        };
                    })
                    : [];
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            updatePreview() {
                const q = this.quantity || 0;
                this.recipe = this.recipe.map(r => {
                    const factor = r.conversion_factor || 1.0;
                    return {
                        ...r,
                        totalRecipeNeeded: r.quantity * q,
                        totalBaseNeeded: r.quantity * factor * q
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

            costPerUnit() {
                const total = this.recipe.reduce((s, r) => {
                    const factor = r.conversion_factor || 1.0;
                    return s + (r.unit_cost * (r.quantity * factor));
                }, 0);
                return total.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            totalCost() {
                const total = this.recipe.reduce((s, r) => s + (r.unit_cost * r.totalBaseNeeded), 0);
                return total.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>
@endpush
@endsection
