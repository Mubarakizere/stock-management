@extends('layouts.app')
@section('title', "Edit Purchase #{$purchase->id}")

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    @can('purchases.edit')
        {{-- Header & Breadcrumbs --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-slate-800">
            <div>
                <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                    <a href="{{ route('dashboard') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Dashboard</a>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <a href="{{ route('purchases.index') }}" class="hover:text-slate-800 dark:hover:text-slate-200 transition">Purchases</a>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-slate-800 dark:text-slate-200 font-medium">Edit #{{ $purchase->id }}</span>
                </nav>

                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Edit Purchase Order #PO-{{ str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Order Modification
                    </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Update supplier purchase details, adjust line item costs and quantities, and reconcile payment terms.
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('purchases.show', $purchase) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700/80 shadow-sm transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>View Purchase</span>
                </a>
            </div>
        </div>

        {{-- Form --}}
        <form method="POST"
              action="{{ route('purchases.update', $purchase) }}"
              enctype="multipart/form-data"
              class="space-y-6">
            @csrf
            @method('PUT')

            @include('purchases._form', [
                'suppliers' => $suppliers,
                'purchase'  => $purchase,
            ])
        </form>
    @else
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/80 bg-amber-50/50 dark:bg-amber-950/30 p-6 text-center">
            <h3 class="text-base font-bold text-amber-900 dark:text-amber-200">Permission Required</h3>
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                You don't have permission to edit purchases.
            </p>
            <div class="mt-4">
                <a href="{{ route('purchases.show', $purchase) }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white">
                    Back to Purchase
                </a>
            </div>
        </div>
    @endcan

</div>
@endsection
