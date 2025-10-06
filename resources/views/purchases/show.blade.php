@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4">
    {{-- Header with Actions --}}
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Purchase #{{ $purchase->id }}</h2>
        <div class="flex gap-2">
            {{-- 🧾 Download PDF --}}
            <a href="{{ route('purchases.invoice', $purchase->id) }}"
               target="_blank"
               class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
               Download PDF
            </a>

            {{-- ✏️ Edit --}}
            <a href="{{ route('purchases.edit', $purchase->id) }}"
               class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
               Edit
            </a>

            {{-- 🔙 Back --}}
            <a href="{{ route('purchases.index') }}"
               class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-sm">
               Back
            </a>
        </div>
    </div>

    {{-- Purchase Info --}}
    <div class="bg-white p-6 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-gray-700"><span class="font-semibold">Supplier:</span> {{ $purchase->supplier->name }}</p>
            <p class="text-gray-700"><span class="font-semibold">Date:</span> {{ $purchase->purchase_date }}</p>
            <p class="text-gray-700"><span class="font-semibold">Status:</span>
                <span class="px-2 py-1 rounded text-sm
                    {{ $purchase->status == 'completed' ? 'bg-green-100 text-green-700' :
                       ($purchase->status == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    {{ ucfirst($purchase->status ?? 'completed') }}
                </span>
            </p>
        </div>
        <div>
            <p class="text-gray-700"><span class="font-semibold">Subtotal:</span> {{ number_format($purchase->subtotal ?? 0, 2) }}</p>
            <p class="text-gray-700"><span class="font-semibold">Tax:</span> {{ number_format($purchase->tax ?? 0, 2) }}</p>
            <p class="text-gray-700"><span class="font-semibold">Discount:</span> {{ number_format($purchase->discount ?? 0, 2) }}</p>
        </div>
    </div>

    {{-- Purchase Items --}}
    <h4 class="text-lg font-semibold mb-2">Purchased Items</h4>
    <div class="overflow-x-auto bg-white shadow rounded-lg mb-6">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">Product</th>
                    <th class="px-4 py-2 border text-center">Quantity</th>
                    <th class="px-4 py-2 border text-center">Unit Cost</th>
                    <th class="px-4 py-2 border text-center">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border">{{ $item->product->name }}</td>
                    <td class="px-4 py-2 border text-center">{{ $item->quantity }}</td>
                    <td class="px-4 py-2 border text-center">{{ number_format($item->cost_price, 2) }}</td>
                    <td class="px-4 py-2 border text-center">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Summary Totals --}}
    <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold">Total Purchase Amount:</h3>
        <span class="text-2xl font-bold text-green-700">{{ number_format($purchase->total_amount, 2) }}</span>
    </div>

    {{-- Payment & Transaction Info --}}
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h4 class="text-lg font-semibold mb-3">Payment Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-gray-700"><span class="font-semibold">Amount Paid:</span> {{ number_format($purchase->amount_paid ?? 0, 2) }}</p>
                <p class="text-gray-700"><span class="font-semibold">Balance Due:</span> {{ number_format(($purchase->total_amount ?? 0) - ($purchase->amount_paid ?? 0), 2) }}</p>
                <p class="text-gray-700"><span class="font-semibold">Payment Method:</span> {{ ucfirst($purchase->method ?? 'cash') }}</p>
            </div>
            <div>
                @if($purchase->transaction)
                    <p class="text-gray-700"><span class="font-semibold">Transaction Type:</span> {{ ucfirst($purchase->transaction->type) }}</p>
                    <p class="text-gray-700"><span class="font-semibold">Recorded By:</span> {{ $purchase->transaction->user->name ?? 'N/A' }}</p>
                    <p class="text-gray-700"><span class="font-semibold">Transaction Date:</span> {{ $purchase->transaction->transaction_date }}</p>
                @else
                    <p class="text-gray-500 italic">No transaction recorded for this purchase.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($purchase->notes)
    <div class="bg-white p-6 rounded-lg shadow">
        <h4 class="text-lg font-semibold mb-2">Notes</h4>
        <p class="text-gray-700 whitespace-pre-line">{{ $purchase->notes }}</p>
    </div>
    @endif
</div>
@endsection
