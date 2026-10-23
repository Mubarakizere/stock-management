@extends('errors.layout')

@section('title', '503 · Scheduled Maintenance')
@section('code', '503')
@section('code-badge', 'HTTP 503 · MAINTENANCE')
@section('status-dot', 'bg-teal-500')
@section('status-text', 'Maintenance in Progress')

@section('badge-class', 'bg-teal-50 dark:bg-teal-950/60 text-teal-800 dark:text-teal-300 border border-teal-200/80 dark:border-teal-800/80')
@section('icon-container-class', 'bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 border border-teal-200 dark:border-teal-900/60')

@section('icon')
    <i data-lucide="wrench" class="w-8 h-8"></i>
@endsection

@section('headline', 'System upgrade & maintenance in progress')
@section('subhead')
    Nezerwa Plus is briefly paused while we deploy core system updates, execute database migrations, and perform scheduled infrastructure enhancements.
@endsection

@section('alert-box')
    <div class="rounded-xl bg-teal-50/70 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800/60 p-4 text-xs space-y-2.5">
        <div class="flex items-center justify-between text-teal-900 dark:text-teal-200">
            <span class="font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-teal-500 animate-ping"></span>
                <span>Live Maintenance Mode</span>
            </span>
            <span class="font-mono text-[11px] text-teal-600 dark:text-teal-400 font-medium">Auto-Safe Mode</span>
        </div>

        <p class="text-teal-800 dark:text-teal-300/90 leading-relaxed">
            All database transactions and stock ledgers are securely preserved. Normal manufacturing and checkout operations will resume automatically once updates are verified.
        </p>

        @if(!empty($exception->getMessage()))
            <div class="pt-1 border-t border-teal-200/60 dark:border-teal-800/60 font-mono text-[11px] text-teal-700 dark:text-teal-300">
                Notice: {{ $exception->getMessage() }}
            </div>
        @endif
    </div>
@endsection

@section('primary-actions')
    <button type="button"
            onclick="window.location.reload()"
            class="btn btn-primary text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2 shadow-sm">
        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
        <span>Check Connection Status</span>
    </button>
@endsection
