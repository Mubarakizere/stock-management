@extends('layouts.app')
@section('title', 'Sales Orders')

@section('content')
@php
    use Carbon\Carbon;

    $paymentsPdfAction = \Illuminate\Support\Facades\Route::has('sales.payments.pdf')
        ? route('sales.payments.pdf')
        : (\Illuminate\Support\Facades\Route::has('reports.sales.payments.pdf')
            ? route('reports.sales.payments.pdf')
            : null);

    $user = auth()->user();

    $rows = $sales instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $sales->getCollection()
        : collect($sales);

    $fmt = fn($n) => number_format((float)$n, 2);
@endphp

<div
    x-data="{
        // Delete Modal State
        deleteModal: {
            open: false,
            id: null,
            customer: '',
            amount: '',
            url: ''
        },
        openDeleteModal(id, customer, amount, url) {
            this.deleteModal = { open: true, id, customer, amount, url };
        },
        closeDeleteModal() {
            this.deleteModal.open = false;
        },
        confirmDelete() {
            if (!this.deleteModal.url) return;
            const f = document.getElementById('single-delete-form');
            f.action = this.deleteModal.url;
            f.submit();
        },

        // Bulk Delete State
        selected: [],
        allIds: [],
        bulkModalOpen: false,
        get allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
        get someSelected() { return this.selected.length > 0 && !this.allSelected; },
        toggleAll() {
            if (this.allSelected) { this.selected = []; }
            else { this.selected = [...this.allIds]; }
        },
        confirmBulkDelete() {
            if (this.selected.length === 0) return;
            const f = document.getElementById('bulk-delete-form');
            f.querySelectorAll('input[name=\'ids[]\']').forEach(el => el.remove());
            this.selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                f.appendChild(input);
            });
            f.submit();
        },

        // Quick range filters
        applyRange(preset) {
            const url = new URL(window.location.href);
            const today = new Date();
            const fmtDate = d => d.toISOString().slice(0, 10);

            let from = null, to = null;
            if (preset === 'today') {
                from = fmtDate(today); to = fmtDate(today);
            } else if (preset === 'week') {
                const day = today.getDay();
                const diffToMon = (day === 0 ? -6 : 1 - day);
                const monday = new Date(today); monday.setDate(today.getDate() + diffToMon);
                const sunday = new Date(monday); sunday.setDate(monday.getDate() + 6);
                from = fmtDate(monday); to = fmtDate(sunday);
            } else if (preset === 'month') {
                const first = new Date(today.getFullYear(), today.getMonth(), 1);
                const last = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                from = fmtDate(first); to = fmtDate(last);
            } else if (preset === 'all') {
                url.searchParams.delete('from');
                url.searchParams.delete('to');
                window.location.href = url.toString();
                return;
            }
            if (from && to) {
                url.searchParams.set('from', from);
                url.searchParams.set('to', to);
            }
            window.location.href = url.toString();
        }
    }"
    x-init="allIds = Array.from(document.querySelectorAll('.sale-row-cb')).map(el => el.value)"
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
>

    {{-- =========================================================================
    | 1. Header & Actions
    |========================================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-gray-200/80 dark:border-gray-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2.5">
                <i data-lucide="shopping-bag" class="w-6 h-6 text-teal-600 dark:text-teal-400"></i>
                <span>Sales Orders & Invoices</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                Track retail point of sale receipts, multi-channel payment settlements, and customer balances.
            </p>
        </div>

        @can('sales.view')
            <div class="flex items-center flex-wrap gap-2 sm:gap-2.5">
                {{-- CSV Export --}}
                <a href="{{ route('sales.export', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 border border-gray-200 dark:border-gray-700 shadow-2xs transition-all">
                    <i data-lucide="download" class="w-4 h-4 text-gray-400"></i>
                    <span>Export CSV</span>
                </a>

                {{-- Payments PDF Modal Trigger --}}
                @if ($paymentsPdfAction)
                    <button type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 border border-gray-200 dark:border-gray-700 shadow-2xs transition-all"
                            @click="$store.paypdf.open = true">
                        <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                        <span>Payments PDF</span>
                    </button>
                @endif

                {{-- New Sale --}}
                @can('sales.create')
                    <a href="{{ route('sales.create') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 shadow-xs transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>New Sale</span>
                    </a>
                @endcan
            </div>
        @endcan
    </div>

    @cannot('sales.view')
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        Access Restricted
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        You do not possess the required permissions to view sales orders. Contact an administrator for access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- =========================================================================
        | 2. Channel Performance Summary Cards
        |========================================================================= --}}
        @if($paymentChannels->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5 sm:gap-4">
                @foreach ($paymentChannels as $channel)
                    @php
                        $slug = strtolower($channel->slug);
                        $label = $channel->name;

                        $channelFiltered = $rows->filter(fn($s) => strtolower($s->payment_channel ?? 'cash') === $slug);
                        $count   = $channelFiltered->count();
                        $chTotal = $channelFiltered->sum(fn($s) => (float)($s->total_amount ?? 0));
                        $chPaid  = $channelFiltered->sum(fn($s) => (float)($s->amount_paid ?? 0));
                        $chBal   = max(0, $chTotal - $chPaid);
                    @endphp
                    <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-4 shadow-xs transition hover:border-gray-300 dark:hover:border-gray-600 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $label }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                {{ $count }} {{ Str::plural('sale', $count) }}
                            </span>
                        </div>
                        <div class="mt-2.5">
                            <p class="text-lg font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">
                                {{ number_format($chTotal) }} <span class="text-xs font-semibold text-gray-400">RWF</span>
                            </p>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Collected: <strong class="text-teal-600 dark:text-teal-400">{{ number_format($chPaid) }}</strong></span>
                            @if($chBal > 0)
                                <span class="text-rose-600 dark:text-rose-400 font-medium">Pending: {{ number_format($chBal) }}</span>
                            @else
                                <span class="text-gray-400">Settled</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- =========================================================================
        | 3. Advanced Filter Hub
        |========================================================================= --}}
        <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl shadow-xs p-4 sm:p-5">
            <form method="GET" action="{{ route('sales.index') }}" class="space-y-4">
                
                {{-- Top Filter Row: Search & Channels & Status --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    {{-- Search Input --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            Search
                        </label>
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-2.5"></i>
                            <input
                                type="text"
                                name="search"
                                placeholder="Invoice #, customer name, ref..."
                                value="{{ request('search') }}"
                                class="w-full pl-9 pr-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                        </div>
                    </div>

                    {{-- Channel Select --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            Payment Channel
                        </label>
                        <select name="channel"
                                class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                            <option value="">All Channels</option>
                            @foreach($paymentChannels as $ch)
                                <option value="{{ $ch->slug }}" {{ request('channel') === $ch->slug ? 'selected' : '' }}>
                                    {{ $ch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Select --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            Settlement Status
                        </label>
                        <select name="status"
                                class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                            <option value="">All Statuses</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed (Paid)</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending (Balance Due)</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    {{-- Sort Order --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            Sort By
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <select name="sort" class="w-full px-2.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                                @php $sort = request('sort', 'sale_date'); @endphp
                                <option value="sale_date" {{ $sort === 'sale_date' ? 'selected' : '' }}>Date</option>
                                <option value="total_amount" {{ $sort === 'total_amount' ? 'selected' : '' }}>Total</option>
                                <option value="amount_paid" {{ $sort === 'amount_paid' ? 'selected' : '' }}>Paid</option>
                                <option value="id" {{ $sort === 'id' ? 'selected' : '' }}>ID</option>
                            </select>
                            <select name="dir" class="w-full px-2.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs sm:text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                                @php $dir = request('dir', 'desc'); @endphp
                                <option value="desc" {{ $dir === 'desc' ? 'selected' : '' }}>Desc</option>
                                <option value="asc" {{ $dir === 'asc' ? 'selected' : '' }}>Asc</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Bottom Filter Row: Dates & Preset Buttons & Submit --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                    {{-- Date pickers --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span>From:</span>
                            <input type="date" name="from" value="{{ request('from') }}"
                                   class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span>To:</span>
                            <input type="date" name="to" value="{{ request('to') }}"
                                   class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 dark:text-white text-xs focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                        </div>

                        {{-- Quick Range Pills --}}
                        <div class="flex items-center gap-1 pl-1">
                            <button type="button" class="px-2 py-1 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition" @click="applyRange('today')">Today</button>
                            <button type="button" class="px-2 py-1 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition" @click="applyRange('week')">This Week</button>
                            <button type="button" class="px-2 py-1 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition" @click="applyRange('month')">This Month</button>
                            <button type="button" class="px-2 py-1 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition" @click="applyRange('all')">Clear</button>
                        </div>
                    </div>

                    {{-- Actions: Filter & Reset & Returns Checkbox --}}
                    <div class="flex items-center gap-2.5 ml-auto">
                        <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 cursor-pointer mr-2">
                            <input type="checkbox" name="has_returns" value="1" {{ request('has_returns') ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500">
                            <span>With Returns</span>
                        </label>

                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 transition">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>Apply</span>
                        </button>
                        <a href="{{ route('sales.index') }}"
                           class="px-3 py-1.5 rounded-xl text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            Reset
                        </a>
                    </div>
                </div>

            </form>
        </div>

        {{-- =========================================================================
        | 4. Sales Orders Table
        |========================================================================= --}}
        <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left min-w-[980px]">
                    <thead class="bg-gray-50/80 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[10px] font-semibold">
                        <tr>
                            @can('sales.delete')
                                <th class="px-4 py-3.5 w-10">
                                    <input type="checkbox"
                                           :checked="allSelected"
                                           :indeterminate.prop="someSelected"
                                           @change="toggleAll()"
                                           class="rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500">
                                </th>
                            @endcan
                            <th class="px-4 py-3.5">Invoice #</th>
                            <th class="px-4 py-3.5">Date</th>
                            <th class="px-4 py-3.5">Customer</th>
                            <th class="px-4 py-3.5 text-right">Gross Total</th>
                            <th class="px-4 py-3.5 text-right">Returns</th>
                            <th class="px-4 py-3.5 text-right">Net Amount</th>
                            <th class="px-4 py-3.5 text-right">Paid</th>
                            <th class="px-4 py-3.5 text-right">Balance Due</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5">Channel</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @forelse ($sales as $sale)
                            @php
                                $date = $sale->sale_date ? Carbon::parse($sale->sale_date) : ($sale->created_at ?? now());
                                $channel = strtolower($sale->payment_channel ?? 'cash');

                                $returnsTotal = (float) ($sale->returns_total ?? $sale->returns()->sum('amount'));
                                $grossTotal   = (float) ($sale->total_amount ?? 0);
                                $netAfter     = max(0, $grossTotal - $returnsTotal);
                                $paid         = (float) ($sale->amount_paid ?? 0);
                                $balance      = max(0, round($netAfter - $paid, 2));

                                $channelColor = match($channel) {
                                    'bank' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/60',
                                    'momo', 'mobile_money' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border border-purple-200/60 dark:border-purple-800/60',
                                    default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60',
                                };
                            @endphp

                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition-colors">
                                @can('sales.delete')
                                    <td class="px-4 py-3.5">
                                        <input type="checkbox"
                                               class="sale-row-cb rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500"
                                               value="{{ $sale->id }}"
                                               x-model="selected">
                                    </td>
                                @endcan

                                {{-- Invoice ID --}}
                                <td class="px-4 py-3.5">
                                    <a href="{{ route('sales.show', $sale) }}" class="font-bold text-gray-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
                                        #{{ $sale->id }}
                                    </a>
                                </td>

                                {{-- Date --}}
                                <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300">
                                    {{ $date->format('M d, Y') }}
                                </td>

                                {{-- Customer --}}
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        @if($sale->customer)
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $sale->customer->name }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Walk-in</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Gross Total --}}
                                <td class="px-4 py-3.5 text-right font-medium text-gray-700 dark:text-gray-300 tabular-nums">
                                    {{ number_format($grossTotal) }}
                                </td>

                                {{-- Returns --}}
                                <td class="px-4 py-3.5 text-right font-medium tabular-nums {{ $returnsTotal > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400' }}">
                                    {{ $returnsTotal > 0 ? '−' . number_format($returnsTotal) : '—' }}
                                </td>

                                {{-- Net Amount --}}
                                <td class="px-4 py-3.5 text-right font-bold text-gray-900 dark:text-white tabular-nums">
                                    {{ number_format($netAfter) }}
                                </td>

                                {{-- Paid --}}
                                <td class="px-4 py-3.5 text-right text-gray-700 dark:text-gray-300 tabular-nums">
                                    {{ number_format($paid) }}
                                </td>

                                {{-- Balance Due --}}
                                <td class="px-4 py-3.5 text-right font-bold tabular-nums {{ $balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $balance > 0 ? number_format($balance) : '0' }}
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3.5 text-center">
                                    @if ($sale->status === 'completed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60">
                                            Completed
                                        </span>
                                    @elseif ($sale->status === 'pending')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/60">
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            Cancelled
                                        </span>
                                    @endif

                                    @if ($returnsTotal > 0)
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-semibold uppercase tracking-wider bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                                            Returned
                                        </span>
                                    @endif
                                </td>

                                {{-- Channel --}}
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider {{ $channelColor }}">
                                        {{ strtoupper($channel) }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        {{-- View --}}
                                        <a href="{{ route('sales.show', $sale) }}"
                                           class="p-1.5 rounded-lg text-gray-500 hover:text-teal-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                           title="View Details">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>

                                        {{-- Invoice --}}
                                        <a href="{{ route('sales.invoice', $sale) }}" target="_blank"
                                           class="p-1.5 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                           title="Print Invoice">
                                            <i data-lucide="file-text" class="w-4 h-4"></i>
                                        </a>

                                        {{-- Edit --}}
                                        @can('sales.edit')
                                            <a href="{{ route('sales.edit', $sale) }}"
                                               class="p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                               title="Edit Sale">
                                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        {{-- Delete Button (Triggers Modal!) --}}
                                        @can('sales.delete')
                                            <button type="button"
                                                    @click="openDeleteModal('{{ $sale->id }}', '{{ addslashes($sale->customer->name ?? 'Walk-in Customer') }}', '{{ number_format($netAfter) }} RWF', '{{ route('sales.destroy', $sale) }}')"
                                                    class="p-1.5 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition"
                                                    title="Delete Sale">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <i data-lucide="inbox" class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2"></i>
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">No sales orders found.</p>
                                        <p class="text-xs text-gray-400 mt-1">Try adjusting your filters or record a new sale.</p>
                                        @can('sales.create')
                                            <a href="{{ route('sales.create') }}" class="mt-4 px-3.5 py-1.5 rounded-xl bg-teal-600 text-white font-medium text-xs hover:bg-teal-700 transition">
                                                Create First Sale
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Strip --}}
            @if ($sales->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-700/20">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>

        {{-- =========================================================================
        | 5. Single Sale Delete Modal (High-End Confirmation)
        |========================================================================= --}}
        @can('sales.delete')
            <div x-show="deleteModal.open"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                 @keydown.escape.window="closeDeleteModal()"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                
                <div @click.outside="closeDeleteModal()"
                     class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl w-full max-w-md p-6 overflow-hidden"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200/60 dark:border-rose-800/60 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                Delete Sale Order #<span x-text="deleteModal.id"></span>?
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Customer: <strong class="text-gray-800 dark:text-gray-200" x-text="deleteModal.customer"></strong> • Amount: <strong class="text-gray-800 dark:text-gray-200" x-text="deleteModal.amount"></strong>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/40 text-xs text-rose-800 dark:text-rose-300">
                        <p class="font-medium">⚠️ Critical Warning:</p>
                        <p class="mt-0.5 text-[11px] text-rose-700 dark:text-rose-300">
                            Deleting this sale order will permanently reverse all product inventory stock movements and erase all recorded payment receipts.
                        </p>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2.5">
                        <button type="button"
                                @click="closeDeleteModal()"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Cancel
                        </button>
                        <button type="button"
                                @click="confirmDelete()"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 shadow-xs transition">
                            Yes, Delete Sale
                        </button>
                    </div>
                </div>
            </div>

            {{-- Hidden Single Delete Form --}}
            <form id="single-delete-form" method="POST" action="" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endcan

        {{-- =========================================================================
        | 6. Bulk Selection Toolbar & Modal
        |========================================================================= --}}
        @can('sales.delete')
            <div x-show="selected.length > 0"
                 x-cloak
                 class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center gap-3 px-5 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl">
                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                    <span x-text="selected.length"></span> sales selected
                </span>
                <button type="button"
                        @click="selected = []"
                        class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 underline">
                    Clear
                </button>
                <button type="button"
                        @click="bulkModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs transition">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Delete Selected</span>
                </button>
            </div>

            {{-- Bulk Delete Confirmation Modal --}}
            <div x-show="bulkModalOpen"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                 @keydown.escape.window="bulkModalOpen = false">
                <div @click.outside="bulkModalOpen = false"
                     class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl w-full max-w-md p-6">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Bulk Delete <span x-text="selected.length"></span> Sales Orders?
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Are you sure you want to delete all <span class="font-bold text-gray-800 dark:text-gray-200" x-text="selected.length"></span> selected sales? All corresponding inventory stock quantities will be restored.
                    </p>
                    <div class="mt-6 flex justify-end gap-2.5">
                        <button type="button"
                                @click="bulkModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 transition">
                            Cancel
                        </button>
                        <button type="button"
                                @click="confirmBulkDelete()"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 shadow-xs transition">
                            Yes, Delete All
                        </button>
                    </div>
                </div>
            </div>

            {{-- Bulk Delete Form --}}
            <form id="bulk-delete-form" method="POST" action="{{ route('sales.bulk-delete') }}" class="hidden">
                @csrf
            </form>
        @endcan

    @endcannot

</div>

{{-- =========================================================================
| 7. Payments PDF Modal
|========================================================================= --}}
@if ($paymentsPdfAction)
    @can('sales.view')
        <div x-data
             x-show="$store.paypdf.open"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
             @keydown.escape.window="$store.paypdf.open = false"
             x-transition>
            <div @click.outside="$store.paypdf.open = false"
                 class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-6 w-full max-w-md">

                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Export Payments Report (PDF)
                    </h3>
                    <button @click="$store.paypdf.open = false" class="p-1 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Download an itemized PDF breakdown of payments by channel.
                </p>

                <form method="GET" action="{{ $paymentsPdfAction }}" class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Report Window</label>
                        <div class="grid grid-cols-2 gap-2 text-xs mt-1.5">
                            <label class="p-2 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center gap-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <input type="radio" name="period" value="daily" class="text-teal-600 focus:ring-teal-500" checked>
                                <span>Daily (Today)</span>
                            </label>
                            <label class="p-2 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center gap-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <input type="radio" name="period" value="weekly" class="text-teal-600 focus:ring-teal-500">
                                <span>Weekly</span>
                            </label>
                            <label class="p-2 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center gap-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <input type="radio" name="period" value="monthly" class="text-teal-600 focus:ring-teal-500">
                                <span>Monthly</span>
                            </label>
                            <label class="p-2 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center gap-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <input type="radio" name="period" value="custom" class="text-teal-600 focus:ring-teal-500"
                                       x-model="$store.paypdf.period"
                                       @change="$store.paypdf.period='custom'">
                                <span>Custom</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5" x-show="$store.paypdf.period === 'custom'">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">From Date</label>
                            <input type="date" name="from"
                                   x-model="$store.paypdf.from"
                                   class="w-full px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">To Date</label>
                            <input type="date" name="to"
                                   x-model="$store.paypdf.to"
                                   class="w-full px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-3.5 py-2 rounded-xl text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100" @click="$store.paypdf.open = false">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-teal-600 hover:bg-teal-700 shadow-xs flex items-center gap-1.5">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            <span>Download PDF</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endif

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('paypdf', {
            open: false,
            period: 'daily',
            from: '{{ request('from') ?? now()->toDateString() }}',
            to:   '{{ request('to')   ?? now()->toDateString() }}'
        });
    });
</script>
@endpush
@endsection
