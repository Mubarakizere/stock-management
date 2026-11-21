<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                <th scope="col" class="py-3.5 pl-4 sm:pl-6 pr-3">Loan ID</th>
                <th scope="col" class="px-3 py-3.5">Type & Direction</th>
                <th scope="col" class="px-3 py-3.5">Counterparty</th>
                <th scope="col" class="px-3 py-3.5 text-right">Principal</th>
                <th scope="col" class="px-3 py-3.5 text-right">Settled</th>
                <th scope="col" class="px-3 py-3.5 text-right">Remaining</th>
                <th scope="col" class="px-3 py-3.5">Timeline & Due</th>
                <th scope="col" class="px-3 py-3.5 text-center">Status</th>
                <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs text-slate-700 dark:text-slate-300">
            @php
                $pagePrincipal = 0;
                $pagePaid = 0;
                $pageRemaining = 0;
            @endphp
            @forelse($loans as $loan)
                @php
                    $isGiven = $loan->type === 'given';
                    $loanCode = '#LN-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT);
                    $paid = (float) ($loan->payments_sum_amount ?? $loan->payments->sum('amount'));
                    $amount = (float) $loan->amount;
                    $remaining = max($amount - $paid, 0);
                    $progress = $amount > 0 ? min(100, (int) round(($paid / $amount) * 100)) : 0;

                    $pagePrincipal += $amount;
                    $pagePaid += $paid;
                    $pageRemaining += $remaining;

                    $today = \Carbon\Carbon::today();
                    $due = $loan->due_date ? \Carbon\Carbon::parse($loan->due_date) : null;
                    $isOverdue = $loan->status !== 'paid' && $due && $due->isPast() && $remaining > 0.01;
                    $dueDiff = $due ? $today->diffInDays($due, false) : null;
                    $isDueSoon = $loan->status !== 'paid' && $due && !$isOverdue && $dueDiff !== null && $dueDiff <= 7 && $dueDiff >= 0;

                    $partyName = $isGiven ? optional($loan->customer)->name : optional($loan->supplier)->name;
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group {{ $isOverdue ? 'bg-rose-50/30 dark:bg-rose-950/10' : '' }}">
                    {{-- Loan ID --}}
                    <td class="py-3.5 pl-4 sm:pl-6 pr-3 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $loan->status === 'paid' ? 'bg-emerald-500' : ($isOverdue ? 'bg-rose-500 animate-pulse' : 'bg-amber-500') }}"></span>
                            <button type="button"
                                    @click="openShowModal({{ json_encode([
                                        'id' => $loan->id,
                                        'code' => $loanCode,
                                        'type' => $loan->type,
                                        'amount' => number_format($amount, 2),
                                        'raw_amount' => $amount,
                                        'paid' => number_format($paid, 2),
                                        'raw_paid' => $paid,
                                        'remaining' => number_format($remaining, 2),
                                        'raw_remaining' => $remaining,
                                        'progress' => $progress,
                                        'loan_date' => optional($loan->loan_date)->format('Y-m-d'),
                                        'due_date' => optional($loan->due_date)->format('Y-m-d'),
                                        'is_overdue' => $isOverdue,
                                        'due_diff' => $dueDiff,
                                        'status' => $loan->status,
                                        'notes' => $loan->notes,
                                        'party' => $isGiven ? ['type' => 'customer', 'name' => optional($loan->customer)->name, 'id' => optional($loan->customer)->id] : ['type' => 'supplier', 'name' => optional($loan->supplier)->name, 'id' => optional($loan->supplier)->id],
                                        'payments_count' => $loan->payments->count()
                                    ]) }})"
                                    class="hover:text-teal-600 dark:hover:text-teal-400 transition hover:underline">
                                {{ $loanCode }}
                            </button>
                        </div>
                    </td>

                    {{-- Type & Direction --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        @if($isGiven)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800/60">
                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Given (Receivable)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800/60">
                                <svg class="w-3.5 h-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                Taken (Payable)
                            </span>
                        @endif
                    </td>

                    {{-- Counterparty --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        @if($isGiven && $loan->customer)
                            <a href="{{ route('customers.show', $loan->customer->id) }}"
                               class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800/60 hover:bg-blue-100 transition">
                                <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="max-w-[130px] truncate">{{ $loan->customer->name }}</span>
                            </a>
                        @elseif(!$isGiven && $loan->supplier)
                            <a href="{{ route('suppliers.show', $loan->supplier->id) }}"
                               class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800/60 hover:bg-purple-100 transition">
                                <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span class="max-w-[130px] truncate">{{ $loan->supplier->name }}</span>
                            </a>
                        @else
                            <span class="text-slate-400 dark:text-slate-600 font-medium">—</span>
                        @endif
                    </td>

                    {{-- Principal Amount --}}
                    <td class="px-3 py-3.5 whitespace-nowrap text-right font-mono font-bold text-sm text-slate-900 dark:text-white">
                        {{ number_format($amount, 2) }}
                    </td>

                    {{-- Settled & Progress --}}
                    <td class="px-3 py-3.5 whitespace-nowrap text-right">
                        <div class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ number_format($paid, 2) }}
                        </div>
                        <div class="flex items-center justify-end gap-1.5 mt-1">
                            <div class="w-16 bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $progress >= 100 ? 'bg-emerald-500' : 'bg-teal-500' }}" style="width: {{ $progress }}%"></div>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400">{{ $progress }}%</span>
                        </div>
                    </td>

                    {{-- Remaining Balance --}}
                    <td class="px-3 py-3.5 whitespace-nowrap text-right font-mono font-bold text-sm {{ $remaining > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ number_format($remaining, 2) }}
                    </td>

                    {{-- Timeline & Due --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        <div class="font-medium text-slate-800 dark:text-slate-200">
                            Issued: {{ optional($loan->loan_date)->format('M d, Y') }}
                        </div>
                        <div class="mt-0.5">
                            @if($loan->status === 'paid')
                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Settled
                                </span>
                            @elseif($isOverdue)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-900/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                    Overdue {{ abs($dueDiff) }}d
                                </span>
                            @elseif($isDueSoon)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900/60">
                                    Due in {{ $dueDiff }}d
                                </span>
                            @elseif($due)
                                <span class="text-[11px] text-slate-400">
                                    Due {{ $due->format('M d, Y') }}
                                </span>
                            @else
                                <span class="text-[11px] text-slate-400">No due date</span>
                            @endif
                        </div>
                    </td>

                    {{-- Status Badge --}}
                    <td class="px-3 py-3.5 whitespace-nowrap text-center">
                        @if($loan->status === 'paid')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Paid
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Pending
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="py-3.5 pl-3 pr-4 sm:pr-6 whitespace-nowrap text-right text-xs">
                        <div class="flex items-center justify-end gap-1.5">
                            {{-- Quick View --}}
                            <button type="button"
                                    @click="openShowModal({{ json_encode([
                                        'id' => $loan->id,
                                        'code' => $loanCode,
                                        'type' => $loan->type,
                                        'amount' => number_format($amount, 2),
                                        'raw_amount' => $amount,
                                        'paid' => number_format($paid, 2),
                                        'raw_paid' => $paid,
                                        'remaining' => number_format($remaining, 2),
                                        'raw_remaining' => $remaining,
                                        'progress' => $progress,
                                        'loan_date' => optional($loan->loan_date)->format('Y-m-d'),
                                        'due_date' => optional($loan->due_date)->format('Y-m-d'),
                                        'is_overdue' => $isOverdue,
                                        'due_diff' => $dueDiff,
                                        'status' => $loan->status,
                                        'notes' => $loan->notes,
                                        'party' => $isGiven ? ['type' => 'customer', 'name' => optional($loan->customer)->name, 'id' => optional($loan->customer)->id] : ['type' => 'supplier', 'name' => optional($loan->supplier)->name, 'id' => optional($loan->supplier)->id],
                                        'payments_count' => $loan->payments->count()
                                    ]) }})"
                                    title="Quick View"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>

                            {{-- Record Payment (if pending) --}}
                            @if($loan->status !== 'paid')
                                @can('loans.view')
                                    <button type="button"
                                            @click="openPaymentModal({{ json_encode([
                                                'loan_id' => $loan->id,
                                                'code' => $loanCode,
                                                'party_name' => $partyName,
                                                'remaining' => $remaining,
                                                'remaining_formatted' => number_format($remaining, 2),
                                                'amount' => $amount
                                            ]) }})"
                                            title="Record Payment"
                                            class="p-1.5 rounded-lg text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </button>
                                @endcan
                            @endif

                            {{-- Edit --}}
                            @can('loans.edit')
                                <button type="button"
                                        @click="openEditModal({{ json_encode([
                                            'id' => $loan->id,
                                            'type' => $loan->type,
                                            'amount' => $amount,
                                            'loan_date' => optional($loan->loan_date)->format('Y-m-d'),
                                            'due_date' => optional($loan->due_date)->format('Y-m-d'),
                                            'status' => $loan->status,
                                            'customer_id' => $loan->customer_id ?? '',
                                            'supplier_id' => $loan->supplier_id ?? '',
                                            'notes' => $loan->notes ?? '',
                                            'action_url' => route('loans.update', $loan->id)
                                        ]) }})"
                                        title="Edit Loan"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-teal-600 hover:bg-teal-50 dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-950/40 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            @endcan

                            {{-- Delete --}}
                            @can('loans.delete')
                                <button type="button"
                                        @click="openDeleteModal('{{ route('loans.destroy', $loan->id) }}', '{{ $loanCode }}', '{{ number_format($amount, 2) }} RWF')"
                                        title="Delete Loan"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:text-slate-400 dark:hover:text-rose-400 dark:hover:bg-rose-950/40 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="py-12 text-center">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No loans match your filters</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Try adjusting your filters, date ranges, or search keywords.</p>
                            </div>
                            <button type="button" @click="resetFilters()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition">
                                Reset Filters
                            </button>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>

        {{-- Page Totals Footer --}}
        @if($loans->count() > 0)
            <tfoot class="bg-slate-50/90 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300">
                <tr>
                    <td colspan="3" class="py-3 pl-4 sm:pl-6 pr-3 uppercase tracking-wider text-[11px] text-slate-500 dark:text-slate-400">
                        Page Totals ({{ $loans->count() }} items)
                    </td>
                    <td class="px-3 py-3 text-right font-mono text-xs whitespace-nowrap text-slate-900 dark:text-white">
                        RWF {{ number_format($pagePrincipal, 2) }}
                    </td>
                    <td class="px-3 py-3 text-right font-mono text-xs whitespace-nowrap text-emerald-600 dark:text-emerald-400">
                        RWF {{ number_format($pagePaid, 2) }}
                    </td>
                    <td class="px-3 py-3 text-right font-mono text-xs whitespace-nowrap text-rose-600 dark:text-rose-400">
                        RWF {{ number_format($pageRemaining, 2) }}
                    </td>
                    <td colspan="3" class="px-3 py-3 text-right text-[11px] text-slate-500 dark:text-slate-400">
                        Showing {{ $loans->firstItem() ?? 0 }} to {{ $loans->lastItem() ?? 0 }} of {{ $loans->total() }} loans
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

{{-- Pagination Links --}}
@if($loans->hasPages())
    <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
        {{ $loans->links() }}
    </div>
@endif

