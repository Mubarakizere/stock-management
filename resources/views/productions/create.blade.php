@extends('layouts.app')
@section('title', 'New Production Run')

@section('content')
@php
    $productRecipes = [];
    foreach ($products as $p) {
        $productRecipes[$p->id] = [
            'name'   => $p->name,
            'recipe' => ($p->recipeItems ?? collect())->map(fn($ri) => [
                'raw_material_name' => $ri->rawMaterial->name ?? '???',
                'quantity'          => (float) $ri->quantity,
                'unit_cost'         => (float) ($ri->rawMaterial->price ?? 0),
                'available'         => (float) ($ri->rawMaterial->currentStock()),
            ])->values()->toArray(),
        ];
    }
@endphp

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="productionForm({{ json_encode($productRecipes) }}, {{ $selectedProductId }})">

    {{-- Page header --}}
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('productions.index') }}"
           class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 leading-tight">New Production Run</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Convert raw materials into finished goods</p>
        </div>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="mb-6 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-700 p-4">
            <div class="flex items-center gap-2 text-sm font-semibold text-rose-700 dark:text-rose-300 mb-2">
                <i data-lucide="alert-circle" class="w-4 h-4"></i> Fix the following errors
            </div>
            <ul class="text-sm text-rose-600 dark:text-rose-400 list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('productions.store') }}" method="POST" class="space-y-4">
        @csrf

        {{-- ① Product --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <span class="w-7 h-7 rounded-full bg-violet-600 text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
                <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm">Select Product</span>
            </div>
            <div class="p-5">
                <select id="product_id" name="product_id"
                        class="form-select w-full text-sm"
                        x-model="selectedProductId"
                        @change="onProductChange()">
                    <option value="">Choose the product to produce…</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ old('product_id', $selectedProductId) == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ② Quantity --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <span class="w-7 h-7 rounded-full bg-violet-600 text-white text-xs font-bold flex items-center justify-center shrink-0">2</span>
                <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm">Set Quantity</span>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-3">
                    <button type="button"
                            @click="quantity = Math.max(1, quantity - 1); updatePreview()"
                            class="w-10 h-10 rounded-xl border-2 border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400
                                   hover:border-violet-400 hover:text-violet-600 dark:hover:border-violet-500 dark:hover:text-violet-400
                                   flex items-center justify-center text-xl font-light transition-all focus:outline-none">
                        −
                    </button>
                    <input type="number" id="quantity" name="quantity" min="1" step="1"
                           x-model.number="quantity"
                           @input="updatePreview()"
                           value="{{ old('quantity', 1) }}"
                           class="form-input text-center text-xl font-bold w-28 rounded-xl border-2 border-gray-200 dark:border-gray-600
                                  focus:border-violet-500 dark:focus:border-violet-400">
                    <button type="button"
                            @click="quantity++; updatePreview()"
                            class="w-10 h-10 rounded-xl border-2 border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400
                                   hover:border-violet-400 hover:text-violet-600 dark:hover:border-violet-500 dark:hover:text-violet-400
                                   flex items-center justify-center text-xl font-light transition-all focus:outline-none">
                        +
                    </button>
                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">unit(s)</span>
                </div>
            </div>
        </div>

        {{-- ③ Details --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <span class="w-7 h-7 rounded-full bg-violet-600 text-white text-xs font-bold flex items-center justify-center shrink-0">3</span>
                <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm">Details</span>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="produced_at" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                        Production Date
                    </label>
                    <input type="datetime-local" id="produced_at" name="produced_at"
                           value="{{ old('produced_at', now()->format('Y-m-d\TH:i')) }}"
                           class="form-input w-full text-sm">
                </div>
                <div>
                    <label for="notes" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                        Notes <span class="normal-case text-gray-400">(optional)</span>
                    </label>
                    <input type="text" id="notes" name="notes"
                           value="{{ old('notes') }}"
                           class="form-input w-full text-sm"
                           placeholder="Batch ID, shift, etc.">
                </div>
            </div>
        </div>

        {{-- Materials Preview --}}
        <div x-show="selectedProductId && recipe.length > 0"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">

            {{-- Header with cost --}}
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="flask-conical" class="w-4 h-4 text-violet-600 dark:text-violet-400"></i>
                    <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm">
                        Materials required · <span x-text="quantity" class="text-violet-600"></span> unit(s)
                    </span>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-400">Est. cost</div>
                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100">RWF <span x-text="totalCost()"></span></div>
                </div>
            </div>

            {{-- Materials rows --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="(m, i) in recipe" :key="i">
                    <div class="px-5 py-3 flex items-center gap-4"
                         :class="m.totalNeeded > m.available ? 'bg-rose-50/60 dark:bg-rose-900/10' : ''">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="m.raw_material_name"></div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                <span x-text="m.quantity.toFixed(2)"></span> per unit
                                · <span x-text="m.available.toFixed(2)"></span> in stock
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-semibold" :class="m.totalNeeded > m.available ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100'" x-text="m.totalNeeded.toFixed(2)"></div>
                            <div class="mt-0.5">
                                <span x-show="m.totalNeeded <= m.available"
                                      class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                                    ✓ OK
                                </span>
                                <span x-show="m.totalNeeded > m.available"
                                      class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">
                                    ✗ Short
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Shortage banner --}}
            <div x-show="hasShortages()"
                 class="px-5 py-3 bg-rose-50 dark:bg-rose-900/20 border-t border-rose-200 dark:border-rose-700 text-sm text-rose-700 dark:text-rose-300 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                Not enough stock. Reduce quantity or buy more raw materials.
            </div>
        </div>

        {{-- No recipe warning --}}
        <div x-show="selectedProductId && recipe.length === 0" x-transition
             class="rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 p-4 flex items-start gap-3">
            <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/40 shrink-0">
                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
            </div>
            <div>
                <div class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-0.5">No recipe defined</div>
                <div class="text-sm text-amber-700 dark:text-amber-300">
                    This product needs a recipe before it can be produced.
                    <a :href="'/products/' + selectedProductId + '/recipe'"
                       class="underline font-medium ml-1">Set up recipe →</a>
                </div>
            </div>
        </div>

        {{-- Submit bar --}}
        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('productions.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1">
                <i data-lucide="x" class="w-4 h-4"></i> Cancel
            </a>
            <button type="submit"
                    :disabled="!canSubmit()"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-semibold text-white
                           bg-violet-600 hover:bg-violet-700 disabled:bg-gray-300 dark:disabled:bg-gray-600
                           disabled:cursor-not-allowed transition-all shadow-sm">
                <i data-lucide="play" class="w-4 h-4"></i>
                Record Production
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function productionForm(recipes, preselected) {
        return {
            selectedProductId: preselected || '',
            quantity: 1,
            recipe: [],

            init() {
                if (this.selectedProductId) this.onProductChange();
            },

            onProductChange() {
                const data = recipes[this.selectedProductId];
                this.recipe = data
                    ? data.recipe.map(r => ({ ...r, totalNeeded: r.quantity * this.quantity }))
                    : [];
            },

            updatePreview() {
                const q = this.quantity || 0;
                this.recipe = this.recipe.map(r => ({ ...r, totalNeeded: r.quantity * q }));
            },

            hasShortages() {
                return this.recipe.some(r => r.totalNeeded > r.available);
            },

            canSubmit() {
                return this.selectedProductId && this.quantity > 0
                    && this.recipe.length > 0 && !this.hasShortages();
            },

            totalCost() {
                const total = this.recipe.reduce((s, r) => s + r.unit_cost * r.totalNeeded, 0);
                return total.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>
@endpush
@endsection
