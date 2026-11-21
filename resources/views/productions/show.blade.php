@extends('layouts.app')
@section('title', 'Batch Dossier: ' . ($production->batch_number ?: '#PRD-' . str_pad($production->id, 5, '0', STR_PAD_LEFT)))

@section('content')
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $product = $production->product;
    $productUnit = $product->unit ?? 'pcs';
    $batchNum = $production->batch_number ?: 'BATCH-' . str_pad($production->id, 5, '0', STR_PAD_LEFT);
    $totalMaterialCost = $production->totalMaterialCost();
    $unitCost = (float)($production->unit_cost ?: ($production->quantity > 0 ? $totalMaterialCost / $production->quantity : 0));
    $expectedQty = (float)($production->expected_quantity ?: ($production->quantity + $production->waste_quantity));
    $scrapLossCost = $production->totalScrapCost();
    $yieldBadge = $yieldBadge ?? $production->yieldStatusBadge();
    $expiryBadge = $expiryBadge ?? $production->expiryStatusBadge();
    $scrapPct = $scrapPct ?? $production->scrapPercentage();
    $totalScrapCost = $totalScrapCost ?? $production->totalScrapCost();
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{ showReverseModal: false }">

    {{-- Breadcrumb & Executive Header --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div class="space-y-1.5">
                <nav class="flex items-center gap-2 text-xs font-medium text-slate-400">
                    <a href="{{ route('productions.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">
                        Production Runs
                    </a>
                    <span>/</span>
                    <span class="font-mono text-slate-600 dark:text-slate-300">#PRD-{{ str_pad($production->id, 5, '0', STR_PAD_LEFT) }}</span>
                    <span>/</span>
                    <span class="font-mono text-teal-600 dark:text-teal-400 font-bold">{{ $batchNum }}</span>
                </nav>

                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span>Batch {{ $batchNum }}</span>
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $yieldBadge['class'] }}">
                        <span>{{ $yieldBadge['label'] }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $expiryBadge['class'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $expiryBadge['dot'] }}"></span>
                        <span>{{ $expiryBadge['label'] }}</span>
                    </span>
                </div>

                <p class="text-xs text-slate-400">
                    Manufactured on {{ $production->produced_at ? $production->produced_at->format('M d, Y \a\t H:i') : '—' }}
                    by <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $production->user->name ?? 'System' }}</span>
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('productions.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Batches</span>
                </a>

                @can('products.edit')
                    <button type="button"
                            @click="showReverseModal = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-rose-600 bg-white border border-rose-200/90 hover:bg-rose-50 dark:bg-slate-900 dark:text-rose-400 dark:border-rose-900/60 dark:hover:bg-rose-950/40 shadow-sm transition">
                        <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <span>Reverse Batch</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-4 text-xs sm:text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- 4 Primary Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Manufactured Good Output --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Actual Good Output</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ $fmt2($production->quantity) }} <span class="text-xs font-sans font-normal text-slate-400">{{ $productUnit }}</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400 truncate">
                    Planned: {{ $fmt2($expectedQty) }} {{ $productUnit }}
                </div>
            </div>
        </div>

        {{-- Scrap / Waste Volume --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Scrap &amp; Shrinkage</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-rose-600 dark:text-rose-400">
                    {{ $fmt2($production->waste_quantity) }} <span class="text-xs font-sans font-normal text-slate-400">{{ $productUnit }}</span>
                </div>
                <div class="mt-1 text-xs font-mono text-slate-500 dark:text-slate-400">
                    {{ $fmt2($scrapLossCost) }} RWF loss value
                </div>
            </div>
        </div>

        {{-- Batch Total Material Cost --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Batch Production Cost</span>
                <span class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ $fmt2($totalMaterialCost) }} <span class="text-xs font-sans font-normal text-slate-400">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ $production->materials->count() }} ingredients consumed
                </div>
            </div>
        </div>

        {{-- Material Cost Per Finished Unit --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Finished Unit Cost</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    {{ $fmt2($unitCost) }} <span class="text-xs font-sans font-normal text-slate-400">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Cost per {{ $productUnit }} produced
                </div>
            </div>
        </div>
    </div>

    {{-- Yield Variance & Batch Traceability 2-Col Card --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Card 1: Yield Variance & Scrap Analysis --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Yield Efficiency &amp; Scrap Analysis</span>
                </h3>
                <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full {{ $yieldBadge['class'] }}">
                    {{ $yieldBadge['label'] }}
                </span>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-300 mb-1">
                        <span>Planned Expected Yield:</span>
                        <span class="font-mono font-bold">{{ $fmt2($expectedQty) }} {{ $productUnit }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-300 mb-1">
                        <span>Actual Good Yield:</span>
                        <span class="font-mono font-bold text-emerald-600">{{ $fmt2($production->quantity) }} {{ $productUnit }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-300">
                        <span>Scrap / Lost Volume:</span>
                        <span class="font-mono font-bold text-rose-600">{{ $fmt2($production->waste_quantity) }} {{ $productUnit }} ({{ $fmt2($production->scrapPercentage()) }}%)</span>
                    </div>
                </div>

                {{-- Visual progress --}}
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden flex">
                    <div class="h-full bg-emerald-500" style="width: {{ min(100, (float)($production->yield_percentage ?? 100)) }}%"></div>
                    <div class="h-full bg-rose-500" style="width: {{ max(0, 100 - (float)($production->yield_percentage ?? 100)) }}%"></div>
                </div>

                @if($production->waste_reason)
                    <div class="p-3 rounded-xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-900/40">
                        <span class="font-semibold text-rose-800 dark:text-rose-300">Recorded Root Cause of Scrap:</span>
                        <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $production->waste_reason }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card 2: Lot Traceability & Shelf-Life --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                    </svg>
                    <span>Lot Traceability &amp; Shelf-Life</span>
                </h3>
                <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $batchNum }}
                </span>
            </div>

            <div class="space-y-3 text-xs">
                <div class="flex justify-between text-slate-600 dark:text-slate-300">
                    <span>Manufacturing Date (MFG):</span>
                    <span class="font-mono font-bold">{{ $production->mfg_date ? $production->mfg_date->format('M d, Y') : ($production->produced_at ? $production->produced_at->format('M d, Y') : '—') }}</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-300">
                    <span>Expiration Date (EXP):</span>
                    <span class="font-mono font-bold {{ $production->isExpired() ? 'text-rose-600' : 'text-slate-800 dark:text-slate-200' }}">
                        {{ $production->expiry_date ? $production->expiry_date->format('M d, Y') : 'No expiration set' }}
                    </span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-300">
                    <span>Days Remaining:</span>
                    <span class="font-mono font-bold">
                        @if($production->expiry_date)
                            {{ $production->daysUntilExpiry() >= 0 ? $production->daysUntilExpiry() . ' days left' : abs($production->daysUntilExpiry()) . ' days expired' }}
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-300">
                    <span>Assigned Supervisor:</span>
                    <span class="font-semibold">{{ $production->user->name ?? 'System' }}</span>
                </div>
                @if($production->notes)
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-slate-500">
                        <span class="font-semibold text-slate-700 dark:text-slate-300">Operator Notes:</span>
                        <p class="mt-0.5">{{ $production->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Consumed Raw Materials Table Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Bill of Materials: Consumed Ingredients</h2>
                    <p class="text-xs text-slate-400">Actual raw material quantities deducted from inventory for batch {{ $batchNum }}</p>
                </div>
            </div>

            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 font-mono">
                {{ $production->materials->count() }} Materials
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/30 dark:bg-slate-900/30">
                        <th class="py-3.5 px-6">Raw Material</th>
                        <th class="py-3.5 px-6 text-right">Recipe Ratio</th>
                        <th class="py-3.5 px-6 text-right">Recipe Total Qty</th>
                        <th class="py-3.5 px-6 text-right">Stock Deducted (Base)</th>
                        <th class="py-3.5 px-6 text-right">Unit Intake Cost</th>
                        <th class="py-3.5 px-6 text-right">Line Total Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @foreach($production->materials as $m)
                        @php
                            $rm = $m->rawMaterial;
                            $rmCost = (float) ($rm->cost_price ?: $rm->price ?: 0);
                            $baseQty = $m->base_quantity_used !== null ? (float)$m->base_quantity_used : (float)$m->quantity_used;
                            $lineCost = $rmCost * $baseQty;
                            $recipeUnit = $m->unit ?: ($rm->unit ?? 'pcs');
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                            <td class="py-3.5 px-6 font-semibold text-slate-900 dark:text-white">
                                {{ $rm->name ?? '—' }}
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono text-slate-600 dark:text-slate-300">
                                {{ $fmt2($m->quantity_per_unit) }} {{ $recipeUnit }}/unit
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono text-slate-600 dark:text-slate-300">
                                {{ $fmt2($m->quantity_used) }} {{ $recipeUnit }}
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono font-bold text-teal-600 dark:text-teal-400">
                                -{{ $fmt2($baseQty) }} {{ $rm->unit ?? 'pcs' }}
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono text-slate-600 dark:text-slate-300">
                                {{ $fmt2($rmCost) }} RWF
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ $fmt2($lineCost) }} RWF
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 font-bold">
                        <td colspan="5" class="py-3.5 px-6 text-slate-900 dark:text-white text-right">
                            Total Batch Raw Material Cost:
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono text-teal-600 dark:text-teal-400 text-sm">
                            {{ $fmt2($totalMaterialCost) }} RWF
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Reverse Run Confirmation Modal --}}
    <div x-show="showReverseModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showReverseModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6 space-y-4">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Reverse Production Batch</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $batchNum }} &bull; {{ $product->name ?? 'Unknown' }}</p>
                    </div>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-300">
                    Are you sure you want to reverse this batch? This action will deduct <strong class="font-mono text-slate-900 dark:text-white">{{ $fmt2($production->quantity) }} {{ $productUnit }}</strong> from finished product stock and restore all consumed raw materials to inventory.
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            @click="showReverseModal = false"
                            class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <form action="{{ route('productions.destroy', $production) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-xs font-bold rounded-xl text-white bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-500/20 transition">
                            Confirm Reversal
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
