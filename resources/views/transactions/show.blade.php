@extends('layouts.app')

@section('content')
<div class="card max-w-3xl mx-auto">
    <h2 class="text-lg font-semibold mb-4">Transaction #{{ $transaction->id }}</h2>

    <div class="space-y-2 text-sm text-gray-700">
        <p><strong>Type:</strong> {{ ucfirst($transaction->type) }}</p>
        <p><strong>Party:</strong>
            @if($transaction->customer)
                Customer: {{ $transaction->customer->name }}
            @elseif($transaction->supplier)
                Supplier: {{ $transaction->supplier->name }}
            @else
                —
            @endif
        </p>
        <p><strong>Linked:</strong>
            @if($transaction->sale) Sale #{{ $transaction->sale->id }}
            @elseif($transaction->purchase) Purchase #{{ $transaction->purchase->id }}
            @else —
            @endif
        </p>
        <p><strong>Amount:</strong> {{ number_format($transaction->amount, 2) }}</p>
        <p><strong>Date:</strong> {{ $transaction->transaction_date }}</p>
        <p><strong>Method:</strong> {{ $transaction->method ?? '-' }}</p>
        <p><strong>Notes:</strong> {{ $transaction->notes ?? '-' }}</p>
    </div>

    <div class="flex justify-end space-x-2 mt-6">
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Back</a>
        <a href="{{ route('transactions.edit', $transaction->id) }}" class="btn btn-primary">Edit</a>
    </div>
</div>
@endsection
