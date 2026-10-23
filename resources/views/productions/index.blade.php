@extends('layouts.app')
@section('title', 'Production Runs')

@section('content')
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide uppercase bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/60">
                    Manufacturing
                </span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                Production Runs
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Monitor finished goods manufacturing, raw material deduction, and batch history.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('reports.manufacturing') }}"
               class="btn btn-outline text-xs sm:text-sm flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition">
                <i data-lucide="bar-chart-2" class="w-4 h-4 text-gray-500"></i>
                <span>Analytics Report</span>
            </a>

            @can('products.edit')
                <a href="{{ route('productions.create') }}"
                   class="btn btn-primary flex items-center gap-1.5 text-xs sm:text-sm px-4 py-2 rounded-xl shadow-sm hover:shadow transition">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New Production Run</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Total Runs --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl p-5 shadow-sm hover:border-gray-300 dark:hover:border-gray-600 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Runs</span>
                <span class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-950/60 border border-violet-100 dark:border-violet-900/60 flex items-center justify-center text-violet-600 dark:text-violet-400">
                    <i data-lucide="factory" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 font-mono">
                    {{ $fmt0($stats['total_runs'] ?? 0) }}
                </span>
                <span class="text-xs text-gray-400 ml-1">batches logged</span>
            </div>
        </div>

        {{-- Total Units Output --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl p-5 shadow-sm hover:border-gray-300 dark:hover:border-gray-600 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Produced</span>
                <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-950/60 border border-teal-100 dark:border-teal-900/60 flex items-center justify-center text-teal-600 dark:text-teal-400">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 font-mono">
                    {{ $fmt0($stats['total_produced'] ?? 0) }}
                </span>
                <span class="text-xs text-gray-400 ml-1">finished units</span>
            </div>
        </div>

        {{-- Active Recipes --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl p-5 shadow-sm hover:border-gray-300 dark:hover:border-gray-600 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Products with Recipes</span>
                <span class="w-9 h-9 rounded-xl bg-orange-50 dark:bg-orange-950/60 border border-orange-100 dark:border-orange-900/60 flex items-center justify-center text-orange-600 dark:text-orange-400">
                    <i data-lucide="chef-hat" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 font-mono">
                    {{ $fmt0($stats['active_recipes'] ?? 0) }}
                </span>
                <span class="text-xs text-gray-400 ml-1">producible items</span>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('productions.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex flex-1 flex-col sm:flex-row items-center gap-3">
                {{-- Search --}}
                <div class="relative w-full sm:w-72">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-gray-400 dark:text-gray-500 pointer-events-none"></i>
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Search product or batch notes…"
                           class="form-input text-xs sm:text-sm pl-9 pr-3.5 py-2 w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-gray-100">
                </div>

                {{-- Product Filter --}}
                <div class="w-full sm:w-60">
                    <select name="product_id"
                            class="form-select text-xs sm:text-sm py-2 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 w-full"
                            onchange="this.form.submit()">
                        <option value="">All Finished Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-outline text-xs px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Apply</span>
                </button>
            </div>

            @if(request()->hasAny(['q', 'product_id']))
                <a href="{{ route('productions.index') }}"
                   class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    <span>Clear filters</span>
                </a>
            @endif
        </form>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-4 text-xs sm:text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-4 text-xs sm:text-sm text-rose-700 dark:text-rose-300 flex items-center gap-2.5 shadow-sm">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Main Table Container --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/80 text-sm">
                <thead class="bg-gray-50/70 dark:bg-gray-900/40 text-gray-500 dark:text-gray-400 uppercase text-[11px] font-semibold tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Run ID</th>
                        <th class="px-5 py-3.5 text-left">Product Produced</th>
                        <th class="px-5 py-3.5 text-right">Output Qty</th>
                        <th class="px-5 py-3.5 text-center">Ingredients</th>
                        <th class="px-5 py-3.5 text-left">Production Date</th>
                        <th class="px-5 py-3.5 text-left">Operator</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($productions as $prod)
                        @php
                            $product = $prod->product;
                            $unit = $product->unit ?? 'pcs';
                        @endphp
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/25 transition">
                            {{-- Run ID --}}
                            <td class="px-5 py-3.5 text-xs font-mono text-gray-500 dark:text-gray-400">
                                <a href="{{ route('productions.show', $prod) }}" class="font-semibold text-gray-700 dark:text-gray-300 hover:text-teal-600 dark:hover:text-teal-400">
                                    #{{ str_pad($prod->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>

                            {{-- Product Name + Notes --}}
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                    <a href="{{ route('productions.show', $prod) }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">
                                        {{ $product->name ?? '—' }}
                                    </a>
                                </div>
                                @if($prod->notes)
                                    <div class="text-[11px] text-gray-400 truncate max-w-xs mt-0.5">
                                        {{ $prod->notes }}
                                    </div>
                                @endif
                            </td>

                            {{-- Output Qty --}}
                            <td class="px-5 py-3.5 text-right font-mono">
                                <span class="font-bold text-gray-900 dark:text-gray-100 text-sm">
                                    {{ $fmt2($prod->quantity) }}
                                </span>
                                <span class="text-xs text-gray-400 font-normal ml-0.5">{{ $unit }}</span>
                            </td>

                            {{-- Ingredients Count --}}
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 border border-gray-200/60 dark:border-gray-600/60">
                                    <i data-lucide="flask-conical" class="w-3 h-3 text-orange-500"></i>
                                    <span>{{ $prod->materials->count() }} items</span>
                                </span>
                            </td>

                            {{-- Date --}}
                            <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ $prod->produced_at->format('M d, Y') }}</div>
                                <div class="text-[11px] text-gray-400 font-mono">{{ $prod->produced_at->format('H:i') }}</div>
                            </td>

                            {{-- Operator --}}
                            <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-teal-50 dark:bg-teal-950/60 border border-teal-200 dark:border-teal-800 text-teal-600 dark:text-teal-400 text-[10px] font-bold flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($prod->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span class="truncate max-w-[120px]">{{ $prod->user->name ?? 'System' }}</span>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('productions.show', $prod) }}"
                                       class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-teal-600 dark:hover:text-teal-400 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition inline-flex items-center gap-1"
                                       title="View Production Run Details">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>Details</span>
                                    </a>

                                    @can('products.edit')
                                        <button type="button"
                                                @click="$store.reverseConfirm.open({{ $prod->id }}, '{{ addslashes($prod->product->name ?? '—') }}', '{{ $fmt2($prod->quantity) }} {{ $unit }}', {{ $prod->materials->count() }})"
                                                class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                title="Reverse & Rollback">
                                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                                <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center text-gray-400 mx-auto mb-3">
                                    <i data-lucide="factory" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">No production runs found</h3>
                                <p class="text-xs text-gray-400 max-w-sm mx-auto mt-1">
                                    Start manufacturing by selecting a finished product with an active recipe.
                                </p>
                                @can('products.edit')
                                    <a href="{{ route('productions.create') }}"
                                       class="mt-4 btn btn-primary btn-sm inline-flex items-center gap-1.5 text-xs px-3.5 py-2 rounded-xl">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Record First Production</span>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($productions->hasPages())
            <div class="px-5 py-3.5 border-t border-gray-100 dark:border-gray-700/80 bg-gray-50/50 dark:bg-gray-900/30">
                {{ $productions->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Reverse Production Confirmation Modal --}}
@can('products.edit')
<div
    x-data
    x-show="$store.reverseConfirm.show"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    @keydown.escape.window="$store.reverseConfirm.close()"
>
    <div
        @click.outside="$store.reverseConfirm.close()"
        x-show="$store.reverseConfirm.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
               rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4"
    >
        <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-900 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Reverse Production Run</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="$store.reverseConfirm.productName"></p>
            </div>
        </div>

        <div class="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/80 p-3.5 text-xs text-amber-800 dark:text-amber-300 space-y-1.5">
            <p class="font-semibold">Reversing will execute the following:</p>
            <ul class="space-y-1 pl-1">
                <li class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Deduct <strong x-text="$store.reverseConfirm.quantity"></strong> from finished goods stock</span>
                </li>
                <li class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Restore <strong x-text="$store.reverseConfirm.materialsCount"></strong> consumed raw material(s) back into inventory</span>
                </li>
                <li class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Permanently remove this production record</span>
                </li>
            </ul>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            This action cannot be undone. Verify your physical inventory before proceeding.
        </p>

        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-gray-100 dark:border-gray-700">
            <button type="button" class="btn btn-outline text-xs px-4 py-2 rounded-xl" @click="$store.reverseConfirm.close()">
                Cancel
            </button>
            <button type="button"
                    class="btn btn-danger text-xs flex items-center gap-1.5 px-4 py-2 rounded-xl"
                    @click="$store.reverseConfirm.submit()">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                <span>Yes, Reverse Run</span>
            </button>
        </div>
    </div>
</div>

{{-- Hidden form for reverse --}}
<form id="reverse-production-form" method="POST" class="hidden">
    @csrf @method('DELETE')
</form>
@endcan

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('reverseConfirm', {
            show: false,
            productionId: null,
            productName: '',
            quantity: '',
            materialsCount: 0,

            open(id, name, qty, materials) {
                this.productionId = id;
                this.productName = name;
                this.quantity = qty;
                this.materialsCount = materials;
                this.show = true;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            close() {
                this.show = false;
            },

            submit() {
                const form = document.getElementById('reverse-production-form');
                form.action = '/productions/' + this.productionId;
                form.submit();
            },
        });
    });
</script>
@endpush
@endsection
