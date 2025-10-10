<x-app-layout>
    {{--  Header --}}
    <x-slot name="header">
        <div class="bg-indigo-600 text-white px-4 py-3 rounded-md shadow flex justify-between items-center">
            <h2 class="font-semibold text-xl leading-tight">Reports & Insights</h2>
            <span class="text-sm opacity-90">Updated {{ now()->format('d M Y H:i') }}</span>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        {{--  Filter Section --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Filter Reports</h3>
            <form method="GET" action="{{ route('reports.index') }}"
                  class="bg-white p-6 rounded-xl shadow border border-gray-100 flex flex-col md:flex-row md:items-end gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="{{ $start }}"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ $end }}"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg shadow hover:bg-indigo-700 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10m-7 4h4m-9 4h14"/>
                    </svg>
                    Apply Filters
                </button>
            </form>
        </section>

        {{--  Summary Cards --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">
                Report Summary ({{ $start }} → {{ $end }})
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <x-stat label="Total Sales" :value="$totalSales" />
                <x-stat label="Total Profit" :value="$totalProfit" color="text-green-600" />
                <x-stat label="Pending Balances" :value="$pendingBalances" color="text-red-600" />
                <x-stat label="Total Purchases" :value="$totalPurchases" />
                <x-stat label="Credits (In)" :value="$credits" />
                <x-stat label="Debits (Out)" :value="$debits" />
                <x-stat label="Net Balance" :value="$netBalance" color="{{ $netBalance >= 0 ? 'text-green-600' : 'text-red-600' }}" />
                <x-stat label="Loans Given" :value="$loansGiven" />
                <x-stat label="Loans Taken" :value="$loansTaken" />
            </div>
        </section>

        {{--  Export Buttons --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Export Reports</h3>
            <div class="bg-white rounded-xl shadow border border-gray-100 p-6 flex flex-wrap gap-4">
                <a href="{{ route('reports.export.sales.csv', ['start_date' => $start, 'end_date' => $end]) }}"
                   class="btn bg-green-600 hover:bg-green-700 text-white">
                    <x-icon.download class="w-5 h-5 mr-2" /> Sales Report (CSV)
                </a>

                <a href="{{ route('reports.export.finance.pdf', ['start_date' => $start, 'end_date' => $end]) }}"
                   class="btn bg-indigo-600 hover:bg-indigo-700 text-white">
                    <x-icon.file-text class="w-5 h-5 mr-2" /> Finance Report (PDF)
                </a>

                <a href="{{ route('reports.export.insights.pdf', ['start_date' => $start, 'end_date' => $end]) }}"
                   class="btn bg-purple-600 hover:bg-purple-700 text-white">
                    <x-icon.bar-chart class="w-5 h-5 mr-2" /> Insights Report (PDF)
                </a>
            </div>
        </section>

        {{--  Business Insights --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Business Insights</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <x-metric-card label="Revenue Growth (vs last month)"
                               :value="number_format($revenueGrowth, 1) . '%'"
                               :color="$revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600'" />
                <x-metric-card label="Profit Margin"
                               :value="number_format($profitMargin, 1) . '%'"
                               color="text-green-600" />
                <x-metric-card label="Expense Ratio"
                               :value="number_format($expenseRatio, 1) . '%'"
                               color="text-indigo-600" />
            </div>
        </section>

        {{--  Revenue vs Expenses Chart --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">
                Revenue vs Expenses (Last 6 Months)
            </h3>
            <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
                <canvas id="revenueExpenseChart" height="100"></canvas>
            </div>
        </section>

        {{--  Insights & Analytics --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Insights & Analytics</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{--  Top Products --}}
                <x-top-table title="Top 5 Products" :headers="['Product', 'Total Sales']" :items="$topProducts"
                             :columns="['product.name', 'total_sales']" color="text-indigo-600" />

                {{--  Top Customers --}}
                <x-top-table title="Top 5 Customers" :headers="['Customer', 'Total Spent']" :items="$topCustomers"
                             :columns="['customer.name', 'total_spent']" color="text-green-600" />
            </div>

            {{--  Monthly Sales Chart --}}
            <div class="mt-10 bg-white rounded-xl shadow p-6 border border-gray-100">
                <h4 class="font-semibold text-gray-800 mb-4">
                    Monthly Sales Trend (Last 6 Months)
                </h4>
                <canvas id="monthlySalesChart" height="100"></canvas>
            </div>
        </section>
    </div>

    {{--  Chart.js Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Monthly Sales Chart
            new Chart(document.getElementById('monthlySalesChart'), {
                type: 'line',
                data: {
                    labels: @json($months),
                    datasets: [{
                        label: 'Total Sales',
                        data: @json($monthlySales),
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79,70,229,0.1)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 2
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
            });

            // Revenue vs Expense Chart
            new Chart(document.getElementById('revenueExpenseChart'), {
                type: 'line',
                data: {
                    labels: @json($revenueExpensesChart->pluck('month')),
                    datasets: [
                        {
                            label: 'Revenue',
                            data: @json($revenueExpensesChart->pluck('sales')),
                            borderColor: '#4F46E5',
                            backgroundColor: 'rgba(79,70,229,0.1)',
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2
                        },
                        {
                            label: 'Expenses',
                            data: @json($revenueExpensesChart->pluck('expenses')),
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2
                        },
                    ]
                },
                options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
            });
        });
    </script>
</x-app-layout>
