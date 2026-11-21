<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                <th scope="col" class="py-3.5 pl-4 sm:pl-6 pr-3">Voucher ID</th>
                <th scope="col" class="px-3 py-3.5">Date</th>
                <th scope="col" class="px-3 py-3.5">Flow Direction</th>
                <th scope="col" class="px-3 py-3.5 text-right">Amount (RWF)</th>
                <th scope="col" class="px-3 py-3.5">Counterparty</th>
                <th scope="col" class="px-3 py-3.5">Description</th>
                <th scope="col" class="px-3 py-3.5">Linked Txn</th>
                <th scope="col" class="px-3 py-3.5">Operator</th>
                <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs text-slate-700 dark:text-slate-300">
            @forelse($records as $item)
                @php
                    $isCredit = $item->type === 'credit';
                    $voucherCode = '#DC-' . str_pad($item->id, 5, '0', STR_PAD_LEFT);
                    $formattedDate = \Carbon\Carbon::parse($item->date);
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                    {{-- Voucher ID --}}
                    <td class="py-3.5 pl-4 sm:pl-6 pr-3 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $isCredit ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            <button type="button"
                                    @click="openShowModal({{ json_encode([
                                        'id' => $item->id,
                                        'code' => $voucherCode,
                                        'type' => $item->type,
                                        'amount' => number_format($item->amount, 2),
                                        'raw_amount' => (float)$item->amount,
                                        'date' => $item->date,
                                        'description' => $item->description,
                                        'customer' => $item->customer ? ['id' => $item->customer->id, 'name' => $item->customer->name] : null,
                                        'supplier' => $item->supplier ? ['id' => $item->supplier->id, 'name' => $item->supplier->name] : null,
                                        'transaction_id' => $item->transaction_id,
                                        'user' => $item->user ? ['name' => $item->user->name, 'email' => $item->user->email] : null,
                                        'created_at' => $item->created_at ? $item->created_at->format('M d, Y H:i') : null,
                                        'updated_at' => $item->updated_at ? $item->updated_at->format('M d, Y H:i') : null,
                                    ]) }})"
                                    class="hover:text-teal-600 dark:hover:text-teal-400 transition hover:underline">
                                {{ $voucherCode }}
                            </button>
                        </div>
                    </td>

                    {{-- Date --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        <div class="font-medium text-slate-800 dark:text-slate-200">
                            {{ $formattedDate->format('M d, Y') }}
                        </div>
                        <div class="text-[10px] text-slate-400 dark:text-slate-500">
                            {{ $formattedDate->isToday() ? 'Today' : ($formattedDate->isYesterday() ? 'Yesterday' : $formattedDate->diffForHumans()) }}
                        </div>
                    </td>

                    {{-- Flow Direction --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        @if($isCredit)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60">
                                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                                Credit (IN)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60">
                                <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                                Debit (OUT)
                            </span>
                        @endif
                    </td>

                    {{-- Amount --}}
                    <td class="px-3 py-3.5 whitespace-nowrap text-right font-mono font-bold text-sm">
                        @if($isCredit)
                            <span class="text-emerald-600 dark:text-emerald-400">
                                + {{ number_format($item->amount, 2) }}
                            </span>
                        @else
                            <span class="text-rose-600 dark:text-rose-400">
                                - {{ number_format($item->amount, 2) }}
                            </span>
                        @endif
                    </td>

                    {{-- Counterparty --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        @if($item->customer)
                            <a href="{{ route('customers.show', $item->customer->id) }}"
                               class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800/60 hover:bg-blue-100 transition">
                                <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="max-w-[130px] truncate">{{ $item->customer->name }}</span>
                            </a>
                        @elseif($item->supplier)
                            <a href="{{ route('suppliers.show', $item->supplier->id) }}"
                               class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800/60 hover:bg-purple-100 transition">
                                <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span class="max-w-[130px] truncate">{{ $item->supplier->name }}</span>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                General Ledger
                            </span>
                        @endif
                    </td>

                    {{-- Description --}}
                    <td class="px-3 py-3.5 max-w-[200px]">
                        @if($item->description)
                            <span class="truncate block text-slate-600 dark:text-slate-400" title="{{ $item->description }}">
                                {{ $item->description }}
                            </span>
                        @else
                            <span class="text-slate-400 dark:text-slate-600">—</span>
                        @endif
                    </td>

                    {{-- Linked Txn --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        @if($item->transaction_id)
                            @can('transactions.view')
                                <a href="{{ route('transactions.show', $item->transaction_id) }}"
                                   class="inline-flex items-center gap-1 font-mono font-medium text-xs text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 hover:underline">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    #TX-{{ str_pad($item->transaction_id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            @else
                                <span class="font-mono text-xs text-slate-500">
                                    #TX-{{ str_pad($item->transaction_id, 5, '0', STR_PAD_LEFT) }}
                                </span>
                            @endcan
                        @else
                            <span class="text-slate-400 dark:text-slate-600 font-mono text-xs">—</span>
                        @endif
                    </td>

                    {{-- Operator --}}
                    <td class="px-3 py-3.5 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-bold flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($item->user->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="text-xs text-slate-700 dark:text-slate-300 max-w-[100px] truncate">
                                {{ $item->user->name ?? 'System' }}
                            </span>
                        </div>
                    </td>

                    {{-- Actions --}}
                    <td class="py-3.5 pl-3 pr-4 sm:pr-6 whitespace-nowrap text-right text-xs">
                        <div class="flex items-center justify-end gap-1.5">
                            {{-- Quick View --}}
                            <button type="button"
                                    @click="openShowModal({{ json_encode([
                                        'id' => $item->id,
                                        'code' => $voucherCode,
                                        'type' => $item->type,
                                        'amount' => number_format($item->amount, 2),
                                        'raw_amount' => (float)$item->amount,
                                        'date' => $item->date,
                                        'description' => $item->description,
                                        'customer' => $item->customer ? ['id' => $item->customer->id, 'name' => $item->customer->name] : null,
                                        'supplier' => $item->supplier ? ['id' => $item->supplier->id, 'name' => $item->supplier->name] : null,
                                        'transaction_id' => $item->transaction_id,
                                        'user' => $item->user ? ['name' => $item->user->name, 'email' => $item->user->email] : null,
                                        'created_at' => $item->created_at ? $item->created_at->format('M d, Y H:i') : null,
                                        'updated_at' => $item->updated_at ? $item->updated_at->format('M d, Y H:i') : null,
                                    ]) }})"
                                    title="Quick View"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>

                            {{-- Edit --}}
                            @can('debits-credits.edit')
                                <button type="button"
                                        @click="openEditModal({{ json_encode([
                                            'id' => $item->id,
                                            'type' => $item->type,
                                            'amount' => (float)$item->amount,
                                            'date' => $item->date,
                                            'customer_id' => $item->customer_id ?? '',
                                            'supplier_id' => $item->supplier_id ?? '',
                                            'transaction_id' => $item->transaction_id ?? '',
                                            'description' => $item->description ?? '',
                                            'action_url' => route('debits-credits.update', $item->id)
                                        ]) }})"
                                        title="Edit Entry"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-teal-600 hover:bg-teal-50 dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-950/40 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            @endcan

                            {{-- Delete --}}
                            @can('debits-credits.delete')
                                <button type="button"
                                        @click="openDeleteModal('{{ route('debits-credits.destroy', $item->id) }}', '{{ $voucherCode }}', '{{ number_format($item->amount, 2) }} RWF')"
                                        title="Delete Entry"
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No entries match your search</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Try adjusting your filters, dates, or search keywords.</p>
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
        @if($records->count() > 0)
            <tfoot class="bg-slate-50/90 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300">
                <tr>
                    <td colspan="3" class="py-3 pl-4 sm:pl-6 pr-3 uppercase tracking-wider text-[11px] text-slate-500 dark:text-slate-400">
                        Page Totals ({{ $records->count() }} items)
                    </td>
                    <td class="px-3 py-3 text-right font-mono text-xs whitespace-nowrap">
                        <div class="space-y-0.5">
                            <div class="text-emerald-600 dark:text-emerald-400">
                                In: +{{ number_format($pageCredits, 2) }}
                            </div>
                            <div class="text-rose-600 dark:text-rose-400">
                                Out: -{{ number_format($pageDebits, 2) }}
                            </div>
                            <div class="pt-1 border-t border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                Net: {{ number_format($pageCredits - $pageDebits, 2) }}
                            </div>
                        </div>
                    </td>
                    <td colspan="5" class="px-3 py-3 text-right text-[11px] text-slate-500 dark:text-slate-400">
                        Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ $records->total() }} entries
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

{{-- Pagination Links --}}
@if($records->hasPages())
    <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
        {{ $records->links() }}
    </div>
@endif

