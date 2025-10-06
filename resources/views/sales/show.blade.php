@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Sale #{{ $sale->id }}</h2>
        <div class="flex gap-2">
            <a href="{{ route('sales.invoice', $sale->id) }}" target="_blank"
               class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
               Download Invoice
            </a>
            <a href="{{ route('sales.edit', $sale->id) }}"
               class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
               Edit
            </a>
            <a href="{{ route('sales.index') }}"
               class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-sm">
               Back
            </a>
        </div>
    </div>

    {{-- Sale Info --}}
    <div class="bg-white p-6 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p><span class="font-semibold text-gray-700">Customer:</span> {{ $sale->customer->name ?? 'Walk-in' }}</p>
            <p><span class="font-semibold text-gray-700">Date:</span> {{ $sale->sale_date }}</p>
            <p><span class="font-semibold text-gray-700">Status:</span>
                <span class="px-2 py-1 rounded text-sm
                    {{ $sale->status == 'completed' ? 'bg-green-100 text-green-700' :
                       ($sale->status == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    {{ ucfirst($sale->status) }}
                </span>
            </p>
        </div>
        <div>
            <p><span class="font-semibold text-gray-700">Total Amount:</span> {{ number_format($sale->total_amount, 2) }}</p>
            <p><span class="font-semibold text-gray-700">Amount Paid:</span> {{ number_format($sale->amount_paid ?? 0, 2) }}</p>
            <p><span class="font-semibold text-gray-700">Balance Due:</span>
                <span class="font-semibold text-{{ $sale->balance > 0 ? 'red' : 'green' }}-600">
                    {{ number_format($sale->balance, 2) }}
                </span>
            </p>
        </div>
    </div>

    {{-- Sale Items --}}
    <h4 class="text-lg font-semibold mb-2">Sold Items</h4>
    <div class="overflow-x-auto bg-white shadow rounded-lg mb-6">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">Product</th>
                    <th class="px-4 py-2 border text-center">Quantity</th>
                    <th class="px-4 py-2 border text-center">Unit Price</th>
                    <th class="px-4 py-2 border text-center">Subtotal</th>
                    <th class="px-4 py-2 border text-center">Profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border">{{ $item->product->name }}</td>
                    <td class="px-4 py-2 border text-center">{{ $item->quantity }}</td>
                    <td class="px-4 py-2 border text-center">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-4 py-2 border text-center">{{ number_format($item->subtotal, 2) }}</td>
                    <td class="px-4 py-2 border text-center text-green-700">
                        {{ number_format($item->profit ?? 0, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Transaction Info --}}
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h4 class="text-lg font-semibold mb-3">Transaction Info</h4>
        @if($sale->transaction)
            <p><span class="font-semibold text-gray-700">Transaction Type:</span> {{ ucfirst($sale->transaction->type) }}</p>
            <p><span class="font-semibold text-gray-700">Payment Method:</span> {{ ucfirst($sale->transaction->method) }}</p>
            <p><span class="font-semibold text-gray-700">Recorded By:</span> {{ $sale->transaction->user->name ?? 'N/A' }}</p>
            <p><span class="font-semibold text-gray-700">Date:</span> {{ $sale->transaction->transaction_date }}</p>
        @else
            <p class="text-gray-500 italic">No transaction recorded for this sale.</p>
        @endif
    </div>

    {{-- Notes --}}
    @if($sale->notes)
    <div class="bg-white p-6 rounded-lg shadow">
        <h4 class="text-lg font-semibold mb-2">Notes</h4>
        <p class="text-gray-700 whitespace-pre-line">{{ $sale->notes }}</p>
    </div>
    @endif
</div>
@endsection
