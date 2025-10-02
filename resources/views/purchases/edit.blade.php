@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">Edit Purchase #{{ $purchase->id }}</h2>

    <form action="{{ route('purchases.update', $purchase->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block mb-1 font-medium">Supplier</label>
            <select name="supplier_id" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ $supplier->id == $purchase->supplier_id ? 'selected' : '' }}>
                    {{ $supplier->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Date</label>
            <input type="date" name="purchase_date" class="w-full border-gray-300 rounded-lg shadow-sm" value="{{ $purchase->purchase_date }}" required>
        </div>

        <h4 class="text-lg font-semibold">Products</h4>
        <div id="product-rows" class="space-y-2">
            @foreach($purchase->items as $i => $item)
            <div class="flex gap-2">
                <select name="products[{{ $i }}][product_id]" class="flex-1 border-gray-300 rounded-lg shadow-sm" required>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ $product->id == $item->product_id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                    @endforeach
                </select>
                <input type="number" name="products[{{ $i }}][quantity]" class="w-24 border-gray-300 rounded-lg shadow-sm" value="{{ $item->quantity }}" required>
                <input type="number" step="0.01" name="products[{{ $i }}][cost_price]" class="w-32 border-gray-300 rounded-lg shadow-sm" value="{{ $item->cost_price }}" required>
            </div>
            @endforeach
        </div>

        <button type="button" id="addRow" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Add Product</button>

        <div class="mt-4 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Update Purchase</button>
            <a href="{{ route('purchases.index') }}" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>

<script>
let rowCount = {{ count($purchase->items) }};
document.getElementById('addRow').addEventListener('click', function() {
    let container = document.getElementById('product-rows');
    let newRow = `
        <div class="flex gap-2 mt-2">
            <select name="products[${rowCount}][product_id]" class="flex-1 border-gray-300 rounded-lg shadow-sm" required>
                <option value="">-- Select Product --</option>
                @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <input type="number" name="products[${rowCount}][quantity]" class="w-24 border-gray-300 rounded-lg shadow-sm" placeholder="Qty" required>
            <input type="number" step="0.01" name="products[${rowCount}][cost_price]" class="w-32 border-gray-300 rounded-lg shadow-sm" placeholder="Cost" required>
        </div>`;
    container.insertAdjacentHTML('beforeend', newRow);
    rowCount++;
});
</script>
@endsection
