@extends('admin.layout')

@section('title', 'Товары')

@section('content')

{{-- ===== Заголовок и кнопки ===== --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
    <i class="ri-box-3-line text-indigo-600 text-2xl"></i>
    <span>Товары</span>
  </h1>

  {{-- ➕ Добавить --}}
  <a href="{{ route('admin.products.create') }}"
     class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow hover:bg-indigo-700 transition">
    <i class="ri-add-line text-lg"></i> Добавить товар
  </a>
</div>

{{-- ===== Панель фильтров / колонок ===== --}}
<div x-data="{
      columns: {
        id: true,
        image: true,
        title: true,
        category: true,
        price: true,
        stock: true,
        location: true,
        seller: true,
        actions: true,
      }
    }"
    class="space-y-4">

  {{-- 🔘 Переключатели столбцов --}}
  <div class="flex flex-wrap items-center gap-2 mb-3">
    <template x-for="(active, key) in columns" :key="key">
      <button @click="columns[key] = !columns[key]"
              class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-all duration-200"
              :class="columns[key]
                ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm hover:bg-indigo-700'
                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
        <i class="ri-eye-line mr-1 text-sm"></i>
        <span x-text="{
          id: 'ID',
          image: 'Фото',
          title: 'Название',
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
  <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm text-gray-800">
      <thead class="bg-gray-50 text-xs uppercase text-gray-600 border-b">
        <tr>
          <th x-show="columns.id" class="px-3 py-3 text-left font-semibold">ID</th>
          <th x-show="columns.image" class="px-3 py-3 text-left font-semibold">Изображение</th>
          <th x-show="columns.title" class="px-3 py-3 text-left font-semibold">Название</th>
          <th x-show="columns.category" class="px-3 py-3 text-left font-semibold">Категория</th>
          <th x-show="columns.price" class="px-3 py-3 text-left font-semibold">Цена</th>
          <th x-show="columns.stock" class="px-3 py-3 text-left font-semibold">Остаток</th>
          <th x-show="columns.location" class="px-3 py-3 text-left font-semibold">Местоположение</th>
          <th x-show="columns.seller" class="px-3 py-3 text-left font-semibold">Продавец</th>
          <th x-show="columns.actions" class="px-3 py-3 text-right font-semibold">Действия</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100">
        @forelse($products as $product)
          <tr class="hover:bg-indigo-50/40 transition duration-150">
            <td x-show="columns.id" class="px-3 py-3 text-gray-500">{{ $product->id }}</td>

            <td x-show="columns.image" class="px-3 py-3">
              <img src="{{ $product->image ? asset('storage/'.$product->image) : '/images/no-image.png' }}"
                   class="w-12 h-12 object-cover rounded border">
            </td>

            <td x-show="columns.title" class="px-3 py-3 font-medium text-gray-900 truncate max-w-[200px]" title="{{ $product->title }}">
              {{ $product->title }}
            </td>

            <td x-show="columns.category" class="px-3 py-3">
              {{ $product->category?->name ?? '—' }}
            </td>

            <td x-show="columns.price" class="px-3 py-3 font-semibold">
              {{ number_format($product->price, 2, '.', ' ') }} ₽
            </td>

            <td x-show="columns.stock" class="px-3 py-3">
              <span class="@if($product->stock <= 3) text-red-600 font-semibold
                           @elseif($product->stock < 10) text-yellow-600 font-medium
                           @else text-green-600 font-semibold @endif">
                {{ $product->stock }}
              </span>
            </td>

            <td x-show="columns.location" class="px-3 py-3 text-gray-600">
              {{ $product->city?->name ?? '—' }},
              <span class="text-gray-400">{{ $product->country?->name ?? $product->city?->country?->name ?? '—' }}</span>
            </td>

            <td x-show="columns.seller" class="px-3 py-3 text-gray-700">
              {{ $product->seller?->name ?? '—' }}
            </td>

            <td x-show="columns.actions" class="px-3 py-3 text-right">
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
            <td colspan="9" class="px-4 py-6 text-center text-gray-500">Товаров пока нет.</td>
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
