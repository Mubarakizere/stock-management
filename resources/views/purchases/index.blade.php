@extends('layouts.app')
@section('title', 'Purchases')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">Purchases</h1>
        <div class="flex gap-2">
            <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                + New Purchase
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

    {{-- Purchases Table --}}
    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse ($purchases as $purchase)
                    <tr class="hover:bg-gray-50 transition-all">
                        {{-- ID --}}
                        <td class="px-4 py-3 text-gray-700">{{ $purchase->id }}</td>

                        {{-- Supplier --}}
                        <td class="px-4 py-3 text-gray-700">
                            {{ $purchase->supplier->name ?? 'Unknown Supplier' }}
                        </td>

                        {{-- Date --}}
                        <td class="px-4 py-3 text-gray-700">
                            @php
                                $date = \Carbon\Carbon::parse($purchase->purchase_date);
                            @endphp
                            {{ $date->format('Y-m-d') }}
                        </td>

                        {{-- Total --}}
                        <td class="px-4 py-3 text-right text-gray-800 font-semibold">
                            {{ number_format($purchase->total_amount, 2) }}
                        </td>

                        {{-- Paid --}}
                        <td class="px-4 py-3 text-right text-gray-800">
                            {{ number_format($purchase->amount_paid, 2) }}
                        </td>

                        {{-- Balance --}}
                        <td class="px-4 py-3 text-right font-semibold
                            {{ $purchase->balance_due > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($purchase->balance_due, 2) }}
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3">
                            @if ($purchase->status === 'completed')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Paid
                                </span>
                            @elseif ($purchase->status === 'pending')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Cancelled
                                </span>
                            @endif
                        </td>

                        {{-- Method --}}
                        <td class="px-4 py-3 text-gray-700 uppercase">
                            {{ strtoupper($purchase->method ?? 'CASH') }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right space-x-1">
                            <a href="{{ route('purchases.show', $purchase) }}"
                               class="btn btn-secondary text-xs">View</a>

                            <a href="{{ route('purchases.edit', $purchase) }}"
                               class="btn btn-outline text-xs">Edit</a>

                            <a href="{{ route('purchases.invoice', $purchase) }}" target="_blank"
                               class="btn btn-outline text-xs">Invoice</a>

                            <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Delete this purchase? This will revert stock movements.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger text-xs">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500 text-sm">
                            No purchases recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $purchases->links() }}
    </div>
</div>
@endsection
