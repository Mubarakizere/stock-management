@extends('layouts.app')

@section('content')
<div class="p-6 space-y-8">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Business Dashboard</h1>
        <p class="text-sm text-gray-500">Updated {{ now()->format('d M Y H:i') }}</p>
    </div>

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
        @php
            $cards = [
                ['label'=>'Total Sales','value'=>$totalSales,'color'=>'text-green-600'],
                ['label'=>'Total Purchases','value'=>$totalPurchases,'color'=>'text-red-600'],
                ['label'=>'Credits','value'=>$totalCredits,'color'=>'text-blue-600'],
                ['label'=>'Debits','value'=>$totalDebits,'color'=>'text-rose-600'],
                ['label'=>'Net Balance','value'=>$netBalance,'color'=>$netBalance>=0?'text-green-700':'text-red-700'],
                ['label'=>'Loans Given','value'=>$totalLoansGiven,'color'=>'text-yellow-600'],
            ];
        @endphp
        @foreach($cards as $c)
            <div class="bg-white p-4 shadow rounded-lg">
                <p class="text-xs text-gray-500 font-semibold">{{ $c['label'] }}</p>
                <p class="text-2xl font-bold {{ $c['color'] }}">{{ number_format($c['value'],2) }}</p>
            </div>
        @endforeach
    </div>

    {{-- CHARTS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-2">Sales vs Purchases (Last 6 Months)</h2>
            <div id="salesPurchasesChart" class="h-64"></div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-2">Cash Flow Trend</h2>
            <div id="cashFlowChart" class="h-64"></div>
        </div>
    </div>

    {{-- RECENT TRANSACTIONS --}}
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-3">Recent Transactions</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border border-gray-200">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="p-2 text-left">Date</th>
                        <th class="p-2 text-left">Type</th>
                        <th class="p-2 text-left">Amount</th>
                        <th class="p-2 text-left">Description</th>
                        <th class="p-2 text-left">User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $t)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-2">{{ $t->date }}</td>
                            <td class="p-2">
                                <span class="px-2 py-1 text-xs rounded
                                    {{ $t->type=='credit'?'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($t->type) }}
                                </span>
                            </td>
                            <td class="p-2 font-semibold">{{ number_format($t->amount,2) }}</td>
                            <td class="p-2">{{ $t->description ?? '-' }}</td>
                            <td class="p-2">{{ $t->user->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-3 text-center text-gray-500">No transactions found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('scripts')
{{-- ApexCharts CDN --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    const months = @json($months);
    const salesData = @json($salesTrend);
    const purchaseData = @json($purchaseTrend);

    // Sales vs Purchases
    new ApexCharts(document.querySelector("#salesPurchasesChart"), {
        chart: { type: 'bar', height: 250, toolbar: {show:false}},
        series: [
            {name: 'Sales', data: salesData},
            {name: 'Purchases', data: purchaseData}
        ],
        xaxis: { categories: months },
        colors: ['#22c55e','#ef4444'],
        dataLabels: { enabled: false },
        legend: { position: 'top' }
    }).render();

    // Cash Flow
    new ApexCharts(document.querySelector("#cashFlowChart"), {
        chart: { type: 'line', height: 250, toolbar: {show:false}},
        series: [{
            name: 'Net Flow',
            data: salesData.map((v,i)=>v - purchaseData[i])
        }],
        xaxis: { categories: months },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#3b82f6']
    }).render();
</script>
@endsection
