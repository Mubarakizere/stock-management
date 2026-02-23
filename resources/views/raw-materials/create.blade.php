@extends('layouts.app')
@section('title', 'Add Raw Material')

@section('content')
@php
    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'raw_material', ['raw_material','both']))
        ->values();
@endphp

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-2">
            <i data-lucide="flask-conical" class="w-6 h-6 text-teal-600 dark:text-teal-400"></i>
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Add Raw Material
            </h1>
        </div>
        <a href="{{ route('raw-materials.index') }}"
           class="btn btn-outline flex items-center gap-1 text-xs sm:text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back</span>
        </a>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Only active categories with kind <span class="font-medium">Raw Material</span> are selectable.
        @can('categories.create')
            <a href="{{ route('categories.create', ['kind' => 'raw_material']) }}"
               class="text-teal-600 dark:text-teal-400 hover:underline">
                Create category
            </a>
        @endcan
    </p>

    @cannot('products.create')
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don't have permission to add raw materials.
                    </h2>
                </div>
            </div>
        </div>
    @else

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

        @if($usableCategories->isEmpty())
            <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4">
                <div class="flex items-start gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5 mt-0.5 text-amber-600 dark:text-amber-300"></i>
                    <div class="text-sm text-amber-800 dark:text-amber-200">
                        <p class="font-semibold">No raw material categories found.</p>
                        <p class="mt-1">Create an active category with kind <em>Raw Material</em> first.</p>
                        @can('categories.create')
                            <a href="{{ route('categories.create', ['kind'=>'raw_material']) }}"
                               class="btn btn-primary btn-sm mt-3 inline-flex items-center gap-1">
                                <i data-lucide="folder-plus" class="w-4 h-4"></i>
                                Create Category
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <form action="{{ route('raw-materials.store') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1" for="name">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required
                        class="form-input w-full" placeholder="e.g. Wheat Flour 50kg">
                    @error('name')
                        <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1" for="category_id">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select id="category_id" name="category_id" class="form-select w-full text-sm" required>
                        <option value="">Select category</option>
                        @foreach ($usableCategories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Price --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1" for="price">
                        Unit Price (RWF) <span class="text-red-500">*</span>
                    </label>
                    <input id="price" type="number" step="0.01" min="0" name="price"
                        value="{{ old('price') }}" required class="form-input w-full" placeholder="e.g. 5000">
                    @error('price')
                        <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Initial Stock --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1" for="stock">
                        Initial Stock
                    </label>
                    <input id="stock" type="number" min="0" step="1" name="stock"
                        value="{{ old('stock', 0) }}" class="form-input w-full">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        If greater than 0, an initial <span class="font-medium">IN</span> movement will be recorded.
                    </p>
                    @error('stock')
                        <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('raw-materials.index') }}" class="btn btn-outline text-sm">Cancel</a>
                    <button type="submit" class="btn btn-success text-sm">Save Raw Material</button>
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
