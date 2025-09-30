@extends('admin.layout')

@section('title', 'Товары')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">📦 Товары</h1>

        <a href="{{ route('admin.products.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow hover:bg-indigo-700 transition">
            ➕ <span>Добавить товар</span>
        </a>
    </div>

    <!-- Таблица товаров -->
    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full w-full table-fixed border-collapse">
            <thead class="bg-gray-100 sticky top-0 z-10">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Изображение</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Название</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Категория</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Цена</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Остаток</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Страна</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Город</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Продавец</th>
                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Действия</th>
            </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr class="border-t hover:bg-gray-50 odd:bg-gray-50/30">
                    <td class="px-4 py-3">{{ $product->id }}</td>
                    <td class="px-4 py-3">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="w-12 h-12 object-cover rounded border">
                        @else
                            <span class="text-gray-400 italic">нет</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-medium">{{ $product->title }}</td>
                    <td class="px-4 py-3">{{ $product->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ number_format($product->price, 2, '.', ' ') }} ₽</td>
                    <td class="px-4 py-3">{{ $product->stock }}</td>
                    <td class="px-4 py-3">{{ $product->country?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $product->city?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $product->seller?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right flex items-center gap-2 justify-end">
                        <!-- Редактировать -->
                        <a href="{{ route('admin.products.edit', $product) }}"
                           class="inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition"
                           title="Редактировать">
                            ✏️
                        </a>

                        <!-- Удалить -->
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                              onsubmit="return confirm('Удалить товар {{ $product->title }}?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 rounded hover:bg-red-100 transition"
                                    title="Удалить">
                                🗑
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-6 text-center text-gray-500">
                        Товаров пока нет.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Пагинация -->
    <div class="mt-4">
        {{ $products->links() }}
    </div>
@endsection
