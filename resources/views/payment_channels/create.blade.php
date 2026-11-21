@extends('layouts.app')
@section('title', 'Create Payment Channel')

@section('content')
<div x-data="{
        name: '{{ old('name', '') }}',
        slug: '{{ old('slug', '') }}',
        description: '{{ old('description', '') }}',
        isActive: {{ old('is_active', '1') == '1' ? 'true' : 'false' }},
        autoSlug: {{ old('slug') ? 'false' : 'true' }},

        updateSlug() {
            if (this.autoSlug) {
                this.slug = this.name
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        },

        get channelType() {
            const str = (this.name + ' ' + this.slug).toLowerCase();
            if (str.includes('cash')) return 'cash';
            if (str.includes('bank') || str.includes('zigama')) return 'bank';
            if (str.includes('momo') || str.includes('mobile') || str.includes('mtn')) return 'mobile_money';
            if (str.includes('airtel')) return 'airtel';
            if (str.includes('gift')) return 'gift';
            return 'gateway';
        }
     }"
     class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumbs & Header --}}
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
            <a href="{{ route('payment-channels.index') }}" class="hover:underline">Payment Gateways</a>
            <span>/</span>
            <span>New Gateway</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <div class="p-2.5 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <span>Create Payment Gateway</span>
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Add a new financial gateway or payment method for POS sales, loans, and expenses.
                </p>
            </div>

            <a href="{{ route('payment-channels.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back to Gateways</span>
            </a>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Form Card (2 cols) --}}
        <div class="lg:col-span-2">
            <form action="{{ route('payment-channels.store') }}" method="POST" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 space-y-6">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-1.5">
                        Gateway / Channel Name <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               name="name"
                               id="name"
                               x-model="name"
                               @input="updateSlug()"
                               required
                               placeholder="e.g. MTN Mobile Money, Bank of Kigali, Cash Desk"
                               class="w-full px-4 py-2.5 rounded-xl text-sm bg-slate-50 dark:bg-slate-800/60 border @error('name') border-rose-500 @else border-slate-300 dark:border-slate-700 @enderror text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    </div>
                    @error('name')
                        <p class="text-xs text-rose-500 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        The public display label shown in checkout screens and invoices.
                    </p>
                </div>

                {{-- Slug --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="slug" class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                            System Slug / Key
                        </label>
                        <button type="button"
                                @click="autoSlug = !autoSlug; if(autoSlug) updateSlug();"
                                class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                            <span x-text="autoSlug ? 'Customize manually' : 'Auto-sync from name'"></span>
                        </button>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-mono text-xs">
                            #
                        </div>
                        <input type="text"
                               name="slug"
                               id="slug"
                               x-model="slug"
                               @input="autoSlug = false"
                               placeholder="e.g. mtn-momo, bank-of-kigali"
                               class="w-full pl-8 pr-4 py-2.5 rounded-xl text-sm font-mono bg-slate-50 dark:bg-slate-800/60 border @error('slug') border-rose-500 @else border-slate-300 dark:border-slate-700 @enderror text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                    </div>
                    @error('slug')
                        <p class="text-xs text-rose-500 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        Internal identifier used in API integrations and transaction database records.
                    </p>
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-1.5">
                        Description / Settlement Notes
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              x-model="description"
                              placeholder="e.g. Merchant code 123456, settlement to main corporate account, handled by Head Cashier..."
                              class="w-full px-4 py-2.5 rounded-xl text-sm bg-slate-50 dark:bg-slate-800/60 border @error('description') border-rose-500 @else border-slate-300 dark:border-slate-700 @enderror text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-rose-500 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Operational Active Toggle --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Operational Status</span>
                            <span :class="isActive ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300'"
                                  class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                  x-text="isActive ? 'Live & Active' : 'Paused'"></span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            When active, this channel appears in POS checkout options and financial payment dropdowns.
                        </p>
                    </div>

                    <input type="hidden" name="is_active" :value="isActive ? '1' : '0'">
                    <button type="button"
                            @click="isActive = !isActive"
                            :class="isActive ? 'bg-emerald-600' : 'bg-slate-300 dark:bg-slate-700'"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <span :class="isActive ? 'translate-x-5' : 'translate-x-0'"
                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('payment-channels.index') }}"
                       class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Create Payment Channel</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Right: Live Badge & Architecture Card (1 col) --}}
        <div class="space-y-6">
            {{-- Live Preview Card --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Live Channel Preview</span>
                </h3>

                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/40 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold shadow-xs border"
                             :class="{
                                 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800/50': channelType === 'cash',
                                 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-950/40 dark:border-blue-800/50': channelType === 'bank',
                                 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-950/40 dark:border-amber-800/50': channelType === 'mobile_money',
                                 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/40 dark:border-rose-800/50': channelType === 'airtel',
                                 'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-950/40 dark:border-purple-800/50': channelType === 'gift',
                                 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-800/50': channelType === 'gateway'
                             }">
                            <template x-if="channelType === 'cash'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </template>
                            <template x-if="channelType === 'bank'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                </svg>
                            </template>
                            <template x-if="channelType === 'mobile_money'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </template>
                            <template x-if="channelType === 'airtel'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                                </svg>
                            </template>
                            <template x-if="channelType === 'gift'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4a2 2 0 112 2h-2zm0 0V4a2 2 0 10-2 2h2zm-7 4h14a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7a1 1 0 011-1z" />
                                </svg>
                            </template>
                            <template x-if="channelType === 'gateway'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </template>
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white" x-text="name ? name : 'Channel Name'"></div>
                            <span class="inline-flex items-center gap-1 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                #<span x-text="slug ? slug : 'system-slug'"></span>
                            </span>
                        </div>
                    </div>

                    <div class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2" x-text="description ? description : 'No description specified.'"></div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-200/60 dark:border-slate-700/60 text-xs">
                        <span class="text-slate-500">Checkout Status</span>
                        <span :class="isActive ? 'text-emerald-600 font-semibold flex items-center gap-1' : 'text-slate-400 font-medium'">
                            <span x-show="isActive" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span x-text="isActive ? 'Selectable' : 'Hidden'"></span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- ERP Integration Guide --}}
            <div class="bg-indigo-50/50 dark:bg-indigo-950/20 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 p-5 space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold text-indigo-900 dark:text-indigo-300">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>ERP Financial Integration</span>
                </div>
                <p class="text-xs text-indigo-900/80 dark:text-indigo-200/70 leading-relaxed">
                    Active channels become immediately available in:
                </p>
                <ul class="text-xs text-indigo-900/80 dark:text-indigo-200/70 space-y-1.5 list-disc list-inside">
                    <li><strong>POS Sales Checkout:</strong> Cashiers can record customer payments.</li>
                    <li><strong>Supplier Purchases:</strong> Record bank wire or cash settlement for POs.</li>
                    <li><strong>Customer / Supplier Loans:</strong> Disburse loans and record repayments.</li>
                    <li><strong>Operating Expenses:</strong> Log petty cash or mobile money vouchers.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
