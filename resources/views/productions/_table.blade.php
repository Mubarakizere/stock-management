@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                <th class="py-3 px-4 w-10 text-center">
                    <input type="checkbox"
                           @change="toggleSelectAll($event.target.checked)"
                           :checked="allSelected"
                           class="rounded border-slate-300 dark:border-slate-600 text-teal-600 focus:ring-teal-500/20 dark:bg-slate-800 cursor-pointer transition">
                </th>
                <th class="py-3 px-4">Batch Lot / ID</th>
                <th class="py-3 px-4">Manufactured Product</th>
                <th class="py-3 px-4 text-right">Yield &amp; Output</th>
                <th class="py-3 px-4 text-center">Batch Shelf-Life</th>
                <th class="py-3 px-4 text-center">BOM Ingredients</th>
                <th class="py-3 px-4 text-right">Batch Cost (Unit Cost)</th>
                <th class="py-3 px-4 text-left">Execution Date</th>
                <th class="py-3 px-4 text-left">Operator</th>
                <th class="py-3 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            @forelse($productions as $prod)
                @php
                    $product = $prod->product;
                    $unit = $product->unit ?? 'pcs';
                    $matCount = $prod->materials ? $prod->materials->count() : 0;
                    $batchCost = $prod->totalMaterialCost();
                    $unitCost = (float)($prod->unit_cost ?: ($prod->quantity > 0 ? $batchCost / $prod->quantity : 0));
                    $yieldBadge = $prod->yieldStatusBadge();
                    $expiryBadge = $prod->expiryStatusBadge();
                    $batchNum = $prod->batch_number ?: 'BATCH-' . str_pad($prod->id, 5, '0', STR_PAD_LEFT);
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                    {{-- Row Checkbox --}}
                    <td class="py-3 px-4 text-center">
                        <input type="checkbox"
                               value="{{ $prod->id }}"
                               x-model="selectedIds"
                               class="rounded border-slate-300 dark:border-slate-600 text-teal-600 focus:ring-teal-500/20 dark:bg-slate-800 cursor-pointer transition">
                    </td>

                    {{-- Batch Lot & Run ID --}}
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <a href="{{ route('productions.show', $prod) }}"
                               class="inline-flex items-center gap-1 font-mono text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline">
                                <svg class="w-3.5 h-3.5 text-teal-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                </svg>
                                <span>{{ $batchNum }}</span>
                            </a>
                            <span class="text-[10px] font-mono text-slate-400">
                                #PRD-{{ str_pad($prod->id, 5, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                    </td>

                    {{-- Product Name + Notes --}}
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-950/60 border border-teal-200/80 dark:border-teal-800/80 flex items-center justify-center text-teal-600 dark:text-teal-400 font-bold text-xs shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div>
                                <a href="{{ route('productions.show', $prod) }}"
                                   class="font-semibold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition">
                                    {{ $product->name ?? '—' }}
                                </a>
                                @if($prod->notes)
                                    <div class="text-[11px] text-slate-400 truncate max-w-xs mt-0.5">
                                        {{ $prod->notes }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Yield & Output Volume --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-bold text-slate-900 dark:text-white text-sm">
                            +{{ $fmt2($prod->quantity) }} <span class="text-[11px] font-sans font-normal text-slate-400">{{ $unit }}</span>
                        </div>
                        <div class="flex items-center justify-end gap-1 mt-0.5">
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono font-bold border {{ $yieldBadge['class'] }}">
                                {{ $yieldBadge['label'] }}
                            </span>
                        </div>
                    </td>

                    {{-- Shelf-Life & Expiration Status --}}
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border shadow-xs {{ $expiryBadge['class'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $expiryBadge['dot'] }}"></span>
                            <span>{{ $expiryBadge['label'] }}</span>
                        </span>
                        @if($prod->expiry_date)
                            <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                EXP: {{ $prod->expiry_date->format('M d, Y') }}
                            </div>
                        @endif
                    </td>

                    {{-- BOM Ingredients --}}
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-mono">
                            <svg class="w-3 h-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                            <span>{{ $matCount }} {{ Str::plural('Material', $matCount) }}</span>
                        </span>
                    </td>

                    {{-- Batch Material Cost & Unit Cost --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-bold text-slate-900 dark:text-white">
                            {{ $fmt2($batchCost) }} <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                        </div>
                        <div class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                            {{ $fmt2($unitCost) }} RWF/{{ $unit }}
                        </div>
                    </td>

                    {{-- Execution Date --}}
                    <td class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                        <div>{{ $prod->produced_at ? $prod->produced_at->format('M d, Y') : '—' }}</div>
                        <div class="text-[10px] font-mono text-slate-400">{{ $prod->produced_at ? $prod->produced_at->format('H:i') : '' }}</div>
                    </td>

                    {{-- Operator --}}
                    <td class="py-3 px-4 whitespace-nowrap text-slate-700 dark:text-slate-300">
                        {{ $prod->user->name ?? 'System' }}
                    </td>

                    {{-- Actions --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1.5">
                            {{-- View Details --}}
                            <a href="{{ route('productions.show', $prod) }}"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-slate-800 transition"
                               title="Inspect Batch Dossier">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>

                            {{-- Reverse/Delete Run --}}
                            @can('products.edit')
                                <button type="button"
                                        @click="confirmReverse('{{ route('productions.destroy', $prod) }}', '{{ $prod->id }}', '{{ addslashes($product->name ?? 'Unknown') }}', {{ $prod->quantity }}, '{{ $unit }}')"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition"
                                        title="Reverse Production Run">
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
                    <td colspan="10" class="py-12 text-center">
                        <div class="max-w-sm mx-auto space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No production batches found</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Try adjusting your filters or record a new production run.
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
