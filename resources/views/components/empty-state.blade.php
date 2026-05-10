@props([
    'icon' => 'ri-inbox-line',
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12 sm:py-20 px-4 bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm']) }}>
    <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mb-4">
        <i class="{{ $icon }} text-4xl"></i>
    </div>

    <div class="text-gray-700 text-lg font-semibold">{{ $title }}</div>

    @if($description)
        <p class="text-sm text-gray-400 mt-1">{{ $description }}</p>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-5">
            {{ $slot }}
        </div>
    @endif
</div>
