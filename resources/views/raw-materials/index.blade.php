@extends('layouts.app')
@section('title', 'Raw Materials')

@section('content')
@php
    use App\Models\Category;
    use Illuminate\Support\Str;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $threshold = (int)($threshold ?? 5);

    $allCategories    = $categories ?? Category::where('kind','raw_material')->orderBy('name')->get();
    $usableCategories = collect($allCategories)
        ->filter(fn($c) => ($c->is_active ?? true) && ($c->kind ?? '') === 'raw_material')
        ->values();

    $__coll = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products);

    $isPaginated = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $pageCount   = $isPaginated ? $products->count() : $__coll->count();
    $totalCount  = $isPaginated ? $products->total() : $pageCount;

    $pageUnits   = $__coll->sum(fn($p) => max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0)));
    $pageValue   = $__coll->sum(function($p){
        $units = max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0));
        return $units * (float)($p->cost_price ?? 0);
    });
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="flex items-center gap-2 text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
            <i data-lucide="flask-conical" class="w-5 h-5 text-teal-600 dark:text-teal-400"></i>
            <span>Raw Materials</span>
        </h1>

        <div class="flex flex-wrap gap-2 justify-start md:justify-end">
            @can('products.create')
                <a href="{{ route('raw-materials.create') }}"
                   class="btn btn-primary flex items-center gap-1 text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add Raw Material</span>
                </a>
            @endcan

            @can('stock.view')
                <a href="{{ route('stock.history') }}"
                   class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="history" class="w-4 h-4"></i>
                    <span>Stock Movements</span>
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
            action="{{ route('raw-materials.index') }}"
            x-data="{ qcat: '' }"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm space-y-3"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                {{-- Search --}}
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Search</label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                        <input
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Name…"
                            class="form-input w-full pl-8 text-sm">
                    </div>
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Category</label>
                    <select name="category_id" class="form-select w-full text-sm">
                        <option value="">All categories</option>
                        @foreach($usableCategories as $c)
                            <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>
                                {{ $c->name }}
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
                <a href="{{ route('raw-materials.index') }}"
                   class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-stat-card title="Raw Materials (page)" value="{{ $fmt0($pageCount) }}" color="teal" />
            <x-stat-card title="Units in stock (page)" value="{{ $fmt0($pageUnits) }}" color="blue" />
            <x-stat-card title="Stock value @ cost (page)" value="RWF {{ $fmt2($pageValue) }}" color="emerald" />
        </div>

        @if($isPaginated && $totalCount !== $pageCount)
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Showing {{ $pageCount }} of {{ $fmt0($totalCount) }} matching raw materials.
            </div>
        @endif

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-full w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-right">Cost (WAC)</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">In</th>
                        <th class="px-4 py-3 text-right">Out</th>
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
                            $stk  = max(0, $in - $out);
                            $low  = $stk <= $threshold && $stk > 0;
                            $zero = $stk <= 0;

                            $cost  = (float)($p->cost_price ?? 0);
                            $price = (float)($p->price ?? 0);
                            $last  = $p->last_moved_at ? \Carbon\Carbon::parse($p->last_moved_at)->diffForHumans() : '—';

                            $cat = $p->category ?? null;
                            $dot = $cat->color ?? '#6b7280';
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition" data-product-id="{{ $p->id }}">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">
                                @can('products.view')
                                    <a href="{{ route('raw-materials.show', $p) }}"
                                       class="hover:text-teal-600 dark:hover:text-teal-400">
                                        {{ $p->name }}
                                    </a>
                                @else
                                    {{ $p->name }}
                                @endcan
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @if($cat)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: {{ $dot }}"></span>
                                        <span>{{ $cat->name }}</span>
                                    </span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-300">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                RWF {{ $fmt2($cost) }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                RWF {{ $fmt2($price) }}
                            </td>

                            <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-300 font-semibold">
                                {{ $fmt0($in) }}
                            </td>

                            <td class="px-4 py-3 text-right text-rose-700 dark:text-rose-300 font-semibold">
                                {{ $fmt0($out) }}
                            </td>

                            <td class="px-4 py-3 text-right font-semibold
                                {{ $zero ? 'text-rose-600 dark:text-rose-300'
                                         : ($low ? 'text-amber-700 dark:text-amber-300'
                                                 : 'text-gray-900 dark:text-gray-100') }}">
                                <span data-stock>{{ $fmt0($stk) }}</span>
                                @if($zero)
                                    <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">Out</span>
                                @elseif($low)
                                    <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">Low</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $last }}</td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div x-data="{ open: false }" class="relative inline-block text-left">
                                    <button @click="open = !open" @click.outside="open = false"
                                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition">
                                        <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                    </button>

                                    <div x-show="open" x-cloak
                                        x-transition
                                        class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 dark:divide-gray-700">
                                        <div class="py-1">
                                            @can('products.view')
                                                <a href="{{ route('raw-materials.show', $p) }}" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    <i data-lucide="eye" class="mr-3 h-4 w-4 text-gray-400 group-hover:text-teal-600 dark:group-hover:text-teal-400"></i>
                                                    View Details
                                                </a>
                                            @endcan
                                            @can('products.edit')
                                                <a href="{{ route('raw-materials.edit', $p) }}" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    <i data-lucide="edit-3" class="mr-3 h-4 w-4 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400"></i>
                                                    Edit
                                                </a>
                                            @endcan
                                        </div>

                                        @can('products.delete')
                                            <div class="py-1">
                                                <button type="button"
                                                    @click="open = false; $dispatch('open-delete-raw-material-modal', {
                                                        url: '{{ route('raw-materials.destroy', $p) }}',
                                                        name: '{{ addslashes($p->name) }}'
                                                    })"
                                                    class="group flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition">
                                                    <i data-lucide="trash-2" class="mr-3 h-4 w-4 text-gray-400 group-hover:text-rose-600"></i>
                                                    <span class="group-hover:text-rose-600">Delete</span>
                                                </button>
                                            </div>
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No raw materials found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($isPaginated)
            <div class="mt-4">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif

    @endcannot
</div>

{{-- Delete Confirmation Modal --}}
@can('products.delete')
<div x-data="{
        open: false,
        deleteUrl: '',
        rawMaterialName: '',
        openModal(detail) {
            this.deleteUrl = detail.url || '';
            this.rawMaterialName = detail.name || 'this raw material';
            this.open = true;
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },
        closeModal() {
            this.open = false;
            this.deleteUrl = '';
            this.rawMaterialName = '';
        }
    }"
    @open-delete-raw-material-modal.window="openModal($event.detail)"
    @keydown.escape.window="closeModal()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div @click.outside="closeModal()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">

        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl bg-rose-100 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 shadow-sm">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
            </div>
            <div class="space-y-1.5 flex-1">
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                    Delete Raw Material
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                    Are you sure you want to delete <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="rawMaterialName"></span>? This raw material will be removed from your catalog.
                </p>
                <p class="text-xs text-rose-600 dark:text-rose-400">
                    This action cannot be undone.
                </p>
            </div>
        </div>

        <form :action="deleteUrl" method="POST" class="mt-6 flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/80">
            @csrf
            @method('DELETE')
            <button type="button"
                    @click="closeModal()"
                    class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-sm font-medium transition shadow-sm">
                Cancel
            </button>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white text-sm font-semibold transition shadow-sm">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>Delete</span>
            </button>
        </form>
    </div>
</div>
@endcan

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });
</script>
@endpush
@endsection
