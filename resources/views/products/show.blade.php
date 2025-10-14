@extends('layouts.app')
@section('title', $product->name . ' Details')

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-6">

    {{-- 🔹 Header --}}
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-800">
            {{ $product->name }}
        </h1>
        <div class="flex gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-secondary">← Back</a>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">Edit</a>
        </div>
    </div>

    {{-- 🔹 Product Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <x-stat-card title="Category" value="{{ $product->category->name ?? '—' }}" color="gray" />
        <x-stat-card title="Price" value="RWF {{ number_format($product->price, 0) }}" color="indigo" />
        <x-stat-card title="Current Stock" value="{{ number_format($current, 0) }}" color="{{ $current <= 5 ? 'red' : 'green' }}" />
    </div>

    {{-- 🔹 Stock Totals --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card title="Total In" value="{{ number_format($totalIn, 0) }}" color="green" />
        <x-stat-card title="Total Out" value="{{ number_format($totalOut, 0) }}" color="red" />
        <x-stat-card title="Stock Value" value="RWF {{ number_format($product->stockValue(), 0) }}" color="blue" />
    </div>

    {{-- 🔹 Recent Movements --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto mt-4">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Recent Stock Movements</h2>
            <a href="{{ route('stock.history', ['product_id' => $product->id]) }}"
               class="text-indigo-600 text-sm hover:underline">
               View All →
            </a>
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Cost</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recorded By</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($product->stockMovements as $move)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-700">{{ $move->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $move->type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ strtoupper($move->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-800">{{ number_format($move->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($move->unit_cost ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">{{ number_format($move->total_cost ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $move->user->name ?? 'System' }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            @if($move->source_type === App\Models\Purchase::class)
                                <a href="{{ route('purchases.show', $move->source_id) }}" class="text-indigo-600 hover:underline">
                                    Purchase #{{ $move->source_id }}
                                </a>
                            @elseif($move->source_type === App\Models\Sale::class)
                                <a href="{{ route('sales.show', $move->source_id) }}" class="text-green-600 hover:underline">
                                    Sale #{{ $move->source_id }}
                                </a>
                            @else
                                <span class="text-gray-400">Manual</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500 text-sm">
                            No recent stock movements found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
