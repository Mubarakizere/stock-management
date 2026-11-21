@extends('layouts.app')
@section('title', "Transaction #TX-" . str_pad($transaction->id, 5, '0', STR_PAD_LEFT))

@section('content')
@php
    use Carbon\Carbon;
    $date = $transaction->transaction_date
        ? Carbon::parse($transaction->transaction_date)->format('M d, Y H:i')
        : '—';
    $isCredit = $transaction->type === 'credit';
    $method = strtolower($transaction->method ?? '');
    $channelPill = match($method) {
        'cash'         => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300',
        'bank'         => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300',
        'momo', 'mobile-money', 'airtel-money' => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950/40 dark:text-purple-300',
        default        => 'bg-slate-100 text-slate-700 ring-slate-600/20 dark:bg-slate-800 dark:text-slate-300',
    };
@endphp

@cannot('transactions.view')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="rounded-2xl border border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/40 p-6">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h1 class="text-base font-bold text-amber-900 dark:text-amber-100">Permission Required</h1>
                    <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-200/80">You do not have permission to view this transaction.</p>
                </div>
            </div>
        </div>
    </div>
@else
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{ deleteModalOpen: false }">

    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('transactions.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Transactions</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Voucher #TX-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-sm ring-1 ring-teal-200 dark:ring-teal-800">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Transaction #TX-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}
                    </h1>
                    <div class="flex items-center gap-3 text-xs text-slate-400 mt-0.5">
                        <span>Posted on {{ $date }}</span>
                        @if($transaction->user)
                            <span>• Recorded by {{ $transaction->user->name }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('transactions.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Ledger</span>
            </a>

            @can('transactions.edit')
                <a href="{{ route('transactions.edit', $transaction) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit</span>
                </a>
            @endcan

            @can('transactions.delete')
                <button type="button" @click="deleteModalOpen = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Delete</span>
                </button>
            @endcan
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Amount Card --}}
        <div class="lg:col-span-1 space-y-5">
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Flow Direction</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $isCredit ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20' }}">
                        {{ $isCredit ? 'Credit (IN)' : 'Debit (OUT)' }}
                    </span>
                </div>

                <div class="text-3xl font-bold font-mono tracking-tight {{ $isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $isCredit ? '+' : '-' }}{{ number_format((float) $transaction->amount, 2) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                    <span class="text-slate-400 block mb-1">Payment Channel</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide ring-1 {{ $channelPill }}">
                        {{ strtoupper(str_replace('-', ' ', $transaction->method ?? '—')) }}
                    </span>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-xs space-y-1.5 text-slate-500 dark:text-slate-400">
                    <div class="flex items-center justify-between">
                        <span>Logged Timestamp:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ optional($transaction->created_at)->format('M d, Y H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Details & Origin --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Transaction Context & Linked Records</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Commercial party allocations and ledger linkages</p>
                </div>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-1">Customer / Client</span>
                        @if($transaction->customer)
                            <a href="{{ route('customers.show', $transaction->customer) }}" class="inline-flex items-center gap-1.5 font-semibold text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400">
                                <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                {{ $transaction->customer->name }}
                            </a>
                        @else
                            <span class="text-slate-400 italic">None / Not Linked</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-1">Supplier / Vendor</span>
                        @if($transaction->supplier)
                            <a href="{{ route('suppliers.show', $transaction->supplier) }}" class="inline-flex items-center gap-1.5 font-semibold text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                {{ $transaction->supplier->name }}
                            </a>
                        @else
                            <span class="text-slate-400 italic">None / Not Linked</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-1">Linked Operational Record</span>
                        @if($transaction->is_withdrawal)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-semibold">
                                Boss Withdrawal
                            </span>
                        @elseif($transaction->is_transfer)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 font-semibold">
                                Inter-Account Transfer
                            </span>
                        @elseif($transaction->sale)
                            <a href="{{ route('sales.show', $transaction->sale) }}" class="inline-flex items-center gap-1 text-teal-600 dark:text-teal-400 font-bold hover:underline">
                                Sales Order #{{ $transaction->sale->id }}
                            </a>
                        @elseif($transaction->purchase)
                            <a href="{{ route('purchases.show', $transaction->purchase) }}" class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold hover:underline">
                                Purchase Order #{{ $transaction->purchase->id }}
                            </a>
                        @else
                            <span class="text-slate-500 font-medium">Direct Manual Cash Movement</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-1">Responsible Operator</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ optional($transaction->user)->name ?? 'System Automated' }}</span>
                    </div>

                    <div class="sm:col-span-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block mb-1">Notes & Narrative</span>
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 text-slate-800 dark:text-slate-200 whitespace-pre-line leading-relaxed">
                            {{ $transaction->notes ?: 'No additional narrative recorded on this transaction.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Two-Step Deletion Modal --}}
    @can('transactions.delete')
    <div x-show="deleteModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="deleteModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 ring-1 ring-rose-200 dark:ring-rose-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete Transaction</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to delete transaction <strong class="font-mono text-slate-800 dark:text-slate-200">#TX-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300">
                <p>Deleting this record will recompute running balances and channel vault sums.</p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Keep Record
                </button>
                <form action="{{ route('transactions.destroy', $transaction) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-rose-600 hover:bg-rose-500 shadow-sm transition">
                        Confirm Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan

</div>
@endcannot
@endsection
