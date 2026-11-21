@extends('layouts.app')
@section('title', 'Users Management')

@section('content')
<div x-data="{
        deleteModalOpen: false,
        deleteUserId: null,
        deleteUserName: '',
        openDeleteModal(id, name) {
            this.deleteUserId = id;
            this.deleteUserName = name;
            this.deleteModalOpen = true;
        },
        closeDeleteModal() {
            this.deleteModalOpen = false;
            this.deleteUserId = null;
            this.deleteUserName = '';
        }
     }"
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
                <span>Users</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <span>Users &amp; Access Control</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Manage administrative, managerial, and operational staff accounts, Spatie roles, and security credentials.
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('roles.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Roles &amp; Permissions</span>
            </a>

            <a href="{{ route('users.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-500/20 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>Create New User</span>
            </a>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. STATS & METRICS DECK                                                   --}}
    {{-- ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Users --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total System Users</span>
                <div class="p-2 rounded-xl bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $stats['total'] ?? $users->total() }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">All accounts</span>
                    <span>active in system</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-slate-400 to-slate-600"></div>
        </div>

        {{-- Administrators --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">System Administrators</span>
                <div class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
                    {{ $stats['admins'] ?? 0 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-rose-600 dark:text-rose-400">Full Access</span>
                    <span>Super Admins &amp; Security</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-rose-500 to-red-600"></div>
        </div>

        {{-- Managers --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Operations Managers</span>
                <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 tracking-tight">
                    {{ $stats['managers'] ?? 0 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">Inventory &amp; Production</span>
                    <span>lead personnel</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500 to-blue-600"></div>
        </div>

        {{-- Cashiers & Accountants --}}
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Cashiers &amp; Finance</span>
                <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                    {{ $stats['operational'] ?? 0 }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">POS &amp; Accounting</span>
                    <span>frontline users</span>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3. SEARCH & FILTERS TOOLBAR                                               --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
        {{-- Fast Role Filter Pills --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800/80">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-400 mr-1">Filter Role:</span>
                <a href="{{ route('users.index', array_filter(['search' => request('search')])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ empty(request('role')) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    All Users
                </a>
                @foreach($roles as $r)
                    <a href="{{ route('users.index', array_filter(['search' => request('search'), 'role' => $r])) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ request('role') === $r ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        {{ ucfirst($r) }}
                    </a>
                @endforeach
            </div>

            <div class="text-xs text-slate-400 font-mono">
                Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} Users
            </div>
        </div>

        {{-- Form Controls --}}
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3.5 items-end">
            {{-- Search Input --}}
            <div class="md:col-span-7">
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Search Users
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           placeholder="Search by full name or email address..."
                           class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>

            {{-- Role Dropdown --}}
            <div class="md:col-span-3">
                <label for="role" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                    Filter by Role
                </label>
                <select name="role" id="role"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="">All Roles</option>
                    @foreach($roles as $r)
                        <option value="{{ $r }}" @selected(request('role') === $r)>
                            {{ ucfirst($r) }}
                        </option>
                    @endforeach
                </select>
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

                @if(request('search') || request('role'))
                    <a href="{{ route('users.index') }}"
                       class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold flex items-center justify-center transition"
                       title="Reset Filters">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ========================================================================= --}}
    {{-- 4. USERS TABLE                                                            --}}
    {{-- ========================================================================= --}}
    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Active User Accounts</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Directory of all authorized staff and assigned permissions</p>
                </div>
            </div>

            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                {{ $users->total() }} Total Users
            </span>
        </div>

        @include('users._table')

        @if($users->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- 5. DELETE CONFIRMATION MODAL (ALPINE.JS)                                   --}}
    {{-- ========================================================================= --}}
    <div x-show="deleteModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
            <div x-show="deleteModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeDeleteModal()"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Panel --}}
            <div x-show="deleteModalOpen"
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
                                Delete User Account
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Are you sure you want to permanently delete user <span class="font-bold text-slate-900 dark:text-white" x-text="deleteUserName"></span>? All assigned permissions and user session tokens will be permanently revoked.
                            </p>
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold pt-1">
                                Warning: This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="closeDeleteModal()"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>

                    <form :action="`/users/${deleteUserId}`" method="POST">
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
