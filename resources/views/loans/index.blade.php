@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Loans Overview</h1>
        <div class="flex gap-2">
            <a href="{{ route('loans.export.pdf') }}"
               class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
               Download PDF Summary
            </a>
            <a href="{{ route('loans.create') }}"
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
               + New Loan
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <div class="p-4 bg-indigo-50 rounded-lg shadow-sm text-center">
            <p class="text-gray-500 text-sm">Total Loans</p>
            <p class="text-xl font-bold text-indigo-700">{{ number_format($stats['total_loans'], 2) }}</p>
        </div>
        <div class="p-4 bg-green-50 rounded-lg shadow-sm text-center">
            <p class="text-gray-500 text-sm">Paid Loans</p>
            <p class="text-xl font-bold text-green-700">{{ number_format($stats['paid_loans'], 2) }}</p>
        </div>
        <div class="p-4 bg-yellow-50 rounded-lg shadow-sm text-center">
            <p class="text-gray-500 text-sm">Pending Loans</p>
            <p class="text-xl font-bold text-yellow-700">{{ number_format($stats['pending_loans'], 2) }}</p>
        </div>
        <div class="p-4 bg-blue-50 rounded-lg shadow-sm text-center">
            <p class="text-gray-500 text-sm">Loans Given</p>
            <p class="text-xl font-bold text-blue-700">{{ $stats['count_given'] }}</p>
        </div>
        <div class="p-4 bg-purple-50 rounded-lg shadow-sm text-center">
            <p class="text-gray-500 text-sm">Loans Taken</p>
            <p class="text-xl font-bold text-purple-700">{{ $stats['count_taken'] }}</p>
        </div>
    </div>

    {{-- Loan Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client / Supplier</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Loan Date</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Due Date</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($loans as $loan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-gray-700 font-medium">#{{ $loan->id }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $loan->type === 'given' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                {{ ucfirst($loan->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-700">
                            @if ($loan->type === 'given')
                                {{ $loan->customer->name ?? '-' }}
                            @else
                                {{ $loan->supplier->name ?? '-' }}
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-gray-800 font-semibold">
                            {{ number_format($loan->amount, 2) }}
                        </td>
                        <td class="px-4 py-2 text-gray-600">{{ $loan->loan_date }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $loan->due_date ?? '-' }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $loan->status === 'paid'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('loans.show', $loan->id) }}"
                                   class="text-indigo-600 hover:underline text-sm">View</a>
                                <a href="{{ route('loans.edit', $loan->id) }}"
                                   class="text-green-600 hover:underline text-sm">Edit</a>
                                <form action="{{ route('loans.destroy', $loan->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">No loans recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
