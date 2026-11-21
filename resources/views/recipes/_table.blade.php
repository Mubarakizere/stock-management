@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $costBasis = $basis ?? 'wac';
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                <th class="py-3 px-4">Finished Product</th>
                <th class="py-3 px-4">Category</th>
                <th class="py-3 px-4 text-center">BOM Configuration</th>
                <th class="py-3 px-4 text-right">Dynamic BOM Cost ({{ strtoupper($costBasis) }})</th>
                <th class="py-3 px-4 text-right">Retail Price</th>
                <th class="py-3 px-4 text-center">Margin Guard Status</th>
                <th class="py-3 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            @forelse ($products as $p)
                @php
                    $cat = $p->category;
                    $catColor = $cat->color ?? '#0d9488';
                    $hasRecipe = $p->recipe_items_count > 0;
                    $guard = $hasRecipe ? $p->marginGuardStatus($costBasis) : null;
                    $bomCost = $guard['bom_cost'] ?? (float)($p->cost_price ?? 0);
                    $price = (float)($p->price ?? 0);
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                    {{-- Finished Product --}}
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg {{ $hasRecipe ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border-amber-200/80 dark:border-amber-800/80' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700' }} border flex items-center justify-center font-bold text-xs shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div>
                                <a href="{{ route('recipes.edit', $p) }}"
                                   class="font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                    {{ $p->name }}
                                </a>
                                <div class="text-[11px] font-mono text-slate-400">
                                    #PRD-{{ str_pad($p->id, 4, '0', STR_PAD_LEFT) }} &bull; Unit: {{ $p->unit ?? 'pcs' }}
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
                            <span class="text-slate-400 text-xs">&mdash;</span>
                        @endif
                    </td>

                    {{-- Recipe Status --}}
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        @if($hasRecipe)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                <span>{{ $p->recipe_items_count }} {{ Str::plural('Ingredient', $p->recipe_items_count) }}</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span>No Recipe</span>
                            </span>
                        @endif
                    </td>

                    {{-- Dynamic BOM Unit Cost --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        @if($hasRecipe)
                            <div class="font-mono font-bold text-slate-900 dark:text-white">
                                {{ $fmt2($bomCost) }} <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-sans">
                                Catalog: {{ $fmt2($p->cost_price ?? 0) }} RWF
                            </div>
                        @else
                            <span class="text-slate-400 font-mono text-xs">&mdash;</span>
                        @endif
                    </td>

                    {{-- Retail Price --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-mono font-medium text-slate-900 dark:text-white">
                            {{ $fmt2($price) }} <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                        </div>
                    </td>

                    {{-- Margin Guard Status --}}
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        @if($guard)
                            <div class="inline-flex flex-col items-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border shadow-xs {{ $guard['badge']['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $guard['badge']['dot'] }}"></span>
                                    <span>{{ $guard['margin_pct'] }}% Margin</span>
                                </span>
                                <span class="text-[10px] font-mono text-slate-400 mt-0.5">
                                    Target: {{ $guard['target_margin'] }}%
                                    @if($guard['deficit_pct'] > 0)
                                        <span class="text-rose-500 font-bold">(-{{ $guard['deficit_pct'] }}%)</span>
                                    @endif
                                </span>
                            </div>
                        @else
                            <span class="text-slate-400 text-xs">&mdash;</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1.5">
                            {{-- 1-Click Sync BOM Cost Modal Trigger --}}
                            @if($hasRecipe && $guard)
                                <button type="button"
                                        @click="confirmSync('{{ route('recipes.sync-bom', $p) }}', '{{ addslashes($p->name) }}', {{ (float)($p->cost_price ?? 0) }}, {{ $bomCost }}, {{ $guard['target_margin'] }}, {{ $guard['suggested_price'] }})"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 transition"
                                        title="Synchronize BOM Cost to Product Catalog">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                            @endif

                            {{-- Configure Recipe --}}
                            <a href="{{ route('recipes.edit', $p) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg {{ $hasRecipe ? 'text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700' : 'text-white bg-amber-600 hover:bg-amber-700' }} shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span>{{ $hasRecipe ? 'Edit BOM' : 'Set Recipe' }}</span>
                            </a>

                            {{-- Execute Production Run if Recipe configured --}}
                            @if($hasRecipe)
                                @can('products.edit')
                                    <a href="{{ route('productions.create', ['product_id' => $p->id]) }}"
                                       class="p-1.5 rounded-lg text-slate-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-slate-800 transition"
                                       title="Run Manufacturing Batch">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </a>
                                @endcan

                                {{-- Clear Recipe Modal Trigger --}}
                                @can('products.edit')
                                    <button type="button"
                                            @click="confirmClear('{{ route('recipes.destroy', $p) }}', '{{ addslashes($p->name) }}', {{ $p->recipe_items_count }})"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition"
                                            title="Clear BOM Recipe">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-12 text-center">
                        <div class="max-w-sm mx-auto space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No products found</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Try adjusting your search, category, or margin filters.
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
