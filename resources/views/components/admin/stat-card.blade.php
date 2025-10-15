@props(['label', 'value', 'color' => 'indigo', 'icon' => '📊'])

<div class="p-5 bg-white border border-gray-100 shadow-sm rounded-xl flex items-center justify-between hover:shadow-md transition">
    <div>
        <div class="text-sm text-gray-500">{{ $label }}</div>
        <div class="text-2xl font-bold text-gray-800 mt-1">{{ $value }}</div>
    </div>
    <div class="text-3xl select-none">
        {{ $icon }}
    </div>
</div>
