@extends('layouts.app')
@section('title', 'Add Raw Material')

@section('content')
@php
    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && ($c->kind ?? '') === 'raw_material')
        ->values();
@endphp

<div class="max-w-xl mx-auto px-4 sm:px-6 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between pb-1">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/60 border border-teal-200/80 dark:border-teal-800/80 flex items-center justify-center text-teal-600 dark:text-teal-400 shadow-sm shrink-0">
                <i data-lucide="flask-conical" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                    Add Raw Material
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Create a new raw material for inventory, purchases, and recipes.
                </p>
            </div>
        </div>

        <a href="{{ route('raw-materials.index') }}"
           class="btn btn-outline text-xs sm:text-sm flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition shadow-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back</span>
        </a>
    </div>

    @cannot('products.create')
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-5">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5 shrink-0"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        Permission Required
                    </h2>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                        You do not have permission to add raw materials.
                    </p>
                </div>
            </div>
        </div>
    @else

        @if ($errors->any())
            <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/80 text-rose-700 dark:text-rose-300 rounded-2xl p-4 text-sm shadow-sm">
                <div class="flex items-center gap-2 font-medium">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
                    <span>Please fix the following:</span>
                </div>
                <ul class="list-disc pl-5 mt-2 space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($usableCategories->isEmpty())
            <div class="rounded-2xl border border-amber-200 dark:border-amber-800/80 bg-amber-50/70 dark:bg-amber-950/30 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    </div>
                    <div class="text-sm">
                        <h3 class="font-semibold text-amber-900 dark:text-amber-200">No raw material categories found</h3>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                            Create an active category with kind <em>Raw Material</em> before creating items.
                        </p>
                        @can('categories.create')
                            <a href="{{ route('categories.create', ['kind'=>'raw_material']) }}"
                               class="inline-flex items-center gap-1.5 mt-2.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition shadow-sm">
                                <i data-lucide="folder-plus" class="w-3.5 h-3.5"></i>
                                <span>Create Category</span>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl shadow-sm p-6 sm:p-7">
            <form action="{{ route('raw-materials.store') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5" for="name">
                        Material Name <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                            <i data-lucide="tag" class="w-4 h-4"></i>
                        </div>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            class="block w-full pl-10 pr-3.5 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 dark:focus:ring-teal-400/20 dark:focus:border-teal-400 transition shadow-sm"
                            placeholder="e.g. Wheat Flour 50kg, Sugar, Cocoa Powder">
                    </div>
                    @error('name')
                        <p class="text-rose-600 dark:text-rose-400 text-xs mt-1.5 flex items-center gap-1">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                {{-- Category --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300" for="category_id">
                            Category <span class="text-rose-500">*</span>
                        </label>
                        @can('categories.create')
                            <a href="{{ route('categories.create', ['kind' => 'raw_material']) }}"
                               class="text-[11px] font-medium text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 hover:underline flex items-center gap-1">
                                <i data-lucide="plus" class="w-3 h-3"></i>
                                <span>New Category</span>
                            </a>
                        @endcan
                    </div>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                            <i data-lucide="folder" class="w-4 h-4"></i>
                        </div>
                        <select id="category_id" name="category_id" required
                            class="block w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 dark:focus:ring-teal-400/20 dark:focus:border-teal-400 transition shadow-sm">
                            <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Select category</option>
                            @foreach ($usableCategories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5">
                        Only categories configured for <span class="font-medium text-gray-700 dark:text-gray-300">Raw Material</span> are shown.
                    </p>
                    @error('category_id')
                        <p class="text-rose-600 dark:text-rose-400 text-xs mt-1.5 flex items-center gap-1">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                {{-- Price & Initial Stock --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Price --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5" for="price">
                            Unit Price (RWF) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm flex">
                            <span class="inline-flex items-center px-3.5 rounded-l-xl border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-semibold select-none">
                                RWF
                            </span>
                            <input id="price" type="number" step="0.01" min="0" name="price"
                                value="{{ old('price') }}" required
                                class="block w-full rounded-r-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 dark:focus:ring-teal-400/20 dark:focus:border-teal-400 transition py-2.5 px-3.5 text-sm shadow-sm"
                                placeholder="0.00">
                        </div>
                        @error('price')
                            <p class="text-rose-600 dark:text-rose-400 text-xs mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    {{-- Initial Stock --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5" for="stock">
                            Initial Stock
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                                <i data-lucide="boxes" class="w-4 h-4"></i>
                            </div>
                            <input id="stock" type="number" min="0" step="any" name="stock"
                                value="{{ old('stock', 0) }}"
                                class="block w-full pl-10 pr-3.5 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 dark:focus:ring-teal-400/20 dark:focus:border-teal-400 transition shadow-sm"
                                placeholder="0">
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5">
                            If &gt; 0, an initial <span class="font-medium text-emerald-600 dark:text-emerald-400">IN</span> movement is recorded.
                        </p>
                        @error('stock')
                            <p class="text-rose-600 dark:text-rose-400 text-xs mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700/80">
                    <a href="{{ route('raw-materials.index') }}"
                       class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-sm font-medium transition shadow-sm">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white text-sm font-medium shadow-sm hover:shadow transition-all">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Save Raw Material</span>
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
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>
@endpush
@endsection
