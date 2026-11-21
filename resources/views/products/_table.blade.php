@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $threshold = (int)($threshold ?? 5);
@endphp

<table class="w-full text-left border-collapse text-xs min-w-[1000px]">
    <thead>
        <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
            <th class="py-3 px-4 w-10 text-center">
                <input type="checkbox" x-model="allSelected" @change="toggleAll()"
                       class="rounded border-slate-300 dark:border-slate-700 text-teal-600 focus:ring-teal-500 dark:bg-slate-800">
            </th>
            <th class="py-3 px-4">Product Details</th>
            <th class="py-3 px-4">Category</th>
            <th class="py-3 px-4 text-right">Cost (WAC)</th>
            <th class="py-3 px-4 text-right">Selling Price</th>
            <th class="py-3 px-4 text-right">Margin</th>
            <th class="py-3 px-4 text-right">In</th>
            <th class="py-3 px-4 text-right">Out</th>
            <th class="py-3 px-4 text-right">Stock Balance</th>
            <th class="py-3 px-4 text-left">Last Moved</th>
            <th class="py-3 px-4 text-right">Actions</th>
        </tr>
    </thead>

    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
        @forelse ($products as $p)
            @php
                $in   = (float)($p->qty_in ?? 0);
                $out  = (float)($p->qty_out ?? 0);
                $ret  = (float)($p->qty_returned ?? 0);
                $stk  = max(0, $in - $out);
                $low  = $stk <= $threshold && $stk > 0;
                $zero = $stk <= 0;

                $cost   = (float)($p->cost_price ?? 0);
                $price  = (float)($p->price ?? 0);
                $margin = $price > 0 ? (($price - $cost) / $price) * 100 : 0;

                $last = $p->last_moved_at ? \Carbon\Carbon::parse($p->last_moved_at)->diffForHumans() : '—';

                $cat   = $p->category ?? null;
                $catOk = $cat && (($cat->is_active ?? true) && in_array($cat->kind ?? 'product', ['product', 'both']));
                $dot   = $cat->color ?? '#0d9488';
            @endphp

            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition"
                :class="selectedIds.includes({{ $p->id }}) ? 'bg-teal-50/40 dark:bg-teal-950/20' : ''"
                data-product-id="{{ $p->id }}">

                {{-- Checkbox --}}
                <td class="py-3.5 px-4 text-center">
                    <input type="checkbox" value="{{ $p->id }}"
                           x-model.number="selectedIds"
                           @change="updateAllSelected()"
                           class="rounded border-slate-300 dark:border-slate-700 text-teal-600 focus:ring-teal-500 dark:bg-slate-800">
                </td>

                {{-- Name & SKU --}}
                <td class="py-3.5 px-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-xs shrink-0 ring-1 ring-slate-200 dark:ring-slate-700">
                            {{ strtoupper(substr($p->name, 0, 2)) }}
                        </div>
                        <div>
                            @can('products.view')
                                <a href="{{ route('products.show', $p) }}" class="font-bold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition">
                                    {{ $p->name }}
                                </a>
                            @else
                                <span class="font-bold text-slate-900 dark:text-white">{{ $p->name }}</span>
                            @endcan

                            <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                <span class="font-mono">#{{ $p->sku ?: ('PRD-' . $p->id) }}</span>
                                @if($ret > 0)
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                        {{ $fmt0($ret) }} returned
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>

                {{-- Category --}}
                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                    @if($cat)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700">
                            <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $dot }}"></span>
                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ $cat->name }}</span>
                            @if($cat->code)
                                <span class="text-[10px] text-slate-400 font-mono">({{ $cat->code }})</span>
                            @endif
                        </span>
                    @else
                        <span class="text-slate-400 italic">Uncategorized</span>
                    @endif
                </td>

                {{-- Cost (WAC) --}}
                <td class="py-3.5 px-4 text-right font-mono text-slate-600 dark:text-slate-400">
                    {{ $fmt2($cost) }}
                </td>

                {{-- Selling Price --}}
                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                    {{ $fmt2($price) }}
                </td>

                {{-- Margin % --}}
                <td class="py-3.5 px-4 text-right font-mono">
                    <span class="font-semibold {{ $margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $fmt2($margin) }}%
                    </span>
                </td>

                {{-- In --}}
                <td class="py-3.5 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-medium">
                    {{ $fmt0($in) }}
                </td>

                {{-- Out --}}
                <td class="py-3.5 px-4 text-right font-mono text-slate-600 dark:text-slate-400">
                    {{ $fmt0($out) }}
                </td>

                {{-- Stock Balance --}}
                <td class="py-3.5 px-4 text-right">
                    <div class="inline-flex items-center gap-1.5">
                        <span class="font-mono font-bold text-sm {{ $zero ? 'text-rose-600 dark:text-rose-400' : ($low ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white') }}" data-stock>
                            {{ $fmt0($stk) }}
                        </span>
                        <span class="text-[11px] text-slate-400">{{ $p->unit ?: 'pcs' }}</span>

                        @if($zero)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-400">
                                Out
                            </span>
                        @elseif($low)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400">
                                <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                Low
                            </span>
                        @endif
                    </div>
                </td>

                {{-- Last Moved --}}
                <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 text-[11px]">
                    {{ $last }}
                </td>

                {{-- Actions --}}
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @can('products.view')
                            <a href="{{ route('products.show', $p) }}"
                               class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                               title="Inspect Product Dossier">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        @endcan

                        @can('products.edit')
                            <a href="{{ route('products.edit', $p) }}"
                               class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                               title="Edit Product">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>

                            <button type="button"
                                    @click="$store.quickAdjust.open({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $stk }})"
                                    class="p-1.5 rounded-lg text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40 transition"
                                    title="Quick Stock Adjustment">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </button>
                        @endcan

                        @can('products.delete')
                            <button type="button"
                                    @click="openSingleDeleteModal('{{ route('products.destroy', $p) }}', '{{ addslashes($p->name) }}', '{{ $p->sku ?: ('PRD-' . $p->id) }}', {{ $stk }})"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                    title="Delete Product">
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
                <td colspan="11" class="py-12 text-center text-slate-400">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No products found</p>
                    <p class="text-xs text-slate-400 mt-1">Try refining your filter criteria or create a new product.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
