@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $threshold = (int)($threshold ?? config('inventory.low_stock', 5));
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
                <th class="py-3 px-4">Raw Material</th>
                <th class="py-3 px-4">Category</th>
                <th class="py-3 px-4 text-right">Cost Price (WAC)</th>
                <th class="py-3 px-4 text-right">Current Stock</th>
                <th class="py-3 px-4 text-center">BOM Recipes</th>
                <th class="py-3 px-4 text-right">Inventory Value</th>
                <th class="py-3 px-4 text-right">Last Activity</th>
                <th class="py-3 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            @forelse ($products as $p)
                @php
                    $in    = (float)($p->qty_in ?? 0);
                    $out   = (float)($p->qty_out ?? 0);
                    $stk   = max(0, $in - $out);
                    $isZero = $stk <= 0;
                    $isLow  = $stk > 0 && $stk <= $threshold;

                    $cost  = (float)($p->cost_price ?: $p->price ?: 0);
                    $val   = $stk * $cost;
                    $unit  = $p->unit ?? 'kg';

                    $cat   = $p->category ?? null;
                    $catColor = $cat->color ?? '#0d9488';
                    $recipeCount = $p->usedInRecipes ? $p->usedInRecipes->count() : 0;
                    $lastMoved = $p->last_moved_at ? \Carbon\Carbon::parse($p->last_moved_at)->diffForHumans() : '—';
                @endphp

                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                    {{-- Row Checkbox --}}
                    <td class="py-3 px-4 text-center">
                        <input type="checkbox"
                               value="{{ $p->id }}"
                               x-model="selectedIds"
                               class="rounded border-slate-300 dark:border-slate-600 text-teal-600 focus:ring-teal-500/20 dark:bg-slate-800 cursor-pointer transition">
                    </td>

                    {{-- Raw Material Name & SKU/ID --}}
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-950/60 border border-teal-200/80 dark:border-teal-800/80 flex items-center justify-center text-teal-600 dark:text-teal-400 font-mono text-xs font-bold shrink-0">
                                RM
                            </div>
                            <div>
                                <a href="{{ route('raw-materials.show', $p) }}"
                                   class="font-semibold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition">
                                    {{ $p->name }}
                                </a>
                                <div class="text-[11px] font-mono text-slate-400">
                                    #RM-{{ str_pad($p->id, 4, '0', STR_PAD_LEFT) }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Category --}}
                    <td class="py-3 px-4 whitespace-nowrap">
                        @if($cat)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $catColor }}"></span>
                                <span>{{ $cat->name }}</span>
                            </span>
                        @else
                            <span class="text-slate-400 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Cost Price (WAC) --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-medium text-slate-900 dark:text-white">
                            {{ $fmt2($cost) }} <span class="text-[10px] text-slate-400 font-sans">RWF</span>
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono">
                            per {{ $unit }}
                        </div>
                    </td>

                    {{-- Current Stock & Health Indicator --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-bold text-slate-900 dark:text-white text-sm">
                            {{ $fmt2($stk) }} <span class="text-[11px] font-normal text-slate-400">{{ $unit }}</span>
                        </div>
                        <div class="mt-0.5">
                            @if($isZero)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Depleted
                                </span>
                            @elseif($isLow)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Low Stock
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Healthy
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Used in BOM Recipes --}}
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        @if($recipeCount > 0)
                            <a href="{{ route('raw-materials.show', $p) }}#recipes"
                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition"
                               title="Used in {{ $recipeCount }} finished product recipe(s)">
                                <svg class="w-3 h-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                                <span>{{ $recipeCount }} {{ Str::plural('Recipe', $recipeCount) }}</span>
                            </a>
                        @else
                            <span class="text-[11px] text-slate-400">Not in BOM</span>
                        @endif
                    </td>

                    {{-- Valuation at Cost --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-semibold text-slate-900 dark:text-white">
                            {{ $fmt2($val) }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-sans">
                            RWF
                        </div>
                    </td>

                    {{-- Last Activity --}}
                    <td class="py-3 px-4 text-right text-slate-500 dark:text-slate-400 whitespace-nowrap text-[11px]">
                        {{ $lastMoved }}
                    </td>

                    {{-- Direct Table Actions --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            {{-- View Dossier --}}
                            @can('products.view')
                                <a href="{{ route('raw-materials.show', $p) }}"
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-slate-800 transition"
                                   title="View Material Dossier">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            @endcan

                            {{-- Edit --}}
                            @can('products.edit')
                                <a href="{{ route('raw-materials.edit', $p) }}"
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 transition"
                                   title="Edit Material">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>

                                {{-- Quick Adjust Trigger --}}
                                <button type="button"
                                        @click="openQuickAdjust({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $stk }}, '{{ $unit }}')"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-slate-800 transition"
                                        title="Quick Stock Adjustment">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </button>
                            @endcan

                            {{-- Delete Modal Trigger --}}
                            @can('products.delete')
                                <button type="button"
                                        @click="confirmDelete('{{ route('raw-materials.destroy', $p) }}', '{{ addslashes($p->name) }}', {{ $recipeCount }})"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition"
                                        title="Delete Raw Material">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No raw materials found</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Try adjusting your search query, clearing filters, or adding a new raw material to your catalog.
                            </p>
                            @can('products.create')
                                <div class="pt-2">
                                    <a href="{{ route('raw-materials.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-700 transition shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Add Raw Material
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

