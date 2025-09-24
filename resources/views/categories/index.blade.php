@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-bold">Categories</h2>
    <a href="{{ route('categories.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">+ Add Category</a>
</div>

<table class="w-full border-collapse bg-white rounded shadow">
    <thead>
        <tr class="bg-gray-100 text-left">
            <th class="p-3 border">ID</th>
            <th class="p-3 border">Name</th>
            <th class="p-3 border">Description</th>
            <th class="p-3 border">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($categories as $category)
            <tr class="hover:bg-gray-50">
                <td class="p-3 border">{{ $category->id }}</td>
                <td class="p-3 border">{{ $category->name }}</td>
                <td class="p-3 border">{{ $category->description }}</td>
                <td class="p-3 border">
                    <a href="{{ route('categories.edit', $category) }}" class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">Edit</a>
                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Are you sure?')" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="p-3 text-center text-gray-500">No categories available.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
