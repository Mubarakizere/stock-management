@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">Loan #{{ $loan->id }}</h2>

    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <p><strong>Type:</strong> {{ ucfirst($loan->type) }}</p>
        <p><strong>Party:</strong>
            @if($loan->customer) Customer: {{ $loan->customer->name }}
            @elseif($loan->supplier) Supplier: {{ $loan->supplier->name }}
            @else N/A
            @endif
        </p>
        <p><strong>Amount:</strong> {{ number_format($loan->amount, 2) }}</p>
        <p><strong>Loan Date:</strong> {{ $loan->loan_date }}</p>
        <p><strong>Due Date:</strong> {{ $loan->due_date ?? '-' }}</p>
        <p><strong>Status:</strong> {{ ucfirst($loan->status) }}</p>
        <p><strong>Notes:</strong> {{ $loan->notes ?? '-' }}</p>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('loans.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Back</a>
        <a href="{{ route('loans.edit', $loan->id) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Edit</a>
    </div>
</div>
@endsection
