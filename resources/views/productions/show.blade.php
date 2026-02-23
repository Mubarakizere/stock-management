@extends('layouts.app')
@section('title', 'Production #' . $production->id)

@section('content')
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $totalMaterialCost = 0;
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{ showReverse: false }">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <i data-lucide="factory" class="w-6 h-6 text-violet-600 dark:text-violet-400"></i>
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Production #{{ $production->id }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $production->produced_at->format('M d, Y \a\t H:i') }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('productions.index') }}" class="btn btn-outline text-sm flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back
            </a>
            @can('products.edit')
                <button type="button"
                        @click="showReverse = true"
                        class="btn btn-danger text-sm flex items-center gap-1">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    Reverse Production
                </button>
            @endcan
        </div>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 p-4 text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Product</p>
            <p class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $production->product->name ?? '—' }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Qty Produced</p>
            <p class="text-lg font-bold text-violet-700 dark:text-violet-300 mt-1">{{ $fmt2($production->quantity) }} units</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Recorded By</p>
            <p class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $production->user->name ?? '—' }}</p>
        </div>
    </div>

    {{-- Notes --}}
    @if($production->notes)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Notes</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $production->notes }}</p>
        </div>
    @endif

    {{-- Materials Consumed --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="flask-conical" class="w-4 h-4 text-teal-600"></i>
                Raw Materials Consumed
                <span class="px-2 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-xs">
                    {{ $production->materials->count() }} item(s)
                </span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Raw Material</th>
                        <th class="px-4 py-3 text-right">Per Unit</th>
                        <th class="px-4 py-3 text-right">Total Used</th>
                        <th class="px-4 py-3 text-right">Unit Cost</th>
                        <th class="px-4 py-3 text-right">Line Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($production->materials as $m)
                        @php
                            $rmCost = $m->rawMaterial->price ?? 0;
                            $lineCost = $rmCost * $m->quantity_used;
                            $totalMaterialCost += $lineCost;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                {{ $m->rawMaterial->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                {{ $fmt2($m->quantity_per_unit) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ $fmt2($m->quantity_used) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                RWF {{ $fmt2($rmCost) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                RWF {{ $fmt2($lineCost) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-800/70">
                    <tr>
                        <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-100" colspan="4">
                            Total Material Cost
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100">
                            RWF {{ $fmt2($totalMaterialCost) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-100" colspan="4">
                            Cost per Unit
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-violet-700 dark:text-violet-300">
                            RWF {{ $fmt2($production->quantity > 0 ? $totalMaterialCost / $production->quantity : 0) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Reverse Confirmation Modal --}}
    @can('products.edit')
    <div
        x-show="showReverse"
        x-cloak
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 backdrop-blur-sm"
        @keydown.escape.window="showReverse = false"
    >
        <div
            @click.outside="showReverse = false"
            x-show="showReverse"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-6 sm:scale-95"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                   rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md mx-0 sm:mx-4 p-6"
        >
            {{-- Modal header --}}
            <div class="flex items-start gap-3 mb-4">
                <div class="p-2.5 rounded-full bg-rose-100 dark:bg-rose-900/30 shrink-0">
                    <i data-lucide="rotate-ccw" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Reverse Production #{{ $production->id }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $production->product->name ?? '—' }}</p>
                </div>
                <button @click="showReverse = false" class="ml-auto p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <i data-lucide="x" class="w-4 h-4 text-gray-400"></i>
                </button>
            </div>

            {{-- What will happen --}}
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-3 mb-4">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">This will permanently:</p>
                <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1.5">
                    <li class="flex items-start gap-2">
                        <i data-lucide="minus-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0 text-rose-500"></i>
                        <span>Remove <strong>{{ number_format($production->quantity, 2) }} unit(s)</strong> of <strong>{{ $production->product->name ?? '—' }}</strong> from stock</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="plus-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0 text-emerald-500"></i>
                        <span>Return <strong>{{ $production->materials->count() }} raw material(s)</strong> back to stock</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400"></i>
                        <span>Delete this production record permanently</span>
                    </li>
                </ul>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 mb-5">This cannot be undone.</p>

            <div class="flex justify-end gap-3">
                <button type="button" class="btn btn-outline text-sm" @click="showReverse = false">Cancel</button>
                <form method="POST" action="{{ route('productions.destroy', $production) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger text-sm flex items-center gap-1.5">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        Yes, Reverse
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan

</div>
@endsection
