@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">New Sale</h2>

    <form action="{{ route('sales.store') }}" method="POST" class="space-y-4">
        @csrf

        {{-- Customer --}}
        <div>
            <label class="block mb-1 font-medium">Customer (optional)</label>
            <select name="customer_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- Walk-in Customer --</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Sale Date --}}
        <div>
            <label class="block mb-1 font-medium">Date</label>
            <input type="date" name="sale_date" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        {{-- Products Section --}}
        <h4 class="text-lg font-semibold mt-4">Products</h4>

        <div id="product-rows" class="space-y-2">
            <div class="flex gap-2 items-center">
                <select name="products[0][product_id]" class="flex-1 border-gray-300 rounded-lg shadow-sm" required>
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <input type="number" name="products[0][quantity]" class="w-24 border-gray-300 rounded-lg shadow-sm" placeholder="Qty" min="1" required>
                <input type="number" step="0.01" name="products[0][unit_price]" class="w-32 border-gray-300 rounded-lg shadow-sm" placeholder="Unit Price" required>
                <button type="button" class="remove-row text-red-500 hover:text-red-700 hidden">✖</button>
            </div>
        </div>

        <button type="button" id="addRow" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Add Product</button>

        {{-- Amount Paid / Method --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <div>
                <label class="block mb-1 font-medium">Amount Paid</label>
                <input type="number" step="0.01" name="amount_paid" class="w-full border-gray-300 rounded-lg shadow-sm" placeholder="0.00">
            </div>

            <div>
                <label class="block mb-1 font-medium">Payment Method</label>
                <select name="method" class="w-full border-gray-300 rounded-lg shadow-sm">
                    <option value="cash">Cash</option>
                    <option value="bank">Bank</option>
                    <option value="momo">Mobile Money</option>
                    <option value="card">Card</option>
                </select>
            </div>
        </div>

        {{-- Notes --}}
        <div>
            <label class="block mb-1 font-medium">Notes (optional)</label>
            <textarea name="notes" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm"></textarea>
        </div>

        {{-- Buttons --}}
        <div class="flex justify-between items-center mt-6">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Save Sale
            </button>
            <a href="{{ route('sales.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Cancel
            </a>
        </div>
    </form>
</div>

{{-- JavaScript for Dynamic Rows --}}
<script>
    let rowCount = 1;

    document.getElementById('addRow').addEventListener('click', function() {
        let container = document.getElementById('product-rows');
        let newRow = `
            <div class="flex gap-2 items-center mt-2">
                <select name="products[${rowCount}][product_id]" class="flex-1 border-gray-300 rounded-lg shadow-sm" required>
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <input type="number" name="products[${rowCount}][quantity]" class="w-24 border-gray-300 rounded-lg shadow-sm" placeholder="Qty" min="1" required>
                <input type="number" step="0.01" name="products[${rowCount}][unit_price]" class="w-32 border-gray-300 rounded-lg shadow-sm" placeholder="Unit Price" required>
                <button type="button" class="remove-row text-red-500 hover:text-red-700">✖</button>
            </div>`;
        container.insertAdjacentHTML('beforeend', newRow);
        rowCount++;
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.parentElement.remove();
        }
    });
</script>
@endsection
