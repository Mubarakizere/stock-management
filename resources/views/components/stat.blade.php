@props(['label', 'value'])
<div class="bg-white shadow rounded-lg p-4 text-center">
    <p class="text-gray-500 text-sm">{{ $label }}</p>
    <p class="text-xl font-semibold text-indigo-600">{{ number_format($value, 0) }}</p>
</div>
