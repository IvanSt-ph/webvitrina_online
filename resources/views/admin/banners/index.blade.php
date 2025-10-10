@extends('admin.layout')

@section('content')
<div class="flex items-center justify-between mb-8">
  <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">🖼 Баннеры</h1>

  <a href="{{ route('admin.banners.create') }}" 
     class="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-xl hover:bg-gray-800 transition-all duration-200 shadow-sm">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    <span>Добавить баннер</span>
  </a>
</div>

@if(session('success'))
  <div class="mb-5 p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm">
    ✅ {{ session('success') }}
  </div>
@endif

@php
  $sort = request('sort', 'latest');
  $sortOptions = [
      'latest' => 'Сначала новые',
      'oldest' => 'Сначала старые',
      'title'  => 'По названию',
  ];
  $sortedBanners = match($sort) {
      'oldest' => $banners->sortBy('created_at'),
      'title'  => $banners->sortBy(fn($b) => mb_strtolower($b->title ?? '')),
      default  => $banners->sortByDesc('created_at'),
  };
@endphp

{{-- Сортировка --}}
<div class="flex justify-end mb-6">
  <form method="GET" class="flex items-center gap-2 text-sm">
    <label for="sort" class="text-gray-600">Сортировка:</label>
    <select name="sort" id="sort" onchange="this.form.submit()" 
            class="border-gray-300 rounded-lg text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 transition">
      @foreach($sortOptions as $value => $label)
        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </form>
</div>

@if($sortedBanners->isEmpty())
  <div class="p-10 text-center text-gray-400 border rounded-xl bg-white/60">
    Пока нет баннеров
  </div>
@else
  <div class="space-y-3">
    @foreach($sortedBanners as $b)
      <div class="group flex items-center gap-5 bg-white border border-gray-200 hover:border-gray-300 rounded-xl p-4 transition-all duration-200 hover:shadow-sm">
        
        <div class="relative w-44 h-24 rounded-lg overflow-hidden flex-shrink-0 border border-gray-100">
          <img src="{{ asset('storage/'.$b->image) }}" alt="Баннер" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
          @unless($b->active)
            <div class="absolute inset-0 bg-gray-800/40 flex items-center justify-center text-white text-sm">Скрыт</div>
          @endunless
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-gray-800 truncate">
              {{ $b->title ?: 'Без названия' }}
            </h3>
            <span class="text-xs text-gray-400">
              {{ $b->created_at->format('d.m.Y') }}
            </span>
          </div>

          <div class="text-sm text-gray-500 mt-1 truncate">
            Ссылка: 
            @if($b->link)
              <a href="{{ $b->link }}" class="text-indigo-600 hover:underline break-all">{{ $b->link }}</a>
            @else
              <span class="text-gray-400">—</span>
            @endif
          </div>

          <div class="text-xs text-gray-400 mt-2">
            Порядок: <b class="text-gray-700">{{ $b->sort_order }}</b> 
            • Статус: 
            <span class="{{ $b->active ? 'text-green-600' : 'text-gray-400' }}">
              {{ $b->active ? 'Активен' : 'Неактивен' }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <a href="{{ route('admin.banners.edit', $b) }}" 
             class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
             ✏️
          </a>
          <form action="{{ route('admin.banners.destroy', $b) }}" method="POST" 
                onsubmit="return confirm('Удалить баннер?')" class="inline">
            @csrf @method('DELETE')
            <button class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-red-600 hover:bg-red-50 transition">
              🗑
            </button>
          </form>
        </div>
      </div>
    @endforeach
  </div>
@endif
@endsection
