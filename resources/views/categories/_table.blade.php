@php
    $currentKind = $filterKind ?? request('kind');
    $isTrash = ($currentKind === 'trash');
    $user = auth()->user();
    $canEdit = $user?->can('categories.edit');
    $canDelete = $user?->can('categories.delete');
    $canManage = $canEdit || $canDelete;
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
        <thead>
            <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-bold">
                <th scope="col" class="py-3 px-4 text-left">#ID</th>
                <th scope="col" class="py-3 px-4 text-left">Category Name</th>
                <th scope="col" class="py-3 px-4 text-left">Classification Kind</th>
                <th scope="col" class="py-3 px-4 text-left">Parent Group</th>
                <th scope="col" class="py-3 px-4 text-center">Status</th>
                @if($isTrash)
                    <th scope="col" class="py-3 px-4 text-left">Deleted At</th>
                @endif
                <th scope="col" class="py-3 px-4 text-left">Description</th>
                <th scope="col" class="py-3 px-4 text-center">Usage Count</th>
                @if($canManage)
                    <th scope="col" class="py-3 px-4 text-right">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">
            @forelse ($categories as $category)
                @php
                    $dot = $category->color ?: '#6366f1';
                    $kindBadge = match($category->kind) {
                        'product'      => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200/80 dark:border-blue-800',
                        'raw_material' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200/80 dark:border-purple-800',
                        'expense'      => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200/80 dark:border-amber-800',
                        'both'         => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200/80 dark:border-indigo-800',
                        default        => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                    };

                    $kindLabel = match($category->kind) {
                        'product'      => 'Finished Product',
                        'raw_material' => 'Raw Material',
                        'expense'      => 'Expense',
                        'both'         => 'Product & Expense',
                        default        => ucfirst($category->kind ?? 'General'),
                    };
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                    {{-- ID --}}
                    <td class="py-3.5 px-4 font-mono font-medium text-slate-400 whitespace-nowrap">
                        #{{ $category->id }}
                    </td>

                    {{-- Name + Color indicator + Code --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-block w-3 h-3 rounded-full shrink-0 shadow-xs ring-2 ring-white dark:ring-slate-900" style="background-color: {{ $dot }};"></span>
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <span>{{ $category->name }}</span>
                                    @if($category->code)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            {{ $category->code }}
                                        </span>
                                    @endif
                                </div>
                                @if($category->slug)
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        slug: {{ $category->slug }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Kind Badge --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $kindBadge }}">
                            @if($category->kind === 'product')
                                <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            @elseif($category->kind === 'raw_material')
                                <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            @elseif($category->kind === 'expense')
                                <svg class="w-3 h-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            @endif
                            <span>{{ $kindLabel }}</span>
                        </span>
                    </td>

                    {{-- Parent --}}
                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400 font-medium">
                        @if($category->parent)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <span>{{ $category->parent->name }}</span>
                            </span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>

                    {{-- Active Status --}}
                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                        @if($category->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Active</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                <span>Inactive</span>
                            </span>
                        @endif
                    </td>

                    {{-- Deleted At (Trash only) --}}
                    @if($isTrash)
                        <td class="py-3.5 px-4 whitespace-nowrap text-rose-600 dark:text-rose-400 font-medium">
                            {{ $category->deleted_at ? $category->deleted_at->format('M d, Y H:i') : '—' }}
                        </td>
                    @endif

                    {{-- Description --}}
                    <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 max-w-xs truncate" title="{{ $category->description }}">
                        {{ $category->description ?: '—' }}
                    </td>

                    {{-- Usage Counts --}}
                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold {{ ($category->products_count ?? 0) > 0 ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}"
                                  title="{{ $category->products_count ?? 0 }} Assigned Products">
                                <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span>{{ $category->products_count ?? 0 }}</span>
                            </span>

                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold {{ ($category->expenses_count ?? 0) > 0 ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}"
                                  title="{{ $category->expenses_count ?? 0 }} Assigned Expenses">
                                <svg class="w-3 h-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>{{ $category->expenses_count ?? 0 }}</span>
                            </span>
                        </div>
                    </td>

                    {{-- Actions --}}
                    @if($canManage)
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($isTrash)
                                    @can('categories.delete')
                                        {{-- Restore --}}
                                        <form action="{{ route('categories.restore', $category->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/60 transition cursor-pointer"
                                                    title="Restore Category">
                                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Restore</span>
                                            </button>
                                        </form>

                                        {{-- Delete Forever --}}
                                        <form action="{{ route('categories.forceDestroy', $category->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    @click="openConfirmModal($el.closest('form'), 'Permanently delete category \'{{ addslashes($category->name) }}\'? This will also cascade-delete associated product catalog items!')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/60 transition cursor-pointer"
                                                    title="Permanently Delete">
                                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Purge Forever</span>
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    @can('categories.edit')
                                        <a href="{{ route('categories.edit', $category) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition"
                                           title="Edit Category">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit</span>
                                        </a>
                                    @endcan

                                    @can('categories.delete')
                                        <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    @click="openConfirmModal($el.closest('form'), 'Move category \'{{ addslashes($category->name) }}\' to trash archive?')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/60 transition cursor-pointer"
                                                    title="Move to Trash">
                                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isTrash ? 9 : 8 }}" class="py-12 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">No categories found</span>
                            <span class="text-xs text-slate-400 max-w-sm">No classifications match the active filters or search keyword.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
