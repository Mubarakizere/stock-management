@extends('layouts.app')
@section('title', 'Supplier: ' . $supplier->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumbs & Navigation Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('suppliers.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Suppliers</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $supplier->name }}</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-sm ring-1 ring-teal-200 dark:ring-teal-800">
                    {{ strtoupper(substr($supplier->name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ $supplier->name }}
                    </h1>
                    <div class="flex items-center gap-3 text-xs text-slate-400 mt-0.5">
                        <span class="font-mono">#SUP-{{ str_pad($supplier->id, 4, '0', STR_PAD_LEFT) }}</span>
                        @if($supplier->created_at)
                            <span>• Partner since {{ $supplier->created_at->format('M d, Y') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplier->id]) }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Vendor Statement
            </a>

            @can('purchases.create')
                <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Purchase Order
                </a>
            @endcan

            @can('suppliers.edit')
                <a href="{{ route('suppliers.edit', $supplier) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            @endcan

            <a href="{{ route('suppliers.index') }}" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition" title="Back to Suppliers">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- 4 Executive Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Lifetime Procurement Spend --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Lifetime Spend</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalSpend, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Total purchase invoices
                </div>
            </div>
        </div>

        {{-- Settled Outflow --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Paid to Date</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    {{ number_format($totalPaid, 2) }} <span class="text-xs font-medium text-emerald-600/70 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ $totalSpend > 0 ? round(($totalPaid / $totalSpend) * 100) : 0 }}% settled settlement rate
                </div>
            </div>
        </div>

        {{-- Outstanding Payables / Debt --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Outstanding Payables</span>
                <span class="w-8 h-8 rounded-xl {{ $outstandingPayables > 0 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' : 'bg-slate-50 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight {{ $outstandingPayables > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($outstandingPayables, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Unsettled PO balances & loans
                </div>
            </div>
        </div>

        {{-- Purchase Orders Count & Average Value --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Orders Fulfilled</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalOrders) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-mono">
                    Avg: {{ number_format($avgOrderValue, 0) }} RWF / order
                </div>
            </div>
        </div>
    </div>

    {{-- Main Profile Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column: Contact & Commercial Overview --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Contact Credentials Card --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Vendor Contact Directory
                </h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Telephone / Mobile</span>
                        @if($supplier->phone)
                            <a href="tel:{{ $supplier->phone }}" class="inline-flex items-center gap-2 font-mono font-medium text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400 transition">
                                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span>{{ $supplier->phone }}</span>
                            </a>
                        @else
                            <span class="text-slate-400 italic">No phone on record</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-0.5">Corporate Email</span>
                        @if($supplier->email)
                            <a href="mailto:{{ $supplier->email }}" class="inline-flex items-center gap-2 font-medium text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400 transition break-all">
                                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $supplier->email }}</span>
                            </a>
                        @else
                            <span class="text-slate-400 italic">No email on record</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-0.5">Physical Facility / Address</span>
                        @if($supplier->address)
                            <div class="flex items-start gap-2 text-slate-700 dark:text-slate-300">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="leading-relaxed">{{ $supplier->address }}</span>
                            </div>
                        @else
                            <span class="text-slate-400 italic">No physical location specified</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Activity Summary Card --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Procurement Activity
                </h3>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400">Last Purchase</span>
                        <span class="font-medium text-slate-900 dark:text-white">
                            {{ $lastPurchase ? $lastPurchase->purchase_date->format('M d, Y') : 'Never' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400">Total Invoiced</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white">
                            {{ number_format($totalSpend, 2) }} RWF
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400">Disbursed Payments</span>
                        <span class="font-mono font-medium text-emerald-600 dark:text-emerald-400">
                            {{ number_format($totalPaid, 2) }} RWF
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-slate-500 dark:text-slate-400">Current Balance Due</span>
                        <span class="font-mono font-bold {{ $outstandingPayables > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ number_format($outstandingPayables, 2) }} RWF
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Recent Purchases & Credit Records --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Recent Purchase Orders --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Recent Purchase Orders</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Historical goods and materials inward logs</p>
                    </div>

                    @can('purchases.create')
                        <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                            <span>Create PO</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </a>
                    @endcan
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                                <th class="py-3 px-4">PO Reference</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Total Amount</th>
                                <th class="py-3 px-4 text-right">Balance Due</th>
                                <th class="py-3 px-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse($supplier->purchases as $purchase)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                        <a href="{{ route('purchases.show', $purchase) }}" class="hover:text-teal-600 dark:hover:text-teal-400">
                                            #{{ $purchase->invoice_number ?: 'PO-' . str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400">
                                        {{ $purchase->purchase_date ? $purchase->purchase_date->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $st = $purchase->status ?? 'pending';
                                            $badgeClasses = match($st) {
                                                'paid'      => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300',
                                                'partial'   => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300',
                                                'completed' => 'bg-teal-50 text-teal-700 ring-teal-600/20 dark:bg-teal-950/40 dark:text-teal-300',
                                                'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300',
                                                default     => 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-400',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ring-1 {{ $badgeClasses }}">
                                            {{ ucfirst($st) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-medium text-slate-900 dark:text-white">
                                        {{ number_format($purchase->total_amount, 2) }} RWF
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono {{ (float) $purchase->balance_due > 0 ? 'font-bold text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                        {{ number_format($purchase->balance_due, 2) }} RWF
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <a href="{{ route('purchases.show', $purchase) }}" class="p-1 rounded text-slate-400 hover:text-teal-600 dark:hover:text-teal-400 transition" title="Inspect PO">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">
                                        <p class="text-xs">No purchase orders on record for this supplier.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Credit Facilities / Loans Ledger (if any) --}}
            @if($supplier->loans && $supplier->loans->count() > 0)
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Credit & Loans Facility</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Supplier loans and taken trade credit ledger</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                                    <th class="py-3 px-4">Loan ID</th>
                                    <th class="py-3 px-4">Type</th>
                                    <th class="py-3 px-4 text-right">Principal</th>
                                    <th class="py-3 px-4 text-right">Paid</th>
                                    <th class="py-3 px-4 text-right">Remaining</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                @foreach($supplier->loans as $loan)
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                        <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                            #LN-{{ str_pad($loan->id, 4, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $loan->type === 'taken' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300' }}">
                                                {{ ucfirst($loan->type) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono font-medium text-slate-900 dark:text-white">
                                            {{ number_format($loan->amount, 2) }} RWF
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                            {{ number_format($loan->paid, 2) }} RWF
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono font-bold {{ $loan->remaining > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                            {{ number_format($loan->remaining, 2) }} RWF
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $loan->status === 'paid' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                                {{ ucfirst($loan->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection

