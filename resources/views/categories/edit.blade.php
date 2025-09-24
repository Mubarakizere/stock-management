@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Edit Category</h2>

    <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-medium text-gray-700">Name</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                class="w-full mt-1 p-2 border rounded focus:ring focus:ring-indigo-200">
            @error('name')
                <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block font-medium text-gray-700">Description</label>
            <textarea name="description" rows="3"
                class="w-full mt-1 p-2 border rounded focus:ring focus:ring-indigo-200">{{ old('description', $category->description) }}</textarea>
            @error('description')
                <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('categories.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Update</button>
        </div>
    </form>
</div>
@endsection
