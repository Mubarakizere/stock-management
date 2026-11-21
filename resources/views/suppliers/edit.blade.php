@extends('layouts.app')
@section('title', 'Edit Supplier: ' . $supplier->name)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('suppliers.index') }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">Suppliers</a>
                <span>/</span>
                <a href="{{ route('suppliers.show', $supplier) }}" class="hover:text-teal-600 dark:hover:text-teal-400 transition">{{ $supplier->name }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Edit</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Edit Supplier
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Update credentials and contact information for <strong class="text-slate-700 dark:text-slate-300">{{ $supplier->name }}</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('suppliers.show', $supplier) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span>View Profile</span>
            </a>

            <a href="{{ route('suppliers.index') }}"
               class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition" title="Back to Directory">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Validation Errors Alert --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 p-4 text-xs text-rose-800 dark:text-rose-200 space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-sm">
                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Please correct the following errors:
            </div>
            <ul class="list-disc pl-5 space-y-0.5 pt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @can('suppliers.edit')
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm">
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST" class="space-y-0" novalidate>
                @csrf
                @method('PUT')

                <div class="p-6 space-y-5">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Fields marked with <span class="text-rose-500 font-bold">*</span> are required.
                    </p>

                    {{-- Supplier Name --}}
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Supplier / Company Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $supplier->name) }}"
                            required
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email & Phone --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Corporate Email Address
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email', $supplier->email) }}"
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"
                            >
                            @error('email')
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-[11px] text-slate-400">
                                Primary contact for purchase order dispatch.
                            </p>
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Contact Telephone
                            </label>
                            <input
                                id="phone"
                                type="text"
                                name="phone"
                                value="{{ old('phone', $supplier->phone) }}"
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"
                            >
                            @error('phone')
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-[11px] text-slate-400">
                                Logistics coordinator telephone.
                            </p>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label for="address" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Physical Facility / Address
                        </label>
                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-teal-500 focus:ring-teal-500"
                        >{{ old('address', $supplier->address) }}</textarea>
                        @error('address')
                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-[11px] text-slate-400">
                            Warehouse location or delivery depot address.
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <a href="{{ route('suppliers.index') }}"
                       class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 transition">
                        Cancel
                    </a>

                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Update Supplier</span>
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="rounded-2xl border border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/40 p-4 text-xs text-amber-800 dark:text-amber-300">
            <div class="font-bold flex items-center gap-1.5 text-sm">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Permission Required
            </div>
            <p class="mt-1">You do not have permissions to edit suppliers. Please contact your system administrator.</p>
        </div>
    @endcan
</div>
@endsection
