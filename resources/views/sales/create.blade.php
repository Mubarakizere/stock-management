@extends('layouts.app')
@section('title', 'Record New Sale')

@section('content')
@php
    $productMeta = collect($products ?? [])->mapWithKeys(function ($p) {
        return [
            $p->id => [
                'price' => (float) ($p->price ?? 0),
                'cost'  => (float) ($p->cost_price ?? 0),
            ],
        ];
    });
@endphp

@cannot('sales.create')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        Permission Denied
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        You do not possess the required permission to record sales. Please contact your manager.
                    </p>
                </div>
            </div>
        </div>
    </div>
@elsecan('sales.create')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
     x-data="saleCreateForm({{ Illuminate\Support\Js::from($productMeta) }})"
     x-init="init()">

    {{-- =========================================================================
    | 1. Header & Quick Shortcuts
    |========================================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-gray-200/80 dark:border-gray-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                <a href="{{ route('sales.index') }}" class="hover:underline">Sales</a>
                <span>/</span>
                <span class="text-gray-900 dark:text-white font-medium">New Order</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2.5">
                <i data-lucide="shopping-cart" class="w-6 h-6 text-teal-600 dark:text-teal-400"></i>
                <span>Point of Sale Register</span>
            </h1>
        </div>

        <div class="flex items-center gap-3">
            {{-- Keyboard Shortcuts Legend --}}
            <div class="hidden md:flex items-center gap-3 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-mono text-[10px]">F2</kbd> Add Item</span>
                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-mono text-[10px]">F4</kbd> Pay</span>
                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-mono text-[10px]">Ctrl+Enter</kbd> Save</span>
            </div>

            <a href="{{ route('sales.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 border border-gray-200 dark:border-gray-700 shadow-2xs transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4 text-gray-400"></i>
                <span>Back</span>
            </a>
        </div>
    </div>

    {{-- Flash / Errors --}}
    @if (($errors ?? null)?->any())
        <div class="rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 p-4 text-xs text-rose-800 dark:text-rose-300 space-y-1">
            <strong class="font-semibold block text-sm flex items-center gap-1.5">
                <i data-lucide="alert-circle" class="w-4 h-4"></i> Please fix the following errors:
            </strong>
            <ul class="list-disc pl-5 space-y-0.5 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main POS Form --}}
    <form action="{{ route('sales.store') }}" method="POST" id="sale-form">
        @csrf

        <input type="hidden" name="cust_mode" x-model="custMode">
        <input type="hidden" name="customer_id" :value="custMode === 'existing' ? existingId : ''">
        <input type="hidden" name="payment_channel" :value="dominantMethod()">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- ===================== LEFT COLUMN (7 COLS): ITEMS & CUSTOMER ===================== --}}
            <div class="lg:col-span-7 space-y-6">

                {{-- Customer & Date Bar --}}
                <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer Details</span>
                        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700/60 p-1 rounded-xl">
                            <button type="button"
                                    @click="custMode = 'walkin'"
                                    :class="custMode === 'walkin' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-2xs font-semibold' : 'text-gray-600 dark:text-gray-400'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition">
                                Walk-in
                            </button>
                            <button type="button"
                                    @click="custMode = 'existing'"
                                    :class="custMode === 'existing' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-2xs font-semibold' : 'text-gray-600 dark:text-gray-400'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition">
                                Existing
                            </button>
                            <button type="button"
                                    @click="custMode = 'new'"
                                    :class="custMode === 'new' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-2xs font-semibold' : 'text-gray-600 dark:text-gray-400'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition">
                                + New Client
                            </button>
                        </div>
                    </div>

                    {{-- Mode 1: Walk-in --}}
                    <div x-show="custMode === 'walkin'" class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/30 p-3 rounded-xl border border-gray-100 dark:border-gray-700">
                        Defaulting to standard <strong>Walk-in Retail Customer</strong> (no account required).
                    </div>

                    {{-- Mode 2: Existing Customer --}}
                    <div x-show="custMode === 'existing'" x-cloak class="space-y-1">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Select Registered Client</label>
                        <select x-model="existingId"
                                :required="custMode === 'existing'"
                                class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                            <option value="">Choose a customer from directory...</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone ?? 'No phone' }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Mode 3: Quick New Customer --}}
                    <div x-show="custMode === 'new'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Customer Full Name *</label>
                            <input type="text" name="customer_name" placeholder="Client or Company Name"
                                   value="{{ old('customer_name') ?? old('new_customer_name') }}"
                                   :required="custMode === 'new'"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Phone Number</label>
                            <input type="text" name="customer_phone" placeholder="e.g. +250 788 123 456"
                                   value="{{ old('customer_phone') ?? old('new_customer_phone') }}"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Email Address (Optional)</label>
                            <input type="email" name="customer_email" placeholder="client@example.com"
                                   value="{{ old('customer_email') }}"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Physical Address</label>
                            <input type="text" name="customer_address" placeholder="Kigali, District..."
                                   value="{{ old('customer_address') }}"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm">
                        </div>
                    </div>

                    {{-- Date & Global Reference row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Transaction Date</label>
                            <input type="date" name="sale_date" x-model="saleDate"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm"
                                   required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">POS / Batch Reference (Optional)</label>
                            <input type="text" name="method" value="{{ old('method') }}"
                                   placeholder="e.g. Register-01, Slip #104"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm">
                        </div>
                    </div>
                </div>

                {{-- Product Items Table --}}
                <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl shadow-xs overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="package" class="w-4 h-4 text-teal-600"></i>
                                <span>Cart Items & Quantities</span>
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">Select catalog products and specify line volumes</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-white bg-teal-600 hover:bg-teal-700 transition"
                                    @click="addLine()">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Add Item (F2)</span>
                            </button>
                            <button type="button"
                                    class="px-2.5 py-1.5 rounded-xl text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                    @click="clearLines()">
                                Clear
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left min-w-[620px]">
                            <thead class="bg-gray-50/80 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[10px] font-semibold">
                                <tr>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-3 py-3 w-28">Type</th>
                                    <th class="px-3 py-3 w-20 text-right">Qty</th>
                                    <th class="px-3 py-3 w-28 text-right">Unit Price</th>
                                    <th class="px-4 py-3 text-right">Subtotal</th>
                                    <th class="px-3 py-3 w-10 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                <template x-for="(row, idx) in lines" :key="row.key">
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors">
                                        {{-- Product selector --}}
                                        <td class="px-4 py-3">
                                            <select :name="`products[${idx}][product_id]`"
                                                    x-model.number="row.product_id"
                                                    @change="onProductChange(row)"
                                                    class="w-full px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs focus:border-teal-500"
                                                    required>
                                                <option value="">Select product from inventory...</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="text-[10px] mt-1 text-gray-400 flex items-center gap-2" x-show="row.product_id">
                                                <span>Cost: <strong x-text="money(prodCost(row))"></strong></span>
                                                <span>•</span>
                                                <span :class="row.unit_price >= prodCost(row) ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600'">
                                                    Margin: <strong x-text="marginPct(row)"></strong>%
                                                </span>
                                            </div>
                                        </td>

                                        {{-- Line Type --}}
                                        <td class="px-3 py-3">
                                            <select :name="`products[${idx}][type]`"
                                                    x-model="row.type"
                                                    @change="onTypeChange(row)"
                                                    class="w-full px-2 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs">
                                                <option value="sale">Sale</option>
                                                <option value="sampler">Sampler</option>
                                                <option value="test">Test</option>
                                                <option value="damaged">Damaged</option>
                                                <option value="replacement">Replace</option>
                                            </select>
                                        </td>

                                        {{-- Quantity --}}
                                        <td class="px-3 py-3 text-right">
                                            <input type="number" step="0.01" min="0.01"
                                                   class="w-20 px-2 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-right text-xs font-semibold tabular-nums"
                                                   x-model.number="row.quantity"
                                                   :name="`products[${idx}][quantity]`"
                                                   @input="recalc()">
                                        </td>

                                        {{-- Unit Price --}}
                                        <td class="px-3 py-3 text-right">
                                            <input type="number" step="0.01" min="0"
                                                   class="w-28 px-2 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-right text-xs font-semibold tabular-nums"
                                                   x-model.number="row.unit_price"
                                                   :name="`products[${idx}][unit_price]`"
                                                   :readonly="row.type !== 'sale'"
                                                   @input="recalc()">
                                            <button type="button" class="text-[10px] text-gray-400 hover:text-teal-600 underline block ml-auto mt-0.5" @click="resetToDefaultPrice(row)">
                                                Reset
                                            </button>
                                        </td>

                                        {{-- Subtotal --}}
                                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white tabular-nums">
                                            <input type="hidden" :name="`products[${idx}][subtotal]`" :value="(row.quantity * row.unit_price).toFixed(2)">
                                            <span x-text="money(row.quantity * row.unit_price) + ' RWF'"></span>
                                        </td>

                                        {{-- Remove Action --}}
                                        <td class="px-3 py-3 text-center">
                                            <button type="button"
                                                    class="p-1 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                    @click="removeLine(idx)">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="!lines.length">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                        Cart is currently empty. Click "Add Item" or press F2 to begin.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Notes / Remarks --}}
                <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-4 shadow-xs">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Sale Notes / Delivery Remarks
                    </label>
                    <textarea name="notes" rows="2"
                              class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs"
                              placeholder="Optional instructions, customer memo, or delivery details...">{{ old('notes') }}</textarea>
                </div>

            </div>

            {{-- ===================== RIGHT COLUMN (5 COLS): CHECKOUT & PAYMENTS ===================== --}}
            <div class="lg:col-span-5 space-y-6">

                <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-5 shadow-xs sticky top-20 space-y-5">
                    
                    <div class="pb-3 border-b border-gray-100 dark:border-gray-700/60">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Checkout Summary</span>
                        <div class="flex items-baseline justify-between mt-2">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Payable</span>
                            <span class="text-2xl font-extrabold text-gray-900 dark:text-white tabular-nums">
                                <span x-text="money(total)"></span> <span class="text-xs font-semibold text-gray-400">RWF</span>
                            </span>
                        </div>
                    </div>

                    {{-- Split Payments Channel Manager --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Payment Allocation</span>
                            <button type="button"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-teal-600 hover:text-teal-700 dark:text-teal-400"
                                    @click="addPayment()">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Split / Add Channel</span>
                            </button>
                        </div>

                        {{-- Payment rows --}}
                        <div class="space-y-2.5">
                            <template x-for="(p, i) in payments" :key="p.key">
                                <div class="p-3 rounded-xl bg-gray-50/80 dark:bg-gray-700/30 border border-gray-200/70 dark:border-gray-700/70 space-y-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Channel</label>
                                            <select :name="`payments[${i}][method]`" x-model="p.method"
                                                    class="w-full px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-900/60 dark:text-white text-xs">
                                                @foreach($paymentChannels as $channel)
                                                    <option value="{{ $channel->slug }}">{{ $channel->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Amount (RWF)</label>
                                            <input type="number" step="0.01" min="0" placeholder="0.00"
                                                   :name="`payments[${i}][amount]`" x-model.number="p.amount"
                                                   @input="recalc()"
                                                   class="w-full px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-900/60 dark:text-white text-right text-xs font-semibold tabular-nums">
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="text" placeholder="Ref code / Mobile phone (optional)"
                                               :name="`payments[${i}][reference]`" x-model="p.reference"
                                               class="flex-1 px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-900/60 dark:text-white text-xs">
                                        <input type="hidden" :name="`payments[${i}][paid_at]`" :value="saleDate">
                                        <button type="button"
                                                class="p-1 text-gray-400 hover:text-rose-600 transition"
                                                @click="removePayment(i)">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Fallback Single Payment --}}
                        <div x-show="!payments.length" class="space-y-2 mt-2">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Amount Tendered</label>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.01" min="0" id="amount_paid"
                                       name="amount_paid"
                                       x-model.number="singlePaid"
                                       @input="recalc()"
                                       value="{{ old('amount_paid', 0) }}"
                                       class="flex-1 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-right font-bold tabular-nums text-sm">
                                <button type="button"
                                        @click="payFull()"
                                        class="px-3 py-2 rounded-xl text-xs font-semibold text-teal-700 bg-teal-50 dark:bg-teal-950/40 dark:text-teal-400 border border-teal-200/60 dark:border-teal-800/60 hover:bg-teal-100 transition">
                                    Full
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Balances Breakdown --}}
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">Total Tendered</span>
                            <span class="font-bold text-gray-900 dark:text-white tabular-nums" x-text="money(totalPaid) + ' RWF'"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm pt-1">
                            <span class="font-bold text-gray-800 dark:text-gray-200">Balance Due</span>
                            <span class="font-extrabold tabular-nums"
                                  :class="(total - totalPaid) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                                  x-text="money(Math.max(total - totalPaid, 0)) + ' RWF'"></span>
                        </div>
                    </div>

                    {{-- CTA Buttons --}}
                    <div class="pt-2 space-y-2">
                        <button type="submit"
                                class="w-full py-3 rounded-xl bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white font-bold text-sm shadow-xs transition flex items-center justify-center gap-2">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Complete & Record Sale (Ctrl+Enter)</span>
                        </button>
                        <a href="{{ route('sales.index') }}"
                           class="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold text-xs transition flex items-center justify-center gap-1.5">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            <span>Discard Sale</span>
                        </a>
                    </div>

                </div>

            </div>

        </div>
    </form>

</div>

@endcan
@endsection

@push('scripts')
<script>
function saleCreateForm(productMeta){
    const rid = () => (crypto.randomUUID?.() || String(Date.now() + Math.random()));

    return {
        custMode: @json(old('cust_mode', 'walkin')),
        existingId: (() => {
            const v = @json(old('customer_id', ''));
            return v ? Number(v) : '';
        })(),

        saleDate: @json(old('sale_date', now()->format('Y-m-d'))),
        meta: productMeta || {},

        lines: (() => {
            const raw = @json(old('products', []));
            if (!Array.isArray(raw) || raw.length === 0) {
                return [{ key: rid(), product_id: '', type: 'sale', quantity: 1, unit_price: 0 }];
            }
            return raw.map(p => ({
                key: rid(),
                product_id: Number(p?.product_id ?? 0) || '',
                type: p?.type || 'sale',
                quantity: Number(p?.quantity ?? 1) || 1,
                unit_price: Number(p?.unit_price ?? 0) || 0,
            }));
        })(),

        payments: (() => {
            const oldPayments = @json(old('payments', []));
            return Array.isArray(oldPayments) && oldPayments.length
                ? oldPayments.map(x => ({
                    key: rid(),
                    method: x.method || 'cash',
                    amount: Number(x.amount || 0),
                    reference: x.reference || '',
                }))
                : [];
        })(),
        singlePaid: Number(@json(old('amount_paid', 0))),

        total: 0,
        totalPaid: 0,

        init(){
            this.recalc();

            window.addEventListener('keydown', (e) => {
                if (e.key === 'F2') {
                    e.preventDefault();
                    this.addLine();
                    this.$nextTick(() => {
                        const selects = document.querySelectorAll('select[name^="products"]');
                        if (selects.length) selects[selects.length - 1].focus();
                    });
                }
                if (e.key === 'F4') {
                    e.preventDefault();
                    document.getElementById('amount_paid')?.focus();
                    document.getElementById('amount_paid')?.select();
                }
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('sale-form')?.submit();
                }
            });
        },

        money(v){ return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

        prodCost(row){
            const meta = this.meta[row.product_id] || {};
            return Number(meta.cost || 0);
        },
        prodDefaultPrice(row){
            const meta = this.meta[row.product_id] || {};
            return Number(meta.price || 0);
        },
        marginPct(row){
            const cost = this.prodCost(row);
            const price = Number(row.unit_price || 0);
            if (price <= 0) return 0;
            return (((price - cost) / price) * 100).toFixed(1);
        },
        resetToDefaultPrice(row){
            const p = this.prodDefaultPrice(row);
            if (p > 0) {
                row.unit_price = p;
                this.recalc();
            }
        },

        addLine(){
            this.lines.push({ key: rid(), product_id: '', type: 'sale', quantity: 1, unit_price: 0 });
        },
        clearLines(){
            this.lines = [];
            this.recalc();
        },
        removeLine(i){
            this.lines.splice(i, 1);
            this.recalc();
        },

        onProductChange(row){
            const defPrice = this.prodDefaultPrice(row);
            if (defPrice > 0 && (!row.unit_price || row.unit_price === 0) && row.type === 'sale') {
                row.unit_price = defPrice;
            }
            this.recalc();
        },

        onTypeChange(row){
            if (row.type !== 'sale') {
                row.unit_price = 0;
            } else {
                row.unit_price = this.prodDefaultPrice(row);
            }
            this.recalc();
        },

        addPayment(){
            this.payments.push({ key: rid(), method: 'cash', amount: 0, reference: '' });
            this.recalc();
        },
        removePayment(i){
            this.payments.splice(i, 1);
            this.recalc();
        },

        dominantMethod(){
            if (!this.payments.length) {
                return 'cash';
            }
            const sums = {};
            for (const p of this.payments) {
                const m = p.method || 'cash';
                sums[m] = (sums[m] || 0) + Number(p.amount || 0);
            }
            return Object.entries(sums).sort((a,b) => b[1] - a[1])[0]?.[0] || 'cash';
        },

        payFull(){
            if (this.payments.length === 1) {
                this.payments[0].amount = this.total;
            } else if (this.payments.length > 1) {
                this.payments[0].amount = this.total;
                for (let j = 1; j < this.payments.length; j++) {
                    this.payments[j].amount = 0;
                }
            } else {
                this.singlePaid = this.total;
            }
            this.recalc();
        },

        recalc(){
            this.total = this.lines.reduce((s, r) =>
                s + (Number(r.quantity || 0) * Number(r.unit_price || 0)), 0
            );

            const splitSum = this.payments.reduce((s, p) => s + Number(p.amount || 0), 0);
            this.totalPaid = splitSum + Number(this.singlePaid || 0);
        },
    };
}
</script>
@endpush
