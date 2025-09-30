@extends('admin.layout')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Общая информация</h1>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <div class="text-3xl font-bold">{{ $stats['products'] }}</div>
            <div class="text-gray-500">Товаров</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <div class="text-3xl font-bold">{{ $stats['categories'] }}</div>
            <div class="text-gray-500">Категорий</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <div class="text-3xl font-bold">{{ $stats['orders'] }}</div>
            <div class="text-gray-500">Заказов</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <div class="text-3xl font-bold">{{ $stats['users'] }}</div>
            <div class="text-gray-500">Пользователей</div>
        </div>
    </div>

    <h2 class="text-xl font-semibold mb-4">Последние товары</h2>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Категория</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Продавец</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($products as $product)
                    <tr>
                        <td class="px-6 py-4 text-sm">{{ $product->id }}</td>
                        <td class="px-6 py-4 text-sm">{{ $product->title }}</td>
                        <td class="px-6 py-4 text-sm">{{ $product->category->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $product->seller->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Нет товаров</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
@endsection
