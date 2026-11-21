@extends('layouts.app')
@section('title', 'Inter-Company Item Loans')

@section('content')
@php
    use Carbon\Carbon;
    $today  = Carbon::today()->toDateString();
    $weekS  = Carbon::today()->startOfWeek()->toDateString();
    $monthS = Carbon::today()->startOfMonth()->toDateString();

    $currentSearch    = $filters['search'] ?? '';
    $currentDirection = $filters['direction'] ?? '';
    $currentStatus    = $filters['status'] ?? '';
    $currentPartner   = $filters['partner_id'] ?? '';
    $currentFrom      = $filters['from'] ?? '';
    $currentTo        = $filters['to'] ?? '';
    $currentOverdue   = !empty($filters['overdue']) ? '1' : '';

    $fmt = fn($n) => number_format((float)$n, 2);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        filters: {
            search: '{{ addslashes($currentSearch) }}',
            direction: '{{ $currentDirection }}',
            status: '{{ $currentStatus }}',
            partner_id: '{{ $currentPartner }}',
            from: '{{ $currentFrom }}',
            to: '{{ $currentTo }}',
            overdue: '{{ $currentOverdue }}'
        },
        loading: false,
        timeout: null,

        // Modal 1: Create Loan
        createModalOpen: false,
        createLoading: false,
        createError: '',
        createForm: {
            partner_id: '',
            product_id: '',
            direction: 'given',
            item_name: '',
            unit: 'pcs',
            quantity: '',
            loan_date: '{{ now()->toDateString() }}',
            due_date: '',
            notes: ''
        },

        // Modal 2: Record Return
        returnModalOpen: false,
        returnLoading: false,
        returnError: '',
        returnForm: {
            id: null,
            code: '',
            item_name: '',
            partner_name: '',
            direction: '',
            unit: 'pcs',
            quantity: 0,
            remaining: 0,
            remaining_formatted: '0.00',
            returned_qty: '',
            return_date: '{{ now()->toDateString() }}',
            note: '',
            has_product: false,
            action_url: ''
        },

        // Modal 3: Quick View
        showModalOpen: false,
        showLoading: false,
        showData: {},

        // Modal 4: Edit Loan
        editModalOpen: false,
        editLoading: false,
        editError: '',
        editForm: {
            id: null,
            code: '',
            partner_id: '',
            product_id: '',
            direction: 'given',
            item_name: '',
            unit: 'pcs',
            quantity: '',
            quantity_returned: 0,
            has_returns: false,
            loan_date: '',
            due_date: '',
            notes: '',
            action_url: ''
        },

        // Modal 5: Delete Confirmation
        deleteModalOpen: false,
        deleteLoading: false,
        deleteActionUrl: '',
        deleteCode: '',
        deleteItemName: '',
        deleteQty: '',

        // Modal 6: Quick Add Partner
        quickPartnerModalOpen: false,
        quickPartnerLoading: false,
        quickPartnerError: '',
        quickPartnerForm: {
            name: '',
            contact_person: '',
            phone: '',
            email: '',
            address: ''
        },

        // Products database cache for fast autofill
        productsList: {{ json_encode($products) }},

        onProductChange(type) {
            const form = type === 'create' ? this.createForm : this.editForm;
            if (!form.product_id) return;
            const p = this.productsList.find(item => item.id == form.product_id);
            if (p) {
                form.item_name = p.name;
                if (p.unit) form.unit = p.unit;
            }
        },

        setDueDatePreset(days, type) {
            const form = type === 'create' ? this.createForm : this.editForm;
            const baseDate = form.loan_date ? new Date(form.loan_date) : new Date();
            baseDate.setDate(baseDate.getDate() + days);
            form.due_date = baseDate.toISOString().split('T')[0];
        },

        openShowModal(data) {
            this.showData = data;
            this.showModalOpen = true;

            // Fetch live returns and full details via AJAX
            this.showLoading = true;
            fetch('/inter-loans/' + data.id, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(full => {
                this.showData = full;
                this.showLoading = false;
            })
            .catch(() => {
                this.showLoading = false;
            });
        },

        openReturnModal(data) {
            this.returnForm = {
                id: data.id,
                code: data.code,
                item_name: data.item_name,
                partner_name: data.partner_name,
                direction: data.direction,
                unit: data.unit,
                quantity: data.quantity,
                remaining: data.remaining,
                remaining_formatted: data.remaining_formatted,
                returned_qty: data.remaining,
                return_date: '{{ now()->toDateString() }}',
                note: '',
                has_product: data.has_product,
                action_url: data.action_url
            };
            this.returnError = '';
            this.returnModalOpen = true;
        },

        fillFullReturn() {
            this.returnForm.returned_qty = this.returnForm.remaining;
        },

        openEditModal(data) {
            this.editForm = {
                id: data.id,
                code: data.code,
                partner_id: data.partner_id || '',
                product_id: data.product_id || '',
                direction: data.direction,
                item_name: data.item_name,
                unit: data.unit || 'pcs',
                quantity: data.quantity,
                quantity_returned: data.quantity_returned,
                has_returns: data.has_returns,
                loan_date: data.loan_date || '',
                due_date: data.due_date || '',
                notes: data.notes || '',
                action_url: data.action_url
            };
            this.editError = '';
            this.editModalOpen = true;
        },

        openDeleteModal(url, code, name, qty) {
            this.deleteActionUrl = url;
            this.deleteCode = code;
            this.deleteItemName = name;
            this.deleteQty = qty;
            this.deleteModalOpen = true;
        },

        fetchResults() {
            this.loading = true;
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                const url = new URL('{{ route('item-loans.index') }}', window.location.origin);
                Object.keys(this.filters).forEach(k => {
                    if (this.filters[k] !== '' && this.filters[k] !== null) {
                        url.searchParams.set(k, this.filters[k]);
                    }
                });

                // Update browser URL without reloading
                window.history.replaceState({}, '', url.toString());

                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('item-loans-table-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
                    }
                    if (data.stats) {
                        const fmt = n => Number(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        if (document.getElementById('stat-active-qty')) document.getElementById('stat-active-qty').innerText = fmt(data.stats.total_active_qty);
                        if (document.getElementById('stat-active-sub')) document.getElementById('stat-active-sub').innerText = data.stats.active_count + ' active vouchers';
                        if (document.getElementById('stat-given-qty')) document.getElementById('stat-given-qty').innerText = fmt(data.stats.given_qty);
                        if (document.getElementById('stat-given-sub')) document.getElementById('stat-given-sub').innerText = data.stats.given_count + ' lending vouchers';
                        if (document.getElementById('stat-taken-qty')) document.getElementById('stat-taken-qty').innerText = fmt(data.stats.taken_qty);
                        if (document.getElementById('stat-taken-sub')) document.getElementById('stat-taken-sub').innerText = data.stats.taken_count + ' borrowing vouchers';
                        if (document.getElementById('stat-overdue-qty')) document.getElementById('stat-overdue-qty').innerText = fmt(data.stats.overdue_qty);
                        if (document.getElementById('stat-overdue-sub')) document.getElementById('stat-overdue-sub').innerText = data.stats.overdue_count + ' overdue loans';
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
            this.filters = { search: '', direction: '', status: '', partner_id: '', from: '', to: '', overdue: '' };
            this.fetchResults();
        },

        submitCreate() {
            if (!this.createForm.partner_id) {
                this.createError = 'Please select a Partner Company.';
                return;
            }
            if (!this.createForm.item_name.trim()) {
                this.createError = 'Item Name is required.';
                return;
            }
            if (!this.createForm.quantity || parseFloat(this.createForm.quantity) <= 0) {
                this.createError = 'Please enter a valid loan quantity greater than 0.';
                return;
            }
            if (!this.createForm.loan_date) {
                this.createError = 'Loan Date is required.';
                return;
            }

            this.createLoading = true;
            this.createError = '';

            fetch('{{ route('item-loans.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(this.createForm)
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        throw new Error(data.errors[firstKey][0]);
                    }
                    throw new Error(data.message || 'Failed to save item loan.');
                }
                return data;
            })
            .then(() => {
                this.createLoading = false;
                this.createModalOpen = false;
                this.createForm = {
                    partner_id: '',
                    product_id: '',
                    direction: 'given',
                    item_name: '',
                    unit: 'pcs',
                    quantity: '',
                    loan_date: '{{ now()->toDateString() }}',
                    due_date: '',
                    notes: ''
                };
                this.fetchResults();
            })
            .catch(err => {
                this.createLoading = false;
                this.createError = err.message;
            });
        },

        submitReturn() {
            const retQty = parseFloat(this.returnForm.returned_qty);
            if (!retQty || retQty <= 0) {
                this.returnError = 'Please enter a valid returned quantity greater than 0.';
                return;
            }
            if (retQty > (this.returnForm.remaining + 0.0001)) {
                this.returnError = 'Returned quantity cannot exceed the outstanding remaining quantity (' + this.returnForm.remaining_formatted + ').';
                return;
            }
            if (!this.returnForm.return_date) {
                this.returnError = 'Return Date is required.';
                return;
            }

            this.returnLoading = true;
            this.returnError = '';

            fetch(this.returnForm.action_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    returned_qty: this.returnForm.returned_qty,
                    return_date: this.returnForm.return_date,
                    note: this.returnForm.note
                })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        throw new Error(data.errors[firstKey][0]);
                    }
                    throw new Error(data.message || 'Failed to record return.');
                }
                return data;
            })
            .then(() => {
                this.returnLoading = false;
                this.returnModalOpen = false;
                this.fetchResults();
            })
            .catch(err => {
                this.returnLoading = false;
                this.returnError = err.message;
            });
        },

        submitEdit() {
            if (!this.editForm.partner_id) {
                this.editError = 'Please select a Partner Company.';
                return;
            }
            if (!this.editForm.item_name.trim()) {
                this.editError = 'Item Name is required.';
                return;
            }
            if (!this.editForm.has_returns && (!this.editForm.quantity || parseFloat(this.editForm.quantity) <= 0)) {
                this.editError = 'Please enter a valid positive quantity.';
                return;
            }
            if (!this.editForm.loan_date) {
                this.editError = 'Loan Date is required.';
                return;
            }

            this.editLoading = true;
            this.editError = '';

            fetch(this.editForm.action_url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    partner_id: this.editForm.partner_id,
                    product_id: this.editForm.product_id || null,
                    direction: this.editForm.direction,
                    item_name: this.editForm.item_name,
                    unit: this.editForm.unit,
                    quantity: this.editForm.quantity,
                    loan_date: this.editForm.loan_date,
                    due_date: this.editForm.due_date || null,
                    notes: this.editForm.notes
                })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        throw new Error(data.errors[firstKey][0]);
                    }
                    throw new Error(data.message || 'Failed to update item loan.');
                }
                return data;
            })
            .then(() => {
                this.editLoading = false;
                this.editModalOpen = false;
                this.fetchResults();
            })
            .catch(err => {
                this.editLoading = false;
                this.editError = err.message;
            });
        },

        submitDelete() {
            this.deleteLoading = true;
            fetch(this.deleteActionUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Failed to delete loan.');
                }
                return data;
            })
            .then(() => {
                this.deleteLoading = false;
                this.deleteModalOpen = false;
                this.fetchResults();
            })
            .catch(err => {
                this.deleteLoading = false;
                alert(err.message);
            });
        },

        submitQuickPartner() {
            if (!this.quickPartnerForm.name.trim()) {
                this.quickPartnerError = 'Company Name is required.';
                return;
            }
            this.quickPartnerLoading = true;
            this.quickPartnerError = '';

            fetch('{{ route('partner-companies.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(this.quickPartnerForm)
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        throw new Error(data.errors[firstKey][0]);
                    }
                    throw new Error(data.message || 'Failed to add partner company.');
                }
                return data;
            })
            .then(data => {
                this.quickPartnerLoading = false;
                this.quickPartnerModalOpen = false;

                // Add to selects
                const partner = data.partner;
                const selects = document.querySelectorAll('select[name=\'partner_id\']');
                selects.forEach(select => {
                    const opt = new Option(partner.name, partner.id, true, true);
                    select.add(opt);
                });

                this.createForm.partner_id = partner.id;
                this.quickPartnerForm = { name: '', contact_person: '', phone: '', email: '', address: '' };
            })
            .catch(err => {
                this.quickPartnerLoading = false;
                this.quickPartnerError = err.message;
            });
        }
     }">

    {{-- Top Action Bar & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <span>Inventory & Assets Logistics</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Inter-Company Item Loans
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Manage loaned materials, branch asset exchanges, and counterparty return audits.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Manage Partners Link --}}
            <a href="{{ route('partner-companies.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Partner Companies</span>
            </a>

            {{-- New Item Loan Trigger --}}
            @can('item-loans.create')
                <button type="button"
                        @click="createModalOpen = true; createError = ''"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Record Item Loan</span>
                </button>
            @endcan
        </div>
    </div>

    {{-- 4-KPI Deck --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- KPI 1: Active Loans Outstanding --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group hover:border-indigo-400 dark:hover:border-indigo-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Active Outstanding
                </span>
                <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span id="stat-active-qty" class="text-2xl font-black font-mono text-slate-900 dark:text-white">
                    {{ $fmt($stats['total_active_qty']) }}
                </span>
                <span class="text-xs text-slate-400 font-medium">units</span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span id="stat-active-sub">{{ $stats['active_count'] }} active vouchers</span>
            </div>
        </div>

        {{-- KPI 2: Items Lent (Given / Out) --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group hover:border-emerald-400 dark:hover:border-emerald-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Items Lent (Outflow)
                </span>
                <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l10-10M17 7H7m10 0v10" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span id="stat-given-qty" class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                    {{ $fmt($stats['given_qty']) }}
                </span>
                <span class="text-xs text-slate-400 font-medium">total lent</span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span id="stat-given-sub">{{ $stats['given_count'] }} lending vouchers</span>
            </div>
        </div>

        {{-- KPI 3: Items Borrowed (Taken / In) --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group hover:border-indigo-400 dark:hover:border-indigo-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Items Borrowed (Inflow)
                </span>
                <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7 17M7 17h10M7 17V7" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span id="stat-taken-qty" class="text-2xl font-black font-mono text-indigo-600 dark:text-indigo-400">
                    {{ $fmt($stats['taken_qty']) }}
                </span>
                <span class="text-xs text-slate-400 font-medium">borrowed in</span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <span id="stat-taken-sub">{{ $stats['taken_count'] }} borrowing vouchers</span>
            </div>
        </div>

        {{-- KPI 4: Overdue Loans --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group hover:border-rose-400 dark:hover:border-rose-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Overdue Returns
                </span>
                <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span id="stat-overdue-qty" class="text-2xl font-black font-mono text-rose-600 dark:text-rose-400">
                    {{ $fmt($stats['overdue_qty']) }}
                </span>
                <span class="text-xs text-slate-400 font-medium">units overdue</span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span class="w-2 h-2 rounded-full {{ $stats['overdue_count'] > 0 ? 'bg-rose-500 animate-ping' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                <span id="stat-overdue-sub">{{ $stats['overdue_count'] }} overdue loans</span>
            </div>
        </div>
    </div>

    {{-- Interactive Filter Toolbar --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
        {{-- Quick Range Selector Pills & Status Toggles --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800/80">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-400 mr-1">Timeframe:</span>
                <button type="button" @click="setPeriod('')"
                        :class="!filters.from && !filters.to && !filters.overdue ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                        class="px-3 py-1 rounded-lg text-xs font-semibold transition">
                    All Time
                </button>
                <button type="button" @click="setPeriod('today')"
                        :class="filters.from === '{{ $today }}' && filters.to === '{{ $today }}' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                        class="px-3 py-1 rounded-lg text-xs font-semibold transition">
                    Today
                </button>
                <button type="button" @click="setPeriod('week')"
                        :class="filters.from === '{{ $weekS }}' && filters.to === '{{ $today }}' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                        class="px-3 py-1 rounded-lg text-xs font-semibold transition">
                    This Week
                </button>
                <button type="button" @click="setPeriod('month')"
                        :class="filters.from === '{{ $monthS }}' && filters.to === '{{ $today }}' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                        class="px-3 py-1 rounded-lg text-xs font-semibold transition">
                    This Month
                </button>
                <button type="button" @click="setPeriod('overdue')"
                        :class="filters.overdue === '1' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800/50'"
                        class="px-3 py-1 rounded-lg text-xs font-semibold transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Overdue Only</span>
                </button>
            </div>

            {{-- Live Indicator --}}
            <div class="flex items-center gap-2">
                <span x-show="loading" x-cloak class="flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 font-medium animate-pulse">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Updating list...
                </span>
                <button type="button" @click="resetFilters()"
                        class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 underline font-medium">
                    Reset
                </button>
            </div>
        </div>

        {{-- Multi-parameter Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Search Input --}}
            <div class="lg:col-span-2 relative">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Search</label>
                <div class="relative">
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.300ms="fetchResults()"
                           placeholder="Search item, partner, #IL-..."
                           class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button type="button" x-show="filters.search" @click="filters.search = ''; fetchResults()"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Direction Filter --}}
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Direction</label>
                <select x-model="filters.direction"
                        @change="fetchResults()"
                        class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                    <option value="">All Directions</option>
                    <option value="given">We LENT (Given)</option>
                    <option value="taken">We BORROWED (Taken)</option>
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status</label>
                <select x-model="filters.status"
                        @change="fetchResults()"
                        class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="partial">Partial</option>
                    <option value="returned">Returned</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>

            {{-- Partner Filter --}}
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Partner Entity</label>
                <select x-model="filters.partner_id"
                        @change="fetchResults()"
                        class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                    <option value="">All Partners</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range (From - To) --}}
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Loan Date From</label>
                <input type="date"
                       x-model="filters.from"
                       @change="fetchResults()"
                       class="w-full py-2 px-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
            </div>
        </div>
    </div>

    {{-- Table Container --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div id="item-loans-table-container">
            @include('item_loans._table')
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 1: RECORD NEW ITEM LOAN (Alpine)                                    --}}
    {{-- ========================================================================= --}}
    <div x-show="createModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
            <div x-show="createModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="createModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Content --}}
            <div x-show="createModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full border border-slate-200 dark:border-slate-700">

                {{-- Modal Header --}}
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 dark:bg-indigo-950/50 rounded-xl text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Inter-Company Item Loan</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Issue or receive physical assets or product stock.</p>
                        </div>
                    </div>
                    <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <form @submit.prevent="submitCreate()" class="p-6 space-y-4">
                    {{-- Error Notice --}}
                    <div x-show="createError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2">
                        <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span x-text="createError"></span>
                    </div>

                    {{-- Direction Switcher --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Loan Direction <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer border rounded-xl p-3 flex items-center gap-3 transition"
                                   :class="createForm.direction === 'given' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-900 dark:text-emerald-200 shadow-xs ring-1 ring-emerald-500' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                                <input type="radio" value="given" x-model="createForm.direction" class="sr-only">
                                <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l10-10M17 7H7m10 0v10" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <div class="text-xs font-bold">We LENT (Given)</div>
                                    <div class="text-[10px] text-slate-500">Asset outflow to partner</div>
                                </div>
                            </label>

                            <label class="cursor-pointer border rounded-xl p-3 flex items-center gap-3 transition"
                                   :class="createForm.direction === 'taken' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-900 dark:text-indigo-200 shadow-xs ring-1 ring-indigo-500' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                                <input type="radio" value="taken" x-model="createForm.direction" class="sr-only">
                                <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7 17M7 17h10M7 17V7" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <div class="text-xs font-bold">We BORROWED (Taken)</div>
                                    <div class="text-[10px] text-slate-500">Asset inflow from partner</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Partner Selection + Inline Trigger --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Partner Company <span class="text-rose-500">*</span>
                            </label>
                            <button type="button" @click="quickPartnerModalOpen = true; quickPartnerError = ''"
                                    class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Add New Partner</span>
                            </button>
                        </div>
                        <select x-model="createForm.partner_id" name="partner_id" required
                                class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                            <option value="">Select a partner company...</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Linked Product (Inventory Sync) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Link to Inventory <span class="text-[10px] font-normal text-slate-400">(Optional)</span>
                            </label>
                            <select x-model="createForm.product_id" @change="onProductChange('create')"
                                    class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                                <option value="">-- Manual Item (No Link) --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->name }} (Stock: {{ $prod->stock }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Item / Asset Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="createForm.item_name" required placeholder="e.g., Cocktail Glasses, Crates"
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                    </div>

                    {{-- Quantity and Unit --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Loan Quantity <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" x-model="createForm.quantity" required placeholder="0.00"
                                   class="w-full py-2 px-3 rounded-xl text-xs font-mono font-bold bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Unit of Measure
                            </label>
                            <input type="text" x-model="createForm.unit" placeholder="pcs, crates"
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Loan Date <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" x-model="createForm.loan_date" required
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Due Date <span class="text-[10px] font-normal text-slate-400">(Optional)</span>
                                </label>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="setDueDatePreset(7, 'create')" class="text-[10px] text-indigo-600 hover:underline">+7d</button>
                                    <span class="text-[10px] text-slate-300">•</span>
                                    <button type="button" @click="setDueDatePreset(14, 'create')" class="text-[10px] text-indigo-600 hover:underline">+14d</button>
                                </div>
                            </div>
                            <input type="date" x-model="createForm.due_date"
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Notes / Terms / Event Context
                        </label>
                        <textarea x-model="createForm.notes" rows="2" placeholder="Purpose, event details, return condition requirements..."
                                  class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50"></textarea>
                    </div>

                    {{-- Footer Buttons --}}
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2.5">
                        <button type="button" @click="createModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="createLoading"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 active:scale-95 transition disabled:opacity-50">
                            <span x-show="!createLoading">Record Loan Voucher</span>
                            <span x-show="createLoading" x-cloak class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 2: RECORD ITEM RETURN (Alpine)                                      --}}
    {{-- ========================================================================= --}}
    <div x-show="returnModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="returnModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="returnModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="returnModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-200 dark:border-slate-700">

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Item Return</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Voucher <span class="font-mono font-bold" x-text="returnForm.code"></span> • <span x-text="returnForm.partner_name"></span>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="returnModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitReturn()" class="p-6 space-y-4">
                    <div x-show="returnError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2">
                        <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span x-text="returnError"></span>
                    </div>

                    {{-- Summary Card --}}
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-xs flex items-center justify-between">
                        <div>
                            <span class="text-slate-400">Item:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 ml-1" x-text="returnForm.item_name"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-slate-400">Remaining to return:</span>
                            <span class="font-mono font-bold text-amber-600 dark:text-amber-400 ml-1">
                                <span x-text="returnForm.remaining_formatted"></span> <span x-text="returnForm.unit"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Return Quantity --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Returned Quantity <span class="text-rose-500">*</span>
                            </label>
                            <button type="button" @click="fillFullReturn()"
                                    class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                Return All (<span x-text="returnForm.remaining_formatted"></span> <span x-text="returnForm.unit"></span>)
                            </button>
                        </div>
                        <div class="relative">
                            <input type="number" step="0.01" min="0.01" :max="returnForm.remaining" x-model="returnForm.returned_qty" required
                                   class="w-full py-2.5 px-3 rounded-xl text-sm font-mono font-bold bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/50">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400 font-mono" x-text="returnForm.unit"></div>
                        </div>
                    </div>

                    {{-- Return Date --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Return Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" x-model="returnForm.return_date" required
                               class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/50">
                    </div>

                    {{-- Return Note --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Notes / Remarks / Return Condition
                        </label>
                        <textarea x-model="returnForm.note" rows="2" placeholder="e.g., Returned in good condition, signed delivery slip..."
                                  class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/50"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2.5">
                        <button type="button" @click="returnModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="returnLoading"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-500/20 active:scale-95 transition disabled:opacity-50">
                            <span x-show="!returnLoading">Record Return</span>
                            <span x-show="returnLoading" x-cloak class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Recording...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 3: QUICK VIEW VOUCHER DETAILS (Alpine)                              --}}
    {{-- ========================================================================= --}}
    <div x-show="showModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="showModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-slate-200 dark:border-slate-700">

                {{-- Header --}}
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold font-mono text-slate-900 dark:text-white" x-text="showData.code"></h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      :class="showData.direction === 'given' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300'">
                                    <span x-text="showData.direction === 'given' ? 'We LENT (Given)' : 'We BORROWED (Taken)'"></span>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Recorded by <span class="font-medium" x-text="showData.user_name || 'System'"></span>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="showModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    {{-- Key Metric Cards --}}
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Loaned</span>
                            <div class="text-base font-black font-mono text-slate-900 dark:text-white mt-1">
                                <span x-text="Number(showData.quantity || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                <span class="text-xs font-normal text-slate-400" x-text="showData.unit"></span>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/50">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Returned</span>
                            <div class="text-base font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                                <span x-text="Number(showData.quantity_returned || showData.returned || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                <span class="text-xs font-normal text-slate-400" x-text="showData.unit"></span>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/50">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Remaining</span>
                            <div class="text-base font-black font-mono text-amber-600 dark:text-amber-400 mt-1">
                                <span x-text="Number(showData.remaining || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                <span class="text-xs font-normal text-slate-400" x-text="showData.unit"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Return Progress Bar --}}
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-medium text-slate-600 dark:text-slate-300">Return Settlement Progress</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="(showData.progress || 0) + '%'"></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-500"
                                 :class="showData.progress === 100 ? 'bg-emerald-500' : 'bg-indigo-500'"
                                 :style="'width: ' + (showData.progress || 0) + '%'"></div>
                        </div>
                    </div>

                    {{-- Details Grid --}}
                    <div class="grid grid-cols-2 gap-4 text-xs pt-2 border-t border-slate-100 dark:border-slate-700/60">
                        <div>
                            <span class="text-slate-400">Partner Company:</span>
                            <div class="font-semibold text-slate-800 dark:text-slate-100 text-sm mt-0.5" x-text="showData.partner ? showData.partner.name : '—'"></div>
                            <div class="text-[11px] text-slate-400" x-text="showData.partner && showData.partner.contact_person ? showData.partner.contact_person + ' • ' + (showData.partner.phone || '') : ''"></div>
                        </div>

                        <div>
                            <span class="text-slate-400">Item Specification:</span>
                            <div class="font-semibold text-slate-800 dark:text-slate-100 text-sm mt-0.5" x-text="showData.item_name"></div>
                            <template x-if="showData.product">
                                <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    Linked Stock: <span x-text="showData.product.name"></span>
                                </div>
                            </template>
                        </div>

                        <div>
                            <span class="text-slate-400">Loan Issue Date:</span>
                            <div class="font-mono text-slate-800 dark:text-slate-200 mt-0.5" x-text="showData.loan_date || '—'"></div>
                        </div>

                        <div>
                            <span class="text-slate-400">Due Date:</span>
                            <div class="font-mono mt-0.5 flex items-center gap-1.5"
                                 :class="showData.is_overdue ? 'text-rose-600 font-bold' : 'text-slate-800 dark:text-slate-200'">
                                <span x-text="showData.due_date || 'No expiry'"></span>
                                <template x-if="showData.is_overdue">
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">Overdue</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <template x-if="showData.notes">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-xs">
                            <span class="text-slate-400 block mb-0.5">Notes:</span>
                            <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap" x-text="showData.notes"></p>
                        </div>
                    </template>

                    {{-- Return History Table --}}
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Returns Settlement Log
                            </h4>
                            <span class="text-[11px] text-slate-400" x-text="(showData.returns ? showData.returns.length : 0) + ' returns recorded'"></span>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900/60 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                        <th class="py-2 px-3">Return Date</th>
                                        <th class="py-2 px-3 text-right">Qty Returned</th>
                                        <th class="py-2 px-3">Note</th>
                                        <th class="py-2 px-3">Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-slate-700 dark:text-slate-300">
                                    <template x-if="showData.returns && showData.returns.length > 0">
                                        <template x-for="ret in showData.returns" :key="ret.id">
                                            <tr>
                                                <td class="py-2 px-3 font-mono" x-text="ret.return_date"></td>
                                                <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                                    <span x-text="Number(ret.returned_qty).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                                    <span class="text-[10px] font-normal text-slate-400" x-text="showData.unit"></span>
                                                </td>
                                                <td class="py-2 px-3 text-[11px]" x-text="ret.note || '—'"></td>
                                                <td class="py-2 px-3 text-[11px]" x-text="ret.user_name || 'System'"></td>
                                            </tr>
                                        </template>
                                    </template>
                                    <template x-if="!showData.returns || showData.returns.length === 0">
                                        <tr>
                                            <td colspan="4" class="py-4 text-center text-slate-400 italic text-xs">
                                                No returns have been recorded for this loan yet.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <template x-if="showData.id">
                            <a :href="'/inter-loans/' + showData.id"
                               class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                <span>Full Voucher Page</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </template>

                        <div class="flex items-center gap-2">
                            <template x-if="showData.remaining > 0">
                                <button type="button"
                                        @click="showModalOpen = false; openReturnModal({
                                            id: showData.id,
                                            code: showData.code,
                                            item_name: showData.item_name,
                                            partner_name: showData.partner ? showData.partner.name : '',
                                            direction: showData.direction,
                                            unit: showData.unit,
                                            remaining: showData.remaining,
                                            remaining_formatted: Number(showData.remaining).toLocaleString(undefined, {minimumFractionDigits: 2}),
                                            quantity: showData.quantity,
                                            has_product: !!showData.product,
                                            action_url: '/inter-loans/' + showData.id + '/return'
                                        })"
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition">
                                    Record Return
                                </button>
                            </template>
                            <button type="button" @click="showModalOpen = false"
                                    class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 4: EDIT ITEM LOAN (Alpine)                                          --}}
    {{-- ========================================================================= --}}
    <div x-show="editModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="editModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="editModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="editModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full border border-slate-200 dark:border-slate-700">

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-50 dark:bg-amber-950/50 rounded-xl text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Loan Voucher</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Editing <span class="font-mono font-bold" x-text="editForm.code"></span></p>
                        </div>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEdit()" class="p-6 space-y-4">
                    <div x-show="editError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2">
                        <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span x-text="editError"></span>
                    </div>

                    <template x-if="editForm.has_returns">
                        <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200 flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Quantity is locked because returns have already been recorded for this loan.</span>
                        </div>
                    </template>

                    {{-- Direction --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Direction <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer border rounded-xl p-3 flex items-center gap-3 transition"
                                   :class="editForm.direction === 'given' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-900 dark:text-emerald-200 shadow-xs ring-1 ring-emerald-500' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                                <input type="radio" value="given" x-model="editForm.direction" class="sr-only">
                                <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l10-10M17 7H7m10 0v10" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <div class="text-xs font-bold">We LENT (Given)</div>
                                    <div class="text-[10px] text-slate-500">Asset outflow</div>
                                </div>
                            </label>

                            <label class="cursor-pointer border rounded-xl p-3 flex items-center gap-3 transition"
                                   :class="editForm.direction === 'taken' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-900 dark:text-indigo-200 shadow-xs ring-1 ring-indigo-500' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                                <input type="radio" value="taken" x-model="editForm.direction" class="sr-only">
                                <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7 17M7 17h10M7 17V7" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <div class="text-xs font-bold">We BORROWED (Taken)</div>
                                    <div class="text-[10px] text-slate-500">Asset inflow</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Partner Selection --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Partner Company <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="editForm.partner_id" required
                                class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                            <option value="">Select partner...</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Linked Product & Item Name --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Linked Inventory Stock
                            </label>
                            <select x-model="editForm.product_id" @change="onProductChange('edit')"
                                    class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                                <option value="">-- Manual Item (No Link) --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Item / Asset Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="editForm.item_name" required
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                    </div>

                    {{-- Quantity and Unit --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Quantity <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" x-model="editForm.quantity" :readonly="editForm.has_returns" required
                                   :class="editForm.has_returns ? 'opacity-60 cursor-not-allowed bg-slate-100 dark:bg-slate-900' : 'bg-slate-50 dark:bg-slate-900/60'"
                                   class="w-full py-2 px-3 rounded-xl text-xs font-mono font-bold border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Unit</label>
                            <input type="text" x-model="editForm.unit"
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Loan Date <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" x-model="editForm.loan_date" required
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Due Date
                            </label>
                            <input type="date" x-model="editForm.due_date"
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                        <textarea x-model="editForm.notes" rows="2"
                                  class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2.5">
                        <button type="button" @click="editModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editLoading"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 active:scale-95 transition disabled:opacity-50">
                            <span x-show="!editLoading">Save Changes</span>
                            <span x-show="editLoading" x-cloak class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Updating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 5: TWO-STEP DELETE CONFIRMATION (Alpine)                            --}}
    {{-- ========================================================================= --}}
    <div x-show="deleteModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="deleteModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="deleteModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="deleteModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-slate-200 dark:border-slate-700">

                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-full bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete Item Loan Voucher?</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Are you sure you want to permanently delete voucher <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="deleteCode"></span>?
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Item:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="deleteItemName"></span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-slate-400">Quantity:</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="deleteQty"></span>
                        </div>
                    </div>

                    <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-3 font-medium">
                        * Associated inventory stock movement records will also be reversed and removed.
                    </p>

                    <div class="mt-6 flex items-center justify-end gap-2.5">
                        <button type="button" @click="deleteModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Cancel
                        </button>
                        <button type="button" @click="submitDelete()" :disabled="deleteLoading"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-500/20 active:scale-95 transition disabled:opacity-50">
                            <span x-show="!deleteLoading">Confirm Delete</span>
                            <span x-show="deleteLoading" x-cloak class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Deleting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 6: QUICK ADD PARTNER COMPANY (Alpine)                               --}}
    {{-- ========================================================================= --}}
    <div x-show="quickPartnerModalOpen"
         x-cloak
         class="fixed inset-0 z-60 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="quickPartnerModalOpen"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="quickPartnerModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="quickPartnerModalOpen"
                 class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-slate-200 dark:border-slate-700">

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Add Partner Company</span>
                    </h3>
                    <button type="button" @click="quickPartnerModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitQuickPartner()" class="p-6 space-y-4">
                    <div x-show="quickPartnerError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300" x-text="quickPartnerError"></div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Company / Branch Name <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="quickPartnerForm.name" required placeholder="e.g. Bralirwa Depot Gisenyi"
                               class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Contact Person</label>
                            <input type="text" x-model="quickPartnerForm.contact_person" placeholder="Manager name"
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Phone</label>
                            <input type="text" x-model="quickPartnerForm.phone" placeholder="+250 78..."
                                   class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/50">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2.5">
                        <button type="button" @click="quickPartnerModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 transition">Cancel</button>
                        <button type="submit" :disabled="quickPartnerLoading" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md transition disabled:opacity-50">Save Partner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
