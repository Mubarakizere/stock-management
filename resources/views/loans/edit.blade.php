<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-indigo-600">Edit Loan #{{ $loan->id }}</h2>
            <a href="{{ route('loans.index') }}"
               class="text-sm px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700">
                ← Back to Loans
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto mt-8 bg-white shadow rounded-xl p-8 border border-gray-100">
        @if ($errors->any())
            <div class="mb-6 bg-red-50 text-red-600 px-4 py-3 rounded-md">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('loans.update', $loan) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- 🔹 Loan Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loan Type</label>
                <select name="type" required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="given" {{ $loan->type === 'given' ? 'selected' : '' }}>Given (We lent money)</option>
                    <option value="taken" {{ $loan->type === 'taken' ? 'selected' : '' }}>Taken (We borrowed money)</option>
                </select>
            </div>

            {{-- 🔹 Amount --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount', $loan->amount) }}" required
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- 🔹 Loan Date & Due Date --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loan Date</label>
                    <input type="date" name="loan_date" value="{{ old('loan_date', $loan->loan_date) }}" required
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $loan->due_date) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            {{-- 🔹 Customer & Supplier --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                    <select name="customer_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— None —</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" {{ $loan->customer_id == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <select name="supplier_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— None —</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $loan->supplier_id == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- 🔹 Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="pending" {{ $loan->status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ $loan->status === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>

            {{-- 🔹 Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3"
                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Optional notes...">{{ old('notes', $loan->notes) }}</textarea>
            </div>

            {{-- 🔘 Action Buttons --}}
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('loans.index') }}"
                   class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md">Cancel</a>

                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 shadow">
                    Update Loan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
