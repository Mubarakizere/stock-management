@extends('layouts.app')
@section('title', 'Financial Transactions Ledger')

@section('content')
@php
    use Carbon\Carbon;
    use Illuminate\Pagination\LengthAwarePaginator;

    $user = auth()->user();
    $canCreate  = $user?->can('transactions.create');
    $canEdit    = $user?->can('transactions.edit');
    $canDelete  = $user?->can('transactions.delete');
    $showActions = $canEdit || $canDelete;

    $today  = Carbon::today()->toDateString();
    $last7  = Carbon::today()->subDays(6)->toDateString();
    $last30 = Carbon::today()->subDays(29)->toDateString();
    $monthS = Carbon::today()->startOfMonth()->toDateString();

    $currentType       = request('type', '');
    $currentMethod     = request('method', '');
    $currentCustomer   = request('customer_id', '');
    $currentSupplier   = request('supplier_id', '');
    $currentFrom       = request('date_from', '');
    $currentTo         = request('date_to', '');
    $currentQ          = request('q', '');
@endphp

@cannot('transactions.view')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="rounded-2xl border border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/40 p-6">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h1 class="text-base font-bold text-amber-900 dark:text-amber-100">
                        Access Restricted
                    </h1>
                    <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-200/80">
                        You do not have permission to view the financial transactions ledger. Please contact your administrator.
                    </p>
                </div>
            </div>
        </div>
    </div>
