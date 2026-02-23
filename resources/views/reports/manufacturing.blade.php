@extends('layouts.app')
@section('title', 'Manufacturing Reports')

@section('content')
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <i data-lucide="bar-chart-3" class="w-6 h-6 text-violet-600 dark:text-violet-400"></i>
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">Manufacturing Reports</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Production, raw material usage & stock levels</p>
            </div>
        </div>
    </div>

    {{-- Date Filters --}}
    <form method="GET" action="{{ route('reports.manufacturing') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="start_date" value="{{ $start }}" class="form-input text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="end_date" value="{{ $end }}" class="form-input text-sm">
        </div>
        <button type="submit" class="btn btn-primary btn-sm text-sm">Apply</button>
        <a href="{{ route('reports.manufacturing') }}" class="btn btn-outline btn-sm text-sm">Reset</a>
    </form>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Production Runs</p>
            <p class="text-2xl font-bold text-violet-700 dark:text-violet-300 mt-1">{{ $fmt0($productionRuns) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Units Produced</p>
            <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">{{ $fmt2($totalUnitsProduced) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Material Cost</p>
            <p class="text-2xl font-bold text-orange-700 dark:text-orange-300 mt-1">RWF {{ $fmt2($totalMaterialCost) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">RM Alerts</p>
            <p class="text-2xl font-bold mt-1 {{ $rmOutCount > 0 ? 'text-rose-600' : 'text-gray-700 dark:text-gray-300' }}">
                {{ $rmOutCount }} out
                @if($rmLowCount > 0)
                    <span class="text-sm font-medium text-amber-600 dark:text-amber-400">· {{ $rmLowCount }} low</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Two-column layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- 1. Top Produced Products --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <i data-lucide="trophy" class="w-4 h-4 text-violet-600"></i>
                    Top Produced Products
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-4 py-2 text-left">Product</th>
                            <th class="px-4 py-2 text-right">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($topProduced as $tp)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $tp->product->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-violet-700 dark:text-violet-300">{{ $fmt2($tp->total_qty) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-4 text-center text-gray-500 text-sm">No production data for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 2. Raw Material Usage --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <i data-lucide="flask-conical" class="w-4 h-4 text-teal-600"></i>
                    Raw Material Usage
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-4 py-2 text-left">Material</th>
                            <th class="px-4 py-2 text-right">Used</th>
                            <th class="px-4 py-2 text-right">Cost</th>
                            <th class="px-4 py-2 text-right">In Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($rawMaterialUsage as $mu)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $mu['name'] }}</td>
                                <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ $fmt2($mu['total_used']) }}</td>
                                <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">RWF {{ $fmt2($mu['total_cost']) }}</td>
                                <td class="px-4 py-2 text-right {{ $mu['in_stock'] <= 0 ? 'text-rose-600 font-semibold' : 'text-gray-700 dark:text-gray-300' }}">
                                    {{ $fmt0($mu['in_stock']) }}
                                    @if($mu['in_stock'] <= 0)
                                        <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700">Out</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500 text-sm">No material usage for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($rawMaterialUsage->isNotEmpty())
                        <tfoot class="bg-gray-50 dark:bg-gray-800/70">
                            <tr>
                                <td class="px-4 py-2 font-semibold text-gray-800 dark:text-gray-100" colspan="2">Total</td>
                                <td class="px-4 py-2 text-right font-bold text-gray-900 dark:text-gray-100">RWF {{ $fmt2($totalMaterialCost) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- 3. Stock Levels --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Raw Materials Stock --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <i data-lucide="flask-conical" class="w-4 h-4 text-teal-600"></i>
                    Raw Materials Stock
                    <span class="px-2 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-xs">{{ $rawMaterialsStock->count() }}</span>
                </h2>
                <span class="text-xs text-gray-500">Total: RWF {{ $fmt2($totalRmValue) }}</span>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-right">Stock</th>
                            <th class="px-4 py-2 text-right">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($rawMaterialsStock as $rm)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 {{ $rm['out'] ? 'bg-rose-50 dark:bg-rose-900/10' : ($rm['low'] ? 'bg-amber-50 dark:bg-amber-900/10' : '') }}">
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $rm['name'] }}</td>
                                <td class="px-4 py-2 text-right {{ $rm['out'] ? 'text-rose-600 font-bold' : ($rm['low'] ? 'text-amber-600 font-semibold' : 'text-gray-700 dark:text-gray-300') }}">
                                    {{ $fmt0($rm['stock']) }}
                                    @if($rm['out'])
                                        <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full bg-rose-100 text-rose-700">Out</span>
                                    @elseif($rm['low'])
                                        <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full bg-amber-100 text-amber-700">Low</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">RWF {{ $fmt2($rm['value']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500 text-sm">No raw materials found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Finished Products Stock (with recipes) --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <i data-lucide="box" class="w-4 h-4 text-blue-600"></i>
                    Finished Products Stock
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs">{{ $finishedProductsStock->count() }}</span>
                </h2>
                <span class="text-xs text-gray-500">Total: RWF {{ $fmt2($totalFpValue) }}</span>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-right">Stock</th>
                            <th class="px-4 py-2 text-right">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($finishedProductsStock as $fp)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 {{ $fp['out'] ? 'bg-rose-50 dark:bg-rose-900/10' : ($fp['low'] ? 'bg-amber-50 dark:bg-amber-900/10' : '') }}">
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $fp['name'] }}</td>
                                <td class="px-4 py-2 text-right {{ $fp['out'] ? 'text-rose-600 font-bold' : ($fp['low'] ? 'text-amber-600 font-semibold' : 'text-gray-700 dark:text-gray-300') }}">
                                    {{ $fmt0($fp['stock']) }}
                                    @if($fp['out'])
                                        <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full bg-rose-100 text-rose-700">Out</span>
                                    @elseif($fp['low'])
                                        <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full bg-amber-100 text-amber-700">Low</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">RWF {{ $fmt2($fp['value']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500 text-sm">No products with recipes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
