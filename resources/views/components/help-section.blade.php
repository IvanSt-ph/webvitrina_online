@props(['icon' => 'ri-file-list-line', 'title' => ''])

<div class="space-y-4">
  <h2 class="text-2xl font-semibold text-indigo-600 flex items-center gap-2">
    <i class="{{ $icon }} text-indigo-500 text-2xl"></i>
    {{ $title }}
  </h2>
  <div class="border-t border-gray-100"></div>
  {{ $slot }}
</div>
