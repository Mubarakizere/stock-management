@extends('layouts.app')
@section('title', 'Add New User')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <a href="{{ route('users.index') }}" class="hover:underline">Users</a>
                <span>/</span>
                <span>Registration</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <span>Create New User Account</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Register a new staff account, designate system permissions, and configure authentication credentials.
            </p>
        </div>

        <a href="{{ route('users.index') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Users</span>
        </a>
    </div>

    {{-- Error Alerts --}}
    @if(isset($errors) && $errors->any())
        <div class="rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/80 p-4 text-sm text-rose-800 dark:text-rose-300 flex items-start gap-3 shadow-xs">
            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="space-y-1">
                <p class="font-bold">Please correct the following errors:</p>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Registration Form --}}
    <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data"
          x-data="{ preview: null, selectedRole: '{{ old('role', '') }}' }"
          class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 sm:p-8 space-y-8">
        @csrf

        {{-- Profile Avatar Section --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-3">
                Profile Photo (Optional)
            </label>
            <div class="flex flex-col sm:flex-row items-center gap-6 p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800">
                <div class="relative group shrink-0">
                    <template x-if="preview">
                        <img :src="preview" alt="User Preview"
                             class="w-24 h-24 rounded-2xl object-cover border-2 border-indigo-500 shadow-md">
                    </template>
                    <template x-if="!preview">
                        <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white text-2xl font-black shadow-md">
                            <svg class="w-10 h-10 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </template>
                </div>

                <div class="space-y-2 text-center sm:text-left grow">
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-sm transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Upload Avatar</span>
                            <input type="file" name="profile_image" accept="image/*" class="hidden"
                                   @change="preview = URL.createObjectURL($event.target.files[0])">
                        </label>
                        <button type="button" x-show="preview" @click="preview = null"
                                class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                            Remove
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Recommended format: Square JPG, PNG, or WEBP up to 2MB. A monogram initials avatar will be generated if omitted.
                    </p>
                </div>
            </div>
        </div>

        {{-- Basic Information Fields --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Full Name --}}
            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Full Legal Name <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           placeholder="e.g. Jean Dupont"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>

            {{-- Email Address --}}
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Email Address (Login ID) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                           placeholder="user@example.com"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Initial Password <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input type="password" name="password" id="password" required minlength="6"
                           placeholder="At least 6 characters"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Confirm Password <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6"
                           placeholder="Repeat password"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>
        </div>

        {{-- Role Selection --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-3">
                Assign System Role <span class="text-rose-500">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($roles as $role)
                    @php
                        $roleTitle = ucfirst($role);
                        $roleDesc = match($role) {
                            'admin'      => 'Full system control, settings, user security & audits',
                            'manager'    => 'Inventory management, production & purchase approvals',
                            'cashier'    => 'POS sales operations, payments & receipts',
                            'accountant' => 'Expenses, financial ledgers, debits & credit oversight',
                            default      => 'Standard staff role with default operational access',
                        };

                        $borderActive = match($role) {
                            'admin'      => 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/50 dark:bg-rose-950/20',
                            'manager'    => 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-50/50 dark:bg-indigo-950/20',
                            'cashier'    => 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/50 dark:bg-emerald-950/20',
                            'accountant' => 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/50 dark:bg-amber-950/20',
                            default      => 'border-slate-500 ring-2 ring-slate-500/20',
                        };
                    @endphp
                    <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition select-none"
                           :class="selectedRole === '{{ $role }}' ? '{{ $borderActive }}' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-600'">
                        <input type="radio" name="role" value="{{ $role }}" x-model="selectedRole" class="sr-only" required>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $roleTitle }}</span>
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="selectedRole === '{{ $role }}' ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300 dark:border-slate-600'">
                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="selectedRole === '{{ $role }}'"></div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $roleDesc }}</p>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Email Dispatch Notice --}}
        <div class="rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/80 p-4 text-xs text-blue-800 dark:text-blue-300 flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>
                An automated email containing initial sign-in instructions will be dispatched to the provided address upon registration.
            </span>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('users.index') }}"
               class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 transition">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-600/20 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Save &amp; Create User</span>
            </button>
        </div>
    </form>

</div>
@endsection
