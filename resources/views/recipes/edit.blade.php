@extends('layouts.app')
@section('title', 'Recipe — ' . $product->name)

@section('content')
@php
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $existingIngredients = $product->recipeItems->map(fn($r) => [
        'raw_material_id'   => $r->raw_material_id,
        'quantity'          => (float) $r->quantity,
        'unit'              => $r->unit ?: ($r->rawMaterial->unit ?? 'pcs'),
        'conversion_factor' => (float) ($r->conversion_factor ?: 1.0),
    ])->values()->toArray();

    $materialsData = $rawMaterials->map(fn($r) => [
        'id'    => $r->id,
        'name'  => $r->name,
        'price' => (float) ($r->price ?? 0),
        'unit'  => strtolower($r->unit ?: 'pcs'),
    ])->values()->toArray();
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="recipeEditor({{ json_encode($existingIngredients) }}, {{ json_encode($materialsData) }}, {{ json_encode($unitFamilies ?? []) }})">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-950/50 border border-orange-200 dark:border-orange-800 flex items-center justify-center text-orange-600 dark:text-orange-400">
                <i data-lucide="chef-hat" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Recipe / Bill of Materials
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Finished Product: <strong class="text-gray-800 dark:text-gray-200">{{ $product->name }}</strong>
                    <span class="ml-2 px-2 py-0.5 rounded-md text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-mono">
                        Unit: {{ $product->unit ?? 'pcs' }}
                    </span>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('products.show', $product) }}"
               class="btn btn-outline flex items-center gap-1.5 text-xs sm:text-sm px-3.5 py-2 rounded-xl">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Product</span>
            </a>
        </div>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 p-4 text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Errors --}}
    @if ($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 rounded-xl p-4 text-sm shadow-sm">
            <p class="font-medium flex items-center gap-1.5">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                <span>Please fix the following:</span>
            </p>
            <ul class="list-disc pl-5 mt-2 space-y-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Guidance Info Card --}}
    <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/80 p-4 text-xs sm:text-sm text-blue-700 dark:text-blue-300 flex items-start gap-2.5">
        <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0"></i>
        <div>
            <p class="font-medium text-blue-800 dark:text-blue-200">Flexible Units of Measurement Supported</p>
            <p class="mt-0.5 text-blue-700/90 dark:text-blue-300/90">
                You can specify quantities in smaller or operational units (e.g., <strong>250 g</strong>, <strong>15 ml</strong>, <strong>1 bottle</strong>).
                The system automatically converts measurements to match the raw material’s base stock unit (e.g. 250 g = 0.25 kg) when calculating costs and deducting stock during production.
            </p>
        </div>
    </div>

    {{-- Recipe Form --}}
    <form action="{{ route('recipes.update', $product) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">

            {{-- Table Header --}}
            <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-orange-500"></i>
                    <span>Recipe Ingredients</span>
                    <span class="ml-1 px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 text-xs font-semibold"
                          x-text="ingredients.length"></span>
                </h2>
                <button type="button"
                        @click="addIngredient()"
                        class="btn btn-outline btn-sm flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Add Ingredient</span>
                </button>
            </div>

            {{-- Table Column Headers (Desktop) --}}
            <div class="hidden sm:grid grid-cols-12 gap-3 px-5 py-2.5 bg-gray-50 dark:bg-gray-900/40 border-b border-gray-100 dark:border-gray-700 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                <div class="col-span-1 text-center">#</div>
                <div class="col-span-5">Raw Material</div>
                <div class="col-span-2 text-right">Amount</div>
                <div class="col-span-2">Unit</div>
                <div class="col-span-1 text-right">Equiv.</div>
                <div class="col-span-1 text-center">Action</div>
            </div>

            {{-- Ingredient Rows --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="(ing, index) in ingredients" :key="index">
                    <div class="px-5 py-3.5 grid grid-cols-1 sm:grid-cols-12 gap-3 items-center hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition">

                        {{-- Row number --}}
                        <div class="hidden sm:block sm:col-span-1 text-center">
                            <span class="text-xs text-gray-400 font-medium" x-text="index + 1"></span>
                        </div>

                        {{-- Raw Material Select --}}
                        <div class="sm:col-span-5">
                            <label class="sm:hidden block text-xs font-medium text-gray-500 mb-1">Raw Material</label>
                            <select
                                :name="'ingredients[' + index + '][raw_material_id]'"
                                x-model="ing.raw_material_id"
                                @change="onMaterialChange(ing)"
                                class="form-select w-full text-xs sm:text-sm rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/60"
                                required>
                                <option value="">Select raw material…</option>
                                <template x-for="rm in availableMaterials" :key="rm.id">
                                    <option :value="rm.id"
                                            :selected="ing.raw_material_id == rm.id"
                                            x-text="rm.name + ' (Stock: ' + (rm.unit || 'pcs') + ')'">
                                    </option>
                                </template>
                            </select>
                        </div>

                        {{-- Quantity --}}
                        <div class="sm:col-span-2">
                            <label class="sm:hidden block text-xs font-medium text-gray-500 mb-1">Amount</label>
                            <input type="number"
                                   :name="'ingredients[' + index + '][quantity]'"
                                   x-model.number="ing.quantity"
                                   @input="recalculateRow(ing)"
                                   step="any" min="0.0001"
                                   class="form-input w-full text-xs sm:text-sm text-right rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/60"
                                   placeholder="Qty"
                                   required>
                        </div>

                        {{-- Measurement Unit --}}
                        <div class="sm:col-span-2">
                            <label class="sm:hidden block text-xs font-medium text-gray-500 mb-1">Unit</label>
                            <select
                                :name="'ingredients[' + index + '][unit]'"
                                x-model="ing.unit"
                                @change="onUnitChange(ing)"
                                class="form-select w-full text-xs sm:text-sm rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/60">
                                <optgroup label="Weight">
                                    <option value="g">Gram (g)</option>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="mg">Milligram (mg)</option>
                                </optgroup>
                                <optgroup label="Volume">
                                    <option value="ml">Milliliter (ml)</option>
                                    <option value="l">Liter (L)</option>
                                    <option value="cl">Centiliter (cl)</option>
                                </optgroup>
                                <optgroup label="Count / Containers">
                                    <option value="pcs">Pieces (pcs)</option>
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

                            {{-- Hidden conversion factor input --}}
                            <input type="hidden"
                                   :name="'ingredients[' + index + '][conversion_factor]'"
                                   :value="ing.conversion_factor">
                        </div>

                        {{-- Equivalent in Base Stock Unit --}}
                        <div class="sm:col-span-1 text-right">
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono"
                                 x-text="formatBaseEquiv(ing)"
                                 title="Converted amount in base stock unit">
                            </div>
                        </div>

                        {{-- Remove button --}}
                        <div class="sm:col-span-1 text-center flex sm:justify-center justify-end">
                            <button type="button"
                                    @click="removeIngredient(index)"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition"
                                    title="Remove ingredient">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty State --}}
            <div x-show="ingredients.length === 0"
                 class="px-4 py-12 text-center text-gray-500 dark:text-gray-400 text-sm">
                <div class="w-12 h-12 rounded-2xl bg-orange-50 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-800/80 flex items-center justify-center text-orange-500 mx-auto mb-3">
                    <i data-lucide="flask-conical" class="w-6 h-6"></i>
                </div>
                <p class="font-medium text-gray-700 dark:text-gray-300">No ingredients added yet.</p>
                <p class="text-xs text-gray-400 mt-1">Add raw materials and measurements to build the Bill of Materials.</p>
                <button type="button"
                        @click="addIngredient()"
                        class="mt-4 btn btn-primary btn-sm inline-flex items-center gap-1.5 text-xs px-3.5 py-2 rounded-xl">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Add First Ingredient</span>
                </button>
            </div>

            {{-- Summary Footer --}}
            <div x-show="ingredients.length > 0"
                 class="px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300 text-xs sm:text-sm">
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="boxes" class="w-4 h-4 text-gray-400"></i>
                        <strong x-text="ingredients.length"></strong> ingredient(s) configured
                    </span>
                </div>

                <div class="text-right">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Estimated Raw Material Cost / 1 Unit</div>
                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100">
                        RWF <span x-text="estimatedCost()"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 mt-4">
            <a href="{{ route('products.show', $product) }}" class="btn btn-outline text-sm px-4 py-2 rounded-xl">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary text-sm flex items-center gap-1.5 px-5 py-2 rounded-xl shadow-sm">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Save Recipe</span>
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

    function recipeEditor(existing, rawMaterials, unitFamilies) {
        // Standard metric conversion factors normalized to base:
        // Weight: kg is 1.0 (g = 0.001, mg = 0.000001)
        // Volume: l is 1.0 (ml = 0.001, cl = 0.01)
        const weightFactors = { kg: 1.0, g: 0.001, mg: 0.000001 };
        const volumeFactors = { l: 1.0, ml: 0.001, cl: 0.01 };

        return {
            ingredients: existing.length > 0 ? existing : [],
            availableMaterials: rawMaterials,

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
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            removeIngredient(index) {
                this.ingredients.splice(index, 1);
            },

            onMaterialChange(ing) {
                const rm = this.availableMaterials.find(r => r.id == ing.raw_material_id);
                if (rm) {
                    const baseUnit = rm.unit || 'pcs';
                    // Default to appropriate unit
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
                const rm = this.availableMaterials.find(r => r.id == ing.raw_material_id);
                const baseUnit = rm ? (rm.unit || 'pcs') : 'pcs';
                const factor = ing.conversion_factor || this.computeFactor(ing.unit, baseUnit);
                const baseQty = (ing.quantity || 0) * factor;

                if (!ing.raw_material_id) return '—';
                return baseQty < 0.01 ? baseQty.toFixed(4) + ' ' + baseUnit : baseQty.toFixed(2) + ' ' + baseUnit;
            },

            estimatedCost() {
                let total = 0;
                this.ingredients.forEach(ing => {
                    const rm = this.availableMaterials.find(r => r.id == ing.raw_material_id);
                    if (rm && ing.quantity > 0) {
                        const baseUnit = rm.unit || 'pcs';
                        const factor = ing.conversion_factor || this.computeFactor(ing.unit, baseUnit);
                        const baseQty = ing.quantity * factor;
                        total += (rm.price || 0) * baseQty;
                    }
                });
                return total.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        };
    }
</script>
@endpush
@endsection
