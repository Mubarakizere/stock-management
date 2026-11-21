@extends('layouts.app')
@section('title', "Edit Loan #{$loan->id}")

@section('content')
@php
    $voucherCode = '#IL-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT);
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        direction: '{{ old('direction', $loan->direction) }}',
        productId: '{{ old('product_id', $loan->product_id) }}',
        itemName: '{{ old('item_name', $loan->item_name) }}',
        unit: '{{ old('unit', $loan->unit) }}',
        products: {{ json_encode($products) }},

        onProductSelect() {
            if (!this.productId) return;
            const p = this.products.find(item => item.id == this.productId);
            if (p) {
                this.itemName = p.name;
                if (p.unit) this.unit = p.unit;
            }
        }
     }">

    {{-- Breadcrumb & Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <a href="{{ route('item-loans.index') }}" class="hover:underline">Item Loans</a>
                <span>/</span>
                <a href="{{ route('item-loans.show', $loan) }}" class="hover:underline font-mono">{{ $voucherCode }}</a>
                <span>/</span>
                <span>Edit</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <span>Edit Loan Voucher <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ $voucherCode }}</span></span>
            </h1>
        </div>
        <a href="{{ route('item-loans.show', $loan) }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Voucher</span>
        </a>
    </div>

    {{-- Validation Errors --}}
    @if (isset($errors) && $errors->any())
        <div class="rounded-2xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 p-4 text-xs text-rose-800 dark:text-rose-200">
            <div class="font-bold mb-1 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Please correct the following errors:
            </div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Returns Lock Notice --}}
    @if($hasReturns)
        <div class="rounded-2xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 p-4 text-xs text-amber-800 dark:text-amber-200 flex items-start gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900/50 rounded-xl text-amber-700 dark:text-amber-300 shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <span class="font-bold">Quantity field is locked.</span>
                <p class="mt-0.5 text-[11px] text-amber-700 dark:text-amber-300">
                    Returns have already been recorded for this loan ({{ number_format($loan->quantity_returned, 2) }} {{ $loan->unit }} returned).
                    Quantity cannot be edited to preserve audit integrity.
                </p>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('item-loans.update', $loan) }}"
          class="space-y-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 bg-white dark:bg-slate-800/90 shadow-xs">
        @csrf
        @method('PUT')

        {{-- Direction Selector --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                Direction <span class="text-rose-500">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="cursor-pointer border rounded-2xl p-4 flex items-center gap-3.5 transition"
                       :class="direction === 'given' ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/30 text-emerald-900 dark:text-emerald-100 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-slate-300'">
                    <input type="radio" name="direction" value="given" x-model="direction" class="sr-only">
                    <div class="p-2.5 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l10-10M17 7H7m10 0v10" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold">We LENT (Given to Partner)</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Asset outflow</div>
                    </div>
                </label>

                <label class="cursor-pointer border rounded-2xl p-4 flex items-center gap-3.5 transition"
                       :class="direction === 'taken' ? 'border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-100 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-slate-300'">
                    <input type="radio" name="direction" value="taken" x-model="direction" class="sr-only">
                    <div class="p-2.5 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7 17M7 17h10M7 17V7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold">We BORROWED (Taken from Partner)</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Asset inflow</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Partner Selection --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Partner Entity <span class="text-rose-500">*</span>
            </label>
            <select name="partner_id" required
                    class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                @foreach ($partners as $p)
                    <option value="{{ $p->id }}" @selected(old('partner_id', $loan->partner_id) == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Linked Product & Item Name --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Linked Inventory Stock <span class="text-[10px] font-normal text-slate-400 lowercase">(optional)</span>
                </label>
                <select name="product_id" x-model="productId" @change="onProductSelect()"
                        class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                    <option value="">-- Manual Item (No Link) --</option>
                    @foreach ($products as $prod)
                        <option value="{{ $prod->id }}" @selected(old('product_id', $loan->product_id) == $prod->id)>
                            {{ $prod->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Item / Asset Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="item_name" x-model="itemName" required maxlength="255"
                       class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
        </div>

        {{-- Quantity & Unit --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Quantity <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" min="0.01" name="quantity"
                       value="{{ old('quantity', $loan->quantity) }}" {{ $hasReturns ? 'readonly' : 'required' }}
                       class="w-full py-2.5 px-3.5 rounded-xl text-sm font-mono font-bold border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50 {{ $hasReturns ? 'opacity-60 cursor-not-allowed bg-slate-100 dark:bg-slate-900' : 'bg-slate-50 dark:bg-slate-900/60' }}">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Unit
                </label>
                <input type="text" name="unit" x-model="unit" maxlength="20"
                       class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
        </div>

        {{-- Dates --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Loan Date <span class="text-rose-500">*</span>
                </label>
                <input type="date" name="loan_date" value="{{ old('loan_date', optional($loan->loan_date)->format('Y-m-d')) }}" required
                       class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Due Date <span class="text-[10px] font-normal text-slate-400 lowercase">(optional)</span>
                </label>
                <input type="date" name="due_date" value="{{ old('due_date', optional($loan->due_date)->format('Y-m-d')) }}"
                       class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
        </div>

        {{-- Notes --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Notes
            </label>
            <textarea name="notes" rows="3"
                      class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">{{ old('notes', $loan->notes) }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="pt-5 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
            <a href="{{ route('item-loans.show', $loan) }}"
               class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 active:scale-95 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Update Loan Voucher</span>
            </button>
        </div>
    </form>
</div>
@endsection
