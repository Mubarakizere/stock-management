@extends('layouts.app')
@section('title', 'Add New Category')

@section('content')
@php
    $parents = $parents ?? \App\Models\Category::orderBy('name')->get(['id','name','kind']);
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <a href="{{ route('categories.index') }}" class="hover:underline">Categories</a>
                <span>/</span>
                <span>Creation</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <span>Create New Category</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Register a new inventory, raw material, or financial expense classification tier.
            </p>
        </div>

        <a href="{{ route('categories.index') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Categories</span>
        </a>
    </div>

    {{-- Error Alerts --}}
    @if(isset($errors) && $errors->any())
        <div class="rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/80 p-4 text-sm text-rose-800 dark:text-rose-300 flex items-start gap-3 shadow-xs">
            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="space-y-1">
                <p class="font-bold">Please correct the following errors:</p>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('categories.store') }}"
          x-data="{
              name: '{{ old('name', '') }}',
              kind: '{{ old('kind', 'product') }}',
              color: '{{ old('color', '#6366f1') }}',
              code: '{{ old('code', '') }}'
          }"
          class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 sm:p-8 space-y-8">
        @csrf

        {{-- Basic Information --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            {{-- Name --}}
            <div class="md:col-span-8">
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Category Name <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <input type="text" name="name" id="name" x-model="name" value="{{ old('name') }}" required
                           placeholder="e.g. Beverages, Packaging Materials, Office Expenses..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>

            {{-- Short Code --}}
            <div class="md:col-span-4">
                <label for="code" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Code / Short Code (Optional)
                </label>
                <input type="text" name="code" id="code" x-model="code" value="{{ old('code') }}"
                       placeholder="e.g. BEV, PKG, UTIL"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition uppercase">
            </div>

            {{-- Classification Kind Radio Cards --}}
            <div class="md:col-span-12">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-3">
                    Classification Kind <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    {{-- Product --}}
                    <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition select-none"
                           :class="kind === 'product' ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/40 dark:bg-blue-950/20' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300'">
                        <input type="radio" name="kind" value="product" x-model="kind" class="sr-only" required>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span>Finished Product</span>
                            </span>
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="kind === 'product' ? 'border-blue-600 bg-blue-600' : 'border-slate-300 dark:border-slate-600'">
                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="kind === 'product'"></div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Inventory merchandise for customer sales and order fulfillment.</p>
                    </label>

                    {{-- Raw Material --}}
                    <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition select-none"
                           :class="kind === 'raw_material' ? 'border-purple-500 ring-2 ring-purple-500/20 bg-purple-50/40 dark:bg-purple-950/20' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300'">
                        <input type="radio" name="kind" value="raw_material" x-model="kind" class="sr-only" required>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                                <span>Raw Material</span>
                            </span>
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="kind === 'raw_material' ? 'border-purple-600 bg-purple-600' : 'border-slate-300 dark:border-slate-600'">
                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="kind === 'raw_material'"></div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Production ingredients, factory inputs, and recipe components.</p>
                    </label>

                    {{-- Expense --}}
                    <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition select-none"
                           :class="kind === 'expense' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/40 dark:bg-amber-950/20' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300'">
                        <input type="radio" name="kind" value="expense" x-model="kind" class="sr-only" required>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>Expense Item</span>
                            </span>
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="kind === 'expense' ? 'border-amber-600 bg-amber-600' : 'border-slate-300 dark:border-slate-600'">
                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="kind === 'expense'"></div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Overhead operations, utility payments, maintenance, and logistics.</p>
                    </label>

                    {{-- Both --}}
                    <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition select-none"
                           :class="kind === 'both' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-50/40 dark:bg-indigo-950/20' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300'">
                        <input type="radio" name="kind" value="both" x-model="kind" class="sr-only" required>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                <span>Universal (Both)</span>
                            </span>
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="kind === 'both' ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300 dark:border-slate-600'">
                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="kind === 'both'"></div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Universal classification usable across product items and ledger expenses.</p>
                    </label>
                </div>
            </div>

            {{-- Parent Category --}}
            <div class="md:col-span-8">
                <label for="parent_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Parent Hierarchy Group (Optional)
                </label>
                <select name="parent_id" id="parent_id"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="">-- No Parent (Top Level Category) --</option>
                    @foreach($parents as $p)
                        <option value="{{ $p->id }}" @selected(old('parent_id') == $p->id)>
                            {{ $p->name }} ({{ ucfirst($p->kind) }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Sort Order --}}
            <div class="md:col-span-4">
                <label for="sort_order" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Sort Order
                </label>
                <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>

            {{-- Description --}}
            <div class="md:col-span-12">
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Description &amp; Operational Notes
                </label>
                <textarea name="description" id="description" rows="3"
                          placeholder="Optional notes regarding items under this classification..."
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">{{ old('description') }}</textarea>
            </div>

            {{-- Color Picker & Visual Tag Preview --}}
            <div class="md:col-span-8">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Taxonomy Color Theme
                </label>
                <div class="flex items-center gap-3">
                    <input type="color" x-model="color"
                           class="h-10 w-12 rounded-xl border border-slate-300 dark:border-slate-700 cursor-pointer bg-white p-1">
                    <input type="text" name="color" x-model="color" value="{{ old('color', '#6366f1') }}"
                           placeholder="#6366f1"
                           class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition font-mono">
                </div>
            </div>

            {{-- Live Badge Preview --}}
            <div class="md:col-span-4 flex flex-col justify-end">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                    Live UI Badge Preview
                </label>
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full shrink-0" :style="`background-color: ${color}`"></span>
                    <span class="font-bold text-xs truncate text-slate-800 dark:text-slate-200" x-text="name || 'Category Name'"></span>
                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400"
                          x-text="code || 'CODE'"></span>
                </div>
            </div>

            {{-- Active Status --}}
            <div class="md:col-span-12 pt-2">
                <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                    <span class="text-sm font-bold text-slate-800 dark:text-slate-200">Active Status</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">(Available for selection when creating products, materials or expenses)</span>
                </label>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('categories.index') }}"
               class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 transition">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-600/20 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Save Category</span>
            </button>
        </div>
    </form>

</div>
@endsection
