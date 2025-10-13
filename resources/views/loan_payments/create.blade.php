@extends('layouts.app')
@section('title', "Add Payment for Loan #{$loan->id}")

@section('content')
<div class="max-w-3xl mx-auto p-6 space-y-8">

    {{-- 🔹 Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">
            Add Payment for Loan #{{ $loan->id }}
        </h1>
        <a href="{{ route('loans.show', $loan) }}" class="btn btn-secondary text-sm">
            ← Back to Loan Details
        </a>
    </div>

    {{-- 🔹 Flash Messages --}}
    @if (session('error'))
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- 🔹 Validation Errors --}}
    @if ($errors->any())
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $totalPaid = $loan->payments->sum('amount');
        $remaining = max(($loan->amount ?? 0) - $totalPaid, 0);
        $progress = $loan->amount > 0 ? round(($totalPaid / $loan->amount) * 100) : 0;
    @endphp

    {{-- 🔹 Loan Summary --}}
    <div class="bg-white shadow rounded-xl border border-gray-100 p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-1">Loan Summary</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Total Amount</p>
                <p class="font-semibold text-indigo-600 text-lg">{{ number_format($loan->amount, 2) }}</p>
            </div>
            <div>
                <p class="text-gray-500">Total Paid</p>
                <p class="font-semibold text-green-600 text-lg">{{ number_format($totalPaid, 2) }}</p>
            </div>
        </div>
        <div class="flex justify-between text-sm text-gray-700">
            <p><strong>Remaining:</strong>
                <span class="{{ $remaining <= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                    {{ number_format($remaining, 2) }}
                </span>
            </p>
            <p><strong>Status:</strong>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                    {{ $loan->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ ucfirst($loan->status) }}
                </span>
            </p>
        </div>

        {{-- Progress Bar --}}
        <div class="pt-2">
            <div class="flex justify-between items-center mb-1 text-xs text-gray-600">
                <span>Repayment Progress</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </div>

    {{-- 🔹 Payment Form --}}
    <form method="POST" action="{{ route('loan-payments.store', $loan) }}"
          class="bg-white shadow rounded-xl border border-gray-100 p-6 space-y-6">
        @csrf

        {{-- Amount --}}
        <div>
            <label for="amount" class="block text-sm font-medium text-gray-700">
                Payment Amount
            </label>
            <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                   value="{{ old('amount') }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="Enter amount..." required>
        </div>

        {{-- Payment Date --}}
        <div>
            <label for="payment_date" class="block text-sm font-medium text-gray-700">
                Payment Date
            </label>
            <input type="date" name="payment_date" id="payment_date"
                   value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" required>
        </div>

        {{-- Method --}}
        <div>
            <label for="method" class="block text-sm font-medium text-gray-700">
                Payment Method
            </label>
            <input type="text" name="method" id="method"
                   value="{{ old('method', 'cash') }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="cash / momo / bank" required>
        </div>

        {{-- Notes --}}
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">
                Notes (optional)
            </label>
            <textarea name="notes" id="notes" rows="3"
                      class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Enter remarks or reference...">{{ old('notes') }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('loans.show', $loan) }}" class="btn btn-secondary text-sm">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary text-sm">
                Record Payment
            </button>
        </div>
    </form>
</div>
@endsection
