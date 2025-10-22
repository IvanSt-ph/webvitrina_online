@props([
  'label' => '',
  'value' => 0,
  'icon'  => null,   // ← сюда передаём класс Remix Icon, напр. "ri-grid-line"
  'color' => 'indigo',
])

@php
  $palette = [
    'blue'   => ['bg' => 'bg-blue-50',   'text' => 'text-blue-600'],
    'green'  => ['bg' => 'bg-green-50',  'text' => 'text-green-600'],
    'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600'],
    'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600'],
    'gray'   => ['bg' => 'bg-gray-50',   'text' => 'text-gray-600'],
  ];
  $bg   = $palette[$color]['bg']   ?? 'bg-indigo-50';
  $text = $palette[$color]['text'] ?? 'text-indigo-600';
@endphp

<div class="group bg-white border border-gray-100 rounded-xl shadow-sm p-4 hover:shadow-md transition">
  <div class="flex items-center justify-between gap-4">
    <div>
      <div class="text-xs text-gray-500">{{ $label }}</div>
      <div class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format($value, 0, '', ' ') }}</div>
    </div>

    @if($icon)
      <div class="shrink-0 {{ $bg }} {{ $text }} rounded-lg p-2">
        <i class="{{ $icon }} text-2xl leading-none"></i>
      </div>
    @endif
  </div>
</div>
