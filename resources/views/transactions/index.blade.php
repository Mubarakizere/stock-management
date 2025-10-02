@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <h1 class="text-2xl font-bold mb-6">Transactions</h1>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('transactions.index') }}" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">

            {{-- Type Filter --}}
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" id="type"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Types</option>
                    <option value="credit" {{ request('type')=='credit' ? 'selected' : '' }}>Credit</option>
                    <option value="debit" {{ request('type')=='debit' ? 'selected' : '' }}>Debit</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">From</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            {{-- Date To --}}
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">To</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            {{-- Customer Dropdown --}}
            <div>
                <label for="customer_id" class="block text-sm font-medium text-gray-700">Customer</label>
                <select name="customer_id" id="customer_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Customers</option>
                    @foreach(\App\Models\Customer::all() as $customer)
                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Supplier Dropdown --}}
            <div>
                <label for="supplier_id" class="block text-sm font-medium text-gray-700">Supplier</label>
                <select name="supplier_id" id="supplier_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Suppliers</option>
                    @foreach(\App\Models\Supplier::all() as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Submit --}}
            <div class="flex items-end">
                <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                    Filter
                </button>
            </div>
        </div>
    </form>

    {{-- Totals --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <p class="text-lg"><strong>Total Credits:</strong> <span class="text-green-600">{{ number_format($totalCredits, 2) }}</span></p>
        <p class="text-lg"><strong>Total Debits:</strong> <span class="text-red-600">{{ number_format($totalDebits, 2) }}</span></p>
    </div>
     <div class="flex space-x-2 mb-4">
    <a href="{{ route('transactions.export.csv') }}"
       class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
       Export CSV
    </a>
    <a href="{{ route('transactions.export.pdf') }}"
       class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
       Export PDF
    </a>
</div>

    {{-- Transactions Table --}}
    <div class="overflow-x-auto bg-white shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Type</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Amount</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Method</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">User</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Customer</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Supplier</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse($transactions as $transaction)
                    <tr>
                        <td class="px-4 py-2">{{ $transaction->transaction_date }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-white {{ $transaction->type == 'credit' ? 'bg-green-500' : 'bg-red-500' }}">
                                {{ ucfirst($transaction->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ number_format($transaction->amount, 2) }}</td>
                        <td class="px-4 py-2">{{ $transaction->method ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $transaction->user->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $transaction->customer->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $transaction->supplier->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $transaction->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-3 text-center text-gray-500">No transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
