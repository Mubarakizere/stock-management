@extends('errors.layout')

@section('title', '429 · Rate Limit Exceeded')
@section('code', '429')
@section('code-badge', 'HTTP 429 · RATE LIMITED')
@section('status-dot', 'bg-orange-500')
@section('status-text', 'Rate Throttling Active')

@section('badge-class', 'bg-orange-50 dark:bg-orange-950/60 text-orange-800 dark:text-orange-300 border border-orange-200/80 dark:border-orange-800/80')
@section('icon-container-class', 'bg-orange-50 dark:bg-orange-950/50 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-900/60')

@section('icon')
    <i data-lucide="gauge" class="w-8 h-8"></i>
@endsection

@section('headline', 'System request frequency exceeded')
@section('subhead')
    A high volume of operations was submitted from your browser in a short duration. Temporary rate limiting is active to protect transaction integrity and API health.
@endsection

@section('alert-box')
    <div class="rounded-xl bg-orange-50/70 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-800/60 p-4 text-xs space-y-2"
         x-data="{
             countdown: 30,
             canRetry: false,
             init() {
                 const interval = setInterval(() => {
                     if (this.countdown > 1) {
                         this.countdown--;
                     } else {
                         this.countdown = 0;
                         this.canRetry = true;
                         clearInterval(interval);
                     }
                 }, 1000);
             }
         }">
        <div class="flex items-center justify-between text-orange-800 dark:text-orange-300">
            <span class="font-semibold flex items-center gap-1.5">
                <i data-lucide="timer" class="w-4 h-4"></i>
                <span>Cooling Window</span>
            </span>
            <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded bg-orange-100 dark:bg-orange-900/60"
                  x-text="canRetry ? 'Ready to reconnect' : countdown + 's remaining'"></span>
        </div>

        <p class="text-orange-700 dark:text-orange-300/90 leading-relaxed">
            Please refrain from rapid clicking or automated re-submissions. Normal access will automatically resume when the throttle window elapses.
        </p>

        <div class="pt-1">
            <button type="button"
                    @click="window.location.reload()"
                    :disabled="!canRetry"
                    :class="canRetry ? 'bg-orange-600 hover:bg-orange-700 text-white cursor-pointer shadow-sm' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="{ 'animate-spin': !canRetry }"></i>
                <span x-text="canRetry ? 'Retry Request Now' : 'Throttled (' + countdown + 's)'"></span>
            </button>
        </div>
    </div>
@endsection

@section('primary-actions')
    <a href="{{ url('/dashboard') }}" class="btn btn-outline text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2">
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
        <span>Return to Dashboard</span>
    </a>
@endsection
