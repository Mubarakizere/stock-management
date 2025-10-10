<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-indigo-600">
                Loan Details #{{ $loan->id }}
            </h2>
            <a href="{{ route('loans.index') }}"
               class="text-sm px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700">
                ← Back to Loans
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto mt-8 space-y-8">

        {{-- ✅ Loan Summary --}}
        <section class="bg-white shadow rounded-xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Loan Summary</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Loan Type</p>
                    <p class="font-medium">{{ ucfirst($loan->type) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Amount</p>
                    <p class="font-bold text-indigo-600">{{ number_format($loan->amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold
                        {{ $loan->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ ucfirst($loan->status) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Loan Date</p>
                    <p class="font-medium">{{ \Carbon\Carbon::parse($loan->loan_date)->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Due Date</p>
                    <p class="font-medium">
                        {{ $loan->due_date ? \Carbon\Carbon::parse($loan->due_date)->format('d M Y') : '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Created By</p>
                    <p class="font-medium">{{ $loan->user->name ?? 'System' }}</p>
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

        {{-- 💵 Payment History --}}
        <section class="bg-white shadow rounded-xl p-6 border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Payment History</h3>
                <a href="{{ route('loan-payments.create', $loan->id) }}"
                   class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">
                    + Add Payment
                </a>
            </div>

            @if($loan->payments->count() > 0)
                <table class="w-full text-sm border-t border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2 text-left">Method</th>
                            <th class="px-4 py-2 text-left">Recorded By</th>
                            <th class="px-4 py-2 text-left">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loan->payments as $payment)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                                <td class="px-4 py-2 text-right text-green-600">{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-4 py-2">{{ ucfirst($payment->method) }}</td>
                                <td class="px-4 py-2">{{ $payment->user->name ?? 'System' }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $payment->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4 flex justify-between text-sm text-gray-700">
                    <p><strong>Total Paid:</strong> {{ number_format($totalPaid, 2) }}</p>
                    <p><strong>Remaining:</strong> {{ number_format($remaining, 2) }}</p>
                </div>
            @else
                <p class="text-gray-500 italic">No payments recorded yet.</p>
            @endif
        </section>

        {{-- 📝 Notes --}}
        @if($loan->notes)
            <section class="bg-white shadow rounded-xl p-6 border border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Notes</h3>
                <p class="text-gray-600 whitespace-pre-line">{{ $loan->notes }}</p>
            </section>
        @endif

        {{-- ⚙️ Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('loans.edit', $loan) }}"
               class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600">Edit</a>

            <form action="{{ route('loans.destroy', $loan) }}" method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this loan? This will remove related payments.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</button>
            </form>
        </div>
    </div>
</x-app-layout>
