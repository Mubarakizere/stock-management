@extends('errors.layout')

@section('title', '500 · Internal Server Error')
@section('code', '500')
@section('code-badge', 'HTTP 500 · SERVER ERROR')
@section('status-dot', 'bg-rose-500')
@section('status-text', 'Service Interruption')

@section('badge-class', 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/80')
@section('icon-container-class', 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/60')

@section('icon')
    <i data-lucide="server-crash" class="w-8 h-8"></i>
@endsection

@section('headline', 'An unhandled server condition occurred')
@section('subhead')
    The web application experienced an unexpected exception while executing your instruction. The runtime state has been safely captured in system error logs.
@endsection

@section('alert-box')
    <div class="rounded-xl bg-rose-50/70 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 p-4 text-xs space-y-2">
        <div class="flex items-center justify-between text-rose-800 dark:text-rose-300">
            <span class="font-semibold flex items-center gap-1.5">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
                <span>Automated Incident Record</span>
            </span>
            <span class="font-mono text-[11px] text-rose-600 dark:text-rose-400">Log: storage/logs/laravel.log</span>
        </div>

        <p class="text-rose-700 dark:text-rose-300/90 leading-relaxed">
            Data integrity checks prevented erroneous writes to the database. If this occurred during a stock movement, production batch, or checkout, verify your transaction history before attempting duplicate entries.
        </p>
    </div>
@endsection

@section('primary-actions')
    <button type="button"
            onclick="window.location.reload()"
            class="btn btn-primary text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2 shadow-sm">
        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
        <span>Retry Operation</span>
    </button>

    <a href="{{ url('/dashboard') }}" class="btn btn-outline text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2">
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
        <span>Return to Dashboard</span>
    </a>
@endsection

@section('extra-diagnostics')
    <div>
        <span class="text-slate-500">Error Notice:</span>
        <span class="text-rose-400">{{ !empty($exception->getMessage()) ? $exception->getMessage() : 'No public stacktrace exposed (production mode).' }}</span>
    </div>
@endsection
