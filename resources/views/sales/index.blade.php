@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Sales</h2>
        <a href="{{ route('sales.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            New Sale
        </a>
    </div>

    {{-- Sales Table --}}
    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">ID</th>
                    <th class="px-4 py-2 border">Customer</th>
                    <th class="px-4 py-2 border">Date</th>
                    <th class="px-4 py-2 border text-right">Total</th>
                    <th class="px-4 py-2 border text-right">Paid</th>
                    <th class="px-4 py-2 border text-right">Balance</th>
                    <th class="px-4 py-2 border">Method</th>
                    <th class="px-4 py-2 border text-center">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border">{{ $sale->id }}</td>
                    <td class="px-4 py-2 border">{{ $sale->customer->name ?? 'Walk-in' }}</td>
                    <td class="px-4 py-2 border">{{ $sale->sale_date }}</td>
                    <td class="px-4 py-2 border text-right">{{ number_format($sale->total_amount, 2) }}</td>
                    <td class="px-4 py-2 border text-right">{{ number_format($sale->amount_paid ?? 0, 2) }}</td>
                    <td class="px-4 py-2 border text-right text-{{ $sale->balance > 0 ? 'red' : 'green' }}-600 font-semibold">
                        {{ number_format($sale->balance, 2) }}
                    </td>
                    <td class="px-4 py-2 border">{{ ucfirst($sale->method ?? 'cash') }}</td>

                    <td class="px-4 py-2 border text-center flex flex-wrap justify-center gap-2">
                        <a href="{{ route('sales.show', $sale->id) }}"
                           class="px-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-xs">
                           View
                        </a>
                        <a href="{{ route('sales.edit', $sale->id) }}"
                           class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">
                           Edit
                        </a>
                        <a href="{{ route('sales.invoice', $sale->id) }}" target="_blank"
                           class="px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-xs">
                           Invoice
                        </a>
                        <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this sale?');">
                            @csrf
                            @method('DELETE')
                            <button class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-gray-500">
                        No sales recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>

            {{-- 📊 Summary Footer --}}
            @if($sales->count() > 0)
            <tfoot class="bg-gray-50 font-semibold text-sm">
                <tr>
                    <td colspan="3" class="px-4 py-2 border text-right">Total Sales:</td>
                    <td class="px-4 py-2 border text-right text-blue-700">
                        {{ number_format($sales->sum('total_amount'), 2) }}
                    </td>
                    <td class="px-4 py-2 border text-right text-green-700">
                        {{ number_format($sales->sum('amount_paid'), 2) }}
                    </td>
                    <td class="px-4 py-2 border text-right text-red-700">
                        {{ number_format($sales->sum(fn($s) => $s->balance), 2) }}
                    </td>
                    <td colspan="2" class="px-4 py-2 border text-center text-gray-600">
                        {{ $sales->count() }} Sale{{ $sales->count() > 1 ? 's' : '' }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
