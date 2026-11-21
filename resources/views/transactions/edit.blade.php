@extends('layouts.app')
@section('title', "Edit Transaction #{$transaction->id}")

@section('content')
@cannot('transactions.edit')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="rounded-2xl border border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/40 p-6">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h1 class="text-base font-bold text-amber-900 dark:text-amber-100">Permission Required</h1>
                    <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-200/80">You do not have permission to edit this transaction.</p>
                </div>
            </div>
        </div>
    </div>
@else
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header & Breadcrumbs --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('transactions.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Transactions</a>
                <span>/</span>
                <a href="{{ route('transactions.show', $transaction) }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">#TX-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Edit</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Edit Transaction Voucher
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Update details for voucher <strong class="font-mono text-slate-700 dark:text-slate-300">#TX-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @can('transactions.view')
                <a href="{{ route('transactions.show', $transaction) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span>View Voucher</span>
                </a>
            @endcan

            <a href="{{ route('transactions.index') }}"
               class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition" title="Back to Ledger">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 p-4 text-xs text-rose-800 dark:text-rose-200 space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-sm">
                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Please correct the following:
            </div>
            <ul class="list-disc pl-5 space-y-0.5 pt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="{ type: '{{ old('type', $transaction->type) }}' }" class="overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm">
        <form action="{{ route('transactions.update', $transaction) }}" method="POST" class="space-y-0">
            @csrf
            @method('PUT')

            <div class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Flow Direction --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Flow Direction <span class="text-rose-500">*</span>
                        </label>
                        <select name="type" x-model="type" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="credit">Credit (Money In +)</option>
                            <option value="debit">Debit (Money Out -)</option>
                        </select>
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Amount (RWF) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $transaction->amount) }}" required class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    {{-- Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Transaction Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="transaction_date" value="{{ old('transaction_date', \Carbon\Carbon::parse($transaction->transaction_date)->toDateString()) }}" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    {{-- Channel / Method --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Payment Channel <span class="text-rose-500">*</span>
                        </label>
                        <select name="method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            @foreach($channels as $ch)
                                <option value="{{ $ch->slug }}" @selected(old('method', $transaction->method) == $ch->slug)>
                                    {{ $ch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Counterparty --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div x-show="type === 'credit'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Customer (For Credits)
                        </label>
                        <select name="customer_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="">None / Walk-in / Cash Sale</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('customer_id', $transaction->customer_id) == $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="type === 'debit'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Supplier (For Debits)
                        </label>
                        <select name="supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="">None / General Disbursed</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id', $transaction->supplier_id) == $s->id)>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Narrative & Purpose
                    </label>
                    <textarea name="notes" rows="3" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">{{ old('notes', $transaction->notes) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                <a href="{{ route('transactions.index') }}" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Update Transaction</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endcannot
@endsection
