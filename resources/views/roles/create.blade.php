@extends('layouts.app')
@section('title', 'Create New Role')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                <a href="{{ route('roles.index') }}" class="hover:underline">Roles</a>
                <span>/</span>
                <span>Definition</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="p-2 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <span>Create New Role</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Define a new security access tier and assign granular module permissions.
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

    {{-- Creation Form --}}
    <form method="POST" action="{{ route('roles.store') }}"
          class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 sm:p-8 space-y-8">
        @csrf

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
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       placeholder="e.g. storekeeper, auditor, supervisor"
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Use a concise, singular title (e.g. <code>storekeeper</code>). The system will automatically normalize it to lowercase.
            </p>
        </div>

        {{-- Permissions Matrix --}}
        @include('roles._permissions', [
            'permissions'     => $permissions,
            'rolePermissions' => old('permissions', []),
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
                <span>Save &amp; Create Role</span>
            </button>
        </div>
    </form>

</div>
@endsection
