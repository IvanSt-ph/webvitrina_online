@props(['color' => 'green', 'icon' => 'ri-information-line'])

<div class="flex items-start gap-2 rounded-xl border border-{{ $color }}-100 bg-{{ $color }}-50 p-4 text-sm text-{{ $color }}-800">
  <i class="{{ $icon }} text-lg mt-0.5"></i>
  <div>{{ $slot }}</div>
</div>
