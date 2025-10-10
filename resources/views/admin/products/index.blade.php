@extends('admin.layout')

@section('title', 'Товары')

@section('content')
{{-- ===== Заголовок и поиск ===== --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
    📦 <span>Товары</span>
  </h1>


{{-- 🔍 Улучшенный Live-поиск товаров --}}
<div x-data="{
      query: '',
      results: [],
      loading: false,
      async search() {
        if (this.query.length < 2) {
          this.results = [];
          return;
        }
        this.loading = true;
        try {
          const res = await fetch(`{{ route('admin.products.search') }}?q=${encodeURIComponent(this.query)}`);
          this.results = await res.json();
        } catch (e) {
          console.error(e);
        }
        this.loading = false;
      },
      clear() { this.query=''; this.results=[]; },
    }"
    class="relative w-full sm:w-96">
  
  {{-- Поле ввода --}}
  <div class="relative">
    <input type="text"
           x-model="query"
           @input.debounce.400ms="search"
           placeholder="Поиск товаров..."
           class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition">
    <span class="absolute left-2 top-2.5 text-gray-400 text-lg">🔍</span>

    {{-- Индикатор загрузки --}}
    <div x-show="loading" class="absolute right-3 top-2.5 text-gray-400 animate-spin">⏳</div>
  </div>

  {{-- Выпадающий список --}}
  <div x-show="results.length"
       @click.outside="results=[]"
       class="absolute z-40 w-full mt-2 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden max-h-96 overflow-y-auto transition-all duration-150">
    
    <template x-for="p in results" :key="p.id">
      <div class="flex items-center gap-3 px-4 py-3 border-b last:border-none hover:bg-gray-50 cursor-pointer">
        {{-- Фото --}}
        <img :src="p.image ? '/storage/' + p.image : '/images/no-image.png'"
             class="w-12 h-12 rounded-lg border object-cover">

        {{-- Информация --}}
        <div class="flex-1 min-w-0">
          <div class="font-medium text-gray-900 text-sm truncate" x-text="p.title"></div>
          <div class="text-xs text-gray-500" x-text="p.price + ' ₽'"></div>
        </div>

        {{-- Кнопка редактирования --}}
        <a :href="`/admin/products/${p.id}/edit`"
           class="px-2.5 py-1 text-xs rounded bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
          ✏️
        </a>
      </div>
    </template>

    {{-- Кнопка "Очистить" --}}
    <button @click="clear"
            class="w-full text-center py-2 text-sm text-gray-500 hover:bg-gray-100 transition">
      Очистить результаты
    </button>
  </div>
</div>


  {{-- ➕ Кнопка добавления --}}
  <a href="{{ route('admin.products.create') }}"
     class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow hover:bg-indigo-700 transition w-full sm:w-auto text-center">
    ➕ <span>Добавить товар</span>
  </a>
</div>

{{-- ======= Десктопный режим ======= --}}
<div class="hidden md:block bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
  <table class="min-w-full text-sm text-gray-800">
    <thead class="bg-gray-100 text-xs uppercase text-gray-600">
      <tr>
        <th class="px-3 py-3 text-left font-semibold">ID</th>
        <th class="px-3 py-3 text-left font-semibold">Изображение</th>
        <th class="px-3 py-3 text-left font-semibold">Название</th>
        <th class="px-3 py-3 text-left font-semibold">Категория</th>
        <th class="px-3 py-3 text-left font-semibold">Цена</th>
        <th class="px-3 py-3 text-left font-semibold">Остаток</th>
        <th class="px-3 py-3 text-left font-semibold">Страна</th>
        <th class="px-3 py-3 text-left font-semibold">Город</th>
        <th class="px-3 py-3 text-left font-semibold">Продавец</th>
        <th class="px-3 py-3 text-right font-semibold">Действия</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse($products as $product)
      <tr class="hover:bg-gray-50 transition">
        <td class="px-3 py-3">{{ $product->id }}</td>
        <td class="px-3 py-3">
          @if($product->image)
            <img src="{{ asset('storage/'.$product->image) }}" class="w-12 h-12 object-cover rounded border">
          @else
            <span class="text-gray-400 italic">нет</span>
          @endif
        </td>
        <td class="px-3 py-3 font-medium">{{ $product->title }}</td>
        <td class="px-3 py-3">{{ $product->category?->name ?? '—' }}</td>
        <td class="px-3 py-3">{{ number_format($product->price, 2, '.', ' ') }} ₽</td>
        <td class="px-3 py-3">{{ $product->stock }}</td>
        <td class="px-3 py-3">{{ $product->country?->name ?? '—' }}</td>
        <td class="px-3 py-3">{{ $product->city?->name ?? '—' }}</td>
        <td class="px-3 py-3">{{ $product->seller?->name ?? '—' }}</td>
        <td class="px-3 py-3 text-right">
          <div class="flex items-center justify-end gap-2">
            <a href="{{ route('admin.products.edit', $product) }}"
               class="inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition"
               title="Редактировать">✏️</a>
            <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                  onsubmit="return confirm('Удалить товар {{ $product->title }}?')">
              @csrf
              @method('DELETE')
              <button type="submit"
                      class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 rounded hover:bg-red-100 transition"
                      title="Удалить">🗑</button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="10" class="px-4 py-6 text-center text-gray-500">Товаров пока нет.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>

{{-- ======= Мобильный режим (карточки в 2–3 колонки) ======= --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:hidden">
  @forelse($products as $product)
  <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition">
    <div class="flex items-center gap-4">
      <div class="flex-shrink-0">
        @if($product->image)
          <img src="{{ asset('storage/'.$product->image) }}" class="w-16 h-16 object-cover rounded border">
        @else
          <div class="w-16 h-16 bg-gray-100 flex items-center justify-center rounded text-gray-400 text-sm italic">нет</div>
        @endif
      </div>
      <div class="flex-1 min-w-0">
        <div class="font-medium text-gray-900 truncate">{{ $product->title }}</div>
        <div class="text-xs text-gray-500">{{ $product->category?->name ?? 'Без категории' }}</div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-x-4 gap-y-1 mt-3 text-xs text-gray-600">
      <div><span class="font-semibold">Цена:</span> {{ number_format($product->price, 2, '.', ' ') }} ₽</div>
      <div><span class="font-semibold">Остаток:</span> {{ $product->stock }}</div>
      <div><span class="font-semibold">Страна:</span> {{ $product->country?->name ?? '—' }}</div>
      <div><span class="font-semibold">Город:</span> {{ $product->city?->name ?? '—' }}</div>
      <div class="col-span-2"><span class="font-semibold">Продавец:</span> {{ $product->seller?->name ?? '—' }}</div>
    </div>

    <div class="flex justify-end gap-2 mt-3">
      <a href="{{ route('admin.products.edit', $product) }}"
         class="px-3 py-1 text-blue-600 bg-blue-50 rounded hover:bg-blue-100 text-sm transition">✏️</a>
      <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
            onsubmit="return confirm('Удалить товар {{ $product->title }}?')">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="px-3 py-1 text-red-600 bg-red-50 rounded hover:bg-red-100 text-sm transition">🗑</button>
      </form>
    </div>
  </div>
  @empty
  <div class="text-center text-gray-500 text-sm py-10 col-span-full">Товаров пока нет.</div>
  @endforelse
</div>

{{-- ======= Пагинация и итоги ======= --}}
@if ($products->count())
<div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-600">
  <div>
    Показано <b>{{ $products->firstItem() }}</b>–<b>{{ $products->lastItem() }}</b> из <b>{{ $products->total() }}</b> товаров
  </div>
  <div>
    {{ $products->links() }}
  </div>
</div>
@endif
@endsection
