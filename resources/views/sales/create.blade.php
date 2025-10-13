@extends('layouts.app')
@section('title', 'New Sale')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    <h1 class="text-2xl font-semibold text-gray-800 mb-4">New Sale</h1>

    {{-- ✅ Success message --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    {{-- ⚠️ Error messages --}}
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 space-y-1">
            <strong class="block font-semibold">⚠️ Error:</strong>
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 🧾 Sale Form --}}
    <form action="{{ route('sales.store') }}" method="POST" x-data="saleForm()" x-init="init()">
        @csrf

        {{-- 🔹 Sale Info --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Customer</label>
                <select name="customer_id" class="mt-1 w-full rounded-lg border-gray-300">
                    <option value="">Walk-in</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Sale Date</label>
                <input type="date" name="sale_date"
                       value="{{ old('sale_date', now()->format('Y-m-d')) }}"
                       class="mt-1 w-full rounded-lg border-gray-300"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                <input type="text" name="method"
                       value="{{ old('method', 'cash') }}"
                       placeholder="cash / momo / bank"
                       class="mt-1 w-full rounded-lg border-gray-300"
                       required>
            </div>
        </div>

        {{-- 🔹 Product Items --}}
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h3 class="font-medium text-gray-800">Products</h3>
                <button type="button" class="btn btn-outline text-sm" @click="addLine()">+ Add Item</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Product</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-600 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-600 uppercase tracking-wider">Unit Price</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-600 uppercase tracking-wider">Subtotal</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <template x-for="(row, idx) in lines" :key="row.key">
                            <tr>
                                <td class="px-4 py-2">
                                    <select :name="`products[${idx}][product_id]`"
                                            x-model.number="row.product_id"
                                            @change="onProductChange(row, $event)"
                                            class="w-full rounded-lg border-gray-300">
                                        <option value="">Select product</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" data-price="{{ $p->price }}">
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.01" min="0.01"
                                           class="w-20 rounded-lg border-gray-300 text-right"
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

                                <td class="px-4 py-2 text-right font-medium text-gray-800">
                                    <input type="hidden" :name="`products[${idx}][subtotal]`"
                                           :value="(row.quantity * row.unit_price).toFixed(2)">
                                    <span x-text="formatMoney(row.quantity * row.unit_price)"></span>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <button type="button"
                                            class="btn btn-danger text-xs"
                                            @click="removeLine(idx)">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 🔹 Payment + Notes --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" rows="3"
                          class="mt-1 w-full rounded-lg border-gray-300"
                          placeholder="Any remarks...">{{ old('notes') }}</textarea>
            </div>

            <div class="bg-white shadow rounded-lg p-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-medium text-gray-900" x-text="formatMoney(total)"></span>
                </div>

                <div class="flex justify-between text-sm items-center">
                    <label for="amount_paid" class="text-gray-700 font-medium">Amount Paid</label>
                    <input type="number" step="0.01" min="0" id="amount_paid"
                           name="amount_paid"
                           value="{{ old('amount_paid', 0) }}"
                           class="w-36 rounded-lg border-gray-300 text-right"
                           x-model.number="paid"
                           required
                           @input="recalc()">
                </div>

                <div class="flex justify-between font-semibold text-gray-800">
                    <span>Balance</span>
                    <span x-text="formatMoney(Math.max(total - paid, 0))"></span>
                </div>

                <div class="pt-3 flex gap-2">
                    <button type="submit" class="btn btn-primary w-full">Save Sale</button>
                    <a href="{{ route('sales.index') }}" class="btn btn-secondary w-full">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- 🔸 Alpine.js Logic --}}
<script>
function saleForm(){
    return {
        lines: [],
        total: 0,
        paid: Number('{{ old('amount_paid', 0) }}'),

        init(){
            // Always start with one blank valid line
            this.addLine();
            this.recalc();
        },

        addLine(){
            this.lines.push({
                key: crypto.randomUUID?.() || Date.now() + Math.random(),
                product_id: '',
                quantity: 1,
                unit_price: 0
            });
        },

        removeLine(i){
            this.lines.splice(i, 1);
            this.recalc();
        },

        onProductChange(row, event){
            const select = event.target;
            const price = Number(select.options[select.selectedIndex]?.dataset?.price || 0);
            if (price > 0 && (!row.unit_price || row.unit_price === 0)) {
                row.unit_price = price;
            }
            this.recalc();
        },

        recalc(){
            // sum only valid lines
            this.total = this.lines.reduce((sum, r) => {
                const qty = Number(r.quantity) || 0;
                const price = Number(r.unit_price) || 0;
                return sum + (qty * price);
            }, 0);
        },

        formatMoney(v){
            return Number(v || 0).toFixed(2);
        }
    }
}
</script>
@endsection
