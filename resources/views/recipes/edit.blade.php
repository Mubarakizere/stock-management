@extends('layouts.app')
@section('title', 'Recipe — ' . $product->name)

@section('content')
@php
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $existingIngredients = $product->recipeItems->map(fn($r) => [
        'raw_material_id' => $r->raw_material_id,
        'quantity'        => (float) $r->quantity,
    ])->values()->toArray();
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="recipeEditor({{ json_encode($existingIngredients) }}, {{ json_encode($rawMaterials->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'price' => (float)$r->price])) }})">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i data-lucide="chef-hat" class="w-6 h-6 text-orange-600 dark:text-orange-400"></i>
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Recipe
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $product->name }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('products.show', $product) }}"
               class="btn btn-outline flex items-center gap-1 text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Product</span>
            </a>
        </div>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 p-4 text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Errors --}}
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg p-4 text-sm">
            <p class="font-medium">Please fix the following:</p>
            <ul class="list-disc pl-5 mt-2 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info --}}
    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 p-4 text-sm text-blue-700 dark:text-blue-300 flex items-start gap-2">
        <i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
        <p>Define the raw materials and quantities needed to produce <strong>1 unit</strong> of {{ $product->name }}. This will be used during production to auto-deduct raw materials.</p>
    </div>

    {{-- Recipe Form --}}
    <form action="{{ route('recipes.update', $product) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">

            {{-- Table Header --}}
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4"></i>
                    Ingredients
                    <span class="ml-1 px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 text-xs font-medium"
                          x-text="ingredients.length"></span>
                </h2>
                <button type="button"
                        @click="addIngredient()"
                        class="btn btn-outline btn-sm flex items-center gap-1 text-xs">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Add Ingredient
                </button>
            </div>

            {{-- Ingredient Rows --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="(ing, index) in ingredients" :key="index">
                    <div class="px-4 py-3 grid grid-cols-12 gap-3 items-center">
                        {{-- Row number --}}
                        <div class="col-span-1 text-center">
                            <span class="text-xs text-gray-400 font-medium" x-text="index + 1"></span>
                        </div>

                        {{-- Raw Material Select --}}
                        <div class="col-span-6">
                            <select
                                :name="'ingredients[' + index + '][raw_material_id]'"
                                x-model="ing.raw_material_id"
                                class="form-select w-full text-sm"
                                required>
                                <option value="">Select raw material…</option>
                                <template x-for="rm in availableMaterials" :key="rm.id">
                                    <option :value="rm.id" x-text="rm.name" :selected="ing.raw_material_id == rm.id"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Quantity --}}
                        <div class="col-span-3">
                            <input type="number"
                                   :name="'ingredients[' + index + '][quantity]'"
                                   x-model.number="ing.quantity"
                                   step="0.01" min="0.01"
                                   class="form-input w-full text-sm text-right"
                                   placeholder="Qty"
                                   required>
                        </div>

                        {{-- Remove --}}
                        <div class="col-span-2 text-center">
                            <button type="button"
                                    @click="removeIngredient(index)"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty State --}}
            <div x-show="ingredients.length === 0"
                 class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                <i data-lucide="flask-conical" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                <p>No ingredients added yet.</p>
                <button type="button"
                        @click="addIngredient()"
                        class="mt-3 btn btn-primary btn-sm inline-flex items-center gap-1 text-xs">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Add First Ingredient
                </button>
            </div>

            {{-- Summary --}}
            <div x-show="ingredients.length > 0"
                 class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300">
                <span x-text="ingredients.length"></span> ingredient(s) •
                Estimated raw material cost:
                <strong class="text-gray-900 dark:text-gray-100">
                    RWF <span x-text="estimatedCost()"></span>
                </strong>
                per unit
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 mt-4">
            <a href="{{ route('products.show', $product) }}" class="btn btn-outline text-sm">Cancel</a>
            <button type="submit" class="btn btn-success text-sm flex items-center gap-1">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save Recipe
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });

    function recipeEditor(existing, rawMaterials) {
        return {
            ingredients: existing.length > 0 ? existing : [],
            availableMaterials: rawMaterials,

            addIngredient() {
                this.ingredients.push({ raw_material_id: '', quantity: 1 });
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            removeIngredient(index) {
                this.ingredients.splice(index, 1);
            },

            estimatedCost() {
                let total = 0;
                this.ingredients.forEach(ing => {
                    const rm = this.availableMaterials.find(r => r.id == ing.raw_material_id);
                    if (rm && ing.quantity > 0) {
                        total += rm.price * ing.quantity;
                    }
                });
                return total.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        };
    }
</script>
@endpush
@endsection
