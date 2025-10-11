{{-- Управление баннерами в админке --}}
{{-- resources/views/admin/banners/index.blade.php --}}

@extends('admin.layout')

@section('content')
@php
  $sort = request('sort', 'latest');
  $sortOptions = [
      'latest'      => 'Сначала новые',
      'oldest'      => 'Сначала старые',
      'title'       => 'По названию',
      'order_asc'   => 'По порядку (1 → 9)',
      'order_desc'  => 'По порядку (9 → 1)',
  ];

  $sortedBanners = match($sort) {
      'oldest'     => $banners->sortBy('created_at'),
      'title'      => $banners->sortBy(fn($b) => mb_strtolower($b->title ?? '')),
      'order_asc'  => $banners->sortBy('sort_order'),
      'order_desc' => $banners->sortByDesc('sort_order'),
      default      => $banners->sortByDesc('created_at'),
  };
@endphp


{{-- === Верхняя панель управления === --}}
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
  <a href="{{ route('admin.banners.create') }}" 
     class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium 
            hover:bg-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    <span>Добавить баннер</span>
  </a>

  {{-- Сортировка --}}
  <form method="GET" 
        class="flex items-center gap-2 text-sm bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm">
    <label for="sort" class="text-gray-600 whitespace-nowrap">Сортировка:</label>
    <select name="sort" id="sort" onchange="this.form.submit()" 
            class="border-0 text-gray-800 font-medium bg-transparent focus:ring-0 cursor-pointer">
      @foreach($sortOptions as $value => $label)
        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </form>
</div>

{{-- === Сообщение об успехе === --}}
@if(session('success'))
  <div class="mb-6 p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm">
    ✅ {{ session('success') }}
  </div>
@endif

{{-- === Если баннеров нет === --}}
@if($sortedBanners->isEmpty())
  <div class="p-10 text-center text-gray-400 border rounded-xl bg-white/60 shadow-inner">
    Пока нет баннеров
  </div>
@else

{{-- === Сетка карточек баннеров === --}}
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
  @foreach($sortedBanners as $b)
    @php
      $img = $b->image_desktop
          ? asset('storage/'.$b->image_desktop)
          : ($b->image_tablet
              ? asset('storage/'.$b->image_tablet)
              : ($b->image_mobile
                  ? asset('storage/'.$b->image_mobile)
                  : asset('storage/banners/sale1.jpg')));
    @endphp

    {{-- 💠 Карточка баннера --}}
    <div class="group bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-lg 
                transition-all duration-300 hover:-translate-y-1 flex flex-col">
      
      {{-- 🖼 Превью --}}
      <div class="relative w-full aspect-[3.5/1] bg-gray-100 overflow-hidden">
        <img src="{{ $img }}" alt="Баннер" 
             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
        @unless($b->active)
          <div class="absolute inset-0 bg-gray-800/60 flex items-center justify-center text-white text-sm font-medium">
            Скрыт
          </div>
        @endunless
      </div>

      {{-- 📋 Контент карточки --}}
      <div class="flex flex-col flex-grow p-4">
        <div class="flex justify-between items-center mb-1">
          <h3 class="font-semibold text-gray-800 truncate">
            {{ $b->title ?: 'Без названия' }}
          </h3>
          <span class="text-xs text-gray-400">{{ $b->created_at?->format('d.m.Y') }}</span>
        </div>

        <p class="text-sm text-gray-500 truncate mb-3">
          Ссылка: 
          @if($b->link)
            <a href="{{ $b->link }}" target="_blank" class="text-indigo-600 hover:underline break-all">
              {{ $b->link }}
            </a>
          @else
            <span class="text-gray-400">—</span>
          @endif
        </p>

        <div class="flex justify-between items-center mt-auto text-xs text-gray-500 pt-2 border-t border-gray-100">
          {{-- Порядок --}}
          <span>Порядок: <b class="text-gray-700">{{ $b->sort_order }}</b></span>

          {{-- Справа: статус + кнопки --}}
          <div class="flex items-center gap-1.5">
            @if($b->active)
              <span class="px-2 py-0.5 rounded-md text-white text-xs font-semibold bg-green-500 shadow-sm">
                Активен
              </span>
            @else
              <span class="px-2 py-0.5 rounded-md text-white text-xs font-semibold bg-red-500 shadow-sm">
                Скрыт
              </span>
            @endif

            {{-- ✏️ --}}
            <a href="{{ route('admin.banners.edit', $b) }}" 
               class="px-2 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100 hover:text-indigo-700 transition">
               ✏️
            </a>

            {{-- 🗑 --}}
            <form action="{{ route('admin.banners.destroy', $b) }}" method="POST" 
                  onsubmit="return confirm('Удалить баннер?')" class="inline">
              @csrf @method('DELETE')
              <button class="px-2 py-1 rounded-md border border-gray-300 text-red-600 hover:bg-red-50 hover:border-red-300 transition">
                🗑
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endif
@endsection
