@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">Transaction #{{ $transaction->id }}</h2>

    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <p><strong>Type:</strong> {{ ucfirst($transaction->type) }}</p>
        <p><strong>Party:</strong>
            @if($transaction->customer) Customer: {{ $transaction->customer->name }}
            @elseif($transaction->supplier) Supplier: {{ $transaction->supplier->name }}
            @else -
            @endif
        </p>
        <p><strong>Linked:</strong>
            @if($transaction->sale) Sale #{{ $transaction->sale->id }}
            @elseif($transaction->purchase) Purchase #{{ $transaction->purchase->id }}
            @else -
            @endif
        </p>
        <p><strong>Amount:</strong> {{ number_format($transaction->amount, 2) }}</p>
        <p><strong>Date:</strong> {{ $transaction->transaction_date }}</p>
        <p><strong>Method:</strong> {{ $transaction->method ?? '-' }}</p>
        <p><strong>Notes:</strong> {{ $transaction->notes ?? '-' }}</p>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Back</a>
        <a href="{{ route('transactions.edit', $transaction->id) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Edit</a>
    </div>
</div>
@endsection
