@extends('layouts.app')
@section('title', 'Add Finished Product')

@section('content')
@php
    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'product', ['product','both']));
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('products.index') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Products</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">New Finished Product</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Create Finished Product</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Register a new finished goods catalog item, define cost structure, and allocate initial warehouse stock.
            </p>
        </div>

        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Cancel
        </a>
    </div>

    {{-- Validation Errors --}}
    @if (($errors ?? null)?->any())
        <div class="rounded-2xl border border-rose-200 dark:border-rose-900 bg-rose-50/80 dark:bg-rose-950/30 p-4 text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-sm">
            <div class="font-semibold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Please correct the following input errors:
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @cannot('products.create')
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Permission Required</p>
            <p class="text-xs text-slate-400 mt-1">You do not have administrative clearance to add new products.</p>
        </div>
    @else

        {{-- Form Container --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden"
             x-data="{
                price: Number('{{ old('price', 0) }}'),
                cost: Number('{{ old('cost_price', 0) }}'),
                margin: Number('{{ old('target_margin', 25) }}'),

                onCostOrMarginChange() {
                    if (this.cost > 0 && this.margin >= 0) {
                        this.price = Number((this.cost * (1 + (this.margin / 100))).toFixed(2));
                    }
                },

                calcMargin() {
                    if (this.price > 0 && this.cost > 0) {
                        return (((this.price - this.cost) / this.price) * 100).toFixed(1);
                    }
                    return 0;
                },

                calcProfit() {
                    if (this.price > 0 && this.cost > 0) {
                        return (this.price - this.cost).toFixed(2);
                    }
                    return 0;
                }
             }">

            <form action="{{ route('products.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                {{-- Section 1: Product Identification --}}
                <div class="space-y-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-2">
                        1. Product Identification
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Product Full Title <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                placeholder="e.g. Absolut Vodka 750ml, Organic Ground Coffee 500g..."
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-teal-500 focus:ring-teal-500"
                            >
                            @error('name')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Category --}}
                        <div>
                            <label for="category_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Category Group <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="category_id"
                                name="category_id"
                                required
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="">Select a Category...</option>
                                @foreach($usableCategories as $cat)
                                    <option value="{{ $cat->id }}" @selected((string)old('category_id') === (string)$cat->id)>
                                        {{ $cat->name }} {{ $cat->code ? "({$cat->code})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Unit of Measure --}}
                        <div>
                            <label for="unit" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Packaging / Unit of Measure <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="unit"
                                name="unit"
                                required
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                                @php $oldUnit = old('unit', 'pcs'); @endphp
                                @foreach($unitFamilies as $familyKey => $family)
                                    <optgroup label="{{ $family['label'] }}">
                                        @foreach($family['units'] as $uKey => $uData)
                                            <option value="{{ $uKey }}" @selected($oldUnit === $uKey)>
                                                {{ $uData['label'] }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('unit')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Section 2: Financials & Pricing Strategy --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            2. Cost, Target Margin & Retail Price
                        </h2>
                        <div class="text-xs flex items-center gap-2" x-show="price > 0 && cost > 0">
                            <span class="text-slate-500">Effective Profit:</span>
                            <span class="font-mono font-bold text-teal-600 dark:text-teal-400" x-text="calcProfit() + ' RWF'"></span>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <span class="font-mono font-bold"
                                  :class="calcMargin() >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                  x-text="calcMargin() + '% Margin'"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- Cost Price --}}
                        <div>
                            <label for="cost_price" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Cost Price (RWF)
                            </label>
                            <input
                                id="cost_price"
                                type="number"
                                step="0.01"
                                min="0"
                                name="cost_price"
                                x-model.number="cost"
                                @input="onCostOrMarginChange()"
                                placeholder="0.00"
                                class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">Production or procurement unit cost</p>
                            @error('cost_price')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Target Margin --}}
                        <div>
                            <label for="target_margin" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Desired Target Margin (%)
                            </label>
                            <input
                                id="target_margin"
                                type="number"
                                step="0.1"
                                min="0"
                                max="1000"
                                name="target_margin"
                                x-model.number="margin"
                                @input="onCostOrMarginChange()"
                                placeholder="25"
                                class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">Auto-calculates retail selling price</p>
                            @error('target_margin')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Selling Price --}}
                        <div>
                            <label for="price" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Retail Selling Price (RWF) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="price"
                                type="number"
                                step="0.01"
                                min="0"
                                name="price"
                                x-model.number="price"
                                required
                                placeholder="0.00"
                                class="w-full text-xs font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">Final standard price charged to customers</p>
                            @error('price')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Section 3: Initial Stock --}}
                <div class="space-y-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-2">
                        3. Initial Warehouse Inventory
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="stock" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Initial Opening Stock (Units) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="stock"
                                type="number"
                                step="0.01"
                                min="0"
                                name="stock"
                                value="{{ old('stock', 0) }}"
                                required
                                placeholder="0"
                                class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">Physical units counted on premises today</p>
                            @error('stock')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 space-y-1">
                                <div class="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Automatic Inventory Inflow
                                </div>
                                <p>Entering initial stock creates an automatic <strong>IN Stock Movement</strong> attributed to your user account for complete audit tracking.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Footer --}}
                <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('products.index') }}" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        Cancel
                    </a>

                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Finished Product
                    </button>
                </div>
            </form>
        </div>

    @endcannot
</div>
@endsection
