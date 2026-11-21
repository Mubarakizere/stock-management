@extends('layouts.app')
@section('title', "Customer: {$customer->name}")

@section('content')
@php
    $user = auth()->user();
    $canEdit = $user?->can('customers.edit');
    $canDelete = $user?->can('customers.delete');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{ showDeleteModal: false }">

    {{-- Breadcrumb Navigation --}}
    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <a href="{{ route('customers.index') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Customers</a>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $customer->name }}</span>
    </div>

    {{-- Header Profile Banner --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-xl shrink-0 ring-1 ring-teal-200 dark:ring-teal-800 shadow-sm">
                    {{ strtoupper(substr($customer->name, 0, 2)) }}
                </div>
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ $customer->name }}
                        </h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                            #CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}
                        </span>
                        @if(($outstandingCredit ?? 0) > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Outstanding Credit
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Good Standing
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                        @if($customer->phone)
                            <span class="flex items-center gap-1 font-mono">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ $customer->phone }}
                            </span>
                        @endif
                        @if($customer->email)
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $customer->email }}
                            </span>
                        @endif
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <span>Client since {{ $customer->created_at ? $customer->created_at->format('d M Y') : '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Actions Suite --}}
            <div class="flex flex-wrap items-center gap-2 pt-2 lg:pt-0">
                <a href="{{ route('sales.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Sale
                </a>

                <a href="{{ route('reports.customers.statement', ['customer_id' => $customer->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Statement
                </a>

                <a href="{{ route('reports.customers.statement.pdf', ['customer_id' => $customer->id]) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    PDF
                </a>

                @can('customers.edit')
                    <a href="{{ route('customers.edit', $customer) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                @endcan

                @can('customers.delete')
                    <button type="button" @click="showDeleteModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-rose-600 hover:text-rose-700 hover:bg-rose-50/60 dark:text-rose-400 dark:hover:bg-rose-950/30 transition" title="Delete Customer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- 4 Executive Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Lifetime Orders --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Lifetime Orders</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalOrders ?? 0) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total completed sales transactions
                </div>
            </div>
        </div>

        {{-- Lifetime Spent --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Purchases</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalSpent ?? 0, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Gross invoiced volume
                </div>
            </div>
        </div>

        {{-- Total Paid / Settled --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Tendered</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    {{ number_format($totalPaid ?? 0, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total cashflow collected
                </div>
            </div>
        </div>

        {{-- Outstanding Credit --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Outstanding Balance</span>
                <span class="w-8 h-8 rounded-xl {{ ($outstandingCredit ?? 0) > 0 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' : 'bg-slate-50 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight {{ ($outstandingCredit ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($outstandingCredit ?? 0, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    @if(($outstandingCredit ?? 0) > 0)
                        <span class="text-amber-600 dark:text-amber-400 font-medium">Pending repayment</span>
                    @else
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">✓ Zero debt</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main 2-Column Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Left Column (8 cols): Orders & Loans --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Recent Sales Orders --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Recent Sales Orders</h2>
                        <span class="ml-1 text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                            {{ $customer->sales->count() }}
                        </span>
                    </div>

                    <a href="{{ route('sales.create') }}" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                        + New Sale
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/30 dark:bg-slate-900/30">
                                <th class="py-3 px-4">Order ID</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4 text-right">Amount</th>
                                <th class="py-3 px-4 text-right">Paid</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse($customer->sales as $sale)
                                @php
                                    $saleStatus = strtolower($sale->status ?? 'completed');
                                    $due = max(0, (float)$sale->total_amount - (float)$sale->amount_paid);
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-4">
                                        <a href="{{ route('sales.show', $sale) }}" class="font-mono font-bold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition">
                                            #SALE-{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">
                                        {{ $sale->sale_date ? $sale->sale_date->format('d M Y') : $sale->created_at?->format('d M Y') }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                        {{ number_format($sale->total_amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-medium">
                                        {{ number_format($sale->amount_paid, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($due <= 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300">
                                                Due: {{ number_format($due, 2) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="{{ route('sales.show', $sale) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                                            Inspect →
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">
                                        No sales orders recorded for this customer yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Credit & Loans Facility --}}
            @if($customer->loans->count())
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Credit & Loan Facilities</h3>
                                <p class="text-xs text-slate-500">Active credit accounts extended to this client</p>
                            </div>
                        </div>

                        <span class="text-xs font-mono font-bold text-amber-600 dark:text-amber-400">
                            {{ $customer->loans->where('status', 'pending')->count() }} Active
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/70 text-[10px] font-semibold text-slate-400 uppercase">
                                <tr>
                                    <th class="py-2.5 px-3">Loan ID</th>
                                    <th class="py-2.5 px-3 text-right">Principal</th>
                                    <th class="py-2.5 px-3 text-right">Repaid</th>
                                    <th class="py-2.5 px-3 text-right">Remaining</th>
                                    <th class="py-2.5 px-3 text-center">Status</th>
                                    <th class="py-2.5 px-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($customer->loans as $loan)
                                    @php
                                        $lPaid = (float) $loan->paid;
                                        $lRem  = (float) $loan->remaining;
                                        $lProg = $loan->amount > 0 ? min(100, round(($lPaid / $loan->amount) * 100)) : 0;
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                        <td class="py-2 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                            #LOAN-{{ str_pad($loan->id, 4, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                            {{ number_format($loan->amount, 2) }}
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono text-emerald-600 dark:text-emerald-400 font-medium">
                                            {{ number_format($lPaid, 2) }}
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono font-bold {{ $lRem > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500' }}">
                                            {{ number_format($lRem, 2) }}
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $loan->status === 'paid' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                                {{ ucfirst($loan->status) }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-3 text-right">
                                            <a href="{{ route('loans.show', $loan) }}" class="text-[11px] font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                                                View →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>

        {{-- Right Column (4 cols): Profile Details & Insights --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Contact Information --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Client Directory Card</span>
                    @can('customers.edit')
                        <a href="{{ route('customers.edit', $customer) }}" class="text-xs text-teal-600 dark:text-teal-400 hover:underline font-semibold">
                            Edit Info
                        </a>
                    @endcan
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-[11px] text-slate-400 block mb-0.5">Primary Phone</span>
                        @if($customer->phone)
                            <a href="tel:{{ $customer->phone }}" class="font-mono font-semibold text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ $customer->phone }}
                            </a>
                        @else
                            <span class="text-slate-400 italic">No phone on file</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-[11px] text-slate-400 block mb-0.5">Email Address</span>
                        @if($customer->email)
                            <a href="mailto:{{ $customer->email }}" class="font-medium text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400 flex items-center gap-1.5 truncate">
                                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $customer->email }}
                            </a>
                        @else
                            <span class="text-slate-400 italic">No email on file</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-[11px] text-slate-400 block mb-0.5">Physical / Delivery Address</span>
                        @if($customer->address)
                            <div class="text-slate-700 dark:text-slate-300 flex items-start gap-1.5 bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>{{ $customer->address }}</span>
                            </div>
                        @else
                            <span class="text-slate-400 italic">No address specified</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Purchasing Insights Card --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Purchasing Insights</span>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between items-center py-1">
                        <span class="text-slate-500">Average Order Value:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white">{{ number_format($avgOrderValue ?? 0, 2) }} RWF</span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">Last Transaction Date:</span>
                        <span class="font-mono text-slate-700 dark:text-slate-300">
                            {{ $lastSale ? ($lastSale->sale_date ? $lastSale->sale_date->format('d M Y') : $lastSale->created_at?->format('d M Y')) : 'Never' }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">Account Standing:</span>
                        <span class="font-semibold {{ ($outstandingCredit ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ ($outstandingCredit ?? 0) > 0 ? 'Receivable Pending' : 'Clear & Balanced' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Ledger Audit Verification Stamp --}}
            <div class="bg-slate-50 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 text-[11px] text-slate-500 dark:text-slate-400 space-y-1.5">
                <div class="flex items-center gap-1.5 font-semibold text-slate-700 dark:text-slate-300">
                    <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Customer Profile Audit
                </div>
                <div>Account Ref: <strong class="text-slate-700 dark:text-slate-300 font-mono">#CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</strong></div>
                <div>Registered: <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ $customer->created_at ? $customer->created_at->format('Y-m-d H:i') : '—' }}</strong></div>
                <div>Last Updated: <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ $customer->updated_at ? $customer->updated_at->format('Y-m-d H:i') : '—' }}</strong></div>
            </div>

        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- DEDICATED DELETE MODAL ON SHOW PAGE                         --}}
    {{-- ============================================================ --}}
    @can('customers.delete')
    <div x-show="showDeleteModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="showDeleteModal = false"
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
                        Delete Customer Account
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to permanently delete <strong class="text-slate-800 dark:text-slate-200">{{ $customer->name }}</strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Ledger Retention Safeguard
                </div>
                <p class="text-amber-700 dark:text-amber-400 pl-5">
                    All existing sales orders and credit records will be retained in your system but unlinked (converted to walk-in/unassigned) to ensure financial audits remain balanced.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showDeleteModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Keep Customer
                </button>
                <form action="{{ route('customers.destroy', $customer) }}" method="POST">
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
