@props(['color' => 'green', 'icon' => 'ri-information-line'])

<div class="bg-{{ $color }}-50 border border-{{ $color }}-100 rounded-xl p-4 text-sm text-{{ $color }}-800 flex items-start gap-2 shadow-sm">
  <i class="{{ $icon }} text-lg mt-0.5"></i>
  <div>{{ $slot }}</div>
</div>
