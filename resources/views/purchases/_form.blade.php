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
                'product_id'           => (int)   $i->product_id,
                'name'                 => $i->product ? $i->product->name : 'Product #'.$i->product_id,
                'quantity'             => (float) $i->quantity,
                'unit_cost'            => (float) $i->unit_cost,
                'previous_cost'        => (float) ($i->product?->cost_price ?: $i->product?->price ?: $i->unit_cost),
                'last_cost'            => (float) ($i->product?->last_purchase_price ?: 0),
                'selling_price'        => (float) ($i->product?->price ?: 0),
                'target_margin'        => (float) ($i->product?->target_margin ?: 0),
                'unit'                 => $i->product?->unit ?: 'pcs',
                'type'                 => $i->product?->type ?: 'raw_material',
                'update_catalog_cost'  => true,
                'update_selling_price' => false,
            ])->values()->all()
            : []
    ));

    $initialState = [
        'supplier_id'     => old('supplier_id', $isEdit ? $purchase->supplier_id : ''),
        'purchase_date'   => old('purchase_date', $isEdit
            ? ($purchase->purchase_date?->format('Y-m-d') ?? now()->format('Y-m-d'))
            : now()->format('Y-m-d')),
        'payment_channel' => old('payment_channel', $isEdit ? ($purchase->payment_channel ?? 'cash') : 'cash'),
        'method'          => old('method',   $isEdit ? ($purchase->method ?? '')   : ''),
        'notes'           => old('notes',    $isEdit ? ($purchase->notes  ?? '')   : ''),
        'tax'             => (float) old('tax',      $isEdit ? $taxPct      : 0),
        'discount'        => (float) old('discount', $isEdit ? $discountPct : 0),
        'amount_paid'     => (float) old('amount_paid', $isEdit ? ($purchase->amount_paid ?? 0) : 0),
        'lines'           => $initialLines->all(),
    ];
@endphp

