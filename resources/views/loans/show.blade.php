@extends('layouts.app')
@section('title', "Loan #LN-" . str_pad($loan->id, 5, '0', STR_PAD_LEFT))

@section('content')
@php
    $loanCode   = '#LN-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT);
    $amount     = (float) ($loan->amount ?? 0);
    $paid       = (float) ($totalPaid ?? $loan->payments->sum('amount'));
    $remaining  = max(round($amount - $paid, 2), 0);
    $progress   = $amount > 0 ? min(100, (int) round(($paid / $amount) * 100)) : 0;

    $isGiven    = $loan->type === 'given';
    $partyName  = $isGiven ? optional($loan->customer)->name : optional($loan->supplier)->name;
    $dueDate    = $loan->due_date ? \Carbon\Carbon::parse($loan->due_date) : null;
    $today      = \Carbon\Carbon::today();
    $dueDiff    = $dueDate ? $today->diffInDays($dueDate, false) : null;
    $isOverdue  = $loan->status !== 'paid' && $dueDate && $dueDate->isPast() && $remaining > 0.01;
    $isDueSoon  = $loan->status !== 'paid' && $dueDate && !$isOverdue && $dueDiff !== null && $dueDiff <= 7 && $dueDiff >= 0;

    $paymentChannels = \App\Models\PaymentChannel::where('is_active', true)->orderBy('name')->get();
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        deleteModalOpen: false,
        paymentModalOpen: false,
        paymentLoading: false,
        paymentError: '',
        paymentForm: {
            amount: '{{ $remaining }}',
            payment_date: '{{ now()->toDateString() }}',
            method: 'cash',
            notes: ''
        },

        submitPayment() {
            if (!this.paymentForm.amount || parseFloat(this.paymentForm.amount) <= 0) {
                this.paymentError = 'A valid positive payment amount is required.';
                return;
            }
            this.paymentLoading = true;
            this.paymentError = '';

            fetch('{{ route('loan-payments.store', $loan) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(this.paymentForm)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.paymentLoading = false;
                if (status === 200 || status === 201 || body.success) {
                    window.location.reload();
                } else if (body.errors) {
                    this.paymentError = Object.values(body.errors).flat().join(' ');
                } else {
                    this.paymentError = body.message || 'Failed to record payment.';
                }
            })
            .catch(() => {
                this.paymentLoading = false;
                this.paymentError = 'Network error occurred while recording payment.';
            });
        }
     }">

    {{-- Breadcrumb & Title --}}
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Dashboard</a>
            <span>/</span>
            <a href="{{ route('loans.index') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Loans Portfolio</a>
            <span>/</span>
            <span class="text-teal-600 dark:text-teal-400 font-semibold">{{ $loanCode }}</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-2">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                    <span class="p-2 rounded-xl {{ $isGiven ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20' : 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </span>
                    Loan Dossier {{ $loanCode }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    {{ $isGiven ? 'Given to customer ' . ($partyName ?: 'N/A') : 'Taken from supplier ' . ($partyName ?: 'N/A') }}
                    · Issued {{ optional($loan->loan_date)->format('M d, Y') }}
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('loans.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-sm">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Loans
                </a>

                @if($loan->status !== 'paid')
                    @can('loans.view')
                        <button type="button"
                                @click="paymentModalOpen = true; paymentError = ''"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Record Payment
                        </button>
                    @endcan
                @endif

                @can('loans.edit')
                    <a href="{{ route('loans.edit', $loan) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-sm">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                @endcan

                @can('loans.delete')
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

    {{-- Overdue Alert Strip --}}
    @if ($isOverdue)
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-800 dark:text-rose-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>
                    This loan is <strong>overdue by {{ abs($dueDiff) }} days</strong> (Matured on {{ $dueDate->format('M d, Y') }}). Action required.
                </span>
            </div>
            @can('loans.view')
                <button type="button" @click="paymentModalOpen = true" class="px-3 py-1 rounded-lg text-xs font-bold bg-rose-600 text-white hover:bg-rose-500 transition">
                    Settle Now
                </button>
            @endcan
        </div>
    @endif

    {{-- Hero Summary Cards Deck --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Principal --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Principal Amount</span>
            <div class="mt-2 text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                RWF {{ number_format($amount, 2) }}
            </div>
            <div class="mt-1 text-[11px] text-slate-400">Total contracted loan</div>
        </div>

        {{-- Settled --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Settled</span>
            <div class="mt-2 text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                RWF {{ number_format($paid, 2) }}
            </div>
            <div class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">
                {{ $progress }}% of principal recovered
            </div>
        </div>

        {{-- Remaining --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Outstanding Balance</span>
            <div class="mt-2 text-2xl font-bold font-mono tracking-tight {{ $remaining > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                RWF {{ number_format($remaining, 2) }}
            </div>
            <div class="mt-1 text-[11px] text-slate-400">
                {{ $remaining > 0 ? 'Due for settlement' : 'Fully closed' }}
            </div>
        </div>

        {{-- Due Status --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Maturity / Due Date</span>
            <div class="mt-2 text-lg font-bold text-slate-900 dark:text-white">
                {{ $dueDate ? $dueDate->format('M d, Y') : 'Open Ended' }}
            </div>
            <div class="mt-1 text-[11px]">
                @if($loan->status === 'paid')
                    <span class="text-emerald-600 font-bold">Paid & Settled</span>
                @elseif($isOverdue)
                    <span class="text-rose-600 font-bold">Overdue by {{ abs($dueDiff) }}d</span>
                @elseif($isDueSoon)
                    <span class="text-amber-600 font-bold">Due in {{ $dueDiff }}d</span>
                @else
                    <span class="text-slate-400">{{ $dueDate ? $dueDate->diffForHumans() : 'No date fixed' }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Progress & Details Card --}}
    <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
        <div>
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="font-bold text-slate-700 dark:text-slate-300">Repayment Progress</span>
                <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $progress }}%</span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden">
                <div class="h-3 rounded-full transition-all duration-500 {{ $progress >= 100 ? 'bg-emerald-500' : 'bg-teal-500' }}" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pt-4 border-t border-slate-100 dark:border-slate-800 text-xs">
            <div>
                <span class="text-slate-400 block mb-1">Direction</span>
                <span class="font-bold text-slate-900 dark:text-white">
                    {{ $isGiven ? 'Given (Customer Receivable)' : 'Taken (Supplier Payable)' }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 block mb-1">Counterparty</span>
                @if($isGiven && $loan->customer)
                    <a href="{{ route('customers.show', $loan->customer->id) }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                        {{ $loan->customer->name }}
                    </a>
                @elseif(!$isGiven && $loan->supplier)
                    <a href="{{ route('suppliers.show', $loan->supplier->id) }}" class="font-bold text-purple-600 dark:text-purple-400 hover:underline">
                        {{ $loan->supplier->name }}
                    </a>
                @else
                    <span class="font-bold text-slate-500">—</span>
                @endif
            </div>
            <div>
                <span class="text-slate-400 block mb-1">Status</span>
                <span class="font-bold {{ $loan->status === 'paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ ucfirst($loan->status) }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 block mb-1">Issue Date</span>
                <span class="font-medium text-slate-900 dark:text-white">
                    {{ optional($loan->loan_date)->format('Y-m-d') }}
                </span>
            </div>
        </div>

        @if($loan->notes)
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-xs">
                <span class="text-slate-400 block mb-1">Notes & Contract Terms</span>
                <p class="text-slate-700 dark:text-slate-300 leading-relaxed font-sans bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                    {{ $loan->notes }}
                </p>
            </div>
        @endif
    </div>

    {{-- Repayment Vouchers History Table --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Repayment Receipts & Vouchers</h3>
                <p class="text-xs text-slate-400">All registered installment payments against this loan</p>
            </div>
            @if($loan->status !== 'paid')
                @can('loans.view')
                    <button type="button"
                            @click="paymentModalOpen = true; paymentError = ''"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Payment
                    </button>
                @endcan
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th scope="col" class="py-3 pl-4 sm:pl-6 pr-3">Payment ID</th>
                        <th scope="col" class="px-3 py-3">Date</th>
                        <th scope="col" class="px-3 py-3 text-right">Amount (RWF)</th>
                        <th scope="col" class="px-3 py-3">Channel</th>
                        <th scope="col" class="px-3 py-3">Recorded By</th>
                        <th scope="col" class="px-3 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs text-slate-700 dark:text-slate-300">
                    @forelse($loan->payments as $p)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3 pl-4 sm:pl-6 pr-3 font-mono font-bold text-slate-900 dark:text-white">
                                #PAY-{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($p->payment_date)->format('M d, Y') }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                + {{ number_format($p->amount, 2) }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ ucfirst($p->method ?? 'Cash') }}
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ $p->user->name ?? 'System' }}
                            </td>
                            <td class="px-3 py-3 max-w-[200px] truncate">
                                {{ $p->notes ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                                No repayment receipts recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Record Payment Modal --}}
    @can('loans.view')
    <div x-show="paymentModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="paymentModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Loan Payment</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $loanCode }} · Remaining: RWF {{ number_format($remaining, 2) }}</p>
                    </div>
                </div>
                <button type="button" @click="paymentModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="paymentError">
                <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300" x-text="paymentError"></div>
            </template>

            <form @submit.prevent="submitPayment()" class="mt-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Payment Amount (RWF) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number"
                           step="0.01"
                           min="0.01"
                           max="{{ $remaining }}"
                           x-model="paymentForm.amount"
                           required
                           class="w-full text-xs font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Payment Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="paymentForm.payment_date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Channel <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="paymentForm.method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($paymentChannels as $ch)
                                <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Notes / Reference
                    </label>
                    <textarea x-model="paymentForm.notes"
                              rows="2"
                              placeholder="Receipt or transaction reference..."
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="paymentModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="paymentLoading"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm transition disabled:opacity-50">
                        <template x-if="paymentLoading">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="paymentLoading ? 'Saving...' : 'Confirm Payment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- Two-Step Delete Modal --}}
    @can('loans.delete')
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
                        Delete Loan Record
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to delete <strong class="text-slate-800 dark:text-slate-200">{{ $loanCode }}</strong> for <strong class="font-mono text-slate-800 dark:text-slate-200">RWF {{ number_format($amount, 2) }}</strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Audit Warning
                </div>
                <p class="text-amber-700 dark:text-amber-400 pl-5">
                    Deleting this loan will also permanently remove its {{ $loan->payments->count() }} payment receipts and adjust balances.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Keep Loan
                </button>
                <form action="{{ route('loans.destroy', $loan) }}" method="POST">
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
