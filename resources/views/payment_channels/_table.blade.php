<div class="overflow-x-auto">
    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300 divide-y divide-slate-200 dark:divide-slate-800">
        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6 text-left">Gateway / Channel</th>
                <th scope="col" class="px-3 py-3.5 text-left">System Key (Slug)</th>
                <th scope="col" class="px-3 py-3.5 text-left hidden md:table-cell">Description</th>
                <th scope="col" class="px-3 py-3.5 text-left">Ledger Activity</th>
                <th scope="col" class="px-3 py-3.5 text-center">Status</th>
                <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
            @forelse($channels as $channel)
                @php
                    $theme = $channel->theme_color;
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                    {{-- Channel Info & Icon --}}
                    <td class="py-4 pl-4 pr-3 sm:pl-6 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold shadow-xs border {{ $theme['border'] }} {{ $theme['icon_bg'] }}">
                                @if($theme['type'] === 'cash')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                @elseif($theme['type'] === 'bank')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                    </svg>
                                @elseif($theme['type'] === 'mobile_money')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                @elseif($theme['type'] === 'airtel')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                                    </svg>
                                @elseif($theme['type'] === 'gift')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4a2 2 0 112 2h-2zm0 0V4a2 2 0 10-2 2h2zm-7 4h14a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7a1 1 0 011-1z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>{{ $channel->name }}</span>
                                    @if($channel->is_active)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                            POS Ready
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    ID #{{ $channel->id }} &bull; Added {{ $channel->created_at ? $channel->created_at->format('M d, Y') : 'System Default' }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Slug / Key --}}
                    <td class="px-3 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            <span>{{ $channel->slug }}</span>
                        </span>
                    </td>

                    {{-- Description --}}
                    <td class="px-3 py-4 text-xs max-w-xs truncate hidden md:table-cell text-slate-600 dark:text-slate-400">
                        @if($channel->description)
                            <span title="{{ $channel->description }}">{{ Str::limit($channel->description, 60) }}</span>
                        @else
                            <span class="italic text-slate-400 dark:text-slate-500">No notes provided</span>
                        @endif
                    </td>

                    {{-- Ledger Activity (Transactions count + volume) --}}
                    <td class="px-3 py-4 whitespace-nowrap">
                        @php
                            $txCount = $channel->tx_count ?? 0;
                            $txVol = $channel->tx_volume ?? 0;
                        @endphp
                        @if($txCount > 0)
                            <div class="flex flex-col">
                                <div class="flex items-center gap-1 text-xs font-bold text-slate-900 dark:text-white">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-[10px]">
                                        {{ $txCount }}
                                    </span>
                                    <span>{{ $txCount === 1 ? 'Transaction' : 'Transactions' }}</span>
                                </div>
                                <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">
                                    {{ number_format($txVol, 2) }} RWF
                                </span>
                            </div>
                        @else
                            <div class="flex items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                <span>No entries yet</span>
                            </div>
                        @endif
                    </td>

                    {{-- Operational Status --}}
                    <td class="px-3 py-4 whitespace-nowrap text-center">
                        @if($channel->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 shadow-xs">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span>Active</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                <span class="h-2 w-2 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                                <span>Inactive</span>
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="py-4 pl-3 pr-4 sm:pr-6 whitespace-nowrap text-right text-xs font-medium">
                        <div class="flex items-center justify-end gap-1.5">
                            @can('payment-channels.edit')
                                <a href="{{ route('payment-channels.edit', $channel) }}"
                                   title="Edit Payment Gateway"
                                   class="p-2 rounded-lg text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 border border-transparent hover:border-indigo-100 dark:hover:border-indigo-900/40 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                            @endcan

                            @can('payment-channels.delete')
                                <button type="button"
                                        @click="openDeleteModal({{ $channel->id }}, '{{ addslashes($channel->name) }}', {{ $channel->tx_count ?? 0 }})"
                                        title="Delete Channel"
                                        class="p-2 rounded-lg text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 border border-transparent hover:border-rose-100 dark:hover:border-rose-900/40 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-12 px-4 text-center">
                        <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">No payment channels found</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-4 text-center">
                                @if(request('q') || (request('status') && request('status') !== 'all'))
                                    No gateways matched your query <span class="font-semibold text-slate-700 dark:text-slate-300">"{{ request('q') }}"</span>. Try clearing search filters.
                                @else
                                    Get started by configuring your first financial gateway or payment method.
                                @endif
                            </p>
                            <div class="flex items-center gap-2">
                                @if(request('q') || (request('status') && request('status') !== 'all'))
                                    <a href="{{ route('payment-channels.index') }}"
                                       class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 transition">
                                        Clear Filters
                                    </a>
                                @endif
                                @can('payment-channels.create')
                                    <a href="{{ route('payment-channels.create') }}"
                                       class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition">
                                        + Add Payment Channel
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($channels->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 dark:divide-slate-800/80 bg-slate-50/50 dark:bg-slate-800/30">
        {{ $channels->links() }}
    </div>
@endif
