@extends('layouts.app')
@section('title', 'Purchases')

@section('content')
@php
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);

    $isPaginated = $purchases instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        // Delete Confirmation Modal
        deleteModalOpen: false,
        deleteActionUrl: '',
        deletePurchaseId: '',
        deleteSupplierName: '',
        deleteAmount: '',

        confirmDelete(url, id, supplier, amount) {
            this.deleteActionUrl = url;
            this.deletePurchaseId = id;
            this.deleteSupplierName = supplier;
            this.deleteAmount = amount;
            this.deleteModalOpen = true;
        }
     }">

    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Dashboard</a>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-800 dark:text-slate-200 font-medium">Purchases</span>
            </nav>

            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Purchases & Supply Inbound
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Procurement Ledger
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                    {{ number_format($purchases->total()) }} Orders
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Manage raw material acquisitions, supplier purchase orders, payment settlements, and supplier balances.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2.5 shrink-0">
            @can('suppliers.view')
                <a href="{{ route('suppliers.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Suppliers Directory</span>
                </a>
            @endcan

            @can('purchases.create')
                <a href="{{ route('purchases.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-teal-600 text-white hover:bg-teal-500 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>New Purchase Order</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Session Notifications --}}
    @if(session('success'))
        <div class="rounded-xl bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 p-4 text-sm text-teal-800 dark:text-teal-200 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-teal-500/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-teal-600 dark:text-teal-400 hover:opacity-75">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    @cannot('purchases.view')
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/80 bg-amber-50/50 dark:bg-amber-950/30 p-6 text-center">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-amber-900 dark:text-amber-200">Access Restricted</h3>
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-1 max-w-sm mx-auto">
                You do not possess the required permissions to view supplier purchases and procurement orders.
            </p>
        </div>
    @else

        {{-- 4-KPI Executive Deck --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: Total Spend --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Purchase Volume</span>
                    <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-xs font-medium text-slate-400">RWF</span>
                    <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                        {{ $fmt2($stats['total_spend'] ?? 0) }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    Gross supply orders value
                </p>
            </div>

            {{-- Card 2: Total Settled / Paid --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Settled Payments</span>
                    <span class="p-2 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-xs font-medium text-slate-400">RWF</span>
                    <span class="text-2xl font-bold tracking-tight text-teal-600 dark:text-teal-400 tabular-nums">
                        {{ $fmt2($stats['total_paid'] ?? 0) }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    Paid cash, bank & mobile transfers
                </p>
            </div>

            {{-- Card 3: Outstanding Supplier Debt --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Supplier Debt (Balance)</span>
                    <span class="p-2 rounded-xl {{ ($stats['total_balance'] ?? 0) > 0 ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-xs font-medium text-slate-400">RWF</span>
                    <span class="text-2xl font-bold tracking-tight tabular-nums {{ ($stats['total_balance'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $fmt2($stats['total_balance'] ?? 0) }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    Accounts payable to suppliers
                </p>
            </div>

            {{-- Card 4: Orders Count --}}
            <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Purchase Orders</span>
                    <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                        {{ number_format($stats['total_count'] ?? 0) }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">active orders</span>
                </div>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    Filtered procurement records
                </p>
            </div>
        </div>

        {{-- Payment Channel Strip --}}
        @if(isset($paymentChannels) && $paymentChannels->isNotEmpty())
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span>Settlement Channels Active</span>
                    </span>
                    <span class="text-[11px] text-slate-400">Payment accounts configured</span>
                </div>

                <div class="flex flex-wrap gap-3 pt-3">
                    @foreach ($paymentChannels as $ch)
                        @php
                            $channelSlug = $ch->slug;
                            $chBadge = match($channelSlug) {
                                'cash' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200/60 dark:border-emerald-800/60',
                                'bank' => 'bg-blue-50 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200/60 dark:border-blue-800/60',
                                'momo', 'mobile_money' => 'bg-purple-50 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200/60 dark:border-purple-800/60',
                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                            };
                        @endphp
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs {{ $chBadge }}">
                            <span class="w-2 h-2 rounded-full {{ $channelSlug === 'cash' ? 'bg-emerald-500' : ($channelSlug === 'bank' ? 'bg-blue-500' : 'bg-purple-500') }}"></span>
                            <span class="font-bold">{{ $ch->name }}</span>
                            <span class="text-[10px] font-mono text-slate-400">({{ strtoupper($channelSlug) }})</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Filter Toolbar --}}
        <form method="GET" action="{{ route('purchases.index') }}"
              class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-4">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                {{-- Search query --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Search Purchases
                    </label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Invoice #, Supplier, Reference, Notes…"
                               class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white pl-9 pr-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                    </div>
                </div>

                {{-- Supplier Select --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Supplier
                    </label>
                    <select name="supplier_id"
                            class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected((string)request('supplier_id') === (string)$s->id)>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Payment Status --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Payment Status
                    </label>
                    <select name="status"
                            class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <option value="">All Statuses</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed (Settled)</option>
                        <option value="partial" @selected(request('status') === 'partial')>Partial (Credit)</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending Payment</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>

                {{-- Payment Channel --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Payment Channel
                    </label>
                    <select name="payment_channel"
                            class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                        <option value="">Any Channel</option>
                        <option value="cash" @selected(request('payment_channel') === 'cash')>Cash</option>
                        <option value="bank" @selected(request('payment_channel') === 'bank')>Bank Transfer</option>
                        <option value="momo" @selected(request('payment_channel') === 'momo')>Mobile Money (MoMo)</option>
                    </select>
                </div>

                {{-- Date Range From & To in 1 col --}}
                <div class="grid grid-cols-2 gap-1.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                            From
                        </label>
                        <input type="date" name="from" value="{{ request('from') }}"
                               class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-2 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                            To
                        </label>
                        <input type="date" name="to" value="{{ request('to') }}"
                               class="w-full text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-2 py-2 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition">
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span>Show:</span>
                    <select name="per_page" onchange="this.form.submit()"
                            class="text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2 py-1">
                        <option value="10" @selected(request('per_page') == 10)>10</option>
                        <option value="15" @selected(request('per_page') == 15)>15</option>
                        <option value="25" @selected(request('per_page', 25) == 25)>25</option>
                        <option value="50" @selected(request('per_page') == 50)>50</option>
                        <option value="100" @selected(request('per_page') == 100)>100</option>
                    </select>
                    <span>purchases per page</span>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center">
                    @if(request()->hasAny(['search', 'supplier_id', 'status', 'payment_channel', 'from', 'to']))
                        <a href="{{ route('purchases.index') }}"
                           class="px-3.5 py-1.5 text-xs font-semibold rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition">
                            Clear Filters
                        </a>
                    @endif

                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 transition shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span>Apply Filters</span>
                    </button>
                </div>
            </div>
        </form>

        {{-- Purchases Table Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            @include('purchases._table', ['purchases' => $purchases])

            {{-- Pagination Footer --}}
            @if($isPaginated)
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $purchases->withQueryString()->links() }}
                </div>
            @endif
        </div>

    @endcannot

    {{-- Delete Confirmation Modal --}}
    @can('purchases.delete')
    <div x-show="deleteModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="deleteModalOpen = false">
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xl p-6"
             @click.outside="deleteModalOpen = false">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete Purchase Order?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        Are you sure you want to delete purchase <strong class="text-slate-800 dark:text-slate-200" x-text="'#PO-' + deletePurchaseId"></strong> from <strong class="text-slate-800 dark:text-slate-200" x-text="deleteSupplierName"></strong> (<span class="font-mono font-bold" x-text="deleteAmount"></span>)?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300">
                ⚠️ Associated stock movement entries will be reversed, and any outstanding supplier loan balances will be cleaned up.
            </div>

            <div class="mt-6 flex items-center justify-end gap-2.5">
                <button type="button"
                        @click="deleteModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Cancel
                </button>
                <form :action="deleteActionUrl" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-rose-600 text-white hover:bg-rose-500 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Yes, Delete Purchase</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection
