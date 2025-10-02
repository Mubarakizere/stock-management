@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">New Sale</h2>

    <form action="{{ route('sales.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block mb-1 font-medium">Customer (optional)</label>
            <select name="customer_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">Walk-in</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Date</label>
            <input type="date" name="sale_date" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        <h4 class="text-lg font-semibold">Products</h4>
        <div id="product-rows" class="space-y-2">
            <div class="flex gap-2">
                <select name="products[0][product_id]" class="flex-1 border-gray-300 rounded-lg shadow-sm" required>
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} (Stock: {{ $product->stock }})</option>
                    @endforeach
                </select>
                <input type="number" name="products[0][quantity]" class="w-24 border-gray-300 rounded-lg shadow-sm" placeholder="Qty" required>
                <input type="number" step="0.01" name="products[0][unit_price]" class="w-32 border-gray-300 rounded-lg shadow-sm" placeholder="Price" required>
            </div>
        </div>

        <button type="button" id="addRow" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Add Product</button>

        <div>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Save Sale</button>
            <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>

<script>
let rowCount = 1;
document.getElementById('addRow').addEventListener('click', function() {
    let container = document.getElementById('product-rows');
    let newRow = `
        <div class="flex gap-2 mt-2">
            <select name="products[${rowCount}][product_id]" class="flex-1 border-gray-300 rounded-lg shadow-sm" required>
                <option value="">-- Select Product --</option>
                @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }} (Stock: {{ $product->stock }})</option>
                @endforeach
            </select>
            <input type="number" name="products[${rowCount}][quantity]" class="w-24 border-gray-300 rounded-lg shadow-sm" placeholder="Qty" required>
            <input type="number" step="0.01" name="products[${rowCount}][unit_price]" class="w-32 border-gray-300 rounded-lg shadow-sm" placeholder="Price" required>
        </div>`;
    container.insertAdjacentHTML('beforeend', newRow);
    rowCount++;
});
</script>
@endsection
