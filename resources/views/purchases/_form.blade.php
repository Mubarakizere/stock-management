@php
    $isEdit = isset($purchase);

    // Guard against divide-by-zero
    $taxPct = $discountPct = 0;
    if ($isEdit && ($purchase->subtotal ?? 0) > 0) {
        $taxPct      = round((($purchase->tax ?? 0) / $purchase->subtotal) * 100, 2);
        $discountPct = round((($purchase->discount ?? 0) / $purchase->subtotal) * 100, 2);
    }

    // Initial line items
    $initialLines = collect(old('products',
        $isEdit
            ? $purchase->items->map(fn($i) => [
                'product_id' => (int)   $i->product_id,
                'name'       => $i->product ? $i->product->name : 'Product #'.$i->product_id,
                'quantity'   => (float) $i->quantity,
                'unit_cost'  => (float) $i->unit_cost,
            ])->values()->all()
            : []
    ));

    $initialState = [
        'supplier_id'     => old('supplier_id', $isEdit ? $purchase->supplier_id : ''),
        'purchase_date'   => old('purchase_date', $isEdit
            ? ($purchase->purchase_date?->format('Y-m-d') ?? now()->format('Y-m-d'))
            : now()->format('Y-m-d')),
        'payment_channel' => old('payment_channel', $isEdit ? ($purchase->payment_channel ?? 'cash') : 'cash'),
        'method'          => old('method',   $isEdit ? ($purchase->method ?? '')   : ''), // reference
        'notes'           => old('notes',    $isEdit ? ($purchase->notes  ?? '')   : ''),
        'tax'             => (float) old('tax',      $isEdit ? $taxPct      : 0),
        'discount'        => (float) old('discount', $isEdit ? $discountPct : 0),
        'amount_paid'     => (float) old('amount_paid', $isEdit ? ($purchase->amount_paid ?? 0) : 0),
        'lines'           => $initialLines->all(),
    ];
@endphp

