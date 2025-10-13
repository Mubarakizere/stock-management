@props(['title' => '', 'value' => '', 'color' => 'indigo'])

<div class="p-4 bg-{{ $color }}-50 rounded-lg shadow-sm text-center">
    <p class="text-gray-500 text-sm">{{ $title }}</p>
    <p class="text-xl font-bold text-{{ $color }}-700">{{ $value }}</p>
</div>
