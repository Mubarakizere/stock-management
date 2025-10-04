@extends('layouts.app')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Edit Entry</h1>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('debits-credits.update', $entry->id) }}" method="POST" class="space-y-4 bg-white p-6 rounded-lg shadow">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-semibold mb-1">Type</label>
            <select name="type" class="w-full border rounded-lg p-2" required>
                <option value="debit" {{ $entry->type=='debit'?'selected':'' }}>Debit (Money Out)</option>
                <option value="credit" {{ $entry->type=='credit'?'selected':'' }}>Credit (Money In)</option>
            </select>
        </div>

        <div>
            <label class="block font-semibold mb-1">Amount</label>
            <input type="number" name="amount" step="0.01" value="{{ old('amount', $entry->amount) }}"
                   class="w-full border rounded-lg p-2" required>
        </div>

        <div>
            <label class="block font-semibold mb-1">Date</label>
            <input type="date" name="date" value="{{ old('date', $entry->date) }}"
                   class="w-full border rounded-lg p-2">
        </div>

        <div>
            <label class="block font-semibold mb-1">Description</label>
            <textarea name="description" class="w-full border rounded-lg p-2" rows="2">{{ old('description', $entry->description) }}</textarea>
        </div>

        <div class="flex justify-between pt-4">
            <a href="{{ route('debits-credits.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update</button>
        </div>
    </form>
</div>
@endsection