<div x-data="purchaseForm(@js($initialState))" x-init="init()" class="space-y-6">
    
    {{-- Progress Indicator --}}
    <div class="mb-8 relative">
        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded-full bg-gray-200 dark:bg-gray-700">
            <div :style="`width: ${((step - 1) / 2) * 100}%`" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500 transition-all duration-300"></div>
        </div>
        <div class="flex justify-between text-xs font-semibold text-gray-500 dark:text-gray-400">
            <span :class="step >= 1 ? 'text-indigo-600 dark:text-indigo-400' : ''">1. Supplier Info</span>
            <span :class="step >= 2 ? 'text-indigo-600 dark:text-indigo-400' : ''">2. Select Materials</span>
            <span :class="step >= 3 ? 'text-indigo-600 dark:text-indigo-400' : ''">3. Review & Payment</span>
        </div>
    </div>

    {{-- STEP 1: Basic Info --}}
    <section x-show="step === 1" x-transition.opacity.duration.300ms class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-6 sm:p-8">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">Step 1: Choose Supplier</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-label value="Supplier *" class="text-lg mb-2" />
                    <select name="supplier_id" x-model="state.supplier_id" class="form-select text-lg py-3" required>
                        <option value="">-- Click to Select Supplier --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label value="Purchase Date *" class="text-lg mb-2" />
                    <input type="date" name="purchase_date" x-model="state.purchase_date" class="form-input text-lg py-3" required>
                    @error('purchase_date')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t dark:border-gray-700 mt-2">
                    <div>
                        <x-label value="Payment Method" />
                        <select name="payment_channel" x-model="state.payment_channel" class="form-select hidden">
                            @foreach($paymentChannels as $channel)
                                <option value="{{ $channel->slug }}">{{ $channel->name }}</option>
                            @endforeach
                        </select>
                        <div class="flex gap-3 mt-2 flex-wrap">
                            @foreach($paymentChannels as $channel)
                            <button type="button"
                                    @click="setChannel('{{ $channel->slug }}')"
                                    class="px-4 py-3 rounded-xl border text-sm font-semibold transition-colors flex-1 text-center"
                                    :class="state.payment_channel === '{{ $channel->slug }}' 
                                        ? 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-300 border-indigo-400 dark:border-indigo-500 shadow-inner' 
                                        : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700'">
                                {{ $channel->name }}
                            </button>
                            @endforeach
                        </div>
                        @error('payment_channel')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-label value="Reference / Invoice No. (Optional)" />
                        <input type="text" name="method" x-model="state.method" class="form-input py-3 mt-2" placeholder="e.g. INV-12345">
                        @error('method')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button @click="nextStep()" type="button" class="btn btn-primary px-8 py-3 text-lg rounded-xl flex items-center gap-2 shadow-lg hover:shadow-xl transition-all" :disabled="!state.supplier_id">
                Next: Select Materials <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </button>
        </div>
    </section>

    {{-- STEP 2: Raw Materials --}}
    <section x-show="step === 2" x-transition.opacity.duration.300ms style="display: none;" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-6 sm:p-8">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">Step 2: Add Raw Materials</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                {{-- Product Catalog --}}
                <div class="lg:col-span-5 flex flex-col h-[500px]">
                    <div class="mb-4 relative">
                        <i data-lucide="search" class="w-5 h-5 absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Search materials..." class="form-input pl-10 py-3 rounded-xl border-gray-300 dark:border-gray-600 w-full shadow-sm">
                    </div>
                    
                    <div class="flex-1 overflow-y-auto pr-2 space-y-2 border border-gray-200 dark:border-gray-700 rounded-xl p-2 bg-gray-50/50 dark:bg-gray-900/50">
                        @foreach($products as $p)
                            <div x-show="matchesSearch('{{ addslashes($p->name) }}')" class="flex justify-between items-center p-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors shadow-sm">
                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-100">{{ $p->name }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Cost: RWF {{ number_format($p->cost_price ?? 0, 2) }}</p>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm px-4 py-2 rounded-lg flex items-center gap-1" @click="addToCart({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $p->cost_price ?? 0 }})">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Add
                                </button>
                            </div>
                        @endforeach
                        <div x-show="searchQuery !== '' && !hasMatches()" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No materials found matching your search.
                        </div>
                    </div>
                </div>

                {{-- Cart / Selected Items --}}
                <div class="lg:col-span-7 flex flex-col h-[500px]">
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-xl p-4 flex-1 flex flex-col">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-lg text-indigo-900 dark:text-indigo-300 flex items-center gap-2">
                                <i data-lucide="shopping-bag" class="w-5 h-5"></i> Selected Items
                            </h3>
                            <span class="bg-indigo-200 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-200 text-xs px-2 py-1 rounded-full font-bold" x-text="state.lines.length + ' Items'"></span>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto pr-2 space-y-3">
                            <template x-for="(row, idx) in state.lines" :key="row.key">
                                <div class="bg-white dark:bg-gray-800 border border-indigo-100 dark:border-gray-700 rounded-xl p-4 shadow-sm relative group">
                                    {{-- Hidden inputs to submit form data properly --}}
                                    <input type="hidden" :name="`products[${idx}][product_id]`" :value="row.product_id">
                                    <input type="hidden" :name="`products[${idx}][total_cost]`" :value="lineTotal(row).toFixed(2)">
                                    
                                    <div class="flex justify-between items-start mb-3">
                                        <h4 class="font-bold text-gray-800 dark:text-gray-100 text-lg" x-text="row.name"></h4>
                                        <button type="button" class="text-rose-400 hover:text-rose-600 bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 p-1.5 rounded-lg transition-colors" @click="removeLine(idx)">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="flex gap-4 items-end">
                                        <div class="flex-1">
                                            <label class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider mb-1 block">Quantity</label>
                                            <input type="number" step="0.01" min="0.01" class="form-input font-bold text-lg py-2" x-model.number="row.quantity" :name="`products[${idx}][quantity]`" @input="recalc()">
                                        </div>
                                        <div class="flex-1">
                                            <label class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider mb-1 block">Unit Cost (RWF)</label>
                                            <input type="number" step="0.01" min="0" class="form-input font-bold text-lg py-2" x-model.number="row.unit_cost" :name="`products[${idx}][unit_cost]`" @input="recalc()">
                                        </div>
                                        <div class="flex-1 text-right bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                            <label class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider block">Line Total</label>
                                            <span class="font-black text-indigo-700 dark:text-indigo-400 text-lg" x-text="formatMoney(lineTotal(row))"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <div x-show="state.lines.length === 0" class="h-full flex flex-col items-center justify-center text-indigo-300 dark:text-indigo-700/50 text-center p-6 border-2 border-dashed border-indigo-200 dark:border-indigo-800/50 rounded-2xl">
                                <i data-lucide="shopping-cart" class="w-16 h-16 mb-4"></i>
                                <p class="text-lg font-medium text-indigo-800 dark:text-indigo-300">Your cart is empty.</p>
                                <p class="text-sm">Search and click "Add" on materials from the left.</p>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-indigo-200 dark:border-indigo-800 flex justify-between items-center bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm">
                            <span class="text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Subtotal</span>
                            <span class="text-2xl font-black text-gray-900 dark:text-white">RWF <span x-text="formatMoney(subtotal)"></span></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <div class="flex justify-between">
            <button @click="prevStep()" type="button" class="btn btn-secondary px-6 py-3 rounded-xl flex items-center gap-2 hover:bg-gray-200 dark:hover:bg-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i> Back
            </button>
            <button @click="nextStep()" type="button" class="btn btn-primary px-8 py-3 text-lg rounded-xl flex items-center gap-2 shadow-lg hover:shadow-xl transition-all" :disabled="state.lines.length === 0">
                Next: Review & Payment <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </button>
        </div>
        
        @if ($errors->has('products'))
            <div class="mt-4 p-4 rounded-xl text-sm text-red-700 bg-red-100 border border-red-200">
                Some products are missing quantities or valid costs. Please verify your selected items.
            </div>
        @endif
    </section>

    {{-- STEP 3: Notes & Totals --}}
    <section x-show="step === 3" x-transition.opacity.duration.300ms style="display: none;" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-6 sm:p-8">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">Step 3: Review & Complete</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- Payment summary --}}
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-bold text-lg mb-4 text-gray-800 dark:text-gray-200">Payment Summary</h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 font-medium">Subtotal</span>
                            <span class="font-bold text-lg" x-text="formatMoney(subtotal)"></span>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <x-label value="Tax (%)" class="text-xs uppercase tracking-wider text-gray-500" />
                                <input type="number" step="0.01" min="0" max="100" name="tax" class="form-input mt-1" x-model.number="state.tax" @input="recalc()">
                            </div>
                            <div class="text-right pt-5">
                                <span class="font-medium text-gray-700 dark:text-gray-300" x-text="'+ ' + formatMoney(taxValue)"></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <x-label value="Discount (%)" class="text-xs uppercase tracking-wider text-gray-500" />
                                <input type="number" step="0.01" min="0" max="100" name="discount" class="form-input mt-1" x-model.number="state.discount" @input="recalc()">
                            </div>
                            <div class="text-right pt-5">
                                <span class="font-medium text-amber-600 dark:text-amber-400" x-text="'- ' + formatMoney(discountValue)"></span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t-2 border-gray-200 dark:border-gray-700 mt-2">
                            <span class="text-xl font-black uppercase text-gray-800 dark:text-white">Total</span>
                            <span class="text-2xl font-black text-indigo-700 dark:text-indigo-400" x-text="'RWF ' + formatMoney(grand)"></span>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 mt-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                            <x-label value="Amount Paid Now" class="font-bold text-gray-800 dark:text-gray-200 mb-2" />
                            <div class="flex items-center gap-3">
                                <input type="number" step="0.01" min="0" id="amount_paid" name="amount_paid" class="form-input text-xl font-bold py-3 text-emerald-700 dark:text-emerald-400" x-model.number="state.amount_paid" @input="recalc()">
                                <button type="button" class="btn btn-success whitespace-nowrap px-4 py-3" @click="payFull()">
                                    Pay Full
                                </button>
                            </div>
                            
                            <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                                <span class="font-bold text-gray-600 dark:text-gray-400">Balance to Supplier</span>
                                <span class="text-xl font-bold" :class="balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400'" x-text="'RWF ' + formatMoney(balance)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Extras (Document & Notes) --}}
                <div class="space-y-6">
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-4">
                            <i data-lucide="paperclip" class="w-5 h-5 text-indigo-500"></i>
                            <h3 class="font-bold text-lg text-gray-800 dark:text-gray-200">Supporting Document</h3>
                        </div>
                        <input type="file" name="document" class="form-input p-2 bg-white dark:bg-gray-800" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <p class="text-xs text-gray-500 mt-2">Upload invoice or receipt (Images, PDF, Word - Max 5MB)</p>
                        @error('document')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                        @if($isEdit && !empty($purchase->document_path))
                            <div class="mt-3 p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-sm text-gray-700 dark:text-gray-300 flex items-center justify-between">
                                <span class="flex items-center gap-2"><i data-lucide="file-check" class="w-4 h-4 text-indigo-600"></i> Document Attached</span>
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($purchase->document_path) }}" target="_blank" class="text-indigo-600 font-bold hover:underline">View</a>
                            </div>
                        @endif
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 flex-1">
                        <div class="flex items-center gap-2 mb-4">
                            <i data-lucide="sticky-note" class="w-5 h-5 text-indigo-500"></i>
                            <h3 class="font-bold text-lg text-gray-800 dark:text-gray-200">Notes (Optional)</h3>
                        </div>
                        <textarea name="notes" x-model="state.notes" rows="4" class="form-textarea w-full rounded-xl" placeholder="Any remarks about this purchase..."></textarea>
                        @error('notes')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between">
            <button @click="prevStep()" type="button" class="btn btn-secondary px-6 py-3 rounded-xl flex items-center gap-2 hover:bg-gray-200 dark:hover:bg-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i> Back
            </button>
            <button type="submit" class="btn btn-success px-8 py-3 text-xl font-bold rounded-xl flex items-center gap-2 shadow-xl hover:shadow-2xl transition-all hover:scale-105">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
                {{ $isEdit ? 'Update Purchase' : 'Confirm & Save Purchase' }}
            </button>
        </div>
    </section>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

