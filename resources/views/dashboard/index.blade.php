<x-app-layout>
    <x-slot name="header">
        @php
            $roleColors = [
                'admin' => 'bg-indigo-600',
                'manager' => 'bg-green-600',
                'cashier' => 'bg-yellow-500',
            ];
            $titles = [
                'admin' => 'Business Dashboard',
                'manager' => 'Management Dashboard',
                'cashier' => 'Daily Sales Dashboard',
            ];
            $color = $roleColors[$role] ?? 'bg-gray-600';
            $title = $titles[$role] ?? 'Dashboard';
        @endphp

        <div class="{{ $color }} text-white px-4 py-3 rounded-md shadow flex justify-between items-center">
            <h2 class="font-semibold text-xl leading-tight">{{ $title }}</h2>
            <span class="text-sm opacity-90">Updated {{ now()->format('d M Y H:i') }}</span>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

        {{-- 📊 KPIs --}}
        @if($sections['kpis'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Key Performance Indicators</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <x-stat label="Total Sales" :value="$totalSales" />
                <x-stat label="Total Purchases" :value="$totalPurchases" />
                @if($sections['finance'])
                    <x-stat label="Credits (Money In)" :value="$totalCredits" />
                    <x-stat label="Debits (Money Out)" :value="$totalDebits" />
                    <x-stat label="Net Balance" :value="$netBalance" color="{{ $netBalance >= 0 ? 'text-green-600' : 'text-red-600' }}" />
                @endif
            </div>
        </section>
        @endif

        {{-- 💰 Loans --}}
        @if($sections['loans'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Loans Overview</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <x-stat label="Loans Given" :value="$totalLoansGiven" />
                <x-stat label="Loans Taken" :value="$totalLoansTaken" />
                <x-stat label="Active Loans" :value="$activeLoans" />
                <x-stat label="Paid Loans" :value="$paidLoans" />
                <x-stat label="Total Loan Payments" :value="$totalLoanPayments" />
            </div>
        </section>
        @endif

        {{-- 📈 Charts --}}
        @if($sections['charts'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Sales & Purchases (Last 6 Months)</h3>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <canvas id="salesChart" height="120"></canvas>
            </div>
        </section>
        @endif

        {{-- 🧾 Recent Transactions --}}
        @if($sections['recentTransactions'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Recent Financial Transactions</h3>
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-4 py-2">User</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2">Customer</th>
                            <th class="px-4 py-2">Supplier</th>
                            <th class="px-4 py-2">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $t)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $t->user->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded font-semibold
                                        {{ $t->type == 'debit' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                        {{ ucfirst($t->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right font-semibold">{{ number_format($t->amount, 2) }}</td>
                                <td class="px-4 py-2">{{ $t->customer->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $t->supplier->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $t->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-3 text-gray-500">No recent transactions</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endif

        {{-- 💼 Cashier --}}
        @if($sections['cashierDaily'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Today’s Performance</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <x-stat label="All Sales Today" :value="$todaySalesTotal" />
                <x-stat label="My Sales Total" :value="$myTodaySalesTotal" />
                <x-stat label="My Sales Count" :value="$myTodaySalesCount" />
            </div>

            <h3 class="text-lg font-semibold text-indigo-600 mb-4">My Latest Sales</h3>
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-4 py-2">Customer</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myLatestSales as $sale)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $sale->customer->name ?? 'Walk-in' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($sale->total_amount, 2) }}</td>
                                <td class="px-4 py-2">{{ $sale->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-3 text-gray-500">No sales today</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endif

        {{-- 📱 PWA Install Banner --}}
        @if($sections['pwaBanner'] ?? false)
        <section class="mt-12">
            <div class="bg-gradient-to-r from-indigo-900 via-purple-900 to-gray-800 text-white rounded-2xl p-8 shadow-lg flex flex-col md:flex-row items-center justify-between">
                <div class="mb-6 md:mb-0">
                    <h3 class="text-2xl font-semibold mb-2">Stock Manager App</h3>
                    <p class="text-gray-200">Install our Progressive Web App for a faster, full-screen experience on iOS or Android.</p>
                </div>

                {{-- Install PWA Button --}}
                <button id="installPWA"
                        class="hidden inline-flex items-center bg-white text-gray-900 px-5 py-3 rounded-xl font-semibold hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2a10 10 0 1010 10A10.011 10.011 0 0012 2zm1 14.59V8h-2v8.59L7.41 12 6 13.41 12 19.41l6-6-1.41-1.41z"/>
                    </svg>
                    Install App
                </button>
            </div>
        </section>
        @endif
    </div>

    {{-- Chart.js --}}
    @if($sections['charts'])
        @vite(['resources/js/chart.js'])
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const ctx = document.getElementById('salesChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($months),
                        datasets: [
                            {
                                label: 'Sales',
                                data: @json($salesTrend),
                                borderColor: '#4F46E5',
                                backgroundColor: 'rgba(79,70,229,0.1)',
                                tension: 0.35,
                                fill: true,
                            },
                            {
                                label: 'Purchases',
                                data: @json($purchaseTrend),
                                borderColor: '#EC4899',
                                backgroundColor: 'rgba(236,72,153,0.1)',
                                tension: 0.35,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'top' } },
                        scales: { y: { beginAtZero: true } },
                    },
                });
            });
        </script>
    @endif

    {{-- PWA Install Logic --}}
    <script>
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const installBtn = document.getElementById('installPWA');
            if (installBtn) installBtn.style.display = 'flex';
            installBtn.addEventListener('click', async () => {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') console.log('PWA installed');
                deferredPrompt = null;
            });
        });
    </script>
</x-app-layout>
