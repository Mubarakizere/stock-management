@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Create New Loan</h1>
        <a href="{{ route('loans.index') }}"
           class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
           ← Back to Loans
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg">
            <strong>Whoops!</strong> There were some problems with your input.
            <ul class="list-disc ml-5 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('loans.store') }}" class="bg-white shadow rounded-lg p-6 space-y-6">
        @csrf

        {{-- Loan Type --}}
        <div>
            <label class="block font-semibold text-gray-700 mb-1">Loan Type <span class="text-red-500">*</span></label>
            <select name="type" id="loan_type" class="form-select w-full border-gray-300 rounded-lg">
                <option value="">Select Type</option>
                <option value="given" {{ old('type') === 'given' ? 'selected' : '' }}>Given (We lend / customer owes us)</option>
                <option value="taken" {{ old('type') === 'taken' ? 'selected' : '' }}>Taken (We owe supplier)</option>
            </select>
        </div>

        {{-- Customer --}}
        <div id="customer_field" class="hidden">
            <label class="block font-semibold text-gray-700 mb-1">Customer</label>
            <select name="customer_id" class="form-select w-full border-gray-300 rounded-lg">
                <option value="">Select Customer</option>
                @foreach ($customers as $c)
                    <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Supplier --}}
        <div id="supplier_field" class="hidden">
            <label class="block font-semibold text-gray-700 mb-1">Supplier</label>
            <select name="supplier_id" class="form-select w-full border-gray-300 rounded-lg">
                <option value="">Select Supplier</option>
                @foreach ($suppliers as $s)
                    <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Amount --}}
        <div>
            <label class="block font-semibold text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" name="amount"
                   class="form-input w-full border-gray-300 rounded-lg"
                   value="{{ old('amount') }}" required>
        </div>

        {{-- Loan Date --}}
        <div>
            <label class="block font-semibold text-gray-700 mb-1">Loan Date <span class="text-red-500">*</span></label>
            <input type="date" name="loan_date" class="form-input w-full border-gray-300 rounded-lg"
                   value="{{ old('loan_date', date('Y-m-d')) }}" required>
        </div>

        {{-- Due Date --}}
        <div>
            <label class="block font-semibold text-gray-700 mb-1">Due Date</label>
            <input type="date" name="due_date" class="form-input w-full border-gray-300 rounded-lg"
                   value="{{ old('due_date') }}">
        </div>

        {{-- Status --}}
        <div>
            <label class="block font-semibold text-gray-700 mb-1">Status</label>
            <select name="status" class="form-select w-full border-gray-300 rounded-lg">
                <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="paid" {{ old('status') === 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>

        {{-- Notes --}}
        <div>
            <label class="block font-semibold text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="3" class="form-textarea w-full border-gray-300 rounded-lg"
                      placeholder="Optional...">{{ old('notes') }}</textarea>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end pt-4">
            <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                Save Loan
            </button>
        </div>
    </form>
</div>

{{-- JS Logic to toggle Customer/Supplier --}}
<script>
    const typeSelect = document.getElementById('loan_type');
    const customerField = document.getElementById('customer_field');
    const supplierField = document.getElementById('supplier_field');

    function toggleFields() {
        const value = typeSelect.value;
        customerField.classList.add('hidden');
        supplierField.classList.add('hidden');
        if (value === 'given') customerField.classList.remove('hidden');
        if (value === 'taken') supplierField.classList.remove('hidden');
    }

    typeSelect.addEventListener('change', toggleFields);
    document.addEventListener('DOMContentLoaded', toggleFields);
</script>
@endsection
