@extends('layouts.app')
@section('title', "Loan #{$loan->id}")

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-8">

    {{-- 🔹 Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">
            Loan Details #{{ $loan->id }}
        </h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('loans.index') }}" class="btn btn-secondary text-sm">← Back</a>
            <a href="{{ route('loans.edit', $loan) }}" class="btn btn-outline text-sm">Edit</a>
            <form action="{{ route('loans.destroy', $loan) }}" method="POST"
                  onsubmit="return confirm('Delete this loan? This will remove related payments.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger text-sm">Delete</button>
            </form>
        </div>
    </div>

    {{-- ✅ Loan Summary --}}
    @php
        $paid = $loan->payments()->sum('amount');
        $remaining = round(($loan->amount ?? 0) - $paid, 2);
        $progress = $loan->amount > 0 ? round(($paid / $loan->amount) * 100) : 0;
    @endphp

    <section class="bg-white shadow rounded-xl p-6 border border-gray-100 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">Loan Summary</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Type</p>
                <p class="font-semibold capitalize">
                    {{ ucfirst($loan->type) }}
                </p>
            </div>
            <div>
                <p class="text-gray-500">Amount</p>
                <p class="font-bold text-indigo-600">{{ number_format($loan->amount, 2) }}</p>
            </div>
            <div>
                <p class="text-gray-500">Status</p>
                <span class="px-2 py-1 rounded-full text-xs font-semibold
                    {{ $loan->status === 'paid' ? 'bg-green-100 text-green-700' :
                       ($loan->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    {{ ucfirst($loan->status) }}
                </span>
            </div>
            <div>
                <p class="text-gray-500">Loan Date</p>
                <p class="font-medium">{{ \Carbon\Carbon::parse($loan->loan_date)->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-gray-500">Due Date</p>
                <p class="font-medium">
                    {{ $loan->due_date ? \Carbon\Carbon::parse($loan->due_date)->format('Y-m-d') : '—' }}
                </p>
            </div>
            <div>
                <p class="text-gray-500">Created By</p>
                <p class="font-medium">{{ $loan->user->name ?? 'System' }}</p>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="pt-4">
            <div class="flex justify-between items-center mb-1 text-sm text-gray-600">
                <span>Repayment Progress</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </section>

    {{-- 👥 Related Parties --}}
    <section class="bg-white shadow rounded-xl p-6 border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Parties Involved</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-700">Customer</h4>
                @if($loan->customer)
                    <p class="text-sm text-gray-600 mt-1">{{ $loan->customer->name }}</p>
                    <p class="text-xs text-gray-400">{{ $loan->customer->email ?? '' }}</p>
                @else
                    <p class="text-sm text-gray-500 italic">— None —</p>
                @endif
            </div>
            <div>
                <h4 class="font-semibold text-gray-700">Supplier</h4>
                @if($loan->supplier)
                    <p class="text-sm text-gray-600 mt-1">{{ $loan->supplier->name }}</p>
                    <p class="text-xs text-gray-400">{{ $loan->supplier->email ?? '' }}</p>
                @else
                    <p class="text-sm text-gray-500 italic">— None —</p>
                @endif
            </div>
        </div>
    </section>

    {{-- 🔗 Linked Transaction (Sale or Purchase) --}}
    @if($loan->sale || $loan->purchase)
        <section class="bg-white shadow rounded-xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Linked Record</h3>
            @if($loan->sale)
                <p class="text-sm text-gray-700">
                    This loan is linked to <strong>Sale #{{ $loan->sale->id }}</strong>.
                </p>
                <a href="{{ route('sales.show', $loan->sale) }}" class="text-indigo-600 text-sm hover:underline">
                    View Sale Details →
                </a>
            @elseif($loan->purchase)
                <p class="text-sm text-gray-700">
                    This loan is linked to <strong>Purchase #{{ $loan->purchase->id }}</strong>.
                </p>
                <a href="{{ route('purchases.show', $loan->purchase) }}" class="text-indigo-600 text-sm hover:underline">
                    View Purchase Details →
                </a>
            @endif
        </section>
    @endif

    {{-- 💵 Payment History --}}
    <section class="bg-white shadow rounded-xl p-6 border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Payment History</h3>

            @if($loan->status !== 'paid')
                <a href="{{ route('loan-payments.create', $loan) }}" class="btn btn-success text-xs">
                    + Add Payment
                </a>
            @else
                <span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-md">
                    Loan fully paid
                </span>
            @endif
        </div>

        @if($loan->payments->count() > 0)
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                        <th class="px-4 py-2 text-left">Method</th>
                        <th class="px-4 py-2 text-left">Recorded By</th>
                        <th class="px-4 py-2 text-left">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($loan->payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                {{ \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-2 text-right text-green-700 font-semibold">
                                {{ number_format($payment->amount, 2) }}
                            </td>
                            <td class="px-4 py-2 capitalize">{{ $payment->method }}</td>
                            <td class="px-4 py-2">{{ $payment->user->name ?? 'System' }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $payment->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 flex justify-between text-sm text-gray-700 border-t pt-3">
                <p><strong>Total Paid:</strong> {{ number_format($paid, 2) }}</p>
                <p><strong>Remaining:</strong>
                    <span class="{{ $remaining <= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                        {{ number_format($remaining, 2) }}
                    </span>
                </p>
            </div>
        @else
            <p class="text-gray-500 italic">No payments recorded yet.</p>
        @endif
    </section>

    {{-- 📝 Notes --}}
    @if($loan->notes)
        <section class="bg-white shadow rounded-xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Notes</h3>
            <p class="text-gray-700 whitespace-pre-line">{{ $loan->notes }}</p>
        </section>
    @endif
</div>
@endsection
