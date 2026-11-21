@extends('layouts.app')
@section('title', 'Record Inter-Company Item Loan')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        direction: '{{ old('direction', 'given') }}',
        productId: '{{ old('product_id', '') }}',
        itemName: '{{ old('item_name', '') }}',
        unit: '{{ old('unit', 'pcs') }}',
        loanDate: '{{ old('loan_date', now()->toDateString()) }}',
        dueDate: '{{ old('due_date', '') }}',
        products: {{ json_encode($products) }},
        quickPartnerOpen: false,

        onProductSelect() {
            if (!this.productId) return;
            const p = this.products.find(item => item.id == this.productId);
            if (p) {
                this.itemName = p.name;
                if (p.unit) this.unit = p.unit;
            }
        },

        setDueDate(days) {
            const base = this.loanDate ? new Date(this.loanDate) : new Date();
            base.setDate(base.getDate() + days);
            this.dueDate = base.toISOString().split('T')[0];
        }
     }">

    {{-- Breadcrumb & Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <a href="{{ route('item-loans.index') }}" class="hover:underline">Item Loans</a>
                <span>/</span>
                <span>New Voucher</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <span>Record New Item Loan</span>
            </h1>
        </div>
        <a href="{{ route('item-loans.index') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Loans</span>
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

    {{-- Main Form Card --}}
    <form method="POST" action="{{ route('item-loans.store') }}"
          class="space-y-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 bg-white dark:bg-slate-800/90 shadow-xs">
        @csrf

        {{-- Direction Toggle --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                Loan Direction <span class="text-rose-500">*</span>
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
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Asset outflow; partner owes return</div>
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
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Asset inflow; we owe return</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Partner Company Selection --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Partner Entity <span class="text-rose-500">*</span>
                </label>
                <a href="{{ route('partner-companies.index') }}" target="_blank"
                   class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Manage Partners</span>
                </a>
            </div>
            <select name="partner_id" required
                    class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                <option value="">Select a partner company...</option>
                @foreach ($partners as $p)
                    <option value="{{ $p->id }}" @selected(old('partner_id') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Linked Product & Item Name --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Link to Inventory Stock <span class="text-[10px] font-normal text-slate-400 lowercase">(optional)</span>
                </label>
                <select name="product_id" x-model="productId" @change="onProductSelect()"
                        class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                    <option value="">-- Manual Item (No Inventory Sync) --</option>
                    @foreach ($products as $prod)
                        <option value="{{ $prod->id }}" @selected(old('product_id') == $prod->id)>
                            {{ $prod->name }} (In Stock: {{ $prod->stock }})
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">If linked, stock movements will adjust automatically.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Item / Asset Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="item_name" x-model="itemName" required maxlength="255"
                       placeholder="e.g. Cocktail Glasses, Draft Kegs"
                       class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
        </div>

        {{-- Quantity and Unit --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Loan Quantity <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity') }}" required placeholder="0.00"
                       class="w-full py-2.5 px-3.5 rounded-xl text-sm font-mono font-bold bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Unit of Measure
                </label>
                <input type="text" name="unit" x-model="unit" maxlength="20" placeholder="pcs, crates, boxes"
                       class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
        </div>

        {{-- Dates --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Loan Date <span class="text-rose-500">*</span>
                </label>
                <input type="date" name="loan_date" x-model="loanDate" required
                       class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Expected Return Date <span class="text-[10px] font-normal text-slate-400 lowercase">(optional)</span>
                    </label>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="setDueDate(7)" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">+7d</button>
                        <span class="text-slate-300">•</span>
                        <button type="button" @click="setDueDate(14)" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">+14d</button>
                        <span class="text-slate-300">•</span>
                        <button type="button" @click="setDueDate(30)" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">+30d</button>
                    </div>
                </div>
                <input type="date" name="due_date" x-model="dueDate"
                       class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
        </div>

        {{-- Notes --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Notes & Terms
            </label>
            <textarea name="notes" rows="3" placeholder="Additional details, return agreement conditions, event references..."
                      class="w-full py-2.5 px-3.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">{{ old('notes') }}</textarea>
        </div>

        {{-- Form Actions --}}
        <div class="pt-5 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
            <a href="{{ route('item-loans.index') }}"
               class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 active:scale-95 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Save Loan Record</span>
            </button>
        </div>
    </form>
</div>
@endsection
