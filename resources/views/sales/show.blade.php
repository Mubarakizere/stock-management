@extends('layouts.app')
@section('title', "Sale #{$sale->id}")

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-8">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">
            Sale #{{ $sale->id }}
        </h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sales.invoice', $sale) }}" target="_blank" class="btn btn-primary text-sm">
                Download Invoice
            </a>
            <a href="{{ route('sales.edit', $sale) }}" class="btn btn-outline text-sm">Edit</a>
            <a href="{{ route('sales.index') }}" class="btn btn-secondary text-sm">Back</a>
        </div>
    </div>

    {{-- Sale Summary --}}
    @php
        $balance = round(($sale->total_amount ?? 0) - ($sale->amount_paid ?? 0), 2);
        $progress = $sale->total_amount > 0
            ? round(($sale->amount_paid / $sale->total_amount) * 100)
            : 0;
        $date = \Carbon\Carbon::parse($sale->sale_date);
    @endphp

    <div class="bg-white shadow rounded-xl p-6 grid md:grid-cols-2 gap-6">
        <div class="space-y-2">
            <p><strong class="text-gray-600">Customer:</strong> {{ $sale->customer->name ?? 'Walk-in' }}</p>
            <p><strong class="text-gray-600">Date:</strong> {{ $date->format('Y-m-d') }}</p>
            <p><strong class="text-gray-600">Status:</strong>
                @if ($sale->status === 'completed')
                    <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Completed</span>
                @elseif ($sale->status === 'pending')
                    <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">Pending</span>
                @else
                    <span class="px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">Cancelled</span>
                @endif
            </p>
        </div>

        <div class="space-y-2">
            <p><strong class="text-gray-600">Total:</strong> {{ number_format($sale->total_amount, 2) }}</p>
            <p><strong class="text-gray-600">Paid:</strong> {{ number_format($sale->amount_paid ?? 0, 2) }}</p>
            <p><strong class="text-gray-600">Balance:</strong>
                <span class="font-semibold {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($balance, 2) }}
                </span>
            </p>
        </div>
    </div>

    {{-- Payment Progress --}}
    <div class="bg-gray-50 p-4 rounded-lg">
        <div class="flex justify-between items-center mb-1">
            <span class="text-sm text-gray-600 font-medium">Payment Progress</span>
            <span class="text-sm text-gray-700 font-semibold">{{ $progress }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-500 h-2 rounded-full transition-all duration-500"
                 style="width: {{ $progress }}%"></div>
        </div>
    </div>

    {{-- Loan Info --}}
    @if($sale->loan)
        <div class="bg-white shadow rounded-xl p-6 border-l-4
            {{ $sale->loan->status === 'paid' ? 'border-green-500' : 'border-yellow-500' }}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-lg font-semibold text-gray-800">Linked Loan</h4>
                <span class="px-2 py-1 rounded-full text-xs font-medium
                    {{ $sale->loan->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ ucfirst($sale->loan->status) }}
                </span>
            </div>

            <div class="grid md:grid-cols-2 gap-2 text-sm">
                <p><strong>Loan Amount:</strong> {{ number_format($sale->loan->amount, 2) }}</p>
                <p><strong>Type:</strong> {{ ucfirst($sale->loan->type) }}</p>
                <p><strong>Loan Date:</strong> {{ \Carbon\Carbon::parse($sale->loan->loan_date)->format('Y-m-d') }}</p>
                @if($sale->loan->due_date)
                    <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($sale->loan->due_date)->format('Y-m-d') }}</p>
                @endif
            </div>

            @if($sale->loan->notes)
                <p class="mt-3 text-gray-600 text-sm italic">{{ $sale->loan->notes }}</p>
            @endif

            @if($sale->loan->status === 'pending')
                <div class="mt-4">
                    <a href="{{ route('loan-payments.create', $sale->loan) }}" class="btn btn-success text-xs">
                        + Add Payment
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- Sold Items --}}
    <div class="bg-white shadow rounded-xl overflow-hidden">
        <h4 class="px-6 py-3 text-lg font-semibold border-b bg-gray-50">Sold Items</h4>
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
                        <td class="px-4 py-2 text-gray-700">{{ $item->product->name ?? 'Unknown Product' }}</td>
                        <td class="px-4 py-2 text-center">{{ $item->quantity }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($item->subtotal, 2) }}</td>
                        <td class="px-4 py-2 text-right text-green-700">{{ number_format($item->profit ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Transaction Info --}}
    <div class="bg-white shadow rounded-xl p-6">
        <h4 class="text-lg font-semibold mb-3">Transaction Details</h4>
        @if($sale->transaction)
            <div class="grid md:grid-cols-2 gap-3 text-sm">
                <p><strong class="text-gray-600">Type:</strong> {{ ucfirst($sale->transaction->type) }}</p>
                <p><strong class="text-gray-600">Method:</strong> {{ ucfirst($sale->transaction->method ?? '-') }}</p>
                <p><strong class="text-gray-600">Recorded By:</strong> {{ $sale->transaction->user->name ?? 'N/A' }}</p>
                <p><strong class="text-gray-600">Date:</strong>
                    {{ optional($sale->transaction->transaction_date)->format('Y-m-d H:i') ?? '-' }}
                </p>
            </div>
        @else
            <p class="text-gray-500 italic">No transaction recorded for this sale.</p>
        @endif
    </div>

    {{-- Notes --}}
    @if($sale->notes)
        <div class="bg-white shadow rounded-xl p-6">
            <h4 class="text-lg font-semibold mb-2">Notes</h4>
            <p class="text-gray-700 whitespace-pre-line">{{ $sale->notes }}</p>
        </div>
    @endif
</div>
@endsection
