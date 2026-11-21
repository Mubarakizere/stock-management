@extends('layouts.app')
@section('title', 'Record Expense')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header & Breadcrumbs --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('expenses.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Expenses</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">New Voucher</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Record Operating Expense
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Post an expenditure entry to the cash disbursement ledger.
            </p>
        </div>

        <a href="{{ route('expenses.index') }}"
           class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Back to Ledger</span>
        </a>
    </div>

    {{-- Validation Errors --}}
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

    @can('expenses.create')
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm">
            <form action="{{ route('expenses.store') }}" method="POST" class="space-y-0" novalidate>
                @csrf

                <div class="p-6 space-y-5">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Fields marked with <span class="text-rose-500 font-bold">*</span> are required.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Date --}}
                        <div>
                            <label for="date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Disbursement Date <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="date"
                                type="date"
                                name="date"
                                value="{{ old('date', now()->toDateString()) }}"
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"
                                required>
                            @error('date')
                                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label for="amount" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Amount (RWF) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                name="amount"
                                value="{{ old('amount') }}"
                                placeholder="e.g. 50000"
                                class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"
                                required>
                            @error('amount')
                                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Category --}}
                        <div>
                            <label for="category_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Expense Category <span class="text-rose-500">*</span>
                            </label>
                            <select id="category_id" name="category_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500" required>
                                <option value="">Select Category…</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Supplier / Payee --}}
                        <div>
                            <label for="supplier_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Vendor / Payee (Optional)
                            </label>
                            <select id="supplier_id" name="supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                                <option value="">None / General Disbursed</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Channel / Method --}}
                        <div>
                            <label for="method" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Payment Channel <span class="text-rose-500">*</span>
                            </label>
                            <select id="method" name="method" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500" required>
                                @foreach($channels as $ch)
                                    <option value="{{ $ch->slug }}" @selected(old('method', 'cash') == $ch->slug)>
                                        {{ $ch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('method')
                                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Reference --}}
                        <div>
                            <label for="reference" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Reference / Receipt #
                            </label>
                            <input
                                id="reference"
                                type="text"
                                name="reference"
                                value="{{ old('reference') }}"
                                class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"
                                placeholder="e.g. REC-1029, MoMo Txn ID">
                            @error('reference')
                                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Note --}}
                    <div>
                        <label for="note" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Expense Notes & Purpose
                        </label>
                        <textarea
                            id="note"
                            name="note"
                            rows="3"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"
                            placeholder="Provide details regarding the expenditure purpose…">{{ old('note') }}</textarea>
                        @error('note')
                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <a href="{{ route('expenses.index') }}"
                       class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </a>

                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Save Expense</span>
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="rounded-2xl border border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/40 p-4 text-xs text-amber-800 dark:text-amber-300">
            <div class="font-bold flex items-center gap-1.5 text-sm">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Permission Required
            </div>
            <p class="mt-1">You do not have permission to record expenses. Please contact your system administrator.</p>
        </div>
    @endcan
</div>
@endsection
