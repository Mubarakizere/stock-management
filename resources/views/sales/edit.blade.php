@extends('layouts.app')
@section('title', 'Edit Sale')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Edit Sale #{{ $sale->id }}</h1>

    <form action="{{ route('sales.update', $sale) }}" method="POST" x-data="saleForm()" x-init="init()">
        @csrf
        @method('PUT')

        {{-- Customer and sale info --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Customer</label>
                <select name="customer_id" class="mt-1 w-full rounded-lg border-gray-300">
                    <option value="">Walk-in</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" @selected($sale->customer_id == $c->id)>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Sale Date</label>
                <input type="date" name="sale_date" value="{{ $sale->sale_date->format('Y-m-d') }}"
                    class="mt-1 w-full rounded-lg border-gray-300" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                <input type="text" name="method" value="{{ $sale->method }}"
                    class="mt-1 w-full rounded-lg border-gray-300">
            </div>
        </div>

        {{-- Product items --}}
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h3 class="font-medium text-gray-800">Products</h3>
                <button type="button" class="btn btn-outline text-sm" @click="addLine()">+ Add Item</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(row, idx) in lines" :key="row.key">
                            <tr>
                                <td class="px-4 py-2">
                                    <select :name="`products[${idx}][product_id]`"
                                        x-model.number="row.product_id"
                                        @change="onProductChange(row, $event)"
                                        class="w-full rounded-lg border-gray-300">
                                        <option value="">Select product</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.01" min="0.01"
                                        class="w-24 rounded-lg border-gray-300 text-right"
                                        x-model.number="row.quantity"
                                        :name="`products[${idx}][quantity]`"
                                        @input="recalc()">
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.01" min="0"
                                        class="w-28 rounded-lg border-gray-300 text-right"
                                        x-model.number="row.unit_price"
                                        :name="`products[${idx}][unit_price]`"
                                        @input="recalc()">
                                </td>
                                <td class="px-4 py-2 text-right text-gray-800 font-medium">
                                    <input type="hidden" :name="`products[${idx}][subtotal]`" :value="(row.quantity * row.unit_price).toFixed(2)">
                                    <span x-text="formatMoney(row.quantity * row.unit_price)"></span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <button type="button" class="btn btn-danger text-xs" @click="removeLine(idx)">Remove</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Payment + notes --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border-gray-300">{{ $sale->notes }}</textarea>
            </div>

            <div class="bg-white shadow rounded-lg p-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span x-text="formatMoney(total)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <label for="amount_paid">Amount Paid</label>
                    <input type="number" step="0.01" min="0" id="amount_paid"
                        name="amount_paid"
                        class="w-32 rounded-lg border-gray-300 text-right"
                        x-model.number="paid"
                        @input="recalc()">
                </div>
                <div class="flex justify-between font-semibold">
                    <span>Balance</span>
                    <span x-text="formatMoney(Math.max(total - paid, 0))"></span>
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Sale</button>
                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- AlpineJS --}}
<script>
function saleForm(){
    return {
        lines: [],
        total: 0,
        paid: Number('{{ $sale->amount_paid }}'),
        init(){
            this.lines = @json($sale->items->map(fn($i) => [
                'key' => uniqid(),
                'product_id' => (int)$i->product_id,
                'quantity' => Number($i->quantity),
                'unit_price' => Number($i->unit_price),
            ]));
            this.recalc();
        },
        addLine(){
            this.lines.push({ key: Date.now()+Math.random(), product_id: '', quantity: 1, unit_price: 0 });
        },
        removeLine(i){
            this.lines.splice(i,1);
            this.recalc();
        },
        onProductChange(row, event){
            const select = event.target;
            const price = Number(select.options[select.selectedIndex]?.dataset?.price || 0);
            if(price > 0 && (!row.unit_price || row.unit_price === 0)){
                row.unit_price = price;
            }
            this.recalc();
        },
        recalc(){
            this.total = this.lines.reduce((sum, r) => sum + (Number(r.quantity) * Number(r.unit_price)), 0);
        },
        formatMoney(v){
            return Number(v || 0).toFixed(2);
        }
    }
}
</script>
@endsection
