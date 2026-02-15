@extends('layouts.app')
@section('title', 'Products')

@section('content')
@php
    use App\Models\Category;
    use Illuminate\Support\Str;

    // Number formatting helpers
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    // Low-stock threshold (fallback to 5 if not provided)
    $threshold = (int)($threshold ?? 5);

    // Ensure categories exist even if controller forgot to pass them
    $allCategories    = $categories ?? Category::orderBy('name')->get();
    $usableCategories = collect($allCategories)
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'product', ['product','both']))
        ->values();

    // Make stats work whether $products is a Paginator or a Collection
    $__coll = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products);

    $isPaginated = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $pageCount   = $isPaginated ? $products->count() : $__coll->count();
    $totalCount  = $isPaginated ? $products->total() : $pageCount;

    // Page (current result set) derived stats
    $pageUnits   = $__coll->sum(fn($p) => max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0)));
    $pageValue   = $__coll->sum(function($p){
        $units = max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0));
        return $units * (float)($p->cost_price ?? 0);
    });
    $pageRevenue = $__coll->sum(function($p){
        $units = max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0));
        return $units * (float)($p->price ?? 0);
    });
    $pageReturns = $__coll->sum(fn($p) => (float)($p->qty_returned ?? 0));
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="flex items-center gap-2 text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
            <i data-lucide="package" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Products</span>
        </h1>

        <div class="flex flex-wrap gap-2 justify-start md:justify-end">
            @can('products.create')
                <a href="{{ route('products.create') }}"
                   class="btn btn-primary flex items-center gap-1 text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add Product</span>
                </a>
            @endcan

            @can('products.view')
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" class="btn btn-outline text-sm flex items-center gap-1">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Export Report</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 ml-1"></i>
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg py-1 z-50">
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'all']) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">All Products</a>
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'low']) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Low Stock (≤ {{ $threshold }})</a>
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'out']) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Out of Stock</a>
                    </div>
                </div>
            @endcan

            @can('stock.view')
                <a href="{{ route('stock.history', request()->only('product_id')) }}"
                   class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="history" class="w-4 h-4"></i>
                    <span>Stock Movements</span>
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

        {{-- Filters --}}
        <form
            method="GET"
            action="{{ route('products.index') }}"
            x-data="{ qcat: '' }"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm space-y-3"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3">
                {{-- Search --}}
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Search</label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                        <input
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Name, SKU…"
                            class="form-input w-full pl-8 text-sm">
                    </div>
                </div>

                {{-- Category --}}
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Category</label>

                    <div class="relative mb-1">
                        <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                        <input
                            x-model="qcat"
                            type="text"
                            placeholder="Filter categories…"
                            class="form-input w-full pl-8 text-xs"
                            aria-label="Filter categories">
                    </div>

                    <select
                        name="category_id"
                        class="form-select w-full text-sm"
                        x-init="
                            $watch('qcat', v => {
                                const opts = $el.querySelectorAll('option[data-name]');
                                const k = (v || '').toLowerCase();
                                opts.forEach(o => {
                                    o.hidden = !o.dataset.name.includes(k) && o.value !== '';
                                });
                            })
                        "
                    >
                        <option value="">All categories</option>
                        @foreach($usableCategories as $c)
                            @php
                                $label = trim($c->name.' '.($c->code ? "({$c->code})" : ''));
                            @endphp
                            <option
                                value="{{ $c->id }}"
                                data-name="{{ Str::lower($label) }}"
                                @selected(request('category_id') == $c->id)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Stock status --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Stock Status</label>
                    <select name="stock_status" class="form-select w-full text-sm">
                        <option value="">Any</option>
                        <option value="in"  @selected(request('stock_status')==='in')>In stock</option>
                        <option value="low" @selected(request('stock_status')==='low')>Low (≤ {{ $threshold }})</option>
                        <option value="out" @selected(request('stock_status')==='out')>Out of stock</option>
                    </select>
                </div>

                {{-- Per page --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Per page</label>
                    <select name="per_page" class="form-select w-full text-sm" onchange="this.form.submit()">
                        @foreach([10,20,50,100] as $pp)
                            <option value="{{ $pp }}" @selected((int)request('per_page', 20)===$pp)>{{ $pp }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-end pt-2">
                <button class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route('products.index') }}"
                   class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>

        {{-- Page stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <x-stat-card title="Products (page)" value="{{ $fmt0($pageCount) }}" color="indigo" />
            <x-stat-card title="Units in stock (page)" value="{{ $fmt0($pageUnits) }}" color="blue" />
            <x-stat-card title="Stock value @ cost (page)" value="RWF {{ $fmt2($pageValue) }}" color="emerald" />
            <x-stat-card title="Potential revenue (page)" value="RWF {{ $fmt2($pageRevenue) }}" color="amber" />
        </div>

        @if($isPaginated && $totalCount !== $pageCount)
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Showing {{ $pageCount }} of {{ $fmt0($totalCount) }} matching products.
            </div>
        @endif

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-[1200px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-left">SKU</th>
                        <th class="px-4 py-3 text-right">Cost (WAC)</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Margin</th>
                        <th class="px-4 py-3 text-right">In</th>
                        <th class="px-4 py-3 text-right">Out</th>
                        <th class="px-4 py-3 text-right">Returned</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3 text-left">Last Moved</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($products as $p)
                        @php
                            $in   = (float)($p->qty_in ?? 0);
                            $out  = (float)($p->qty_out ?? 0);
                            $ret  = (float)($p->qty_returned ?? 0);
                            $stk  = max(0, $in - $out);
                            $low  = $stk <= $threshold && $stk > 0;
                            $zero = $stk <= 0;

                            $cost   = (float)($p->cost_price ?? 0);
                            $price  = (float)($p->price ?? 0);
                            $margin = $price > 0 ? (($price - $cost) / $price) * 100 : 0;

                            $last  = $p->last_moved_at ? \Carbon\Carbon::parse($p->last_moved_at)->diffForHumans() : '—';

                            $cat   = $p->category ?? null;
                            $catOk = $cat && (($cat->is_active ?? true) && in_array($cat->kind ?? 'product',['product','both']));
                            $dot   = $cat->color ?? '#6b7280';
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition" data-product-id="{{ $p->id }}">
                            {{-- Name --}}
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">
                                @can('products.view')
                                    <a href="{{ route('products.show', $p) }}"
                                       class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $p->name }}
                                    </a>
                                @else
                                    {{ $p->name }}
                                @endcan

                                @if($ret > 0)
                                    <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        <i data-lucide="u-turn-left" class="w-3 h-3"></i>
                                        Returns
                                    </span>
                                @endif
                            </td>

                            {{-- Category --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @if($cat)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: {{ $dot }}"></span>
                                        <span>{{ $cat->name }}</span>

                                        @if(!empty($cat->code))
                                            <span class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                                {{ $cat->code }}
                                            </span>
                                        @endif

                                        @unless($catOk)
                                            <span class="ml-2 text-[11px] px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                                Not usable
                                            </span>
                                        @endunless
                                    </span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-300">—</span>
                                @endif
                            </td>

                            {{-- SKU --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $p->sku ?? '—' }}
                            </td>

                            {{-- Cost --}}
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                RWF {{ $fmt2($cost) }}
                            </td>

                            {{-- Price --}}
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                RWF {{ $fmt2($price) }}
                            </td>

                            {{-- Margin --}}
                            <td class="px-4 py-3 text-right">
                                <span class="font-medium {{ $margin >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">
                                    {{ $fmt2($margin) }}%
                                </span>
                            </td>

                            {{-- In --}}
                            <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-300 font-semibold">
                                {{ $fmt0($in) }}
                            </td>

                            {{-- Out --}}
                            <td class="px-4 py-3 text-right text-rose-700 dark:text-rose-300 font-semibold">
                                {{ $fmt0($out) }}
                            </td>

                            {{-- Returned --}}
                            <td class="px-4 py-3 text-right text-amber-700 dark:text-amber-300">
                                {{ $fmt0($ret) }}
                            </td>

                            {{-- Stock --}}
                            <td class="px-4 py-3 text-right font-semibold
                                {{ $zero ? 'text-rose-600 dark:text-rose-300'
                                         : ($low ? 'text-amber-700 dark:text-amber-300'
                                                 : 'text-gray-900 dark:text-gray-100') }}">
                                <span data-stock>{{ $fmt0($stk) }}</span>
                                @if($zero)
                                    <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">
                                        Out
                                    </span>
                                @elseif($low)
                                    <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                        Low
                                    </span>
                                @endif
                            </td>

                            {{-- Last moved --}}
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ $last }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                                @can('products.view')
                                    <a href="{{ route('products.show', $p) }}"
                                       class="btn btn-secondary text-xs inline-flex items-center gap-1 px-2.5 py-1.5">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        View
                                    </a>
                                @endcan

                                @can('products.edit')
                                    <a href="{{ route('products.edit', $p) }}"
                                       class="btn btn-outline text-xs inline-flex items-center gap-1 px-2.5 py-1.5">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        Edit
                                    </a>
                                @endcan

                                @can('products.edit')
                                    <button
                                        type="button"
                                        class="btn btn-warning text-xs inline-flex items-center gap-1 px-2.5 py-1.5"
                                        @click="$store.quickAdjust.open({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $stk }})">
                                        <i data-lucide="plus-minus" class="w-3.5 h-3.5"></i>
                                        ± Stock
                                    </button>
                                @endcan

                                @can('stock.view')
                                    <a href="{{ route('stock.history', ['product_id' => $p->id]) }}"
                                       class="btn btn-outline text-xs inline-flex items-center gap-1 px-2.5 py-1.5">
                                        <i data-lucide="history" class="w-3.5 h-3.5"></i>
                                        Moves
                                    </a>
                                @endcan

                                @can('products.delete')
                                    <form action="{{ route('products.destroy', $p) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            class="btn btn-danger text-xs inline-flex items-center gap-1 px-2.5 py-1.5"
                                            @click="$store.confirm.openWith($el.closest('form'))">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($isPaginated)
            <div class="mt-4">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif

    @endcannot
</div>

{{-- Global Delete Confirmation Modal --}}
@can('products.delete')
<div
    x-data
    x-show="$store.confirm.open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    @keydown.escape.window="$store.confirm.close()"
>
    <div
        @click.outside="$store.confirm.close()"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md"
    >
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Delete product</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this product?
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.close()">Cancel</button>
            <button type="button" class="btn btn-danger" @click="$store.confirm.confirm()">
                Delete
            </button>
        </div>
    </div>
</div>
@endcan

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

        {{-- Type toggle --}}
        <div class="flex gap-2 mb-4">
            <button
                type="button"
                class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-1.5"
                :class="$store.quickAdjust.type === 'add'
                    ? 'bg-emerald-600 text-white shadow-md'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                @click="$store.quickAdjust.type = 'add'"
            >
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                Add
            </button>
            <button
                type="button"
                class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-1.5"
                :class="$store.quickAdjust.type === 'remove'
                    ? 'bg-rose-600 text-white shadow-md'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                @click="$store.quickAdjust.type = 'remove'"
            >
                <i data-lucide="minus-circle" class="w-4 h-4"></i>
                Remove
            </button>
        </div>

        {{-- Quantity --}}
        <div class="mb-4">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Quantity</label>
            <input
                type="number"
                step="1"
                min="1"
                class="form-input w-full"
                x-model.number="$store.quickAdjust.quantity"
                placeholder="e.g. 10"
                @keydown.enter.prevent="$store.quickAdjust.submit()"
            >
        </div>

        {{-- Preview --}}
        <div class="mb-4 p-3 rounded-lg text-sm font-medium"
            :class="$store.quickAdjust.type === 'add'
                ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300'
                : 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300'"
        >
            <span x-text="$store.quickAdjust.currentStock"></span>
            <span x-text="$store.quickAdjust.type === 'add' ? ' + ' : ' − '"></span>
            <span x-text="$store.quickAdjust.quantity || 0"></span>
            <span> = </span>
            <span class="font-bold" x-text="$store.quickAdjust.type === 'add'
                ? ($store.quickAdjust.currentStock + ($store.quickAdjust.quantity || 0))
                : Math.max(0, $store.quickAdjust.currentStock - ($store.quickAdjust.quantity || 0))"></span>
        </div>

        {{-- Notes --}}
        <div class="mb-5">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">Reason (optional)</label>
            <textarea
                class="form-input w-full text-sm"
                rows="2"
                x-model="$store.quickAdjust.notes"
                placeholder="e.g. Physical count, damaged goods…"
            ></textarea>
        </div>

        {{-- Error --}}
        <template x-if="$store.quickAdjust.error">
            <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm">
                <span x-text="$store.quickAdjust.error"></span>
            </div>
        </template>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline text-sm" @click="$store.quickAdjust.close()">Cancel</button>
            <button
                type="button"
                class="btn text-sm text-white"
                :class="$store.quickAdjust.type === 'add' ? 'btn-success' : 'btn-danger'"
                :disabled="$store.quickAdjust.loading || !$store.quickAdjust.quantity || $store.quickAdjust.quantity <= 0"
                @click="$store.quickAdjust.submit()"
            >
                <span x-show="!$store.quickAdjust.loading" class="flex items-center gap-1">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Apply
                </span>
                <span x-show="$store.quickAdjust.loading">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endcan

{{-- Toast Notification --}}
<div
    x-data="{ toasts: [] }"
    x-on:quick-adjust-toast.window="
        toasts.push({ id: Date.now(), message: $event.detail.message, success: $event.detail.success });
        setTimeout(() => toasts.shift(), 4000);
    "
    class="fixed bottom-6 right-6 z-[60] flex flex-col gap-2"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2 min-w-[280px]"
            :class="toast.success
                ? 'bg-emerald-600 text-white'
                : 'bg-rose-600 text-white'"
        >
            <i :data-lucide="toast.success ? 'check-circle' : 'alert-circle'" class="w-4 h-4"></i>
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
        // Global delete-confirm store (same pattern as sales)
        Alpine.store('confirm', {
            open: false,
            submitEl: null,
            openWith(form) {
                this.submitEl = form;
                this.open = true;
            },
            close() {
                this.open = false;
                this.submitEl = null;
            },
            confirm() {
                if (this.submitEl) this.submitEl.submit();
                this.close();
            },
        });

        // Quick stock adjustment store
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

            close() {
                this.show = false;
            },

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
                        body: JSON.stringify({
                            type: this.type,
                            quantity: this.quantity,
                            notes: this.notes || null,
                        }),
                    });

                    const data = await resp.json();

                    if (!resp.ok || !data.success) {
                        this.error = data.message || 'Something went wrong.';
                        this.loading = false;
                        return;
                    }

                    // Update the stock cell in the table row
                    const row = document.querySelector(`[data-product-id="${this.productId}"]`);
                    if (row) {
                        const stockCell = row.querySelector('[data-stock]');
                        if (stockCell) {
                            stockCell.textContent = Math.round(data.new_stock).toLocaleString();
                            // Flash the cell
                            stockCell.closest('td').classList.add('ring-2', 'ring-indigo-400', 'ring-offset-1');
                            setTimeout(() => {
                                stockCell.closest('td').classList.remove('ring-2', 'ring-indigo-400', 'ring-offset-1');
                            }, 2000);
                        }
                    }

                    // Toast
                    window.dispatchEvent(new CustomEvent('quick-adjust-toast', {
                        detail: { message: `✓ ${data.message}`, success: true }
                    }));

                    this.close();
                    // Re-init icons for any new content
                    if (window.lucide) lucide.createIcons();

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