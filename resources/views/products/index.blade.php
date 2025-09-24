@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Products</h2>
    <a href="{{ route('products.create') }}"
       class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">+ Add Product</a>
</div>

<table class="min-w-full bg-white border">
    <thead>
        <tr class="bg-gray-200">
            <th class="px-4 py-2 border">Name</th>
            <th class="px-4 py-2 border">Category</th>
            <th class="px-4 py-2 border">Price</th>
            <th class="px-4 py-2 border">Stock</th>
            <th class="px-4 py-2 border">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($products as $product)
            <tr>
                <td class="px-4 py-2 border">{{ $product->name }}</td>
                <td class="px-4 py-2 border">{{ $product->category->name }}</td>
                <td class="px-4 py-2 border">{{ $product->price }}</td>
                <td class="px-4 py-2 border">{{ $product->stock }}</td>
                <td class="px-4 py-2 border space-x-2">
                    <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:underline">Edit</a>
                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Delete this product?')" class="text-red-600 hover:underline">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
