@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold">Stock History</h2>
    <div class="flex gap-2">
        {{-- Summary Bar --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-green-100 p-4 rounded-lg text-center shadow">
        <p class="text-sm text-green-700 font-semibold">Total In</p>
        <p class="text-2xl font-bold text-green-800">{{ number_format($totals['in'], 2) }}</p>
    </div>
    <div class="bg-red-100 p-4 rounded-lg text-center shadow">
        <p class="text-sm text-red-700 font-semibold">Total Out</p>
        <p class="text-2xl font-bold text-red-800">{{ number_format($totals['out'], 2) }}</p>
    </div>
    <div class="bg-blue-100 p-4 rounded-lg text-center shadow">
        <p class="text-sm text-blue-700 font-semibold">Net Movement</p>
        <p class="text-2xl font-bold text-blue-800">{{ number_format($totals['net'], 2) }}</p>
    </div>
</div>

        <a href="{{ route('stock.history.export.csv', request()->query()) }}"
           class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
           Export CSV
        </a>
        <a href="{{ route('stock.history.export.pdf', request()->query()) }}"
           target="_blank"
           class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
           Export PDF
        </a>
    </div>
</div>


    {{-- Filters --}}
    <form method="GET" action="{{ route('stock.history') }}" class="bg-white p-4 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Product</label>
            <select name="product_id" class="w-full border-gray-300 rounded-lg">
                <option value="">All Products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Type</label>
            <select name="type" class="w-full border-gray-300 rounded-lg">
                <option value="">All</option>
                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>In</option>
                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Out</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">From</label>
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">To</label>
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full border-gray-300 rounded-lg">
        </div>

        <div class="md:col-span-4 flex justify-end mt-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Filter</button>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full border-collapse text-sm text-left">
            <thead class="bg-gray-100">
    <tr>
        <th class="px-4 py-2 border">Date</th>
        <th class="px-4 py-2 border">Product</th>
        <th class="px-4 py-2 border">Type</th>
        <th class="px-4 py-2 border text-right">Quantity</th>
        <th class="px-4 py-2 border text-right">Unit Cost</th>
        <th class="px-4 py-2 border text-right">Total Cost</th>
        <th class="px-4 py-2 border">Recorded By</th>
        <th class="px-4 py-2 border">Source</th>
    </tr>
</thead>

<tbody>
@forelse($movements as $m)
<tr class="hover:bg-gray-50">
    <td class="px-4 py-2 border">{{ $m->created_at->format('Y-m-d H:i') }}</td>
    <td class="px-4 py-2 border">{{ $m->product->name }}</td>
    <td class="px-4 py-2 border">
        <span class="px-2 py-1 rounded text-xs font-medium {{ $m->type == 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
            {{ strtoupper($m->type) }}
        </span>
    </td>
    <td class="px-4 py-2 border text-right">{{ number_format($m->quantity, 2) }}</td>
    <td class="px-4 py-2 border text-right">{{ number_format($m->unit_cost ?? 0, 2) }}</td>
    <td class="px-4 py-2 border text-right">{{ number_format($m->total_cost ?? 0, 2) }}</td>
    <td class="px-4 py-2 border">{{ $m->user->name ?? 'System' }}</td>
    <td class="px-4 py-2 border">
        @if($m->source_type === App\Models\Purchase::class)
            <a href="{{ route('purchases.show', $m->source_id) }}" class="text-indigo-600 hover:underline">Purchase #{{ $m->source_id }}</a>
        @elseif($m->source_type === App\Models\Sale::class)
            <a href="{{ route('sales.show', $m->source_id) }}" class="text-green-600 hover:underline">Sale #{{ $m->source_id }}</a>
        @else
            <span class="text-gray-500">N/A</span>
        @endif
    </td>
</tr>
@empty
<tr>
    <td colspan="8" class="text-center py-4 text-gray-500">No movements found.</td>
</tr>
@endforelse
</tbody>

        </table>
    </div>

    <div class="mt-4">
        {{ $movements->withQueryString()->links() }}
    </div>
</div>
@endsection
