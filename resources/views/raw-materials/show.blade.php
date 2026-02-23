@extends('layouts.app')
@section('title', $product->name . ' — Raw Material')

@section('content')
@php
    use App\Models\Purchase;
    use App\Models\Sale;
    use App\Models\PurchaseReturn;
    use App\Models\Product as ProductModel;
    use Illuminate\Support\Facades\Route;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $category     = $product->category;
    $catDeleted   = method_exists($category, 'trashed') ? $category?->trashed() : false;
    $catInactive  = (bool) ($category->is_active ?? true) === false;
    $catColor     = $category->color ?? '#6b7280';
    $lowThreshold = (int) config('inventory.low_stock', 5);

    $wac = $product->weightedAverageCost();
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <i data-lucide="flask-conical" class="w-7 h-7 text-teal-600 dark:text-teal-400"></i>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ $product->name }}
                </h1>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 font-medium">
                        Raw Material
                    </span>
                    @if($category)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: {{ $catColor }}"></span>
                            {{ $category->name }}
                            @if($catDeleted)
                                <span class="ml-1 text-[10px] px-1 rounded bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">Archived</span>
                            @elseif($catInactive)
                                <span class="ml-1 text-[10px] px-1 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">Inactive</span>
                            @endif
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('raw-materials.index') }}" class="btn btn-outline text-sm flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>

            @can('products.edit')
                <a href="{{ route('raw-materials.edit', $product) }}" class="btn btn-primary text-sm flex items-center gap-1">
                    <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
                </a>
            @endcan

            @can('stock.view')
                <a href="{{ route('stock.history', ['product_id' => $product->id]) }}"
                   class="btn btn-secondary text-sm flex items-center gap-1">
                    <i data-lucide="history" class="w-4 h-4"></i> Stock Movements
                </a>
            @endcan
        </div>
    </div>

    @cannot('products.view')
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don't have permission to view raw materials.
                    </h2>
                </div>
            </div>
        </div>
    @else

        {{-- Quick stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card title="Unit Price" value="RWF {{ $fmt2($product->price) }}" color="teal" />
            <x-stat-card title="WAC (Cost)" value="RWF {{ $fmt2($wac) }}" color="slate" />
            <x-stat-card title="Total In" value="{{ $fmt0($totalIn) }}" color="green" />
            <x-stat-card
                title="Current Stock"
                value="{{ $fmt0($current) }}"
                color="{{ $current <= 0 ? 'red' : ($current <= $lowThreshold ? 'amber' : 'green') }}"
            />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-stat-card title="Total Out" value="{{ $fmt0($totalOut) }}" color="red" />
            <x-stat-card title="Stock Value (at WAC)" value="RWF {{ $fmt2($product->stockValue()) }}" color="blue" />
        </div>

        {{-- Purchase Price History --}}
        @if($purchaseHistory->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-sm sm:text-base font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <i data-lucide="trending-up" class="w-4 h-4 text-indigo-600"></i>
                        Purchase Price History
                        <span class="px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs">{{ $purchaseHistory->count() }} records</span>
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Supplier</th>
                                <th class="px-4 py-3 text-right">Quantity</th>
                                <th class="px-4 py-3 text-right">Unit Cost</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-center">Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($purchaseHistory as $idx => $ph)
                                @php
                                    $purchaseDate = $ph->purchase->purchase_date ?? $ph->created_at;
                                    $supplier = $ph->purchase->supplier->name ?? '—';
                                    $unitCost = (float)($ph->unit_cost ?? 0);
                                    $prevCost = isset($purchaseHistory[$idx + 1]) ? (float)($purchaseHistory[$idx + 1]->unit_cost ?? 0) : null;
                                    $diff = $prevCost !== null ? $unitCost - $prevCost : null;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($purchaseDate)->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $supplier }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                        {{ $fmt2($ph->quantity) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                        RWF {{ $fmt2($unitCost) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                        RWF {{ $fmt2($ph->total_cost ?? $unitCost * ($ph->quantity ?? 0)) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($diff !== null && $diff != 0)
                                            @if($diff > 0)
                                                <span class="px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 text-xs font-semibold">
                                                    ↑ +{{ $fmt2(abs($diff)) }}
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-xs font-semibold">
                                                    ↓ -{{ $fmt2(abs($diff)) }}
                                                </span>
                                            @endif
                                        @elseif($diff !== null)
                                            <span class="text-gray-400 text-xs">—</span>
                                        @else
                                            <span class="text-gray-400 text-xs">First</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Recent Stock Movements --}}
        @can('stock.view')
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden mt-4">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-sm sm:text-base font-semibold text-gray-800 dark:text-gray-100">
                        Recent Stock Movements
                    </h2>
                    <a href="{{ route('stock.history', ['product_id' => $product->id]) }}"
                       class="text-teal-600 dark:text-teal-400 text-xs sm:text-sm hover:underline">
                        View All →
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-right">Quantity</th>
                                <th class="px-4 py-3 text-right">Unit Cost</th>
                                <th class="px-4 py-3 text-right">Total Cost</th>
                                <th class="px-4 py-3 text-left">Recorded By</th>
                                <th class="px-4 py-3 text-left">Source</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($product->stockMovements as $move)
                                @php
                                    $badge = $move->type === 'in'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';

                                    $src = null;
                                    if ($move->source_type === Purchase::class) {
                                        $src = [
                                            'label' => "Purchase #{$move->source_id}",
                                            'url'   => Route::has('purchases.show') ? route('purchases.show', $move->source_id) : null,
                                            'class' => 'text-indigo-600 dark:text-indigo-400'
                                        ];
                                    } elseif ($move->source_type === Sale::class) {
                                        $src = [
                                            'label' => "Sale #{$move->source_id}",
                                            'url'   => Route::has('sales.show') ? route('sales.show', $move->source_id) : null,
                                            'class' => 'text-green-600 dark:text-green-400'
                                        ];
                                    } elseif ($move->source_type === ProductModel::class) {
                                        $src = [
                                            'label' => 'Manual Adjustment',
                                            'url'   => null,
                                            'class' => 'text-gray-500 dark:text-gray-400'
                                        ];
                                    }
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $move->created_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                                            {{ strtoupper($move->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $fmt2($move->quantity) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                        {{ $fmt2($move->unit_cost ?? 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $fmt2($move->total_cost ?? 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $move->user->name ?? 'System' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($src && $src['url'])
                                            <a href="{{ $src['url'] }}" class="{{ $src['class'] }} hover:underline">
                                                {{ $src['label'] }}
                                            </a>
                                        @elseif($src)
                                            <span class="{{ $src['class'] }}">{{ $src['label'] }}</span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                        No recent stock movements found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endcan

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
