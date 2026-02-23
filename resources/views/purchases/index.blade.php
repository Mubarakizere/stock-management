@extends('layouts.app')
@section('title', 'Purchases')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    $fmt = fn($n) => number_format((float)$n, 2);
    $channels = ['cash','bank','momo'];
@endphp

<div
    x-data="{
        pa: { open: false, id: null, supplierName: '', viewUrl: '', editUrl: '', invoiceUrl: '', deleteUrl: '', deleteLabel: '' },
        openPA(id, supplierName, viewUrl, editUrl, invoiceUrl, deleteUrl) {
            this.pa = { open: true, id, supplierName, viewUrl, editUrl, invoiceUrl, deleteUrl };
        },
        closePA() { this.pa.open = false; },
        confirmDeletePA() {
            if (!this.pa.deleteUrl) return;
            const f = document.getElementById('purchase-delete-form');
            f.action = this.pa.deleteUrl;
            f.submit();
        }
    }"
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Purchases</span>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Track supplier purchases, payments, balances and returns.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('purchases.create')
                <a href="{{ route('purchases.create') }}"
                   class="btn btn-primary flex items-center gap-1 text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New Purchase</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- FLASH / ERRORS --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @cannot('purchases.view')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to view purchases.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- FILTERS --}}
        <form method="GET" action="{{ route('purchases.index') }}"
              class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
                {{-- Supplier --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Supplier</label>
                    <select name="supplier_id"
                            class="form-select w-full text-sm">
                        <option value="">All suppliers</option>
                        @foreach(($suppliers ?? []) as $s)
                            <option value="{{ $s->id }}" @selected((string)request('supplier_id')===(string)$s->id)>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" class="form-select w-full text-sm">
                        <option value="">All</option>
                        <option value="completed" @selected(request('status')==='completed')>Completed</option>
                        <option value="partial"   @selected(request('status')==='partial')>Partial</option>
                        <option value="pending"   @selected(request('status')==='pending')>Pending</option>
                        <option value="cancelled" @selected(request('status')==='cancelled')>Cancelled</option>
                    </select>
                </div>

                {{-- Channel --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Channel</label>
                    <select name="payment_channel" class="form-select w-full text-sm">
                        <option value="">Any</option>
                        @foreach($channels as $ch)
                            <option value="{{ $ch }}" @selected(request('payment_channel')===$ch)>
                                {{ strtoupper($ch) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- From --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-input w-full text-sm">
                </div>

                {{-- To --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-input w-full text-sm">
                </div>

                {{-- Search --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Supplier, reference, notes…"
                           class="form-input w-full text-sm">
                </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-end pt-1 border-t border-dashed border-gray-200 dark:border-gray-800">
                <button class="btn btn-secondary flex items-center gap-1 text-sm">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route('purchases.index') }}"
                   class="btn btn-outline flex items-center gap-1 text-sm">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>

        @php
            // Current page collection (paginator-safe)
            $rows = $purchases instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
                ? $purchases->getCollection()
                : collect($purchases);

            // Monetary fields
            $calcTotal = function($p){
                $subtotal = (float)($p->subtotal ?? 0);
                $tax      = (float)($p->tax ?? 0);
                $discount = (float)($p->discount ?? 0);
                return $subtotal + $tax - $discount;
            };

            $pageTotal   = $rows->sum(fn($p) => $calcTotal($p));
            $pagePaid    = $rows->sum(fn($p) => (float)($p->amount_paid ?? 0));
            $pageBalance = max(0, $pageTotal - $pagePaid);

            // Per-channel aggregation
            $byChannel = collect($channels)->mapWithKeys(function($ch) use ($rows, $calcTotal){
                $filtered = $rows->filter(fn($p) => strtolower($p->payment_channel ?? 'cash') === $ch);
                $total    = $filtered->sum(fn($p) => $calcTotal($p));
                $paid     = $filtered->sum(fn($p) => (float)($p->amount_paid ?? 0));
                $balance  = max(0, $total - $paid);
                return [$ch => [
                    'count'   => $filtered->count(),
                    'total'   => $total,
                    'paid'    => $paid,
                    'balance' => $balance,
                ]];
            });

            // Status counts on page
            $statusCounts = $rows
                ->groupBy(fn($p) => strtolower($p->status ?? 'pending'))
                ->map->count();

            // Page-level returns/refunds (works with or without eager loads / withSum)
            $pageReturned = $rows->sum(function($p){
                if (isset($p->returns_value_sum)) return (float)$p->returns_value_sum;
                return (float) optional($p->returns)->sum('total_amount');
            });
            $pageRefund = $rows->sum(function($p){
                if (isset($p->returns_refund_sum)) return (float)$p->returns_refund_sum;
                return (float) optional($p->returns)->sum('refund_amount');
            });
        @endphp

        {{-- PAGE SUMMARY / TOTALS --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">Total (page)</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    RWF {{ $fmt($pageTotal) }}
                </div>
            </div>
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">Paid (page)</div>
                <div class="text-lg font-semibold text-emerald-700 dark:text-emerald-300">
                    RWF {{ $fmt($pagePaid) }}
                </div>
            </div>
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">Balance (page)</div>
                <div class="text-lg font-semibold {{ $pageBalance>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                    RWF {{ $fmt($pageBalance) }}
                </div>
            </div>
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">Status (page)</div>
                <div class="mt-1 flex flex-wrap gap-1.5 text-[11px]">
                    @foreach (['completed'=>'green','partial'=>'yellow','pending'=>'amber','cancelled'=>'gray'] as $st=>$clr)
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 bg-{{ $clr }}-100 text-{{ $clr }}-800 dark:bg-{{ $clr }}-900/40 dark:text-{{ $clr }}-300">
                            {{ ucfirst($st) }}: {{ $statusCounts[$st] ?? 0 }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- PER-CHANNEL SUMMARY --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach ($paymentChannels as $channel)
                @php
                    $slug = $channel->slug;
                    $label = $channel->name;
                    // Define colors based on slug or fallback
                    $color = match($slug) {
                        'cash' => 'green',
                        'bank' => 'blue',
                        'momo', 'mobile_money' => 'purple',
                        default => 'gray'
                    };

                    // Filter rows for this channel
                    $filtered = $rows->filter(fn($p) => strtolower($p->payment_channel ?? 'cash') === $slug);
                    $count    = $filtered->count();
                    $total    = $filtered->sum(fn($p) => $calcTotal($p));
                    $paid     = $filtered->sum(fn($p) => (float)($p->amount_paid ?? 0));
                    $balance  = max(0, $total - $paid);
                @endphp
                <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $label }} ({{ $count }})
                        </span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/40 dark:text-{{ $color }}-300">
                            {{ strtoupper($slug) }}
                        </span>
                    </div>
                    <div class="mt-1 grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $fmt($total) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Paid</div>
                            <div class="font-medium text-emerald-700 dark:text-emerald-300">
                                {{ $fmt($paid) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Balance</div>
                            <div class="font-medium {{ $balance>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                                {{ $fmt($balance) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- TABLE --}}
        <div class="rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-indigo-500"></i>
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    Results
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1250px] w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-5 py-3 text-left">#</th>
                            <th class="px-5 py-3 text-left">Date</th>
                            <th class="px-5 py-3 text-left">Supplier</th>
                            <th class="px-5 py-3 text-right">Subtotal</th>
                            <th class="px-5 py-3 text-right">Tax</th>
                            <th class="px-5 py-3 text-right">Discount</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-right">Paid</th>
                            <th class="px-5 py-3 text-right">Balance</th>
                            <th class="px-5 py-3 text-right">Returned</th>
                            <th class="px-5 py-3 text-right">Refund</th>
                            <th class="px-5 py-3 text-left">Status</th>
                            <th class="px-5 py-3 text-left">Channel</th>
                            <th class="px-5 py-3 text-left">Reference</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($purchases as $p)
                            @php
                                $pdate    = $p->purchase_date ?? $p->created_at;
                                $subtotal = (float)($p->subtotal ?? 0);
                                $tax      = (float)($p->tax ?? 0);
                                $discount = (float)($p->discount ?? 0);
                                $total    = $subtotal + $tax - $discount;
                                $paid     = (float)($p->amount_paid ?? 0);
                                $balance  = max(0, $total - $paid);
                                $status   = strtolower($p->status ?? ($balance > 0 ? 'pending' : 'completed'));
                                $channel  = strtolower($p->payment_channel ?? 'cash');
                                $ref      = $p->method ?: '—';

                                // Returns info
                                $returnsCount   = (int)($p->returns_count ?? optional($p->returns)->count() ?? 0);
                                $returnedValue  = (float)($p->returns_value_sum ?? optional($p->returns)->sum('total_amount') ?? 0);
                                $refundSum      = (float)($p->returns_refund_sum ?? optional($p->returns)->sum('refund_amount') ?? 0);
                                $hasReturns     = $returnsCount > 0;

                                $statusClass = match($status) {
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                    'partial'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                    'pending'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                    'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300',
                                    default     => 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300',
                                };
                                $chClass = [
                                    'cash' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                    'bank' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                    'momo' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                ][$channel] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300';
                            @endphp

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    #{{ $p->id }}
                                    @if($hasReturns)
                                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold
                                                    bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                                              title="{{ $returnsCount }} return(s)">
                                            {{ $returnsCount }}x
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $pdate ? \Carbon\Carbon::parse($pdate)->format('Y-m-d') : '—' }}
                                </td>

                                <td class="px-5 py-3 text-gray-900 dark:text-gray-100">
                                    {{ optional($p->supplier)->name ?? '—' }}
                                </td>

                                <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-200">
                                    {{ $fmt($subtotal) }}
                                </td>
                                <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-200">
                                    {{ $fmt($tax) }}
                                </td>
                                <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-200">
                                    - {{ $fmt($discount) }}
                                </td>

                                <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $fmt($total) }}
                                </td>
                                <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-200">
                                    {{ $fmt($paid) }}
                                </td>
                                <td class="px-5 py-3 text-right font-semibold {{ $balance>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                                    {{ $fmt($balance) }}
                                </td>

                                {{-- Returned value --}}
                                <td class="px-5 py-3 text-right {{ $hasReturns ? 'text-amber-700 dark:text-amber-300 font-medium' : 'text-gray-400 dark:text-gray-600' }}">
                                    {{ $hasReturns ? $fmt($returnedValue) : '—' }}
                                </td>

                                {{-- Refund --}}
                                <td class="px-5 py-3 text-right {{ $refundSum>0 ? 'text-emerald-700 dark:text-emerald-300 font-medium' : 'text-gray-400 dark:text-gray-600' }}">
                                    {{ $refundSum>0 ? $fmt($refundSum) : '—' }}
                                </td>

                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClass }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>

                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $chClass }}">
                                        {{ strtoupper($channel) }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $ref }}
                                </td>

                                <td class="px-5 py-3">
                                    <button
                                        type="button"
                                        @click="openPA(
                                            '{{ $p->id }}',
                                            '{{ addslashes(optional($p->supplier)->name ?? '—') }}',
                                            '{{ route('purchases.show', $p) }}',
                                            '{{ route('purchases.edit', $p) }}',
                                            '{{ Route::has('purchases.invoice') ? route('purchases.invoice', $p) : '' }}',
                                            '{{ route('purchases.destroy', $p) }}'
                                        )"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                               bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                                               hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-300
                                               transition">
                                        <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
                                        Actions
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No purchases found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- PAGE TOTALS FOOTER --}}
                    <tfoot class="bg-gray-50 dark:bg-gray-800/50 text-sm">
                        <tr>
                            <td colspan="6" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">
                                Page Totals
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ $fmt($pageTotal) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ $fmt($pagePaid) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $pageBalance>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                                {{ $fmt($pageBalance) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-amber-700 dark:text-amber-300">
                                {{ $fmt($pageReturned) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-300">
                                {{ $fmt($pageRefund) }}
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/40">
                {{ $purchases instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
                    ? $purchases->withQueryString()->links()
                    : '' }}
            </div>
        </div>

        {{-- PURCHASE ACTIONS MODAL --}}
        <div
            x-show="pa.open"
            x-cloak
            @keydown.escape.window="closePA()"
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 backdrop-blur-sm"
        >
            <div
                @click.outside="closePA()"
                x-show="pa.open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-6 sm:scale-95"
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                       rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-sm mx-0 sm:mx-4 p-5"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100" x-text="'Purchase #' + pa.id"></h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="pa.supplierName"></p>
                    </div>
                    <button @click="closePA()" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i data-lucide="x" class="w-4 h-4 text-gray-400"></i>
                    </button>
                </div>

                {{-- Action cards --}}
                <div class="grid grid-cols-2 gap-2">
                    @can('purchases.view')
                    <a :href="pa.viewUrl"
                       class="flex flex-col items-center gap-1.5 p-3 rounded-xl border border-gray-200 dark:border-gray-700
                              hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:border-indigo-200 dark:hover:border-indigo-700 transition group">
                        <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                            <i data-lucide="eye" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">View Details</span>
                    </a>
                    @endcan

                    @can('purchases.edit')
                    <a :href="pa.editUrl"
                       class="flex flex-col items-center gap-1.5 p-3 rounded-xl border border-gray-200 dark:border-gray-700
                              hover:bg-violet-50 dark:hover:bg-violet-900/20 hover:border-violet-200 dark:hover:border-violet-700 transition group">
                        <div class="p-2 rounded-lg bg-violet-100 dark:bg-violet-900/30">
                            <i data-lucide="file-edit" class="w-4 h-4 text-violet-600 dark:text-violet-400"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-violet-700 dark:group-hover:text-violet-300">Edit</span>
                    </a>
                    @endcan

                    @can('purchases.view')
                    <a :href="pa.invoiceUrl" target="_blank"
                       x-show="pa.invoiceUrl"
                       class="flex flex-col items-center gap-1.5 p-3 rounded-xl border border-gray-200 dark:border-gray-700
                              hover:bg-sky-50 dark:hover:bg-sky-900/20 hover:border-sky-200 dark:hover:border-sky-700 transition group">
                        <div class="p-2 rounded-lg bg-sky-100 dark:bg-sky-900/30">
                            <i data-lucide="file-text" class="w-4 h-4 text-sky-600 dark:text-sky-400"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-sky-700 dark:group-hover:text-sky-300">Invoice</span>
                    </a>
                    @endcan

                    @can('purchases.delete')
                    <button type="button"
                        @click="if(confirm('Delete Purchase #' + pa.id + '? Stock movements will be reversed.')) confirmDeletePA()"
                        class="flex flex-col items-center gap-1.5 p-3 rounded-xl border border-gray-200 dark:border-gray-700
                               hover:bg-rose-50 dark:hover:bg-rose-900/20 hover:border-rose-200 dark:hover:border-rose-700 transition group w-full">
                        <div class="p-2 rounded-lg bg-rose-100 dark:bg-rose-900/30">
                            <i data-lucide="trash-2" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-rose-700 dark:group-hover:text-rose-300">Delete</span>
                    </button>
                    @endcan
                </div>
            </div>
        </div>

    @endcannot
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@can('purchases.delete')
<form id="purchase-delete-form" method="POST" action="" class="hidden">
    @csrf @method('DELETE')
</form>
@endcan

@endsection
