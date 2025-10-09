@extends('layouts.app')

@section('content')
<div class="card max-w-4xl mx-auto">
    <h2 class="text-lg font-semibold mb-4">Add Transaction</h2>

    <form action="{{ route('transactions.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="credit">Credit (Money In)</option>
                    <option value="debit">Debit (Money Out)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Transaction Date</label>
                <input type="date" name="transaction_date" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Method</label>
                <input type="text" name="method" placeholder="Cash, Bank, MoMo..." class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Customer (for Credit)</label>
                <select name="customer_id" class="form-select">
                    <option value="">None</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Supplier (for Debit)</label>
                <select name="supplier_id" class="form-select">
                    <option value="">None</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Link to Sale</label>
                <select name="sale_id" class="form-select">
                    <option value="">None</option>
                    @foreach($sales as $sale)
                        <option value="{{ $sale->id }}">Sale #{{ $sale->id }} ({{ $sale->sale_date }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Link to Purchase</label>
                <select name="purchase_id" class="form-select">
                    <option value="">None</option>
                    @foreach($purchases as $purchase)
                        <option value="{{ $purchase->id }}">Purchase #{{ $purchase->id }} ({{ $purchase->purchase_date }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="3" class="form-textarea"></textarea>
        </div>

        <div class="flex justify-end space-x-2 mt-6">
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
