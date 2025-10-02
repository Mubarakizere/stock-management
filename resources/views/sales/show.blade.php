@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">Sale #{{ $sale->id }}</h2>

    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <p><span class="font-semibold">Customer:</span> {{ $sale->customer->name ?? 'Walk-in' }}</p>
        <p><span class="font-semibold">Date:</span> {{ $sale->sale_date }}</p>
        <p><span class="font-semibold">Total:</span> {{ number_format($sale->total_amount, 2) }}</p>
    </div>

    <h4 class="text-lg font-semibold mb-2">Items</h4>
    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">Product</th>
                    <th class="px-4 py-2 border">Quantity</th>
                    <th class="px-4 py-2 border">Unit Price</th>
                    <th class="px-4 py-2 border">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border">{{ $item->product->name }}</td>
                    <td class="px-4 py-2 border">{{ $item->quantity }}</td>
                    <td class="px-4 py-2 border">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-4 py-2 border">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Back</a>
        <a href="{{ route('sales.edit', $sale->id) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Edit</a>
    </div>
</div>
@endsection
