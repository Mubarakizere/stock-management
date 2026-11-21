@extends('layouts.app')
@section('title', 'Suppliers Directory')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $user = auth()->user();
    $canCreate  = $user?->can('suppliers.create');
    $canEdit    = $user?->can('suppliers.edit');
    $canDelete  = $user?->can('suppliers.delete');
    $showActions = $canEdit || $canDelete;

    $currentQuery = $query ?? request('q');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        searchQuery: '{{ addslashes($currentQuery) }}',
        loading: false,
        timeout: null,

        // Modals state
        createModalOpen: false,
        createLoading: false,
        createError: '',
        createForm: {
            name: '',
            phone: '',
            email: '',
            address: ''
        },

        editModalOpen: false,
        editLoading: false,
        editError: '',
        editForm: {
            id: null,
            name: '',
            phone: '',
            email: '',
            address: '',
            action_url: ''
        },

        showModalOpen: false,
        showData: {
            id: null,
            name: '',
            phone: '',
            email: '',
            address: '',
            orders_count: 0,
            total_spend: 0,
            balance_due: 0,
            show_url: '',
            statement_url: ''
        },

        deleteModalOpen: false,
        deleteActionUrl: '',
        deleteSupplierName: '',

        openCreateModal() {
            this.createForm = { name: '', phone: '', email: '', address: '' };
            this.createError = '';
            this.createModalOpen = true;
        },

        openEditModal(data) {
            this.editForm = {
                id: data.id,
                name: data.name,
                phone: data.phone,
                email: data.email,
                address: data.address,
                action_url: data.action_url
            };
            this.editError = '';
            this.editModalOpen = true;
        },

        openShowModal(data) {
            this.showData = data;
            this.showModalOpen = true;
        },

        openDeleteModal(url, name) {
            this.deleteActionUrl = url;
            this.deleteSupplierName = name;
            this.deleteModalOpen = true;
        },

        fetchResults() {
            this.loading = true;
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                const url = new URL('{{ route('suppliers.index') }}', window.location.origin);
                if (this.searchQuery) {
                    url.searchParams.set('q', this.searchQuery);
                }

                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('suppliers-table-container');
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

        submitCreate() {
            if (!this.createForm.name.trim()) {
                this.createError = 'Supplier name is required.';
                return;
            }
            this.createLoading = true;
            this.createError = '';

            fetch('{{ route('suppliers.store') }}', {
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
                    this.createError = body.message || 'Failed to create supplier. Please check your inputs.';
                }
            })
            .catch(() => {
                this.createLoading = false;
                this.createError = 'A network error occurred. Please try again.';
            });
        },

        submitEdit() {
            if (!this.editForm.name.trim()) {
                this.editError = 'Supplier name is required.';
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
                body: JSON.stringify({
                    name: this.editForm.name,
                    phone: this.editForm.phone,
                    email: this.editForm.email,
                    address: this.editForm.address
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.editLoading = false;
                if (status === 200) {
                    this.editModalOpen = false;
                    window.location.reload();
                } else {
                    this.editError = body.message || 'Failed to update supplier.';
                }
            })
            .catch(() => {
                this.editLoading = false;
                this.editError = 'A network error occurred. Please try again.';
            });
        }
     }">

    {{-- Breadcrumbs & Navigation Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span>Directory</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Suppliers</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Suppliers Directory</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Manage raw material vendors, equipment partners, credit facilities, and procurement transactions.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('reports.suppliers.statement') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Vendor Statements
            </a>

            @can('suppliers.create')
                <button type="button"
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Supplier
                </button>
            @endcan
        </div>
    </div>

    {{-- 4 Executive Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Registered Suppliers --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Suppliers</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalSuppliers ?? 0) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Registered vendor accounts
                </div>
            </div>
        </div>

        {{-- Active Procuring Suppliers --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Active Procurement</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    {{ number_format($activeSuppliers ?? 0) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ $totalSuppliers > 0 ? round(($activeSuppliers / $totalSuppliers) * 100) : 0 }}% active vendor engagement
                </div>
            </div>
        </div>

        {{-- Total Procurement Volume (Spend) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Lifetime Spend</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalSpend ?? 0, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Invoiced purchases volume
                </div>
            </div>
        </div>

        {{-- Outstanding Payables / Debt --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Outstanding Payables</span>
                <span class="w-8 h-8 rounded-xl {{ ($totalPayables ?? 0) > 0 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' : 'bg-slate-50 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight {{ ($totalPayables ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($totalPayables ?? 0, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Supplier credit & pending balances
                </div>
            </div>
        </div>
    </div>

    {{-- Filter & Search Toolbar --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('suppliers.index') }}" @submit.prevent="fetchResults()" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input
                    type="text"
                    name="q"
                    x-model="searchQuery"
                    @input="fetchResults()"
                    placeholder="Search by vendor name, email, phone number or address..."
                    class="w-full pl-9 pr-9 text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-teal-500 focus:ring-teal-500"
                >
                <template x-if="searchQuery">
                    <button type="button"
                            @click="searchQuery = ''; fetchResults()"
                            class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                            title="Clear search">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </template>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>

                <template x-if="searchQuery">
                    <button type="button"
                            @click="searchQuery = ''; fetchResults()"
                            class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Reset
                    </button>
                </template>
            </div>
        </form>

        {{-- Filter Status Strip --}}
        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
            <span class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full" :class="loading ? 'bg-amber-400 animate-ping' : 'bg-emerald-400'"></span>
                <span x-text="loading ? 'Refreshing ledger...' : 'Live database connection active'"></span>
            </span>
            <span>Showing registered suppliers directory</span>
        </div>
    </div>

    {{-- Suppliers Directory Table Container --}}
    <div id="suppliers-table-container" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        @include('suppliers._table', ['suppliers' => $suppliers])
    </div>

    {{-- ============================================================ --}}
    {{-- 1. CREATE SUPPLIER MODAL                                     --}}
    {{-- ============================================================ --}}
    @can('suppliers.create')
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Add New Supplier</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Register a new vendor or procurement partner</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Error Notice --}}
            <template x-if="createError">
                <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300" x-text="createError"></div>
            </template>

            <form @submit.prevent="submitCreate()" class="mt-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Supplier / Company Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           x-model="createForm.name"
                           required
                           placeholder="e.g. Bralirwa, Inyange Industries, Prime Packaging"
                           class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Phone Number
                        </label>
                        <input type="text"
                               x-model="createForm.phone"
                               placeholder="e.g. +250 788 000 000"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Email Address
                        </label>
                        <input type="email"
                               x-model="createForm.email"
                               placeholder="e.g. sales@vendor.rw"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Physical Address / Location
                    </label>
                    <textarea x-model="createForm.address"
                              rows="2"
                              placeholder="e.g. Kigali Special Economic Zone, Masoro, Plot 14"
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
                        <span x-text="createLoading ? 'Saving...' : 'Register Supplier'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- 2. EDIT SUPPLIER MODAL                                       --}}
    {{-- ============================================================ --}}
    @can('suppliers.edit')
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
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Supplier Details</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Update vendor contact credentials and address</p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Error Notice --}}
            <template x-if="editError">
                <div class="mt-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300" x-text="editError"></div>
            </template>

            <form @submit.prevent="submitEdit()" class="mt-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Supplier / Company Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           x-model="editForm.name"
                           required
                           class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Phone Number
                        </label>
                        <input type="text"
                               x-model="editForm.phone"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Email Address
                        </label>
                        <input type="email"
                               x-model="editForm.email"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Physical Address / Location
                    </label>
                    <textarea x-model="editForm.address"
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
    {{-- 3. QUICK SHOW SUPPLIER MODAL                                 --}}
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
                    <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-sm ring-1 ring-teal-200 dark:ring-teal-800">
                        <span x-text="showData.name ? showData.name.substring(0,2).toUpperCase() : 'SU'"></span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white" x-text="showData.name"></h3>
                        <div class="text-[11px] font-mono text-slate-400">
                            #SUP-<span x-text="String(showData.id).padStart(4, '0')"></span>
                        </div>
                    </div>
                </div>
                <button type="button" @click="showModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- 3 KPI Strip --}}
            <div class="grid grid-cols-3 gap-2.5 my-4">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-center">
                    <div class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Purchase Orders</div>
                    <div class="text-base font-bold font-mono text-slate-900 dark:text-white mt-0.5" x-text="showData.orders_count"></div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-center">
                    <div class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Lifetime Spend</div>
                    <div class="text-xs font-bold font-mono text-slate-900 dark:text-white mt-0.5" x-text="Number(showData.total_spend).toLocaleString(undefined, {minimumFractionDigits: 0}) + ' RWF'"></div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-center">
                    <div class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Balance Due</div>
                    <div class="text-xs font-bold font-mono mt-0.5" :class="showData.balance_due > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'" x-text="Number(showData.balance_due).toLocaleString(undefined, {minimumFractionDigits: 0}) + ' RWF'"></div>
                </div>
            </div>

            {{-- Contact Credentials --}}
            <div class="space-y-2.5 text-xs">
                <div class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span class="text-slate-500 dark:text-slate-400 w-16">Phone:</span>
                    <span class="font-mono font-medium text-slate-800 dark:text-slate-200" x-text="showData.phone || 'Not provided'"></span>
                </div>

                <div class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-slate-500 dark:text-slate-400 w-16">Email:</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200" x-text="showData.email || 'Not provided'"></span>
                </div>

                <div class="flex items-start gap-2 p-2.5 rounded-xl bg-slate-50/70 dark:bg-slate-800/40">
                    <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-slate-500 dark:text-slate-400 w-16">Address:</span>
                    <span class="text-slate-800 dark:text-slate-200 leading-relaxed" x-text="showData.address || 'Not provided'"></span>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                <a :href="showData.statement_url" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                    <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Statement
                </a>

                <div class="flex items-center gap-2">
                    <button type="button" @click="showModalOpen = false" class="px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Close
                    </button>
                    <a :href="showData.show_url" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                        Full Profile & Orders
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 4. DEDICATED DELETE SUPPLIER MODAL                           --}}
    {{-- ============================================================ --}}
    @can('suppliers.delete')
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
                        Delete Supplier Account
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to remove <strong class="text-slate-800 dark:text-slate-200" x-text="deleteSupplierName"></strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Procurement & Audit Integrity Notice
                </div>
                <p class="text-amber-700 dark:text-amber-400 pl-5">
                    Historical purchase orders, receipts, and material inward records linked to this vendor will remain preserved in the audit ledger to protect inventory valuations.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Keep Supplier
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
