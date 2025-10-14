@extends('layouts.app')
@section('title', 'Stock Movements')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- 🔹 Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">Stock Movements</h1>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('stock.history.export.csv', request()->query()) }}"
               class="btn btn-success text-sm">
               Export CSV
            </a>
            <a href="{{ route('stock.history.export.pdf', request()->query()) }}"
               target="_blank"
               class="btn btn-primary text-sm">
               Export PDF
            </a>
        </div>
    </div>

    {{-- 🔹 Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card title="Total In" value="{{ number_format($totals['in'], 2) }}" color="green" />
        <x-stat-card title="Total Out" value="{{ number_format($totals['out'], 2) }}" color="red" />
        <x-stat-card title="Net Movement" value="{{ number_format($totals['net'], 2) }}" color="blue" />
    </div>

    {{-- 🔹 Filters --}}
    <form method="GET" action="{{ route('stock.history') }}" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
            <select name="product_id" class="form-select w-full">
                <option value="">All Products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select name="type" class="form-select w-full">
                <option value="">All</option>
                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>In</option>
                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Out</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-input w-full">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-input w-full">
        </div>

        <div class="md:col-span-5 flex justify-end mt-2">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    {{-- 🔹 Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase">Unit Cost</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase">Total Cost</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Recorded By</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Source</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($movements as $m)
                    <tr class="hover:bg-gray-50 transition-all">
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->product->name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $m->type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ strtoupper($m->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($m->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($m->unit_cost ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-800 font-medium">{{ number_format($m->total_cost ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $m->user->name ?? 'System' }}</td>
                        <td class="px-4 py-3">
                            @if($m->source_type === App\Models\Purchase::class)
                                <a href="{{ route('purchases.show', $m->source_id) }}" class="text-indigo-600 hover:underline">
                                    Purchase #{{ $m->source_id }}
                                </a>
                            @elseif($m->source_type === App\Models\Sale::class)
                                <a href="{{ route('sales.show', $m->source_id) }}" class="text-green-600 hover:underline">
                                    Sale #{{ $m->source_id }}
                                </a>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500 text-sm">
                            No movements found for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 🔹 Pagination --}}
    <div class="mt-4">
        {{ $movements->withQueryString()->links() }}
    </div>
</div>
@endsection
