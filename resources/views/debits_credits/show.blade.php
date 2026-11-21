@extends('layouts.app')
@section('title', 'Voucher #DC-' . str_pad($entry->id, 5, '0', STR_PAD_LEFT) . ' - Debits & Credits')

@section('content')
@php
    $voucherCode = '#DC-' . str_pad($entry->id, 5, '0', STR_PAD_LEFT);
    $isCredit = $entry->type === 'credit';
    $formattedDate = \Carbon\Carbon::parse($entry->date);
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{ deleteModalOpen: false }">

    {{-- Breadcrumb & Title --}}
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Dashboard</a>
            <span>/</span>
            <a href="{{ route('debits-credits.index') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Debits & Credits</a>
            <span>/</span>
            <span class="text-teal-600 dark:text-teal-400 font-semibold">{{ $voucherCode }}</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-2">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                    <span class="p-2 rounded-xl {{ $isCredit ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    Ledger Voucher {{ $voucherCode }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Audited accounting voucher recorded on {{ $formattedDate->format('M d, Y') }} ({{ $formattedDate->diffForHumans() }}).
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2.5">
                <a href="{{ route('debits-credits.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-sm">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Ledger
                </a>

                @can('debits-credits.edit')
                    <a href="{{ route('debits-credits.edit', $entry->id) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Entry
                    </a>
                @endcan

                @can('debits-credits.delete')
                    <button type="button"
                            @click="deleteModalOpen = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60 dark:hover:bg-rose-900/60 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Main Voucher Card --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        {{-- Hero Value Banner --}}
        <div class="p-6 sm:p-8 bg-gradient-to-br from-slate-50 to-white dark:from-slate-800/50 dark:to-slate-900 border-b border-slate-100 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Voucher Flow</span>
                        @if($isCredit)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60">
                                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                                Credit (Money In)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60">
                                <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                                Debit (Money Out)
                            </span>
                        @endif
                    </div>
                    <div class="mt-2 text-3xl sm:text-4xl font-extrabold font-mono tracking-tight {{ $isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $isCredit ? '+' : '-' }} RWF {{ number_format($entry->amount, 2) }}
                    </div>
                </div>

                {{-- Status Tag --}}
                <div class="text-right sm:border-l sm:border-slate-200 sm:dark:border-slate-800 sm:pl-8">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block">Ledger Status</span>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800/60 mt-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                        Reconciled & Audited
                    </span>
                    <div class="mt-1 font-mono text-xs text-slate-400">
                        Posted: {{ $formattedDate->format('Y-m-d') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Voucher Metadata Grid --}}
        <div class="p-6 sm:p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Counterparty --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Counterparty</span>
                    <div class="mt-2">
                        @if($entry->customer)
                            <a href="{{ route('customers.show', $entry->customer->id) }}"
                               class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                <span class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </span>
                                {{ $entry->customer->name }}
                            </a>
                            <div class="text-[11px] text-slate-500 mt-1">Customer Account</div>
                        @elseif($entry->supplier)
                            <a href="{{ route('suppliers.show', $entry->supplier->id) }}"
                               class="inline-flex items-center gap-2 text-sm font-bold text-purple-600 dark:text-purple-400 hover:underline">
                                <span class="p-1.5 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </span>
                                {{ $entry->supplier->name }}
                            </a>
                            <div class="text-[11px] text-slate-500 mt-1">Supplier Account</div>
                        @else
                            <div class="inline-flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                                <span class="p-1.5 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </span>
                                General Ledger
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1">Internal / Direct Voucher</div>
                        @endif
                    </div>
                </div>

                {{-- Linked Transaction --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Linked Transaction</span>
                    <div class="mt-2">
                        @if($entry->transaction_id)
                            @can('transactions.view')
                                <a href="{{ route('transactions.show', $entry->transaction_id) }}"
                                   class="inline-flex items-center gap-1.5 font-mono font-bold text-sm text-teal-600 dark:text-teal-400 hover:underline">
                                    <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    #TX-{{ str_pad($entry->transaction_id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            @else
                                <span class="font-mono font-bold text-sm text-slate-700 dark:text-slate-300">
                                    #TX-{{ str_pad($entry->transaction_id, 5, '0', STR_PAD_LEFT) }}
                                </span>
                            @endcan
                            <div class="text-[11px] text-slate-500 mt-1">Cash Register Entry</div>
                        @else
                            <span class="font-mono text-sm text-slate-400">None</span>
                            <div class="text-[11px] text-slate-500 mt-1">Standalone voucher</div>
                        @endif
                    </div>
                </div>

                {{-- Recorded By --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Recorded By</span>
                    <div class="mt-2 flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($entry->user->name ?? 'U', 0, 1)) }}
                        </span>
                        <div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">
                                {{ $entry->user->name ?? 'System' }}
                            </div>
                            <div class="text-[11px] text-slate-400">
                                {{ $entry->user->email ?? 'Automated Ledger' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Narrative & Notes --}}
            <div class="p-5 rounded-xl bg-slate-50/60 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-2">Narrative & Notes</span>
                <p class="text-sm text-slate-800 dark:text-slate-200 leading-relaxed font-sans">
                    {{ $entry->description ?: 'No narrative recorded for this entry.' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Two-Step Delete Modal --}}
    @can('debits-credits.delete')
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
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Delete Ledger Entry
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to delete <strong class="text-slate-800 dark:text-slate-200">{{ $voucherCode }}</strong> for <strong class="font-mono text-slate-800 dark:text-slate-200">{{ number_format($entry->amount, 2) }} RWF</strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Ledger Audit Impact
                </div>
                <p class="text-amber-700 dark:text-amber-400 pl-5">
                    This will permanently remove the double-entry mirror from the system.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Keep Record
                </button>
                <form action="{{ route('debits-credits.destroy', $entry->id) }}" method="POST">
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
@endsection

