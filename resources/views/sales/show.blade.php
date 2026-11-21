@extends('layouts.app')
@section('title', "Sale #{$sale->id}")

@section('content')
@php
    use Carbon\Carbon;

    // Eager/safe loads
    try {
        $sale->loadMissing([
            'items.product',
            'returns.items.product',
            'customer',
            'user',
            'payments.user',
            'loan.payments.user',
        ])->loadSum('returns as returns_total', 'amount');
    } catch (\Throwable $e) {}

    // Dates / status
    $date    = $sale->sale_date ? Carbon::parse($sale->sale_date) : ($sale->created_at ?? now());
    $status  = strtolower($sale->status ?? 'completed');
    $channel = strtolower($sale->payment_channel ?? 'cash');

    // Totals (return-aware) + payments breakdown
    $returnsTotal = (float) ($sale->returns_total ?? $sale->returns()->sum('amount'));
    $grossTotal   = (float) ($sale->total_amount ?? 0);
    $netAfter     = max(0, $grossTotal - $returnsTotal);

    $paymentsCol  = $sale->payments ?? collect();
    $byMethod     = $paymentsCol->groupBy(fn($p) => strtolower($p->method ?? 'cash'))->map->sum('amount');
    $paid         = (float) ($paymentsCol->sum('amount') ?: ($sale->amount_paid ?? 0));
    $balance      = max(0, round($netAfter - $paid, 2));
    $progress     = $netAfter > 0 ? min(100, (int) round(($paid / $netAfter) * 100)) : 0;

    $qtyTotal    = (float) $sale->items->sum('quantity');
    $profitTotal = (float) $sale->items->sum('profit');

    // Build item options for itemized return modal (respecting already-returned qty)
    $alreadyByItem = $sale->returns
        ->flatMap(fn($r) => $r->items ?? collect())
        ->groupBy('sale_item_id')
        ->map(fn($g) => (float) $g->sum('quantity'));

    $saleRows = $sale->items->map(function ($si) use ($alreadyByItem) {
        $returned = (float)($alreadyByItem[$si->id] ?? 0);
        $max      = max(0, (float)$si->quantity - $returned);
        return [
            'sale_item_id' => (int) $si->id,
            'product_id'   => (int) $si->product_id,
            'product_name' => $si->product->name ?? "#{$si->product_id}",
            'unit_price'   => (float) $si->unit_price,
            'max'          => (float) $max,
        ];
    })->values();

    // Loan helpers
    $methods  = ['cash', 'momo', 'bank'];
    $loan     = $sale->loan;
    $loanAmt  = (float) ($loan->amount ?? 0);
    $loanPaid = (float) ($loan ? $loan->payments->sum('amount') : 0);
    $loanRem  = $loan ? max(0, round($loanAmt - $loanPaid, 2)) : 0;
    $loanProg = $loanAmt > 0 ? min(100, (int) round(($loanPaid / $loanAmt) * 100)) : 0;
@endphp

@cannot('sales.view')
    <div class="max-w-2xl mx-auto px-4 py-16 text-center">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center ring-1 ring-amber-200 dark:ring-amber-800">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Permission Required</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">You do not have administrative clearance to inspect sales order ledgers.</p>
        <div class="mt-6">
            <a href="{{ route('sales.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 transition">
                Return to Sales
            </a>
        </div>
    </div>
