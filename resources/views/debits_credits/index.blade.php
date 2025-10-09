@extends('layouts.app')

@section('content')
<div class="p-6">
    {{-- 🔔 Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded-lg">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 🧾 Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Debits & Credits</h1>
        <a href="{{ route('debits-credits.create') }}" class="btn-primary">+ New Entry</a>
    </div>

    {{-- 💰 Totals --}}
    @php
        $netColor = $net > 0 ? 'text-green-700' : ($net < 0 ? 'text-red-700' : 'text-gray-800');
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-500 font-semibold">Total Debits</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($debitsTotal, 2) }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-sm text-green-500 font-semibold">Total Credits</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($creditsTotal, 2) }}</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-sm text-gray-500 font-semibold">Net Balance</p>
            <p class="text-2xl font-bold {{ $netColor }}">{{ number_format($net, 2) }}</p>
        </div>
    </div>

    {{-- 🔍 Filters --}}
    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
        <select name="type" class="form-input">
            <option value="">All Types</option>
            <option value="debit" {{ request('type')=='debit'?'selected':'' }}>Debits</option>
            <option value="credit" {{ request('type')=='credit'?'selected':'' }}>Credits</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="form-input" placeholder="From">
        <input type="date" name="to" value="{{ request('to') }}" class="form-input" placeholder="To">
        <input type="text" name="q" value="{{ request('q') }}" class="form-input" placeholder="Search...">
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    {{-- 📋 Table --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full border border-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-600 font-semibold">
                <tr>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-right">Amount</th>
                    <th class="p-3 text-left">Description</th>
                    <th class="p-3 text-left">Customer</th>
                    <th class="p-3 text-left">Supplier</th>
                    <th class="p-3 text-left">User</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $item)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3">{{ $item->date }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs font-semibold
                                {{ $item->type == 'debit' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                {{ ucfirst($item->type) }}
                            </span>
                        </td>
                        <td class="p-3 text-right font-semibold">{{ number_format($item->amount, 2) }}</td>
                        <td class="p-3">{{ $item->description ?? '-' }}</td>
                        <td class="p-3">{{ $item->customer->name ?? '-' }}</td>
                        <td class="p-3">{{ $item->supplier->name ?? '-' }}</td>
                        <td class="p-3">{{ $item->user->name ?? '-' }}</td>
                        <td class="p-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('debits-credits.edit', $item->id) }}" class="text-blue-600 hover:underline">Edit</a>
                                <form action="{{ route('debits-credits.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this entry?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4 text-center text-gray-500">No records found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</div>
@endsection
