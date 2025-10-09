@extends('layouts.app')
@section('title', 'Sales')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">Sales</h1>
        <div class="flex gap-2">
            <a href="{{ route('sales.create') }}" class="btn btn-primary">
                + New Sale
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 p-3">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 p-3">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Search & Filter --}}
    <div class="bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('sales.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" placeholder="Search by customer or method..."
                    value="{{ request('search') }}"
                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <input type="date" name="from" value="{{ request('from') }}"
                    class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <input type="date" name="to" value="{{ request('to') }}"
                    class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button type="submit" class="btn btn-outline">Filter</button>
        </form>
    </div>

    {{-- Sales Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($sales as $sale)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $sale->id }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $sale->sale_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $sale->customer->name ?? 'Walk-in' }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">
                            {{ number_format($sale->total_amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">
                            {{ number_format($sale->amount_paid, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($sale->status === 'completed')
                                <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Completed</span>
                            @elseif ($sale->status === 'pending')
                                <span class="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Cancelled</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 uppercase">
                            {{ $sale->method ?? 'cash' }}
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-secondary text-xs">View</a>
                            <a href="{{ route('sales.edit', $sale) }}" class="btn btn-outline text-xs">Edit</a>
                            <a href="{{ route('sales.invoice', $sale) }}" target="_blank" class="btn btn-outline text-xs">Invoice</a>
                            <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="inline"
                                onsubmit="return confirm('Delete this sale? This will revert stock movements.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger text-xs">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500 text-sm">
                            No sales recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $sales->links() }}
    </div>
</div>
@endsection
