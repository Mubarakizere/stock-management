<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ ucfirst($role) }} Dashboard
        </h2>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        {{-- 🧮 KPIs --}}
        @if($sections['kpis'] ?? false)
            <div>
                <h3 class="text-lg font-bold text-indigo-600 mb-4">Key Performance Indicators</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <x-stat label="Total Sales" :value="$totalSales ?? 0" />
                    <x-stat label="Total Purchases" :value="$totalPurchases ?? 0" />
                    @if($sections['finance'] ?? false)
                        <x-stat label="Total Credits" :value="$totalCredits ?? 0" />
                        <x-stat label="Total Debits" :value="$totalDebits ?? 0" />
                        <x-stat label="Net Balance" :value="$netBalance ?? 0" />
                    @endif
                </div>
            </div>
        @endif


        {{-- 💰 Loan Summary --}}
        @if($sections['loans'] ?? false)
            <div>
                <h3 class="text-lg font-bold text-indigo-600 mb-4">Loan Overview</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <x-stat label="Loans Given" :value="$totalLoansGiven ?? 0" />
                    <x-stat label="Loans Taken" :value="$totalLoansTaken ?? 0" />
                    <x-stat label="Active Loans" :value="$activeLoans ?? 0" />
                    <x-stat label="Paid Loans" :value="$paidLoans ?? 0" />
                    <x-stat label="Total Payments" :value="$totalLoanPayments ?? 0" />
                </div>
            </div>
        @endif


        {{-- 📊 Charts --}}
        @if($sections['charts'] ?? false)
            <div>
                <h3 class="text-lg font-bold text-indigo-600 mb-4">Monthly Trends (Last 6 Months)</h3>
                <div class="bg-white p-4 rounded-xl shadow">
                    <canvas id="salesChart" height="120"></canvas>
                </div>
            </div>
        @endif


        {{-- 🧾 Recent Transactions --}}
        @if($sections['recentTransactions'] ?? false)
            <div>
                <h3 class="text-lg font-bold text-indigo-600 mb-4">Recent Transactions</h3>
                <div class="overflow-x-auto bg-white shadow rounded-xl">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-3 py-2">User</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Customer</th>
                                <th class="px-3 py-2">Supplier</th>
                                <th class="px-3 py-2">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $t)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-3 py-2">{{ $t->user->name ?? 'N/A' }}</td>
                                    <td class="px-3 py-2">{{ ucfirst($t->type) }}</td>
                                    <td class="px-3 py-2">{{ number_format($t->amount) }}</td>
                                    <td class="px-3 py-2">{{ $t->customer->name ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $t->supplier->name ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $t->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-3 text-gray-500">No recent transactions</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif


        {{-- 💼 Cashier Daily Summary --}}
        @if($sections['cashierDaily'] ?? false)
            <div>
                <h3 class="text-lg font-bold text-indigo-600 mb-4">Today’s Summary</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    <x-stat label="All Sales Today" :value="$todaySalesTotal ?? 0" />
                    <x-stat label="My Sales Today" :value="$myTodaySalesTotal ?? 0" />
                    <x-stat label="My Sales Count" :value="$myTodaySalesCount ?? 0" />
                </div>

                <h3 class="text-lg font-bold text-indigo-600 mb-4">My Latest Sales</h3>
                <div class="overflow-x-auto bg-white shadow rounded-xl">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-3 py-2">Customer</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myLatestSales ?? [] as $sale)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-3 py-2">{{ $sale->customer->name ?? 'Walk-in' }}</td>
                                    <td class="px-3 py-2">{{ number_format($sale->total_amount) }}</td>
                                    <td class="px-3 py-2">{{ $sale->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-3 text-gray-500">No sales today</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

    {{-- Chart.js --}}
    @if($sections['charts'] ?? false)
        @vite(['resources/js/chart.js'])
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const ctx = document.getElementById('salesChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($months ?? []),
                        datasets: [
                            {
                                label: 'Sales',
                                data: @json($salesTrend ?? []),
                                borderColor: '#4F46E5',
                                tension: 0.3,
                                fill: false,
                            },
                            {
                                label: 'Purchases',
                                data: @json($purchaseTrend ?? []),
                                borderColor: '#EC4899',
                                tension: 0.3,
                                fill: false,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'top' } },
                    },
                });
            });
        </script>
    @endif

</x-app-layout>
