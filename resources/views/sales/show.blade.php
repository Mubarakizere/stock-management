@extends('layouts.app')
@section('title', "Sale #{$sale->id}")

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">
            Sale #{{ $sale->id }}
        </h1>
        <div class="flex gap-2">
            <a href="{{ route('sales.invoice', $sale) }}" target="_blank"
               class="btn btn-primary text-sm">Download Invoice</a>
            <a href="{{ route('sales.edit', $sale) }}"
               class="btn btn-success text-sm">Edit</a>
            <a href="{{ route('sales.index') }}"
               class="btn btn-secondary text-sm">Back</a>
        </div>
    </div>

    {{-- Sale Summary --}}
    <div class="bg-white shadow rounded-lg p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p><span class="font-semibold text-gray-700">Customer:</span> {{ $sale->customer->name ?? 'Walk-in' }}</p>
            <p><span class="font-semibold text-gray-700">Date:</span> {{ optional($sale->sale_date)->format('Y-m-d') }}</p>
            <p><span class="font-semibold text-gray-700">Status:</span>
                <span class="px-2 py-1 rounded text-xs font-medium
                    {{ $sale->status === 'completed' ? 'bg-green-100 text-green-800' :
                       ($sale->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                    {{ ucfirst($sale->status) }}
                </span>
            </p>
        </div>
        <div>
            <p><span class="font-semibold text-gray-700">Total:</span> {{ number_format($sale->total_amount, 2) }}</p>
            <p><span class="font-semibold text-gray-700">Paid:</span> {{ number_format($sale->amount_paid ?? 0, 2) }}</p>
            <p><span class="font-semibold text-gray-700">Balance:</span>
                <span class="font-semibold {{ $sale->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($sale->balance, 2) }}
                </span>
            </p>
        </div>
    </div>

    {{-- Sold Items --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <h4 class="px-6 py-3 text-lg font-semibold border-b bg-gray-50">Sold Items</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Product</th>
                        <th class="px-4 py-2 text-center">Qty</th>
                        <th class="px-4 py-2 text-right">Unit Price</th>
                        <th class="px-4 py-2 text-right">Subtotal</th>
                        <th class="px-4 py-2 text-right">Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($sale->items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $item->product->name ?? 'Unknown Product' }}</td>
                            <td class="px-4 py-2 text-center">{{ $item->quantity }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($item->subtotal, 2) }}</td>
                            <td class="px-4 py-2 text-right text-green-700">{{ number_format($item->profit ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Transaction Info --}}
    <div class="bg-white shadow rounded-lg p-6">
        <h4 class="text-lg font-semibold mb-3">Transaction Details</h4>
        @if($sale->transaction)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <p><span class="font-semibold text-gray-700">Type:</span> {{ ucfirst($sale->transaction->type) }}</p>
                <p><span class="font-semibold text-gray-700">Payment Method:</span> {{ ucfirst($sale->transaction->method ?? '-') }}</p>
                <p><span class="font-semibold text-gray-700">Recorded By:</span> {{ $sale->transaction->user->name ?? 'N/A' }}</p>
                <p><span class="font-semibold text-gray-700">Date:</span>
                    {{ optional($sale->transaction->transaction_date)->format('Y-m-d H:i') ?? '-' }}
                </p>
            </div>
        @else
            <p class="text-gray-500 italic">No transaction recorded for this sale.</p>
        @endif
    </div>

    {{-- Notes --}}
    @if($sale->notes)
        <div class="bg-white shadow rounded-lg p-6">
            <h4 class="text-lg font-semibold mb-2">Notes</h4>
            <p class="text-gray-700 whitespace-pre-line">{{ $sale->notes }}</p>
        </div>
    @endif
</div>
@endsection
