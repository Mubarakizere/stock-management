@extends('layouts.app')
@section('title', 'Edit Role: ' . ucfirst($role->name))

@section('content')
@php
    $isSystemRole = in_array($role->name, ['admin', 'manager']);
@endphp

<div x-data="{ deleteModalOpen: false }"
     class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <a href="{{ route('roles.index') }}" class="hover:underline">Roles</a>
                <span>/</span>
                <span>Edit Access Tier</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <span>Edit Role: {{ ucfirst($role->name) }}</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Update role naming and modify inherited module permissions.
            </p>
        </div>

        <a href="{{ route('roles.index') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 shadow-xs transition">
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Roles</span>
        </a>
    </div>

    {{-- System Role Warning Banner --}}
    @if($isSystemRole)
        <div class="rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/80 p-4 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-3 shadow-xs">
            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <span class="font-bold">Protected Core System Role:</span>
                <code>{{ $role->name }}</code> is a foundational role required by application middleware and security policies. The role cannot be deleted, but you may tune its granted permissions below.
            </div>
        </div>
    @endif

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

    {{-- Edit Form --}}
    <form method="POST" action="{{ route('roles.update', $role->id) }}"
          class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 sm:p-8 space-y-8">
        @csrf
        @method('PUT')

        {{-- Role Identifier --}}
        <div class="max-w-xl space-y-2">
            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                Role Name <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" required
                       {{ $isSystemRole ? 'readonly' : '' }}
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition {{ $isSystemRole ? 'bg-slate-100 dark:bg-slate-800 cursor-not-allowed text-slate-500' : '' }}">
            </div>
            @if($isSystemRole)
                <p class="text-xs text-slate-400 dark:text-slate-500">
                    System role name cannot be modified to prevent authorization breaks.
                </p>
            @else
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Stored in lowercase (e.g. <code>{{ $role->name }}</code>).
                </p>
            @endif
        </div>

        {{-- Permissions Matrix --}}
        @include('roles._permissions', [
            'permissions'     => $permissions,
            'rolePermissions' => old('permissions', $rolePermissions),
        ])

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('roles.index') }}"
               class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 transition">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-md shadow-indigo-600/20 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Save Role Permissions</span>
            </button>
        </div>
    </form>

    {{-- Danger Zone (Delete Custom Role) --}}
    @if(!$isSystemRole)
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-rose-200 dark:border-rose-900/50 p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-rose-600 dark:text-rose-400">Danger Zone: Delete Role</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Permanently delete this role. Any users currently assigned will lose these role-specific permissions.
                </p>
            </div>
            <button type="button"
                    @click="deleteModalOpen = true"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:scale-95 shadow-sm shadow-rose-600/20 transition shrink-0 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Delete This Role</span>
            </button>
        </div>

        {{-- Delete Confirmation Modal (Alpine.js) --}}
        <div x-show="deleteModalOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="deleteModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="deleteModalOpen = false"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                     aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

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
                                    Confirm Role Deletion
                                </h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Are you sure you want to delete role <span class="font-bold text-slate-900 dark:text-white">{{ ucfirst($role->name) }}</span>? Assigned users will immediately lose these permissions.
                                </p>
                                <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold pt-1">
                                    Warning: This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-end gap-2.5">
                        <button type="button"
                                @click="deleteModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                            Cancel
                        </button>

                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST">
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
    @endif

</div>
@endsection
