@extends('layouts.app')
@section('title', 'Expenses Ledger')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $user = auth()->user();
    $canCreate  = $user?->can('expenses.create');
    $canEdit    = $user?->can('expenses.edit');
    $canDelete  = $user?->can('expenses.delete');
    $showActions = $canEdit || $canDelete;

    $currentMethod   = request('method', '');
    $currentCategory = request('category_id', '');
    $currentSupplier = request('supplier_id', '');
    $currentFrom     = request('from', '');
    $currentTo       = request('to', '');
    $currentQ        = request('q', '');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        filters: {
            q: '{{ addslashes($currentQ) }}',
            category_id: '{{ $currentCategory }}',
            supplier_id: '{{ $currentSupplier }}',
            method: '{{ $currentMethod }}',
            from: '{{ $currentFrom }}',
            to: '{{ $currentTo }}'
        },
        loading: false,
        timeout: null,

        // Modal states
        createModalOpen: false,
        createLoading: false,
        createError: '',
        createForm: {
            date: '{{ now()->toDateString() }}',
            amount: '',
            category_id: '',
            supplier_id: '',
            method: '{{ $channels->first()?->slug ?? 'cash' }}',
            reference: '',
            note: ''
        },

        editModalOpen: false,
        editLoading: false,
        editError: '',
        editForm: {
            id: null,
            date: '',
            amount: '',
            category_id: '',
            supplier_id: '',
            method: '',
            reference: '',
            note: '',
            action_url: ''
        },

        showModalOpen: false,
        showData: {
            id: null,
            date: '',
            amount: 0,
            category: '',
            supplier: '',
            method: '',
            reference: '',
            note: '',
            creator: '',
            created_at: '',
            show_url: ''
        },

        deleteModalOpen: false,
        deleteActionUrl: '',
        deleteExpenseName: '',
        deleteExpenseAmount: '',

        openCreateModal() {
            this.createForm = {
                date: '{{ now()->toDateString() }}',
                amount: '',
                category_id: '',
                supplier_id: '',
                method: '{{ $channels->first()?->slug ?? 'cash' }}',
                reference: '',
                note: ''
            };
            this.createError = '';
            this.createModalOpen = true;
        },

        openEditModal(data) {
            this.editForm = {
                id: data.id,
                date: data.date,
                amount: data.amount,
                category_id: data.category_id,
                supplier_id: data.supplier_id,
                method: data.method,
                reference: data.reference,
                note: data.note,
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
            this.deleteExpenseName = name;
            this.deleteExpenseAmount = amount;
            this.deleteModalOpen = true;
        },

        fetchResults() {
            this.loading = true;
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                const url = new URL('{{ route('expenses.index') }}', window.location.origin);
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
                    const container = document.getElementById('expenses-table-container');
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

        resetFilters() {
            this.filters = { q: '', category_id: '', supplier_id: '', method: '', from: '', to: '' };
            this.fetchResults();
        },

        submitCreate() {
            if (!this.createForm.amount || parseFloat(this.createForm.amount) <= 0) {
                this.createError = 'A valid expense amount is required.';
                return;
            }
            if (!this.createForm.category_id) {
                this.createError = 'Please select an expense category.';
                return;
            }
            this.createLoading = true;
            this.createError = '';

            fetch('{{ route('expenses.store') }}', {
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
                    this.createError = body.message || 'Failed to record expense. Please verify inputs.';
                }
            })
            .catch(() => {
                this.createLoading = false;
                this.createError = 'Network error while recording expense.';
            });
        },

        submitEdit() {
            if (!this.editForm.amount || parseFloat(this.editForm.amount) <= 0) {
                this.editError = 'A valid expense amount is required.';
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
                    this.editError = body.message || 'Failed to update expense.';
                }
            })
            .catch(() => {
                this.editLoading = false;
                this.editError = 'Network error while updating expense.';
            });
        }
     }">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span>Financial Ledger</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Expenses</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Expenses Ledger</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Monitor operational burn rate, overhead expenses, supplier disbursements, and capital outflows.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Financial Reports
            </a>

            @can('expenses.create')
                <button type="button"
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Record Expense
                </button>
            @endcan
        </div>
    </div>

    {{-- 4 Executive Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Lifetime Expenses Volume --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Outflow</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalExpenses, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Cumulative operating expenses
                </div>
            </div>
        </div>

        {{-- Monthly Burn Rate --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Current Month Outflow</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-amber-600 dark:text-amber-400">
                    {{ number_format($monthlyExpenses, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Disbursed since {{ now()->startOfMonth()->format('M 1') }}
                </div>
            </div>
        </div>

        {{-- Transactions Count --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Expense Vouchers</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalTransactions) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Audited ledger records
                </div>
            </div>
        </div>

        {{-- Top Expense Category --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Dominant Category</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-lg font-bold truncate text-slate-900 dark:text-white" title="{{ $topCategory?->name ?? 'None' }}">
                    {{ $topCategory ? $topCategory->name : 'None Recorded' }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-mono">
                    {{ $topCategory ? number_format((float) $topCategory->total_spend, 2) . ' RWF' : 'No expense logs' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Channels Settlement Strip --}}
    @if($channels->count() > 0)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
            <div class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">
                Disbursement Channel Breakdown
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                @foreach($channels as $channel)
                    @php
                        $slug = strtolower($channel->slug);
                        $item = $byMethod[$slug] ?? ['amount' => 0, 'count' => 0];
                    @endphp
                    <div class="p-3 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs">
                            <span class="font-medium truncate">{{ $channel->name }}</span>
                            <span class="font-mono text-[11px]">{{ $item['count'] }}</span>
                        </div>
                        <div class="mt-1 font-mono font-bold text-xs text-slate-900 dark:text-white">
                            {{ number_format((float) $item['amount'], 2) }} <span class="text-[10px] text-slate-400 font-sans">RWF</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Interactive Live Filter Toolbar --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm space-y-3">
        <form method="GET" action="{{ route('expenses.index') }}" @submit.prevent="fetchResults()" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            {{-- Category Filter --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                <select x-model="filters.category_id" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Supplier Filter --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payee / Supplier</label>
                <select x-model="filters.supplier_id" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                    @endforeach
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

            {{-- From Date --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">From Date</label>
                <input type="date" x-model="filters.from" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
            </div>

            {{-- To Date --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">To Date</label>
                <input type="date" x-model="filters.to" @change="fetchResults()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
            </div>

            {{-- Search Input --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Search Keywords</label>
                <div class="relative">
                    <input type="text"
                           x-model="filters.q"
                           @input="fetchResults()"
                           placeholder="Ref, note, category…"
                           class="w-full pl-8 text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    <span class="absolute inset-y-0 left-2.5 flex items-center text-slate-400 pointer-events-none">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                </div>
            </div>
        </form>

        {{-- Filter Status Strip --}}
        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
            <span class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full" :class="loading ? 'bg-amber-400 animate-ping' : 'bg-emerald-400'"></span>
                <span x-text="loading ? 'Filtering ledger records...' : 'Expenses ledger synchronized'"></span>
            </span>

            <button type="button" @click="resetFilters()" class="text-xs text-slate-600 hover:text-teal-600 dark:text-slate-400 dark:hover:text-teal-400 underline font-medium">
                Reset Filters
            </button>
        </div>
    </div>

    {{-- Expenses Ledger Table Container --}}
    <div id="expenses-table-container" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        @include('expenses._table', ['expenses' => $expenses])
    </div>

    {{-- ============================================================ --}}
    {{-- 1. RECORD EXPENSE MODAL                                      --}}
    {{-- ============================================================ --}}
    @can('expenses.create')
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Operating Expense</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Post an expense voucher to the cash disbursement ledger</p>
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
                            Disbursement Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="createForm.date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
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
                               placeholder="e.g. 25000"
                               class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Category <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="createForm.category_id" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Select Category…</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Payment Channel <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="createForm.method" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            @foreach($channels as $ch)
                                <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Vendor / Supplier (Optional)
                        </label>
                        <select x-model="createForm.supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="">None / General Payee</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Reference / Receipt ID
                        </label>
                        <input type="text"
                               x-model="createForm.reference"
                               placeholder="e.g. REC-9921, MoMo Txn ID"
                               class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Expense Description / Purpose
                    </label>
                    <textarea x-model="createForm.note"
                              rows="2"
                              placeholder="e.g. Office generator fuel replenishment, monthly internet package"
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
                        <span x-text="createLoading ? 'Recording...' : 'Save Expense'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- 2. EDIT EXPENSE MODAL                                        --}}
    {{-- ============================================================ --}}
    @can('expenses.edit')
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Expense Voucher</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Update expense category, amount, or voucher references</p>
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
                            Disbursement Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               x-model="editForm.date"
                               required
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
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
                            Category <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="editForm.category_id" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Payment Channel <span class="text-rose-500">*</span>
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
                            Vendor / Supplier
                        </label>
                        <select x-model="editForm.supplier_id" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                            <option value="">None / General Payee</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Reference / Receipt ID
                        </label>
                        <input type="text"
                               x-model="editForm.reference"
                               class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Expense Description / Purpose
                    </label>
                    <textarea x-model="editForm.note"
                              rows="2"
                              class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
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
    {{-- 3. QUICK DETAIL VIEW MODAL                                   --}}
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Expense Voucher #EXP-<span x-text="String(showData.id).padStart(5, '0')"></span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="'Recorded on ' + showData.date"></p>
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
                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Disbursed Amount</div>
                    <div class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-0.5">
                        <span x-text="Number(showData.amount).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300 ring-1 ring-teal-600/20" x-text="showData.method"></span>
                </div>
            </div>

            {{-- Voucher Metadata Grid --}}
            <div class="space-y-2.5 text-xs">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <span class="text-slate-500 dark:text-slate-400">Expense Category:</span>
                    <span class="font-bold text-slate-900 dark:text-white" x-text="showData.category"></span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <span class="text-slate-500 dark:text-slate-400">Recipient / Vendor:</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200" x-text="showData.supplier"></span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <span class="text-slate-500 dark:text-slate-400">Receipt / Txn Ref:</span>
                    <span class="font-mono font-medium text-slate-800 dark:text-slate-200" x-text="showData.reference"></span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <span class="text-slate-500 dark:text-slate-400">Authorized By:</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200" x-text="showData.creator"></span>
                </div>

                <div class="p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 space-y-1">
                    <span class="text-slate-500 dark:text-slate-400 block">Notes & Purpose:</span>
                    <p class="text-slate-800 dark:text-slate-200 leading-relaxed font-normal" x-text="showData.note"></p>
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
    {{-- 4. DELETE EXPENSE CONFIRMATION MODAL                         --}}
    {{-- ============================================================ --}}
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
                        Delete Expense Voucher
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to permanently delete <strong class="text-slate-800 dark:text-slate-200" x-text="deleteExpenseName"></strong> for <strong class="font-mono text-slate-800 dark:text-slate-200" x-text="deleteExpenseAmount"></strong>?
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
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
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
