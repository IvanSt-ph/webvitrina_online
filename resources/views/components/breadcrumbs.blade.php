@props(['items' => []])

<nav class="text-sm text-gray-500 mb-6" aria-label="Breadcrumb">
  <ol class="flex flex-wrap items-center gap-1">
    <li>
      <a href="{{ route('home') }}" class="hover:text-indigo-600">Главная</a>
    </li>
    @foreach ($items as $label => $url)
      <li class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mx-2 text-gray-400" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5l7 7-7 7" />
        </svg>
        @if ($loop->last)
          <span class="text-gray-700 font-medium">{{ $label }}</span>
        @else
          <a href="{{ $url }}" class="hover:text-indigo-600">{{ $label }}</a>
        @endif
      </li>
    @endforeach
  </ol>
</nav>
