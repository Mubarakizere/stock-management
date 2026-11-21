@extends('layouts.app')
@section('title', 'Internal Money Transfer')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header & Breadcrumbs --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('transactions.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Transactions</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Transfer</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </span>
                Internal Money Transfer
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Shift cash between vault accounts, banks, and mobile wallets.
            </p>
        </div>

        <a href="{{ route('transactions.index') }}"
           class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Back to Ledger</span>
        </a>
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

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm">
        <form action="{{ route('transactions.transfer.store') }}" method="POST" class="space-y-0">
            @csrf

            <div class="p-6 space-y-5">
                {{-- Amount --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Transfer Amount (RWF) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required placeholder="e.g. 50000" class="w-full text-base font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                    @error('amount') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Date --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Transfer Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                    @error('transaction_date') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- From Channel --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            From (Source Account) <span class="text-rose-500">*</span>
                        </label>
                        <select name="from_method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Source…</option>
                            @foreach($channels as $ch)
                                <option value="{{ $ch->slug }}" @selected(old('from_method') == $ch->slug)>
                                    {{ $ch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('from_method') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- To Channel --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            To (Target Account) <span class="text-rose-500">*</span>
                        </label>
                        <select name="to_method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Target…</option>
                            @foreach($channels as $ch)
                                <option value="{{ $ch->slug }}" @selected(old('to_method') == $ch->slug)>
                                    {{ $ch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('to_method') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Transfer Purpose / Notes
                    </label>
                    <textarea name="notes" rows="3" placeholder="Optional details (e.g. Deposit to bank for vendor payments)..." class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                <a href="{{ route('transactions.index') }}" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-blue-600 hover:bg-blue-500 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span>Execute Transfer</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
