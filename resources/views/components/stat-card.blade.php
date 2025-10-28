@props(['icon', 'label', 'value', 'color' => 'indigo'])

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-start justify-between">
    <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-{{ $color }}-100 text-{{ $color }}-600">
            <i class="{{ $icon }} text-xl"></i>
        </div>
        <span class="text-sm text-gray-500 font-medium">{{ $label }}</span>
    </div>
    <div class="text-3xl font-bold text-gray-800">
        {{ number_format($value, 0, '.', ' ') }}
    </div>
</div>
