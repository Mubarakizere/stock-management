@extends('layouts.app')
@section('title', "Edit {$product->name}")

@section('content')
@php
    use Illuminate\Support\Str;

    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'product', ['product','both']))
        ->values();

    $currentCat = $product->category;
    $currentUsable = $currentCat
        ? $usableCategories->contains(fn($c) => (int)$c->id === (int)$product->category_id)
        : false;

    $currentStock = $product->currentStock();
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        showDeleteModal: false,
        price: Number('{{ old('price', $product->price) }}'),
        cost: Number('{{ old('cost_price', $product->cost_price ?? 0) }}'),
        margin: Number('{{ old('target_margin', $product->target_margin ?? 25) }}'),
        stock: Number('{{ old('stock', $currentStock) }}'),
        originalStock: Number('{{ $currentStock }}'),

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
        },

        stockDelta() {
            return this.stock - this.originalStock;
        }
     }">

    {{-- Breadcrumb & Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('products.index') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Products</a>
                <span>/</span>
                <a href="{{ route('products.show', $product) }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">{{ $product->name }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Edit</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-xs shrink-0 ring-1 ring-slate-200 dark:ring-slate-700">
                    {{ strtoupper(substr($product->name, 0, 2)) }}
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Edit {{ $product->name }}
                </h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('products.show', $product) }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                View Dossier
            </a>
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                Cancel
            </a>
        </div>
    </div>

    {{-- Category Alert if unusable --}}
    @if(!$currentUsable && $currentCat)
        <div class="rounded-2xl border border-amber-200 dark:border-amber-900 bg-amber-50/80 dark:bg-amber-950/30 p-4 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-3 shadow-sm">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="font-semibold text-sm">Assigned Category Requires Update</p>
                <p class="mt-0.5 text-amber-700 dark:text-amber-400">
                    The currently assigned category (<strong>{{ $currentCat->name }}</strong>) has been archived or inactivated. Please choose an active finished goods category below.
                </p>
            </div>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if (($errors ?? null)?->any())
        <div class="rounded-2xl border border-rose-200 dark:border-rose-900 bg-rose-50/80 dark:bg-rose-950/30 p-4 text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-sm">
            <div class="font-semibold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Please correct the following errors:
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @cannot('products.edit')
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Permission Required</p>
            <p class="text-xs text-slate-400 mt-1">You do not have permission to modify product catalog records.</p>
        </div>
    @else

        {{-- Form Container --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
            <form action="{{ route('products.update', $product) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

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
                                value="{{ old('name', $product->name) }}"
                                required
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
                                @foreach($usableCategories as $cat)
                                    <option value="{{ $cat->id }}" @selected((string)old('category_id', $product->category_id) === (string)$cat->id)>
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
                                @php $selUnit = old('unit', $product->unit ?? 'pcs'); @endphp
                                @foreach($unitFamilies as $familyKey => $family)
                                    <optgroup label="{{ $family['label'] }}">
                                        @foreach($family['units'] as $uKey => $uData)
                                            <option value="{{ $uKey }}" @selected($selUnit === $uKey)>
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
                                class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">Procurement or manufacture base cost</p>
                            @error('cost_price')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Target Margin --}}
                        <div>
                            <label for="target_margin" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Target Margin (%)
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
                                class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">Auto-adjusts selling price</p>
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
                                class="w-full text-xs font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">Active retail selling price</p>
                            @error('price')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Section 3: Warehouse Stock Reconciliation --}}
                <div class="space-y-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-2">
                        3. Stock Level Reconciliation
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="stock" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Reconciled Total Stock (Units) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="stock"
                                type="number"
                                step="0.01"
                                min="0"
                                name="stock"
                                x-model.number="stock"
                                required
                                class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">
                                Previous recorded stock: <strong class="font-mono text-slate-700 dark:text-slate-300">{{ number_format($currentStock, 2) }}</strong>
                            </p>
                            @error('stock')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <div class="p-3.5 rounded-xl border text-[11px] space-y-1 w-full"
                                 :class="stockDelta() === 0 ? 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/80 dark:border-slate-700 text-slate-500' : (stockDelta() > 0 ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300')">
                                <div class="font-semibold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span x-text="stockDelta() === 0 ? 'No Stock Variance' : (stockDelta() > 0 ? 'Positive Inventory Adjustment' : 'Negative Stock Variance')"></span>
                                </div>
                                <p x-text="stockDelta() === 0 ? 'Stock matches warehouse count. No movement entry will be generated.' : (stockDelta() > 0 ? 'A stock IN movement of +' + stockDelta().toFixed(2) + ' units will be recorded.' : 'A stock OUT movement of ' + stockDelta().toFixed(2) + ' units will be recorded.')"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Footer --}}
                <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        @can('products.delete')
                            <button type="button" @click="showDeleteModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900 dark:hover:bg-rose-900/60 shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete Product
                            </button>
                        @endcan
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('products.show', $product) }}" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                            Cancel
                        </a>

                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ============================================================ --}}
        {{-- DEDICATED DELETE MODAL ON EDIT PAGE                         --}}
        {{-- ============================================================ --}}
        @can('products.delete')
        <div x-show="showDeleteModal"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

            <div @click.away="showDeleteModal = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 ring-1 ring-rose-200 dark:ring-rose-800">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Delete Product Catalog Entry
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Are you sure you want to delete <strong class="text-slate-800 dark:text-slate-200">{{ $product->name }}</strong>?
                        </p>
                    </div>
                </div>

                <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                    <div class="font-semibold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Stock Movements Cascade Notice
                    </div>
                    <p class="text-amber-700 dark:text-amber-400 pl-5">
                        This item has <strong>{{ number_format($currentStock, 2) }} units</strong> in recorded stock. Deleting it will clear all movement records and recipe configurations.
                    </p>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showDeleteModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        Keep Product
                    </button>
                    <form action="{{ route('products.destroy', $product) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-rose-600 hover:bg-rose-500 shadow-sm transition">
                            Confirm Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endcan

    @endcannot
</div>
@endsection
