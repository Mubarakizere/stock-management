@extends('layouts.app')
@section('title', $product->name . ' Details')

@section('content')
@php
    use App\Models\Purchase;
    use App\Models\Sale;
    use App\Models\PurchaseReturn;
    use App\Models\Product as ProductModel;
    use Illuminate\Support\Facades\Route;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $category     = $product->category; // may be soft-deleted (withTrashed on relation)
    $catDeleted   = method_exists($category, 'trashed') ? $category?->trashed() : false;
    $catInactive  = (bool) ($category->is_active ?? true) === false;
    $catColor     = $category->color ?? '#6b7280';
    $lowThreshold = (int) config('inventory.low_stock', 5);

    $wac = $product->weightedAverageCost();
    $margin = ($product->price ?? 0) > 0
        ? (($product->price - $wac) / max(0.0001, $product->price)) * 100
        : 0;
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <i data-lucide="package" class="w-7 h-7 text-indigo-600 dark:text-indigo-400"></i>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ $product->name }}
                </h1>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                    @if($product->sku)
                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                            SKU: {{ $product->sku }}
                        </span>
                    @endif
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
            @can('products.view')
                <a href="{{ route('products.index') }}" class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Back
                </a>
            @endcan

            @can('products.edit')
                <a href="{{ route('products.edit', $product) }}" class="btn btn-primary text-sm flex items-center gap-1">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                    Edit
                </a>
                <button
                    type="button"
                    class="btn btn-warning text-sm flex items-center gap-1"
                    @click="$store.quickAdjust.open({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $current }})">
                    <i data-lucide="plus-minus" class="w-4 h-4"></i>
                    ± Quick Adjust
                </button>
                <a href="{{ route('products.adjust.create', $product) }}" class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="sliders" class="w-4 h-4"></i>
                    Full Adjust
                </a>
            @endcan

            @can('stock.view')
                <a href="{{ route('stock.history', ['product_id' => $product->id]) }}"
                   class="btn btn-secondary text-sm flex items-center gap-1">
                    <i data-lucide="history" class="w-4 h-4"></i>
                    Stock Movements
                </a>
            @endcan
        </div>
    </div>

    @cannot('products.view')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to view products.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- Quick stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card title="Selling Price" value="RWF {{ $fmt2($product->price) }}" color="indigo" />
            <x-stat-card title="WAC (Cost)" value="RWF {{ $fmt2($wac) }}" color="slate" />
            <x-stat-card
                title="Margin"
                value="{{ $fmt2($margin) }}%"
                color="{{ $margin >= 0 ? 'emerald' : 'rose' }}"
            />
            <x-stat-card
                title="Current Stock"
                value="{{ $fmt0($current) }}"
                color="{{ $current <= 0 ? 'red' : ($current <= $lowThreshold ? 'amber' : 'green') }}"
            />
        </div>

        {{-- Totals --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card title="Total In" value="{{ $fmt0($totalIn) }}" color="green" />
            <x-stat-card title="Total Out" value="{{ $fmt0($totalOut) }}" color="red" />
            <x-stat-card title="Stock Value (at WAC)" value="RWF {{ $fmt2($product->stockValue()) }}" color="blue" />
        </div>

        {{-- Recent Stock Movements --}}
        @can('stock.view')
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden mt-4">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-sm sm:text-base font-semibold text-gray-800 dark:text-gray-100">
                        Recent Stock Movements
                    </h2>
                    <a href="{{ route('stock.history', ['product_id' => $product->id]) }}"
                       class="text-indigo-600 dark:text-indigo-400 text-xs sm:text-sm hover:underline">
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

                                    // Build a safe link for the "source" column
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
                                    } elseif ($move->source_type === PurchaseReturn::class) {
                                        $prRoute = null;
                                        foreach (['purchase-returns.show','purchase_returns.show','purchasereturns.show','returns.purchase.show'] as $cand) {
                                            if (Route::has($cand)) { $prRoute = $cand; break; }
                                        }
                                        $src = [
                                            'label' => "Purchase Return #{$move->source_id}",
                                            'url'   => $prRoute ? route($prRoute, $move->source_id) : null,
                                            'class' => 'text-amber-600 dark:text-amber-400'
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

{{-- Quick Adjust Modal --}}
@can('products.edit')
<div
    x-data
    x-show="$store.quickAdjust.show"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    @keydown.escape.window="$store.quickAdjust.close()"
>
    <div
        @click.outside="$store.quickAdjust.close()"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md mx-4"
    >
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="package" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Quick Stock Adjustment</h2>
        </div>

        <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">
            Product: <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="$store.quickAdjust.productName"></span>
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            Current stock: <span class="font-semibold" x-text="$store.quickAdjust.currentStock"></span>
        </p>

        <div class="flex gap-2 mb-4">
            <button type="button" class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-1.5"
                :class="$store.quickAdjust.type === 'add' ? 'bg-emerald-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                @click="$store.quickAdjust.type = 'add'">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Add
            </button>
            <button type="button" class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-1.5"
                :class="$store.quickAdjust.type === 'remove' ? 'bg-rose-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                @click="$store.quickAdjust.type = 'remove'">
                <i data-lucide="minus-circle" class="w-4 h-4"></i> Remove
            </button>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Quantity</label>
            <input type="number" step="1" min="1" class="form-input w-full"
                x-model.number="$store.quickAdjust.quantity" placeholder="e.g. 10"
                @keydown.enter.prevent="$store.quickAdjust.submit()">
        </div>

        <div class="mb-4 p-3 rounded-lg text-sm font-medium"
            :class="$store.quickAdjust.type === 'add' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300'">
            <span x-text="$store.quickAdjust.currentStock"></span>
            <span x-text="$store.quickAdjust.type === 'add' ? ' + ' : ' − '"></span>
            <span x-text="$store.quickAdjust.quantity || 0"></span>
            <span> = </span>
            <span class="font-bold" x-text="$store.quickAdjust.type === 'add' ? ($store.quickAdjust.currentStock + ($store.quickAdjust.quantity || 0)) : Math.max(0, $store.quickAdjust.currentStock - ($store.quickAdjust.quantity || 0))"></span>
        </div>

        <div class="mb-5">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Reason (optional)</label>
            <textarea class="form-input w-full text-sm" rows="2" x-model="$store.quickAdjust.notes" placeholder="e.g. Physical count, damaged goods…"></textarea>
        </div>

        <template x-if="$store.quickAdjust.error">
            <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm">
                <span x-text="$store.quickAdjust.error"></span>
            </div>
        </template>

        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline text-sm" @click="$store.quickAdjust.close()">Cancel</button>
            <button type="button" class="btn text-sm text-white"
                :class="$store.quickAdjust.type === 'add' ? 'btn-success' : 'btn-danger'"
                :disabled="$store.quickAdjust.loading || !$store.quickAdjust.quantity || $store.quickAdjust.quantity <= 0"
                @click="$store.quickAdjust.submit()">
                <span x-show="!$store.quickAdjust.loading" class="flex items-center gap-1"><i data-lucide="check" class="w-4 h-4"></i> Apply</span>
                <span x-show="$store.quickAdjust.loading">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endcan

{{-- Toast Notification --}}
<div
    x-data="{ toasts: [] }"
    x-on:quick-adjust-toast.window="toasts.push({ id: Date.now(), message: $event.detail.message, success: $event.detail.success }); setTimeout(() => toasts.shift(), 4000);"
    class="fixed bottom-6 right-6 z-[60] flex flex-col gap-2">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition class="px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2 min-w-[280px]"
            :class="toast.success ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });

    document.addEventListener('alpine:init', () => {
        Alpine.store('quickAdjust', {
            show: false,
            productId: null,
            productName: '',
            currentStock: 0,
            type: 'add',
            quantity: null,
            notes: '',
            error: '',
            loading: false,

            open(id, name, stock) {
                this.productId = id;
                this.productName = name;
                this.currentStock = stock;
                this.type = 'add';
                this.quantity = null;
                this.notes = '';
                this.error = '';
                this.loading = false;
                this.show = true;
            },

            close() { this.show = false; },

            async submit() {
                if (!this.quantity || this.quantity <= 0) return;
                this.loading = true;
                this.error = '';

                try {
                    const resp = await fetch(`/products/${this.productId}/quick-adjust`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ type: this.type, quantity: this.quantity, notes: this.notes || null }),
                    });

                    const data = await resp.json();

                    if (!resp.ok || !data.success) {
                        this.error = data.message || 'Something went wrong.';
                        this.loading = false;
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('quick-adjust-toast', {
                        detail: { message: `✓ ${data.message}`, success: true }
                    }));

                    this.close();
                    // Reload to reflect updated stats on the show page
                    setTimeout(() => location.reload(), 800);

                } catch (e) {
                    this.error = 'Network error. Please try again.';
                    this.loading = false;
                }
            },
        });
    });
</script>
@endpush
@endsection
