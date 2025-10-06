@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4">
    <h2 class="text-2xl font-bold mb-6">New Purchase</h2>

    <form action="{{ route('purchases.store') }}" method="POST" class="space-y-6 bg-white p-6 rounded-lg shadow">
        @csrf

        {{-- Supplier & Date --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-medium">Supplier</label>
                <select name="supplier_id" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                    <option value="">-- Select Supplier --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block mb-1 font-medium">Date</label>
                <input type="date" name="purchase_date" class="w-full border-gray-300 rounded-lg shadow-sm" required>
            </div>
        </div>

        {{-- Product Rows --}}
        <div>
            <h4 class="text-lg font-semibold mb-2">Products</h4>
            <div id="product-rows" class="space-y-2">
                <div class="flex gap-2">
                    <select name="products[0][product_id]" class="flex-1 border-gray-300 rounded-lg shadow-sm" required>
                        <option value="">-- Select Product --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="products[0][quantity]" class="w-24 border-gray-300 rounded-lg shadow-sm" placeholder="Qty" required>
                    <input type="number" step="0.01" name="products[0][cost_price]" class="w-32 border-gray-300 rounded-lg shadow-sm" placeholder="Unit Cost" required>
                    <input type="text" class="w-32 border-gray-200 rounded-lg bg-gray-100 text-gray-600 text-center subtotal-field" value="0.00" readonly>
                </div>
            </div>

            <button type="button" id="addRow" class="mt-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Add Product</button>
        </div>

        {{-- Totals --}}
        <div class="border-t pt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block mb-1 font-medium">Tax (optional)</label>
                <input type="number" step="0.01" name="tax" id="tax" class="w-full border-gray-300 rounded-lg shadow-sm" value="0">
            </div>
            <div>
                <label class="block mb-1 font-medium">Discount (optional)</label>
                <input type="number" step="0.01" name="discount" id="discount" class="w-full border-gray-300 rounded-lg shadow-sm" value="0">
            </div>
            <div>
                <label class="block mb-1 font-medium">Amount Paid</label>
                <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="w-full border-gray-300 rounded-lg shadow-sm" value="0">
            </div>
        </div>

        {{-- Payment Method --}}
        <div>
            <label class="block mb-1 font-medium">Payment Method</label>
            <select name="method" class="w-full border-gray-300 rounded-lg shadow-sm">
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="momo">Mobile Money</option>
            </select>
        </div>

        {{-- Notes --}}
        <div>
            <label class="block mb-1 font-medium">Notes (optional)</label>
            <textarea name="notes" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm"></textarea>
        </div>

        {{-- Summary --}}
        <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center">
            <h3 class="text-lg font-semibold">Total Amount:</h3>
            <span id="total-display" class="text-2xl font-bold text-green-700">0.00</span>
            <input type="hidden" id="total-amount" name="total_amount">
        </div>

        {{-- Actions --}}
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Save Purchase</button>
            <a href="{{ route('purchases.index') }}" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>

{{-- JS: Dynamic Rows & Auto Totals --}}
<script>
let rowCount = 1;
document.getElementById('addRow').addEventListener('click', () => {
    const container = document.getElementById('product-rows');
    const newRow = `
        <div class="flex gap-2 mt-2">
            <select name="products[${rowCount}][product_id]" class="flex-1 border-gray-300 rounded-lg shadow-sm" required>
                <option value="">-- Select Product --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <input type="number" name="products[${rowCount}][quantity]" class="w-24 border-gray-300 rounded-lg shadow-sm qty-field" placeholder="Qty" required>
            <input type="number" step="0.01" name="products[${rowCount}][cost_price]" class="w-32 border-gray-300 rounded-lg shadow-sm cost-field" placeholder="Unit Cost" required>
            <input type="text" class="w-32 border-gray-200 rounded-lg bg-gray-100 text-gray-600 text-center subtotal-field" value="0.00" readonly>
        </div>`;
    container.insertAdjacentHTML('beforeend', newRow);
    rowCount++;
});

document.addEventListener('input', () => {
    let total = 0;
    document.querySelectorAll('#product-rows > div').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-field')?.value || 0);
        const cost = parseFloat(row.querySelector('.cost-field')?.value || 0);
        const subtotal = qty * cost;
        row.querySelector('.subtotal-field').value = subtotal.toFixed(2);
        total += subtotal;
    });
    const tax = parseFloat(document.getElementById('tax').value || 0);
    const discount = parseFloat(document.getElementById('discount').value || 0);
    total = total + tax - discount;
    document.getElementById('total-display').textContent = total.toFixed(2);
    document.getElementById('total-amount').value = total.toFixed(2);
});
</script>
@endsection
