@extends('layouts.app')
@section('title', 'Categories Management')

@section('content')
@php
    $kinds = [
        'all'          => 'All Categories',
        'product'      => 'Finished Products',
        'raw_material' => 'Raw Materials',
        'expense'      => 'Operating Expenses',
        'both'         => 'Universal (Both)',
        'inactive'     => 'Inactive',
        'trash'        => 'Trash Archive',
    ];

    $current     = $filterKind ?? request('kind');
    $isTrashView = ($current === 'trash');
    $q           = trim((string) request('q', ''));
    $showAll     = request('per_page') === 'all';
    $toggleParams = array_merge(request()->except('page','per_page'), ['per_page' => $showAll ? null : 'all']);
    $toggleHref   = route('categories.index', array_filter($toggleParams, fn($v) => $v !== null));
@endphp

<div x-data="{
        confirmOpen: false,
        confirmMsg: '',
        targetForm: null,
        openConfirmModal(form, message) {
            this.targetForm = form;
            this.confirmMsg = message || 'Are you sure you want to proceed?';
            this.confirmOpen = true;
        },
        executeConfirm() {
            if (this.targetForm) {
                this.targetForm.submit();
            }
            this.confirmOpen = false;
        }
     }"
     @keydown.escape.window="confirmOpen = false"
     class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- ========================================================================= --}}
    {{-- 1. HEADER & TOP ACTIONS                                                   --}}
    {{-- ========================================================================= --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <span>Inventory &amp; Operations</span>
                <span>/</span>
                <span>Master Data</span>
                <span>/</span>
                <span>Categories</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
                <span>Categories &amp; Taxonomies</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Organize finished inventory, manufacturing raw materials, and ledger expense items into hierarchical groups.
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('products.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span>Products Catalog</span>
            </a>

            <a href="{{ route('raw-materials.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                <span>Raw Materials</span>
            </a>

            @can('categories.create')
                <a href="{{ route('categories.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span>Add Category</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. METRICS & STATS DECK                                                   --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Active Categories --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Active Groups</span>
                <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $stats['total'] ?? 0 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">Master Taxonomies</span>
                    <span>in live use</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500 to-blue-600"></div>
        </div>

        {{-- Product Categories --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Finished Products</span>
                <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-blue-600 dark:text-blue-400 tracking-tight">
                    {{ $stats['product'] ?? 0 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-blue-600 dark:text-blue-400">Catalog Lines</span>
                    <span>retail &amp; wholesale goods</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-600"></div>
        </div>

        {{-- Raw Material Categories --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Raw Material Tiers</span>
                <div class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-purple-600 dark:text-purple-400 tracking-tight">
                    {{ $stats['raw_material'] ?? 0 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-purple-600 dark:text-purple-400">Production Stock</span>
                    <span>ingredients &amp; inputs</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-purple-500 to-pink-600"></div>
        </div>

        {{-- Operating Expenses --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Expense Classifications</span>
                <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 tracking-tight">
                    {{ $stats['expense'] ?? 0 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-amber-600 dark:text-amber-400">Overhead Accounts</span>
                    <span>utilities, rent &amp; logistics</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3. INTERACTIVE TABS & SEARCH TOOLBAR                                      --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
        {{-- Fast Classification Tabs --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800/80">
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach($kinds as $key => $label)
                    @php
                        $target = $key === 'all' ? null : $key;
                        $params = array_filter(array_merge(request()->except('page'), ['kind' => $target]));
                        $isActive = ($current === $key) || ($key === 'all' && empty($current));
                        $isTrashTab = ($key === 'trash');
                        $trashCount = $stats['trash'] ?? 0;
                    @endphp
                    <a href="{{ route('categories.index', $params) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 {{ $isActive ? ($isTrashTab ? 'bg-rose-600 text-white shadow-xs' : 'bg-indigo-600 text-white shadow-xs') : ($isTrashTab ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 hover:bg-rose-100' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700') }}">
                        <span>{{ $label }}</span>
                        @if($isTrashTab && $trashCount > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $isActive ? 'bg-white text-rose-600' : 'bg-rose-600 text-white' }}">
                                {{ $trashCount }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $toggleHref }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 transition">
                    @if($showAll)
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span>Paginate</span>
                    @else
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Show All</span>
                    @endif
                </a>
            </div>
        </div>

        {{-- Search Input Form --}}
        <form method="GET" action="{{ route('categories.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3.5 items-end">
            {{-- Preserve active filters --}}
            @if($current)
                <input type="hidden" name="kind" value="{{ $current }}">
            @endif
            @if($showAll)
                <input type="hidden" name="per_page" value="all">
            @endif

            <div class="md:col-span-10">
                <label for="q" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Search Categories
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="q" id="q" value="{{ $q }}"
                           placeholder="Search category name, code (e.g. BEER), description, or parent group..."
                           class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">

                    @if($q !== '')
                        @php
                            $clearParams = array_filter(request()->except('page','q'));
                        @endphp
                        <a href="{{ route('categories.index', $clearParams) }}"
                           class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                           title="Clear search">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-sm font-semibold flex items-center justify-center gap-1.5 shadow-md shadow-indigo-600/20 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>Filter</span>
                </button>

                @if($q !== '')
                    <a href="{{ route('categories.index', array_filter(request()->except('page','q'))) }}"
                       class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold flex items-center justify-center transition"
                       title="Reset Filters">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>

        {{-- Search & Tab Context indicator --}}
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400 pt-1">
            @if($q !== '')
                <div>Searching: <span class="font-bold text-slate-900 dark:text-white">“{{ $q }}”</span></div>
            @endif
            @if($isTrashView)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                    Trash Archive — soft-deleted categories
                </span>
            @endif
            @if($showAll)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300">
                    Showing unpaginated list
                </span>
            @endif
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 4. CATEGORIES TABLE                                                       --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Active Classification Records</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Category hierarchical taxonomy and item allocations</p>
                </div>
            </div>

            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                {{ $categories instanceof \Illuminate\Pagination\LengthAwarePaginator ? $categories->total() : count($categories) }} Categories
            </span>
        </div>

        @include('categories._table')

        @if($categories instanceof \Illuminate\Pagination\LengthAwarePaginator && $categories->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- 5. CONFIRMATION ACTION MODAL (ALPINE.JS)                                   --}}
    {{-- ========================================================================= --}}
    <div x-show="confirmOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="confirmOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="confirmOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="confirmOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-700">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white" id="modal-title">
                                Confirm Category Action
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400" x-text="confirmMsg"></p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="confirmOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>

                    <button type="button"
                            @click="executeConfirm()"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:scale-95 shadow-md shadow-rose-600/20 transition cursor-pointer">
                        Confirm Action
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