function purchaseForm(initial){
    const withKeys = (arr) =>
        arr.map(r => ({
            key: (crypto?.randomUUID?.() || (Date.now() + Math.random())),
            ...r
        }));

    return {
        step: 1,
        searchQuery: '',
        
        state: {
            supplier_id:     initial.supplier_id     ?? '',
            purchase_date:   initial.purchase_date   ?? '',
            payment_channel: initial.payment_channel ?? 'cash',
            method:          initial.method          ?? '',
            notes:           initial.notes           ?? '',
            tax:             Number(initial.tax      || 0),
            discount:        Number(initial.discount || 0),
            amount_paid:     Number(initial.amount_paid || 0),
            lines:           withKeys(Array.isArray(initial.lines) && initial.lines.length
                                ? initial.lines
                                : []), // Empty cart by default now
        },

        subtotal: 0,
        taxValue: 0,
        discountValue: 0,
        grand: 0,
        balance: 0,

        init() {
            this.recalc();
            // If editing and has items, maybe jump to review or just start at 1
        },
        
        nextStep() {
            if (this.step === 1 && !this.state.supplier_id) return;
            if (this.step === 2 && this.state.lines.length === 0) return;
            if (this.step < 3) this.step++;
        },
        
        prevStep() {
            if (this.step > 1) this.step--;
        },

        setChannel(c) {
            this.state.payment_channel = c;
        },

        matchesSearch(name) {
            if (this.searchQuery === '') return true;
            return name.toLowerCase().includes(this.searchQuery.toLowerCase());
        },
        
        hasMatches() {
            // A simple DOM check is easiest for Alpine lists
            return document.querySelectorAll(`[x-show="matchesSearch('...' )]`).length > 0;
        },

        addToCart(id, name, cost) {
            // check if already exists
            const existing = this.state.lines.find(l => l.product_id === id);
            if(existing) {
                existing.quantity++;
            } else {
                this.state.lines.push({
                    key: (crypto?.randomUUID?.() || (Date.now() + Math.random())),
                    product_id: id,
                    name: name,
                    quantity: 1,
                    unit_cost: cost,
                });
            }
            this.recalc();
        },

        removeLine(i) {
            this.state.lines.splice(i, 1);
            this.recalc();
        },

        lineTotal(r) {
            return Number(r.quantity || 0) * Number(r.unit_cost || 0);
        },

        recalc() {
            this.subtotal = this.state.lines.reduce((s, r) => s + this.lineTotal(r), 0);
            this.taxValue = (this.subtotal * Number(this.state.tax || 0)) / 100;
            this.discountValue = (this.subtotal * Number(this.state.discount || 0)) / 100;
            this.grand = Math.max(this.subtotal + this.taxValue - this.discountValue, 0);
            this.balance = Math.max(this.grand - Number(this.state.amount_paid || 0), 0);
        },

        payFull() {
            this.state.amount_paid = this.grand;
            this.recalc();
        },

        formatMoney(v) {
            return Number(v || 0).toFixed(2);
        },
    }
}
</script>
@endpush