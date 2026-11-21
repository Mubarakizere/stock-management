@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $user = auth()->user();
    $canEdit   = $user?->can('expenses.edit');
    $canDelete = $user?->can('expenses.delete');
    $showActions = $canEdit || $canDelete;
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs">
        <thead>
            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                <th class="py-3 px-4">Expense Ref</th>
                <th class="py-3 px-4">Date</th>
                <th class="py-3 px-4">Category</th>
                <th class="py-3 px-4">Vendor / Payee</th>
                <th class="py-3 px-4">Channel</th>
                <th class="py-3 px-4 text-right">Amount (RWF)</th>
                <th class="py-3 px-4">Description / Note</th>
                @if($showActions)
                    <th class="py-3 px-4 text-right">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse($expenses as $expense)
                @php
                    $method = strtolower($expense->method ?? '');
                    $channelPill = match($method) {
                        'cash'         => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300',
                        'bank'         => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300',
                        'momo', 'mobile-money', 'airtel-money' => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950/40 dark:text-purple-300',
                        default        => 'bg-slate-100 text-slate-700 ring-slate-600/20 dark:bg-slate-800 dark:text-slate-300',
                    };
                @endphp
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                    {{-- Ref --}}
                    <td class="py-3.5 px-4">
                        <div class="flex flex-col">
                            <a href="{{ route('expenses.show', $expense) }}" class="font-mono font-bold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400">
                                #EXP-{{ str_pad($expense->id, 5, '0', STR_PAD_LEFT) }}
                            </a>
                            @if($expense->reference)
                                <span class="font-mono text-[10px] text-slate-400">Ref: {{ $expense->reference }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- Date --}}
                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-700 dark:text-slate-300 font-medium">
                        {{ optional($expense->date)->format('M d, Y') ?? '—' }}
                    </td>

                    {{-- Category --}}
                    <td class="py-3.5 px-4">
                        @if($expense->category)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                {{ $expense->category->name }}
                            </span>
                        @else
                            <span class="text-slate-400 italic">Uncategorized</span>
                        @endif
                    </td>

                    {{-- Payee / Supplier --}}
                    <td class="py-3.5 px-4">
                        @if($expense->supplier)
                            <a href="{{ route('suppliers.show', $expense->supplier) }}" class="inline-flex items-center gap-1 font-medium text-slate-800 dark:text-slate-200 hover:text-teal-600 dark:hover:text-teal-400">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span>{{ $expense->supplier->name }}</span>
                            </a>
                        @else
                            <span class="text-slate-400 italic">Internal / Disbursed</span>
                        @endif
                    </td>

                    {{-- Method / Channel --}}
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide ring-1 {{ $channelPill }}">
                            {{ strtoupper(str_replace('-', ' ', $expense->method ?? '—')) }}
                        </span>
                    </td>

                    {{-- Amount --}}
                    <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap text-sm">
                        {{ number_format((float) $expense->amount, 2) }}
                    </td>

                    {{-- Description --}}
                    <td class="py-3.5 px-4 max-w-xs text-slate-600 dark:text-slate-400">
                        @if($expense->note)
                            <span class="line-clamp-1" title="{{ $expense->note }}">
                                {{ $expense->note }}
                            </span>
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
                                            id: {{ $expense->id }},
                                            date: '{{ optional($expense->date)->format('M d, Y') }}',
                                            amount: {{ (float) $expense->amount }},
                                            category: '{{ addslashes($expense->category->name ?? 'Uncategorized') }}',
                                            supplier: '{{ addslashes($expense->supplier->name ?? 'Internal / None') }}',
                                            method: '{{ strtoupper($expense->method ?? '—') }}',
                                            reference: '{{ addslashes($expense->reference ?? '—') }}',
                                            note: '{{ addslashes($expense->note ?? 'No notes provided.') }}',
                                            creator: '{{ addslashes($expense->creator->name ?? 'System') }}',
                                            created_at: '{{ optional($expense->created_at)->format('M d, Y H:i') }}',
                                            show_url: '{{ route('expenses.show', $expense) }}'
                                        })"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                                        title="Quick View Details">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                {{-- Quick Edit --}}
                                @can('expenses.edit')
                                    <button type="button"
                                            @click="openEditModal({
                                                id: {{ $expense->id }},
                                                date: '{{ optional($expense->date)->toDateString() }}',
                                                amount: {{ (float) $expense->amount }},
                                                category_id: '{{ $expense->category_id }}',
                                                supplier_id: '{{ $expense->supplier_id ?? '' }}',
                                                method: '{{ $expense->method }}',
                                                reference: '{{ addslashes($expense->reference ?? '') }}',
                                                note: '{{ addslashes($expense->note ?? '') }}',
                                                action_url: '{{ route('expenses.update', $expense) }}'
                                            })"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                                            title="Edit Expense">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                @endcan

                                {{-- Delete --}}
                                @can('expenses.delete')
                                    <button type="button"
                                            @click="openDeleteModal('{{ route('expenses.destroy', $expense) }}', 'Expense #EXP-{{ str_pad($expense->id, 5, '0', STR_PAD_LEFT) }}', '{{ number_format((float) $expense->amount, 2) }} RWF')"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                            title="Delete Expense">
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
                    <td colspan="{{ $showActions ? 8 : 7 }}" class="py-12 text-center text-slate-400">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No expense records found</p>
                        <p class="text-xs text-slate-400 mt-1">Try adjusting your filter criteria or record a new operational expense.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($expenses->count() > 0)
            <tfoot class="bg-slate-50/80 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 font-semibold text-xs text-slate-800 dark:text-slate-200">
                <tr>
                    <td colspan="5" class="py-3 px-4 text-right text-slate-500 uppercase tracking-wider text-[11px]">
                        Page Total Subtotal:
                    </td>
                    <td class="py-3 px-4 text-right font-mono font-bold text-teal-600 dark:text-teal-400 text-sm">
                        {{ number_format((float) $expenses->getCollection()->sum('amount'), 2) }}
                    </td>
                    <td colspan="{{ $showActions ? 2 : 1 }}"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

{{-- Pagination Links --}}
@if($expenses instanceof LengthAwarePaginator && $expenses->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
        {{ $expenses->links() }}
    </div>
@endif