<div x-data="purchaseForm(@js($initialState))" x-init="init()" class="space-y-6">

    {{-- Step Navigation Indicator --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
        <div class="grid grid-cols-3 gap-2">
            {{-- Step 1 --}}
            <button type="button"
                    @click="if(step > 1) step = 1"
                    :class="step === 1 ? 'border-teal-500 bg-teal-50/40 dark:bg-teal-950/20' : (step > 1 ? 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800' : 'border-slate-100 dark:border-slate-800 opacity-60')"
                    class="flex items-center gap-3 p-3 rounded-xl border text-left transition">
                <span :class="step >= 1 ? 'bg-teal-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'"
                      class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0">
                    1
                </span>
                <div class="hidden sm:block">
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Supplier & Terms</div>
                    <div class="text-[10px] text-slate-400">Order metadata</div>
                </div>
            </button>

            {{-- Step 2 --}}
            <button type="button"
                    @click="if(state.supplier_id) step = 2"
                    :class="step === 2 ? 'border-teal-500 bg-teal-50/40 dark:bg-teal-950/20' : (step > 2 ? 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800' : 'border-slate-100 dark:border-slate-800 opacity-60')"
                    class="flex items-center gap-3 p-3 rounded-xl border text-left transition">
                <span :class="step >= 2 ? 'bg-teal-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'"
                      class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0">
                    2
                </span>
                <div class="hidden sm:block">
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Materials & Items</div>
                    <div class="text-[10px] text-slate-400"><span x-text="state.lines.length"></span> items selected</div>
                </div>
            </button>

            {{-- Step 3 --}}
            <button type="button"
                    @click="if(state.lines.length > 0) step = 3"
                    :class="step === 3 ? 'border-teal-500 bg-teal-50/40 dark:bg-teal-950/20' : 'border-slate-100 dark:border-slate-800 opacity-60'"
                    class="flex items-center gap-3 p-3 rounded-xl border text-left transition">
                <span :class="step >= 3 ? 'bg-teal-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'"
                      class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0">
                    3
                </span>
                <div class="hidden sm:block">
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Payment & Review</div>
                    <div class="text-[10px] text-slate-400">Taxes, balances & notes</div>
                </div>
            </button>
        </div>
    </div>

    {{-- STEP 1: Basic Info --}}
    <section x-show="step === 1" x-transition.opacity.duration.200ms class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs p-6 space-y-6">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                <span class="w-8 h-8 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                    1
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Supplier & Order Terms</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Select the supplier and define transaction schedule and settlement method.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Supplier Select --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1.5">
                        Supplier / Vendor <span class="text-rose-500">*</span>
                    </label>
                    <select name="supplier_id" x-model="state.supplier_id"
                            class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"
                            required>
                        <option value="">-- Choose Supplier --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->phone ?: ($s->email ?: 'ID #'.$s->id) }})</option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Purchase Date --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1.5">
                        Purchase Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="purchase_date" x-model="state.purchase_date"
                           class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"
                           required>
                    @error('purchase_date')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Payment Channel --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1.5">
                        Payment Channel / Method
                    </label>
                    <input type="hidden" name="payment_channel" :value="state.payment_channel">
                    <div class="flex gap-2 flex-wrap">
                        @foreach($paymentChannels as $channel)
                            @php
                                $cSlug = $channel->slug;
                            @endphp
                            <button type="button"
                                    @click="setChannel('{{ $cSlug }}')"
                                    :class="state.payment_channel === '{{ $cSlug }}'
                                        ? 'bg-teal-50 dark:bg-teal-950/60 text-teal-800 dark:text-teal-300 border-teal-500 shadow-xs font-bold'
                                        : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'"
                                    class="px-3.5 py-2 rounded-xl border text-xs transition flex-1 text-center">
                                {{ $channel->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Reference / Invoice No. --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1.5">
                        Supplier Invoice # / Delivery Note (Optional)
                    </label>
                    <input type="text" name="method" x-model="state.method"
                           placeholder="e.g. INV-88421 or DN-009"
                           class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button @click="nextStep()" type="button"
                    class="inline-flex items-center gap-2 px-6 py-2.5 text-xs font-semibold rounded-xl bg-teal-600 text-white hover:bg-teal-500 shadow-sm transition"
                    :disabled="!state.supplier_id">
                <span>Continue: Select Materials</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </section>

    {{-- STEP 2: Raw Materials Selection --}}
    <section x-show="step === 2" x-transition.opacity.duration.200ms style="display: none;" class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs p-6 space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                        2
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Select Inventory & Raw Materials</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Search materials from catalog and add line items with cost and quantity.</p>
                    </div>
                </div>

                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 border border-teal-200 dark:border-teal-800"
                      x-text="state.lines.length + ' Selected Items'"></span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- Product Catalog (Left Pane) --}}
                <div class="lg:col-span-5 flex flex-col h-[520px]">
                    <div class="mb-3 relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="searchQuery" placeholder="Search catalog items..."
                               class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white pl-9 pr-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                    </div>

                    <div class="flex-1 overflow-y-auto pr-1 space-y-2 rounded-xl border border-slate-200/80 dark:border-slate-800 p-2 bg-slate-50/50 dark:bg-slate-900/40">
                        @foreach($products as $p)
                            @php
                                $cCost = (float)($p->cost_price ?: $p->price ?: 0);
                                $lCost = (float)($p->last_purchase_price ?: 0);
                                $sPrice = (float)($p->price ?: 0);
                                $tMargin = (float)($p->target_margin ?: 0);
                                $pUnit = $p->unit ?? 'pcs';
                            @endphp
                            <div x-show="matchesSearch('{{ addslashes($p->name) }}')"
                                 class="flex justify-between items-center p-3 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-800 rounded-xl hover:border-teal-500/40 transition-colors shadow-2xs">
                                <div>
                                    <h4 class="font-bold text-xs text-slate-900 dark:text-white">{{ $p->name }}</h4>
                                    <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        <span>Cost: RWF {{ number_format($cCost, 2) }}</span>
                                        @if($lCost > 0 && $lCost != $cCost)
                                            <span class="text-teal-600 dark:text-teal-400 font-medium">· Last: RWF {{ number_format($lCost, 2) }}</span>
                                        @endif
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-[10px] font-mono">{{ $pUnit }}</span>
                                    </div>
                                </div>
                                <button type="button"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 dark:bg-teal-950/40 dark:text-teal-300 dark:hover:bg-teal-900/50 border border-teal-200 dark:border-teal-800 transition shadow-2xs"
                                        @click="addToCart({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $cCost }}, {{ $lCost }}, {{ $sPrice }}, {{ $tMargin }}, '{{ $pUnit }}', '{{ $p->type ?: 'raw_material' }}')">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Add</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Selected Items Cart (Right Pane) --}}
                <div class="lg:col-span-7 flex flex-col h-[520px]">
                    <div class="bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 flex-1 flex flex-col">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span>Order Line Items</span>
                            </h3>
                            <span class="text-xs text-slate-400 font-medium" x-text="state.lines.length + ' item(s)'"></span>
                        </div>

                        <div class="flex-1 overflow-y-auto pr-1 space-y-3">
                            <template x-for="(row, idx) in state.lines" :key="row.key">
                                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3.5 shadow-2xs relative">
                                    <input type="hidden" :name="`products[${idx}][product_id]`" :value="row.product_id">
                                    <input type="hidden" :name="`products[${idx}][total_cost]`" :value="lineTotal(row).toFixed(2)">

                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="font-bold text-xs text-slate-900 dark:text-white" x-text="row.name"></h4>
                                            <span class="text-[10px] font-mono text-slate-400" x-text="'Unit: ' + (row.unit || 'pcs')"></span>
                                        </div>
                                        <button type="button"
                                                class="text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 p-1 rounded-lg transition"
                                                @click="removeLine(idx)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                        {{-- Quantity --}}
                                        <div class="sm:col-span-4">
                                            <label class="text-[11px] font-semibold text-slate-500 mb-1 block">Quantity</label>
                                            <input type="number" step="any" min="0.01"
                                                   class="w-full text-xs font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"
                                                   x-model.number="row.quantity"
                                                   :name="`products[${idx}][quantity]`"
                                                   @input="recalc()">
                                        </div>

                                        {{-- Unit Cost --}}
                                        <div class="sm:col-span-5">
                                            <label class="text-[11px] font-semibold text-slate-500 mb-1 block">Unit Cost (RWF)</label>
                                            <input type="number" step="any" min="0"
                                                   class="w-full text-xs font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"
                                                   x-model.number="row.unit_cost"
                                                   :name="`products[${idx}][unit_cost]`"
                                                   @input="recalc()">
                                        </div>

                                        {{-- Line Subtotal --}}
                                        <div class="sm:col-span-3 text-right">
                                            <label class="text-[11px] font-semibold text-slate-500 mb-1 block">Line Total</label>
                                            <div class="font-mono font-bold text-xs text-slate-900 dark:text-white">
                                                <span x-text="formatMoney(lineTotal(row))"></span>
                                            </div>
                                            <span class="text-[10px] text-slate-400">RWF</span>
                                        </div>
                                    </div>

                                    {{-- Price Change Alert Pill --}}
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5" x-show="priceDiffPct(row) !== 0">
                                        <template x-if="priceDiffPct(row) > 0">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded-md border border-rose-200 dark:border-rose-800">
                                                ▲ +<span x-text="priceDiffPct(row)"></span>% cost increase (Prior: RWF <span x-text="formatMoney(row.previous_cost)"></span>)
                                            </span>
                                        </template>
                                        <template x-if="priceDiffPct(row) < 0">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800">
                                                ▼ -<span x-text="Math.abs(priceDiffPct(row))"></span>% cost decrease (Prior: RWF <span x-text="formatMoney(row.previous_cost)"></span>)
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div x-show="state.lines.length === 0"
                                 class="h-full flex flex-col items-center justify-center text-slate-400 text-center p-8 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                                <svg class="w-10 h-10 mb-2 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Order is currently empty</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Click "Add" on any material from the left panel.</p>
                            </div>
                        </div>

                        {{-- Subtotal Card Bar --}}
                        <div class="mt-3 pt-3 border-t border-slate-200/80 dark:border-slate-800 flex justify-between items-center">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Items Subtotal</span>
                            <div class="text-base font-bold text-slate-900 dark:text-white tabular-nums">
                                <span class="text-xs text-slate-400 font-normal">RWF</span>
                                <span x-text="formatMoney(subtotal)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between">
            <button @click="prevStep()" type="button"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back: Supplier Info</span>
            </button>

            <button @click="nextStep()" type="button"
                    class="inline-flex items-center gap-2 px-6 py-2 text-xs font-semibold rounded-xl bg-teal-600 text-white hover:bg-teal-500 shadow-sm transition"
                    :disabled="state.lines.length === 0">
                <span>Continue: Payment & Settlement</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </section>

    {{-- STEP 3: Review & Payment --}}
    <section x-show="step === 3" x-transition.opacity.duration.200ms style="display: none;" class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs p-6 space-y-6">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                <span class="w-8 h-8 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                    3
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Review & Financial Settlement</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Configure taxes, discounts, down-payment, supporting documents, and audit notes.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Payment Details (Left) --}}
                <div class="rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-4">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                        Order Financial Breakdown
                    </h3>

                    <div class="flex justify-between items-center pb-3 border-b border-slate-200 dark:border-slate-700 text-xs">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Subtotal</span>
                        <div class="font-mono font-bold text-slate-900 dark:text-white">
                            RWF <span x-text="formatMoney(subtotal)"></span>
                        </div>
                    </div>

                    {{-- Tax % --}}
                    <div class="flex items-center justify-between gap-4 text-xs">
                        <div class="w-1/2">
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Tax Rate (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="tax"
                                   x-model.number="state.tax" @input="recalc()"
                                   class="w-full text-xs font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        </div>
                        <div class="text-right pt-4 font-mono font-bold text-slate-700 dark:text-slate-300">
                            + RWF <span x-text="formatMoney(taxValue)"></span>
                        </div>
                    </div>

                    {{-- Discount % --}}
                    <div class="flex items-center justify-between gap-4 text-xs">
                        <div class="w-1/2">
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Discount (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="discount"
                                   x-model.number="state.discount" @input="recalc()"
                                   class="w-full text-xs font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        </div>
                        <div class="text-right pt-4 font-mono font-bold text-amber-600 dark:text-amber-400">
                            - RWF <span x-text="formatMoney(discountValue)"></span>
                        </div>
                    </div>

                    {{-- Total Banner --}}
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-white">Grand Total Amount</span>
                        <div class="text-xl font-bold text-slate-900 dark:text-white tabular-nums">
                            <span class="text-xs text-slate-400 font-normal">RWF</span>
                            <span x-text="formatMoney(grand)"></span>
                        </div>
                    </div>

                    {{-- Downpayment & Balance --}}
                    <div class="rounded-xl p-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-200">Amount Paid Now (Deposit / Cash Out)</label>
                            <button type="button" @click="payFull()"
                                    class="text-[11px] font-bold text-teal-600 dark:text-teal-400 hover:underline">
                                Pay In Full
                            </button>
                        </div>

                        <div class="relative">
                            <input type="number" step="any" min="0" id="amount_paid" name="amount_paid"
                                   x-model.number="state.amount_paid" @input="recalc()"
                                   class="w-full text-sm font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 px-3.5 py-2.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                            <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-mono pointer-events-none">RWF</span>
                        </div>

                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-xs">
                            <span class="font-medium text-slate-600 dark:text-slate-400">Remaining Balance to Supplier:</span>
                            <span class="font-mono font-bold"
                                  :class="balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                                  x-text="'RWF ' + formatMoney(balance)"></span>
                        </div>
                    </div>
                </div>

                {{-- Extras (Document & Notes) (Right) --}}
                <div class="space-y-4">
                    {{-- Document Attachment --}}
                    <div class="rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5 uppercase tracking-wider">
                            Supporting Document / Receipt
                        </label>
                        <input type="file" name="document"
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-slate-800 dark:file:text-slate-300"
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <p class="text-[11px] text-slate-400 mt-1.5">Attach supplier invoice, delivery docket, or payment receipt (Max 5MB).</p>

                        @if($isEdit && !empty($purchase->document_path))
                            <div class="mt-3 p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-xs flex items-center justify-between">
                                <span class="font-medium text-slate-700 dark:text-slate-300">Current Attachment on file</span>
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($purchase->document_path) }}" target="_blank"
                                   class="text-teal-600 dark:text-teal-400 font-bold hover:underline">
                                    View File →
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Notes --}}
                    <div class="rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5 uppercase tracking-wider">
                            Purchase Notes & Remarks
                        </label>
                        <textarea name="notes" x-model="state.notes" rows="4"
                                  placeholder="Terms of delivery, payment schedule notes, quality checks..."
                                  class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between">
            <button @click="prevStep()" type="button"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back: Edit Materials</span>
            </button>

            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 text-xs font-semibold rounded-xl bg-teal-600 text-white hover:bg-teal-500 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ $isEdit ? 'Update Purchase Order' : 'Confirm & Save Purchase' }}</span>
            </button>
        </div>
    </section>
</div>

@push('scripts')
<script>
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
                                : []),
        },

        subtotal: 0,
        taxValue: 0,
        discountValue: 0,
        grand: 0,
        balance: 0,

        init() {
            this.recalc();
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

        addToCart(id, name, cost, lastCost = 0, selling = 0, margin = 0, unit = 'pcs', type = 'raw_material') {
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
                    previous_cost: cost,
                    last_cost: lastCost,
                    selling_price: selling,
                    target_margin: margin,
                    unit: unit,
                    type: type,
                    update_catalog_cost: true,
                    update_selling_price: false,
                });
            }
            this.recalc();
        },

        priceDiffPct(row) {
            const oldCost = Number(row.previous_cost || 0);
            const newCost = Number(row.unit_cost || 0);
            if (oldCost <= 0) return 0;
            return Number((((newCost - oldCost) / oldCost) * 100).toFixed(1));
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
            return Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
}
</script>
@endpush
