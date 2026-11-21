@php
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                <th class="py-3 px-4">PO & Date</th>
                <th class="py-3 px-4">Supplier</th>
                <th class="py-3 px-4 text-right">Total Amount</th>
                <th class="py-3 px-4 text-right">Paid</th>
                <th class="py-3 px-4 text-right">Balance Due</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center">Channel</th>
                <th class="py-3 px-4">Reference</th>
                <th class="py-3 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            @forelse ($purchases as $p)
                @php
                    $pdate = $p->purchase_date ?? $p->created_at;
                    $total = (float) ($p->total_amount ?? 0);
                    $paid  = (float) ($p->amount_paid ?? 0);
                    $bal   = max(0, $total - $paid);
                    $status = strtolower($p->status ?? ($bal > 0 ? 'pending' : 'completed'));
                    $channel = strtolower($p->payment_channel ?? 'cash');

                    $returnsCount = (int) ($p->returns_count ?? optional($p->returns)->count() ?? 0);
                    $hasReturns   = $returnsCount > 0;

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
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                    {{-- PO & Date --}}
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <div>
                                <a href="{{ route('purchases.show', $p) }}"
                                   class="font-mono font-bold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition">
                                    #PO-{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $pdate ? \Carbon\Carbon::parse($pdate)->format('d M Y') : '—' }}
                                </div>
                            </div>
                            @if($hasReturns)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800"
                                      title="{{ $returnsCount }} return(s) processed">
                                    {{ $returnsCount }}x Return
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Supplier --}}
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2.5 min-w-[180px]">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                                {{ strtoupper(substr(optional($p->supplier)->name ?? 'SUP', 0, 2)) }}
                            </div>
                            <div>
                                <div class="font-semibold text-slate-900 dark:text-white">
                                    {{ optional($p->supplier)->name ?? 'Walk-in Supplier' }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ optional($p->supplier)->phone ?: (optional($p->supplier)->email ?: 'Supplier #' . ($p->supplier_id ?? '—')) }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Total Amount --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-bold text-slate-900 dark:text-white">
                            {{ $fmt2($total) }} <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                        </div>
                        @if((float)($p->tax ?? 0) > 0 || (float)($p->discount ?? 0) > 0)
                            <div class="text-[10px] text-slate-400 font-mono">
                                Sub: {{ $fmt0($p->subtotal) }}
                                @if((float)($p->tax ?? 0) > 0)+T:{{ $fmt0($p->tax) }}@endif
                                @if((float)($p->discount ?? 0) > 0)-D:{{ $fmt0($p->discount) }}@endif
                            </div>
                        @endif
                    </td>

                    {{-- Paid --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ $fmt2($paid) }} <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                        </div>
                    </td>

                    {{-- Balance Due --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-bold {{ $bal > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ $fmt2($bal) }} <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                        </div>
                    </td>

                    {{-- Status Badge --}}
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $statusBadge }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $status === 'completed' ? 'bg-emerald-500' : ($status === 'partial' ? 'bg-amber-500' : ($status === 'cancelled' ? 'bg-slate-400' : 'bg-rose-500')) }}"></span>
                            <span>{{ ucfirst($status) }}</span>
                        </span>
                    </td>

                    {{-- Payment Channel Badge --}}
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wider border {{ $channelBadge }}">
                            {{ $channel }}
                        </span>
                    </td>

                    {{-- Reference --}}
                    <td class="py-3 px-4">
                        @if($p->method)
                            <span class="font-mono text-slate-700 dark:text-slate-300 max-w-[140px] truncate block" title="{{ $p->method }}">
                                {{ $p->method }}
                            </span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            {{-- View Details --}}
                            <a href="{{ route('purchases.show', $p) }}"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-slate-800 transition"
                               title="View Order Details">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>

                            {{-- Edit --}}
                            @can('purchases.edit')
                                <a href="{{ route('purchases.edit', $p) }}"
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-slate-800 transition"
                                   title="Edit Purchase">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            @endcan

                            {{-- Invoice PDF --}}
                            <a href="{{ route('purchases.invoice', $p) }}"
                               target="_blank"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-slate-800 transition"
                               title="Download PDF Invoice">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </a>

                            {{-- Delete with Modal --}}
                            @can('purchases.delete')
                                <button type="button"
                                        @click="confirmDelete('{{ route('purchases.destroy', $p) }}', '{{ $p->id }}', '{{ addslashes(optional($p->supplier)->name ?? 'Supplier') }}', '{{ $fmt2($total) }} RWF')"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition"
                                        title="Delete Purchase">
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
                        <div class="max-w-sm mx-auto space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No purchases found</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Try adjusting your search query, supplier, or date range filters.
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

