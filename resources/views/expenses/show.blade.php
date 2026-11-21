@extends('layouts.app')
@section('title', "Expense #EXP-" . str_pad($expense->id, 5, '0', STR_PAD_LEFT))

@section('content')
@php
    $method = strtolower($expense->method ?? '');
    $channelPill = match($method) {
        'cash'         => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300',
        'bank'         => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300',
        'momo', 'mobile-money', 'airtel-money' => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950/40 dark:text-purple-300',
        default        => 'bg-slate-100 text-slate-700 ring-slate-600/20 dark:bg-slate-800 dark:text-slate-300',
    };
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        deleteModalOpen: false,
        openDeleteModal() { this.deleteModalOpen = true; },
        closeDeleteModal() { this.deleteModalOpen = false; }
     }">

    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('expenses.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Expenses</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Voucher #EXP-{{ str_pad($expense->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-sm ring-1 ring-teal-200 dark:ring-teal-800">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Expense Voucher #EXP-{{ str_pad($expense->id, 5, '0', STR_PAD_LEFT) }}
                    </h1>
                    <div class="flex items-center gap-3 text-xs text-slate-400 mt-0.5">
                        <span>Disbursed on {{ optional($expense->date)->format('M d, Y') ?? '—' }}</span>
                        @if($expense->creator)
                            <span>• Recorded by {{ $expense->creator->name }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('expenses.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Ledger</span>
            </a>

            @can('expenses.edit')
                <a href="{{ route('expenses.edit', $expense) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit</span>
                </a>
            @endcan

            @can('expenses.delete')
                <button type="button" @click="openDeleteModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Delete</span>
                </button>
            @endcan
        </div>
    </div>

    {{-- Main Financial Overview Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Amount & Primary Badges --}}
        <div class="lg:col-span-1 space-y-5">
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Outflow Total</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide ring-1 {{ $channelPill }}">
                        {{ strtoupper(str_replace('-', ' ', $expense->method ?? '—')) }}
                    </span>
                </div>

                <div class="text-3xl font-bold font-mono text-slate-900 dark:text-white tracking-tight">
                    {{ number_format((float) $expense->amount, 2) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>

                @if($expense->reference)
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <span class="text-slate-400 block mb-0.5">Reference / Receipt Number</span>
                        <span class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ $expense->reference }}</span>
                    </div>
                @endif

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-xs space-y-1.5 text-slate-500 dark:text-slate-400">
                    <div class="flex items-center justify-between">
                        <span>Created:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ optional($expense->created_at)->format('M d, Y H:i') ?? '—' }}</span>
                    </div>
                    @if($expense->updated_at && $expense->updated_at != $expense->created_at)
                        <div class="flex items-center justify-between">
                            <span>Last Updated:</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $expense->updated_at->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Voucher Accounting Details --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Voucher Classification & Payee</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Accounting allocations and disbursements</p>
                </div>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-1">Expense Category</span>
                        @if($expense->category)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 font-semibold text-slate-800 dark:text-slate-200">
                                <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                {{ $expense->category->name }}
                            </div>
                        @else
                            <span class="text-slate-400 italic">Uncategorized</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-1">Vendor / Payee</span>
                        @if($expense->supplier)
                            <a href="{{ route('suppliers.show', $expense->supplier) }}" class="inline-flex items-center gap-1.5 font-semibold text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span>{{ $expense->supplier->name }}</span>
                            </a>
                        @else
                            <span class="text-slate-500 dark:text-slate-400 font-medium">Internal / General Payee</span>
                        @endif
                    </div>

                    <div class="sm:col-span-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block mb-1">Description & Purpose</span>
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 text-slate-800 dark:text-slate-200 whitespace-pre-line leading-relaxed">
                            {{ $expense->note ?: 'No specific description or narrative recorded on this expense voucher.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Two-Step Deletion Modal --}}
    @can('expenses.delete')
    <div x-show="deleteModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="closeDeleteModal()"
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
                        Delete Expense Voucher
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to remove voucher <strong class="font-mono text-slate-800 dark:text-slate-200">#EXP-{{ str_pad($expense->id, 5, '0', STR_PAD_LEFT) }}</strong> for <strong class="font-mono text-slate-800 dark:text-slate-200">{{ number_format((float) $expense->amount, 2) }} RWF</strong>?
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
                    Deleting this entry will remove the outflow from cash balance sheets and adjust historical expense reporting.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="closeDeleteModal()" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Keep Record
                </button>
                <form action="{{ route('expenses.destroy', $expense) }}" method="POST">
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
