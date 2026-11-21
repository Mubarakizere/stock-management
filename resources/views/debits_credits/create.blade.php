@extends('layouts.app')
@section('title', 'Record Ledger Entry - Debits & Credits')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        type: '{{ old('type', 'debit') }}',
        party_type: '{{ old('customer_id') ? 'customer' : (old('supplier_id') ? 'supplier' : 'none') }}',
        customerId: '{{ old('customer_id', '') }}',
        supplierId: '{{ old('supplier_id', '') }}'
     }">

    {{-- Breadcrumb & Title --}}
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Dashboard</a>
            <span>/</span>
            <a href="{{ route('debits-credits.index') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Debits & Credits</a>
            <span>/</span>
            <span class="text-teal-600 dark:text-teal-400 font-semibold">Record Entry</span>
        </div>
        <div class="flex items-center justify-between mt-2">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </span>
                    Record Ledger Entry
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Post a new operational credit or debit entry with counterparty attribution.
                </p>
            </div>
            <a href="{{ route('debits-credits.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-sm">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Ledger
            </a>
        </div>
    </div>

    {{-- Error Banner --}}
    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-800 dark:text-rose-200">
            <div class="font-bold flex items-center gap-1.5 mb-1 text-rose-900 dark:text-rose-100">
                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Please correct the following errors:
            </div>
            <ul class="list-disc list-inside space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Container --}}
    <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        @can('debits-credits.create')
            <form action="{{ route('debits-credits.store') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Type Selection Cards --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Flow Direction <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label :class="type === 'debit' ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300 ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-2xl p-4 flex items-center gap-3.5 cursor-pointer transition">
                            <input type="radio" name="type" value="debit" x-model="type" class="sr-only">
                            <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">Debit (Money Out)</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Expense, payout, supplier payment</div>
                            </div>
                        </label>

                        <label :class="type === 'credit' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-2xl p-4 flex items-center gap-3.5 cursor-pointer transition">
                            <input type="radio" name="type" value="credit" x-model="type" class="sr-only">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">Credit (Money In)</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Customer receipt, income, deposit</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Amount (RWF) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-mono text-xs font-bold">
                                RWF
                            </div>
                            <input type="number"
                                   name="amount"
                                   step="0.01"
                                   min="0.01"
                                   value="{{ old('amount') }}"
                                   required
                                   placeholder="0.00"
                                   class="w-full pl-12 pr-4 py-2 text-sm font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                        </div>
                    </div>

                    {{-- Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Voucher Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               name="date"
                               value="{{ old('date', now()->toDateString()) }}"
                               required
                               class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                {{-- Counterparty Selection --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 space-y-3">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Counterparty Attribution
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="party_type = 'none'; customerId = ''; supplierId = ''"
                                :class="party_type === 'none' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-white text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition">
                            None (General Ledger)
                        </button>
                        <button type="button"
                                @click="party_type = 'customer'; supplierId = ''"
                                :class="party_type === 'customer' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition">
                            Customer
                        </button>
                        <button type="button"
                                @click="party_type = 'supplier'; customerId = ''"
                                :class="party_type === 'supplier' ? 'bg-purple-600 text-white' : 'bg-white text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition">
                            Supplier
                        </button>
                    </div>

                    {{-- Customer Select --}}
                    <div x-show="party_type === 'customer'">
                        <select name="customer_id" x-model="customerId" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Customer...</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Supplier Select --}}
                    <div x-show="party_type === 'supplier'">
                        <select name="supplier_id" x-model="supplierId" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-purple-500 focus:ring-purple-500">
                            <option value="">Select Supplier...</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Optional Transaction Link --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Linked Transaction ID <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <input type="number"
                           name="transaction_id"
                           value="{{ old('transaction_id') }}"
                           placeholder="e.g. 35"
                           class="w-full py-2 px-3 text-sm font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                        If this entry directly reflects an existing Transaction, specify its ID.
                    </p>
                </div>

                {{-- Description / Narrative --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Narrative / Description
                    </label>
                    <textarea name="description"
                              rows="3"
                              placeholder="Operational reason, reference, or voucher details..."
                              class="w-full text-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">{{ old('description') }}</textarea>
                </div>

                {{-- Action Bar --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('debits-credits.index') }}"
                       class="px-4 py-2.5 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Post Ledger Entry
                    </button>
                </div>
            </form>
        @else
            <div class="p-6 text-center text-amber-700 dark:text-amber-300">
                You do not have permission to record ledger entries.
            </div>
        @endcan
    </div>
</div>
@endsection
