@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">Edit Sale #{{ $sale->id }}</h2>

    <form action="{{ route('sales.update', $sale->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        {{-- Customer --}}
        <div>
            <label class="block mb-1 font-medium">Customer</label>
            <select name="customer_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="">-- Walk-in Customer --</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ $customer->id == $sale->customer_id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Date --}}
        <div>
            <label class="block mb-1 font-medium">Date</label>
            <input type="date" name="sale_date" value="{{ $sale->sale_date }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        </div>

        {{-- Products --}}
        <h4 class="text-lg font-semibold mt-4">Products</h4>
        <div id="product-rows" class="space-y-2">
            @foreach($sale->items as $i => $item)
            <div class="flex gap-2 items-center">
                <select name="products[{{ $i }}][product_id]" class="flex-1 border-gray-300 rounded-lg shadow-sm" required>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ $product->id == $item->product_id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                <input type="number" name="products[{{ $i }}][quantity]" value="{{ $item->quantity }}" class="w-24 border-gray-300 rounded-lg shadow-sm" required>
                <input type="number" step="0.01" name="products[{{ $i }}][unit_price]" value="{{ $item->unit_price }}" class="w-32 border-gray-300 rounded-lg shadow-sm" required>
                <button type="button" class="remove-row text-red-500 hover:text-red-700 hidden">✖</button>
            </div>
            @endforeach
        </div>

        <button type="button" id="addRow" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Add Product</button>

        {{-- Amount + Method --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <div>
                <label class="block mb-1 font-medium">Amount Paid</label>
                <input type="number" step="0.01" name="amount_paid" value="{{ $sale->amount_paid ?? 0 }}" class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>
            <div>
                <label class="block mb-1 font-medium">Payment Method</label>
                <select name="method" class="w-full border-gray-300 rounded-lg shadow-sm">
                    <option value="cash" {{ $sale->method == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank" {{ $sale->method == 'bank' ? 'selected' : '' }}>Bank</option>
                    <option value="momo" {{ $sale->method == 'momo' ? 'selected' : '' }}>Mobile Money</option>
                    <option value="card" {{ $sale->method == 'card' ? 'selected' : '' }}>Card</option>
                </select>
            </div>
        </div>

        {{-- Notes --}}
        <div>
            <label class="block mb-1 font-medium">Notes</label>
            <textarea name="notes" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm">{{ $sale->notes }}</textarea>
        </div>

        {{-- Submit --}}
        <div class="flex justify-between items-center mt-6">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Update Sale
            </button>
            <a href="{{ route('sales.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Cancel
            </a>
        </div>
    </form>
</div>

{{-- JS for adding/removing rows --}}
<script>
    let rowCount = {{ count($sale->items) }};

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
                <input type="number" name="products[${rowCount}][quantity]" class="w-24 border-gray-300 rounded-lg shadow-sm" placeholder="Qty" required>
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
