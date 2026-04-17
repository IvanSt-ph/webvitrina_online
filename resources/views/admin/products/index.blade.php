@extends('admin.layout')

@section('title', 'Товары')

@section('content')

<div 
  x-data="{
    // === Поиск ===
    query: '',
    results: [],
    loading: false,
    
    // === Мобильное меню ===
    mobileFilterOpen: false,

    // === Видимость столбцов ===
    columns: {
      id: false,
      image: true,
      title: true,
      sku: true,
      category: true,
      price: true,
      stock: true,
      location: true,
      seller: false,
      actions: true,
    },

    // === Методы ===
    highlight(text) {
      if (!this.query) return text;
      const safeQuery = this.query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const regex = new RegExp('(' + safeQuery + ')', 'gi');
      return text.replace(regex, '<mark class=\'bg-yellow-200 text-gray-900 rounded px-0.5\'>$1</mark>');
    },

    async search() {
      if (this.query.length < 2) { this.results = []; return; }
      this.loading = true;
      try {
        const res = await fetch(`{{ route('admin.products.search') }}?q=${encodeURIComponent(this.query)}`);
        this.results = await res.json();
      } catch (e) { console.error(e); }
      this.loading = false;
    },

    clear() { this.query=''; this.results=[]; },
    
    getVisibleCount() {
      let count = 0;
      for (let key in this.columns) {
        if (this.columns[key]) count++;
      }
      return count;
    }
  }"
  class="space-y-4 md:space-y-6">
  
@if(session('success'))
<div
    x-data="{ show: true }"
    x-init="setTimeout(() => show = false, 3500)"
    x-show="show"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-400"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
    class="backdrop-blur-xl bg-emerald-50/80 border border-emerald-300/60 shadow-xl
           rounded-2xl p-3 md:p-4 flex items-center gap-3 md:gap-4 mb-4 md:mb-6"
>
    <div class="flex items-center justify-center w-8 h-8 md:w-10 md:h-10 rounded-xl
                bg-emerald-100 border border-emerald-300 shadow-inner">
        <i class="ri-check-line text-xl md:text-2xl text-emerald-600"></i>
    </div>
    <div class="flex-1">
        <div class="text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        <div class="text-xs text-emerald-600/80 mt-0.5">Операция выполнена успешно</div>
    </div>
    <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 transition">
        <i class="ri-close-line text-xl"></i>
    </button>
