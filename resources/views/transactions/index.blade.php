@extends('layouts.app')

@section('content')
{{-- <div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-semibold text-gray-800">Transactions</h1>
    <a href="{{ route('transactions.create') }}" class="btn btn-primary">+ New Transaction</a>
</div> --}}

<div class="card mb-6">
    {{-- 🔍 Filter Form --}}
    <form method="GET" action="{{ route('transactions.index') }}">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="form-label" for="type">Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="">All</option>
                    <option value="credit" @selected(request('type')=='credit')>Credit</option>
                    <option value="debit" @selected(request('type')=='debit')>Debit</option>
                </select>
            </div>

            <div>
                <label class="form-label" for="date_from">From</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-input">
            </div>

            <div>
                <label class="form-label" for="date_to">To</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-input">
            </div>

            <div>
                <label class="form-label" for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Models\Customer::all() as $customer)
                        <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Models\Supplier::all() as $supplier)
                        <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">Filter</button>
            </div>
        </div>
    </form>
</div>

{{-- Totals Summary --}}
<div class="card mb-6 flex flex-col sm:flex-row justify-between text-sm sm:text-base">
    <p><strong>Total Credits:</strong> <span class="text-green-600">{{ number_format($totalCredits, 2) }}</span></p>
    <p><strong>Total Debits:</strong> <span class="text-red-600">{{ number_format($totalDebits, 2) }}</span></p>
</div>

{{-- Export Buttons --}}
<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('transactions.export.csv') }}" class="btn btn-success">Export CSV</a>
    <a href="{{ route('transactions.export.pdf') }}" class="btn btn-danger">Export PDF</a>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>User</th>
                    <th>Customer</th>
                    <th>Supplier</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date }}</td>
                        <td>
                            <span class="px-2 py-1 rounded text-white {{ $transaction->type === 'credit' ? 'bg-green-500' : 'bg-red-500' }}">
                                {{ ucfirst($transaction->type) }}
                            </span>
                        </td>
                        <td>{{ number_format($transaction->amount, 2) }}</td>
                        <td>{{ $transaction->method ?? '-' }}</td>
                        <td>{{ $transaction->user->name ?? '-' }}</td>
                        <td>{{ $transaction->customer->name ?? '-' }}</td>
                        <td>{{ $transaction->supplier->name ?? '-' }}</td>
                        <td>{{ $transaction->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="table-empty">No transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">{{ $transactions->links() }}</div>
</div>
@endsection
