@php
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                <th class="py-3 px-4">Date & Time</th>
                <th class="py-3 px-4">Inventory Item</th>
                <th class="py-3 px-4 text-center">Direction</th>
                <th class="py-3 px-4 text-right">Quantity</th>
                <th class="py-3 px-4 text-right">Unit Cost</th>
                <th class="py-3 px-4 text-right">Total Cost</th>
                <th class="py-3 px-4">Reference / Memo</th>
                <th class="py-3 px-4">Recorded By</th>
                <th class="py-3 px-4">Transaction Source</th>
                @can('stock.delete')
                    <th class="py-3 px-4 text-right">Actions</th>
                @endcan
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            @forelse ($movements as $m)
                @php
                    $isFlowIn  = $m->isFlowIn();
                    $product   = $m->product;
                    $unit      = $product->unit ?? 'pcs';
                    $isRawMat  = ($product->type ?? '') === 'raw_material';
                    $prodRoute = $product ? ($isRawMat ? route('raw-materials.show', $product) : route('products.show', $product)) : null;

                    $source = $m->sourceDetails();
                    $ref    = $m->referenceText();
                    $isTrashed = $m->trashed();
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors {{ $isTrashed ? 'bg-rose-50/40 dark:bg-rose-950/20' : '' }}">
                    {{-- Date & Time --}}
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <div>
                                <div class="font-mono font-medium text-slate-800 dark:text-slate-200">
                                    {{ $m->created_at ? $m->created_at->format('d M Y, H:i') : '—' }}
                                </div>
                                <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                    <span>#MV-{{ str_pad($m->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <span>·</span>
                                    <span>{{ $m->created_at ? $m->created_at->diffForHumans() : '' }}</span>
                                </div>
                            </div>
                            @if($isTrashed)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                    Trashed
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Inventory Item --}}
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2.5 min-w-[200px]">
                            <div class="w-8 h-8 rounded-lg {{ $isRawMat ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20' }} flex items-center justify-center shrink-0">
                                @if($isRawMat)
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                @if($prodRoute)
                                    <a href="{{ $prodRoute }}"
                                       class="font-semibold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition">
                                        {{ $product->name ?? 'Unknown Item' }}
                                    </a>
                                @else
                                    <span class="font-semibold text-slate-900 dark:text-white">
                                        {{ $product->name ?? 'Unknown Item' }}
                                    </span>
                                @endif
                                <div class="text-[11px] font-mono text-slate-400 mt-0.5">
                                    {{ $isRawMat ? 'Raw Material' : 'Finished Good' }} · Unit: {{ $unit }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Direction Badge --}}
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        @if($isFlowIn)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                </svg>
                                <span>INFLOW</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200/60 dark:border-rose-800/60">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                </svg>
                                <span>OUTFLOW</span>
                            </span>
                        @endif
                    </td>

                    {{-- Quantity --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-bold text-sm {{ $isFlowIn ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                            {{ $isFlowIn ? '+' : '-' }}{{ $fmt2($m->quantity) }}
                        </div>
                        <span class="text-[10px] text-slate-400 font-mono">{{ $unit }}</span>
                    </td>

                    {{-- Unit Cost --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-slate-600 dark:text-slate-300">
                        {{ isset($m->unit_cost) ? $fmt2($m->unit_cost) : '—' }}
                        <span class="text-[10px] text-slate-400">RWF</span>
                    </td>

                    {{-- Total Cost --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold text-slate-900 dark:text-white">
                        {{ isset($m->total_cost) ? $fmt2($m->total_cost) : '—' }}
                        <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                    </td>

                    {{-- Reference / Memo --}}
                    <td class="py-3 px-4">
                        @if($ref)
                            <div class="max-w-[18rem] truncate font-medium text-slate-700 dark:text-slate-300" title="{{ $ref }}">
                                {{ $ref }}
                            </div>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>

                    {{-- Recorded By --}}
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <span class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-[10px] uppercase">
                                {{ substr($m->user->name ?? 'SYS', 0, 2) }}
                            </span>
                            <span class="text-slate-700 dark:text-slate-300 font-medium">
                                {{ $m->user->name ?? 'System' }}
                            </span>
                        </div>
                    </td>

                    {{-- Transaction Source --}}
                    <td class="py-3 px-4 whitespace-nowrap">
                        @if($source['link'])
                            <a href="{{ $source['link'] }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-teal-500/50 hover:text-teal-600 dark:hover:text-teal-400 shadow-2xs transition">
                                <span>{{ $source['label'] }}</span>
                                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                {{ $source['label'] }}
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    @can('stock.delete')
                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                @if($isTrashed)
                                    {{-- Restore Button --}}
                                    <form action="{{ route('stock.history.restore', $m->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-1.5 rounded-lg text-teal-600 hover:text-teal-700 hover:bg-teal-50 dark:hover:bg-slate-800 transition"
                                                title="Restore Stock Movement">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                    </form>

                                    {{-- Permanent Purge Button --}}
                                    <button type="button"
                                            @click="confirmForceDelete('{{ route('stock.history.forceDestroy', $m->id) }}', '{{ $m->id }}', '{{ addslashes($product->name ?? 'Item') }}')"
                                            class="p-1.5 rounded-lg text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-slate-800 transition"
                                            title="Permanently Purge Movement Record">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @else
                                    {{-- Soft Delete Button --}}
                                    <button type="button"
                                            @click="confirmSoftDelete('{{ route('stock.history.destroy', $m) }}', '{{ $m->id }}', '{{ addslashes($product->name ?? 'Item') }}', '{{ $isFlowIn ? 'IN' : 'OUT' }}', '{{ $fmt2($m->quantity) }} {{ $unit }}')"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition"
                                            title="Move Record to Trash">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr>
                    <td colspan="{{ auth()->user()->can('stock.delete') ? '10' : '9' }}" class="py-12 text-center">
                        <div class="max-w-sm mx-auto space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No stock movements found</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                There are no transaction records matching your current filter parameters.
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

