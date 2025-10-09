@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Purchase #{{ $purchase->id }}</h2>
        <div class="flex gap-2">
            <a href="{{ route('purchases.invoice', $purchase->id) }}" target="_blank" class="btn-primary">Download PDF</a>
            <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn-warning">Edit</a>
            <a href="{{ route('purchases.index') }}" class="btn-secondary">Back</a>
        </div>
    </div>

    {{-- Info --}}
    <div class="bg-white p-6 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p><strong>Supplier:</strong> {{ $purchase->supplier->name }}</p>
            <p><strong>Date:</strong> {{ $purchase->purchase_date }}</p>
            <p><strong>Status:</strong>
                <span class="badge {{ $purchase->status == 'completed' ? 'badge-green' : ($purchase->status == 'pending' ? 'badge-yellow' : 'badge-red') }}">
                    {{ ucfirst($purchase->status ?? 'completed') }}
                </span>
            </p>
        </div>
        <div>
            <p><strong>Subtotal:</strong> {{ number_format($purchase->subtotal, 2) }}</p>
            <p><strong>Tax:</strong> {{ number_format($purchase->tax, 2) }}</p>
            <p><strong>Discount:</strong> {{ number_format($purchase->discount, 2) }}</p>
        </div>
    </div>

    {{-- Items --}}
    <h4 class="text-lg font-semibold mb-2">Purchased Items</h4>
    <div class="overflow-x-auto bg-white shadow rounded-lg mb-6">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">Product</th>
                    <th class="px-4 py-2 border text-center">Qty</th>
                    <th class="px-4 py-2 border text-center">Unit Cost</th>
                    <th class="px-4 py-2 border text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border">{{ $item->product->name }}</td>
                    <td class="px-4 py-2 border text-center">{{ $item->quantity }}</td>
                    <td class="px-4 py-2 border text-center">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="px-4 py-2 border text-center">{{ number_format($item->total_cost, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Summary --}}
    <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold">Total:</h3>
        <span class="text-2xl font-bold text-green-700">{{ number_format($purchase->total_amount, 2) }}</span>
    </div>

    {{-- Payment Info --}}
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h4 class="text-lg font-semibold mb-3">Payment</h4>
        <p><strong>Paid:</strong> {{ number_format($purchase->amount_paid, 2) }}</p>
        <p><strong>Balance:</strong> {{ number_format($purchase->balance_due, 2) }}</p>
        <p><strong>Method:</strong> {{ ucfirst($purchase->method ?? 'cash') }}</p>
        <p><strong>Transaction:</strong>
            @if($purchase->transaction)
                {{ ucfirst($purchase->transaction->type) }} • {{ $purchase->transaction->user->name ?? 'N/A' }}
            @else
                <em>No transaction recorded.</em>
            @endif
        </p>
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
