@extends('layouts.app')
@section('title', "Add Payment for Loan #LN-" . str_pad($loan->id, 5, '0', STR_PAD_LEFT))

@section('content')
@php
    use Carbon\Carbon;
    $totalPaid  = (float) ($loan->payments()->sum('amount') ?? 0);
    $remaining  = max((float) ($loan->amount ?? 0) - $totalPaid, 0);
    $progress   = ($loan->amount ?? 0) > 0 ? min(100, (int) round(($totalPaid / $loan->amount) * 100)) : 0;

    $dueDate    = $loan->due_date ? Carbon::parse($loan->due_date) : null;
    $isOverdue  = $dueDate && $dueDate->isPast() && ($loan->status !== 'paid') && $remaining > 0.01;
    $overdueDays= $isOverdue ? $dueDate->diffInDays(today()) : 0;
    $loanCode   = '#LN-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT);
    $isGiven    = $loan->type === 'given';
    $partyName  = $isGiven ? optional($loan->customer)->name : optional($loan->supplier)->name;
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Title --}}
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Dashboard</a>
            <span>/</span>
            <a href="{{ route('loans.index') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Loans</a>
            <span>/</span>
            <a href="{{ route('loans.show', $loan) }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">{{ $loanCode }}</a>
            <span>/</span>
            <span class="text-teal-600 dark:text-teal-400 font-semibold">Record Payment</span>
        </div>
        <div class="flex items-center justify-between mt-2">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </span>
                    Record Payment for {{ $loanCode }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    {{ $isGiven ? 'Customer debt repayment from ' . ($partyName ?: 'N/A') : 'Supplier credit settlement to ' . ($partyName ?: 'N/A') }}
                </p>
            </div>
            <a href="{{ route('loans.show', $loan) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-sm">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Loan
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

    {{-- Loan Summary Card --}}
    <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Loan Financial Status</h3>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $loan->status === 'paid' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $loan->status === 'paid' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ ucfirst($loan->status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Principal</span>
                <span class="text-base font-bold font-mono text-slate-900 dark:text-white mt-1 block">
                    RWF {{ number_format($loan->amount, 2) }}
                </span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Paid to Date</span>
                <span class="text-base font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1 block">
                    RWF {{ number_format($totalPaid, 2) }}
                </span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Remaining Due</span>
                <span class="text-base font-bold font-mono text-rose-600 dark:text-rose-400 mt-1 block">
                    RWF {{ number_format($remaining, 2) }}
                </span>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="text-slate-500">Repayment Progress</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $progress }}%</span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                <div class="h-2 rounded-full {{ $progress >= 100 ? 'bg-emerald-500' : 'bg-teal-500' }}" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </div>

    {{-- Payment Form --}}
    <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        @can('loans.view')
            <form action="{{ route('loan-payments.store', $loan) }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    {{-- Payment Amount --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Payment Amount (RWF) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-mono text-xs font-bold">
                                RWF
                            </div>
                            <input type="number"
                                   name="amount"
                                   step="0.01"
                                   min="0.01"
                                   max="{{ $remaining }}"
                                   value="{{ old('amount', $remaining) }}"
                                   required
                                   class="w-full pl-12 pr-4 py-2 text-sm font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    {{-- Payment Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Payment Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               name="payment_date"
                               value="{{ old('payment_date', now()->toDateString()) }}"
                               required
                               class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>

                {{-- Payment Channel --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Payment Channel / Method <span class="text-rose-500">*</span>
                    </label>
                    <select name="method" required class="w-full py-2 px-3 text-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach($paymentChannels as $ch)
                            <option value="{{ $ch->slug }}" @selected(old('method', 'cash') === $ch->slug)>{{ $ch->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Receipt Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Receipt Notes & References
                    </label>
                    <textarea name="notes"
                              rows="3"
                              placeholder="Cheque number, mobile money transaction ID, or repayment remarks..."
                              class="w-full text-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes') }}</textarea>
                </div>

                {{-- Action Bar --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('loans.show', $loan) }}"
                       class="px-4 py-2.5 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Confirm Repayment
                    </button>
                </div>
            </form>
        @else
            <div class="p-6 text-center text-amber-700 dark:text-amber-300">
                You do not have permission to record payments.
            </div>
        @endcan
    </div>
</div>
@endsection