@else
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        filters: {
            q: '{{ addslashes($currentQ) }}',
            type: '{{ $currentType }}',
            method: '{{ $currentMethod }}',
            customer_id: '{{ $currentCustomer }}',
            supplier_id: '{{ $currentSupplier }}',
            date_from: '{{ $currentFrom }}',
            date_to: '{{ $currentTo }}'
        },
        loading: false,
        timeout: null,

        // Modal states
        createModalOpen: false,
        createLoading: false,
        createError: '',
        createForm: {
            type: 'credit',
            amount: '',
            transaction_date: '{{ now()->toDateString() }}',
            method: '{{ $channels->first()?->slug ?? 'cash' }}',
            customer_id: '',
            supplier_id: '',
            notes: ''
        },

        withdrawalModalOpen: false,
        withdrawalLoading: false,
        withdrawalError: '',
        withdrawalForm: {
            amount: '',
            transaction_date: '{{ now()->toDateString() }}',
            method: '{{ $channels->first()?->slug ?? 'cash' }}',
            notes: ''
        },

        transferModalOpen: false,
        transferLoading: false,
        transferError: '',
        transferForm: {
            amount: '',
            transaction_date: '{{ now()->toDateString() }}',
            from_method: '{{ $channels->first()?->slug ?? 'cash' }}',
            to_method: '{{ $channels->skip(1)->first()?->slug ?? ($channels->first()?->slug ?? '') }}',
            notes: ''
        },

        editModalOpen: false,
        editLoading: false,
        editError: '',
        editForm: {
            id: null,
            type: 'credit',
            amount: '',
            transaction_date: '',
            method: '',
            customer_id: '',
            supplier_id: '',
            notes: '',
            action_url: ''
        },

        showModalOpen: false,
        showData: {
            id: null,
            date: '',
            type: '',
            amount: 0,
            method: '',
            customer: '',
            supplier: '',
            user: '',
            notes: '',
            show_url: ''
        },

        deleteModalOpen: false,
        deleteActionUrl: '',
        deleteTxName: '',
        deleteTxAmount: '',

        openCreateModal() {
            this.createForm = {
                type: 'credit',
                amount: '',
                transaction_date: '{{ now()->toDateString() }}',
                method: '{{ $channels->first()?->slug ?? 'cash' }}',
                customer_id: '',
                supplier_id: '',
                notes: ''
            };
            this.createError = '';
            this.createModalOpen = true;
        },

        openWithdrawalModal() {
            this.withdrawalForm = {
                amount: '',
                transaction_date: '{{ now()->toDateString() }}',
                method: '{{ $channels->first()?->slug ?? 'cash' }}',
                notes: ''
            };
            this.withdrawalError = '';
            this.withdrawalModalOpen = true;
        },

        openTransferModal() {
            this.transferForm = {
                amount: '',
                transaction_date: '{{ now()->toDateString() }}',
                from_method: '{{ $channels->first()?->slug ?? 'cash' }}',
                to_method: '{{ $channels->skip(1)->first()?->slug ?? ($channels->first()?->slug ?? '') }}',
                notes: ''
            };
            this.transferError = '';
            this.transferModalOpen = true;
        },

        openEditModal(data) {
            this.editForm = {
                id: data.id,
                type: data.type,
                amount: data.amount,
                transaction_date: data.transaction_date,
                method: data.method,
                customer_id: data.customer_id,
                supplier_id: data.supplier_id,
                notes: data.notes,
                action_url: data.action_url
            };
            this.editError = '';
            this.editModalOpen = true;
        },

        openShowModal(data) {
            this.showData = data;
            this.showModalOpen = true;
        },

        openDeleteModal(url, name, amount) {
            this.deleteActionUrl = url;
            this.deleteTxName = name;
            this.deleteTxAmount = amount;
            this.deleteModalOpen = true;
        },

        fetchResults() {
            this.loading = true;
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                const url = new URL('{{ route('transactions.index') }}', window.location.origin);
                Object.keys(this.filters).forEach(k => {
                    if (this.filters[k]) {
                        url.searchParams.set(k, this.filters[k]);
                    }
                });

                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('transactions-table-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
                    }
                    this.loading = false;
                })
                .catch(() => {
                    this.loading = false;
                });
            }, 300);
        },

        setRange(from, to) {
            this.filters.date_from = from;
            this.filters.date_to = to;
            this.fetchResults();
        },

        resetFilters() {
            this.filters = { q: '', type: '', method: '', customer_id: '', supplier_id: '', date_from: '', date_to: '' };
            this.fetchResults();
        },

        submitCreate() {
            if (!this.createForm.amount || parseFloat(this.createForm.amount) <= 0) {
                this.createError = 'A valid positive amount is required.';
                return;
            }
            this.createLoading = true;
            this.createError = '';

            fetch('{{ route('transactions.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.createForm)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.createLoading = false;
                if (status === 200 || status === 201) {
                    this.createModalOpen = false;
                    window.location.reload();
                } else {
                    this.createError = body.message || 'Failed to record transaction.';
                }
            })
            .catch(() => {
                this.createLoading = false;
                this.createError = 'Network error while recording transaction.';
            });
        },

        submitWithdrawal() {
            if (!this.withdrawalForm.amount || parseFloat(this.withdrawalForm.amount) <= 0) {
                this.withdrawalError = 'A valid withdrawal amount is required.';
                return;
            }
            this.withdrawalLoading = true;
            this.withdrawalError = '';

            fetch('{{ route('transactions.withdrawal.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.withdrawalForm)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.withdrawalLoading = false;
                if (status === 200 || status === 201) {
                    this.withdrawalModalOpen = false;
                    window.location.reload();
                } else {
                    this.withdrawalError = body.message || 'Failed to process withdrawal.';
                }
            })
            .catch(() => {
                this.withdrawalLoading = false;
                this.withdrawalError = 'Network error while processing withdrawal.';
            });
        },

        submitTransfer() {
            if (!this.transferForm.amount || parseFloat(this.transferForm.amount) <= 0) {
                this.transferError = 'A valid transfer amount is required.';
                return;
            }
            if (this.transferForm.from_method === this.transferForm.to_method) {
                this.transferError = 'Source and destination channels must be different.';
                return;
            }
            this.transferLoading = true;
            this.transferError = '';

            fetch('{{ route('transactions.transfer.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.transferForm)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.transferLoading = false;
                if (status === 200 || status === 201) {
                    this.transferModalOpen = false;
                    window.location.reload();
                } else {
                    this.transferError = body.message || 'Failed to process transfer.';
                }
            })
            .catch(() => {
                this.transferLoading = false;
                this.transferError = 'Network error while processing transfer.';
            });
        },

        submitEdit() {
            if (!this.editForm.amount || parseFloat(this.editForm.amount) <= 0) {
                this.editError = 'A valid amount is required.';
                return;
            }
            this.editLoading = true;
            this.editError = '';

            fetch(this.editForm.action_url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.editForm)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.editLoading = false;
                if (status === 200) {
                    this.editModalOpen = false;
                    window.location.reload();
                } else {
                    this.editError = body.message || 'Failed to update transaction.';
                }
            })
            .catch(() => {
                this.editLoading = false;
                this.editError = 'Network error while updating transaction.';
            });
        }
     }">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span>Financial Ledger</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Cash Flow & Transactions</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Financial Transactions Ledger</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Double-entry cashbook, channel disbursements, boss withdrawals, and inter-account liquidity transfers.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('transactions.create')
                <button type="button"
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Transaction
                </button>

                <button type="button"
                        @click="openWithdrawalModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-rose-700 bg-rose-50 border border-rose-200/80 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/60 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Send to Boss
                </button>

                <button type="button"
                        @click="openTransferModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-blue-700 bg-blue-50 border border-blue-200/80 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900/60 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Internal Transfer
                </button>
            @endcan

            <a href="{{ route('transactions.export.csv', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                CSV
            </a>

            <a href="{{ route('transactions.export.pdf', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                PDF
            </a>
        </div>
    </div>

    {{-- 4 Executive Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Inflow (Credits) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Inflow (Credits)</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    +{{ number_format($totalCredits, 2) }} <span class="text-xs font-medium font-sans text-emerald-600/70">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Sales receipts & receivables
                </div>
            </div>
        </div>

        {{-- Total Outflow (Debits) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Outflow (Debits)</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-rose-600 dark:text-rose-400">
                    -{{ number_format($totalDebits, 2) }} <span class="text-xs font-medium font-sans text-rose-600/70">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Purchases, expenses & withdrawals
                </div>
            </div>
        </div>

        {{-- Net Cash Liquidity --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Net Cash Balance</span>
                <span class="w-8 h-8 rounded-xl {{ $netBalance >= 0 ? 'bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400' : 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight {{ $netBalance >= 0 ? 'text-slate-900 dark:text-white' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ number_format($netBalance, 2) }} <span class="text-xs font-medium font-sans text-slate-400">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ $netBalance >= 0 ? 'Positive operational reserve' : 'Deficit / Overdraft position' }}
                </div>
            </div>
        </div>

        {{-- Audited Records --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Audited Entries</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalCount) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Filtered cash movement vouchers
                </div>
            </div>
        </div>
    </div>

    {{-- Channel Balances Strip --}}
    @if(isset($channelBalances) && count($channelBalances) > 0)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
            <div class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">
                Current Channel Vault Balances (All Time)
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                @foreach($channelBalances as $name => $item)
                    @php $bal = $item['balance']; @endphp
                    <div class="p-3 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs">
                            <span class="font-medium truncate">{{ $name }}</span>
                            <span class="w-2 h-2 rounded-full {{ $bal >= 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        </div>
                        <div class="mt-1 font-mono font-bold text-xs {{ $bal < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                            {{ number_format((float) $bal, 2) }} <span class="text-[10px] text-slate-400 font-sans">RWF</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Interactive Live Filter Toolbar --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm space-y-3">
        <form method="GET" action="{{ route('transactions.index') }}" @submit.prevent="fetchResults()" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3 items-end">
            {{-- Type Filter --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Type</label>
                <select x-model="filters.type" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <option value="">All Types</option>
                    <option value="credit">Credit (Money In)</option>
                    <option value="debit">Debit (Money Out)</option>
                </select>
            </div>

            {{-- Channel Filter --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Channel</label>
                <select x-model="filters.method" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <option value="">All Channels</option>
                    @foreach($channels as $ch)
                        <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Customer Filter --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Customer</label>
                <select x-model="filters.customer_id" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <option value="">All Customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Supplier Filter --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Supplier</label>
                <select x-model="filters.supplier_id" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- From Date --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">From Date</label>
                <input type="date" x-model="filters.date_from" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
            </div>

            {{-- To Date --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">To Date</label>
                <input type="date" x-model="filters.date_to" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
            </div>

            {{-- Search Keywords --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Search Keywords</label>
                <div class="relative">
                    <input type="text"
                           x-model="filters.q"
                           @input="fetchResults()"
                           placeholder="Notes, user, client…"
                           class="w-full pl-8 text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <span class="absolute inset-y-0 left-2.5 flex items-center text-slate-400 pointer-events-none">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                </div>
            </div>
        </form>

        {{-- Quick Range Presets & Action Status --}}
        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-1.5">
                <span class="text-slate-400 mr-1">Quick Ranges:</span>
                <button type="button" @click="setRange('{{ $today }}', '{{ $today }}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 font-medium text-slate-700 dark:text-slate-300 transition">Today</button>
                <button type="button" @click="setRange('{{ $last7 }}', '{{ $today }}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 font-medium text-slate-700 dark:text-slate-300 transition">Last 7d</button>
                <button type="button" @click="setRange('{{ $last30 }}', '{{ $today }}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 font-medium text-slate-700 dark:text-slate-300 transition">Last 30d</button>
                <button type="button" @click="setRange('{{ $monthS }}', '{{ $today }}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 font-medium text-slate-700 dark:text-slate-300 transition">This Month</button>
            </div>

            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full" :class="loading ? 'bg-amber-400 animate-ping' : 'bg-emerald-400'"></span>
                    <span x-text="loading ? 'Recalculating ledger...' : 'Live audit stream'"></span>
                </span>
                <button type="button" @click="resetFilters()" class="text-slate-600 hover:text-teal-600 dark:text-slate-400 dark:hover:text-teal-400 underline font-medium">Reset</button>
            </div>
        </div>
    </div>

    {{-- Transactions Table Container --}}
    <div id="transactions-table-container" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        @include('transactions._table', ['transactions' => $transactions, 'hasRunning' => $hasRunning])
    </div>

    {{-- ============================================================ --}}
    {{-- 1. RECORD TRANSACTION MODAL                                  --}}
    {{-- ============================================================ --}}
    @can('transactions.create')
    <div x-show="createModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="createModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Cash Transaction</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Post a manual credit or debit to the financial ledger</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="createError">
                <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300" x-text="createError"></div>
            </template>

            <form @submit.prevent="submitCreate()" class="mt-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Flow Direction <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="createForm.type" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="credit">Credit (Money In +)</option>
                            <option value="debit">Debit (Money Out -)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Amount (RWF) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               x-model="createForm.amount"
                               required
                               placeholder="e.g. 50000"
                               class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Transaction Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="createForm.transaction_date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Channel / Vault <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="createForm.method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            @foreach($channels as $ch)
                                <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Counterparty selection --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div x-show="createForm.type === 'credit'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Client (For Credits)
                        </label>
                        <select x-model="createForm.customer_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="">None / Walk-in / Cash</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="createForm.type === 'debit'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Supplier (For Debits)
                        </label>
                        <select x-model="createForm.supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="">None / General Disbursed</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Narrative / Notes
                    </label>
                    <textarea x-model="createForm.notes"
                              rows="2"
                              placeholder="Explanation of cash voucher..."
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="createLoading"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition disabled:opacity-50">
                        <template x-if="createLoading">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="createLoading ? 'Posting...' : 'Save Transaction'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 2. SEND TO BOSS (WITHDRAWAL) MODAL                           --}}
    {{-- ============================================================ --}}
    <div x-show="withdrawalModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="withdrawalModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Send to Boss</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Owner profit taking or equity withdrawal</p>
                    </div>
                </div>
                <button type="button" @click="withdrawalModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="withdrawalError">
                <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300" x-text="withdrawalError"></div>
            </template>

            <form @submit.prevent="submitWithdrawal()" class="mt-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Withdrawal Amount (RWF) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number"
                           step="0.01"
                           min="0.01"
                           x-model="withdrawalForm.amount"
                           required
                           placeholder="e.g. 100000"
                           class="w-full text-sm font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-rose-500 focus:ring-rose-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Disbursement Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="withdrawalForm.transaction_date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-rose-500 focus:ring-rose-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Source Vault / Channel <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="withdrawalForm.method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-rose-500 focus:ring-rose-500">
                            @foreach($channels as $ch)
                                <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Purpose / Note
                    </label>
                    <textarea x-model="withdrawalForm.notes"
                              rows="2"
                              placeholder="e.g. Weekly net profit payout to owner"
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-rose-500 focus:ring-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="withdrawalModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="withdrawalLoading"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-rose-600 hover:bg-rose-500 shadow-sm transition disabled:opacity-50">
                        <template x-if="withdrawalLoading">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="withdrawalLoading ? 'Disbursing...' : 'Confirm Withdrawal'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 3. INTERNAL CHANNEL TRANSFER MODAL                           --}}
    {{-- ============================================================ --}}
    <div x-show="transferModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="transferModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Internal Transfer</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Shift funds between accounts or mobile wallets</p>
                    </div>
                </div>
                <button type="button" @click="transferModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="transferError">
                <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300" x-text="transferError"></div>
            </template>

            <form @submit.prevent="submitTransfer()" class="mt-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Transfer Amount (RWF) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number"
                           step="0.01"
                           min="0.01"
                           x-model="transferForm.amount"
                           required
                           placeholder="e.g. 75000"
                           class="w-full text-sm font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Transfer Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date"
                           x-model="transferForm.transaction_date"
                           required
                           class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            From (Source) <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="transferForm.from_method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            @foreach($channels as $ch)
                                <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            To (Target) <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="transferForm.to_method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            @foreach($channels as $ch)
                                <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Transfer Notes
                    </label>
                    <textarea x-model="transferForm.notes"
                              rows="2"
                              placeholder="e.g. Bank deposit from daily cash till"
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="transferModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="transferLoading"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-blue-600 hover:bg-blue-500 shadow-sm transition disabled:opacity-50">
                        <template x-if="transferLoading">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="transferLoading ? 'Transferring...' : 'Execute Transfer'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- 4. EDIT TRANSACTION MODAL                                    --}}
    {{-- ============================================================ --}}
    @can('transactions.edit')
    <div x-show="editModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="editModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Transaction</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Modify voucher metadata or amounts</p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="editError">
                <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300" x-text="editError"></div>
            </template>

            <form @submit.prevent="submitEdit()" class="mt-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Flow Direction <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="editForm.type" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="credit">Credit (Money In +)</option>
                            <option value="debit">Debit (Money Out -)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Amount (RWF) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               x-model="editForm.amount"
                               required
                               class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="editForm.transaction_date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Channel / Vault <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="editForm.method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            @foreach($channels as $ch)
                                <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Customer (Optional)
                        </label>
                        <select x-model="editForm.customer_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="">None / Walk-in</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Supplier (Optional)
                        </label>
                        <select x-model="editForm.supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="">None / General Disbursed</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Narrative / Notes
                    </label>
                    <textarea x-model="editForm.notes"
                              rows="2"
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="editLoading"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition disabled:opacity-50">
                        <template x-if="editLoading">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="editLoading ? 'Updating...' : 'Save Changes'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- 5. QUICK VIEW TRANSACTION MODAL                              --}}
    {{-- ============================================================ --}}
    <div x-show="showModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="showModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Transaction #TX-<span x-text="String(showData.id).padStart(5, '0')"></span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="'Posted on ' + showData.date"></p>
                    </div>
                </div>
                <button type="button" @click="showModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Amount Highlight Card --}}
            <div class="my-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Transaction Amount</div>
                    <div class="text-2xl font-bold font-mono mt-0.5" :class="showData.type === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                        <span x-text="(showData.type === 'credit' ? '+' : '-') + Number(showData.amount).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                          :class="showData.type === 'credit' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20'"
                          x-text="showData.type"></span>
                </div>
            </div>

            {{-- Metadata Grid --}}
            <div class="space-y-2.5 text-xs">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <span class="text-slate-500 dark:text-slate-400">Payment Channel:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-white" x-text="showData.method"></span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <span class="text-slate-500 dark:text-slate-400">Client / Counterparty:</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200" x-text="showData.customer !== '—' ? showData.customer : showData.supplier"></span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <span class="text-slate-500 dark:text-slate-400">Operator / User:</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200" x-text="showData.user"></span>
                </div>

                <div class="p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 space-y-1">
                    <span class="text-slate-500 dark:text-slate-400 block">Notes & Details:</span>
                    <p class="text-slate-800 dark:text-slate-200 leading-relaxed font-normal" x-text="showData.notes"></p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Close
                </button>
                <a :href="showData.show_url" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                    Full Voucher Details
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 6. DELETE TRANSACTION CONFIRMATION MODAL                     --}}
    {{-- ============================================================ --}}
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
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Delete Transaction
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to delete <strong class="text-slate-800 dark:text-slate-200" x-text="deleteTxName"></strong> for <strong class="font-mono text-slate-800 dark:text-slate-200" x-text="deleteTxAmount"></strong>?
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
                    Deleting this entry will adjust running balances and channel vault balances across the accounting system.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Keep Record
                </button>
                <form :action="deleteActionUrl" method="POST">
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
