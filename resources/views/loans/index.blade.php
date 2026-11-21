@extends('layouts.app')
@section('title', 'Loans Overview & Portfolios')

@section('content')
@php
    use Carbon\Carbon;
    $today  = Carbon::today()->toDateString();
    $weekS  = Carbon::today()->startOfWeek()->toDateString();
    $monthS = Carbon::today()->startOfMonth()->toDateString();

    $currentType       = request('type', '');
    $currentStatus     = request('status', '');
    $currentCustomer   = request('customer_id', '');
    $currentSupplier   = request('supplier_id', '');
    $currentFrom       = request('from', '');
    $currentTo         = request('to', '');
    $currentQ          = request('q', '');
    $currentOverdue    = request()->boolean('overdue') ? '1' : '';
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        filters: {
            q: '{{ addslashes($currentQ) }}',
            type: '{{ $currentType }}',
            status: '{{ $currentStatus }}',
            customer_id: '{{ $currentCustomer }}',
            supplier_id: '{{ $currentSupplier }}',
            from: '{{ $currentFrom }}',
            to: '{{ $currentTo }}',
            overdue: '{{ $currentOverdue }}'
        },
        loading: false,
        timeout: null,

        // Modal States
        createModalOpen: false,
        createLoading: false,
        createError: '',
        createForm: {
            type: 'given',
            amount: '',
            loan_date: '{{ now()->toDateString() }}',
            due_date: '',
            status: 'pending',
            customer_id: '',
            supplier_id: '',
            notes: ''
        },

        editModalOpen: false,
        editLoading: false,
        editError: '',
        editForm: {
            id: null,
            type: 'given',
            amount: '',
            loan_date: '',
            due_date: '',
            status: 'pending',
            customer_id: '',
            supplier_id: '',
            notes: '',
            action_url: ''
        },

        paymentModalOpen: false,
        paymentLoading: false,
        paymentError: '',
        paymentForm: {
            loan_id: null,
            loan_code: '',
            party_name: '',
            remaining: 0,
            remaining_formatted: '0.00',
            amount: '',
            payment_date: '{{ now()->toDateString() }}',
            method: 'cash',
            notes: ''
        },

        showModalOpen: false,
        showData: {},

        deleteModalOpen: false,
        deleteActionUrl: '',
        deleteLoanName: '',
        deleteLoanAmount: '',

        openShowModal(data) {
            this.showData = data;
            this.showModalOpen = true;
        },

        openEditModal(data) {
            this.editForm = {
                id: data.id,
                type: data.type,
                amount: data.amount,
                loan_date: data.loan_date || '',
                due_date: data.due_date || '',
                status: data.status,
                customer_id: data.customer_id || '',
                supplier_id: data.supplier_id || '',
                notes: data.notes || '',
                action_url: data.action_url
            };
            this.editError = '';
            this.editModalOpen = true;
        },

        openPaymentModal(data) {
            this.paymentForm = {
                loan_id: data.loan_id,
                loan_code: data.code,
                party_name: data.party_name,
                remaining: data.remaining,
                remaining_formatted: data.remaining_formatted,
                amount: data.remaining,
                payment_date: '{{ now()->toDateString() }}',
                method: 'cash',
                notes: ''
            };
            this.paymentError = '';
            this.paymentModalOpen = true;
        },

        openDeleteModal(url, name, amount) {
            this.deleteActionUrl = url;
            this.deleteLoanName = name;
            this.deleteLoanAmount = amount;
            this.deleteModalOpen = true;
        },

        fetchResults() {
            this.loading = true;
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                const url = new URL('{{ route('loans.index') }}', window.location.origin);
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
                    const container = document.getElementById('loans-table-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
                    }
                    if (data.stats) {
                        const fmt = n => 'RWF ' + Number(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        if (document.getElementById('stat-pending-val')) document.getElementById('stat-pending-val').innerText = fmt(data.stats.pending_loans);
                        if (document.getElementById('stat-given-val')) document.getElementById('stat-given-val').innerText = fmt(data.stats.amount_given);
                        if (document.getElementById('stat-taken-val')) document.getElementById('stat-taken-val').innerText = fmt(data.stats.amount_taken);
                        if (document.getElementById('stat-paid-val')) document.getElementById('stat-paid-val').innerText = fmt(data.stats.paid_loans);
                        if (document.getElementById('stat-overdue-val')) document.getElementById('stat-overdue-val').innerText = data.stats.overdue_count + ' overdue';
                    }
                    this.loading = false;
                })
                .catch(() => {
                    this.loading = false;
                });
            }, 300);
        },

        setPeriod(period) {
            if (period === 'today') {
                this.filters.from = '{{ $today }}';
                this.filters.to = '{{ $today }}';
                this.filters.overdue = '';
            } else if (period === 'week') {
                this.filters.from = '{{ $weekS }}';
                this.filters.to = '{{ $today }}';
                this.filters.overdue = '';
            } else if (period === 'month') {
                this.filters.from = '{{ $monthS }}';
                this.filters.to = '{{ $today }}';
                this.filters.overdue = '';
            } else if (period === 'overdue') {
                this.filters.from = '';
                this.filters.to = '';
                this.filters.overdue = '1';
            } else {
                this.filters.from = '';
                this.filters.to = '';
                this.filters.overdue = '';
            }
            this.fetchResults();
        },

        resetFilters() {
            this.filters = { q: '', type: '', status: '', customer_id: '', supplier_id: '', from: '', to: '', overdue: '' };
            this.fetchResults();
        },

        submitCreate() {
            if (!this.createForm.amount || parseFloat(this.createForm.amount) <= 0) {
                this.createError = 'A valid positive loan amount is required.';
                return;
            }
            if (this.createForm.type === 'given' && !this.createForm.customer_id) {
                this.createError = 'Customer is required for a Given loan.';
                return;
            }
            if (this.createForm.type === 'taken' && !this.createForm.supplier_id) {
                this.createError = 'Supplier is required for a Taken loan.';
                return;
            }
            this.createLoading = true;
            this.createError = '';

            const payload = {
                type: this.createForm.type,
                amount: this.createForm.amount,
                loan_date: this.createForm.loan_date,
                due_date: this.createForm.due_date || null,
                status: this.createForm.status,
                customer_id: this.createForm.type === 'given' ? this.createForm.customer_id : null,
                supplier_id: this.createForm.type === 'taken' ? this.createForm.supplier_id : null,
                notes: this.createForm.notes
            };

            fetch('{{ route('loans.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.createLoading = false;
                if (status === 200 || status === 201 || body.success) {
                    this.createModalOpen = false;
                    this.createForm = {
                        type: 'given',
                        amount: '',
                        loan_date: '{{ now()->toDateString() }}',
                        due_date: '',
                        status: 'pending',
                        customer_id: '',
                        supplier_id: '',
                        notes: ''
                    };
                    this.fetchResults();
                } else if (body.errors) {
                    this.createError = Object.values(body.errors).flat().join(' ');
                } else {
                    this.createError = body.message || 'Failed to record loan.';
                }
            })
            .catch(() => {
                this.createLoading = false;
                this.createError = 'Network error occurred while saving loan.';
            });
        },

        submitEdit() {
            if (!this.editForm.amount || parseFloat(this.editForm.amount) <= 0) {
                this.editError = 'A valid positive amount is required.';
                return;
            }
            if (this.editForm.type === 'given' && !this.editForm.customer_id) {
                this.editError = 'Customer is required for a Given loan.';
                return;
            }
            if (this.editForm.type === 'taken' && !this.editForm.supplier_id) {
                this.editError = 'Supplier is required for a Taken loan.';
                return;
            }
            this.editLoading = true;
            this.editError = '';

            const payload = {
                type: this.editForm.type,
                amount: this.editForm.amount,
                loan_date: this.editForm.loan_date,
                due_date: this.editForm.due_date || null,
                status: this.editForm.status,
                customer_id: this.editForm.type === 'given' ? this.editForm.customer_id : null,
                supplier_id: this.editForm.type === 'taken' ? this.editForm.supplier_id : null,
                notes: this.editForm.notes
            };

            fetch(this.editForm.action_url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.editLoading = false;
                if (status === 200 || body.success) {
                    this.editModalOpen = false;
                    this.fetchResults();
                } else if (body.errors) {
                    this.editError = Object.values(body.errors).flat().join(' ');
                } else {
                    this.editError = body.message || 'Failed to update loan.';
                }
            })
            .catch(() => {
                this.editLoading = false;
                this.editError = 'Network error occurred while updating loan.';
            });
        },

        submitPayment() {
            if (!this.paymentForm.amount || parseFloat(this.paymentForm.amount) <= 0) {
                this.paymentError = 'A valid positive payment amount is required.';
                return;
            }
            this.paymentLoading = true;
            this.paymentError = '';

            const payload = {
                amount: this.paymentForm.amount,
                payment_date: this.paymentForm.payment_date,
                method: this.paymentForm.method,
                notes: this.paymentForm.notes
            };

            const storeUrl = '/loans/' + this.paymentForm.loan_id + '/payments';

            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.paymentLoading = false;
                if (status === 200 || status === 201 || body.success) {
                    this.paymentModalOpen = false;
                    this.fetchResults();
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-medium">Finance & Accounting</span>
                <span>/</span>
                <span class="text-teal-600 dark:text-teal-400 font-semibold">Loans Overview</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1 flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </span>
                Loans Portfolio & Overview
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Monitor debts, track receivables from customers, manage supplier credit, and audit settlement progress.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- PDF Export --}}
            @canany(['loans.export','loans.view'])
                <a href="{{ route('loans.export.pdf', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-sm">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    PDF Summary
                </a>
            @endcanany

            {{-- CSV Export --}}
            @canany(['loans.export','loans.view'])
                <a href="{{ route('loans.export.csv', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-sm">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    CSV Export
                </a>
            @endcanany

            {{-- New Loan Modal Button --}}
            @can('loans.create')
                <button type="button"
                        @click="createModalOpen = true; createError = ''"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm shadow-teal-500/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Loan
                </button>
            @endcan
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    {{-- Executive 5-KPI Deck --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Total Outstanding Debt --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Outstanding</span>
                <span class="p-2 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div id="stat-pending-val" class="text-lg font-bold font-mono tracking-tight text-rose-600 dark:text-rose-400">
                    RWF {{ number_format($stats['pending_loans'], 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                    {{ $stats['count_pending'] }} pending loans
                </div>
            </div>
        </div>

        {{-- Loans Given (Receivables) --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Given (Receivables)</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div id="stat-given-val" class="text-lg font-bold font-mono tracking-tight text-blue-600 dark:text-blue-400">
                    RWF {{ number_format($stats['amount_given'], 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                    {{ $stats['count_given'] }} customer loans
                </div>
            </div>
        </div>

        {{-- Loans Taken (Payables) --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Taken (Payables)</span>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div id="stat-taken-val" class="text-lg font-bold font-mono tracking-tight text-purple-600 dark:text-purple-400">
                    RWF {{ number_format($stats['amount_taken'], 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                    {{ $stats['count_taken'] }} supplier credits
                </div>
            </div>
        </div>

        {{-- Total Settled / Paid --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Settled</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div id="stat-paid-val" class="text-lg font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    RWF {{ number_format($stats['paid_loans'], 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                    {{ $stats['count_paid'] }} fully settled
                </div>
            </div>
        </div>

        {{-- Overdue Alert --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Overdue Alerts</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div id="stat-overdue-val" class="text-lg font-bold font-mono tracking-tight {{ $stats['overdue_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-200' }}">
                    {{ $stats['overdue_count'] }} overdue
                </div>
                <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                    RWF {{ number_format($stats['overdue_amount'], 2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Multi-Filter Toolbar --}}
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Direct Filter Selectors --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:flex lg:flex-wrap items-center gap-3 flex-1">
                {{-- Search --}}
                <div class="relative min-w-[200px]">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           x-model="filters.q"
                           @input="fetchResults()"
                           placeholder="Search party, notes, #id…"
                           class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 transition">
                </div>

                {{-- Loan Type --}}
                <div>
                    <select x-model="filters.type"
                            @change="fetchResults()"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 py-2">
                        <option value="">All Loan Types</option>
                        <option value="given">Given (Customer Debts)</option>
                        <option value="taken">Taken (Supplier Credits)</option>
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <select x-model="filters.status"
                            @change="fetchResults()"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 py-2">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid (Settled)</option>
                    </select>
                </div>

                {{-- Customer Filter --}}
                <div x-show="!filters.type || filters.type === 'given'">
                    <select x-model="filters.customer_id"
                            @change="fetchResults()"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 py-2">
                        <option value="">All Customers</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Supplier Filter --}}
                <div x-show="!filters.type || filters.type === 'taken'">
                    <select x-model="filters.supplier_id"
                            @change="fetchResults()"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 py-2">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Range --}}
                <div class="flex items-center gap-2">
                    <input type="date"
                           x-model="filters.from"
                           @change="fetchResults()"
                           class="text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 py-1.5 px-2">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="date"
                           x-model="filters.to"
                           @change="fetchResults()"
                           class="text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 py-1.5 px-2">
                </div>
            </div>

            {{-- Reset Button --}}
            <button type="button"
                    @click="resetFilters()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Reset
            </button>
        </div>

        {{-- Quick Presets --}}
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mr-1">Presets:</span>
            <button type="button"
                    @click="setPeriod('all')"
                    :class="!filters.from && !filters.to && !filters.overdue ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition">
                All Time
            </button>
            <button type="button"
                    @click="setPeriod('today')"
                    :class="filters.from === '{{ $today }}' && filters.to === '{{ $today }}' ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition">
                Today
            </button>
            <button type="button"
                    @click="setPeriod('week')"
                    :class="filters.from === '{{ $weekS }}' ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition">
                This Week
            </button>
            <button type="button"
                    @click="setPeriod('month')"
                    :class="filters.from === '{{ $monthS }}' ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition">
                This Month
            </button>
            <button type="button"
                    @click="setPeriod('overdue')"
                    :class="filters.overdue === '1' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 hover:bg-rose-100'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                Overdue Only
            </button>
        </div>
    </div>

    {{-- Loans Table Card --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
        {{-- Loading Overlay --}}
        <div x-show="loading"
             x-cloak
             class="absolute inset-0 z-20 bg-white/60 dark:bg-slate-900/60 backdrop-blur-xs flex items-center justify-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900/90 text-white text-xs font-semibold shadow-lg">
                <svg class="w-4 h-4 animate-spin text-teal-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>Updating loans...</span>
            </div>
        </div>

        <div id="loans-table-container">
            @include('loans._table', ['loans' => $loans])
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 1. RECORD NEW LOAN MODAL                                     --}}
    {{-- ============================================================ --}}
    @can('loans.create')
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Record New Loan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Add a given (receivable) or taken (payable) loan</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="createError">
                <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300" x-text="createError"></div>
            </template>

            <form @submit.prevent="submitCreate()" class="mt-5 space-y-4">
                {{-- Type Selection Cards --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Loan Direction <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label :class="createForm.type === 'given' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300 ring-2 ring-blue-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition">
                            <input type="radio" value="given" x-model="createForm.type" class="sr-only">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold">Given (Receivable)</div>
                                <div class="text-[10px] text-slate-400">Customer owes us</div>
                            </div>
                        </label>

                        <label :class="createForm.type === 'taken' ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-300 ring-2 ring-purple-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition">
                            <input type="radio" value="taken" x-model="createForm.type" class="sr-only">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold">Taken (Payable)</div>
                                <div class="text-[10px] text-slate-400">We owe supplier</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Counterparty Selection --}}
                <div>
                    <div x-show="createForm.type === 'given'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Customer <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="createForm.customer_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Customer...</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="createForm.type === 'taken'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Supplier <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="createForm.supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-purple-500 focus:ring-purple-500">
                            <option value="">Select Supplier...</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Principal Amount (RWF) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               x-model="createForm.amount"
                               required
                               placeholder="e.g. 500000"
                               class="w-full text-xs font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Initial Status <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="createForm.status" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid (Fully Settled)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Issue Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Loan Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="createForm.loan_date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Due Date <span class="text-slate-400 font-normal">(optional)</span>
                        </label>
                        <input type="date"
                               x-model="createForm.due_date"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Notes / Terms
                    </label>
                    <textarea x-model="createForm.notes"
                              rows="2"
                              placeholder="Contract details, repayment agreement, or collateral terms..."
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"></textarea>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
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
                        <span x-text="createLoading ? 'Saving...' : 'Create Loan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- 2. RECORD LOAN PAYMENT MODAL                                 --}}
    {{-- ============================================================ --}}
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
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="paymentForm.loan_code + ' · ' + paymentForm.party_name"></p>
                    </div>
                </div>
                <button type="button" @click="paymentModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Remaining Balance Notice --}}
            <div class="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <span class="text-xs text-slate-500 dark:text-slate-400">Remaining Balance:</span>
                <span class="font-mono font-bold text-xs text-rose-600 dark:text-rose-400" x-text="'RWF ' + paymentForm.remaining_formatted"></span>
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
                           :max="paymentForm.remaining"
                           x-model="paymentForm.amount"
                           required
                           class="w-full text-xs font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Payment Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Payment Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="paymentForm.payment_date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    {{-- Channel / Method --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Payment Channel <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="paymentForm.method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($paymentChannels as $ch)
                                <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Receipt Notes
                    </label>
                    <textarea x-model="paymentForm.notes"
                              rows="2"
                              placeholder="Cheque number, mobile money reference..."
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                </div>

                {{-- Actions --}}
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
                        <span x-text="paymentLoading ? 'Posting...' : 'Record Payment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- 3. EDIT LOAN MODAL                                           --}}
    {{-- ============================================================ --}}
    @can('loans.edit')
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Loan Record</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Update loan terms or dates</p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="editError">
                <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300" x-text="editError"></div>
            </template>

            <form @submit.prevent="submitEdit()" class="mt-5 space-y-4">
                {{-- Type Selection --}}
                <div class="grid grid-cols-2 gap-3">
                    <label :class="editForm.type === 'given' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300 ring-2 ring-blue-500/20' : 'border-slate-200 dark:border-slate-700'"
                           class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition">
                        <input type="radio" value="given" x-model="editForm.type" class="sr-only">
                        <div class="text-xs font-bold">Given (Customer Debt)</div>
                    </label>
                    <label :class="editForm.type === 'taken' ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-300 ring-2 ring-purple-500/20' : 'border-slate-200 dark:border-slate-700'"
                           class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition">
                        <input type="radio" value="taken" x-model="editForm.type" class="sr-only">
                        <div class="text-xs font-bold">Taken (Supplier Credit)</div>
                    </label>
                </div>

                {{-- Counterparty Selection --}}
                <div>
                    <div x-show="editForm.type === 'given'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Customer</label>
                        <select x-model="editForm.customer_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Customer...</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="editForm.type === 'taken'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Supplier</label>
                        <select x-model="editForm.supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-purple-500 focus:ring-purple-500">
                            <option value="">Select Supplier...</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Principal Amount (RWF) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               x-model="editForm.amount"
                               required
                               class="w-full text-xs font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Status <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="editForm.status" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid (Fully Settled)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Loan Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Loan Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="editForm.loan_date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Due Date
                        </label>
                        <input type="date"
                               x-model="editForm.due_date"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Notes / Narrative
                    </label>
                    <textarea x-model="editForm.notes"
                              rows="2"
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"></textarea>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="editLoading"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition disabled:opacity-50">
                        <template x-if="editLoading">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="editLoading ? 'Saving...' : 'Update Loan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- 4. QUICK VIEW MODAL                                          --}}
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
                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white" x-text="showData.code || 'Loan Detail'"></h3>
                            <span :class="showData.type === 'given' ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300' : 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300'"
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider"
                                  x-text="showData.type === 'given' ? 'Given (Receivable)' : 'Taken (Payable)'"></span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="showData.party ? showData.party.name : '—'"></p>
                    </div>
                </div>
                <button type="button" @click="showModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Financial Breakdown Hero --}}
            <div class="mt-5 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-3">
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Principal</span>
                        <div class="text-sm font-bold font-mono text-slate-900 dark:text-white mt-1" x-text="'RWF ' + (showData.amount || '0.00')"></div>
                    </div>
                    <div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Settled</span>
                        <div class="text-sm font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1" x-text="'RWF ' + (showData.paid || '0.00')"></div>
                    </div>
                    <div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Remaining</span>
                        <div class="text-sm font-bold font-mono text-rose-600 dark:text-rose-400 mt-1" x-text="'RWF ' + (showData.remaining || '0.00')"></div>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="pt-2">
                    <div class="flex justify-between text-[11px] mb-1">
                        <span class="text-slate-500">Repayment Progress</span>
                        <span class="font-bold text-slate-900 dark:text-white" x-text="(showData.progress || 0) + '%'"></span>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-300"
                             :class="showData.progress >= 100 ? 'bg-emerald-500' : 'bg-teal-500'"
                             :style="'width: ' + (showData.progress || 0) + '%'"></div>
                    </div>
                </div>
            </div>

            {{-- Metadata Grid --}}
            <div class="mt-5 space-y-3 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Issue Date</span>
                    <span class="font-medium text-slate-900 dark:text-white" x-text="showData.loan_date || '—'"></span>
                </div>

                <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Due Date</span>
                    <span class="font-medium text-slate-900 dark:text-white" x-text="showData.due_date || 'No due date'"></span>
                </div>

                <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Status</span>
                    <span :class="showData.status === 'paid' ? 'text-emerald-600 font-bold' : 'text-amber-600 font-bold'"
                          x-text="showData.status === 'paid' ? 'Paid in Full' : 'Pending Settlement'"></span>
                </div>

                <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Payment Vouchers</span>
                    <span class="font-medium text-slate-900 dark:text-white" x-text="(showData.payments_count || 0) + ' payments recorded'"></span>
                </div>

                <div class="py-2" x-show="showData.notes">
                    <span class="text-slate-500 dark:text-slate-400 block mb-1">Contract / Notes</span>
                    <p class="text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800"
                       x-text="showData.notes"></p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                <div>
                    <a :href="'/loans/' + showData.id" class="text-xs font-semibold text-teal-600 hover:text-teal-700 dark:text-teal-400 hover:underline">
                        Open Full Dossier →
                    </a>
                </div>
                <button type="button" @click="showModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 5. TWO-STEP DELETE CONFIRMATION MODAL                        --}}
    {{-- ============================================================ --}}
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
                        Are you sure you want to delete <strong class="text-slate-800 dark:text-slate-200" x-text="deleteLoanName"></strong> for <strong class="font-mono text-slate-800 dark:text-slate-200" x-text="deleteLoanAmount"></strong>?
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
                    Deleting this loan will automatically delete all associated repayment receipts and ledger records.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Keep Loan
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
@endsection
