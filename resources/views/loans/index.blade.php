@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Loans</h2>
        <a href="{{ route('loans.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">New Loan</a>
    </div>

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">ID</th>
                    <th class="px-4 py-2 border">Type</th>
                    <th class="px-4 py-2 border">Party</th>
                    <th class="px-4 py-2 border">Amount</th>
                    <th class="px-4 py-2 border">Loan Date</th>
                    <th class="px-4 py-2 border">Due Date</th>
                    <th class="px-4 py-2 border">Status</th>
                    <th class="px-4 py-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border">{{ $loan->id }}</td>
                    <td class="px-4 py-2 border capitalize">{{ $loan->type }}</td>
                    <td class="px-4 py-2 border">
                        @if($loan->customer) Customer: {{ $loan->customer->name }}
                        @elseif($loan->supplier) Supplier: {{ $loan->supplier->name }}
                        @else N/A
                        @endif
                    </td>
                    <td class="px-4 py-2 border">{{ number_format($loan->amount, 2) }}</td>
                    <td class="px-4 py-2 border">{{ $loan->loan_date }}</td>
                    <td class="px-4 py-2 border">{{ $loan->due_date ?? '-' }}</td>
                    <td class="px-4 py-2 border">
                        <span class="px-2 py-1 rounded text-white text-xs
                            {{ $loan->status == 'paid' ? 'bg-green-600' : 'bg-yellow-500' }}">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 border flex gap-2">
                        <a href="{{ route('loans.show', $loan->id) }}" class="px-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-xs">View</a>
                        <a href="{{ route('loans.edit', $loan->id) }}" class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">Edit</a>
                        <form action="{{ route('loans.destroy', $loan->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-gray-500">No loans found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
