@extends('layouts.app')

@section('content')
<div class="p-6 max-w-md mx-auto">
    <h1 class="text-2xl font-bold mb-4">Record Payment for Loan #{{ $loan->id }}</h1>

    <form action="{{ route('loan-payments.store', $loan->id) }}" method="POST" class="space-y-4 bg-white p-6 rounded-lg shadow">
        @csrf

        <div>
            <label class="block font-semibold mb-1">Amount</label>
            <input type="number" name="amount" step="0.01" required class="w-full border rounded-lg p-2">
        </div>

        <div>
            <label class="block font-semibold mb-1">Payment Date</label>
            <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="w-full border rounded-lg p-2">
        </div>

        <div>
            <label class="block font-semibold mb-1">Method</label>
            <input type="text" name="method" value="cash" required class="w-full border rounded-lg p-2">
        </div>

        <div>
            <label class="block font-semibold mb-1">Notes</label>
            <textarea name="notes" rows="2" class="w-full border rounded-lg p-2" placeholder="Optional..."></textarea>
        </div>

        <div class="flex justify-between pt-4">
            <a href="{{ route('loans.show', $loan->id) }}" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
        </div>
    </form>
</div>
@endsection
