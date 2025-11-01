<x-seller-layout title="Мои товары" :hideHeader="true">

  {{-- ✅ Глобальный fix для Alpine --}}
  <style>[x-cloak]{display:none!important}</style>

  <div class="min-h-screen bg-white text-gray-800" 
       x-data="{ viewMode: localStorage.getItem('seller_view') || 'grid' }">

    <!-- 🔹 Заголовок страницы -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Мои товары</h1>
        <p class="text-sm text-gray-500 mt-1">Управляйте своим ассортиментом и следите за активностью</p>
      </div>
      <a href="{{ route('seller.products.create') }}"
         class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow transition">
        <i class="ri-add-line text-lg"></i> Добавить товар
      </a>
    </div>

    <!-- 📊 Аналитика -->
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 hover:shadow transition">
        <p class="text-sm text-gray-500">Всего товаров</p>
        <h3 class="text-2xl font-semibold mt-2 text-gray-800">{{ $products->total() ?? 0 }}</h3>
        <p class="text-xs text-gray-400 mt-1">+3 новых за неделю</p>
      </div>
      <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 hover:shadow transition">
        <p class="text-sm text-gray-500">Просмотры за неделю</p>
        <h3 class="text-2xl font-semibold mt-2 text-blue-600">1 248</h3>
        <p class="text-xs text-green-600 mt-1">▲ 12%</p>
      </div>
      <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 hover:shadow transition">
        <p class="text-sm text-gray-500">Средняя цена</p>
        <h3 class="text-2xl font-semibold mt-2 text-gray-800">
          {{ number_format($products->avg('price') ?? 0, 0, ',', ' ') }} ₽
        </h3>
      </div>
      <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 hover:shadow transition">
        <p class="text-sm text-gray-500">Активность продавца</p>
        <h3 class="text-2xl font-semibold mt-2 text-green-600">92%</h3>
        <p class="text-xs text-gray-400 mt-1">Онлайн 3 ч назад</p>
      </div>
    </section>

    <!-- 🧭 Фильтры и переключатель -->
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6">
      <div class="mb-5 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold text-gray-800">Все товары</h2>
            <!-- Переключатель -->
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
              <button 
                @click="viewMode='grid'; localStorage.setItem('seller_view','grid')" 
                :class="viewMode==='grid' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'" 
                class="px-3 py-1.5 text-sm transition">
                <i class="ri-layout-grid-fill text-lg"></i>
              </button>
              <button 
                @click="viewMode='list'; localStorage.setItem('seller_view','list')" 
                :class="viewMode==='list' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'" 
                class="px-3 py-1.5 text-sm transition border-l border-gray-200">
                <i class="ri-list-unordered text-lg"></i>
              </button>
            </div>
          </div>

          <select class="h-9 text-sm border border-gray-300 rounded-lg px-3 bg-white focus:ring-indigo-500 focus:border-indigo-500 w-48 sm:w-56">
            <option>Сначала новые</option>
            <option>Сначала дешёвые</option>
            <option>Сначала дорогие</option>
          </select>
        </div>

        <input type="text"
               placeholder="Поиск по названию или категории..."
               class="w-full h-10 text-sm border border-gray-300 rounded-lg px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white transition">
      </div>

      @if($products->count())

      {{-- 🟦 Плитка --}}
      <div 
        x-show="viewMode==='grid'" 
        x-cloak
        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-5 transition-all duration-300 ease-in-out">
        @foreach($products as $p)
          <div class="relative bg-white border border-gray-300 rounded-xl hover:shadow-md transition overflow-hidden">
            <div class="relative h-44 bg-gray-50 overflow-hidden pt-[5px] pl-[10px] pr-[10px] rounded-t-xl group">
              @if($p->image)
                <img src="{{ asset('storage/'.$p->image) }}" alt="{{ $p->title }}"
                     class="object-cover w-full h-full group-hover:scale-105 transition duration-300 rounded-t-[10px]">
              @else
                <div class="flex items-center justify-center h-full text-gray-400 text-xs">Без фото</div>
              @endif

              <span class="absolute top-2 left-2 text-xs px-2 py-0.5 rounded-md
                    {{ $p->stock>0 ? 'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">
                {{ $p->stock>0 ? 'Активен':'Нет' }}
              </span>

              <div class="absolute inset-0 bg-black/25 backdrop-blur-sm opacity-0 
                          group-hover:opacity-100 flex items-center justify-center gap-2 transition duration-300">
                <a href="{{ route('seller.products.edit',$p) }}"
                   class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md bg-white/90 hover:bg-gray-100">Редактировать</a>
                <form method="POST" action="{{ route('seller.products.destroy',$p) }}">
                  @csrf @method('DELETE')
                  <button type="submit"
                          class="px-3 py-1.5 text-xs font-medium border border-red-300 text-red-600 rounded-md bg-white/90 hover:bg-red-50">Удалить</button>
                </form>
              </div>
            </div>

            <div class="p-3">
              <h3 class="text-sm font-medium text-gray-800 line-clamp-1">{{ $p->title }}</h3>
              <div class="mt-1 text-xs text-gray-500 flex justify-between">
                <span>{{ $p->category->name ?? '—' }}</span>
                <span>{{ $p->city->name ?? '' }}</span>
              </div>
              <div class="mt-2 text-sm font-semibold text-gray-800">{{ number_format($p->price,0,',',' ') }} ₽</div>
            </div>

            <div class="bg-gray-50 text-[11px] text-gray-500 px-3 py-1.5 flex justify-between border-t border-gray-100">
              <span>{{ $p->created_at->format('d.m.Y') }}</span>
              <span>👁 {{ rand(10,250) }}</span>
            </div>
          </div>
        @endforeach
      </div>

      {{-- 🟧 Список --}}
      <div 
        x-show="viewMode==='list'" 
        x-cloak 
        class="flex flex-col gap-2 transition-all duration-300 ease-in-out">
        @foreach($products as $p)
          <div class="flex items-center justify-between gap-2 p-2 sm:p-3 bg-white border border-gray-200 rounded-xl hover:shadow transition">
            <div class="flex items-center gap-3 w-full min-w-0">
              <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gray-50 rounded-lg overflow-hidden flex-shrink-0">
                @if($p->image)
                  <img src="{{ asset('storage/'.$p->image) }}" alt="{{ $p->title }}" class="object-cover w-full h-full">
                @else
                  <div class="flex items-center justify-center h-full text-gray-400 text-xs">Без фото</div>
                @endif
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-sm font-medium text-gray-800 truncate">{{ $p->title }}</h3>
                <p class="text-xs text-gray-500 truncate">{{ $p->category->name ?? '—' }} • {{ $p->city->name ?? '' }}</p>
              </div>
            </div>

            <div class="flex flex-col items-end justify-center gap-1 text-right">
              <div class="text-sm font-semibold text-gray-800 whitespace-nowrap">{{ number_format($p->price,0,',',' ') }} ₽</div>
              <div class="flex items-center gap-2">
                <a href="{{ route('seller.products.edit',$p) }}" class="text-[11px] sm:text-xs text-indigo-600 hover:underline whitespace-nowrap">Редактировать</a>
                <form method="POST" action="{{ route('seller.products.destroy',$p) }}">
                  @csrf @method('DELETE')
                  <button type="submit" class="text-[11px] sm:text-xs text-red-600 hover:underline whitespace-nowrap">Удалить</button>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="mt-10">{{ $products->links() }}</div>

      @else
        <div class="text-center text-gray-500 mt-20">
          <p class="text-lg mb-2">Нет товаров</p>
          <a href="{{ route('seller.products.create') }}" class="text-indigo-600 hover:underline text-sm">Добавить первый товар</a>
        </div>
      @endif
    </section>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
  @include('layouts.mobile-bottom-seller-nav')

</x-seller-layout>
