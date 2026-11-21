@extends('layouts.app')
@section('title', "Edit {$customer->name}")

@section('content')
@php
    $user = auth()->user();
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{ showDeleteModal: false }">

    {{-- Breadcrumb & Back --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('customers.index') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Customers</a>
                <span>/</span>
                <a href="{{ route('customers.show', $customer) }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">{{ $customer->name }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Edit</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-xs shrink-0 ring-1 ring-teal-200 dark:ring-teal-800">
                    {{ strtoupper(substr($customer->name, 0, 2)) }}
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Edit {{ $customer->name }}
                </h1>
            </div>
        </div>

        <a href="{{ route('customers.show', $customer) }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Cancel
        </a>
    </div>

    {{-- Validation summary --}}
    @if (($errors ?? null)?->any())
        <div class="rounded-2xl border border-rose-200 dark:border-rose-900 bg-rose-50/80 dark:bg-rose-950/30 p-4 text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-sm">
            <div class="font-semibold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Please correct the following errors:
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @cannot('customers.edit')
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Permission Required</p>
            <p class="text-xs text-slate-400 mt-1">You do not have permission to modify customer account records.</p>
        </div>
    @elsecan('customers.edit')

        {{-- Form Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
            <form action="{{ route('customers.update', $customer) }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                <div class="p-6 space-y-5">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Customer / Company Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                value="{{ old('name', $customer->name) }}"
                                required
                                autocomplete="name"
                                class="w-full pl-9 text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-teal-500 focus:ring-teal-500"
                            >
                        </div>
                        @error('name')
                            <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Contact Grid: Email & Phone --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Phone --}}
                        <div>
                            <label for="phone" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Phone Number
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </span>
                                <input
                                    id="phone"
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone', $customer->phone) }}"
                                    autocomplete="tel"
                                    class="w-full pl-9 font-mono text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-teal-500 focus:ring-teal-500"
                                >
                            </div>
                            @error('phone')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Email Address
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email', $customer->email) }}"
                                    autocomplete="email"
                                    class="w-full pl-9 text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-teal-500 focus:ring-teal-500"
                                >
                            </div>
                            @error('email')
                                <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label for="address" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Physical Location / Billing Address
                        </label>
                        <div class="relative">
                            <textarea
                                id="address"
                                name="address"
                                rows="3"
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-teal-500 focus:ring-teal-500"
                            >{{ old('address', $customer->address) }}</textarea>
                        </div>
                        @error('address')
                            <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Action Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        @can('customers.delete')
                            <button type="button" @click="showDeleteModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900 dark:hover:bg-rose-900/60 shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete Customer
                            </button>
                        @endcan
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('customers.show', $customer) }}" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                            Cancel
                        </a>

                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 text-xs font-semibold rounded-xl text-white bg-teal-600 hover:bg-teal-500 shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ============================================================ --}}
        {{-- DEDICATED DELETE MODAL ON EDIT PAGE                         --}}
        {{-- ============================================================ --}}
        @can('customers.delete')
        <div x-show="showDeleteModal"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

            <div @click.away="showDeleteModal = false"
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
                            Are you sure you want to delete <strong class="text-slate-800 dark:text-slate-200">{{ $customer->name }}</strong>?
                        </p>
                    </div>
                </div>

                <div class="mt-4 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                    <div class="font-semibold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Ledger Retention Notice
                    </div>
                    <p class="text-amber-700 dark:text-amber-400 pl-5">
                        Existing sales orders and credit ledgers will remain intact but will be decoupled (reclassified as walk-in / unassigned) to prevent ledger discrepancies.
                    </p>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showDeleteModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        Keep Customer
                    </button>
                    <form action="{{ route('customers.destroy', $customer) }}" method="POST">
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

    @endcannot
</div>
@endsection
