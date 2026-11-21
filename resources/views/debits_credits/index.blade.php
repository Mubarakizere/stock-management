@extends('layouts.app')
@section('title', 'Debits & Credits Ledger')

@section('content')
@php
    use Carbon\Carbon;
    $today  = Carbon::today()->toDateString();
    $last7  = Carbon::today()->subDays(6)->toDateString();
    $last30 = Carbon::today()->subDays(29)->toDateString();
    $monthS = Carbon::today()->startOfMonth()->toDateString();

    $currentType       = request('type', '');
    $currentPartyType  = request('party_type', '');
    $currentCustomer   = request('customer_id', '');
    $currentSupplier   = request('supplier_id', '');
    $currentFrom       = request('from', '');
    $currentTo         = request('to', '');
    $currentQ          = request('q', '');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        filters: {
            q: '{{ addslashes($currentQ) }}',
            type: '{{ $currentType }}',
            party_type: '{{ $currentPartyType }}',
            customer_id: '{{ $currentCustomer }}',
            supplier_id: '{{ $currentSupplier }}',
            from: '{{ $currentFrom }}',
            to: '{{ $currentTo }}'
        },
        loading: false,
        timeout: null,

        // Modal States
        createModalOpen: false,
        createLoading: false,
        createError: '',
        createForm: {
            type: 'debit',
            amount: '',
            date: '{{ now()->toDateString() }}',
            party_type: 'none',
            customer_id: '',
            supplier_id: '',
            transaction_id: '',
            description: ''
        },

        editModalOpen: false,
        editLoading: false,
        editError: '',
        editForm: {
            id: null,
            type: 'debit',
            amount: '',
            date: '',
            party_type: 'none',
            customer_id: '',
            supplier_id: '',
            transaction_id: '',
            description: '',
            action_url: ''
        },

        showModalOpen: false,
        showData: {},

        deleteModalOpen: false,
        deleteActionUrl: '',
        deleteEntryName: '',
        deleteEntryAmount: '',

        openShowModal(data) {
            this.showData = data;
            this.showModalOpen = true;
        },

        openEditModal(data) {
            let pType = 'none';
            if (data.customer_id) pType = 'customer';
            else if (data.supplier_id) pType = 'supplier';

            this.editForm = {
                id: data.id,
                type: data.type,
                amount: data.amount,
                date: data.date,
                party_type: pType,
                customer_id: data.customer_id || '',
                supplier_id: data.supplier_id || '',
                transaction_id: data.transaction_id || '',
                description: data.description || '',
                action_url: data.action_url
            };
            this.editError = '';
            this.editModalOpen = true;
        },

        openDeleteModal(url, name, amount) {
            this.deleteActionUrl = url;
            this.deleteEntryName = name;
            this.deleteEntryAmount = amount;
            this.deleteModalOpen = true;
        },

        fetchResults() {
            this.loading = true;
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                const url = new URL('{{ route('debits-credits.index') }}', window.location.origin);
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
                    const container = document.getElementById('debits-credits-table-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
                    }
                    if (document.getElementById('stat-credits-val')) {
                        document.getElementById('stat-credits-val').innerText = 'RWF ' + Number(data.credits || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    if (document.getElementById('stat-debits-val')) {
                        document.getElementById('stat-debits-val').innerText = 'RWF ' + Number(data.debits || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    if (document.getElementById('stat-net-val')) {
                        document.getElementById('stat-net-val').innerText = 'RWF ' + Number(data.net || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    if (document.getElementById('stat-total-val')) {
                        document.getElementById('stat-total-val').innerText = data.total || 0;
                    }
                    this.loading = false;
                })
                .catch(() => {
                    this.loading = false;
                });
            }, 300);
        },

        setRange(from, to) {
            this.filters.from = from;
            this.filters.to = to;
            this.fetchResults();
        },

        resetFilters() {
            this.filters = { q: '', type: '', party_type: '', customer_id: '', supplier_id: '', from: '', to: '' };
            this.fetchResults();
        },

        submitCreate() {
            if (!this.createForm.amount || parseFloat(this.createForm.amount) <= 0) {
                this.createError = 'A valid positive amount is required.';
                return;
            }
            this.createLoading = true;
            this.createError = '';

            const payload = {
                type: this.createForm.type,
                amount: this.createForm.amount,
                date: this.createForm.date,
                description: this.createForm.description,
                transaction_id: this.createForm.transaction_id || null,
                customer_id: this.createForm.party_type === 'customer' ? this.createForm.customer_id : null,
                supplier_id: this.createForm.party_type === 'supplier' ? this.createForm.supplier_id : null
            };

            fetch('{{ route('debits-credits.store') }}', {
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
                        type: 'debit',
                        amount: '',
                        date: '{{ now()->toDateString() }}',
                        party_type: 'none',
                        customer_id: '',
                        supplier_id: '',
                        transaction_id: '',
                        description: ''
                    };
                    this.fetchResults();
                } else if (body.errors) {
                    this.createError = Object.values(body.errors).flat().join(' ');
                } else {
                    this.createError = body.message || 'Failed to record entry.';
                }
            })
            .catch(() => {
                this.createLoading = false;
                this.createError = 'Network error occurred while saving.';
            });
        },

        submitEdit() {
            if (!this.editForm.amount || parseFloat(this.editForm.amount) <= 0) {
                this.editError = 'A valid positive amount is required.';
                return;
            }
            this.editLoading = true;
            this.editError = '';

            const payload = {
                type: this.editForm.type,
                amount: this.editForm.amount,
                date: this.editForm.date,
                description: this.editForm.description,
                transaction_id: this.editForm.transaction_id || null,
                customer_id: this.editForm.party_type === 'customer' ? this.editForm.customer_id : null,
                supplier_id: this.editForm.party_type === 'supplier' ? this.editForm.supplier_id : null
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
                    this.editError = body.message || 'Failed to update entry.';
                }
            })
            .catch(() => {
                this.editLoading = false;
                this.editError = 'Network error occurred while updating.';
            });
        }
     }">

    {{-- Breadcrumb & Top Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-medium">Finance & Accounting</span>
                <span>/</span>
                <span class="text-teal-600 dark:text-teal-400 font-semibold">Debits & Credits</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1 flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </span>
                Debits & Credits Ledger
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Double-entry audit log of operational cash movements, counterparty adjustments, and vouchers.
            </p>
        </div>

        {{-- Header Actions --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Quick jump to Transactions --}}
            @can('transactions.view')
                <a href="{{ route('transactions.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-sm">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Transactions Ledger
                </a>
            @endcan

            {{-- New Entry Modal Button --}}
            @can('debits-credits.create')
                <button type="button"
                        @click="createModalOpen = true; createError = ''"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm shadow-teal-500/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Record Entry
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

    {{-- Executive 4-KPI Deck --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Inflows (Credits) --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Credits (Inflows)</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div id="stat-credits-val" class="text-xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    RWF {{ number_format($creditsTotal, 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                    {{ $creditsCount }} credits recorded
                </div>
            </div>
        </div>

        {{-- Total Outflows (Debits) --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Debits (Outflows)</span>
                <span class="p-2 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div id="stat-debits-val" class="text-xl font-bold font-mono tracking-tight text-rose-600 dark:text-rose-400">
                    RWF {{ number_format($debitsTotal, 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                    {{ $debitsCount }} debits recorded
                </div>
            </div>
        </div>

        {{-- Net Balance --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Net Operational Position</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div id="stat-net-val" class="text-xl font-bold font-mono tracking-tight {{ $net >= 0 ? 'text-slate-900 dark:text-white' : 'text-rose-600 dark:text-rose-400' }}">
                    RWF {{ number_format($net, 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                    Credits minus Debits
                </div>
            </div>
        </div>

        {{-- Total Audit Records --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Audited Entries</span>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div id="stat-total-val" class="text-xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ $totalCount }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                    Total journal records
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Multi-Filter Toolbar --}}
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Search & Direct Selectors --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:flex lg:flex-wrap items-center gap-3 flex-1">
                {{-- Search --}}
                <div class="relative min-w-[220px]">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           x-model="filters.q"
                           @input="fetchResults()"
                           placeholder="Search narrative, party, user..."
                           class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 transition">
                </div>

                {{-- Type Picker --}}
                <div>
                    <select x-model="filters.type"
                            @change="fetchResults()"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 py-2">
                        <option value="">All Flow Types</option>
                        <option value="credit">Credits (Money In)</option>
                        <option value="debit">Debits (Money Out)</option>
                    </select>
                </div>

                {{-- Party Type --}}
                <div>
                    <select x-model="filters.party_type"
                            @change="fetchResults()"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 py-2">
                        <option value="">All Counterparties</option>
                        <option value="customer">Customers</option>
                        <option value="supplier">Suppliers</option>
                        <option value="general">General / Internal</option>
                    </select>
                </div>

                {{-- Customer Filter --}}
                <div x-show="!filters.party_type || filters.party_type === 'customer'">
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
                <div x-show="!filters.party_type || filters.party_type === 'supplier'">
                    <select x-model="filters.supplier_id"
                            @change="fetchResults()"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-900/60 dark:text-white focus:border-teal-500 focus:ring-teal-500 py-2">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From & To --}}
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

        {{-- Quick Range Presets --}}
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mr-1">Presets:</span>
            <button type="button"
                    @click="setRange('{{ $today }}', '{{ $today }}')"
                    :class="filters.from === '{{ $today }}' && filters.to === '{{ $today }}' ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition">
                Today
            </button>
            <button type="button"
                    @click="setRange('{{ $last7 }}', '{{ $today }}')"
                    :class="filters.from === '{{ $last7 }}' && filters.to === '{{ $today }}' ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition">
                Last 7 Days
            </button>
            <button type="button"
                    @click="setRange('{{ $last30 }}', '{{ $today }}')"
                    :class="filters.from === '{{ $last30 }}' && filters.to === '{{ $today }}' ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition">
                Last 30 Days
            </button>
            <button type="button"
                    @click="setRange('{{ $monthS }}', '{{ $today }}')"
                    :class="filters.from === '{{ $monthS }}' && filters.to === '{{ $today }}' ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition">
                This Month
            </button>
            <button type="button"
                    @click="setRange('', '')"
                    :class="!filters.from && !filters.to ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition">
                All Time
            </button>
        </div>
    </div>

    {{-- Table Card with Live Spinner --}}
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
                <span>Updating records...</span>
            </div>
        </div>

        <div id="debits-credits-table-container">
            @include('debits_credits._table', ['records' => $records, 'pageCredits' => $pageCredits, 'pageDebits' => $pageDebits])
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 1. RECORD ENTRY MODAL                                        --}}
    {{-- ============================================================ --}}
    @can('debits-credits.create')
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Ledger Entry</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Post a direct credit or debit voucher</p>
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
                        Flow Direction <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label :class="createForm.type === 'debit' ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300 ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition">
                            <input type="radio" value="debit" x-model="createForm.type" class="sr-only">
                            <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/40 text-rose-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold">Debit (Money Out)</div>
                                <div class="text-[10px] text-slate-400">Outflow / Expense / Supplier</div>
                            </div>
                        </label>

                        <label :class="createForm.type === 'credit' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition">
                            <input type="radio" value="credit" x-model="createForm.type" class="sr-only">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold">Credit (Money In)</div>
                                <div class="text-[10px] text-slate-400">Inflow / Income / Customer</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Amount --}}
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
                               class="w-full text-xs font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    {{-- Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Voucher Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="createForm.date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                {{-- Counterparty Selection --}}
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Party Type & Entity
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="createForm.party_type = 'none'; createForm.customer_id = ''; createForm.supplier_id = ''"
                                :class="createForm.party_type === 'none' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition">
                            None (General)
                        </button>
                        <button type="button"
                                @click="createForm.party_type = 'customer'; createForm.supplier_id = ''"
                                :class="createForm.party_type === 'customer' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition">
                            Customer
                        </button>
                        <button type="button"
                                @click="createForm.party_type = 'supplier'; createForm.customer_id = ''"
                                :class="createForm.party_type === 'supplier' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition">
                            Supplier
                        </button>
                    </div>

                    {{-- Customer dropdown --}}
                    <div x-show="createForm.party_type === 'customer'">
                        <select x-model="createForm.customer_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500 mt-1">
                            <option value="">Select Customer...</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Supplier dropdown --}}
                    <div x-show="createForm.party_type === 'supplier'">
                        <select x-model="createForm.supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-purple-500 focus:ring-purple-500 mt-1">
                            <option value="">Select Supplier...</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Optional Linked Transaction --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Linked Transaction ID <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <input type="number"
                           x-model="createForm.transaction_id"
                           placeholder="e.g. 35"
                           class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                        Link this double-entry mirror to a corresponding Transaction ID.
                    </p>
                </div>

                {{-- Narrative / Description --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Narrative / Description
                    </label>
                    <textarea x-model="createForm.description"
                              rows="2"
                              placeholder="Reason, operational notes, or voucher description..."
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
                        <span x-text="createLoading ? 'Saving...' : 'Post Entry'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- 2. EDIT ENTRY MODAL                                          --}}
    {{-- ============================================================ --}}
    @can('debits-credits.edit')
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Ledger Entry</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Modify voucher parameters</p>
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
                {{-- Flow Direction Radio Cards --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Flow Direction <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label :class="editForm.type === 'debit' ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300 ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition">
                            <input type="radio" value="debit" x-model="editForm.type" class="sr-only">
                            <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/40 text-rose-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold">Debit (Money Out)</div>
                                <div class="text-[10px] text-slate-400">Outflow</div>
                            </div>
                        </label>

                        <label :class="editForm.type === 'credit' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                               class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition">
                            <input type="radio" value="credit" x-model="editForm.type" class="sr-only">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold">Credit (Money In)</div>
                                <div class="text-[10px] text-slate-400">Inflow</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Amount (RWF) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               x-model="editForm.amount"
                               required
                               class="w-full text-xs font-mono font-bold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    {{-- Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Voucher Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="editForm.date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                {{-- Counterparty Selection --}}
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Party Type & Entity
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="editForm.party_type = 'none'; editForm.customer_id = ''; editForm.supplier_id = ''"
                                :class="editForm.party_type === 'none' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition">
                            None (General)
                        </button>
                        <button type="button"
                                @click="editForm.party_type = 'customer'; editForm.supplier_id = ''"
                                :class="editForm.party_type === 'customer' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition">
                            Customer
                        </button>
                        <button type="button"
                                @click="editForm.party_type = 'supplier'; editForm.customer_id = ''"
                                :class="editForm.party_type === 'supplier' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition">
                            Supplier
                        </button>
                    </div>

                    {{-- Customer dropdown --}}
                    <div x-show="editForm.party_type === 'customer'">
                        <select x-model="editForm.customer_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-blue-500 focus:ring-blue-500 mt-1">
                            <option value="">Select Customer...</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Supplier dropdown --}}
                    <div x-show="editForm.party_type === 'supplier'">
                        <select x-model="editForm.supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-purple-500 focus:ring-purple-500 mt-1">
                            <option value="">Select Supplier...</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Optional Linked Transaction --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Linked Transaction ID <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <input type="number"
                           x-model="editForm.transaction_id"
                           placeholder="Transaction ID"
                           class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                </div>

                {{-- Narrative / Description --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Narrative / Description
                    </label>
                    <textarea x-model="editForm.description"
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
                        <span x-text="editLoading ? 'Saving...' : 'Update Entry'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- 3. QUICK VIEW MODAL                                          --}}
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
                            <h3 class="text-base font-bold text-slate-900 dark:text-white" x-text="showData.code || 'Voucher Detail'"></h3>
                            <span :class="showData.type === 'credit' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300'"
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider"
                                  x-text="showData.type === 'credit' ? 'Credit (IN)' : 'Debit (OUT)'"></span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="'Date: ' + (showData.date || '—')"></p>
                    </div>
                </div>
                <button type="button" @click="showModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Amount Highlight Card --}}
            <div class="mt-5 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Voucher Value</span>
                <div class="text-2xl font-bold font-mono tracking-tight mt-1"
                     :class="showData.type === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                    <span x-text="showData.type === 'credit' ? '+ RWF ' : '- RWF '"></span><span x-text="showData.amount"></span>
                </div>
            </div>

            {{-- Voucher Metadata Grid --}}
            <div class="mt-5 space-y-3 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Counterparty</span>
                    <span class="font-medium text-slate-900 dark:text-white"
                          x-text="showData.customer ? 'Customer: ' + showData.customer.name : (showData.supplier ? 'Supplier: ' + showData.supplier.name : 'General Ledger / Internal')"></span>
                </div>

                <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Linked Transaction</span>
                    <span class="font-mono text-slate-900 dark:text-white"
                          x-text="showData.transaction_id ? '#TX-' + showData.transaction_id : 'None'"></span>
                </div>

                <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Operator</span>
                    <span class="font-medium text-slate-900 dark:text-white"
                          x-text="showData.user ? showData.user.name : 'System'"></span>
                </div>

                <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Created At</span>
                    <span class="text-slate-700 dark:text-slate-300" x-text="showData.created_at || '—'"></span>
                </div>

                <div class="py-2">
                    <span class="text-slate-500 dark:text-slate-400 block mb-1">Narrative / Description</span>
                    <p class="text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800"
                       x-text="showData.description || 'No descriptive notes recorded.'"></p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 4. TWO-STEP DELETE CONFIRMATION MODAL                        --}}
    {{-- ============================================================ --}}
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
                        Are you sure you want to delete <strong class="text-slate-800 dark:text-slate-200" x-text="deleteEntryName"></strong> for <strong class="font-mono text-slate-800 dark:text-slate-200" x-text="deleteEntryAmount"></strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Double-Entry Ledger Notice
                </div>
                <p class="text-amber-700 dark:text-amber-400 pl-5">
                    Deleting this entry alters the audited totals and historical party balances. Note that linked Transactions will remain intact.
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
@endsection
