@extends('layouts.app')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Add New Entry</h1>

    {{-- Flash messages --}}
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded-lg">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('debits-credits.store') }}" method="POST" class="space-y-4 bg-white p-6 rounded-lg shadow">
        @csrf

        <div>
            <label class="block font-semibold mb-1">Type</label>
            <select name="type" class="form-input" required>
                <option value="">Select type</option>
                <option value="debit" {{ old('type')=='debit'?'selected':'' }}>Debit (Money Out)</option>
                <option value="credit" {{ old('type')=='credit'?'selected':'' }}>Credit (Money In)</option>
            </select>
        </div>

        <div>
            <label class="block font-semibold mb-1">Amount</label>
            <input type="number" name="amount" step="0.01" value="{{ old('amount') }}"
                   class="form-input" placeholder="Enter amount" required>
        </div>

        <div>
            <label class="block font-semibold mb-1">Date</label>
            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                   class="form-input">
        </div>

        <div>
            <label class="block font-semibold mb-1">Description</label>
            <textarea name="description" class="form-input" rows="2" placeholder="Optional note...">{{ old('description') }}</textarea>
        </div>

        <div class="flex justify-between pt-4">
            <a href="{{ route('debits-credits.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
