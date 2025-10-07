@props(['label' => 'Label', 'value' => 0])

@php
    $iconMap = [
        'sales'     => 'dollar-sign',
        'purchases' => 'shopping-cart',
        'credits'   => 'credit-card',
        'debits'    => 'arrow-down-circle',
        'balance'   => 'scale',
        'loan'      => 'banknote',
        'payment'   => 'wallet',
        'count'     => 'hash',
    ];

    $selectedIcon = 'bar-chart-2'; // default
    foreach ($iconMap as $key => $icon) {
        if (str_contains(strtolower($label), $key)) {
            $selectedIcon = $icon;
            break;
        }
    }

    $colorMap = [
        'sales' => 'text-green-600',
        'purchases' => 'text-pink-600',
        'credits' => 'text-blue-600',
        'debits' => 'text-red-600',
        'balance' => 'text-emerald-600',
        'loan' => 'text-yellow-600',
        'payment' => 'text-purple-600',
        'count' => 'text-gray-600',
    ];

    $color = 'text-gray-700';
    foreach ($colorMap as $key => $clr) {
        if (str_contains(strtolower($label), $key)) {
            $color = $clr;
            break;
        }
    }
@endphp

<div class="bg-white p-4 rounded-xl shadow hover:shadow-md transition">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm text-gray-500">{{ $label }}</span>
        <i data-lucide="{{ $selectedIcon }}" class="w-5 h-5 {{ $color }}"></i>
    </div>
    <div class="text-2xl font-bold {{ $color }}">
        {{ number_format($value, 2) }}
    </div>
</div>
