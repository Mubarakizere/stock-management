@extends('layouts.app')
@section('title', 'Production Run #' . $production->id)

@section('content')
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $product = $production->product;
    $productUnit = $product->unit ?? 'pcs';
    $totalMaterialCost = 0;
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{ showReverse: false }">

    {{-- Breadcrumb & Top Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                <a href="{{ route('productions.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">
                    Production Runs
                </a>
                <span>/</span>
                <span class="font-mono text-gray-700 dark:text-gray-300">#{{ str_pad($production->id, 5, '0', STR_PAD_LEFT) }}</span>
            </nav>

            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                    Batch Run #{{ str_pad($production->id, 5, '0', STR_PAD_LEFT) }}
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Completed</span>
                </span>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">
                Recorded on {{ $production->produced_at->format('M d, Y \a\t H:i') }} ({{ $production->produced_at->diffForHumans() }})
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('productions.index') }}"
               class="btn btn-outline text-xs sm:text-sm flex items-center gap-1.5 px-3.5 py-2 rounded-xl">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Runs</span>
            </a>

            @can('products.edit')
                <button type="button"
                        @click="showReverse = true"
                        class="px-3.5 py-2 rounded-xl border border-rose-200 dark:border-rose-800/80 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-xs sm:text-sm font-medium transition shadow-sm inline-flex items-center gap-1.5">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reverse Batch</span>
                </button>
            @endcan
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-4 text-xs sm:text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Overview Bento Card --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/80 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/30">
            <div class="flex items-center gap-2">
                <i data-lucide="clipboard-check" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                    Production Batch Summary
                </h2>
            </div>
            <div class="text-xs text-gray-400 font-mono">
                Operator: {{ $production->user->name ?? 'System' }}
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Target Product --}}
            <div class="space-y-1">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Output Product</span>
                <div class="text-base font-bold text-gray-900 dark:text-gray-100">
                    {{ $product->name ?? '—' }}
                </div>
                <div class="text-xs text-teal-600 dark:text-teal-400 font-medium">
                    Packaging: {{ $productUnit }}
                </div>
            </div>

            {{-- Quantity Output --}}
            <div class="space-y-1">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Quantity Manufactured</span>
                <div class="text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">
                    {{ $fmt2($production->quantity) }}
                    <span class="text-xs font-sans font-normal text-gray-400">{{ $productUnit }}</span>
                </div>
                <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                    <i data-lucide="arrow-down-left" class="w-3.5 h-3.5"></i>
                    <span>Added to product inventory</span>
                </div>
            </div>

            {{-- Ingredients Used Count --}}
            <div class="space-y-1">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Ingredients Consumed</span>
                <div class="text-2xl font-bold font-mono text-gray-900 dark:text-gray-100">
                    {{ $production->materials->count() }}
                    <span class="text-xs font-sans font-normal text-gray-400">raw materials</span>
                </div>
                <div class="text-xs text-gray-400">
                    Auto-decremented from stock
                </div>
            </div>

            {{-- Timestamp & Shift --}}
            <div class="space-y-1">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Executed At</span>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $production->produced_at->format('M d, Y') }}
                </div>
                <div class="text-xs font-mono text-gray-400">
                    {{ $production->produced_at->format('H:i:s') }}
                </div>
            </div>
        </div>

        {{-- Notes Snippet if present --}}
        @if($production->notes)
            <div class="px-6 py-3.5 border-t border-gray-100 dark:border-gray-700/80 bg-gray-50/30 dark:bg-gray-900/20 flex items-start gap-3">
                <i data-lucide="file-text" class="w-4 h-4 text-gray-400 mt-0.5 shrink-0"></i>
                <div class="text-xs text-gray-600 dark:text-gray-300">
                    <span class="font-semibold text-gray-700 dark:text-gray-200">Batch Notes:</span>
                    {{ $production->notes }}
                </div>
            </div>
        @endif
    </div>

    {{-- Consumed Raw Materials Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/80 flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="flask-conical" class="w-4 h-4 text-orange-500"></i>
                <span>Raw Materials Consumed</span>
                <span class="px-2 py-0.5 rounded-full bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300 text-xs font-semibold">
                    {{ $production->materials->count() }}
                </span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/80 text-sm">
                <thead class="bg-gray-50/70 dark:bg-gray-900/40 text-gray-500 dark:text-gray-400 uppercase text-[11px] font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5 text-left">Raw Material</th>
                        <th class="px-6 py-3.5 text-right">Recipe Ratio</th>
                        <th class="px-6 py-3.5 text-right">Recipe Amount Used</th>
                        <th class="px-6 py-3.5 text-right">Stock Deducted</th>
                        <th class="px-6 py-3.5 text-right">Unit Cost</th>
                        <th class="px-6 py-3.5 text-right">Material Cost</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @foreach($production->materials as $m)
                        @php
                            $rm = $m->rawMaterial;
                            $rmCost = (float) ($rm->price ?? 0);
                            $baseQty = $m->base_quantity_used !== null ? (float)$m->base_quantity_used : (float)$m->quantity_used;
                            $lineCost = $rmCost * $baseQty;
                            $totalMaterialCost += $lineCost;
                            $recipeUnit = $m->unit ?: ($rm->unit ?? 'pcs');
                            $baseUnit = $rm->unit ?? 'pcs';
                        @endphp
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20 transition">
                            {{-- Raw Material Name --}}
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                    {{ $rm->name ?? '—' }}
                                </div>
                                <div class="text-[11px] text-gray-400">
                                    Base stock unit: {{ $baseUnit }}
                                </div>
                            </td>

                            {{-- Per Unit Ratio --}}
                            <td class="px-6 py-4 text-right font-mono text-xs text-gray-600 dark:text-gray-300">
                                {{ $fmt2($m->quantity_per_unit) }} {{ $recipeUnit }}
                            </td>

                            {{-- Recipe Amount Used --}}
                            <td class="px-6 py-4 text-right font-mono text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ $fmt2($m->quantity_used) }} {{ $recipeUnit }}
                            </td>

                            {{-- Deducted in Base Stock Unit --}}
                            <td class="px-6 py-4 text-right font-mono text-xs text-teal-600 dark:text-teal-400 font-bold">
                                {{ $fmt2($baseQty) }} {{ $baseUnit }}
                            </td>

                            {{-- Unit Cost --}}
                            <td class="px-6 py-4 text-right text-xs text-gray-600 dark:text-gray-400">
                                RWF {{ $fmt2($rmCost) }} <span class="text-[10px] text-gray-400">/ {{ $baseUnit }}</span>
                            </td>

                            {{-- Line Cost --}}
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-gray-100 text-xs font-mono">
                                RWF {{ $fmt2($lineCost) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot class="bg-gray-50/70 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                    <tr>
                        <td class="px-6 py-3.5 text-xs font-bold text-gray-800 dark:text-gray-200" colspan="5">
                            Total Batch Raw Material Cost
                        </td>
                        <td class="px-6 py-3.5 text-right font-bold text-base font-mono text-gray-900 dark:text-gray-100">
                            RWF {{ $fmt2($totalMaterialCost) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400" colspan="5">
                            Raw Material Cost Per Finished Unit
                        </td>
                        <td class="px-6 py-3 text-right font-bold text-xs font-mono text-teal-600 dark:text-teal-400">
                            RWF {{ $fmt2($production->quantity > 0 ? $totalMaterialCost / $production->quantity : 0) }} / {{ $productUnit }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Stock Movements Link Footer --}}
    <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 bg-white dark:bg-gray-800 p-5 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/60 border border-teal-200 dark:border-teal-800 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                <i data-lucide="history" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Audit & Stock Movement Log</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">All inventory entries associated with this batch were recorded to the stock ledger.</p>
            </div>
        </div>

        <a href="{{ route('stock.history') }}"
           class="btn btn-outline text-xs px-4 py-2 rounded-xl flex items-center gap-1.5 shrink-0">
            <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
            <span>View Stock History</span>
        </a>
    </div>

    {{-- Reverse Confirmation Modal --}}
    @can('products.edit')
    <div
        x-show="showReverse"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @keydown.escape.window="showReverse = false"
    >
        <div
            @click.outside="showReverse = false"
            x-show="showReverse"
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
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Reverse Batch #{{ $production->id }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $product->name ?? '—' }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/80 p-3.5 text-xs text-amber-800 dark:text-amber-300 space-y-1.5">
                <p class="font-semibold">Reversing will execute the following:</p>
                <ul class="space-y-1 pl-1">
                    <li class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>Remove <strong>{{ $fmt2($production->quantity) }} {{ $productUnit }}</strong> from product stock</span>
                    </li>
                    <li class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>Return <strong>{{ $production->materials->count() }} consumed raw material(s)</strong> back to inventory</span>
                    </li>
                    <li class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>Permanently remove this production batch record</span>
                    </li>
                </ul>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                This action is irreversible. Check physical warehouse stock before proceeding.
            </p>

            <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-gray-100 dark:border-gray-700">
                <button type="button" class="btn btn-outline text-xs px-4 py-2 rounded-xl" @click="showReverse = false">
                    Cancel
                </button>
                <form method="POST" action="{{ route('productions.destroy', $production) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger text-xs flex items-center gap-1.5 px-4 py-2 rounded-xl">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        <span>Yes, Reverse Batch</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan

</div>
@endsection
