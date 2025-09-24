@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Edit Product</h2>

    <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-medium">Name</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full border rounded p-2">
        </div>

        <div>
            <label class="block font-medium">Category</label>
            <select name="category_id" class="w-full border rounded p-2">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @if($product->category_id == $category->id) selected @endif>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-medium">Price</label>
            <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required class="w-full border rounded p-2">
        </div>

        <div>
            <label class="block font-medium">Stock</label>
            <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" required class="w-full border rounded p-2">
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('products.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Update</button>
        </div>
    </form>
</div>
@endsection
