@extends('layouts.app')
@section('title', 'Edit Product')

@section('content')
@php
    use Illuminate\Support\Str;

    // Safety: ensure we only show usable categories here
    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'product', ['product','both']))
        ->values();

    $currentCat = $product->category ?? null; // controller should eager-load withTrashed() for show/edit
    $currentUsable = $currentCat
        ? $usableCategories->contains(fn($c) => (int)$c->id === (int)$product->category_id)
        : false;

    // Text to show when the current category is not usable anymore
    $currentBadge = $currentCat
        ? ($currentCat->name . (
            (method_exists($currentCat,'trashed') && $currentCat->trashed()) ? ' (deleted)' :
            (($currentCat->is_active ?? true) ? '' : ' (inactive)')
        ))
        : 'Unknown (missing)';
@endphp

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
            <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Edit Product
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('products.index') }}"
               class="btn btn-outline flex items-center gap-1 text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back</span>
            </a>
            @can('products.view')
                <a href="{{ route('products.show', $product) }}"
                   class="btn btn-secondary flex items-center gap-1 text-sm">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span>View</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Category warning --}}
    @if(!$currentUsable && $currentCat)
        <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4">
            <div class="flex items-start gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 mt-0.5 text-amber-600 dark:text-amber-300"></i>
                <div class="text-sm text-amber-800 dark:text-amber-200">
                    <p class="font-semibold">Category needs attention</p>
                    <p class="mt-1">
                        Current category
                        <span class="font-medium">{{ $currentBadge }}</span>
                        is not usable for products. Please select an active category with kind
                        <em>Product</em> or <em>Both</em>.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg p-4">
            <p class="font-medium">Please fix the following:</p>
            <ul class="list-disc pl-5 mt-2 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @cannot('products.edit')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to edit products.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        You can still view product details, but changes are restricted. Please contact your administrator if you need edit access.
                    </p>
                </div>
            </div>
        </div>
    @else
        {{-- Form Card --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Product Name --}}
                <div>
                    <label class="form-label" for="name">
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $product->name) }}"
                        required
                        class="form-input w-full"
                        placeholder="e.g. Coca-Cola 500ml"
                    >
                    @error('name')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category (with inline filter + fallback for unusable current) --}}
                <div x-data="{ q: '' }">
                    <label class="form-label" for="category_id">
                        Category <span class="text-red-500">*</span>
                    </label>

                    {{-- Tiny client-side filter for long lists --}}
                    <div class="relative mb-2">
                        <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                        <input
                            x-model="q"
                            type="text"
                            placeholder="Filter categories…"
                            class="form-input w-full pl-8"
                            aria-label="Filter categories"
                        >
                    </div>

                    <select
                        id="category_id"
                        name="category_id"
                        class="form-select w-full"
                        required
                        x-init="$watch('q', v => {
                            const opts = $el.querySelectorAll('option[data-name]');
                            const k = (v || '').toLowerCase();
                            opts.forEach(o => {
                                const visible = o.dataset.name.includes(k);
                                o.hidden = !visible && o.value !== '';
                            });
                        })"
                    >
                        @if(!$currentUsable)
                            {{-- Force user to choose a new valid category --}}
                            <option value="" selected disabled>
                                Current: {{ $currentBadge }} — select a new category —
                            </option>
                        @else
                            <option value="">Select category</option>
                        @endif

                        @foreach ($usableCategories as $category)
                            @php
                                $label = trim($category->name.' '.($category->code ? "({$category->code})" : ''));
                            @endphp
                            <option
                                value="{{ $category->id }}"
                                data-name="{{ Str::lower($label) }}"
                                {{ (string)old('category_id', $product->category_id) === (string)$category->id ? 'selected' : '' }}
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Can’t find it?
                        <a href="{{ route('categories.create', ['kind'=>'product']) }}"
                           class="text-indigo-600 dark:text-indigo-400 hover:underline">
                            Create a category
                        </a>.
                    </p>
                </div>

                {{-- Pricing, Margin & Cost Section --}}
                <div x-data="{
                    price: Number('{{ old('price', $product->price) }}'),
                    cost: Number('{{ old('cost_price', $product->cost_price ?? 0) }}'),
                    margin: Number('{{ old('target_margin', $product->target_margin ?? 0) }}'),
                    onCostOrMarginChange() {
                        if (this.cost > 0 && this.margin > 0) {
                            this.price = Number((this.cost * (1 + (this.margin / 100))).toFixed(2));
                        }
                    },
                    calcMargin() {
                        if (this.price > 0 && this.cost > 0) {
                            return (((this.price - this.cost) / this.price) * 100).toFixed(1);
                        }
                        return 0;
                    }
                }" class="space-y-4 p-4 rounded-xl bg-gray-50/70 dark:bg-gray-900/40 border border-gray-200/80 dark:border-gray-700/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Cost & Pricing Strategy</span>
                        <div class="text-xs text-gray-500 dark:text-gray-400" x-show="price > 0 && cost > 0">
                            <span>Effective Margin: </span>
                            <span :class="price >= cost ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-rose-600 dark:text-rose-400 font-bold'" x-text="calcMargin() + '%'"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- Cost Price --}}
                        <div>
                            <label class="form-label text-xs" for="cost_price">
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
                                class="form-input w-full"
                                placeholder="e.g. 800"
                            >
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">What it costs to produce or buy</p>
                            @error('cost_price')
                                <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Target Margin % --}}
                        <div>
                            <label class="form-label text-xs" for="target_margin">
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
                                class="form-input w-full"
                                placeholder="e.g. 25"
                            >
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Auto-adjusts selling price</p>
                            @error('target_margin')
                                <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Selling Price --}}
                        <div>
                            <label class="form-label text-xs" for="price">
                                Selling Price (RWF) <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="price"
                                type="number"
                                step="0.01"
                                min="0"
                                name="price"
                                x-model.number="price"
                                required
                                class="form-input w-full font-bold text-indigo-700 dark:text-indigo-400"
                                placeholder="e.g. 1200"
                            >
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">What customers pay</p>
                            @error('price')
                                <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Unit & Stock --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Product Unit --}}
                    <div>
                        <label class="form-label" for="unit">
                            Product Unit <span class="text-red-500">*</span>
                        </label>
                        @php
                            $prodUnit = strtolower(old('unit', $product->unit ?? 'pcs'));
                        @endphp
                        <select id="unit" name="unit" class="form-select w-full text-sm" required>
                            <option value="pcs" {{ $prodUnit === 'pcs' ? 'selected' : '' }}>Piece (pcs)</option>
                            <option value="bottle" {{ $prodUnit === 'bottle' ? 'selected' : '' }}>Bottle</option>
                            <option value="can" {{ $prodUnit === 'can' ? 'selected' : '' }}>Can</option>
                            <option value="box" {{ $prodUnit === 'box' ? 'selected' : '' }}>Box</option>
                            <option value="pack" {{ $prodUnit === 'pack' ? 'selected' : '' }}>Pack</option>
                            <option value="bag" {{ $prodUnit === 'bag' ? 'selected' : '' }}>Bag</option>
                            <option value="carton" {{ $prodUnit === 'carton' ? 'selected' : '' }}>Carton</option>
                            <option value="crate" {{ $prodUnit === 'crate' ? 'selected' : '' }}>Crate</option>
                            <option value="kg" {{ $prodUnit === 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                            <option value="l" {{ $prodUnit === 'l' ? 'selected' : '' }}>Liter (L)</option>
                        </select>
                        @error('unit')
                            <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label" for="stock">
                            Stock <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="stock"
                            type="number"
                            step="any"
                            min="0"
                            name="stock"
                            value="{{ old('stock', $product->stock) }}"
                            required
                            class="form-input w-full"
                        >
                        @error('stock')
                            <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('products.index') }}" class="btn btn-outline">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-success">
                        Update Product
                    </button>
                </div>
            </form>
        </div>
    @endcannot
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });
</script>
@endpush
@endsection
