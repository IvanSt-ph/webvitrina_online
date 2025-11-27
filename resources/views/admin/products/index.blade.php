
@extends('admin.layout')

@section('title', 'Товары')

@section('content')

<div 
  x-data="{
    // === Поиск ===
    query: '',
    results: [],
    loading: false,

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
  }"
  class="space-y-6">
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
           rounded-2xl p-4 flex items-center gap-4 mb-6"
>
    <div class="flex items-center justify-center w-10 h-10 rounded-xl
                bg-emerald-100 border border-emerald-300 shadow-inner">
        <i class="ri-check-line text-2xl text-emerald-600"></i>
    </div>

    <div class="flex-1">
        <div class="text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
        <div class="text-xs text-emerald-600/80 mt-0.5">
            Операция выполнена успешно
        </div>
    </div>

    <button @click="show = false"
            class="text-emerald-600 hover:text-emerald-800 transition">
        <i class="ri-close-line text-xl"></i>
    </button>
</div>
@endif

  {{-- ===== Заголовок, поиск и кнопки ===== --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div class="flex items-center gap-3">
      <div class="bg-indigo-100 text-indigo-600 p-2 rounded-lg">
        <i class="ri-box-3-line text-xl"></i>
      </div>
      <div>
        <h1 class="text-2xl font-semibold text-gray-800">Список товаров</h1>
        <p class="text-sm text-gray-500">Всего товаров: {{ $products->total() }}</p>
      </div>
    </div>

    {{-- 🔍 Live-поиск --}}
    <div class="relative w-full sm:w-96 order-last sm:order-none">
      <div class="relative group">
        <input type="text"
               x-model="query"
               @input.debounce.400ms="search"
               placeholder="Поиск по названию или артикулу..."
               class="w-full pl-10 pr-9 py-2.5 text-sm border border-gray-300 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition">
        <i class="ri-search-line absolute left-3 top-2.5 text-gray-400 group-focus-within:text-indigo-500"></i>
        <div x-show="loading" class="absolute right-3 top-2.5 text-gray-400 animate-spin">⏳</div>
      </div>

      {{-- Результаты поиска --}}
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
              <div class="font-medium text-gray-900 text-sm truncate"
                   x-html="highlight(p.title)"></div>
              <div class="text-xs text-gray-500" x-text="p.price + ' ₽'"></div>
              <div class="text-[11px] text-gray-400 font-mono"
                   x-html="'SKU: ' + highlight(p.sku ?? '')"></div>
            </div>
            <i class="ri-edit-2-line text-indigo-500 text-lg"></i>
          </a>
        </template>

        <button @click="clear"
                class="w-full text-center py-2 text-sm text-gray-500 hover:bg-gray-100 transition">
          Очистить результаты
        </button>
      </div>
    </div>


    <form action="{{ route('admin.products.purge-old') }}" method="POST" class="inline-block">
    @csrf
    <button
        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition"
        onclick="return confirm('Удалить товары, удалённые более 90 дней назад?')"
    >
        🧹 Очистить старые товары
    </button>
</form>


    {{-- ➕ Добавить --}}
    <a href="{{ route('admin.products.create') }}"
       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl shadow hover:bg-indigo-700 hover:shadow-md transition">
      <i class="ri-add-line text-lg"></i> Добавить товар
    </a>
  </div>

  {{-- 🔘 Переключатели столбцов --}}
  <div class="flex flex-wrap items-center gap-2 text-xs">
    <template x-for="(active, key) in columns" :key="key">
      <button @click="columns[key] = !columns[key]"
              class="px-3 py-1.5 font-medium rounded-lg border transition"
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

  {{-- ===== Таблица ===== --}}
  <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-x-auto">
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
      <td x-show="columns.id" class="px-4 py-3 text-gray-500">{{ $product->id }}</td>

      <td x-show="columns.image" class="px-4 py-3">
        <img src="{{ $product->image ? asset('storage/'.$product->image) : '/images/no-image.png' }}"
             class="w-14 h-14 object-cover rounded-lg border border-gray-200 shadow-sm">
      </td>

      <td x-show="columns.title" class="px-4 py-3 font-medium text-gray-900 max-w-[200px] truncate" title="{{ $product->title }}">
        {{ $product->title }}
      </td>

      <td x-show="columns.sku" class="px-4 py-3 font-mono text-xs text-gray-700 text-center">
        {{ $product->sku ?? '—' }}
      </td>

      <td x-show="columns.category" class="px-4 py-3 text-gray-700">
        {{ $product->category?->name ?? '—' }}
      </td>

      <td x-show="columns.price" class="px-4 py-3 font-semibold text-gray-900">
        {{ number_format($product->price, 2, '.', ' ') }} ₽
      </td>

      <td x-show="columns.stock" class="px-4 py-3">
        <span class="@if($product->stock <= 3) text-red-600 font-semibold
                     @elseif($product->stock < 10) text-yellow-600 font-medium
                     @else text-green-600 font-semibold @endif">
          {{ $product->stock }}
        </span>
      </td>

      <td x-show="columns.location" class="px-4 py-3 text-gray-600">
        {{ $product->city?->name ?? '—' }},
        <span class="text-gray-400">{{ $product->country?->name ?? $product->city?->country?->name ?? '—' }}</span>
      </td>

      {{-- 🧍 Продавец --}}
      <td x-show="columns.seller" class="px-4 py-3 text-gray-700">
       {{ $product->seller?->name ?? '—' }}
      </td>

      <td x-show="columns.actions" class="px-4 py-3 text-right">
        <div class="flex items-center justify-end gap-2">
          <a href="{{ route('admin.products.edit', $product) }}"
             class="p-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition"
             title="Редактировать">
            <i class="ri-edit-2-line"></i>
          </a>
          <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                onsubmit="return confirm('Удалить товар {{ $product->title }}?')">
            @csrf @method('DELETE')
            <button type="submit"
                    class="p-2 bg-red-50 text-red-600 rounded hover:bg-red-100 transition"
                    title="Удалить">
              <i class="ri-delete-bin-line"></i>
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
      <div>
        Показано <b>{{ $products->firstItem() }}</b>–<b>{{ $products->lastItem() }}</b> из <b>{{ $products->total() }}</b> товаров
      </div>
      <div>{{ $products->links() }}</div>
    </div>
  @endif
</div>

@endsection
