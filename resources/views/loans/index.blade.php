@extends('layouts.app')
@section('title', 'Loans Overview')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">

    {{-- 🔹 Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">Loans Overview</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('loans.export.pdf') }}" class="btn btn-outline text-sm">
                Download PDF Summary
            </a>
            <a href="{{ route('loans.create') }}" class="btn btn-primary text-sm">
                + New Loan
            </a>
        </div>
    </div>

    {{-- 🔹 Stats Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <x-stat-card title="Total Loans" value="{{ number_format($stats['total_loans'], 2) }}" color="indigo" />
        <x-stat-card title="Paid Loans" value="{{ number_format($stats['paid_loans'], 2) }}" color="green" />
        <x-stat-card title="Pending Loans" value="{{ number_format($stats['pending_loans'], 2) }}" color="yellow" />
        <x-stat-card title="Loans Given" value="{{ $stats['count_given'] }}" color="blue" />
        <x-stat-card title="Loans Taken" value="{{ $stats['count_taken'] }}" color="purple" />
    </div>

    {{-- 🔹 Loans Table --}}
    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">#</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Client / Supplier</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">Remaining</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Loan Date</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($loans as $loan)
                    @php
                        $paid = $loan->payments()->sum('amount');
                        $remaining = round(($loan->amount ?? 0) - $paid, 2);
                    @endphp

                    <tr class="hover:bg-gray-50 transition">
                        {{-- ID --}}
                        <td class="px-4 py-3 text-gray-700 font-medium">#{{ $loan->id }}</td>

                        {{-- Type --}}
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $loan->type === 'given' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                {{ ucfirst($loan->type) }}
                            </span>
                        </td>

                        {{-- Client / Supplier --}}
                        <td class="px-4 py-3 text-gray-700">
                            @if ($loan->type === 'given')
                                {{ $loan->customer->name ?? '-' }}
                            @else
                                {{ $loan->supplier->name ?? '-' }}
                            @endif
                        </td>

                        {{-- Amount --}}
                        <td class="px-4 py-3 text-right text-gray-800 font-semibold">
                            {{ number_format($loan->amount, 2) }}
                        </td>

                        {{-- Paid --}}
                        <td class="px-4 py-3 text-right text-gray-700">
                            {{ number_format($paid, 2) }}
                        </td>

                        {{-- Remaining --}}
                        <td class="px-4 py-3 text-right font-semibold
                            {{ $remaining > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($remaining, 2) }}
                        </td>

                        {{-- Loan Date --}}
                        <td class="px-4 py-3 text-gray-600">
                            {{ optional($loan->loan_date)->format('Y-m-d') }}
                        </td>

                        {{-- Due Date --}}
                        <td class="px-4 py-3 text-gray-600">
                            {{ optional($loan->due_date)->format('Y-m-d') ?? '-' }}
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $loan->status === 'paid'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-secondary text-xs">View</a>
                                <a href="{{ route('loans.edit', $loan->id) }}" class="btn btn-outline text-xs">Edit</a>
                                <form action="{{ route('loans.destroy', $loan->id) }}" method="POST"
                                      onsubmit="return confirm('Delete this loan? This will remove linked payments.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-6 text-center text-gray-500 text-sm">
                            No loans recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $loans->links() }}
    </div>
</div>
@endsection
