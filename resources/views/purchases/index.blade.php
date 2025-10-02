@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Purchases</h2>
        <a href="{{ route('purchases.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">New Purchase</a>
    </div>

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">ID</th>
                    <th class="px-4 py-2 border">Supplier</th>
                    <th class="px-4 py-2 border">Date</th>
                    <th class="px-4 py-2 border">Total</th>
                    <th class="px-4 py-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border">{{ $purchase->id }}</td>
                    <td class="px-4 py-2 border">{{ $purchase->supplier->name }}</td>
                    <td class="px-4 py-2 border">{{ $purchase->purchase_date }}</td>
                    <td class="px-4 py-2 border">{{ number_format($purchase->total_amount, 2) }}</td>
                    <td class="px-4 py-2 border flex gap-2">
                        <a href="{{ route('purchases.show', $purchase->id) }}" class="px-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-xs">View</a>
                        <a href="{{ route('purchases.edit', $purchase->id) }}" class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">Edit</a>
                        <form action="{{ route('purchases.destroy', $purchase->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-gray-500">No purchases found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
