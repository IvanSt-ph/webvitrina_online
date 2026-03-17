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
         class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-xl p-6 w-full max-w-md border border-gray-200/50">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center shadow-md">
            <i class="ri-delete-bin-6-line text-white text-lg"></i>
          </div>
          <h2 class="text-lg font-semibold text-gray-900">Удалить товар?</h2>
        </div>
        <p class="text-sm text-gray-600 mb-5 pl-13">
          Это действие <span class="font-semibold text-rose-600">необратимо</span>.<br>
          После удаления товар исчезнет из магазина навсегда.
        </p>
        <div class="flex justify-end gap-3">
          <button @click="showConfirm=false"
                  class="px-4 py-2 text-gray-600 hover:text-gray-800 text-sm font-medium transition-colors">
            Отмена
          </button>

          <form :action="`/seller/products/${productId}`" method="POST" x-ref="deleteForm">
            @csrf @method('DELETE')
            <button type="submit"
                    class="relative overflow-hidden group px-4 py-2 bg-rose-500/90 hover:bg-rose-600 
                           text-white font-medium rounded-lg shadow-md hover:shadow-lg 
                           transition-all duration-300 transform hover:-translate-y-0.5
                           flex items-center gap-2 backdrop-blur-sm border border-rose-400/30 text-sm">
              <span class="relative z-10 flex items-center gap-1">
                <i class="ri-delete-bin-line"></i>
                Удалить
              </span>
              <span class="absolute inset-0 bg-rose-600 translate-y-full 
                           group-hover:translate-y-0 transition-transform duration-300"></span>
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
         class="relative overflow-hidden group px-5 py-2.5 bg-indigo-500/90 hover:bg-indigo-600 
                text-white text-sm font-medium rounded-xl shadow-md hover:shadow-lg 
                transition-all duration-300 transform hover:-translate-y-0.5
                flex items-center gap-2 backdrop-blur-sm border border-indigo-400/30">
        <span class="relative z-10 flex items-center gap-2">
          <i class="ri-add-line text-lg"></i>
          Добавить товар
          <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
        </span>
        <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                     group-hover:translate-y-0 transition-transform duration-300"></span>
      </a>
    </div>

    <!-- 📊 Аналитика -->
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white/80 backdrop-blur-sm border border-gray-100/80 rounded-xl p-6 hover:bg-white hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
        <p class="text-sm text-gray-500 flex items-center gap-1">
          <i class="ri-stack-line text-indigo-400 text-xs"></i>
          Всего товаров
        </p>
        <h3 class="text-2xl font-semibold mt-2 text-gray-800">{{ $products->total() ?? 0 }}</h3>
        <p class="text-xs mt-1 {{ $newProductsCount > 0 ? 'text-emerald-600' : 'text-gray-400' }} flex items-center gap-1">
          <i class="ri-{{ $newProductsCount > 0 ? 'arrow-up-line' : 'minus-line' }}"></i>
          {{ $newProductsCount > 0 ? '+' . $newProductsCount . ' новых за период' : 'Без новых товаров' }}
        </p>
      </div>

      <div class="bg-white/80 backdrop-blur-sm border border-gray-100/80 rounded-xl p-6 hover:bg-white hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
        <p class="text-sm text-gray-500 flex items-center gap-1">
          <i class="ri-eye-line text-indigo-400 text-xs"></i>
          Просмотры за неделю
        </p>
        @php $d = delta($summary->views, $prev->views); @endphp
        <h3 class="text-2xl font-semibold mt-2 text-blue-600">
          {{ number_format($summary->views, 0, ',', ' ') }}
        </h3>
        <p class="text-xs mt-1 {{ str_starts_with($d,'+') ? 'text-emerald-600' : 'text-rose-600' }} flex items-center gap-1">
          <i class="ri-{{ str_starts_with($d,'+') ? 'arrow-up-line' : 'arrow-down-line' }}"></i>
          {{ $d }}
        </p>
      </div>

      <div class="bg-white/80 backdrop-blur-sm border border-gray-100/80 rounded-xl p-6 hover:bg-white hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
        <p class="text-sm text-gray-500 flex items-center gap-1">
          <i class="ri-price-tag-3-line text-indigo-400 text-xs"></i>
          Средняя цена
        </p>
        <h3 class="text-2xl font-semibold mt-2 text-gray-800">
          {{ number_format($products->avg('price') ?? 0, 0, ',', ' ') }} ₽
        </h3>
      </div>

      <div class="bg-white/80 backdrop-blur-sm border border-gray-100/80 rounded-xl p-6 hover:bg-white hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
        <p class="text-sm text-gray-500 flex items-center gap-1">
          <i class="ri-flashlight-line text-indigo-400 text-xs"></i>
          Активность продавца
        </p>
        <h3 class="text-2xl font-semibold mt-2
            {{ $activityPercent >= 70 ? 'text-emerald-600' : ($activityPercent >= 40 ? 'text-amber-600' : 'text-rose-600') }}">
            {{ $activityPercent }}%
        </h3>
        <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
          <i class="ri-information-line text-indigo-300"></i>
          {{ $activityPercent >= 70 ? 'Высокая активность' : ($activityPercent >= 40 ? 'Средняя активность' : 'Низкая активность') }}
        </p>
      </div>
    </section>

    <!-- 🧭 Фильтры и переключатель -->
    <section class="bg-white/90 backdrop-blur-sm border border-gray-100/80 rounded-xl shadow-sm p-6">
      <form method="GET" action="{{ route('seller.products.index') }}" class="mb-5 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
              <div class="w-5 h-5 rounded bg-indigo-100 flex items-center justify-center">
                <i class="ri-store-2-line text-indigo-600 text-xs"></i>
              </div>
              Все товары
            </h2>

            <!-- 🔘 Переключатель вида -->
            <div class="flex items-center border border-gray-200/80 rounded-lg overflow-hidden bg-white/50 backdrop-blur-sm">
              <button
                type="button"
                @click="viewMode='grid'; localStorage.setItem('seller_view','grid')"
                :class="viewMode==='grid' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'"
                class="px-3 py-1.5 text-sm transition">
                <i class="ri-layout-grid-fill text-lg"></i>
              </button>
              <button
                type="button"
                @click="viewMode='list'; localStorage.setItem('seller_view','list')"
                :class="viewMode==='list' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'"
                class="px-3 py-1.5 text-sm transition border-l border-gray-200/80">
                <i class="ri-list-unordered text-lg"></i>
              </button>
            </div>
          </div>

          <!-- 🔃 СОРТИРОВКА -->
          <select
            name="sort"
            onchange="this.form.submit()"
            class="h-10 text-sm border border-gray-200/80 rounded-xl px-3 bg-white/80 backdrop-blur-sm
                   focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50 
                   transition-all duration-200 outline-none w-48 sm:w-56">
            <option value="new"        @selected($sort === 'new')>Сначала новые</option>
            <option value="cheap"      @selected($sort === 'cheap')>Сначала дешёвые</option>
            <option value="expensive"  @selected($sort === 'expensive')>Сначала дорогие</option>
            <option value="popular"    @selected($sort === 'popular')>По просмотрам</option>
          </select>
        </div>

        <!-- 🔍 ПОИСК -->
        <div class="relative group">
          <div class="absolute -inset-0.5 bg-indigo-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
          <input
            type="text"
            name="q"
            value="{{ $search }}"
            placeholder="Поиск по названию или категории..."
            class="relative w-full h-11 text-sm border border-gray-200/80 rounded-xl px-4
                   focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50
                   bg-white/80 backdrop-blur-sm transition-all duration-200 outline-none"
          />
        </div>
      </form>

      @if($products->count())
        {{-- 🟦 Плитка --}}
        <div 
          x-show="viewMode==='grid'" 
          x-cloak
          class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-5 transition-all duration-300 ease-in-out">
          @foreach($products as $p)
            <div class="relative bg-white/80 backdrop-blur-sm border border-gray-200/80 rounded-xl hover:bg-white hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden group">
              <div class="relative h-44 bg-gray-50/50 overflow-hidden pt-[5px] px-[10px] rounded-t-xl">
                @if($p->image)
                  <img src="{{ asset('storage/'.$p->image) }}" alt="{{ $p->title }}"
                       class="object-cover w-full h-full group-hover:scale-105 transition duration-300 rounded-t-[10px]">
                @else
                  <div class="flex items-center justify-center h-full text-gray-400 text-xs bg-gray-100/50">Без фото</div>
                @endif

                <span class="absolute top-2 left-2 text-xs px-2 py-0.5 rounded-full
                      {{ $p->stock>0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/50' : 'bg-rose-50 text-rose-700 border border-rose-200/50' }}">
                  {{ $p->stock>0 ? 'Активен' : 'Нет' }}
                </span>

                <div class="absolute inset-0 bg-black/25 backdrop-blur-sm opacity-0 
                            group-hover:opacity-100 flex items-center justify-center gap-2 transition duration-300">
                  <a href="{{ route('seller.products.edit',$p) }}"
                     class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/90 hover:bg-white text-indigo-700 border border-indigo-200/50 backdrop-blur-sm transition-all hover:-translate-y-0.5">
                    <i class="ri-edit-line mr-1"></i>Редактировать
                  </a>
                  <button type="button"
                          @click="productId={{ $p->id }}; showConfirm=true"
                          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/90 hover:bg-white text-rose-700 border border-rose-200/50 backdrop-blur-sm transition-all hover:-translate-y-0.5">
                    <i class="ri-delete-bin-6-line mr-1"></i>Удалить
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

              <div class="bg-indigo-50/30 text-[11px] text-gray-500 px-3 py-1.5 flex justify-between border-t border-indigo-100/30">
                <span class="flex items-center gap-1">
                  <i class="ri-calendar-line text-indigo-300"></i>
                  {{ $p->created_at->format('d.m.Y') }}
                </span>
                <span class="flex items-center gap-1">
                  <i class="ri-eye-line text-indigo-300"></i>
                  {{ number_format($p->views_sum ?? 0, 0, ',', ' ') }}
                </span>
              </div>
            </div>
          @endforeach
        </div>

        {{-- 🟧 Список (ультра-компактный режим) --}}
        <div 
          x-show="viewMode==='list'" 
          x-cloak 
          class="flex flex-col divide-y divide-indigo-100/30 transition-all duration-300 ease-in-out bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200/80 overflow-hidden">
          @foreach($products as $p)
            <div class="flex items-center justify-between bg-transparent hover:bg-indigo-50/30 transition-all cursor-pointer
                        w-full h-[55px] px-3 hover:pl-4 duration-200">
              
              <!-- 🖼 Изображение + инфо -->
              <div class="flex items-center gap-3 w-full overflow-hidden">
                @if($p->image)
                  <img src="{{ asset('storage/'.$p->image) }}" alt="{{ $p->title }}"
                       class="w-8 h-8 object-cover rounded-lg flex-shrink-0 border border-gray-200/50">
                @else
                  <div class="w-8 h-8 bg-indigo-50/50 rounded-lg flex items-center justify-center text-[9px] text-indigo-400 border border-indigo-200/50">
                    <i class="ri-image-line"></i>
                  </div>
                @endif

                <div class="flex flex-col justify-center min-w-0 leading-tight">
                  <!-- 🔹 Название + статус -->
                  <div class="flex items-center gap-1.5">
                    <h3 class="text-[12.5px] font-medium text-gray-800 truncate">{{ $p->title }}</h3>
                    <span class="text-[8px] px-1.5 py-[2px] rounded-full
                                {{ $p->stock>0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/50' : 'bg-rose-50 text-rose-700 border border-rose-200/50' }}">
                      {{ $p->stock>0 ? 'Активен' : 'Нет' }}
                    </span>
                  </div>

                  <!-- 🔸 всё остальное -->
                  <p class="text-[10px] text-gray-500 truncate flex items-center gap-1">
                    <span>{{ $p->category->name ?? '—' }}</span>
                    <span class="w-1 h-1 rounded-full bg-indigo-300"></span>
                    <span>{{ $p->city->name ?? '—' }}</span>
                    <span class="w-1 h-1 rounded-full bg-indigo-300"></span>
                    <span>👁 {{ number_format($p->views_sum ?? 0, 0, ',', ' ') }}</span>
                    <span class="w-1 h-1 rounded-full bg-indigo-300"></span>
                    <span><i class="ri-calendar-line text-indigo-300"></i> {{ $p->created_at->format('d.m.Y') }}</span>
                  </p>
                </div>
              </div>

              <!-- 💰 Цена + иконки -->
              <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-[12.5px] font-semibold text-gray-800 whitespace-nowrap">
                  {{ number_format($p->price,0,',',' ') }} ₽
                </span>

                <a href="{{ route('seller.products.edit',$p) }}" 
                   class="w-7 h-7 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 flex items-center justify-center transition-all hover:-translate-y-0.5">
                   <i class="ri-edit-line text-sm"></i>
                </a>
                <button type="button"
                        @click="productId={{ $p->id }}; showConfirm=true"
                        class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-all hover:-translate-y-0.5">
                  <i class="ri-delete-bin-6-line text-sm"></i>
                </button>
              </div>
            </div>
          @endforeach
        </div>

        <div class="mt-10">{{ $products->links() }}</div>

      @else
        <div class="text-center text-gray-500 mt-20 py-12 bg-white/50 backdrop-blur-sm rounded-xl border border-gray-200/50">
          <div class="w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center mx-auto mb-4">
            <i class="ri-store-2-line text-indigo-400 text-2xl"></i>
          </div>
          <p class="text-lg mb-2">Нет товаров</p>
          <a href="{{ route('seller.products.create') }}" 
             class="relative overflow-hidden group inline-flex px-5 py-2.5 bg-indigo-500/90 hover:bg-indigo-600 
                    text-white text-sm font-medium rounded-xl shadow-md hover:shadow-lg 
                    transition-all duration-300 transform hover:-translate-y-0.5
                    items-center gap-2 backdrop-blur-sm border border-indigo-400/30">
            <span class="relative z-10 flex items-center gap-2">
              <i class="ri-add-line text-lg"></i>
              Добавить первый товар
            </span>
            <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                         group-hover:translate-y-0 transition-transform duration-300"></span>
          </a>
        </div>
      @endif
    </section>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
  @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>