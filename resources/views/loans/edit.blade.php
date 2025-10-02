@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">Edit Loan #{{ $loan->id }}</h2>

    <form action="{{ route('loans.update', $loan->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block mb-1 font-medium">Type</label>
            <select name="type" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                <option value="given" {{ $loan->type == 'given' ? 'selected' : '' }}>Loan Given</option>
                <option value="taken" {{ $loan->type == 'taken' ? 'selected' : '' }}>Loan Taken</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Customer (if loan given)</label>
            <select name="customer_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ $loan->customer_id == $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Supplier (if loan taken)</label>
            <select name="supplier_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ $loan->supplier_id == $supplier->id ? 'selected' : '' }}>
                    {{ $supplier->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Amount</label>
            <input type="number" step="0.01" name="amount" value="{{ $loan->amount }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        <div>
            <label class="block mb-1 font-medium">Loan Date</label>
            <input type="date" name="loan_date" value="{{ $loan->loan_date }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        <div>
            <label class="block mb-1 font-medium">Due Date</label>
            <input type="date" name="due_date" value="{{ $loan->due_date }}" class="w-full border-gray-300 rounded-lg shadow-sm">
        </div>

        <div>
            <label class="block mb-1 font-medium">Status</label>
            <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                <option value="pending" {{ $loan->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="paid" {{ $loan->status == 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Notes</label>
            <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm">{{ $loan->notes }}</textarea>
        </div>

        <div>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Update Loan</button>
            <a href="{{ route('loans.index') }}" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
