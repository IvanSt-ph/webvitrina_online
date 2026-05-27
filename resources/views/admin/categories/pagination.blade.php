@if ($categories->hasPages())
  <nav class="flex items-center space-x-1 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
    @if ($categories->onFirstPage())
      <span class="px-3 py-1 text-slate-400">&laquo;</span>
    @else
      <a href="{{ $categories->previousPageUrl() }}" class="rounded-md px-3 py-1 text-indigo-600 transition hover:bg-indigo-50">&laquo;</a>
    @endif

    @foreach ($categories->links()->elements[0] ?? [] as $page => $url)
      @if ($page == $categories->currentPage())
        <span class="rounded-md bg-indigo-600 px-3 py-1 text-white shadow">{{ $page }}</span>
      @else
        <a href="{{ $url }}" class="rounded-md px-3 py-1 text-slate-700 transition hover:bg-slate-100">{{ $page }}</a>
      @endif
    @endforeach

    @if ($categories->hasMorePages())
      <a href="{{ $categories->nextPageUrl() }}" class="rounded-md px-3 py-1 text-indigo-600 transition hover:bg-indigo-50">&raquo;</a>
    @else
      <span class="px-3 py-1 text-slate-400">&raquo;</span>
    @endif
  </nav>
@endif
