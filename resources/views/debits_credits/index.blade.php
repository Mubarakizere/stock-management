@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Debits & Credits</h1>
        <a href="{{ route('debits-credits.create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            + New Entry
        </a>
    </div>

    {{-- Totals --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-500 font-semibold">Total Debits</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($debitsTotal, 2) }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-sm text-green-500 font-semibold">Total Credits</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($creditsTotal, 2) }}</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-sm text-gray-500 font-semibold">Net Balance</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($net, 2) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
        <select name="type" class="border rounded-lg p-2">
            <option value="">All Types</option>
            <option value="debit" {{ request('type')=='debit'?'selected':'' }}>Debits</option>
            <option value="credit" {{ request('type')=='credit'?'selected':'' }}>Credits</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="border rounded-lg p-2" placeholder="From">
        <input type="date" name="to" value="{{ request('to') }}" class="border rounded-lg p-2" placeholder="To">
        <input type="text" name="q" value="{{ request('q') }}" class="border rounded-lg p-2" placeholder="Search...">
        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Filter</button>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full border border-gray-200">
            <thead class="bg-gray-100">
                <tr class="text-left text-sm font-semibold text-gray-600">
                    <th class="p-3">Date</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Amount</th>
                    <th class="p-3">Description</th>
                    <th class="p-3">Customer</th>
                    <th class="p-3">Supplier</th>
                    <th class="p-3">User</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $item)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3">{{ $item->date }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs font-semibold
                                {{ $item->type == 'debit' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                {{ ucfirst($item->type) }}
                            </span>
                        </td>
                        <td class="p-3 font-semibold">{{ number_format($item->amount, 2) }}</td>
                        <td class="p-3">{{ $item->description ?? '-' }}</td>
                        <td class="p-3">{{ $item->customer->name ?? '-' }}</td>
                        <td class="p-3">{{ $item->supplier->name ?? '-' }}</td>
                        <td class="p-3">{{ $item->user->name ?? '-' }}</td>
                        <td class="p-3 text-right">
                            <a href="{{ route('debits-credits.edit', $item->id) }}"
                               class="text-blue-600 hover:underline">Edit</a>
                            <form action="{{ route('debits-credits.destroy', $item->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this entry?')"
                                        class="text-red-600 hover:underline ml-2">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4 text-center text-gray-500">No records found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
</div>
@endsection
