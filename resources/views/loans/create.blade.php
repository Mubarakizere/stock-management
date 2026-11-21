@extends('layouts.app')
@section('title', 'Create New Loan')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        type: '{{ old('type', 'given') }}',
        customerId: '{{ old('customer_id', '') }}',
        supplierId: '{{ old('supplier_id', '') }}'
     }">

    {{-- Breadcrumb & Title --}}
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Dashboard</a>
            <span>/</span>
            <a href="{{ route('loans.index') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Loans Portfolio</a>
            <span>/</span>
            <span class="text-teal-600 dark:text-teal-400 font-semibold">New Loan</span>
        </div>
        <div class="flex items-center justify-between mt-2">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </span>
                    Record New Loan
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Establish a customer debt (given) or a supplier credit obligation (taken).
                </p>
            </div>
            <a href="{{ route('loans.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-sm">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Loans
            </a>
        </div>
    </div>

    {{-- Error Alert --}}
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
        @can('loans.create')
            <form action="{{ route('loans.store') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Direction Radio Cards --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Loan Direction <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label :class="type === 'given' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300 ring-2 ring-blue-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-2xl p-4 flex items-center gap-3.5 cursor-pointer transition">
                            <input type="radio" name="type" value="given" x-model="type" class="sr-only">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">Given (Receivable)</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">We lend money / Customer owes us</div>
                            </div>
                        </label>

                        <label :class="type === 'taken' ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-300 ring-2 ring-purple-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-2xl p-4 flex items-center gap-3.5 cursor-pointer transition">
                            <input type="radio" name="type" value="taken" x-model="type" class="sr-only">
                            <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">Taken (Payable)</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">We borrow money / We owe supplier</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Counterparty Selection --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    {{-- Customer Selector --}}
                    <div x-show="type === 'given'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Customer Account <span class="text-rose-500">*</span>
                        </label>
                        <select name="customer_id" x-model="customerId" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Customer...</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Supplier Selector --}}
                    <div x-show="type === 'taken'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Supplier Account <span class="text-rose-500">*</span>
                        </label>
                        <select name="supplier_id" x-model="supplierId" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-purple-500 focus:ring-purple-500">
                            <option value="">Select Supplier...</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Principal Amount (RWF) <span class="text-rose-500">*</span>
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

                    {{-- Initial Status --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" required class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="pending" @selected(old('status', 'pending') === 'pending')>Pending (Active Debt)</option>
                            <option value="paid" @selected(old('status') === 'paid')>Paid (Fully Settled)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    {{-- Loan Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Loan Issue Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               name="loan_date"
                               value="{{ old('loan_date', now()->toDateString()) }}"
                               required
                               class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Repayment Due Date <span class="text-slate-400 font-normal">(optional)</span>
                        </label>
                        <input type="date"
                               name="due_date"
                               value="{{ old('due_date') }}"
                               class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                {{-- Notes / Contract --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Notes & Terms
                    </label>
                    <textarea name="notes"
                              rows="3"
                              placeholder="Contract details, repayment terms, collateral, or internal notes..."
                              class="w-full text-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">{{ old('notes') }}</textarea>
                </div>

                {{-- Action Bar --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('loans.index') }}"
                       class="px-4 py-2.5 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Create Loan
                    </button>
                </div>
            </form>
        @else
            <div class="p-6 text-center text-amber-700 dark:text-amber-300">
                You do not have permission to create loans.
            </div>
        @endcan
    </div>
</div>
@endsection
