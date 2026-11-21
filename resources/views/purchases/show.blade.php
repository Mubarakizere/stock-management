@extends('layouts.app')
@section('title', "Purchase #{$purchase->id}")

@section('content')
@php
    use Carbon\Carbon;

    try {
        $purchase->loadMissing([
            'items.product.category',
            'items.returnItems',
            'returns.items.product',
            'supplier',
            'user',
            'loan.payments.user',
            'transaction',
        ]);
    } catch (\Throwable $e) {}

    $date      = $purchase->purchase_date ?? $purchase->created_at;
    $status    = strtolower($purchase->status ?? 'completed');
    $channel   = strtolower($purchase->payment_channel ?? 'cash');
    $reference = trim((string) ($purchase->method ?? ''));

    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);

    $computedSubtotal = (float) ($purchase->subtotal ?? optional($purchase->items)->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost));
    $tax      = (float) ($purchase->tax ?? 0);
    $discount = (float) ($purchase->discount ?? 0);
    $total    = (float) ($purchase->total_amount ?? ($computedSubtotal + $tax - $discount));
    $paid     = (float) ($purchase->amount_paid ?? 0);
    $balance  = max(0, $total - $paid);
    $paidPct  = $total > 0 ? min(100, round(($paid / $total) * 100)) : 0;

    $returns         = $purchase->returns->sortByDesc('return_date')->values();
    $sumReturnValue  = (float) $returns->sum('total_amount');
    $sumCashRefunds  = (float) $returns->sum('refund_amount');

    $loan        = $purchase->loan;
    $loanPaid    = $loan ? (float) $loan->payments->sum('amount') : 0.0;
    $loanAmt     = $loan ? (float) $loan->amount : 0.0;
    $loanRemain  = $loan ? max(0, round($loanAmt - $loanPaid, 2)) : 0.0;

    $statusBadge = match($status) {
        'completed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/60',
        'partial'   => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/60',
        'pending'   => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200/60 dark:border-rose-800/60',
        'cancelled' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700',
        default     => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
    };

    $channelBadge = match($channel) {
        'cash' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200/60 dark:border-emerald-800/60',
        'bank' => 'bg-blue-50 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200/60 dark:border-blue-800/60',
        'momo', 'mobile_money' => 'bg-purple-50 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200/60 dark:border-purple-800/60',
        default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
    };
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    @cannot('purchases.view')
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/80 bg-amber-50/50 dark:bg-amber-950/30 p-6 text-center">
            <h3 class="text-base font-bold text-amber-900 dark:text-amber-200">Access Restricted</h3>
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                You do not possess permission to inspect purchase orders.
            </p>
        </div>
    @else

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Dashboard</a>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ route('purchases.index') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Purchases</a>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-800 dark:text-slate-200 font-medium">Order #{{ $purchase->id }}</span>
            </nav>

            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Purchase Order #PO-{{ str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $statusBadge }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $status === 'completed' ? 'bg-emerald-500' : ($status === 'partial' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                    <span>{{ ucfirst($status) }}</span>
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold uppercase tracking-wider border {{ $channelBadge }}">
                    {{ $channel }}
                </span>
                @if($reference !== '')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                        Ref: {{ $reference }}
                    </span>
                @endif
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Purchased from <strong class="text-slate-800 dark:text-slate-200">{{ optional($purchase->supplier)->name ?? 'Supplier' }}</strong> on {{ $date ? Carbon::parse($date)->format('d M Y, H:i') : '—' }} · Recorded by {{ optional($purchase->user)->name ?? 'System' }}.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('purchases.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Purchases Hub</span>
            </a>

            @can('purchases.edit')
                <a href="{{ route('purchases.edit', $purchase) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Order</span>
                </a>

                <button type="button"
                        onclick="window.openPurchaseReturn()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-amber-800 bg-amber-50 border border-amber-200 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 shadow-sm transition">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    <span>Return to Supplier</span>
                </button>
            @endcan

            @if(Route::has('purchases.invoice'))
                <a href="{{ route('purchases.invoice', $purchase) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span>Invoice PDF</span>
                </a>
            @endif

            @if($loan && $loan->status !== 'paid')
                @can('loans.view')
                    <a href="{{ route('loan-payments.create', $loan) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-teal-600 text-white hover:bg-teal-500 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span>Settle Supplier Balance</span>
                    </a>
                @endcan
            @endif
        </div>
    </div>

    {{-- Session Notifications --}}
    @if(session('success'))
        <div class="rounded-xl bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 p-4 text-sm text-teal-800 dark:text-teal-200 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-teal-600 dark:text-teal-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-teal-600 dark:text-teal-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- 4-KPI Deck --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Order Amount --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gross Order Total</span>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xs text-slate-400 font-normal">RWF</span>
                <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                    {{ $fmt2($total) }}
                </span>
            </div>
            <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                <span>Subtotal: {{ $fmt2($computedSubtotal) }}</span>
                @if($tax > 0)<span>+Tax: {{ $fmt2($tax) }}</span>@endif
            </div>
        </div>

        {{-- Card 2: Settled / Paid --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Amount Settled</span>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xs text-slate-400 font-normal">RWF</span>
                <span class="text-2xl font-bold tracking-tight text-teal-600 dark:text-teal-400 tabular-nums">
                    {{ $fmt2($paid) }}
                </span>
            </div>
            <div class="mt-1 flex items-center justify-between text-[11px]">
                <span class="text-slate-400">Settlement Progress:</span>
                <span class="font-bold text-teal-600 dark:text-teal-400">{{ $paidPct }}%</span>
            </div>
        </div>

        {{-- Card 3: Balance Due --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Outstanding Balance</span>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xs text-slate-400 font-normal">RWF</span>
                <span class="text-2xl font-bold tracking-tight tabular-nums {{ $balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ $fmt2($balance) }}
                </span>
            </div>
            <p class="mt-1 text-[11px] text-slate-400">
                {{ $balance > 0 ? 'Due to supplier' : 'Fully settled' }}
            </p>
        </div>

        {{-- Card 4: Returns & Refunds --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Returned to Supplier</span>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-xs text-slate-400 font-normal">RWF</span>
                <span class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
                    {{ $fmt2($sumReturnValue) }}
                </span>
            </div>
            <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                <span>{{ $returns->count() }} return ticket(s)</span>
                @if($sumCashRefunds > 0)
                    <span class="text-emerald-600 font-bold">Refund: {{ $fmt2($sumCashRefunds) }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Order Context Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Supplier Profile Card --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Supplier Vendor</h3>
            </div>
            <div class="mt-3 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                <div class="font-bold text-slate-900 dark:text-white text-sm">
                    {{ optional($purchase->supplier)->name ?? 'Walk-in / Cash Vendor' }}
                </div>
                @if(optional($purchase->supplier)->phone)
                    <div><span class="text-slate-400">Phone:</span> <span class="font-mono">{{ $purchase->supplier->phone }}</span></div>
                @endif
                @if(optional($purchase->supplier)->email)
                    <div><span class="text-slate-400">Email:</span> {{ $purchase->supplier->email }}</div>
                @endif
                @if(optional($purchase->supplier)->address)
                    <div><span class="text-slate-400">Address:</span> {{ $purchase->supplier->address }}</div>
                @endif
            </div>
        </div>

        {{-- Order Metadata Card --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Transaction Details</h3>
            </div>
            <div class="mt-3 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                <div class="flex justify-between">
                    <span class="text-slate-400">Order Date:</span>
                    <span class="font-mono font-medium">{{ $date ? Carbon::parse($date)->format('d M Y') : '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Payment Channel:</span>
                    <span class="font-bold uppercase">{{ $channel }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Invoice / Reference:</span>
                    <span class="font-mono font-medium">{{ $reference ?: 'None' }}</span>
                </div>
                @if($loan)
                    <div class="flex justify-between">
                        <span class="text-slate-400">Supplier Loan Ticket:</span>
                        <span class="font-bold {{ $loan->status === 'paid' ? 'text-teal-600' : 'text-amber-600' }}">
                            Loan #{{ $loan->id }} ({{ ucfirst($loan->status) }})
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Notes & Attachments Card --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Notes & File</h3>
            </div>
            <div class="mt-3 text-xs space-y-2">
                @if($purchase->notes)
                    <p class="text-slate-700 dark:text-slate-300 leading-relaxed italic bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-800">
                        "{{ $purchase->notes }}"
                    </p>
                @else
                    <p class="text-slate-400 italic">No notes recorded for this order.</p>
                @endif

                @if(!empty($purchase->document_path))
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($purchase->document_path) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 dark:bg-teal-950/40 dark:text-teal-300 border border-teal-200 dark:border-teal-800 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>View Attached Document</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Line Items Table Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Purchased Items Breakdown</h2>
            </div>
            <span class="text-xs text-slate-400 font-medium">{{ $purchase->items->count() }} line items</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50/30 dark:bg-slate-900/30">
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">Raw Material / Product</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4 text-right">Quantity</th>
                        <th class="py-3 px-4 text-right">Unit Purchase Cost</th>
                        <th class="py-3 px-4 text-right">Line Total</th>
                        <th class="py-3 px-4 text-right">Returned Qty</th>
                        <th class="py-3 px-4 text-right">Net Received</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @foreach($purchase->items as $idx => $it)
                        @php
                            $prod = $it->product;
                            $retQty = (float) optional($it->returnItems)->sum('quantity');
                            $netQty = max(0, (float)$it->quantity - $retQty);
                            $isRawMat = ($prod->type ?? '') === 'raw_material';
                            $prodLink = $prod ? ($isRawMat ? route('raw-materials.show', $prod) : route('products.show', $prod)) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                            <td class="py-3 px-4 font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-3 px-4">
                                @if($prodLink)
                                    <a href="{{ $prodLink }}" class="font-semibold text-slate-900 dark:text-white hover:text-teal-600 transition">
                                        {{ $prod->name ?? 'Item #'.$it->product_id }}
                                    </a>
                                @else
                                    <span class="font-semibold text-slate-900 dark:text-white">
                                        {{ $prod->name ?? 'Item #'.$it->product_id }}
                                    </span>
                                @endif
                                <div class="text-[11px] font-mono text-slate-400">Unit: {{ $prod->unit ?? 'pcs' }}</div>
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                {{ optional(optional($prod)->category)->name ?? 'Uncategorized' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ $fmt2($it->quantity) }} {{ $prod->unit ?? 'pcs' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                {{ $fmt2($it->unit_cost) }} RWF
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ $fmt2($it->total_cost ?? ($it->quantity * $it->unit_cost)) }} RWF
                            </td>
                            <td class="py-3 px-4 text-right font-mono {{ $retQty > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' }}">
                                {{ $retQty > 0 ? $fmt2($retQty) . ' ' . ($prod->unit ?? 'pcs') : '—' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-teal-600 dark:text-teal-400">
                                {{ $fmt2($netQty) }} {{ $prod->unit ?? 'pcs' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-xs font-bold">
                    <tr>
                        <td colspan="5" class="py-3 px-4 text-right text-slate-600 dark:text-slate-400 uppercase tracking-wider">Subtotal:</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-900 dark:text-white">{{ $fmt2($computedSubtotal) }} RWF</td>
                        <td colspan="2"></td>
                    </tr>
                    @if($tax > 0)
                        <tr>
                            <td colspan="5" class="py-2 px-4 text-right text-slate-600 dark:text-slate-400 uppercase tracking-wider">Tax:</td>
                            <td class="py-2 px-4 text-right font-mono text-slate-900 dark:text-white">+{{ $fmt2($tax) }} RWF</td>
                            <td colspan="2"></td>
                        </tr>
                    @endif
                    @if($discount > 0)
                        <tr>
                            <td colspan="5" class="py-2 px-4 text-right text-slate-600 dark:text-slate-400 uppercase tracking-wider">Discount:</td>
                            <td class="py-2 px-4 text-right font-mono text-amber-600">-{{ $fmt2($discount) }} RWF</td>
                            <td colspan="2"></td>
                        </tr>
                    @endif
                    <tr class="border-t border-slate-200 dark:border-slate-700 text-sm">
                        <td colspan="5" class="py-3 px-4 text-right uppercase tracking-wider text-slate-900 dark:text-white">Order Total:</td>
                        <td class="py-3 px-4 text-right font-mono text-teal-600 dark:text-teal-400">{{ $fmt2($total) }} RWF</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Returns Section (If processed) --}}
    @if($returns->isNotEmpty())
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-amber-50/40 dark:bg-amber-950/20">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Processed Supplier Returns</h2>
                </div>
                <span class="text-xs text-amber-700 dark:text-amber-300 font-bold">{{ $returns->count() }} return(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-50/30">
                            <th class="py-2.5 px-4">Date</th>
                            <th class="py-2.5 px-4">Ticket</th>
                            <th class="py-2.5 px-4 text-right">Return Value</th>
                            <th class="py-2.5 px-4 text-right">Cash Refund</th>
                            <th class="py-2.5 px-4">Items Returned</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($returns as $ret)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                <td class="py-2.5 px-4 font-mono">{{ $ret->return_date ? \Carbon\Carbon::parse($ret->return_date)->format('d M Y') : '—' }}</td>
                                <td class="py-2.5 px-4 font-mono font-bold">#RET-{{ $ret->id }}</td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-amber-600">{{ $fmt2($ret->total_amount) }} RWF</td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-600">{{ $fmt2($ret->refund_amount) }} RWF</td>
                                <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300">
                                    @foreach($ret->items as $rit)
                                        <span class="inline-block mr-2 px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[11px]">
                                            {{ optional($rit->product)->name ?? 'Item' }} × {{ $fmt2($rit->quantity) }}
                                        </span>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @endcannot
</div>

{{-- Return Modal Mount --}}
@can('purchases.edit')
    @include('purchases._return_modal', ['purchase' => $purchase])
@endcan
@endsection

@push('scripts')
<script>
window.openPurchaseReturn = function(){
    window.dispatchEvent(new CustomEvent('open-purchase-return'));
};
</script>
@endpush
