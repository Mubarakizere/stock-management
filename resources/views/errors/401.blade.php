@extends('errors.layout')

@section('title', '401 · Authentication Required')
@section('code', '401')
@section('code-badge', 'HTTP 401 · UNAUTHORIZED')
@section('status-dot', 'bg-indigo-500')
@section('status-text', 'Authentication Required')

@section('badge-class', 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80')
@section('icon-container-class', 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/60')

@section('icon')
    <i data-lucide="key-round" class="w-8 h-8"></i>
@endsection

@section('headline', 'Authentication credentials required')
@section('subhead')
    The requested system route requires an active, verified session. Please sign in with your corporate credentials to continue.
@endsection

@section('primary-actions')
    <a href="{{ url('/login') }}" class="btn btn-primary text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2 shadow-sm">
        <i data-lucide="log-in" class="w-4 h-4"></i>
        <span>Sign In to System</span>
    </a>

    <a href="{{ url('/') }}" class="btn btn-outline text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2">
        <i data-lucide="home" class="w-4 h-4"></i>
        <span>Return to Homepage</span>
    </a>
@endsection
