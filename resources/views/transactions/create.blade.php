@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">New Transaction</h2>

    <form action="{{ route('transactions.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block mb-1 font-medium">Type</label>
            <select name="type" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                <option value="">-- Select Type --</option>
                <option value="credit">Credit (Money In)</option>
                <option value="debit">Debit (Money Out)</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Customer (for credit)</label>
            <select name="customer_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Supplier (for debit)</label>
            <select name="supplier_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Link to Sale (if payment)</label>
            <select name="sale_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($sales as $sale)
                <option value="{{ $sale->id }}">Sale #{{ $sale->id }} ({{ $sale->sale_date }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Link to Purchase (if payment)</label>
            <select name="purchase_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- None --</option>
                @foreach($purchases as $purchase)
                <option value="{{ $purchase->id }}">Purchase #{{ $purchase->id }} ({{ $purchase->purchase_date }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Amount</label>
            <input type="number" step="0.01" name="amount" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        <div>
            <label class="block mb-1 font-medium">Transaction Date</label>
            <input type="date" name="transaction_date" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        <div>
            <label class="block mb-1 font-medium">Method</label>
            <input type="text" name="method" placeholder="Cash, Bank, MoMo..." class="w-full border-gray-300 rounded-lg shadow-sm">
        </div>

        <div>
            <label class="block mb-1 font-medium">Notes</label>
            <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm"></textarea>
        </div>

        <div>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Save Transaction</button>
            <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
