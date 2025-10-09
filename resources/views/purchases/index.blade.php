@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Purchases</h2>
        <a href="{{ route('purchases.create') }}" class="btn-primary">+ New Purchase</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-100">
                <tr class="text-gray-700">
                    <th class="px-4 py-2 border">#</th>
                    <th class="px-4 py-2 border">Supplier</th>
                    <th class="px-4 py-2 border">Date</th>
                    <th class="px-4 py-2 border text-right">Total</th>
                    <th class="px-4 py-2 border text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border">{{ $purchase->id }}</td>
                    <td class="px-4 py-2 border">{{ $purchase->supplier->name }}</td>
                    <td class="px-4 py-2 border">{{ $purchase->purchase_date }}</td>
                    <td class="px-4 py-2 border text-right text-green-700 font-medium">
                        {{ number_format($purchase->total_amount, 2) }}
                    </td>
                    <td class="px-4 py-2 border text-center space-x-2">
                        <a href="{{ route('purchases.show', $purchase->id) }}" class="btn-secondary text-xs">View</a>
                        <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn-warning text-xs">Edit</a>
                        <form action="{{ route('purchases.destroy', $purchase->id) }}" method="POST" class="inline"
                              onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger text-xs">Delete</button>
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

    <div class="mt-4">
        {{ $purchases->links() }}
    </div>
</div>
@endsection
