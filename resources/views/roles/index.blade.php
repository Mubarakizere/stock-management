@extends('layouts.app')
@section('title', 'Roles & Permissions Management')

@section('content')
<div x-data="{
        deleteRoleId: null,
        deleteRoleName: '',
        showDrawer: false,
        selectedRole: null
     }"
     @keydown.escape.window="deleteRoleId = null; showDrawer = false"
     class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- ========================================================================= --}}
    {{-- 1. HEADER & TOP ACTIONS                                                   --}}
    {{-- ========================================================================= --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <span>Settings &amp; Access</span>
                <span>/</span>
                <span>Security</span>
                <span>/</span>
                <span>Roles &amp; Permissions</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <span>Roles &amp; Permissions Governance</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Configure role-based access control (RBAC), govern security privileges, and inspect user permission inheritance.
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('users.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>Users Directory</span>
            </a>

            <a href="{{ route('roles.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span>Create New Role</span>
            </a>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. METRICS & STATS DECK                                                   --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Roles --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Defined Roles</span>
                <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $stats['total_roles'] ?? $roles->total() }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">RBAC groups</span>
                    <span>active in system</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500 to-blue-600"></div>
        </div>

        {{-- Core System Roles --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Core System Roles</span>
                <div class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
                    {{ $stats['system_roles'] ?? 2 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-rose-600 dark:text-rose-400">Admin &amp; Manager</span>
                    <span>protected from deletion</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-rose-500 to-red-600"></div>
        </div>

        {{-- Custom Defined Roles --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Custom Assigned Roles</span>
                <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                    {{ $stats['custom_roles'] ?? 0 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">Operational tiers</span>
                    <span>cashiers, accountants, staff</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
        </div>

        {{-- Total Permissions --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Security Permissions</span>
                <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 tracking-tight">
                    {{ $stats['total_permissions'] ?? 0 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-amber-600 dark:text-amber-400">Granular gates</span>
                    <span>across modules</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3. SEARCH & TOOLBAR                                                       --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
        <form method="GET" action="{{ route('roles.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3.5 items-end">
            {{-- Search Input --}}
            <div class="md:col-span-10">
                <label for="q" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Search Roles &amp; Permissions
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="q" id="q" value="{{ $query }}"
                           placeholder="Search by role name, permission (e.g. sales.create), or user name..."
                           class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">

                    @if($query !== '')
                        <a href="{{ route('roles.index') }}"
                           class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                           title="Clear search">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-sm font-semibold flex items-center justify-center gap-1.5 shadow-md shadow-indigo-600/20 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>Filter</span>
                </button>

                @if($query !== '')
                    <a href="{{ route('roles.index') }}"
                       class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold flex items-center justify-center transition"
                       title="Reset Filters">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>

        @if($query !== '')
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                Filtered search for: <span class="font-bold text-slate-900 dark:text-white">“{{ $query }}”</span>
            </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- 4. ROLES TABLE                                                            --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Active Security Roles</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Click a role name or inspect button to preview inherited access</p>
                </div>
            </div>

            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                {{ $roles->total() }} Configured Roles
            </span>
        </div>

        @include('roles._table')

        @if($roles->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                {{ $roles->links() }}
            </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- 5. SLIDE-OVER INSPECTION DRAWER (ALPINE.JS)                               --}}
    {{-- ========================================================================= --}}
    <div x-show="showDrawer" x-cloak
         class="fixed inset-0 z-50 overflow-hidden"
         aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            {{-- Backdrop --}}
            <div x-show="showDrawer"
                 x-transition:enter="ease-in-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in-out duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showDrawer = false"
                 class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"></div>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div x-show="showDrawer"
                     x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="pointer-events-auto w-screen max-w-md">
                    <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-slate-800">
                        {{-- Drawer Header --}}
                        <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-850">
                            <div class="flex items-start justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xl font-black text-slate-900 dark:text-white" x-text="selectedRole?.name"></h2>
                                        <template x-if="selectedRole?.is_system">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 uppercase">
                                                System
                                            </span>
                                        </template>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Role created on <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="selectedRole?.created_at"></span>
                                    </p>
                                </div>
                                <button type="button" @click="showDrawer = false"
                                        class="rounded-xl p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    <span class="sr-only">Close panel</span>
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-4 flex items-center gap-2">
                                <a :href="selectedRole?.edit_url"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Edit Role &amp; Permissions</span>
                                </a>
                            </div>
                        </div>

                        {{-- Drawer Body --}}
                        <div class="p-6 space-y-6">
                            {{-- Assigned Users --}}
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <span>Assigned Users</span>
                                    </h3>
                                    <span class="text-xs font-bold font-mono px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300" x-text="selectedRole?.users_count ?? 0"></span>
                                </div>

                                <template x-if="selectedRole?.users?.length">
                                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                        <template x-for="user in selectedRole.users" :key="user.id">
                                            <div class="flex items-center gap-2.5 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                                <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center shrink-0"
                                                     x-text="user.name.substring(0, 2).toUpperCase()"></div>
                                                <div class="min-w-0 grow">
                                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate" x-text="user.name"></p>
                                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate" x-text="user.email"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!selectedRole?.users?.length">
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-dashed border-slate-200 dark:border-slate-700 text-center text-xs text-slate-400 italic">
                                        No users currently hold this role.
                                    </div>
                                </template>
                            </div>

                            {{-- Granted Permissions List --}}
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                        <span>Granted Permissions</span>
                                    </h3>
                                    <span class="text-xs font-bold font-mono px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800"
                                          x-text="`${selectedRole?.permissions_count ?? 0} Active`"></span>
                                </div>

                                <template x-if="selectedRole?.permissions?.length">
                                    <div class="space-y-1 max-h-96 overflow-y-auto pr-1">
                                        <template x-for="perm in selectedRole.permissions" :key="perm">
                                            <div class="flex items-center gap-2 py-1 px-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 text-xs text-slate-700 dark:text-slate-300 font-mono">
                                                <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span class="truncate" x-text="perm"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!selectedRole?.permissions?.length">
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-dashed border-slate-200 dark:border-slate-700 text-center text-xs text-slate-400 italic">
                                        No permissions assigned to this role yet.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 6. DELETE CONFIRMATION MODAL (ALPINE.JS)                                   --}}
    {{-- ========================================================================= --}}
    <div x-show="deleteRoleId" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="deleteRoleId"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="deleteRoleId = null"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="deleteRoleId"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-700">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white" id="modal-title">
                                Delete Security Role
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Are you sure you want to delete role <span class="font-bold text-slate-900 dark:text-white" x-text="deleteRoleName"></span>? Users currently assigned to this role will lose their inherited permissions immediately.
                            </p>
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold pt-1">
                                Warning: This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="deleteRoleId = null"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>

                    <form :action="`{{ url('roles') }}/${deleteRoleId}`" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:scale-95 shadow-md shadow-rose-600/20 transition">
                            Confirm Deletion
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
