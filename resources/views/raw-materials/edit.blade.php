@extends('layouts.app')
@section('title', "Edit Raw Material: {$product->name}")

@section('content')
@php
    use App\Models\Category;

    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? '', ['raw_material', 'both']))
        ->values();

    $currentCat = $product->category ?? null;
    $currentUsable = $currentCat
        ? $usableCategories->contains(fn($c) => (int)$c->id === (int)$product->category_id)
        : false;

    $currentStock = $product->currentStock();
    $currentUnit = strtolower(old('unit', $product->unit ?? 'kg'));
    $unitFamilies = $unitFamilies ?? \App\Services\UnitService::getGrouped();

    $recipeCount = $product->usedInRecipes()->count();
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        showDeleteModal: false,
        price: {{ old('price', $product->cost_price ?: $product->price ?: 0) }},
        stock: {{ old('stock', $currentStock) }},
        originalStock: {{ $currentStock }},
        unit: '{{ $currentUnit }}',

        get stockDiff() {
            return (parseFloat(this.stock) || 0) - this.originalStock;
        },

        formatMoney(num) {
            return (parseFloat(num) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
     }">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('raw-materials.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Raw Materials</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white font-semibold">Edit Material</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
                Edit: {{ $product->name }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Update raw material specifications, intake cost, and warehouse inventory balance.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('raw-materials.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Catalog
            </a>

            @can('products.view')
                <a href="{{ route('raw-materials.show', $product) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View Dossier
                </a>
            @endcan
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

    {{-- Category Alert if Inactive/Deleted --}}
    @if(!$currentUsable && $currentCat)
        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <div class="font-bold">Category Needs Reassignment</div>
                <p>The current category is inactive or archived. Please select an active Raw Material category below.</p>
            </div>
        </div>
    @endif

    <form action="{{ route('raw-materials.update', $product) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Card 1: Material Identification --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-5">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                <span class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                    1
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Material Identification</h2>
                    <p class="text-xs text-slate-400">Name, category assignment, and measurement unit</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Material Name --}}
                <div class="sm:col-span-2">
                    <label for="name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Material Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $product->name) }}"
                           required
                           class="w-full py-2.5 px-3.5 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                </div>

                {{-- Category --}}
                <div>
                    <label for="category_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Category <span class="text-rose-500">*</span>
                    </label>
                    <select id="category_id"
                            name="category_id"
                            required
                            class="w-full py-2.5 px-3 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        @if(!$currentUsable)
                            <option value="" disabled selected>Current: {{ $currentCat->name ?? 'None' }} (Needs Reassignment)</option>
                        @endif
                        @foreach ($usableCategories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)old('category_id', $product->category_id) === (string)$cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Base Unit --}}
                <div>
                    <label for="unit" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Base Measurement Unit <span class="text-rose-500">*</span>
                    </label>
                    <select id="unit"
                            name="unit"
                            x-model="unit"
                            required
                            class="w-full py-2.5 px-3 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        @foreach($unitFamilies as $familyKey => $family)
                            <optgroup label="{{ $family['label'] }}">
                                @foreach($family['units'] as $uKey => $uData)
                                    <option value="{{ $uKey }}" {{ $currentUnit === $uKey ? 'selected' : '' }}>
                                        {{ $uData['label'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Card 2: Cost Price per Unit --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-5">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                <span class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                    2
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Pricing & Cost Strategy</h2>
                    <p class="text-xs text-slate-400">Baseline unit cost used for intake purchases and recipe calculations</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Cost Price / Unit --}}
                <div>
                    <label for="price" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Unit Cost Price <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               step="0.01"
                               min="0"
                               id="price"
                               name="price"
                               x-model.number="price"
                               required
                               class="w-full py-2.5 pl-3.5 pr-14 text-xs font-mono rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-medium text-slate-400 pointer-events-none">
                            RWF
                        </span>
                    </div>
                    @if($product->last_purchase_price)
                        <p class="mt-1 text-[11px] text-teal-600 dark:text-teal-400 font-mono">
                            Last purchase inflow: RWF {{ number_format($product->last_purchase_price, 2) }}
                        </p>
                    @endif
                </div>

                {{-- Live Cost Preview Pill --}}
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex flex-col justify-center">
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Current Unit Cost:</span>
                    <div class="text-lg font-bold font-mono text-slate-900 dark:text-white mt-0.5">
                        <span x-text="formatMoney(price)"></span> <span class="text-xs font-sans font-normal text-slate-400">RWF / <span x-text="unit"></span></span>
                    </div>
                </div>
            </div>

            {{-- Downstream Recipe Notice --}}
            @if($recipeCount > 0)
                <div class="p-3.5 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 flex items-start gap-3 text-xs text-indigo-900 dark:text-indigo-200">
                    <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <span class="font-bold">Downstream BOM Synchronization:</span>
                        This raw material is currently consumed by <span class="font-bold underline">{{ $recipeCount }} finished product recipe(s)</span>. Updating this cost price will automatically re-estimate downstream manufacturing costs and suggested selling prices.
                    </div>
                </div>
            @endif
        </div>

        {{-- Card 3: Warehouse Physical Inventory --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-5">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                <span class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                    3
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Physical Warehouse Inventory</h2>
                    <p class="text-xs text-slate-400">Recorded warehouse inventory balance with automatic movement discrepancy logging</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Physical Stock Input --}}
                <div>
                    <label for="stock" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Physical Stock Level <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               step="0.01"
                               min="0"
                               id="stock"
                               name="stock"
                               x-model.number="stock"
                               required
                               class="w-full py-2.5 pl-3.5 pr-14 text-xs font-mono rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-medium text-slate-400 pointer-events-none" x-text="unit"></span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Current recorded balance: <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">{{ number_format($currentStock, 2) }} {{ $currentUnit }}</span>.
                    </p>
                </div>

                {{-- Live Stock Variance Indicator --}}
                <div class="p-3.5 rounded-xl border flex flex-col justify-center"
                     :class="stockDiff > 0 ? 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-100 dark:border-emerald-900/40' : (stockDiff < 0 ? 'bg-rose-50/60 dark:bg-rose-950/30 border-rose-100 dark:border-rose-900/40' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-800')">
                    <span class="text-[11px] font-medium"
                          :class="stockDiff > 0 ? 'text-emerald-700 dark:text-emerald-300' : (stockDiff < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-500')">
                        Inventory Variance:
                    </span>
                    <div class="text-lg font-bold font-mono mt-0.5"
                         :class="stockDiff > 0 ? 'text-emerald-900 dark:text-emerald-200' : (stockDiff < 0 ? 'text-rose-900 dark:text-rose-200' : 'text-slate-900 dark:text-white')">
                        <span x-text="stockDiff > 0 ? `+${stockDiff.toFixed(2)}` : (stockDiff < 0 ? `${stockDiff.toFixed(2)}` : '0.00')"></span>
                        <span class="text-xs font-sans font-normal text-slate-400" x-text="unit"></span>
                    </div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5"
                          x-text="stockDiff > 0 ? 'Will log an automatic intake adjustment movement' : (stockDiff < 0 ? 'Will log an automatic deduction adjustment movement' : 'Stock balance unchanged')">
                    </span>
                </div>
            </div>
        </div>

        {{-- Form Actions Bar --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/80 dark:border-slate-800">
            <a href="{{ route('raw-materials.index') }}"
               class="px-4 py-2.5 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 transition">
                Cancel
            </a>

            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Update Raw Material
            </button>
        </div>
    </form>

    {{-- Danger Zone: Dedicated Delete Section --}}
    @can('products.delete')
        <div class="bg-rose-50/40 dark:bg-rose-950/20 border border-rose-200/70 dark:border-rose-900/50 rounded-2xl p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-rose-900 dark:text-rose-200 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Danger Zone: Remove Raw Material
                    </h3>
                    <p class="text-xs text-rose-700/80 dark:text-rose-300/80 leading-relaxed max-w-xl">
                        Deleting this raw material removes it from your catalog and detaches it from all finished product recipes. This action cannot be reversed.
                    </p>
                </div>

                <button type="button"
                        @click="showDeleteModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-rose-700 bg-white border border-rose-300 hover:bg-rose-50 dark:bg-slate-900 dark:text-rose-400 dark:border-rose-800 dark:hover:bg-rose-950/50 transition shadow-sm self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Raw Material
                </button>
            </div>
        </div>

        {{-- Delete Confirmation Modal --}}
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
                            Are you certain you want to delete <span class="font-semibold text-slate-900 dark:text-white">{{ $product->name }}</span>?
                        </p>
                    </div>
                </div>

                @if($recipeCount > 0)
                    <div class="rounded-xl border border-amber-200/80 dark:border-amber-800/80 bg-amber-50/70 dark:bg-amber-950/30 p-3 text-xs text-amber-800 dark:text-amber-300">
                        <div class="font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Active BOM Recipe Warning</span>
                        </div>
                        <p class="mt-1">
                            This ingredient is currently consumed by <span class="font-bold">{{ $recipeCount }} product recipe(s)</span>. Deleting it will detach it from all manufacturing bills of materials.
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
