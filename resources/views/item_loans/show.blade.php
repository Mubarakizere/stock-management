@extends('layouts.app')
@section('title', "Loan #{$loan->id}")

@section('content')
@php
    $fmt = fn($n) => number_format((float)$n, 2);
    $qty = (float) $loan->quantity;
    $ret = (float) $loan->quantity_returned;
    $rem = max($qty - $ret, 0.0);
    $pct = $qty > 0 ? min(100, (int) round(($ret / $qty) * 100)) : 0;

    $today = \Carbon\Carbon::today();
    $due = $loan->due_date ? \Carbon\Carbon::parse($loan->due_date) : null;
    $isOverdue = $loan->status !== 'returned' && $due && $due->isPast() && $rem > 0.001;
    $dueDiff = $due ? $today->diffInDays($due, false) : null;
    $isDueSoon = $loan->status !== 'returned' && $due && !$isOverdue && $dueDiff !== null && $dueDiff <= 3 && $dueDiff >= 0;

    $voucherCode = '#IL-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT);
    $isGiven = $loan->direction === 'given';
    $hasReturns = $loan->returns->isNotEmpty();
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Top Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <a href="{{ route('item-loans.index') }}" class="hover:underline">Item Loans</a>
                <span>/</span>
                <span class="font-mono">{{ $voucherCode }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold font-mono text-slate-900 dark:text-white tracking-tight">
                    {{ $voucherCode }}
                </h1>
                {{-- Direction Badge --}}
                @if($isGiven)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l10-10M17 7H7m10 0v10" />
                        </svg>
                        We LENT (Given to Partner)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/60 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7 17M7 17h10M7 17V7" />
                        </svg>
                        We BORROWED (Taken from Partner)
                    </span>
                @endif

                {{-- Status Badge --}}
                @if($loan->status === 'returned')
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Fully Returned
                    </span>
                @elseif($loan->status === 'partial')
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300">
                        <svg class="w-3.5 h-3.5 text-sky-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        Partially Returned
                    </span>
                @elseif($isOverdue || $loan->status === 'overdue')
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300">
                        <svg class="w-3.5 h-3.5 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Overdue Return
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300">
                        <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        Pending Return
                    </span>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2.5">
            <a href="{{ route('item-loans.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back</span>
            </a>

            @can('item-loans.edit')
                <a href="{{ route('item-loans.edit', $loan) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                </a>
            @endcan

            @can('item-loans.delete')
                @if(!$hasReturns)
                    <form action="{{ route('item-loans.destroy', $loan) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this loan voucher and its stock movements?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800/60 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Delete</span>
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 p-4 text-xs text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Card 1: Principal Loaned --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Quantity Loaned</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black font-mono text-slate-900 dark:text-white">{{ $fmt($qty) }}</span>
                <span class="text-xs text-slate-400 font-medium">{{ $loan->unit ?? 'pcs' }}</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Issued on <span class="font-mono font-medium text-slate-700 dark:text-slate-300">{{ optional($loan->loan_date)->format('M d, Y') }}</span>
            </div>
        </div>

        {{-- Card 2: Returned --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Quantity Returned</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">{{ $fmt($ret) }}</span>
                <span class="text-xs text-slate-400 font-medium">{{ $loan->unit ?? 'pcs' }}</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="font-mono font-semibold">{{ $pct }}%</span> settled across {{ $loan->returns->count() }} return entries
            </div>
        </div>

        {{-- Card 3: Remaining --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Remaining Outstanding</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black font-mono {{ $rem > 0 ? ($isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400') : 'text-slate-400' }}">
                    {{ $fmt($rem) }}
                </span>
                <span class="text-xs text-slate-400 font-medium">{{ $loan->unit ?? 'pcs' }}</span>
            </div>
            <div class="mt-2 text-xs">
                @if($loan->due_date)
                    <span class="text-slate-400">Due:</span>
                    <span class="font-mono font-medium {{ $isOverdue ? 'text-rose-600 font-bold' : ($isDueSoon ? 'text-amber-600 font-semibold' : 'text-slate-700 dark:text-slate-300') }}">
                        {{ optional($loan->due_date)->format('M d, Y') }}
                    </span>
                    @if($isOverdue)
                        <span class="ml-1 text-[10px] font-bold text-rose-600">(Overdue)</span>
                    @endif
                @else
                    <span class="text-slate-400">No scheduled due date</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Return Progress Bar --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
        <div class="flex items-center justify-between text-xs mb-2">
            <span class="font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px]">
                Return Settlement Progress
            </span>
            <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $pct }}%</span>
        </div>
        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
            <div class="h-2.5 rounded-full {{ $pct === 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $pct }}%"></div>
        </div>
    </div>

    {{-- Detail Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Partner Card --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Partner Entity</span>
            <div class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>{{ $loan->partner->name ?? '—' }}</span>
            </div>
            @if(optional($loan->partner)->contact_person)
                <div class="text-xs text-slate-500 dark:text-slate-400">Contact: {{ $loan->partner->contact_person }}</div>
            @endif
            @if(optional($loan->partner)->phone)
                <div class="text-xs text-slate-500 dark:text-slate-400">Phone: {{ $loan->partner->phone }}</div>
            @endif
        </div>

        {{-- Item Card --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Loaned Asset / Item</span>
            <div class="text-base font-bold text-slate-900 dark:text-white">
                {{ $loan->item_name }}
            </div>
            @if($loan->product)
                <div class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    <span>Linked: {{ $loan->product->name }} (Stock: {{ $loan->product->stock }})</span>
                </div>
            @else
                <div class="text-xs text-slate-400 italic">Unlinked Asset (Manual Tracking)</div>
            @endif
        </div>

        {{-- Audit & User --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Audit Info</span>
            <div class="text-xs text-slate-700 dark:text-slate-300">
                Created by: <span class="font-semibold">{{ optional($loan->user)->name ?? 'System' }}</span>
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                {{ optional($loan->created_at)->format('Y-m-d H:i') }}
            </div>
            @if($loan->updated_at && $loan->updated_at != $loan->created_at)
                <div class="text-[11px] text-slate-400 font-mono">
                    Updated: {{ optional($loan->updated_at)->format('Y-m-d H:i') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Notes if present --}}
    @if($loan->notes)
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Notes & Context</h3>
            <p class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ $loan->notes }}</p>
        </div>
    @endif

    {{-- Dual Column: Record Return + Return History --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Left: Record Return Form --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Record Item Return</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Post inbound/outbound returns against this loan.</p>
                    </div>
                </div>
                @if($rem <= 0)
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        Fully Settled
                    </span>
                @endif
            </div>

            @if (isset($errors) && $errors->any())
                <div class="rounded-xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 p-3 text-xs text-rose-800 dark:text-rose-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($rem > 0)
                <form method="POST" action="{{ route('item-loans.return', $loan) }}" class="space-y-4"
                      x-data="{ retQty: '{{ old('returned_qty', $rem) }}' }">
                    @csrf

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Returned Quantity <span class="text-rose-500">*</span>
                            </label>
                            <button type="button" @click="retQty = '{{ $rem }}'"
                                    class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                Return All ({{ $fmt($rem) }} {{ $loan->unit ?? 'pcs' }})
                            </button>
                        </div>
                        <div class="relative">
                            <input type="number" step="0.01" min="0.01" max="{{ $rem }}" name="returned_qty" x-model="retQty" required
                                   class="w-full py-2.5 px-3 rounded-xl text-sm font-mono font-bold bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/50">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400 font-mono">
                                {{ $loan->unit ?? 'pcs' }}
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Maximum remaining to return: {{ $fmt($rem) }} {{ $loan->unit ?? 'pcs' }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Return Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="return_date" value="{{ old('return_date', now()->toDateString()) }}" required
                               class="w-full py-2.5 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/50">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Notes / Condition
                        </label>
                        <textarea name="note" rows="2" placeholder="e.g. Returned in original crates, signed receipt #..."
                                  class="w-full py-2.5 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/50">{{ old('note') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end pt-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-500/20 active:scale-95 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Save Return</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="py-8 text-center text-slate-400 text-xs">
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="font-bold text-slate-800 dark:text-slate-200">Fully Settled</div>
                    <p class="text-[11px] text-slate-400 mt-1">All loaned quantities have been accounted for and returned.</p>
                </div>
            @endif
        </div>

        {{-- Right: Returns Log History --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Returns Settlement Log</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $loan->returns->count() }} return entries recorded</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/60 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-700">
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3 text-right">Qty Returned</th>
                            <th class="py-2.5 px-3">Notes</th>
                            <th class="py-2.5 px-3">Logged By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-slate-700 dark:text-slate-300">
                        @forelse($loan->returns as $r)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="py-2.5 px-3 font-mono">{{ optional($r->return_date)->format('Y-m-d') }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $fmt($r->returned_qty) }} <span class="text-[10px] font-normal text-slate-400">{{ $loan->unit ?? 'pcs' }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-[11px] max-w-[150px] truncate" title="{{ $r->note }}">
                                    {{ $r->note ?: '—' }}
                                </td>
                                <td class="py-2.5 px-3 text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ optional($r->user)->name ?? 'System' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                                    No returns have been recorded for this loan voucher yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
