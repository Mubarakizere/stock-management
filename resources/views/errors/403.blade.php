@extends('errors.layout')

@section('title', '403 · Access Restricted')
@section('code', '403')
@section('code-badge', 'HTTP 403 · FORBIDDEN')
@section('status-dot', 'bg-amber-500')
@section('status-text', 'Permission Denied')

@section('badge-class', 'bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/80')
@section('icon-container-class', 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-900/60')

@section('icon')
    <i data-lucide="shield-alert" class="w-8 h-8"></i>
@endsection

@section('headline', 'Access restricted by security policy')
@section('subhead')
    {{ $exception->getMessage() ?: 'Your user profile does not possess the required permissions or role elevation to access this operational module.' }}
@endsection

@section('alert-box')
    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 p-4 text-xs space-y-2">
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
            <span class="font-medium uppercase tracking-wider text-[11px]">Current Security Context</span>
            <span class="inline-flex items-center gap-1 font-mono text-[11px] text-amber-600 dark:text-amber-400">
                <i data-lucide="lock" class="w-3 h-3"></i> Policy Enforced
            </span>
        </div>

        @if(auth()->check())
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1 text-slate-700 dark:text-slate-300">
                <div>
                    <span class="text-slate-400">Signed in as:</span>
                    <strong class="ml-1 text-slate-900 dark:text-slate-100">{{ auth()->user()->email }}</strong>
                </div>
                <div>
                    <span class="text-slate-400">Role:</span>
                    <span class="ml-1 px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-[11px] font-semibold text-slate-800 dark:text-slate-200 uppercase">
                        {{ auth()->user()->roles->pluck('name')->join(', ') ?: 'Standard User' }}
                    </span>
                </div>
            </div>
        @else
            <p class="text-slate-600 dark:text-slate-300">
                You are currently browsing as a guest. Logging in with an authorized team member account may grant access.
            </p>
        @endif
    </div>
@endsection

@section('primary-actions')
    <a href="{{ url('/dashboard') }}" class="btn btn-primary text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2 shadow-sm">
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
        <span>Return to Dashboard</span>
    </a>

    @if(auth()->check())
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="btn btn-outline text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Switch Account</span>
            </button>
        </form>
    @else
        <a href="{{ url('/login') }}" class="btn btn-outline text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2">
            <i data-lucide="log-in" class="w-4 h-4"></i>
            <span>Sign In to Continue</span>
        </a>
    @endif
@endsection
