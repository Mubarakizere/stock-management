<x-app-layout>
    @section('title', 'Executive Dashboard')

    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- =========================================================================
        | 1. Hero Header & Quick Actions
        |========================================================================= --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-gray-200/80 dark:border-gray-800">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Welcome back, {{ Auth::user()->name }}
                    </h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wider bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-400 border border-teal-200/60 dark:border-teal-800/60">
                        {{ ucfirst($role) }}
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                    <span>{{ now()->format('l, d F Y') }}</span>
                    <span>•</span>
                    <span>Real-time inventory and financial performance pulse</span>
                </p>
            </div>

            {{-- Action Shortcuts --}}
            <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                @can('sales.view')
                    <a href="{{ route('sales.create') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 shadow-xs transition-all">
                        <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                        <span>New Sale</span>
                    </a>
                @endcan

                @if(in_array($role, ['admin', 'manager', 'accountant']))
                    @can('purchases.view')
                        <a href="{{ route('purchases.create') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 border border-gray-200 dark:border-gray-700 shadow-2xs transition-all">
                            <i data-lucide="package-plus" class="w-4 h-4 text-gray-400"></i>
                            <span>Purchase</span>
                        </a>
                    @endcan

                    @can('expenses.view')
                        <a href="{{ route('expenses.create') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 border border-gray-200 dark:border-gray-700 shadow-2xs transition-all">
                            <i data-lucide="receipt" class="w-4 h-4 text-gray-400"></i>
                            <span>Expense</span>
                        </a>
                    @endcan

                    @can('loans.view')
                        <a href="{{ route('loans.create') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 border border-gray-200 dark:border-gray-700 shadow-2xs transition-all">
                            <i data-lucide="hand-coins" class="w-4 h-4 text-gray-400"></i>
                            <span>Loan</span>
                        </a>
                    @endcan
                @endif

                <button onclick="window.location.reload()"
                        class="p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-2xs transition-all"
                        title="Refresh data">
                    <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- =========================================================================
        | 2. Cashier Mode (Dedicated Daily Register)
        |========================================================================= --}}
        @if($role === 'cashier')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="bg-white dark:bg-gray-800 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">My Sales Today</p>
                    <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums mt-2">
                        {{ number_format($myTodaySalesTotal ?? 0) }} <span class="text-xs font-semibold text-gray-400">RWF</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-2">Total revenue collected during this register session</p>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Receipts Issued</p>
                    <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums mt-2">
                        {{ number_format($myTodaySalesCount ?? 0) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-2">Transactions processed today</p>
                </div>

                <div class="bg-teal-50/60 dark:bg-teal-950/20 border border-teal-200/80 dark:border-teal-800/60 rounded-2xl p-5 flex flex-col justify-between">
                    <div>
                        <p class="text-xs font-semibold text-teal-800 dark:text-teal-300 uppercase tracking-wider">Active Register</p>
                        <p class="text-sm text-teal-700 dark:text-teal-400 mt-1">Ready to ring up customers and issue official receipts.</p>
                    </div>
                    <a href="{{ route('sales.create') }}" class="mt-4 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-sm transition">
                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                        <span>Launch Point of Sale</span>
                    </a>
                </div>
            </div>

            {{-- My Recent Sales --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl overflow-hidden shadow-xs">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">My Recent Sales Transactions</h3>
                    <a href="{{ route('sales.index') }}" class="text-xs font-medium text-teal-600 hover:underline">View All Sales →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400 font-medium">
                            <tr>
                                <th class="px-4 py-3">Receipt / ID</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Time</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @forelse($myLatestSales ?? [] as $s)
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">#{{ $s->id }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $s->customer->name ?? 'Walk-in Customer' }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white tabular-nums">
                                        {{ number_format($s->total_amount) }} RWF
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider {{ $s->status === 'paid' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400' }}">
                                            {{ ucfirst($s->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500">{{ $s->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('sales.invoice', $s->id) }}" target="_blank" class="text-teal-600 hover:text-teal-700 font-medium">Invoice</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No sales recorded today yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else

            {{-- =========================================================================
            | 3. Primary Executive KPI Cards
            |========================================================================= --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
                
                {{-- Total Sales Revenue --}}
                <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-gray-300 dark:hover:border-gray-600 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gross Revenue</span>
                            <div class="h-8 w-8 rounded-xl bg-gray-50 dark:bg-gray-700/60 border border-gray-200/60 dark:border-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300">
                                <i data-lucide="dollar-sign" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">
                            {{ number_format($totalSales ?? 0) }} <span class="text-xs font-semibold text-gray-400">RWF</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-[11px]">
                        <span class="text-gray-500 dark:text-gray-400">Today: {{ number_format($todaySales ?? 0) }}</span>
                        <span class="font-medium {{ ($salesChange ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ ($salesChange ?? 0) >= 0 ? '↑ +' : '↓ ' }}{{ number_format(abs($salesChange ?? 0), 1) }}%
                        </span>
                    </div>
                </div>

                {{-- Gross Profit --}}
                <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-gray-300 dark:hover:border-gray-600 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gross Profit</span>
                            <div class="h-8 w-8 rounded-xl bg-gray-50 dark:bg-gray-700/60 border border-gray-200/60 dark:border-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300">
                                <i data-lucide="trending-up" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">
                            {{ number_format($totalProfit ?? 0) }} <span class="text-xs font-semibold text-gray-400">RWF</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-[11px]">
                        @php
                            $margin = ($totalSales ?? 0) > 0 ? (($totalProfit ?? 0) / $totalSales) * 100 : 0;
                        @endphp
                        <span class="text-gray-500 dark:text-gray-400">Profit Margin</span>
                        <span class="font-semibold text-teal-600 dark:text-teal-400">{{ number_format($margin, 1) }}%</span>
                    </div>
                </div>

                {{-- Net Cash Balance (Liquidity) --}}
                <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-gray-300 dark:hover:border-gray-600 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cash Liquidity</span>
                            <div class="h-8 w-8 rounded-xl bg-gray-50 dark:bg-gray-700/60 border border-gray-200/60 dark:border-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300">
                                <i data-lucide="wallet" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">
                            {{ number_format($netBalance ?? 0) }} <span class="text-xs font-semibold text-gray-400">RWF</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-[11px]">
                        <span class="text-gray-500 dark:text-gray-400">Ledger Status</span>
                        <span class="font-medium {{ ($netBalance ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600' }}">
                            {{ ($netBalance ?? 0) >= 0 ? 'Positive Net' : 'Negative Net' }}
                        </span>
                    </div>
                </div>

                {{-- Inventory Asset Value --}}
                <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-gray-300 dark:hover:border-gray-600 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Inventory Asset</span>
                            <div class="h-8 w-8 rounded-xl bg-gray-50 dark:bg-gray-700/60 border border-gray-200/60 dark:border-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300">
                                <i data-lucide="boxes" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">
                            {{ number_format($totalStockValue ?? 0) }} <span class="text-xs font-semibold text-gray-400">RWF</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-[11px]">
                        <span class="text-gray-500 dark:text-gray-400">Valuation Base</span>
                        <span class="font-medium text-gray-600 dark:text-gray-300">At Cost Price</span>
                    </div>
                </div>

                {{-- Pending Receivables --}}
                <div class="col-span-2 md:col-span-1 bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-gray-300 dark:hover:border-gray-600 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Receivables</span>
                            <div class="h-8 w-8 rounded-xl bg-gray-50 dark:bg-gray-700/60 border border-gray-200/60 dark:border-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300">
                                <i data-lucide="clock" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">
                            {{ number_format($pendingBalances ?? 0) }} <span class="text-xs font-semibold text-gray-400">RWF</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-[11px]">
                        <span class="text-gray-500 dark:text-gray-400">Uncollected</span>
                        <span class="font-medium {{ ($pendingBalances ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
                            {{ ($pendingBalances ?? 0) > 0 ? 'Pending' : 'Settled' }}
                        </span>
                    </div>
                </div>

            </div>

            {{-- =========================================================================
            | 4. Main Executive Grid (7 Cols Analytics / 5 Cols Operations)
            |========================================================================= --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- ===================== LEFT COLUMN (7 COLS) ===================== --}}
                <div class="lg:col-span-7 space-y-6">

                    {{-- Sales & Purchasing Velocity Chart --}}
                    <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-5 shadow-xs">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    Revenue & Purchasing Velocity
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Sales intake versus inventory restocking across the last 6 months
                                </p>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-medium">
                                <div class="flex items-center gap-1.5 text-teal-600 dark:text-teal-400">
                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                                    <span>Sales</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-indigo-500 dark:text-indigo-400">
                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                    <span>Purchases</span>
                                </div>
                            </div>
                        </div>

                        {{-- Chart Canvas --}}
                        <div class="h-64 sm:h-72 w-full">
                            <canvas id="trendChart"></canvas>
                        </div>

                        {{-- Period Aggregates Strip --}}
                        <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700/60 grid grid-cols-3 gap-2 text-center">
                            <div class="px-2 py-1.5 rounded-xl bg-gray-50/70 dark:bg-gray-700/30">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Today's Sales</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white tabular-nums mt-0.5">
                                    {{ number_format($todaySales ?? 0) }} RWF
                                </p>
                            </div>
                            <div class="px-2 py-1.5 rounded-xl bg-gray-50/70 dark:bg-gray-700/30">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">This Week</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white tabular-nums mt-0.5">
                                    {{ number_format($weekSales ?? 0) }} RWF
                                </p>
                            </div>
                            <div class="px-2 py-1.5 rounded-xl bg-gray-50/70 dark:bg-gray-700/30">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">This Month</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white tabular-nums mt-0.5">
                                    {{ number_format($monthSales ?? 0) }} RWF
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Top Performing Products --}}
                    <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-5 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Top Performing Products</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Highest volume sales and contribution</p>
                            </div>
                            <a href="{{ route('products.index') }}" class="text-xs font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400 hover:underline">
                                Full Catalog →
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-50/80 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider text-[10px]">
                                    <tr>
                                        <th class="px-3.5 py-2.5 rounded-l-lg">Product</th>
                                        <th class="px-3.5 py-2.5 text-right">Units Sold</th>
                                        <th class="px-3.5 py-2.5 text-right">Revenue</th>
                                        <th class="px-3.5 py-2.5 rounded-r-lg text-right">Stock Left</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                    @forelse($topProducts ?? [] as $idx => $p)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-3.5 py-3">
                                                <div class="flex items-center gap-2.5">
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-md text-[10px] font-bold {{ $idx === 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                                        {{ $idx + 1 }}
                                                    </span>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $p->product->name ?? 'Unknown Product' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-3.5 py-3 text-right font-medium text-gray-700 dark:text-gray-300 tabular-nums">
                                                {{ number_format($p->total_qty ?? 0, 0) }}
                                            </td>
                                            <td class="px-3.5 py-3 text-right font-semibold text-teal-600 dark:text-teal-400 tabular-nums">
                                                {{ number_format($p->total_revenue ?? 0, 0) }} RWF
                                            </td>
                                            <td class="px-3.5 py-3 text-right text-gray-500 tabular-nums">
                                                {{ $p->product->formatted_stock ?? '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                                No sales data recorded yet to rank products.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Manufacturing & Raw Materials (BOM) Pulse --}}
                    @if(in_array($role, ['admin', 'manager']))
                        <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-5 shadow-xs">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <i data-lucide="factory" class="w-4 h-4 text-gray-500"></i>
                                        <span>Production & Raw Materials</span>
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Manufacturing supply inventory and production batches</p>
                                </div>
                                <a href="{{ route('raw-materials.index') }}" class="text-xs font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400 hover:underline">
                                    Raw Materials →
                                </a>
                            </div>

                            <div class="grid grid-cols-3 gap-3 mb-4">
                                <div class="px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700/50">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Catalog Items</p>
                                    <p class="text-base font-bold text-gray-900 dark:text-white mt-0.5">{{ $rawMaterialsTotal ?? 0 }}</p>
                                </div>
                                <div class="px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700/50">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">RM Asset Value</p>
                                    <p class="text-base font-bold text-gray-900 dark:text-white mt-0.5">{{ number_format($rawMaterialsStockValue ?? 0, 0) }} <span class="text-[10px] text-gray-400">RWF</span></p>
                                </div>
                                <div class="px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700/50">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Low Stock</p>
                                    <p class="text-base font-bold {{ ($rawMaterialsLowStock ?? 0) > 0 ? 'text-amber-600' : 'text-emerald-600' }} mt-0.5">
                                        {{ $rawMaterialsLowStock ?? 0 }} items
                                    </p>
                                </div>
                            </div>

                            {{-- Low Stock Alert or Well Stocked State --}}
                            @if(isset($lowStockRawMaterials) && $lowStockRawMaterials->count() > 0)
                                <div class="space-y-2.5 pt-2">
                                    <p class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Low Inventory Attention Required:</p>
                                    @foreach($lowStockRawMaterials as $rm)
                                        @php
                                            $stock = $rm->current_stock ?? 0;
                                            $pct = min(100, max(0, ($stock / 5) * 100));
                                            $barColor = $pct < 25 ? 'bg-rose-500' : ($pct < 60 ? 'bg-amber-400' : 'bg-teal-500');
                                        @endphp
                                        <div class="px-3 py-2 rounded-xl bg-gray-50/70 dark:bg-gray-700/30 flex items-center justify-between text-xs">
                                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $rm->name }}</span>
                                            <div class="flex items-center gap-3">
                                                <div class="w-20 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                                    <div class="h-full {{ $barColor }} rounded-full" style="width: {{ $pct }}%"></div>
                                                </div>
                                                <span class="font-semibold {{ $stock <= 0 ? 'text-rose-600' : 'text-amber-600' }} tabular-nums">
                                                    {{ number_format($stock, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="px-4 py-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200/50 dark:border-emerald-800/40 text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
                                    <span>All manufacturing raw materials are within normal stock thresholds.</span>
                                </div>
                            @endif
                        </div>
                    @endif

                </div>

                {{-- ===================== RIGHT COLUMN (5 COLS) ===================== --}}
                <div class="lg:col-span-5 space-y-6">

                    {{-- Financial Breakdown & Liquidity --}}
                    <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-5 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Cashflow & Liquidity</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Double-entry ledger summary</p>
                            </div>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider {{ ($netBalance ?? 0) >= 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-rose-50 text-rose-700' }}">
                                {{ ($netBalance ?? 0) >= 0 ? 'Solvent' : 'Deficit' }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Total Credits (Inflow)</span>
                                </div>
                                <span class="text-xs font-bold text-gray-900 dark:text-white tabular-nums">
                                    {{ number_format($totalCredits ?? 0) }} RWF
                                </span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Total Debits (Outflow)</span>
                                </div>
                                <span class="text-xs font-bold text-gray-900 dark:text-white tabular-nums">
                                    {{ number_format($totalDebits ?? 0) }} RWF
                                </span>
                            </div>

                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-gray-100/80 dark:bg-gray-700/60 border border-gray-200/80 dark:border-gray-600">
                                <span class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Net Position</span>
                                <span class="text-sm font-extrabold {{ ($netBalance ?? 0) >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600' }} tabular-nums">
                                    {{ number_format($netBalance ?? 0) }} RWF
                                </span>
                            </div>
                        </div>

                        {{-- Monthly Expenses Summary --}}
                        <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center justify-between text-xs mb-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Monthly Operating Expenses</span>
                                <span class="font-bold text-gray-900 dark:text-white tabular-nums">
                                    {{ number_format($monthExpenses ?? 0) }} RWF
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                                @php
                                    $expRatio = ($monthSales ?? 0) > 0 ? min(100, (($monthExpenses ?? 0) / $monthSales) * 100) : 0;
                                @endphp
                                <div class="bg-indigo-500 h-full rounded-full" style="width: {{ $expRatio }}%"></div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">Represents {{ number_format($expRatio, 1) }}% of this month's revenue</p>
                        </div>
                    </div>

                    {{-- Credit & Loans Lifecycle Monitor --}}
                    <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-5 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i data-lucide="hand-coins" class="w-4 h-4 text-gray-400"></i>
                                    <span>Loans & Credit Pulse</span>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Active receivables and credit liabilities</p>
                            </div>
                            @can('loans.view')
                                <a href="{{ route('loans.index') }}" class="text-xs font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400 hover:underline">
                                    All Loans →
                                </a>
                            @endcan
                        </div>

                        {{-- Loans quick badges --}}
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Active Loans</span>
                                <p class="text-lg font-bold text-gray-900 dark:text-white tabular-nums mt-0.5">{{ $activeLoans ?? 0 }}</p>
                            </div>
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Overdue Status</span>
                                <p class="text-lg font-bold {{ ($overdueLoans ?? 0) > 0 ? 'text-rose-600' : 'text-emerald-600' }} tabular-nums mt-0.5">
                                    {{ $overdueLoans ?? 0 }} overdue
                                </p>
                            </div>
                        </div>

                        {{-- Active Loans List --}}
                        <div class="space-y-2">
                            @forelse($activeLoansList ?? [] as $loan)
                                <div class="p-3 rounded-xl border border-gray-100 dark:border-gray-700/60 hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition-colors flex items-center justify-between text-xs">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ $loan->customer->name ?? $loan->supplier->name ?? 'Direct Counterparty' }}
                                        </p>
                                        <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400">
                                            {{ ucfirst($loan->type ?? 'Loan') }} • Due: {{ $loan->due_date ? $loan->due_date->format('M d, Y') : 'Open' }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-gray-900 dark:text-white tabular-nums">
                                            {{ number_format($loan->remaining ?? $loan->amount) }} RWF
                                        </p>
                                        <span class="inline-block text-[9px] font-semibold uppercase tracking-wider px-1.5 py-0.2 rounded {{ $loan->is_overdue ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $loan->is_overdue ? 'Overdue' : 'Pending' }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-5 text-center text-gray-400 text-xs">
                                    No active loans outstanding.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Recent Ledger Pulse (Debit / Credit Trail) --}}
                    <div class="bg-white dark:bg-gray-800/95 border border-gray-200/90 dark:border-gray-700/80 rounded-2xl p-5 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i data-lucide="activity" class="w-4 h-4 text-gray-400"></i>
                                    <span>Recent Transactions</span>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Live audit ledger flow</p>
                            </div>
                            @can('debits-credits.view')
                                <a href="{{ route('debits-credits.index') }}" class="text-xs font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400 hover:underline">
                                    Ledger →
                                </a>
                            @endcan
                        </div>

                        <div class="space-y-2.5">
                            @forelse($recentTransactions ?? [] as $tx)
                                <div class="p-2.5 rounded-xl border border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-md text-[10px] font-bold {{ $tx->type === 'credit' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' }}">
                                            {{ $tx->type === 'credit' ? '+' : '−' }}
                                        </span>
                                        <div>
                                            <p class="font-medium text-gray-800 dark:text-gray-200 line-clamp-1">
                                                {{ $tx->description ?? 'Transaction #' . $tx->id }}
                                            </p>
                                            <p class="text-[10px] text-gray-400">
                                                {{ $tx->created_at ? $tx->created_at->diffForHumans() : $tx->date }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="font-bold tabular-nums {{ $tx->type === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600' }}">
                                        {{ $tx->type === 'credit' ? '+' : '−' }}{{ number_format($tx->amount) }}
                                    </span>
                                </div>
                            @empty
                                <div class="px-4 py-5 text-center text-gray-400 text-xs">
                                    No ledger entries recorded yet.
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>

            </div>

        @endif

    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Trend Chart (Sales vs Purchases)
            const ctxTrend = document.getElementById('trendChart')?.getContext('2d');
            if (ctxTrend && window.Chart) {
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)';
                const textColor = isDark ? '#94a3b8' : '#64748b';

                new Chart(ctxTrend, {
                    type: 'line',
                    data: {
                        labels: {{ Illuminate\Support\Js::from($months ?? ['Jun','Jul','Aug','Sep','Oct','Nov']) }},
                        datasets: [
                            {
                                label: 'Sales (RWF)',
                                data: {{ Illuminate\Support\Js::from($salesTrend ?? [0,0,0,0,0,0]) }},
                                borderColor: '#0d9488',
                                backgroundColor: 'rgba(13, 148, 136, 0.08)',
                                borderWidth: 2.5,
                                tension: 0.35,
                                fill: true,
                                pointBackgroundColor: '#0d9488',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            },
                            {
                                label: 'Purchases (RWF)',
                                data: {{ Illuminate\Support\Js::from($purchaseTrend ?? [0,0,0,0,0,0]) }},
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.06)',
                                borderWidth: 2,
                                tension: 0.35,
                                fill: true,
                                pointBackgroundColor: '#6366f1',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor, font: { size: 11, weight: '500' } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: {
                                    color: textColor,
                                    font: { size: 11 },
                                    callback: function(val) {
                                        if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                        if (val >= 1000) return (val / 1000).toFixed(0) + 'k';
                                        return val;
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: isDark ? '#1e293b' : '#0f172a',
                                titleColor: '#f8fafc',
                                bodyColor: '#cbd5e1',
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + Number(context.raw).toLocaleString() + ' RWF';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>