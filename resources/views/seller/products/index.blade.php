@php
    function delta($now, $prev) {
        if ($prev == 0) return $now > 0 ? '+100%' : '0%';
        $d = (($now - $prev) / $prev) * 100;
        return ($d >= 0 ? '+' : '') . round($d, 1) . '%';
    }
@endphp

<x-seller-layout title="Мои товары" :hideHeader="true">
  <style>[x-cloak]{display:none!important}</style>

  <!-- 🧩 Обёртка с Alpine -->
  <div x-data="{ viewMode: localStorage.getItem('seller_view') || 'grid', showConfirm:false, productId:null }"
       class="min-h-screen bg-white text-gray-800 pb-[5.5rem]">

    {{-- 🌌 Модалка подтверждения удаления --}}
    <div x-show="showConfirm"
         x-cloak
         class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl shadow-xl p-6 w-[90%] sm:w-[400px] border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Удалить товар?</h2>
        <p class="text-sm text-gray-600 mb-5">
          Это действие <span class="font-semibold text-red-600">необратимо</span>.<br>
          После удаления товар исчезнет из магазина навсегда.
        </p>
        <div class="flex justify-end gap-3">
          <button @click="showConfirm=false"
                  class="px-4 py-2 text-gray-600 hover:text-gray-800 text-sm">
            Отмена
          </button>

          <form :action="`/seller/products/${productId}`" method="POST" x-ref="deleteForm">
            @csrf @method('DELETE')
            <button type="submit"
                    class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:opacity-90 text-white rounded-lg text-sm shadow">
              Удалить
            </button>
          </form>
        </div>
      </div>
    </div>

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

    {{-- 🔹 дальше идёт весь твой код страницы как был --}}


    <!-- 📊 Аналитика -->
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 hover:shadow transition">
        <p class="text-sm text-gray-500">Всего товаров</p>

        <h3 class="text-2xl font-semibold mt-2 text-gray-800">{{ $products->total() ?? 0 }}</h3>
    <p class="text-xs mt-1 {{ $newProductsCount > 0 ? 'text-green-600' : 'text-gray-400' }}">
    {{ $newProductsCount > 0
        ? '+' . $newProductsCount . ' новых за период'
        : 'Без новых товаров'
    }}
</p>


      </div>
      <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 hover:shadow transition">
        <p class="text-sm text-gray-500">Просмотры за неделю</p>

@php $d = delta($summary->views, $prev->views); @endphp

<h3 class="text-2xl font-semibold mt-2 text-blue-600">
  {{ number_format($summary->views, 0, ',', ' ') }}
</h3>

<p class="text-xs mt-1 {{ str_starts_with($d,'+') ? 'text-green-600' : 'text-red-600' }}">
  {{ $d }}
</p>

      </div>
      <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 hover:shadow transition">
        <p class="text-sm text-gray-500">Средняя цена</p>
        <h3 class="text-2xl font-semibold mt-2 text-gray-800">
          {{ number_format($products->avg('price') ?? 0, 0, ',', ' ') }} ₽
        </h3>
      </div>
      <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 hover:shadow transition">


        <p class="text-sm text-gray-500">Активность продавца</p>

        <h3 class="text-2xl font-semibold mt-2
            {{ $activityPercent >= 70 ? 'text-green-600' : ($activityPercent >= 40 ? 'text-yellow-600' : 'text-red-600') }}">
            {{ $activityPercent }}%
        </h3>

        <p class="text-xs text-gray-400 mt-1">
            {{ $activityPercent >= 70 ? 'Высокая активность' : ($activityPercent >= 40 ? 'Средняя активность' : 'Низкая активность') }}
        </p>


      </div>
    </section>

    <!-- 🧭 Фильтры и переключатель -->
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6">
      <div class="mb-5 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold text-gray-800">Все товары</h2>

            <!-- 🔘 Переключатель -->
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
<button type="button"
        @click="productId={{ $p->id }}; showConfirm=true"
        class="px-3 py-1.5 text-xs font-medium border border-red-300 text-red-600 rounded-md bg-white/90 hover:bg-red-50">
  Удалить
</button>

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
            👁 {{ number_format($p->views_sum ?? 0, 0, ',', ' ') }}

            </div>
          </div>
        @endforeach
      </div>


{{-- 🟧 Список (ультра-компактный режим) --}}
<div 
  x-show="viewMode==='list'" 
  x-cloak 
  class="flex flex-col divide-y divide-gray-100 transition-all duration-300 ease-in-out">
  @foreach($products as $p)
    <div class="flex items-center justify-between bg-transparent hover:bg-gray-50 transition cursor-pointer
                w-[100%] lg:w-full h-[55px] px-1.5">
      
      <!-- 🖼 Изображение + инфо -->
      <div class="flex items-center gap-2 w-full overflow-hidden">
        @if($p->image)
          <img src="{{ asset('storage/'.$p->image) }}" alt="{{ $p->title }}"
               class="w-7 h-7 object-cover rounded-md flex-shrink-0">
        @else
          <div class="w-7 h-7 bg-gray-100 flex items-center justify-center text-[9px] text-gray-400">Нет</div>
        @endif

        <div class="flex flex-col justify-center min-w-0 leading-tight">
          <!-- 🔹 Название + статус -->
          <div class="flex items-center gap-1.5">
            <h3 class="text-[12.5px] font-medium text-gray-800 truncate">{{ $p->title }}</h3>
            <span class="text-[9px] px-1 py-[1px] rounded-md
                        {{ $p->stock>0 ? 'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">
              {{ $p->stock>0 ? 'Активен':'Нет' }}
            </span>
          </div>

          <!-- 🔸 всё остальное -->
          <p class="text-[10px] text-gray-500 truncate">
            {{ $p->category->name ?? '—' }} • {{ $p->city->name ?? '—' }} • 👁 {{ number_format($p->views_sum ?? 0, 0, ',', ' ') }}
• {{ $p->created_at->format('d.m.Y') }}
          </p>
        </div>
      </div>

      <!-- 💰 Цена + иконки -->
      <div class="flex items-center gap-[6px] flex-shrink-0">
        <span class="text-[12.5px] font-semibold text-gray-800 whitespace-nowrap">
          {{ number_format($p->price,0,',',' ') }} ₽
        </span>

        <a href="{{ route('seller.products.edit',$p) }}" 
           class="text-[14px] text-indigo-600 hover:text-indigo-800 transition">
           <i class="ri-edit-line"></i>
        </a>
<button type="button"
        @click="productId={{ $p->id }}; showConfirm=true"
        class="text-[14px] text-red-600 hover:text-red-700 transition">
  <i class="ri-delete-bin-6-line"></i>
</button>

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
