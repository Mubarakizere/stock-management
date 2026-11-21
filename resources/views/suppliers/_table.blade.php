@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $user = auth()->user();
    $canEdit   = $user?->can('suppliers.edit');
    $canDelete = $user?->can('suppliers.delete');
    $showActions = $canEdit || $canDelete;
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs">
        <thead>
            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                <th class="py-3 px-4">Supplier Identity</th>
                <th class="py-3 px-4">Contact Phone</th>
                <th class="py-3 px-4">Email Address</th>
                <th class="py-3 px-4">Physical Address</th>
                <th class="py-3 px-4 text-center">Procurement Volume</th>
                <th class="py-3 px-4 text-center">Outstanding Payables</th>
                @if($showActions)
                    <th class="py-3 px-4 text-right">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse($suppliers as $supplier)
                @php
                    $poCount   = (int) ($supplier->purchases_count ?? 0);
                    $spendSum  = (float) ($supplier->total_spend ?? 0);
                    $balanceDue = (float) ($supplier->total_balance_due ?? 0);
                    $initials  = strtoupper(substr($supplier->name, 0, 2));
                @endphp
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                    {{-- Supplier Identity --}}
                    <td class="py-3.5 px-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-xs shrink-0 ring-1 ring-teal-200 dark:ring-teal-800">
                                {{ $initials }}
                            </div>
                            <div>
                                <a href="{{ route('suppliers.show', $supplier) }}" class="font-bold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition">
                                    {{ $supplier->name }}
                                </a>
                                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-2 font-mono">
                                    <span>#SUP-{{ str_pad($supplier->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    @if($supplier->created_at)
                                        <span class="font-sans">• Since {{ $supplier->created_at->format('M Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Phone --}}
                    <td class="py-3.5 px-4">
                        @if($supplier->phone)
                            <a href="tel:{{ $supplier->phone }}" class="inline-flex items-center gap-1.5 font-mono text-slate-700 dark:text-slate-300 hover:text-teal-600 dark:hover:text-teal-400 transition">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span>{{ $supplier->phone }}</span>
                            </a>
                        @else
                            <span class="text-slate-400 italic">—</span>
                        @endif
                    </td>

                    {{-- Email --}}
                    <td class="py-3.5 px-4">
                        @if($supplier->email)
                            <a href="mailto:{{ $supplier->email }}" class="inline-flex items-center gap-1.5 text-slate-700 dark:text-slate-300 hover:text-teal-600 dark:hover:text-teal-400 transition truncate max-w-xs">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $supplier->email }}</span>
                            </a>
                        @else
                            <span class="text-slate-400 italic">—</span>
                        @endif
                    </td>

                    {{-- Physical Address --}}
                    <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 max-w-xs truncate">
                        @if($supplier->address)
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>{{ $supplier->address }}</span>
                            </span>
                        @else
                            <span class="text-slate-400 italic">—</span>
                        @endif
                    </td>

                    {{-- Procurement Volume --}}
                    <td class="py-3.5 px-4 text-center">
                        @if($poCount > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-700 ring-1 ring-teal-600/20 dark:bg-teal-950/40 dark:text-teal-300">
                                {{ $poCount }} {{ Str::plural('order', $poCount) }}
                            </span>
                            <div class="text-[10px] font-mono font-medium text-slate-600 dark:text-slate-300 mt-0.5">
                                {{ number_format($spendSum, 2) }} RWF
                            </div>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                No purchase orders
                            </span>
                        @endif
                    </td>

                    {{-- Outstanding Payables --}}
                    <td class="py-3.5 px-4 text-center">
                        @if($balanceDue > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold font-mono bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300">
                                {{ number_format($balanceDue, 2) }} RWF
                            </span>
                            <div class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5">
                                Balance Due
                            </div>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Settled / Clear
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    @if($showActions)
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                {{-- Quick View / Drawer --}}
                                <button type="button"
                                        @click="openShowModal({
                                            id: {{ $supplier->id }},
                                            name: '{{ addslashes($supplier->name) }}',
                                            phone: '{{ addslashes($supplier->phone ?? '') }}',
                                            email: '{{ addslashes($supplier->email ?? '') }}',
                                            address: '{{ addslashes($supplier->address ?? '') }}',
                                            orders_count: {{ $poCount }},
                                            total_spend: {{ $spendSum }},
                                            balance_due: {{ $balanceDue }},
                                            show_url: '{{ route('suppliers.show', $supplier) }}',
                                            statement_url: '{{ route('reports.suppliers.statement', ['supplier_id' => $supplier->id]) }}'
                                        })"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                                        title="Quick View Supplier">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                {{-- Quick Edit Modal Trigger --}}
                                @can('suppliers.edit')
                                    <button type="button"
                                            @click="openEditModal({
                                                id: {{ $supplier->id }},
                                                name: '{{ addslashes($supplier->name) }}',
                                                phone: '{{ addslashes($supplier->phone ?? '') }}',
                                                email: '{{ addslashes($supplier->email ?? '') }}',
                                                address: '{{ addslashes($supplier->address ?? '') }}',
                                                action_url: '{{ route('suppliers.update', $supplier) }}'
                                            })"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                                            title="Edit Supplier">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                @endcan

                                {{-- Delete Trigger --}}
                                @can('suppliers.delete')
                                    <button type="button"
                                            @click="openDeleteModal('{{ route('suppliers.destroy', $supplier) }}', '{{ addslashes($supplier->name) }}')"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                            title="Delete Supplier">
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
                    <td colspan="{{ $showActions ? 7 : 6 }}" class="py-12 text-center text-slate-400">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No suppliers found</p>
                        <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria or register a new vendor.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination Links --}}
@if($suppliers instanceof LengthAwarePaginator && $suppliers->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
        {{ $suppliers->links() }}
    </div>
@endif

