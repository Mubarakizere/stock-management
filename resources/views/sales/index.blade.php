@extends('layouts.app')
@section('title', 'Sales')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- 🔹 Page Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">Sales</h1>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">+ New Sale</a>
    </div>

    {{-- 🔹 Flash Messages --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- 🔹 Search & Filter --}}
    <div class="bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('sales.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" placeholder="Search by customer, method or status..."
                value="{{ request('search') }}"
                class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">

            <input type="date" name="from" value="{{ request('from') }}"
                class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">

            <input type="date" name="to" value="{{ request('to') }}"
                class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">

            <button type="submit" class="btn btn-outline text-sm px-4">Filter</button>
        </form>
    </div>

    {{-- 🔹 Sales Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse ($sales as $sale)
                    @php
                        $balance = round(($sale->total_amount ?? 0) - ($sale->amount_paid ?? 0), 2);
                        $date = \Carbon\Carbon::parse($sale->sale_date);
                    @endphp

                    <tr class="hover:bg-gray-50 transition-all">
                        {{-- ID --}}
                        <td class="px-4 py-3 text-gray-700">{{ $sale->id }}</td>

                        {{-- Date --}}
                        <td class="px-4 py-3 text-gray-700">{{ $date->format('Y-m-d') }}</td>

                        {{-- Customer --}}
                        <td class="px-4 py-3 text-gray-700">
                            {{ $sale->customer->name ?? 'Walk-in' }}
                        </td>

                        {{-- Total --}}
                        <td class="px-4 py-3 text-right text-gray-800 font-medium">
                            {{ number_format($sale->total_amount, 2) }}
                        </td>

                        {{-- Paid --}}
                        <td class="px-4 py-3 text-right text-gray-800">
                            {{ number_format($sale->amount_paid ?? 0, 2) }}
                        </td>

                        {{-- Balance --}}
                        <td class="px-4 py-3 text-right font-semibold
                            {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($balance, 2) }}
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3">
                            @if ($sale->status === 'completed')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Completed
                                </span>
                            @elseif ($sale->status === 'pending')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Cancelled
                                </span>
                            @endif

                            {{-- Loan indicator --}}
                            @if ($sale->loan)
                                <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    {{ $sale->loan->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    Loan {{ ucfirst($sale->loan->status) }}
                                </span>
                            @endif
                        </td>

                        {{-- Method --}}
                        <td class="px-4 py-3 text-gray-700 uppercase">
                            {{ strtoupper($sale->method ?? 'CASH') }}
                        </td>

                        {{-- Actions --}}
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
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500 text-sm">
                            No sales recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 🔹 Pagination --}}
    <div class="mt-4">
        {{ $sales->links() }}
    </div>
</div>
@endsection
