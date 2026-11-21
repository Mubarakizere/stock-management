@php
    $fmt = fn($n) => number_format((float)$n, 2);
    $pageTotalQty = 0;
    $pageTotalRet = 0;
    $pageTotalRem = 0;
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                <th scope="col" class="py-3.5 pl-4 sm:pl-6 pr-3">Voucher #</th>
                <th scope="col" class="px-3 py-3.5">Direction</th>
                <th scope="col" class="px-3 py-3.5">Partner Entity</th>
                <th scope="col" class="px-3 py-3.5">Item & Linked Stock</th>
                <th scope="col" class="px-3 py-3.5 text-right">Loaned Qty</th>
                <th scope="col" class="px-3 py-3.5 text-right">Returned</th>
                <th scope="col" class="px-3 py-3.5 text-right">Remaining</th>
                <th scope="col" class="px-3 py-3.5">Timeline & Due</th>
                <th scope="col" class="px-3 py-3.5 text-center">Status</th>
                <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs text-slate-700 dark:text-slate-300">
            @forelse($loans as $loan)
                @php
                    $isGiven = $loan->direction === 'given';
                    $voucherCode = '#IL-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT);
                    $qty = (float) $loan->quantity;
                    $ret = (float) $loan->quantity_returned;
                    $rem = max($qty - $ret, 0.0);
                    $pct = $qty > 0 ? min(100, (int) round(($ret / $qty) * 100)) : 0;

                    $pageTotalQty += $qty;
                    $pageTotalRet += $ret;
                    $pageTotalRem += $rem;

                    $today = \Carbon\Carbon::today();
                    $due = $loan->due_date ? \Carbon\Carbon::parse($loan->due_date) : null;
                    $isOverdue = $loan->status !== 'returned' && $due && $due->isPast() && $rem > 0.001;
                    $dueDiff = $due ? $today->diffInDays($due, false) : null;
                    $isDueSoon = $loan->status !== 'returned' && $due && !$isOverdue && $dueDiff !== null && $dueDiff <= 3 && $dueDiff >= 0;

                    $hasReturns = $loan->returns->isNotEmpty();
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group {{ $isOverdue ? 'bg-rose-50/30 dark:bg-rose-950/10' : '' }}">
                    {{-- Voucher ID & Quick View Link --}}
                    <td class="py-3.5 pl-4 sm:pl-6 pr-3 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $loan->status === 'returned' ? 'bg-emerald-500' : ($isOverdue ? 'bg-rose-500 animate-pulse' : ($loan->status === 'partial' ? 'bg-sky-500' : 'bg-amber-500')) }}"></span>
                            <button type="button"
                                    @click="openShowModal({{ json_encode([
                                        'id' => $loan->id,
                                        'code' => $voucherCode,
                                        'direction' => $loan->direction,
                                        'item_name' => $loan->item_name,
                                        'unit' => $loan->unit ?? 'pcs',
                                        'quantity' => $qty,
                                        'quantity_formatted' => $fmt($qty),
                                        'returned' => $ret,
                                        'returned_formatted' => $fmt($ret),
                                        'remaining' => $rem,
                                        'remaining_formatted' => $fmt($rem),
                                        'progress' => $pct,
                                        'loan_date' => optional($loan->loan_date)->format('Y-m-d'),
                                        'due_date' => optional($loan->due_date)->format('Y-m-d'),
                                        'is_overdue' => $isOverdue,
                                        'due_diff' => $dueDiff,
                                        'status' => $loan->status,
                                        'notes' => $loan->notes,
                                        'partner' => $loan->partner ? [
                                            'id' => $loan->partner->id,
                                            'name' => $loan->partner->name,
                                            'contact_person' => $loan->partner->contact_person,
                                            'phone' => $loan->partner->phone,
                                        ] : null,
                                        'product' => $loan->product ? [
                                            'id' => $loan->product->id,
                                            'name' => $loan->product->name,
                                            'stock' => $loan->product->stock,
                                        ] : null,
                                        'returns_count' => $loan->returns->count(),
                                        'user_name' => optional($loan->user)->name ?? 'System',
                                    ]) }})"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400 transition hover:underline flex items-center gap-1">
                                <span>{{ $voucherCode }}</span>
                            </button>
                        </div>
                    </td>

                    {{-- Direction --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        @if($isGiven)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60 shadow-xs">
                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l10-10M17 7H7m10 0v10" />
                                </svg>
                                We LENT (Given)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/60 shadow-xs">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7 17M7 17h10M7 17V7" />
                                </svg>
                                We BORROWED (Taken)
                            </span>
                        @endif
                    </td>

                    {{-- Partner Entity --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                {{ $loan->partner->name ?? '—' }}
                            </span>
                            @if(optional($loan->partner)->contact_person || optional($loan->partner)->phone)
                                <span class="text-[10px] text-slate-400 pl-5">
                                    {{ optional($loan->partner)->contact_person ?? '' }}
                                    @if(optional($loan->partner)->contact_person && optional($loan->partner)->phone) • @endif
                                    {{ optional($loan->partner)->phone ?? '' }}
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Item & Linked Stock --}}
                    <td class="px-3 py-3.5">
                        <div class="flex flex-col max-w-[220px]">
                            <span class="font-medium text-slate-900 dark:text-slate-100 truncate" title="{{ $loan->item_name }}">
                                {{ $loan->item_name }}
                            </span>
                            @if($loan->product)
                                <span class="inline-flex items-center gap-1 text-[10px] text-indigo-600 dark:text-indigo-400 mt-0.5 truncate" title="Linked to {{ $loan->product->name }}">
                                    <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    Stock: {{ $loan->product->name }} ({{ $loan->product->stock }})
                                </span>
                            @else
                                <span class="text-[10px] text-slate-400 italic">Manual Asset (Unlinked)</span>
                            @endif
                        </div>
                    </td>

                    {{-- Loaned Qty --}}
                    <td class="px-3 py-3.5 text-right whitespace-nowrap">
                        <span class="font-semibold text-slate-800 dark:text-slate-100 font-mono text-sm">
                            {{ $fmt($qty) }}
                        </span>
                        <span class="text-[10px] text-slate-400 ml-1">{{ $loan->unit ?? 'pcs' }}</span>
                    </td>

                    {{-- Returned & Progress Bar --}}
                    <td class="px-3 py-3.5 text-right whitespace-nowrap">
                        <div class="flex flex-col items-end gap-1">
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400 font-mono text-xs">
                                {{ $fmt($ret) }} <span class="text-[10px] font-normal text-slate-400">{{ $loan->unit ?? 'pcs' }}</span>
                            </span>
                            <div class="w-20 bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $pct === 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-[9px] text-slate-400 font-mono">{{ $pct }}%</span>
                        </div>
                    </td>

                    {{-- Remaining --}}
                    <td class="px-3 py-3.5 text-right whitespace-nowrap">
                        <div class="flex flex-col items-end">
                            <span class="font-bold font-mono text-sm {{ $rem > 0 ? ($isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400') : 'text-slate-400 dark:text-slate-500' }}">
                                {{ $fmt($rem) }}
                            </span>
                            <span class="text-[10px] text-slate-400">{{ $loan->unit ?? 'pcs' }} pending</span>
                        </div>
                    </td>

                    {{-- Timeline & Due --}}
                    <td class="px-3 py-3.5 whitespace-nowrap text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[11px] flex items-center gap-1">
                                <span class="text-slate-400">Loan:</span>
                                <span class="font-mono text-slate-700 dark:text-slate-300">{{ optional($loan->loan_date)->format('M d, Y') }}</span>
                            </span>
                            @if($loan->due_date)
                                <div class="flex items-center gap-1.5 text-[10px]">
                                    <span class="text-slate-400">Due:</span>
                                    <span class="font-mono {{ $isOverdue ? 'text-rose-600 font-bold' : ($isDueSoon ? 'text-amber-600 font-semibold' : '') }}">
                                        {{ optional($loan->due_date)->format('M d, Y') }}
                                    </span>
                                    @if($isOverdue)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            Overdue
                                        </span>
                                    @elseif($isDueSoon)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            Due in {{ $dueDiff }}d
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-[10px] text-slate-400 italic">No expiry</span>
                            @endif
                        </div>
                    </td>

                    {{-- Status Badge --}}
                    <td class="px-3 py-3.5 whitespace-nowrap text-center">
                        @if($loan->status === 'returned')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60 shadow-xs">
                                <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Returned
                            </span>
                        @elseif($loan->status === 'partial')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60 shadow-xs">
                                <svg class="w-3 h-3 text-sky-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                Partial
                            </span>
                        @elseif($isOverdue || $loan->status === 'overdue')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60 shadow-xs">
                                <svg class="w-3 h-3 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Overdue
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60 shadow-xs">
                                <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                Pending
                            </span>
                        @endif
                    </td>

                    {{-- Actions Menu --}}
                    <td class="py-3.5 pl-3 pr-4 sm:pr-6 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            {{-- Quick Return Button (if remaining > 0) --}}
                            @if($rem > 0)
                                <button type="button"
                                        @click="openReturnModal({
                                            id: {{ $loan->id }},
                                            code: '{{ $voucherCode }}',
                                            item_name: '{{ addslashes($loan->item_name) }}',
                                            partner_name: '{{ addslashes($loan->partner->name ?? 'Partner') }}',
                                            direction: '{{ $loan->direction }}',
                                            unit: '{{ addslashes($loan->unit ?? 'pcs') }}',
                                            remaining: {{ $rem }},
                                            remaining_formatted: '{{ $fmt($rem) }}',
                                            quantity: {{ $qty }},
                                            has_product: {{ $loan->product_id ? 'true' : 'false' }},
                                            action_url: '{{ route('item-loans.return', $loan) }}'
                                        })"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800/60 transition"
                                        title="Record Item Return">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                    </svg>
                                    <span>Return</span>
                                </button>
                            @endif

                            {{-- View Details --}}
                            <button type="button"
                                    @click="openShowModal({{ json_encode([
                                        'id' => $loan->id,
                                        'code' => $voucherCode,
                                        'direction' => $loan->direction,
                                        'item_name' => $loan->item_name,
                                        'unit' => $loan->unit ?? 'pcs',
                                        'quantity' => $qty,
                                        'quantity_formatted' => $fmt($qty),
                                        'returned' => $ret,
                                        'returned_formatted' => $fmt($ret),
                                        'remaining' => $rem,
                                        'remaining_formatted' => $fmt($rem),
                                        'progress' => $pct,
                                        'loan_date' => optional($loan->loan_date)->format('Y-m-d'),
                                        'due_date' => optional($loan->due_date)->format('Y-m-d'),
                                        'is_overdue' => $isOverdue,
                                        'due_diff' => $dueDiff,
                                        'status' => $loan->status,
                                        'notes' => $loan->notes,
                                        'partner' => $loan->partner ? [
                                            'id' => $loan->partner->id,
                                            'name' => $loan->partner->name,
                                            'contact_person' => $loan->partner->contact_person,
                                            'phone' => $loan->partner->phone,
                                        ] : null,
                                        'product' => $loan->product ? [
                                            'id' => $loan->product->id,
                                            'name' => $loan->product->name,
                                            'stock' => $loan->product->stock,
                                        ] : null,
                                        'returns_count' => $loan->returns->count(),
                                        'user_name' => optional($loan->user)->name ?? 'System',
                                    ]) }})"
                                    class="p-1.5 rounded-md text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 dark:hover:text-indigo-400 transition"
                                    title="View Details">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>

                            {{-- Edit Loan Modal Trigger --}}
                            @can('item-loans.edit')
                                <button type="button"
                                        @click="openEditModal({{ json_encode([
                                            'id' => $loan->id,
                                            'code' => $voucherCode,
                                            'partner_id' => $loan->partner_id,
                                            'product_id' => $loan->product_id,
                                            'direction' => $loan->direction,
                                            'item_name' => $loan->item_name,
                                            'unit' => $loan->unit ?? '',
                                            'quantity' => $qty,
                                            'quantity_returned' => $ret,
                                            'has_returns' => $hasReturns,
                                            'loan_date' => optional($loan->loan_date)->format('Y-m-d'),
                                            'due_date' => optional($loan->due_date)->format('Y-m-d'),
                                            'notes' => $loan->notes ?? '',
                                            'action_url' => route('item-loans.update', $loan),
                                        ]) }})"
                                        class="p-1.5 rounded-md text-slate-500 hover:text-amber-600 hover:bg-slate-100 dark:hover:bg-slate-800 dark:hover:text-amber-400 transition"
                                        title="Edit Loan">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            @endcan

                            {{-- Delete Modal Trigger --}}
                            @can('item-loans.delete')
                                @if(!$hasReturns)
                                    <button type="button"
                                            @click="openDeleteModal('{{ route('item-loans.destroy', $loan) }}', '{{ $voucherCode }}', '{{ addslashes($loan->item_name) }}', '{{ $fmt($qty) }} {{ addslashes($loan->unit ?? 'pcs') }}')"
                                            class="p-1.5 rounded-md text-slate-500 hover:text-rose-600 hover:bg-slate-100 dark:hover:bg-slate-800 dark:hover:text-rose-400 transition"
                                            title="Delete Loan">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @else
                                    <button type="button"
                                            disabled
                                            class="p-1.5 rounded-md text-slate-300 dark:text-slate-600 cursor-not-allowed opacity-50"
                                            title="Cannot delete: returns have already been recorded.">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="py-16 text-center">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="p-3 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">No Inter-Company Loans Found</div>
                            <p class="text-xs text-slate-400 max-w-sm">No item loan records match your search criteria or date filters.</p>
                            <button type="button" @click="resetFilters()" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Reset Filters
                            </button>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($loans->isNotEmpty())
            <tfoot>
                <tr class="border-t-2 border-slate-200 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/80 font-bold text-xs text-slate-800 dark:text-slate-200">
                    <td colspan="4" class="py-3.5 pl-4 sm:pl-6 pr-3 uppercase tracking-wider text-[11px] text-slate-500 dark:text-slate-400">
                        Page Aggregate ({{ $loans->count() }} records)
                    </td>
                    <td class="px-3 py-3.5 text-right font-mono text-slate-900 dark:text-white">
                        {{ $fmt($pageTotalQty) }}
                    </td>
                    <td class="px-3 py-3.5 text-right font-mono text-emerald-600 dark:text-emerald-400">
                        {{ $fmt($pageTotalRet) }}
                    </td>
                    <td class="px-3 py-3.5 text-right font-mono text-amber-600 dark:text-amber-400">
                        {{ $fmt($pageTotalRem) }}
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

@if($loans->hasPages())
    <div class="px-4 sm:px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
        {{ $loans->links() }}
    </div>
@endif
