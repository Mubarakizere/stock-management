@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $user = auth()->user();
    $canEdit   = $user?->can('transactions.edit');
    $canDelete = $user?->can('transactions.delete');
    $showActions = $canEdit || $canDelete;
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs">
        <thead>
            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                <th class="py-3 px-4">TX ID & Date</th>
                <th class="py-3 px-4">Flow Type</th>
                <th class="py-3 px-4 text-right">Amount (RWF)</th>
                @if($hasRunning ?? false)
                    <th class="py-3 px-4 text-right">Running Balance</th>
                @endif
                <th class="py-3 px-4">Channel</th>
                <th class="py-3 px-4">Origin / Source</th>
                <th class="py-3 px-4">Party</th>
                <th class="py-3 px-4">Notes / Narrative</th>
                @if($showActions)
                    <th class="py-3 px-4 text-right">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse($transactions as $t)
                @php
                    $isCredit = $t->type === 'credit';
                    $method = strtolower($t->method ?? '');
                    $channelPill = match($method) {
                        'cash'         => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300',
                        'bank'         => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300',
                        'momo', 'mobile-money', 'airtel-money' => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950/40 dark:text-purple-300',
                        default        => 'bg-slate-100 text-slate-700 ring-slate-600/20 dark:bg-slate-800 dark:text-slate-300',
                    };
                @endphp
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                    {{-- TX ID & Date --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <a href="{{ route('transactions.show', $t) }}" class="font-mono font-bold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400">
                                #TX-{{ str_pad($t->id, 5, '0', STR_PAD_LEFT) }}
                            </a>
                            <span class="text-[11px] text-slate-400 font-medium">
                                {{ \Carbon\Carbon::parse($t->transaction_date)->format('M d, Y H:i') }}
                            </span>
                        </div>
                    </td>

                    {{-- Flow Type --}}
                    <td class="py-3.5 px-4">
                        @if($isCredit)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                                Credit (IN)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
                                <svg class="w-3 h-3 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                                Debit (OUT)
                            </span>
                        @endif
                    </td>

                    {{-- Amount --}}
                    <td class="py-3.5 px-4 text-right font-mono font-bold whitespace-nowrap text-sm {{ $isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $isCredit ? '+' : '-' }}{{ number_format((float) $t->amount, 2) }}
                    </td>

                    {{-- Running Balance (if available) --}}
                    @if($hasRunning ?? false)
                        <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                            {{ number_format((float) $t->running_balance, 2) }}
                        </td>
                    @endif

                    {{-- Channel --}}
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide ring-1 {{ $channelPill }}">
                            {{ strtoupper(str_replace('-', ' ', $t->method ?? '—')) }}
                        </span>
                    </td>

                    {{-- Origin / Source --}}
                    <td class="py-3.5 px-4">
                        @if($t->is_withdrawal)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                                <svg class="w-3 h-3 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Boss Withdrawal
                            </span>
                        @elseif($t->is_transfer)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                Transfer
                            </span>
                        @elseif($t->sale_id)
                            <a href="{{ route('sales.show', $t->sale_id) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                Sale #{{ $t->sale_id }}
                            </a>
                        @elseif($t->purchase_id)
                            <a href="{{ route('purchases.show', $t->purchase_id) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 dark:text-amber-400 hover:underline">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                PO #{{ $t->purchase_id }}
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1 text-[10px] font-medium text-slate-500 dark:text-slate-400">
                                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Manual
                            </span>
                        @endif
                    </td>

                    {{-- Party --}}
                    <td class="py-3.5 px-4">
                        @if($t->customer)
                            <a href="{{ route('customers.show', $t->customer) }}" class="inline-flex items-center gap-1 font-medium text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                {{ $t->customer->name }}
                            </a>
                        @elseif($t->supplier)
                            <a href="{{ route('suppliers.show', $t->supplier) }}" class="inline-flex items-center gap-1 font-medium text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                {{ $t->supplier->name }}
                            </a>
                        @else
                            <span class="text-slate-400 italic">Internal</span>
                        @endif
                    </td>

                    {{-- Notes --}}
                    <td class="py-3.5 px-4 max-w-xs text-slate-600 dark:text-slate-400">
                        @if($t->notes)
                            <span class="line-clamp-1" title="{{ $t->notes }}">{{ $t->notes }}</span>
                        @else
                            <span class="text-slate-400 italic">—</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    @if($showActions)
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                {{-- Quick View --}}
                                <button type="button"
                                        @click="openShowModal({
                                            id: {{ $t->id }},
                                            date: '{{ \Carbon\Carbon::parse($t->transaction_date)->format('M d, Y H:i') }}',
                                            type: '{{ $t->type }}',
                                            amount: {{ (float) $t->amount }},
                                            method: '{{ strtoupper($t->method ?? '—') }}',
                                            customer: '{{ addslashes($t->customer->name ?? '—') }}',
                                            supplier: '{{ addslashes($t->supplier->name ?? '—') }}',
                                            user: '{{ addslashes($t->user->name ?? 'System') }}',
                                            notes: '{{ addslashes($t->notes ?? 'No notes recorded.') }}',
                                            show_url: '{{ route('transactions.show', $t) }}'
                                        })"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                                        title="Inspect Details">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                {{-- Quick Edit --}}
                                @can('transactions.edit')
                                    <button type="button"
                                            @click="openEditModal({
                                                id: {{ $t->id }},
                                                type: '{{ $t->type }}',
                                                amount: {{ (float) $t->amount }},
                                                transaction_date: '{{ \Carbon\Carbon::parse($t->transaction_date)->toDateString() }}',
                                                method: '{{ $t->method }}',
                                                customer_id: '{{ $t->customer_id ?? '' }}',
                                                supplier_id: '{{ $t->supplier_id ?? '' }}',
                                                notes: '{{ addslashes($t->notes ?? '') }}',
                                                action_url: '{{ route('transactions.update', $t) }}'
                                            })"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                                            title="Edit Transaction">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                @endcan

                                {{-- Delete --}}
                                @can('transactions.delete')
                                    <button type="button"
                                            @click="openDeleteModal('{{ route('transactions.destroy', $t) }}', 'Transaction #TX-{{ str_pad($t->id, 5, '0', STR_PAD_LEFT) }}', '{{ number_format((float) $t->amount, 2) }} RWF')"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                            title="Delete Transaction">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($hasRunning ?? false) ? ($showActions ? 10 : 9) : ($showActions ? 9 : 8) }}" class="py-12 text-center text-slate-400">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No transactions recorded</p>
                        <p class="text-xs text-slate-400 mt-1">Try adjusting your filter criteria or record a new cash movement.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($transactions->count() > 0)
            <tfoot class="bg-slate-50/80 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 font-semibold text-xs text-slate-800 dark:text-slate-200">
                <tr>
                    <td colspan="2" class="py-3 px-4 text-slate-500 uppercase tracking-wider text-[11px]">
                        Page Totals:
                    </td>
                    <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                        {{ number_format((float) ($pageCredits ?? 0) - (float) ($pageDebits ?? 0), 2) }}
                    </td>
                    <td colspan="{{ ($hasRunning ?? false) ? ($showActions ? 7 : 6) : ($showActions ? 6 : 5) }}" class="py-3 px-4">
                        <div class="flex items-center gap-4 text-xs font-mono">
                            <span class="text-emerald-600 dark:text-emerald-400">
                                Inflows (+): {{ number_format((float) ($pageCredits ?? 0), 2) }} RWF
                            </span>
                            <span class="text-rose-600 dark:text-rose-400">
                                Outflows (-): {{ number_format((float) ($pageDebits ?? 0), 2) }} RWF
                            </span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

{{-- Pagination Links --}}
@if($transactions instanceof LengthAwarePaginator && $transactions->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
        {{ $transactions->links() }}
    </div>
@endif

