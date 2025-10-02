@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">Edit Transaction #{{ $transaction->id }}</h2>

    <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block mb-1 font-medium">Type</label>
            <select name="type" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                <option value="credit" {{ $transaction->type == 'credit' ? 'selected' : '' }}>Credit (Money In)</option>
                <option value="debit" {{ $transaction->type == 'debit' ? 'selected' : '' }}>Debit (Money Out)</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Customer</label>
            <select name="customer_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ $transaction->customer_id == $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Supplier</label>
            <select name="supplier_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ $transaction->supplier_id == $supplier->id ? 'selected' : '' }}>
                    {{ $supplier->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Link to Sale</label>
            <select name="sale_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($sales as $sale)
                <option value="{{ $sale->id }}" {{ $transaction->sale_id == $sale->id ? 'selected' : '' }}>
                    Sale #{{ $sale->id }} ({{ $sale->sale_date }})
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Link to Purchase</label>
            <select name="purchase_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($purchases as $purchase)
                <option value="{{ $purchase->id }}" {{ $transaction->purchase_id == $purchase->id ? 'selected' : '' }}>
                    Purchase #{{ $purchase->id }} ({{ $purchase->purchase_date }})
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Amount</label>
            <input type="number" step="0.01" name="amount" value="{{ $transaction->amount }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        <div>
            <label class="block mb-1 font-medium">Transaction Date</label>
            <input type="date" name="transaction_date" value="{{ $transaction->transaction_date }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        <div>
            <label class="block mb-1 font-medium">Method</label>
            <input type="text" name="method" value="{{ $transaction->method }}" class="w-full border-gray-300 rounded-lg shadow-sm">
        </div>

        <div>
            <label class="block mb-1 font-medium">Notes</label>
            <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm">{{ $transaction->notes }}</textarea>
        </div>

        <div>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Update Transaction</button>
            <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
