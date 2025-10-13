@extends('layouts.app')
@section('title', 'Purchase Details')

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-8">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">
            Purchase #{{ $purchase->id }}
        </h1>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('purchases.invoice', $purchase->id) }}" target="_blank" class="btn btn-primary">
                Download PDF
            </a>
            <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn btn-outline">
                Edit
            </a>
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                Back
            </a>
        </div>
    </div>

    {{-- Purchase Info --}}
    <div class="bg-white shadow rounded-xl p-6 grid md:grid-cols-2 gap-6">
        <div class="space-y-2">
            <p><strong class="text-gray-600">Supplier:</strong>
                {{ $purchase->supplier->name ?? 'Unknown Supplier' }}</p>
            <p><strong class="text-gray-600">Date:</strong>
                {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') }}</p>
            <p><strong class="text-gray-600">Status:</strong>
                @if ($purchase->status === 'completed')
                    <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Paid</span>
                @elseif ($purchase->status === 'pending')
                    <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">Pending</span>
                @else
                    <span class="px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">Cancelled</span>
                @endif
            </p>
            <p><strong class="text-gray-600">Recorded By:</strong>
                {{ $purchase->user->name ?? 'N/A' }}</p>
        </div>

        <div class="space-y-2">
            <p><strong class="text-gray-600">Subtotal:</strong> {{ number_format($purchase->subtotal, 2) }}</p>
            <p><strong class="text-gray-600">Tax:</strong> {{ number_format($purchase->tax, 2) }}</p>
            <p><strong class="text-gray-600">Discount:</strong> {{ number_format($purchase->discount, 2) }}</p>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="bg-white shadow rounded-xl overflow-hidden">
        <h3 class="text-lg font-semibold text-gray-800 px-6 py-4 border-b">Purchased Items</h3>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($purchase->items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-gray-700">{{ $item->product->name }}</td>
                        <td class="px-4 py-2 text-center">{{ $item->quantity }}</td>
                        <td class="px-4 py-2 text-center">{{ number_format($item->unit_cost, 2) }}</td>
                        <td class="px-4 py-2 text-center text-gray-800 font-medium">
                            {{ number_format($item->total_cost, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Summary & Payment Section --}}
    <div class="bg-white shadow rounded-xl p-6 space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Purchase Summary</h3>
            <span class="text-2xl font-bold text-green-700">
                {{ number_format($purchase->total_amount, 2) }}
            </span>
        </div>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div>
                <p><strong>Paid:</strong>
                    <span class="text-gray-800">{{ number_format($purchase->amount_paid, 2) }}</span>
                </p>
                <p><strong>Balance:</strong>
                    <span class="{{ $purchase->balance_due > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold' }}">
                        {{ number_format($purchase->balance_due, 2) }}
                    </span>
                </p>
                <p><strong>Payment Method:</strong> {{ strtoupper($purchase->method ?? 'CASH') }}</p>
            </div>

            <div>
                <p><strong>Transaction:</strong>
                    @if ($purchase->transaction)
                        {{ ucfirst($purchase->transaction->type) }} •
                        {{ $purchase->transaction->user->name ?? 'N/A' }}
                    @else
                        <em class="text-gray-500">No transaction recorded</em>
                    @endif
                </p>
            </div>
        </div>

        {{-- Progress bar for installment payments --}}
        @php
            $progress = $purchase->total_amount > 0
                ? round(($purchase->amount_paid / $purchase->total_amount) * 100)
                : 0;
        @endphp

        <div class="mt-4">
            <div class="w-full bg-gray-100 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-300"
                     style="width: {{ $progress }}%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-1">Payment Progress: {{ $progress }}%</p>
        </div>
    </div>

    {{-- Loan Info (auto-created if unpaid) --}}
    @php
        $loan = \App\Models\Loan::where('purchase_id', $purchase->id)->first();
    @endphp

    @if ($loan)
        <div class="bg-white shadow rounded-xl p-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-2">Linked Loan</h4>
            <p><strong>Loan Type:</strong>
                <span class="text-purple-700 font-medium">{{ ucfirst($loan->type) }}</span>
            </p>
            <p><strong>Loan Amount:</strong> {{ number_format($loan->amount, 2) }}</p>
            <p><strong>Status:</strong>
                <span class="px-2 py-1 rounded-full text-xs font-medium
                    {{ $loan->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ ucfirst($loan->status) }}
                </span>
            </p>
            @if ($loan->status === 'pending')
                <div class="mt-3">
                    <a href="{{ route('loan-payments.create', $loan) }}" class="btn btn-success text-xs">
                        + Add Payment
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- Notes --}}
    @if ($purchase->notes)
        <div class="bg-white shadow rounded-xl p-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-2">Notes</h4>
            <p class="text-gray-700 whitespace-pre-line">{{ $purchase->notes }}</p>
        </div>
    @endif
</div>
@endsection
