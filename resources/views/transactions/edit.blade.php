@extends('layouts.app')

@section('content')
<div class="card max-w-4xl mx-auto">
    <h2 class="text-lg font-semibold mb-4">Edit Transaction #{{ $transaction->id }}</h2>

    <form action="{{ route('transactions.update', $transaction->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" required>
                    <option value="credit" @selected($transaction->type=='credit')>Credit</option>
                    <option value="debit" @selected($transaction->type=='debit')>Debit</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Transaction Date</label>
                <input type="date" name="transaction_date" value="{{ $transaction->transaction_date }}" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount" value="{{ $transaction->amount }}" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Method</label>
                <input type="text" name="method" value="{{ $transaction->method }}" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-select">
                    <option value="">None</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected($transaction->customer_id==$customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">None</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected($transaction->supplier_id==$supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Sale</label>
                <select name="sale_id" class="form-select">
                    <option value="">None</option>
                    @foreach($sales as $sale)
                        <option value="{{ $sale->id }}" @selected($transaction->sale_id==$sale->id)>
                            Sale #{{ $sale->id }} ({{ $sale->sale_date }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Purchase</label>
                <select name="purchase_id" class="form-select">
                    <option value="">None</option>
                    @foreach($purchases as $purchase)
                        <option value="{{ $purchase->id }}" @selected($transaction->purchase_id==$purchase->id)>
                            Purchase #{{ $purchase->id }} ({{ $purchase->purchase_date }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="3" class="form-textarea">{{ $transaction->notes }}</textarea>
        </div>

        <div class="flex justify-end space-x-2 mt-6">
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>
@endsection
