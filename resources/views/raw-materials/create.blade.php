@extends('layouts.app')
@section('title', 'Add Raw Material')

@section('content')
@php
    use App\Models\Category;

    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? '', ['raw_material', 'both']))
        ->values();

    $unitFamilies = $unitFamilies ?? \App\Services\UnitService::getGrouped();
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        price: {{ old('price') ? (float)old('price') : 0 }},
        stock: {{ old('stock') ? (float)old('stock') : 0 }},
        unit: '{{ old('unit', 'kg') }}',

        get initialValue() {
            return (parseFloat(this.price) || 0) * (parseFloat(this.stock) || 0);
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
                <span class="text-slate-900 dark:text-white font-semibold">New Raw Material</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
                Add Raw Material
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Register a new production ingredient, packaging material, or consumable component.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('raw-materials.index') }}"
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

    @if($usableCategories->isEmpty())
        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <div class="font-bold">No active Raw Material categories found</div>
                <p>Create an active category assigned to Raw Materials before cataloging new items.</p>
                @can('categories.create')
                    <div class="pt-1">
                        <a href="{{ route('categories.create', ['kind' => 'raw_material']) }}" class="inline-flex items-center gap-1 font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                            + Create Raw Material Category →
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    @endif

    <form action="{{ route('raw-materials.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Card 1: Material Identification --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-5">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                <span class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                    1
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Material Identification</h2>
                    <p class="text-xs text-slate-400">Basic naming, category attribution, and measurement unit</p>
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
                           value="{{ old('name') }}"
                           required
                           autofocus
                           placeholder="e.g. Premium Wheat Flour 50kg, Refined Sugar, Glass Bottles 500ml"
                           class="w-full py-2.5 px-3.5 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                </div>

                {{-- Category --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="category_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Category <span class="text-rose-500">*</span>
                        </label>
                        @can('categories.create')
                            <a href="{{ route('categories.create', ['kind' => 'raw_material']) }}"
                               class="text-[11px] font-medium text-teal-600 dark:text-teal-400 hover:underline">
                                + New Category
                            </a>
                        @endcan
                    </div>
                    <select id="category_id"
                            name="category_id"
                            required
                            class="w-full py-2.5 px-3 text-xs rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Select raw material category</option>
                        @foreach ($usableCategories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)old('category_id') === (string)$cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Unit of Measure --}}
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
                                    <option value="{{ $uKey }}" {{ old('unit', 'kg') === $uKey ? 'selected' : '' }}>
                                        {{ $uData['label'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Card 2: Cost & Valuation Strategy --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-5">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                <span class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                    2
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Pricing & Valuation</h2>
                    <p class="text-xs text-slate-400">Baseline unit cost for intake purchases and BOM consumption</p>
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
                               placeholder="0.00"
                               class="w-full py-2.5 pl-3.5 pr-14 text-xs font-mono rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-medium text-slate-400 pointer-events-none">
                            RWF
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Initial baseline intake cost per <span x-text="unit" class="font-semibold text-slate-600 dark:text-slate-300"></span>.
                    </p>
                </div>

                {{-- Live Cost Preview Pill --}}
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex flex-col justify-center">
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Formatted Unit Cost:</span>
                    <div class="text-lg font-bold font-mono text-slate-900 dark:text-white mt-0.5">
                        <span x-text="formatMoney(price)"></span> <span class="text-xs font-sans font-normal text-slate-400">RWF / <span x-text="unit"></span></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Initial Warehouse Inventory Allocation --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-5">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                <span class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                    3
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Initial Warehouse Inventory</h2>
                    <p class="text-xs text-slate-400">Optional opening balance automatically logged into the stock movements audit trail</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Opening Stock --}}
                <div>
                    <label for="stock" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Opening Physical Stock <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               step="0.01"
                               min="0"
                               id="stock"
                               name="stock"
                               x-model.number="stock"
                               required
                               placeholder="0.00"
                               class="w-full py-2.5 pl-3.5 pr-14 text-xs font-mono rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-medium text-slate-400 pointer-events-none" x-text="unit"></span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Enter 0 if this material has no initial warehouse balance.
                    </p>
                </div>

                {{-- Opening Asset Valuation Card --}}
                <div class="p-3.5 rounded-xl bg-teal-50/60 dark:bg-teal-950/30 border border-teal-100 dark:border-teal-900/40 flex flex-col justify-center">
                    <span class="text-[11px] font-medium text-teal-700 dark:text-teal-300">Initial Asset Valuation:</span>
                    <div class="text-lg font-bold font-mono text-teal-900 dark:text-teal-200 mt-0.5">
                        <span x-text="formatMoney(initialValue)"></span> <span class="text-xs font-sans font-normal text-teal-600 dark:text-teal-400">RWF</span>
                    </div>
                    <span class="text-[10px] text-teal-600 dark:text-teal-400 mt-0.5">
                        Valued as opening inventory intake
                    </span>
                </div>
            </div>
        </div>

        {{-- Form Actions Bar --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/80 dark:border-slate-800">
            <a href="{{ route('raw-materials.index') }}"
               class="px-4 py-2.5 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 transition">
                Discard Changes
            </a>

            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save & Create Material
            </button>
        </div>
    </form>
</div>
@endsection