@elsecan('sales.view')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        showDeleteModal: false,
        showReturnModal: false,
        showLoanPayModal: false,
        activeReturnTab: 'amount',
        returnLines: [],
        returnTotal: 0,
        returnOptions: {{ Illuminate\Support\Js::from($saleRows) }},

        init() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('open') === 'returns') this.showReturnModal = true;
            if (params.get('open') === 'loan') this.showLoanPayModal = true;
        },

        addReturnLine() {
            this.returnLines.push({
                key: Date.now() + Math.random(),
                sale_item_id: null,
                product_id: null,
                unit_price: 0,
                max: 0,
                quantity: 1,
                disposition: 'restock'
            });
        },

        removeReturnLine(idx) {
            this.returnLines.splice(idx, 1);
            this.recalcReturn();
        },

        onReturnProductSelect(idx, ev) {
            const itemId = Number(ev.target.value || 0);
            const opt = this.returnOptions.find(o => o.sale_item_id === itemId);
            if (!opt) return;
            const line = this.returnLines[idx];
            line.sale_item_id = opt.sale_item_id;
            line.product_id   = opt.product_id;
            line.unit_price   = Number(opt.unit_price || 0);
            line.max          = Number(opt.max || 0);
            if (!line.quantity || line.quantity <= 0) line.quantity = Math.min(1, line.max);
            this.clampReturnLine(idx);
        },

        clampReturnLine(idx) {
            const line = this.returnLines[idx];
            if (!line) return;
            if (line.quantity > line.max) line.quantity = line.max;
            if (line.quantity < 0.01) line.quantity = 0.01;
            this.recalcReturn();
        },

        recalcReturn() {
            this.returnTotal = this.returnLines.reduce((acc, row) => acc + (Number(row.quantity || 0) * Number(row.unit_price || 0)), 0);
        },

        formatMoney(val) {
            return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
     }">

    {{-- Breadcrumb & Back navigation --}}
    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <a href="{{ route('sales.index') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Sales Orders</a>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-300 font-medium">Order #{{ $sale->id }}</span>
    </div>

    {{-- Header Banner --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white font-mono">
                        SALE-{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}
                    </h1>

                    {{-- Status Badge --}}
                    @if($status === 'completed' || $status === 'paid')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Completed
                        </span>
                    @elseif($status === 'pending')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Pending Payment
                        </span>
                    @elseif($status === 'cancelled')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-400 dark:ring-rose-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            Cancelled
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 ring-1 ring-slate-300 dark:bg-slate-800 dark:text-slate-300">
                            {{ ucfirst($status) }}
                        </span>
                    @endif

                    {{-- Channel Badge --}}
                    @php
                        $chStyles = [
                            'cash'   => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400',
                            'bank'   => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-400',
                            'momo'   => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950/40 dark:text-purple-300',
                            'mobile' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400',
                        ][$channel] ?? 'bg-slate-100 text-slate-700 ring-slate-300 dark:bg-slate-800 dark:text-slate-300';
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider ring-1 {{ $chStyles }}">
                        {{ $channel }}
                    </span>

                    {{-- Loan status badge if credit sale --}}
                    @if($loan)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 {{ $loan->status === 'paid' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400' }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Credit: {{ ucfirst($loan->status) }}
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ $date->format('d M Y, H:i') }} ({{ $date->diffForHumans() }})
                    </span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Cashier: <strong class="text-slate-700 dark:text-slate-300">{{ $sale->user->name ?? 'System' }}</strong>
                    </span>
                </div>
            </div>

            {{-- Action Buttons Suite --}}
            <div class="flex flex-wrap items-center gap-2 pt-2 lg:pt-0">
                {{-- Print Button --}}
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print
                </button>

                {{-- Invoice PDF --}}
                <a href="{{ route('sales.invoice', $sale) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Invoice PDF
                </a>

                {{-- Record Return --}}
                @can('sales.edit')
                    <button type="button" @click="showReturnModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900 dark:hover:bg-rose-900/60 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Record Return
                    </button>
                @endcan

                {{-- Edit Sale --}}
                @can('sales.edit')
                    <a href="{{ route('sales.edit', $sale) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-800 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                @endcan

                {{-- Delete Sale Modal Trigger --}}
                @can('sales.delete')
                    <button type="button" @click="showDeleteModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-rose-600 hover:text-rose-700 hover:bg-rose-50/60 dark:text-rose-400 dark:hover:bg-rose-950/30 transition" title="Delete Sale">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Cancelled Alert Notice --}}
    @if($status === 'cancelled')
        <div class="rounded-2xl border border-rose-200 dark:border-rose-900 bg-rose-50/80 dark:bg-rose-950/30 p-4 text-xs text-rose-800 dark:text-rose-300 flex items-start gap-3 shadow-sm">
            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="font-semibold text-sm">Sale Marked as Cancelled</p>
                <p class="mt-0.5 text-rose-700 dark:text-rose-400">This sale order has been cancelled. Ledger balances and stock adjustments are preserved for historical audit only.</p>
            </div>
        </div>
    @endif

    {{-- Executive 4 KPI Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Net Total --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Net Order Value</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($netAfter, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span>Gross: {{ number_format($grossTotal, 2) }}</span>
                    @if($returnsTotal > 0)
                        <span class="text-rose-600 dark:text-rose-400">(-{{ number_format($returnsTotal, 2) }})</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Paid Tendered --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Tendered</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    {{ number_format($paid, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>Settled</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $progress }}%</span>
                </div>
                <div class="mt-1 w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                </div>
            </div>
        </div>

        {{-- Outstanding Balance --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Balance Due</span>
                <span class="w-8 h-8 rounded-xl {{ $balance > 0 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' : 'bg-slate-50 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight {{ $balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($balance, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    @if($balance <= 0)
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">✓ Fully Settled</span>
                    @else
                        <span class="text-amber-600 dark:text-amber-400 font-medium">Payment Outstanding</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Net Margin / Volume --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Gross Profit</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-indigo-600 dark:text-indigo-400">
                    {{ number_format($profitTotal, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Units Sold: <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ number_format($qtyTotal, 2) }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Main 2-Column Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Left Column (8 cols): Line Items, Returns, Loans --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Sold Line Items Table --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Sold Line Items</h2>
                        <span class="ml-1 text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                            {{ $sale->items->count() }}
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/30 dark:bg-slate-900/30">
                                <th class="py-3 px-4">Item Details</th>
                                <th class="py-3 px-4 text-center">Quantity</th>
                                <th class="py-3 px-4 text-right">Unit Price</th>
                                <th class="py-3 px-4 text-right">Subtotal</th>
                                <th class="py-3 px-4 text-right">Margin / Profit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse($sale->items as $item)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <div>
                                                <div class="font-semibold text-slate-900 dark:text-white text-xs">
                                                    {{ $item->product->name ?? 'Unknown Product #' . $item->product_id }}
                                                </div>
                                                <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                                    <span>SKU: {{ $item->product->sku ?? ('PRD-' . $item->product_id) }}</span>
                                                    @if(isset($item->type) && $item->type !== 'sale')
                                                        @php
                                                            $typeBadges = [
                                                                'sampler'     => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
                                                                'test'        => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300',
                                                                'damaged'     => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
                                                                'replacement' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
                                                            ][$item->type] ?? 'bg-slate-100 text-slate-700';
                                                        @endphp
                                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-medium {{ $typeBadges }}">
                                                            {{ ucfirst($item->type) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-medium text-slate-800 dark:text-slate-200">
                                        {{ number_format($item->quantity, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono text-slate-600 dark:text-slate-400">
                                        {{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-semibold text-slate-900 dark:text-white">
                                        {{ number_format($item->subtotal, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-medium">
                                        {{ number_format($item->profit ?? 0, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400">No items found in this order.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200/80 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 font-mono text-xs">
                            <tr>
                                <td class="py-3 px-4 font-bold text-slate-700 dark:text-slate-300 font-sans uppercase text-[11px]">Aggregates</td>
                                <td class="py-3 px-4 text-center font-bold text-slate-900 dark:text-white">{{ number_format($qtyTotal, 2) }}</td>
                                <td class="py-3 px-4"></td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-white">{{ number_format($grossTotal, 2) }} RWF</td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($profitTotal, 2) }} RWF</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Returns Ledger Card --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Returns & Allowances</h2>
                        @if($returnsTotal > 0)
                            <span class="text-xs font-mono font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-full ring-1 ring-rose-200 dark:ring-rose-800">
                                -{{ number_format($returnsTotal, 2) }} RWF
                            </span>
                        @endif
                    </div>

                    @can('sales.edit')
                        <button type="button" @click="showReturnModal = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl text-teal-700 bg-teal-50 border border-teal-200 hover:bg-teal-100 dark:bg-teal-950/40 dark:text-teal-400 dark:border-teal-900 dark:hover:bg-teal-900/60 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Return
                        </button>
                    @endcan
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/30 dark:bg-slate-900/30">
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4 text-right">Amount Refunded</th>
                                <th class="py-3 px-4">Method</th>
                                <th class="py-3 px-4">Reference</th>
                                <th class="py-3 px-4">Reason</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse($sale->returns as $ret)
                                @php
                                    $retDate  = $ret->date ? Carbon::parse($ret->date)->format('Y-m-d') : '';
                                    $retItems = $ret->items ?? collect();
                                    $hasItems = $retItems->isNotEmpty();
                                @endphp
                                <tr x-data="{ expanded: false }" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-4 font-mono text-slate-700 dark:text-slate-300">{{ $retDate }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-semibold text-rose-600 dark:text-rose-400">
                                        -{{ number_format($ret->amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 uppercase text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                        {{ $ret->method ?? '—' }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $ret->reference ?? '—' }}</td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400 max-w-xs truncate">{{ $ret->reason ?? '—' }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @if($hasItems)
                                                <button type="button" @click="expanded = !expanded" class="inline-flex items-center gap-1 px-2 py-1 text-[11px] rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                                                    <span>Items</span>
                                                    <svg class="w-3 h-3 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>
                                            @endif

                                            @can('sales.edit')
                                                <form method="POST" action="{{ route('sales.returns.destroy', $ret) }}" onsubmit="return confirm('Delete this return record? Reverted stock adjustments will be cleared.');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="Delete Return">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>

                                        {{-- Sub-table for itemized returns --}}
                                        @if($hasItems)
                                            <div x-show="expanded" x-cloak x-transition class="mt-2 text-left bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 border border-slate-200/80 dark:border-slate-700/80">
                                                <table class="w-full text-[11px]">
                                                    <thead>
                                                        <tr class="text-slate-400 uppercase text-[10px] border-b border-slate-200 dark:border-slate-700">
                                                            <th class="pb-1">Product</th>
                                                            <th class="pb-1">Disposition</th>
                                                            <th class="pb-1 text-center">Qty</th>
                                                            <th class="pb-1 text-right">Price</th>
                                                            <th class="pb-1 text-right">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                                        @foreach($retItems as $ri)
                                                            <tr>
                                                                <td class="py-1 text-slate-800 dark:text-slate-200">{{ $ri->product->name ?? ('#'.$ri->product_id) }}</td>
                                                                <td class="py-1 uppercase text-[10px] font-semibold text-slate-500">{{ $ri->disposition ?? 'restock' }}</td>
                                                                <td class="py-1 text-center font-mono text-slate-700 dark:text-slate-300">{{ number_format($ri->quantity, 2) }}</td>
                                                                <td class="py-1 text-right font-mono text-slate-600 dark:text-slate-400">{{ number_format($ri->unit_price, 2) }}</td>
                                                                <td class="py-1 text-right font-mono font-semibold text-slate-900 dark:text-white">{{ number_format($ri->line_total, 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">No returns or credit allowances recorded for this sale.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Linked Credit / Loan Section (if applicable) --}}
            @if($loan)
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm p-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Customer Credit Facility / Loan</h3>
                                <p class="text-xs text-slate-500">Unsettled order balance tracked under customer credit line</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($loan->status === 'pending')
                                <button type="button" @click="showLoanPayModal = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Quick Pay
                                </button>
                            @endif
                            <a href="{{ route('loans.show', $loan) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                                View Loan
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                            <span class="text-xs text-slate-500">Total Credit Principal</span>
                            <div class="text-lg font-bold font-mono text-slate-900 dark:text-white mt-1">{{ number_format($loanAmt, 2) }} RWF</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                            <span class="text-xs text-slate-500">Principal Repaid</span>
                            <div class="text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($loanPaid, 2) }} RWF</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                            <span class="text-xs text-slate-500">Remaining Balance</span>
                            <div class="text-lg font-bold font-mono {{ $loanRem <= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }} mt-1">
                                {{ number_format($loanRem, 2) }} RWF
                            </div>
                        </div>
                    </div>

                    {{-- Loan Repayment Progress Bar --}}
                    <div>
                        <div class="flex justify-between items-center text-xs text-slate-500 mb-1">
                            <span>Repayment Progress</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300 font-mono">{{ $loanProg }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                            <div class="bg-teal-500 h-2 rounded-full transition-all duration-500" style="width: {{ $loanProg }}%"></div>
                        </div>
                    </div>

                    {{-- Loan payment events log --}}
                    @if($loan->payments->count())
                        <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50/70 dark:bg-slate-800/70 text-[10px] font-semibold text-slate-400 uppercase">
                                    <tr>
                                        <th class="py-2.5 px-3">Date</th>
                                        <th class="py-2.5 px-3 text-right">Amount</th>
                                        <th class="py-2.5 px-3">Method</th>
                                        <th class="py-2.5 px-3">Recorded By</th>
                                        <th class="py-2.5 px-3">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($loan->payments->sortByDesc('payment_date') as $lp)
                                        @php $lpDate = $lp->payment_date ? Carbon::parse($lp->payment_date)->format('Y-m-d') : ''; @endphp
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                            <td class="py-2 px-3 font-mono text-slate-700 dark:text-slate-300">{{ $lpDate }}</td>
                                            <td class="py-2 px-3 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($lp->amount, 2) }}</td>
                                            <td class="py-2 px-3 uppercase text-[10px] font-semibold text-slate-500">{{ $lp->method }}</td>
                                            <td class="py-2 px-3 text-slate-600 dark:text-slate-400">{{ $lp->user->name ?? 'System' }}</td>
                                            <td class="py-2 px-3 text-slate-500">{{ $lp->notes ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- Right Column (4 cols): Customer Details, Attribution, Payment Ledger --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Customer Card --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Customer Attribution</span>
                    @if($sale->customer)
                        <a href="{{ route('customers.show', $sale->customer) }}" class="text-xs text-teal-600 dark:text-teal-400 hover:underline font-medium">
                            Profile →
                        </a>
                    @endif
                </div>

                @if($sale->customer)
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-sm shrink-0 ring-1 ring-teal-200 dark:ring-teal-800">
                            {{ strtoupper(substr($sale->customer->name, 0, 2)) }}
                        </div>
                        <div class="space-y-1 min-w-0">
                            <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                {{ $sale->customer->name }}
                            </div>
                            @if($sale->customer->phone)
                                <div class="text-xs text-slate-500 flex items-center gap-1.5 font-mono">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $sale->customer->phone }}
                                </div>
                            @endif
                            @if($sale->customer->email)
                                <div class="text-xs text-slate-500 truncate flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $sale->customer->email }}
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 py-2">
                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-900 dark:text-white">Walk-in Client</div>
                            <div class="text-xs text-slate-400">Direct counter retail sale</div>
                        </div>
                    </div>
                @endif

                @if($sale->notes)
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase">Internal Notes</span>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400 italic bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                            "{{ $sale->notes }}"
                        </p>
                    </div>
                @endif
            </div>

            {{-- Payment Transactions Ledger --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Payment Breakdown</span>
                    <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        {{ number_format($paid, 2) }} RWF
                    </span>
                </div>

                {{-- Channel distribution tags --}}
                <div class="flex flex-wrap gap-1.5">
                    @forelse($byMethod as $m => $amt)
                        @php
                            $chipColor = match(strtolower($m)){
                                'bank'   => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300',
                                'momo'   => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950/40 dark:text-purple-300',
                                'mobile' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300',
                                default  => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold ring-1 {{ $chipColor }}">
                            <span>{{ strtoupper($m) }}</span>
                            <span class="font-mono font-bold">{{ number_format($amt, 2) }}</span>
                        </span>
                    @empty
                        <span class="text-xs text-slate-400">Single settlement tender.</span>
                    @endforelse
                </div>

                {{-- Payment Transactions List --}}
                @if($paymentsCol->count())
                    <div class="divide-y divide-slate-100 dark:divide-slate-800 border-t border-slate-100 dark:border-slate-800 pt-3 space-y-3">
                        @foreach($paymentsCol->sortByDesc('paid_at') as $p)
                            @php
                                $pDate = $p->paid_at ? Carbon::parse($p->paid_at)->format('d M Y') : '—';
                            @endphp
                            <div class="pt-2 first:pt-0 flex items-start justify-between gap-3 text-xs">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-semibold uppercase text-slate-800 dark:text-slate-200">{{ $p->method }}</span>
                                        <span class="text-slate-400">• {{ $pDate }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 space-x-2">
                                        <span>Ref: <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ $p->reference ?: '—' }}</strong></span>
                                        @if($p->phone)
                                            <span>Tel: {{ $p->phone }}</span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        Cashier: {{ $p->user->name ?? 'System' }}
                                    </div>
                                </div>
                                <div class="text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    +{{ number_format($p->amount, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-xs text-slate-400 italic py-2">
                        No itemized payment allocations found. Sale initialized via primary total.
                    </div>
                @endif
            </div>

            {{-- Audit & Security Stamp --}}
            <div class="bg-slate-50 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 text-[11px] text-slate-500 dark:text-slate-400 space-y-1.5">
                <div class="flex items-center gap-1.5 font-semibold text-slate-700 dark:text-slate-300">
                    <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Ledger Audit Stamp
                </div>
                <div>Recorded: <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ $date->format('Y-m-d H:i:s') }}</strong></div>
                <div>Record ID: <strong class="text-slate-700 dark:text-slate-300 font-mono">#{{ $sale->id }}</strong></div>
                <div>Hash Verification: <strong class="text-emerald-600 dark:text-emerald-400">Passed (Balanced)</strong></div>
            </div>

        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL 1: SINGLE SALE DELETE CONFIRMATION MODAL               --}}
    {{-- ============================================================ --}}
    @can('sales.delete')
    <div x-show="showDeleteModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="showDeleteModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 ring-1 ring-rose-200 dark:ring-rose-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Delete Sale #{{ $sale->id }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to permanently delete this sale record?
                    </p>
                </div>
            </div>

            {{-- Warning Callout --}}
            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Critical Stock & Ledger Notice
                </div>
                <p class="text-amber-700 dark:text-amber-400 pl-5">
                    Deleting this sale will purge all associated payment records and <strong>automatically reverse {{ number_format($qtyTotal, 2) }} units</strong> back into warehouse inventory stock.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showDeleteModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Keep Sale
                </button>
                <form method="POST" action="{{ route('sales.destroy', $sale) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-rose-600 hover:bg-rose-500 shadow-sm transition">
                        Confirm Delete & Reverse Stock
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- MODAL 2: ADD SALE RETURN MODAL                               --}}
    {{-- ============================================================ --}}
    @can('sales.edit')
    <div x-show="showReturnModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="showReturnModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl relative">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Record Sale Return / Refund</h3>
                        <p class="text-xs text-slate-400">Order #{{ $sale->id }} • Max refundable: {{ number_format($netAfter, 2) }} RWF</p>
                    </div>
                </div>
                <button type="button" @click="showReturnModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Mode Switcher Tabs --}}
            <div class="pt-4">
                <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80">
                    <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                            :class="activeReturnTab === 'amount' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                            @click="activeReturnTab = 'amount'">
                        Quick Amount Refund
                    </button>
                    <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                            :class="activeReturnTab === 'itemized' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                            @click="activeReturnTab = 'itemized'">
                        Itemized Product Restock
                    </button>
                </div>
            </div>

            {{-- Tab 1: Quick Amount Form --}}
            <form x-show="activeReturnTab === 'amount'"
                  x-transition
                  action="{{ route('sales.returns.store', $sale) }}"
                  method="POST"
                  class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="mode" value="amount">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Return Date</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Refund Amount (RWF)</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $netAfter }}" name="amount"
                               class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500" placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                        <select name="method" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500">
                            <option value="cash">CASH</option>
                            <option value="bank">BANK TRANSFER</option>
                            <option value="momo">MOBILE MONEY (MoMo)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reference / Note</label>
                        <input type="text" name="reference" placeholder="Txn ID or receipt ref"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Return Reason</label>
                    <textarea name="reason" rows="2" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500" placeholder="Optional audit description..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showReturnModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition">
                        Submit Refund
                    </button>
                </div>
            </form>

            {{-- Tab 2: Itemized Product Return Form --}}
            <form x-show="activeReturnTab === 'itemized'"
                  x-transition
                  action="{{ route('sales.returns.store', $sale) }}"
                  method="POST"
                  class="mt-4 space-y-4"
                  x-init="if (returnLines.length === 0) addReturnLine()">
                @csrf
                <input type="hidden" name="mode" value="itemized">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Return Date</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Refund Method</label>
                        <select name="method" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                            <option value="cash">CASH</option>
                            <option value="bank">BANK TRANSFER</option>
                            <option value="momo">MOBILE MONEY</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reference</label>
                        <input type="text" name="reference" placeholder="Txn ID"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    </div>
                </div>

                {{-- Itemized Rows Table --}}
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] font-semibold text-slate-400 uppercase">
                            <tr>
                                <th class="py-2 px-3">Item to Return</th>
                                <th class="py-2 px-2 text-right">Max</th>
                                <th class="py-2 px-2 text-right">Qty</th>
                                <th class="py-2 px-2 text-right">Unit Price</th>
                                <th class="py-2 px-2">Disposition</th>
                                <th class="py-2 px-3 text-right">Subtotal</th>
                                <th class="py-2 px-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="(row, idx) in returnLines" :key="row.key">
                                <tr>
                                    <td class="py-2 px-3">
                                        <select class="w-48 text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                                :name="`items[${idx}][sale_item_id]`"
                                                @change="onReturnProductSelect(idx, $event)"
                                                required>
                                            <option value="">Select item...</option>
                                            <template x-for="opt in returnOptions" :key="opt.sale_item_id">
                                                <option :value="opt.sale_item_id" x-text="opt.product_name + ' (avail: ' + opt.max + ')'"></option>
                                            </template>
                                        </select>
                                        <input type="hidden" :name="`items[${idx}][product_id]`" :value="row.product_id">
                                    </td>
                                    <td class="py-2 px-2 text-right font-mono text-slate-500" x-text="row.max"></td>
                                    <td class="py-2 px-2 text-right">
                                        <input type="number" step="0.01" min="0.01"
                                               class="w-20 text-right text-xs font-mono rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                               :name="`items[${idx}][quantity]`"
                                               x-model.number="row.quantity"
                                               @input="clampReturnLine(idx)"
                                               required>
                                    </td>
                                    <td class="py-2 px-2 text-right font-mono text-slate-600 dark:text-slate-300">
                                        <input type="number" step="0.01" min="0"
                                               class="w-24 text-right text-xs font-mono rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                               :name="`items[${idx}][unit_price]`"
                                               x-model.number="row.unit_price"
                                               @input="recalcReturn()"
                                               required>
                                    </td>
                                    <td class="py-2 px-2">
                                        <select class="w-28 text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                                :name="`items[${idx}][disposition]`"
                                                x-model="row.disposition">
                                            <option value="restock">Restock</option>
                                            <option value="writeoff">Write-off</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-3 text-right font-mono font-semibold text-rose-600 dark:text-rose-400"
                                        x-text="'-' + formatMoney(row.quantity * row.unit_price)">
                                    </td>
                                    <td class="py-2 px-2 text-center">
                                        <button type="button" @click="removeReturnLine(idx)" class="p-1 text-slate-400 hover:text-rose-600 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <td colspan="7" class="p-2.5">
                                    <div class="flex items-center justify-between">
                                        <button type="button" @click="addReturnLine()" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-lg text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700">
                                            + Add Item
                                        </button>
                                        <div class="text-xs">
                                            <span class="text-slate-500">Calculated Refund Total:</span>
                                            <span class="ml-1 font-mono font-bold text-rose-600 dark:text-rose-400" x-text="formatMoney(returnTotal) + ' RWF'"></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reason</label>
                    <textarea name="reason" rows="2" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="Optional audit description..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showReturnModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition"
                            :disabled="returnLines.length === 0 || returnTotal <= 0">
                        Confirm Return & Restock
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ============================================================ --}}
    {{-- MODAL 3: LOAN QUICK-PAY MODAL                                --}}
    {{-- ============================================================ --}}
    @if($loan && $loan->status === 'pending')
    <div x-show="showLoanPayModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="showLoanPayModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative"
             x-data="{
                loanRemaining: {{ json_encode($loanRem) }},
                loanAmount: {{ json_encode(number_format($loanRem, 2, '.', '')) }},
                loanMethod: {{ json_encode(in_array($channel, $methods, true) ? $channel : 'cash') }},
                loanDate: {{ json_encode(now()->toDateString()) }},
                loanExceeded: false,
                checkLoanClamp() {
                    const parsed = parseFloat(this.loanAmount || 0);
                    this.loanExceeded = parsed > (this.loanRemaining + 0.009);
                }
             }">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Record Credit Repayment</h3>
                        <p class="text-xs text-slate-400">Loan #{{ $loan->id }} • Customer: {{ $sale->customer->name ?? 'Client' }}</p>
                    </div>
                </div>
                <button type="button" @click="showLoanPayModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('loan-payments.store', $loan) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Repayment Amount (RWF) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" name="amount"
                           x-model="loanAmount"
                           @input="checkLoanClamp()"
                           class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500" required>
                    <p class="mt-1 text-[11px]" :class="loanExceeded ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'">
                        Remaining Principal: <strong class="font-mono text-slate-700 dark:text-slate-300">{{ number_format($loanRem, 2) }} RWF</strong>
                        <span x-show="loanExceeded"> • Exceeds remaining loan balance!</span>
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Date</label>
                        <input type="date" name="payment_date" x-model="loanDate"
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-teal-500 focus:ring-teal-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Method</label>
                        <select name="method" x-model="loanMethod" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                            @foreach($methods as $m)
                                <option value="{{ $m }}">{{ strtoupper($m) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Notes / Ref</label>
                    <textarea name="notes" rows="2" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="Optional reference notes..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showLoanPayModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition"
                            :disabled="loanExceeded || !loanAmount || loanAmount <= 0">
                        Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endcannot
@endsection
