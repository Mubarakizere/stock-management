@extends('layouts.app')

@section('content')
<div class="card max-w-lg mx-auto">
    <h2 class="text-lg font-semibold mb-4">Add Product</h2>

    <form action="{{ route('products.store') }}" method="POST">
        @csrf

        {{-- Product Name --}}
        <div class="form-group">
            <label class="form-label" for="name">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required class="form-input">
            @error('name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Category --}}
        <div class="form-group">
            <label class="form-label" for="category_id">Category</label>
            <select id="category_id" name="category_id" class="form-select" required>
                <option value="">Select category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Selling Price --}}
        <div class="form-group">
            <label class="form-label" for="price">Selling Price (RWF)</label>
            <input id="price" type="number" step="0.01" name="price"
                   value="{{ old('price') }}" required class="form-input">
            @error('price')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Hidden stock field (always starts at 0) --}}
        <input type="hidden" name="stock" value="0">

        <div class="flex justify-end space-x-2 mt-6">
            <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
