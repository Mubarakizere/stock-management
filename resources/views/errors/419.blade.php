@extends('errors.layout')

@section('title', '419 · Session Expired')
@section('code', '419')
@section('code-badge', 'HTTP 419 · CSRF EXPIRED')
@section('status-dot', 'bg-violet-500')
@section('status-text', 'Session Invalidation')

@section('badge-class', 'bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200/80 dark:border-violet-800/80')
@section('icon-container-class', 'bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-900/60')

@section('icon')
    <i data-lucide="history" class="w-8 h-8"></i>
@endsection

@section('headline', 'Security token or session expired')
@section('subhead')
    Your active session was closed or your CSRF anti-tamper token lapsed after a period of inactivity. This is a built-in security safeguard to prevent cross-site form misuse.
@endsection

@section('alert-box')
    <div class="rounded-xl bg-violet-50/70 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800/60 p-4 text-xs text-violet-800 dark:text-violet-300 flex items-start gap-3">
        <i data-lucide="info" class="w-4 h-4 text-violet-600 dark:text-violet-400 shrink-0 mt-0.5"></i>
        <div class="space-y-1">
            <span class="font-semibold block">Quick Resolution</span>
            <p class="text-violet-700 dark:text-violet-300 leading-relaxed">
                Clicking <strong>Refresh Session</strong> will regenerate a fresh security handshake. If you were submitting a long form, you may need to re-verify your submission.
            </p>
        </div>
    </div>
@endsection

@section('primary-actions')
    <button type="button"
            onclick="window.location.reload()"
            class="btn btn-primary text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2 shadow-sm">
        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
        <span>Refresh Session & Retry</span>
    </button>

    <a href="{{ url('/login') }}" class="btn btn-outline text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2">
        <i data-lucide="log-in" class="w-4 h-4"></i>
        <span>Sign In Again</span>
    </a>
@endsection