</div>
@endif

  {{-- ===== Заголовок, поиск и кнопки ===== --}}
  <div class="flex flex-col gap-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="bg-indigo-100 text-indigo-600 p-2 rounded-lg">
          <i class="ri-box-3-line text-xl"></i>
        </div>
        <div>
          <h1 class="text-xl md:text-2xl font-semibold text-gray-800">Список товаров</h1>
          <p class="text-xs md:text-sm text-gray-500">Всего товаров: {{ $products->total() }}</p>
        </div>
      </div>
      
      <a href="{{ route('admin.products.create') }}"
         class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl shadow hover:bg-indigo-700 transition">
        <i class="ri-add-line text-lg"></i>
        <span class="hidden sm:inline">Добавить</span>
      </a>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      {{-- 🔍 Live-поиск --}}
      <div class="relative flex-1">
        <div class="relative group">
          <input type="text"
                 x-model="query"
                 @input.debounce.400ms="search"
                 placeholder="Поиск по названию или артикулу..."
                 class="w-full pl-10 pr-9 py-2.5 text-sm border border-gray-300 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition">
          <i class="ri-search-line absolute left-3 top-3 text-gray-400 group-focus-within:text-indigo-500"></i>
          <div x-show="loading" class="absolute right-3 top-3 text-gray-400 animate-spin">⏳</div>
        </div>

        <div 
          x-show="results.length"
          x-transition:enter="transition ease-out duration-200"
          x-transition:leave="transition ease-in duration-150"
          @click.outside="results=[]"
          class="absolute z-40 w-full mt-2 bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden max-h-96 overflow-y-auto"
        >
          <template x-for="p in results" :key="p.id">
            <a :href="`/admin/products/${p.id}/edit`"
               class="flex items-center gap-3 px-4 py-3 border-b last:border-none hover:bg-indigo-50 transition">
              <img :src="p.image ? '/storage/' + p.image : '/images/no-image.png'"
                   class="w-12 h-12 rounded-md border object-cover shadow-sm">
              <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900 text-sm line-clamp-2 break-words"
                     x-html="highlight(p.title)"></div>
                <div class="text-xs text-gray-500" x-text="Number(p.price).toLocaleString('ru-RU') + ' ₽'"></div>
                <div class="text-[11px] text-gray-400 font-mono break-all"
                     x-html="'SKU: ' + highlight(p.sku ?? '')"></div>
              </div>
              <i class="ri-edit-2-line text-indigo-500 text-lg"></i>
            </a>
          </template>
          <button @click="clear" class="w-full text-center py-2 text-sm text-gray-500 hover:bg-gray-100 transition">
            Очистить результаты
          </button>
        </div>
      </div>

      <div class="flex gap-2">
        {{-- Кнопка очистки с полным текстом --}}
        <form action="{{ route('admin.products.purge-old') }}" method="POST">
          @csrf
          <button class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition whitespace-nowrap"
                  onclick="return confirm('Удалить товары, удалённые более 90 дней назад?')">
            🧹 Очистить старые товары
          </button>
        </form>
      </div>
    </div>
  </div>

  {{-- 🔘 Переключатели столбцов (ДЕСКТОП) --}}
  <div class="hidden md:flex flex-wrap items-center gap-2 text-xs">
    <template x-for="(active, key) in columns" :key="key">
      <button @click="columns[key] = !columns[key]"
              class="px-3 py-1.5 font-medium rounded-lg border transition whitespace-nowrap"
              :class="columns[key]
                ? 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700 shadow-sm'
                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
        <i class="ri-eye-line mr-1 text-sm"></i>
        <span x-text="{
          id: 'ID',
          image: 'Фото',
          title: 'Название',
          sku: 'Артикул',
          category: 'Категория',
          price: 'Цена',
          stock: 'Остаток',
          location: 'Местоположение',
          seller: 'Продавец',
          actions: 'Действия'
        }[key]"></span>
      </button>
    </template>
  </div>

  {{-- 🔘 Выпадающее меню фильтров (МОБИЛЬНАЯ ВЕРСИЯ) --}}
  <div class="md:hidden relative">
    <button @click="mobileFilterOpen = !mobileFilterOpen"
            class="w-full flex items-center justify-between px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
      <span class="flex items-center gap-2">
        <i class="ri-layout-column-line text-base"></i>
        <span>Настройка отображения полей</span>
        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full" x-text="getVisibleCount()"></span>
      </span>
      <i class="ri-arrow-down-s-line text-base" :class="{'rotate-180': mobileFilterOpen}"></i>
    </button>
    
    <div x-show="mobileFilterOpen" 
         @click.away="mobileFilterOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="absolute z-30 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-xl p-3">
      <div class="grid grid-cols-2 gap-2">
        <button @click="columns.id = !columns.id"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.id ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.id}"></i>
          <span>ID</span>
        </button>
        <button @click="columns.image = !columns.image"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.image ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.image}"></i>
          <span>Фото</span>
        </button>
        <button @click="columns.title = !columns.title"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.title ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.title}"></i>
          <span>Название</span>
        </button>
        <button @click="columns.sku = !columns.sku"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.sku ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.sku}"></i>
          <span>Артикул</span>
        </button>
        <button @click="columns.category = !columns.category"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.category ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.category}"></i>
          <span>Категория</span>
        </button>
        <button @click="columns.price = !columns.price"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.price ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.price}"></i>
          <span>Цена</span>
        </button>
        <button @click="columns.stock = !columns.stock"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.stock ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.stock}"></i>
          <span>Остаток</span>
        </button>
        <button @click="columns.location = !columns.location"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.location ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.location}"></i>
          <span>Местоположение</span>
        </button>
        <button @click="columns.seller = !columns.seller"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.seller ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.seller}"></i>
          <span>Продавец</span>
        </button>
        <button @click="columns.actions = !columns.actions"
                class="px-3 py-2 text-xs font-medium rounded-lg border transition text-left flex items-center gap-2"
                :class="columns.actions ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300'">
          <i class="ri-eye-line text-sm" :class="{'ri-eye-off-line': !columns.actions}"></i>
          <span>Действия</span>
        </button>
      </div>
    </div>
  </div>

  {{-- ===== МОБИЛЬНЫЕ КАРТОЧКИ (с учетом видимости полей) ===== --}}
  <div class="md:hidden space-y-3">
    @forelse($products as $product)
      <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition">
        <div class="p-4">
          {{-- Верхняя часть с фото и названием --}}
          <div class="flex gap-3">
            <div x-show="columns.image" class="flex-shrink-0">
              <img src="{{ $product->image ? asset('storage/'.$product->image) : '/images/no-image.png' }}"
                   class="w-20 h-20 object-cover rounded-xl border border-gray-200">
            </div>
            <div class="flex-1 min-w-0">
              <h3 x-show="columns.title" class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2 leading-tight" title="{{ $product->title }}">
                {{ $product->title }}
              </h3>
              <div x-show="columns.category" class="text-xs text-gray-500">
                <span class="font-medium">Категория:</span> {{ $product->category?->name ?? '—' }}
              </div>
              <div x-show="columns.sku" class="text-xs text-gray-500 mt-0.5">
                <span class="font-medium">Артикул:</span> {{ $product->sku ?? '—' }}
              </div>
              <div x-show="columns.id" class="text-xs text-gray-400 mt-0.5">
                <span class="font-medium">ID:</span> {{ $product->id }}
              </div>
            </div>
          </div>
          
          {{-- Разделитель --}}
          <div class="border-t border-gray-100 my-3"></div>
          
          {{-- Нижняя часть с ценой, остатком и действиями --}}
          <div class="flex items-center justify-between">
            <div class="flex flex-col">
              <div x-show="columns.price" class="text-lg font-bold text-indigo-600">
                {{ number_format($product->price, 2, '.', ' ') }} ₽
              </div>
              <div x-show="columns.stock" class="mt-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                  @if($product->stock <= 3) bg-red-100 text-red-700
                  @elseif($product->stock < 10) bg-yellow-100 text-yellow-700
                  @else bg-green-100 text-green-700 @endif">
                  <i class="ri-stack-line mr-1 text-xs"></i>
                  {{ $product->stock }} шт.
                </span>
              </div>
            </div>
            <div x-show="columns.actions" class="flex gap-2">
              <a href="{{ route('admin.products.edit', $product) }}"
                 class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 transition"
                 title="Редактировать">
                <i class="ri-edit-2-line text-base"></i>
              </a>
              <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                    onsubmit="return confirm('Удалить товар {{ $product->title }}?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="p-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition"
                        title="Удалить">
                  <i class="ri-delete-bin-line text-base"></i>
                </button>
              </form>
            </div>
          </div>
          
          {{-- Доп. информация (локация, продавец) --}}
          <div x-show="columns.location || columns.seller" class="mt-3 pt-2 border-t border-gray-50 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-400">
            <span x-show="columns.location" class="flex items-center gap-1">
              <i class="ri-map-pin-line text-xs"></i> {{ $product->city?->name ?? '—' }}
            </span>
            <span x-show="columns.seller" class="flex items-center gap-1">
              <i class="ri-store-line text-xs"></i> {{ $product->seller?->name ?? '—' }}
            </span>
          </div>
        </div>
      </div>
    @empty
      <div class="bg-white rounded-2xl p-8 text-center text-gray-500 text-sm border border-gray-200">
        <i class="ri-box-3-line text-4xl mb-2 block"></i>
        Нет товаров 😔
      </div>
    @endforelse
  </div>

  {{-- ===== ДЕСКТОПНАЯ ТАБЛИЦА ===== --}}
  <div class="hidden md:block bg-white border border-gray-100 rounded-2xl shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm text-gray-800">
      <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
        <tr>
          <th x-show="columns.id" class="px-4 py-3 text-left font-semibold">ID</th>
          <th x-show="columns.image" class="px-4 py-3 text-left font-semibold">Фото</th>
          <th x-show="columns.title" class="px-4 py-3 text-left font-semibold">Название</th>
          <th x-show="columns.sku" class="px-4 py-3 text-left font-semibold">Артикул</th>
          <th x-show="columns.category" class="px-4 py-3 text-left font-semibold">Категория</th>
          <th x-show="columns.price" class="px-4 py-3 text-left font-semibold">Цена</th>
          <th x-show="columns.stock" class="px-4 py-3 text-left font-semibold">Остаток</th>
          <th x-show="columns.location" class="px-4 py-3 text-left font-semibold">Местоположение</th>
          <th x-show="columns.seller" class="px-4 py-3 text-left font-semibold">Продавец</th>
          <th x-show="columns.actions" class="px-4 py-3 text-right font-semibold">Действия</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($products as $product)
          <tr class="hover:bg-indigo-50/30 transition duration-200">
            <td x-show="columns.id" class="px-4 py-3 text-gray-500 align-top">{{ $product->id }}</td>
            <td x-show="columns.image" class="px-4 py-3 align-top">
              <img src="{{ $product->image ? asset('storage/'.$product->image) : '/images/no-image.png' }}"
                   class="w-12 h-12 md:w-14 md:h-14 object-cover rounded-lg border border-gray-200 shadow-sm">
            </td>
            <td x-show="columns.title" class="px-4 py-3 align-top">
              <div class="font-medium text-gray-900 line-clamp-2 break-words" title="{{ $product->title }}">
                {{ $product->title }}
              </div>
            </td>
            <td x-show="columns.sku" class="px-4 py-3 font-mono text-xs text-gray-700 align-top break-all">
              {{ $product->sku ?? '—' }}
            </td>
            <td x-show="columns.category" class="px-4 py-3 text-gray-700 align-top break-words">
              {{ $product->category?->name ?? '—' }}
            </td>
            <td x-show="columns.price" class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap align-top">
              {{ number_format($product->price, 2, '.', ' ') }} ₽
            </td>
            <td x-show="columns.stock" class="px-4 py-3 align-top">
              <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold
                @if($product->stock <= 3) bg-red-100 text-red-700
                @elseif($product->stock < 10) bg-yellow-100 text-yellow-700
                @else bg-green-100 text-green-700 @endif">
                {{ $product->stock }} шт.
              </span>
            </td>
            <td x-show="columns.location" class="px-4 py-3 text-gray-600 align-top break-words">
              {{ $product->city?->name ?? '—' }},
              <span class="text-gray-400">{{ $product->country?->name ?? $product->city?->country?->name ?? '—' }}</span>
            </td>
            <td x-show="columns.seller" class="px-4 py-3 text-gray-700 align-top break-words">
              {{ $product->seller?->name ?? '—' }}
            </td>
            <td x-show="columns.actions" class="px-4 py-3 text-right align-top">
              <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.products.edit', $product) }}"
                   class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition"
                   title="Редактировать">
                  <i class="ri-edit-2-line text-sm md:text-base"></i>
                </a>
                <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                      onsubmit="return confirm('Удалить товар {{ $product->title }}?')">
                  @csrf @method('DELETE')
                  <button type="submit"
                          class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition"
                          title="Удалить">
                    <i class="ri-delete-bin-line text-sm md:text-base"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="px-4 py-8 text-center text-gray-500 text-sm">Нет товаров 😔</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ===== Пагинация ===== --}}
  @if ($products->count())
    <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-600">
      <div class="text-xs md:text-sm">
        Показано <b>{{ $products->firstItem() }}</b>–<b>{{ $products->lastItem() }}</b> из <b>{{ $products->total() }}</b> товаров
      </div>
      <div class="w-full sm:w-auto">
        {{ $products->onEachSide(1)->links() }}
      </div>
    </div>
  @endif
</div>

{{-- Стили для line-clamp --}}
<style>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
.break-words {
  word-break: break-word;
}
.break-all {
  word-break: break-all;
}
</style>

@endsection