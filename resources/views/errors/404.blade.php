@extends('errors.layout')

@section('title', '404 · Page Not Found')
@section('code', '404')
@section('code-badge', 'HTTP 404 · NOT FOUND')
@section('status-dot', 'bg-slate-400')
@section('status-text', 'Route Not Located')

@section('badge-class', 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700')
@section('icon-container-class', 'bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700')

@section('icon')
    <i data-lucide="compass" class="w-8 h-8"></i>
@endsection

@section('headline', 'The requested resource could not be found')
@section('subhead')
    The URL you accessed does not correspond to an active record, page, or API route. The item may have been deleted, moved, or the link may have expired.
@endsection

@section('primary-actions')
    <a href="{{ url('/dashboard') }}" class="btn btn-primary text-xs sm:text-sm px-4 py-2.5 rounded-xl inline-flex items-center gap-2 shadow-sm">
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
        <span>Return to Dashboard</span>
    </a>
@endsection

@section('quick-nav')
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <a href="{{ url('/products') }}"
           class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-teal-500 dark:hover:border-teal-500 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group text-left">
            <i data-lucide="package" class="w-4 h-4 text-slate-400 group-hover:text-teal-600 dark:group-hover:text-teal-400 mb-1.5 transition"></i>
            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Products</div>
            <div class="text-[10px] text-slate-400">Inventory items</div>
        </a>

        <a href="{{ url('/productions') }}"
           class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-teal-500 dark:hover:border-teal-500 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group text-left">
            <i data-lucide="factory" class="w-4 h-4 text-slate-400 group-hover:text-teal-600 dark:group-hover:text-teal-400 mb-1.5 transition"></i>
            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Production</div>
            <div class="text-[10px] text-slate-400">Batch runs</div>
        </a>

        <a href="{{ url('/sales') }}"
           class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-teal-500 dark:hover:border-teal-500 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group text-left">
            <i data-lucide="shopping-cart" class="w-4 h-4 text-slate-400 group-hover:text-teal-600 dark:group-hover:text-teal-400 mb-1.5 transition"></i>
            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Sales</div>
            <div class="text-[10px] text-slate-400">Invoices & POS</div>
        </a>

        <a href="{{ url('/raw-materials') }}"
           class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-teal-500 dark:hover:border-teal-500 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group text-left">
            <i data-lucide="flask-conical" class="w-4 h-4 text-slate-400 group-hover:text-teal-600 dark:group-hover:text-teal-400 mb-1.5 transition"></i>
            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Materials</div>
            <div class="text-[10px] text-slate-400">Stock & recipes</div>
        </a>
    </div>
@endsection
