@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Sales</h2>
        <a href="{{ route('sales.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">New Sale</a>
    </div>

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">ID</th>
                    <th class="px-4 py-2 border">Customer</th>
                    <th class="px-4 py-2 border">Date</th>
                    <th class="px-4 py-2 border">Total</th>
                    <th class="px-4 py-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border">{{ $sale->id }}</td>
                    <td class="px-4 py-2 border">{{ $sale->customer->name ?? 'Walk-in' }}</td>
                    <td class="px-4 py-2 border">{{ $sale->sale_date }}</td>
                    <td class="px-4 py-2 border">{{ number_format($sale->total_amount, 2) }}</td>
                    <td class="px-4 py-2 border flex gap-2">
                        <a href="{{ route('sales.show', $sale->id) }}" class="px-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-xs">View</a>
                        <a href="{{ route('sales.edit', $sale->id) }}" class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">Edit</a>
                        <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-gray-500">No sales found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
