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
        <div x-data="bulkSelect()" class="relative">

            {{-- Bulk Actions Bar --}}
            <div x-show="selectedIds.length > 0" x-transition x-cloak
                 class="mb-3 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-xl px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-indigo-700 dark:text-indigo-300">
                    <i data-lucide="check-square" class="w-4 h-4"></i>
                    <span><strong x-text="selectedIds.length"></strong> product(s) selected</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="selectedIds = []; allSelected = false"
                            class="btn btn-outline text-xs px-3 py-1.5">
                        Clear
                    </button>
                    @can('products.delete')
                        <button type="button" @click="$store.bulkConfirm.open = true"
                                class="btn btn-danger text-xs px-3 py-1.5 flex items-center gap-1">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            Delete Selected
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Hidden form for bulk delete --}}
            <form id="bulk-delete-form" action="{{ route('products.bulk-delete') }}" method="POST" class="hidden">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
            </form>

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-[1200px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
                    <tr>
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" x-model="allSelected" @change="toggleAll()"
                                   class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        </th>
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

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition" :class="selectedIds.includes({{ $p->id }}) ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : ''" data-product-id="{{ $p->id }}">
                            {{-- Checkbox --}}
                            <td class="px-4 py-3">
                                <input type="checkbox" value="{{ $p->id }}"
                                       x-model.number="selectedIds"
                                       @change="updateAllSelected()"
                                       class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                            </td>
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
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="$store.productActions.open({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $stk }}, {{ $p->recipeItems ? $p->recipeItems->count() : 0 }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg
                                           bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                                           hover:bg-indigo-100 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-300
                                           transition-all"
                                >
                                    <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                                    Actions
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        {{-- Pagination --}}
        @if($isPaginated)
            <div class="mt-4">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif

    @endcannot
</div>

{{-- Product Actions Modal --}}
<div
    x-data
    x-show="$store.productActions.show"
    x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 backdrop-blur-sm"
    @keydown.escape.window="$store.productActions.close()"
>
    <div
        @click.outside="$store.productActions.close()"
        x-show="$store.productActions.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-95"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
               rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md mx-0 sm:mx-4
               max-h-[85vh] overflow-y-auto"
    >
        {{-- Header --}}
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 px-5 py-4 flex items-center justify-between rounded-t-2xl">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="$store.productActions.name"></h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Current stock:</span>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full"
                          :class="$store.productActions.stock <= 0
                              ? 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300'
                              : ($store.productActions.stock <= 5
                                  ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
                                  : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300')"
                          x-text="$store.productActions.stock"></span>
                </div>
            </div>
            <button @click="$store.productActions.close()" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <i data-lucide="x" class="w-5 h-5 text-gray-400"></i>
            </button>
        </div>

        {{-- Action Cards --}}
        <div class="p-4 grid grid-cols-2 gap-3">
            @can('products.view')
                <a :href="'/products/' + $store.productActions.id"
                   class="group flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700
                          hover:border-indigo-300 dark:hover:border-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all">
                    <div class="p-2.5 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800/40 transition">
                        <i data-lucide="eye" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">View Details</span>
                </a>
            @endcan

            @can('products.edit')
                <a :href="'/products/' + $store.productActions.id + '/edit'"
                   class="group flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700
                          hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all">
                    <div class="p-2.5 rounded-xl bg-blue-100 dark:bg-blue-900/30 group-hover:bg-blue-200 dark:group-hover:bg-blue-800/40 transition">
                        <i data-lucide="edit-3" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Edit Product</span>
                </a>
            @endcan

            @can('products.view')
                <a :href="'/products/' + $store.productActions.id + '/recipe'"
                   class="group flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700
                          hover:border-violet-300 dark:hover:border-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-all relative">
                    <div class="p-2.5 rounded-xl bg-violet-100 dark:bg-violet-900/30 group-hover:bg-violet-200 dark:group-hover:bg-violet-800/40 transition">
                        <i data-lucide="chef-hat" class="w-5 h-5 text-violet-600 dark:text-violet-400"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Recipe</span>
                    <span class="absolute top-2 right-2 px-1.5 py-0.5 text-[10px] font-semibold rounded-full"
                          :class="$store.productActions.recipeCount > 0
                              ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300'
                              : 'bg-gray-100 dark:bg-gray-700 text-gray-500'"
                          x-text="$store.productActions.recipeCount > 0 ? $store.productActions.recipeCount : 'None'"></span>
                </a>
            @endcan

            @can('products.edit')
                <button type="button"
                        @click="$store.productActions.close(); $store.quickAdjust.open($store.productActions.id, $store.productActions.name, $store.productActions.stock)"
                        class="group flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700
                               hover:border-amber-300 dark:hover:border-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-all">
                    <div class="p-2.5 rounded-xl bg-amber-100 dark:bg-amber-900/30 group-hover:bg-amber-200 dark:group-hover:bg-amber-800/40 transition">
                        <i data-lucide="plus-minus" class="w-5 h-5 text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Adjust Stock</span>
                </button>
            @endcan

            @can('stock.view')
                <a :href="'/stock/history?product_id=' + $store.productActions.id"
                   class="group flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700
                          hover:border-emerald-300 dark:hover:border-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all">
                    <div class="p-2.5 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-800/40 transition">
                        <i data-lucide="history" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Stock History</span>
                </a>
            @endcan

            @can('products.delete')
                <button type="button"
                        @click="$store.productActions.close(); $store.productActions.confirmDelete()"
                        class="group flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700
                               hover:border-rose-300 dark:hover:border-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all">
                    <div class="p-2.5 rounded-xl bg-rose-100 dark:bg-rose-900/30 group-hover:bg-rose-200 dark:group-hover:bg-rose-800/40 transition">
                        <i data-lucide="trash-2" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                    </div>
                    <span class="text-sm font-medium text-rose-600 dark:text-rose-400">Delete</span>
                </button>
            @endcan
        </div>

        {{-- Mobile drag handle --}}
        <div class="sm:hidden pb-3 flex justify-center">
            <div class="w-10 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></div>
        </div>
    </div>
</div>

{{-- Hidden delete form for product actions modal --}}
<form id="product-action-delete-form" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

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

{{-- Bulk Delete Confirmation Modal --}}
@can('products.delete')
<div
    x-data
    x-show="$store.bulkConfirm.open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    @keydown.escape.window="$store.bulkConfirm.open = false"
>
    <div
        @click.outside="$store.bulkConfirm.open = false"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md"
    >
        <div class="flex items-center gap-2 mb-2">
            <div class="p-2 rounded-full bg-rose-100 dark:bg-rose-900/30">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Bulk Delete Products</h2>
        </div>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-1">
            Are you sure you want to delete the selected products?
        </p>
        <p class="text-rose-600 dark:text-rose-400 text-xs mb-6">
            This will also remove their stock movements, recipes, and production references. This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.bulkConfirm.open = false">Cancel</button>
            <button type="button" class="btn btn-danger flex items-center gap-1"
                    @click="$store.bulkConfirm.open = false; document.getElementById('bulk-delete-form').submit()">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                Delete All
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

    // Bulk selection component
    function bulkSelect() {
        return {
            selectedIds: [],
            allSelected: false,
            pageIds: [{{ $products->pluck('id')->join(',') }}],

            toggleAll() {
                this.selectedIds = this.allSelected ? [...this.pageIds] : [];
            },

            updateAllSelected() {
                this.allSelected = this.pageIds.length > 0 && this.pageIds.every(id => this.selectedIds.includes(id));
            },
        };
    }

    document.addEventListener('alpine:init', () => {
        // Product Actions Modal store
        Alpine.store('productActions', {
            show: false,
            id: null,
            name: '',
            stock: 0,
            recipeCount: 0,

            open(id, name, stock, recipeCount) {
                this.id = id;
                this.name = name;
                this.stock = stock;
                this.recipeCount = recipeCount;
                this.show = true;
            },

            close() {
                this.show = false;
            },

            confirmDelete() {
                const form = document.getElementById('product-action-delete-form');
                form.action = '/products/' + this.id;
                Alpine.store('confirm').openWith(form);
            },
        });

        // Bulk delete confirm store
        Alpine.store('bulkConfirm', { open: false });

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