@extends('layouts.app')
@section('title', 'Customers Directory')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $user = auth()->user();
    $canCreate  = $user?->can('customers.create');
    $canEdit    = $user?->can('customers.edit');
    $canDelete  = $user?->can('customers.delete');
    $showActions = $canEdit || $canDelete;

    $currentQuery = $query ?? request('q');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
        deleteModalOpen: false,
        deleteActionUrl: '',
        deleteCustomerName: '',
        openDeleteModal(url, name) {
            this.deleteActionUrl = url;
            this.deleteCustomerName = name;
            this.deleteModalOpen = true;
        }
     }">

    {{-- Breadcrumbs & Navigation Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span>Directory</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Customers</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Customers Directory</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Manage retail and wholesale clients, contact directories, credit facilities, and purchasing histories.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('reports.customers.statement') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Account Statements
            </a>

            @can('customers.create')
                <a href="{{ route('customers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Customer
                </a>
            @endcan
        </div>
    </div>

    {{-- 4 Executive Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Accounts --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Clients</span>
                <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalCustomers ?? 0) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Registered customer directory
                </div>
            </div>
        </div>

        {{-- Active Purchasing Accounts --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Active Purchasing</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                    {{ number_format($activeCustomers ?? 0) }}
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100) : 0 }}% purchase conversion
                </div>
            </div>
        </div>

        {{-- Customer Sales Revenue --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Lifetime Revenue</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($totalRevenue ?? 0, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Client invoiced volume
                </div>
            </div>
        </div>

        {{-- Receivables Outstanding --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Receivables</span>
                <span class="w-8 h-8 rounded-xl {{ ($totalReceivables ?? 0) > 0 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400' : 'bg-slate-50 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold font-mono tracking-tight {{ ($totalReceivables ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($totalReceivables ?? 0, 2) }} <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Pending credit facilities
                </div>
            </div>
        </div>
    </div>

    {{-- Filter & Search Toolbar --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input
                    type="text"
                    name="q"
                    value="{{ $currentQuery }}"
                    placeholder="Search by client name, email, phone number or address..."
                    class="w-full pl-9 pr-9 text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-teal-500 focus:ring-teal-500"
                >
                @if(!empty($currentQuery))
                    <a href="{{ route('customers.index') }}"
                       class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                       title="Clear search">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>

                @if(!empty($currentQuery))
                    <a href="{{ route('customers.index') }}" class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        @if(!empty($currentQuery))
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                <span>Filtering for: <strong class="text-slate-800 dark:text-slate-200">"{{ $currentQuery }}"</strong></span>
                <span>Found {{ $customers->total() }} matching {{ Str::plural('client', $customers->total()) }}</span>
            </div>
        @endif
    </div>

    {{-- Customers Directory Table --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-900/50">
                        <th class="py-3 px-4">Client Name & Profile</th>
                        <th class="py-3 px-4">Contact Phone</th>
                        <th class="py-3 px-4">Email Address</th>
                        <th class="py-3 px-4">Location / Address</th>
                        <th class="py-3 px-4 text-center">Purchasing Activity</th>
                        @if($showActions)
                            <th class="py-3 px-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                            {{-- Client Name & Profile --}}
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-xs shrink-0 ring-1 ring-teal-200 dark:ring-teal-800">
                                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('customers.show', $customer) }}" class="font-bold text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition">
                                            {{ $customer->name }}
                                        </a>
                                        <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-2">
                                            <span>ID: #{{ $customer->id }}</span>
                                            @if($customer->created_at)
                                                <span>• Since {{ $customer->created_at->format('M Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Phone --}}
                            <td class="py-3.5 px-4">
                                @if($customer->phone)
                                    <a href="tel:{{ $customer->phone }}" class="inline-flex items-center gap-1.5 font-mono text-slate-700 dark:text-slate-300 hover:text-teal-600 dark:hover:text-teal-400 transition">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <span>{{ $customer->phone }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Email --}}
                            <td class="py-3.5 px-4">
                                @if($customer->email)
                                    <a href="mailto:{{ $customer->email }}" class="inline-flex items-center gap-1.5 text-slate-700 dark:text-slate-300 hover:text-teal-600 dark:hover:text-teal-400 transition truncate max-w-xs">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span>{{ $customer->email }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Address --}}
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 max-w-xs truncate">
                                @if($customer->address)
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $customer->address }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Purchasing Activity --}}
                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $orderCount = (int) ($customer->sales_count ?? 0);
                                    $spentSum   = (float) ($customer->total_spent ?? 0);
                                @endphp
                                @if($orderCount > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-700 ring-1 ring-teal-600/20 dark:bg-teal-950/40 dark:text-teal-300">
                                        {{ $orderCount }} {{ Str::plural('order', $orderCount) }}
                                    </span>
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                        {{ number_format($spentSum, 2) }} RWF
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        No sales yet
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            @if($showActions)
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('customers.show', $customer) }}"
                                           class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                                           title="Inspect Client Profile">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        @can('customers.edit')
                                            <a href="{{ route('customers.edit', $customer) }}"
                                               class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                                               title="Edit Customer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                        @endcan

                                        @can('customers.delete')
                                            <button type="button"
                                                    @click="openDeleteModal('{{ route('customers.destroy', $customer) }}', '{{ addslashes($customer->name) }}')"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                    title="Delete Customer">
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
                            <td colspan="{{ $showActions ? 6 : 5 }}" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No customers found</p>
                                <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria or register a new client.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers instanceof LengthAwarePaginator && $customers->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- DEDICATED DELETE CUSTOMER MODAL                             --}}
    {{-- ============================================================ --}}
    @can('customers.delete')
    <div x-show="deleteModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.away="deleteModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 ring-1 ring-rose-200 dark:ring-rose-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Delete Customer Account
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Are you sure you want to remove <strong class="text-slate-800 dark:text-slate-200" x-text="deleteCustomerName"></strong>?
                    </p>
                </div>
            </div>

            <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Audit & Ledger Integrity Notice
                </div>
                <p class="text-amber-700 dark:text-amber-400 pl-5">
                    Existing sales records and credit transactions will not be deleted; they will be unlinked (converted to walk-in/unassigned) to preserve accounting balance sheets.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                    Keep Account
                </button>
                <form :action="deleteActionUrl" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl text-white bg-rose-600 hover:bg-rose-500 shadow-sm transition">
                        Confirm Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan

</div>
@endsection
