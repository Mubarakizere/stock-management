<x-app-layout>
    <x-slot name="header">
        @php
            $roleColors = [
                'admin'   => 'bg-indigo-600',
                'manager' => 'bg-green-600',
                'cashier' => 'bg-yellow-500',
            ];
            $titles = [
                'admin'   => 'Business Dashboard',
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
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <x-stat label="Total Sales" :value="$totalSales" />
                <x-stat label="Total Profit" :value="$totalProfit" color="text-green-600" />
                <x-stat label="Pending Balances" :value="$pendingBalances" color="text-red-600" />
                <x-stat label="Total Purchases" :value="$totalPurchases" />
                @if($sections['finance'])
                    <x-stat label="Credits (In)" :value="$totalCredits" />
                    <x-stat label="Debits (Out)" :value="$totalDebits" />
                    <x-stat label="Net Balance"
                            :value="$netBalance"
                            color="{{ $netBalance >= 0 ? 'text-green-600' : 'text-red-600' }}" />
                @endif
            </div>
        </section>
        @endif

        {{-- 📈 Charts --}}
        @if($sections['charts'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Sales Performance</h3>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-10">
                <canvas id="trendChart" height="120"></canvas>

                <div class="mt-10">
                    <h4 class="text-md font-semibold text-gray-700 mb-3">Sales (Last 30 Days)</h4>
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </section>
        @endif

        {{-- 🧠 Advanced Insights --}}
        @if($sections['insights'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Advanced Insights</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- 🥇 Top Products Table & Chart --}}
                <div class="bg-white rounded-xl shadow p-5 border border-gray-100 space-y-5">
                    <h4 class="text-md font-semibold text-gray-700 flex items-center">
                        <x-lucide-trophy class="w-5 h-5 text-yellow-500 mr-2" /> Top 5 Selling Products
                    </h4>
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-2">Product</th>
                                <th class="px-4 py-2 text-right">Qty Sold</th>
                                <th class="px-4 py-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $p)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-4 py-2">{{ $p->product->name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($p->total_qty, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                        {{ number_format($p->total_revenue, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-3 text-gray-500">No sales data yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        <canvas id="topProductsChart" height="140"></canvas>
                    </div>
                </div>

                {{-- 🧍 Top Customers Table & Chart --}}
                <div class="bg-white rounded-xl shadow p-5 border border-gray-100 space-y-5">
                    <h4 class="text-md font-semibold text-gray-700 flex items-center">
                        <x-lucide-users class="w-5 h-5 text-indigo-600 mr-2" /> Top 5 Customers
                    </h4>
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-2">Customer</th>
                                <th class="px-4 py-2 text-right">Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topCustomers as $c)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-4 py-2">{{ $c->customer->name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-2 text-right text-indigo-600 font-semibold">
                                        {{ number_format($c->total_spent, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center py-3 text-gray-500">No customer data yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        <canvas id="topCustomersChart" height="140"></canvas>
                    </div>
                </div>
            </div>
        </section>
        @endif
    </div>

    {{-- 📊 Chart Scripts --}}
    @if($sections['charts'] || $sections['insights'])
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // === Trend Chart ===
                const ctxTrend = document.getElementById('trendChart')?.getContext('2d');
                if (ctxTrend) {
                    new Chart(ctxTrend, {
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
                        options: { responsive: true, scales: { y: { beginAtZero: true } } },
                    });
                }

                // === 30-Day Sales Chart ===
                fetch('{{ route('dashboard.sales.chart') }}')
                    .then(res => res.json())
                    .then(data => {
                        const ctxSales = document.getElementById('salesChart')?.getContext('2d');
                        if (ctxSales) {
                            new Chart(ctxSales, {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: 'Daily Sales',
                                        data: data.sales,
                                        backgroundColor: 'rgba(79,70,229,0.6)',
                                        borderRadius: 5,
                                    }],
                                },
                                options: { responsive: true, scales: { y: { beginAtZero: true } } },
                            });
                        }
                    });

                // === Top Products Chart ===
                const productsCtx = document.getElementById('topProductsChart')?.getContext('2d');
                if (productsCtx) {
                    new Chart(productsCtx, {
                        type: 'bar',
                        data: {
                            labels: @json($topProducts->pluck('product.name')),
                            datasets: [{
                                label: 'Qty Sold',
                                data: @json($topProducts->pluck('total_qty')),
                                backgroundColor: '#4F46E5',
                                borderRadius: 6,
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true } },
                        },
                    });
                }

                // === Top Customers Chart ===
                const customersCtx = document.getElementById('topCustomersChart')?.getContext('2d');
                if (customersCtx) {
                    new Chart(customersCtx, {
                        type: 'doughnut',
                        data: {
                            labels: @json($topCustomers->pluck('customer.name')),
                            datasets: [{
                                label: 'Total Spent',
                                data: @json($topCustomers->pluck('total_spent')),
                                backgroundColor: [
                                    '#4F46E5', '#6366F1', '#818CF8', '#A5B4FC', '#C7D2FE'
                                ],
                                borderWidth: 1,
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { position: 'bottom' },
                            },
                        },
                    });
                }
            });
        </script>
    @endif
</x-app-layout>
