@if(in_array($role ?? 'guest', ['admin', 'manager']))
<section class="space-y-4">
    <h3 class="text-lg font-semibold text-teal-700 dark:text-teal-400 flex items-center gap-2">
        <i data-lucide="flask-conical" class="w-5 h-5"></i>
        Raw Materials &amp; Productions
    </h3>

    {{-- 4 stat cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Raw Materials</p>
                <i data-lucide="layers" class="w-4 h-4 text-teal-500"></i>
            </div>
            <p class="text-2xl font-bold text-teal-700 dark:text-teal-300">{{ $rawMaterialsTotal ?? 0 }}</p>
            <a href="{{ route('raw-materials.index') }}" class="text-xs text-teal-600 hover:underline mt-1 block">View all →</a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Low Stock</p>
                <i data-lucide="alert-triangle" class="w-4 h-4 {{ ($rawMaterialsLowStock ?? 0) > 0 ? 'text-amber-500' : 'text-gray-300 dark:text-gray-600' }}"></i>
            </div>
            <p class="text-2xl font-bold {{ ($rawMaterialsLowStock ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
                {{ $rawMaterialsLowStock ?? 0 }}
            </p>
            <span class="text-xs text-gray-400 mt-1 block">items below threshold</span>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">RM Stock Value</p>
                <i data-lucide="coins" class="w-4 h-4 text-teal-500"></i>
            </div>
            <p class="text-2xl font-bold text-teal-700 dark:text-teal-300">RWF {{ number_format($rawMaterialsStockValue ?? 0, 0) }}</p>
            <span class="text-xs text-gray-400 mt-1 block">at cost price</span>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Productions</p>
                <i data-lucide="factory" class="w-4 h-4 text-violet-500"></i>
            </div>
            <p class="text-2xl font-bold text-violet-700 dark:text-violet-300">{{ $monthProductions ?? 0 }}</p>
            <span class="text-xs text-gray-400 mt-1 block">this month ({{ $totalProductions ?? 0 }} total)</span>
        </div>

    </div>

    {{-- Two panels --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Low-stock raw materials list --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500"></i>
                    Low-Stock Raw Materials
                </h4>
                <a href="{{ route('raw-materials.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
            </div>

            @if(isset($lowStockRawMaterials) && $lowStockRawMaterials->count() > 0)
                <div class="space-y-3">
                    @foreach($lowStockRawMaterials as $rm)
                        @php
                            $stock    = $rm->current_stock ?? 0;
                            $pct      = min(100, max(0, ($stock / 5) * 100)); // threshold = 5
                            $barColor = $pct < 25 ? 'bg-rose-500' : ($pct < 60 ? 'bg-amber-400' : 'bg-teal-500');
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $rm->name }}</span>
                                <span class="{{ $stock <= 0 ? 'text-rose-600 font-semibold' : 'text-amber-600' }}">
                                    {{ number_format($stock, 1) }} units
                                </span>
                            </div>
                            <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full {{ $barColor }} rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i data-lucide="check-circle-2" class="w-10 h-10 mb-2 text-teal-400"></i>
                    <p class="text-sm">All raw materials are well-stocked</p>
                </div>
            @endif
        </div>

        {{-- Recent productions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <i data-lucide="factory" class="w-4 h-4 text-violet-500"></i>
                    Recent Productions
                </h4>
                <a href="{{ route('productions.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
            </div>

            @if(isset($recentProductions) && $recentProductions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">Product</th>
                                <th class="px-3 py-2 text-right font-medium">Qty</th>
                                <th class="px-3 py-2 text-left font-medium">Status</th>
                                <th class="px-3 py-2 text-left font-medium">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($recentProductions as $prod)
                                @php
                                    $sc = match($prod->status ?? '') {
                                        'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                        'pending'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                        'reversed'  => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                                        default     => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-200">
                                        <a href="{{ route('productions.show', $prod) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ optional($prod->product)->name ?? '—' }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold text-violet-700 dark:text-violet-300">
                                        {{ number_format($prod->quantity, 0) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[0.65rem] font-medium {{ $sc }}">
                                            {{ ucfirst($prod->status ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                        {{ $prod->created_at->format('M d') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i data-lucide="factory" class="w-10 h-10 mb-2"></i>
                    <p class="text-sm">No productions recorded yet</p>
                    <a href="{{ route('productions.create') }}" class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                        Start a production run →
                    </a>
                </div>
            @endif
        </div>

    </div>
</section>
@endif
