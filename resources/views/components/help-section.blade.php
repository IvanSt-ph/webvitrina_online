@props(['icon' => 'ri-file-list-line', 'title' => ''])

<div class="space-y-4">
  <h2 class="flex items-center gap-2 text-xl font-semibold text-neutral-950 sm:text-2xl">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="{{ $icon }} text-lg"></i></span>
    {{ $title }}
  </h2>
  <div class="border-t border-neutral-100"></div>
  {{ $slot }}
</div>
