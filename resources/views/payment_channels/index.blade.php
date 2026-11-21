@extends('layouts.app')
@section('title', 'Payment Gateways & Financial Channels')

@section('content')
<div x-data="{
        search: '{{ addslashes($q ?? '') }}',
        status: '{{ $filterStatus ?? 'all' }}',
        isLoading: false,
        deleteModalOpen: false,
        deleteId: null,
        deleteName: '',
        deleteTxCount: 0,
        deleteForce: false,

        openDeleteModal(id, name, txCount) {
            this.deleteId = id;
            this.deleteName = name;
            this.deleteTxCount = txCount;
            this.deleteForce = false;
            this.deleteModalOpen = true;
        },

        closeDeleteModal() {
            this.deleteModalOpen = false;
            this.deleteId = null;
            this.deleteName = '';
            this.deleteTxCount = 0;
            this.deleteForce = false;
        },

        filterByStatus(newStatus) {
            this.status = newStatus;
            this.applyFilters();
        },

        resetFilters() {
            this.search = '';
            this.status = 'all';
            this.applyFilters();
        },

        applyFilters() {
            this.isLoading = true;
            const params = new URLSearchParams();
            if (this.search) params.set('q', this.search);
            if (this.status && this.status !== 'all') params.set('status', this.status);
            params.set('table_only', '1');

            fetch(`{{ route('payment-channels.index') }}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(res => res.text())
            .then(html => {
                document.getElementById('channels-table-container').innerHTML = html;
                this.isLoading = false;

                // Update browser URL without full reload
                const cleanParams = new URLSearchParams();
                if (this.search) cleanParams.set('q', this.search);
                if (this.status && this.status !== 'all') cleanParams.set('status', this.status);
                const queryStr = cleanParams.toString();
                const newUrl = `${window.location.pathname}${queryStr ? '?' + queryStr : ''}`;
                window.history.replaceState({}, '', newUrl);
            })
            .catch(err => {
                console.error(err);
                this.isLoading = false;
            });
        }
     }"
     @keydown.escape.window="closeDeleteModal()"
     class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- ========================================================================= --}}
    {{-- 1. BREADCRUMBS & EXECUTIVE HEADER                                         --}}
    {{-- ========================================================================= --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <span>Financial Management</span>
                <span>/</span>
                <span>Settings &amp; Configuration</span>
                <span>/</span>
                <span>Payment Gateways</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2.5 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <span>Payment Channels &amp; Gateways</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl">
                Configure accepted billing methods across POS checkouts, loan disbursements, supplier purchases, and operating expense settlements.
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('transactions.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span>Transactions Ledger</span>
            </a>

            <a href="{{ route('expenses.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>Operating Expenses</span>
            </a>

            @can('payment-channels.create')
                <a href="{{ route('payment-channels.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span>+ Add Payment Channel</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. 4-KPI METRIC DECK                                                      --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Channels --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Channels</p>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($stats['total'] ?? 0) }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Configured gateways</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
        </div>

        {{-- Card 2: Active Operational Channels --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Gateways</p>
                <div class="flex items-center gap-2 mt-1">
                    <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['active'] ?? 0) }}</h3>
                    @if(($stats['total'] ?? 0) > 0)
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40">
                            {{ round((($stats['active'] ?? 0) / $stats['total']) * 100) }}% Live
                        </span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Ready at checkout</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        {{-- Card 3: Inactive / Paused Channels --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Paused / Inactive</p>
                <h3 class="text-2xl font-extrabold text-slate-600 dark:text-slate-400 mt-1">{{ number_format($stats['inactive'] ?? 0) }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Disabled at checkout</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        {{-- Card 4: Financial Transactions --}}
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Recorded Activity</p>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($stats['transactions'] ?? 0) }}</h3>
                <p class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">
                    {{ number_format($stats['volume'] ?? 0, 2) }} RWF
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3. FILTER TOOLBAR & LIVE SEARCH                                           --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        {{-- Status Filter Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
            <button type="button"
                    @click="filterByStatus('all')"
                    :class="status === 'all' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                All Gateways ({{ $stats['total'] ?? 0 }})
            </button>
            <button type="button"
                    @click="filterByStatus('active')"
                    :class="status === 'active' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Active ({{ $stats['active'] ?? 0 }})</span>
            </button>
            <button type="button"
                    @click="filterByStatus('inactive')"
                    :class="status === 'inactive' ? 'bg-white dark:bg-slate-900 text-slate-800 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                <span>Inactive ({{ $stats['inactive'] ?? 0 }})</span>
            </button>
        </div>

        {{-- Search Input & Reset --}}
        <div class="flex items-center gap-2">
            <div class="relative w-full md:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       x-model="search"
                       @input.debounce.300ms="applyFilters()"
                       placeholder="Search name, slug, description..."
                       class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                <button type="button"
                        x-show="search.length > 0"
                        @click="search = ''; applyFilters();"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <button type="button"
                    x-show="search.length > 0 || status !== 'all'"
                    @click="resetFilters()"
                    class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Reset
            </button>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 4. TABLE CONTAINER                                                        --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden relative">
        {{-- Loading overlay --}}
        <div x-show="isLoading"
             x-cloak
             class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-10">
            <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white dark:bg-slate-800 shadow-md border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200">
                <svg class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Updating gateways...</span>
            </div>
        </div>

        <div id="channels-table-container">
            @include('payment_channels._table', ['channels' => $channels])
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 5. DELETE CONFIRMATION MODAL                                              --}}
    {{-- ========================================================================= --}}
    <div x-show="deleteModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
            <div x-show="deleteModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeDeleteModal()"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Panel --}}
            <div x-show="deleteModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800 p-6 space-y-4">

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white" id="modal-title">
                            Delete Payment Channel
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Are you sure you want to delete <span class="font-bold text-slate-900 dark:text-white" x-text="deleteName"></span>?
                        </p>
                    </div>
                </div>

                {{-- Financial Integrity Warning when transactions exist --}}
                <template x-if="deleteTxCount > 0">
                    <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 text-xs text-amber-800 dark:text-amber-300 space-y-2">
                        <div class="flex items-center gap-1.5 font-bold">
                            <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Financial Audit Trail Alert</span>
                        </div>
                        <p>
                            This channel is linked to <strong x-text="deleteTxCount"></strong> recorded ledger transactions. We strongly recommend setting its status to <strong>Inactive</strong> instead of deleting it to preserve financial reports and receipts.
                        </p>
                        <div class="pt-1 flex items-center gap-2">
                            <input type="checkbox" id="confirmForce" x-model="deleteForce" class="rounded border-amber-400 text-rose-600 focus:ring-rose-500">
                            <label for="confirmForce" class="text-[11px] text-amber-900 dark:text-amber-200 font-medium">
                                I understand the risks and want to force delete
                            </label>
                        </div>
                    </div>
                </template>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            @click="closeDeleteModal()"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition cursor-pointer">
                        Cancel
                    </button>

                    <form :action="`{{ url('payment-channels') }}/${deleteId}`" method="POST">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="force" :value="deleteForce ? 1 : 0">
                        <button type="submit"
                                :disabled="deleteTxCount > 0 && !deleteForce"
                                :class="(deleteTxCount > 0 && !deleteForce) ? 'opacity-50 cursor-not-allowed bg-rose-400' : 'bg-rose-600 hover:bg-rose-700 active:scale-95 shadow-md shadow-rose-500/20 cursor-pointer'"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-white transition">
                            Confirm Deletion
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
