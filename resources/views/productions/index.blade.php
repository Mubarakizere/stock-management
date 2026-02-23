@extends('layouts.app')
@section('title', 'Production History')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <i data-lucide="factory" class="w-6 h-6 text-violet-600 dark:text-violet-400"></i>
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">Production</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage production runs & raw material consumption</p>
            </div>
        </div>
        @can('products.edit')
            <a href="{{ route('productions.create') }}" class="btn btn-primary flex items-center gap-1 text-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                New Production
            </a>
        @endcan
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('productions.index') }}" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ request('q') }}"
               placeholder="Search product or notes…"
               class="form-input text-sm w-48">
        <select name="product_id" class="form-select text-sm w-48">
            <option value="">All Products</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline btn-sm text-sm">Filter</button>
        @if(request()->hasAny(['q', 'product_id']))
            <a href="{{ route('productions.index') }}" class="btn btn-outline btn-sm text-sm text-gray-500">Clear</a>
        @endif
    </form>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 p-3 text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-700 p-3 text-sm text-rose-700 dark:text-rose-300 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Product</th>
                        <th class="px-4 py-3 text-right">Qty Produced</th>
                        <th class="px-4 py-3 text-right">Materials Used</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Recorded By</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($productions as $prod)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                            <td class="px-4 py-3 text-gray-500">#{{ $prod->id }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                {{ $prod->product->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-violet-700 dark:text-violet-300">
                                {{ number_format($prod->quantity, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                {{ $prod->materials->count() }} items
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $prod->produced_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $prod->user->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('productions.show', $prod) }}"
                                       class="p-1.5 rounded-lg text-gray-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition"
                                       title="View Details">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    @can('products.edit')
                                        <button type="button"
                                                @click="$store.reverseConfirm.open({{ $prod->id }}, '{{ addslashes($prod->product->name ?? '—') }}', {{ $prod->quantity }}, {{ $prod->materials->count() }})"
                                                class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition"
                                                title="Reverse & Delete">
                                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i data-lucide="factory" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                <p>No production runs yet.</p>
                                @can('products.edit')
                                    <a href="{{ route('productions.create') }}" class="text-violet-600 hover:underline text-sm mt-1 inline-block">
                                        Record your first production →
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($productions->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700">
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
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 backdrop-blur-sm"
    @keydown.escape.window="$store.reverseConfirm.close()"
>
    <div
        @click.outside="$store.reverseConfirm.close()"
        x-show="$store.reverseConfirm.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 sm:scale-95"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
               rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md mx-0 sm:mx-4 p-6"
    >
        <div class="flex items-start gap-3 mb-4">
            <div class="p-2 rounded-full bg-rose-100 dark:bg-rose-900/30 shrink-0">
                <i data-lucide="rotate-ccw" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Reverse Production</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" x-text="$store.reverseConfirm.productName"></p>
            </div>
        </div>

        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-3 mb-4 space-y-1.5">
            <p class="text-sm text-amber-800 dark:text-amber-300 font-medium">This action will:</p>
            <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1 ml-1">
                <li class="flex items-start gap-1.5">
                    <i data-lucide="minus-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i>
                    <span>Remove <strong x-text="$store.reverseConfirm.quantity"></strong> unit(s) of <strong x-text="$store.reverseConfirm.productName"></strong> from stock</span>
                </li>
                <li class="flex items-start gap-1.5">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i>
                    <span>Return <strong x-text="$store.reverseConfirm.materialsCount"></strong> raw material(s) back to stock</span>
                </li>
                <li class="flex items-start gap-1.5">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i>
                    <span>Delete this production record permanently</span>
                </li>
            </ul>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">
            This cannot be undone. Make sure you want to reverse this production run.
        </p>

        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline text-sm" @click="$store.reverseConfirm.close()">Cancel</button>
            <button type="button"
                    class="btn btn-danger text-sm flex items-center gap-1"
                    @click="$store.reverseConfirm.submit()">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Yes, Reverse
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
            quantity: 0,
            materialsCount: 0,

            open(id, name, qty, materials) {
                this.productionId = id;
                this.productName = name;
                this.quantity = qty;
                this.materialsCount = materials;
                this.show = true;
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
