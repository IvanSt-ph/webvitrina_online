@props(['color' => 'indigo', 'icon' => 'ri-lightbulb-line'])

<div class="flex items-start gap-2 rounded-xl border border-{{ $color }}-200 bg-{{ $color }}-50 px-4 py-3 text-sm text-{{ $color }}-800">
  <i class="{{ $icon }} text-lg mt-0.5"></i>
  <div>{{ $slot }}</div>
</div>
