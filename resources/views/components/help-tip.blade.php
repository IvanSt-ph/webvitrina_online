@props(['color' => 'indigo', 'icon' => 'ri-lightbulb-line'])

<div class="border-l-4 rounded-xl px-4 py-3 text-sm flex items-start gap-2
     bg-{{ $color }}-50 border-{{ $color }}-200 text-{{ $color }}-800 shadow-sm">
  <i class="{{ $icon }} text-lg mt-0.5"></i>
  <div>{{ $slot }}</div>
</div>
