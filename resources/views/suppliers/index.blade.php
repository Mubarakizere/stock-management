@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-bold">Suppliers</h2>
    <a href="{{ route('suppliers.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded">+ Add Supplier</a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

<table class="w-full bg-white shadow rounded">
    <thead>
        <tr class="bg-gray-100">
            <th class="p-2">Name</th>
            <th class="p-2">Email</th>
            <th class="p-2">Phone</th>
            <th class="p-2">Address</th>
            <th class="p-2">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($suppliers as $supplier)
            <tr class="border-t">
                <td class="p-2">{{ $supplier->name }}</td>
                <td class="p-2">{{ $supplier->email }}</td>
                <td class="p-2">{{ $supplier->phone }}</td>
                <td class="p-2">{{ $supplier->address }}</td>
                <td class="p-2">
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="px-3 py-1 bg-yellow-500 text-white rounded">Edit</a>
                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Are you sure?')" class="px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="p-3 text-center text-gray-500">No suppliers yet.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
