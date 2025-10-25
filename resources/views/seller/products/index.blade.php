<x-seller-layout title="Мои товары" :hideHeader="true">

  <div class="min-h-screen bg-white text-gray-800">



    <!-- 🌤 Основной контент -->
    <main class="max-w-7xl mx-auto px-6 py-10 space-y-10">

      <!-- Заголовок -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
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
      <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
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

      <!-- 🧭 Фильтры и поиск -->
      <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6">
        <div class="mb-5 space-y-3">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Все товары</h2>
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

        <!-- 🛍 Сетка товаров -->
        @if($products->count())
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-5">
            @foreach($products as $p)
              <div class="relative group bg-white border border-gray-200 rounded-xl hover:shadow-md transition overflow-hidden">

                <!-- Фото -->
                <div class="relative h-44 bg-gray-50 overflow-hidden">
                  @if($p->image)
                    <img src="{{ asset('storage/'.$p->image) }}"
                         alt="{{ $p->title }}"
                         class="object-cover w-full h-full group-hover:scale-105 transition duration-300">
                  @else
                    <div class="flex items-center justify-center h-full text-gray-400 text-xs">Без фото</div>
                  @endif

                  <span class="absolute top-2 left-2 text-xs px-2 py-0.5 rounded-md
                        {{ $p->stock > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $p->stock > 0 ? 'Активен' : 'Нет' }}
                  </span>
                </div>

                <!-- Контент -->
                <div class="p-3">
                  <h3 class="text-sm font-medium text-gray-800 line-clamp-1">{{ $p->title }}</h3>
                  <div class="mt-1 text-xs text-gray-500 flex justify-between">
                    <span>{{ $p->category->name ?? '—' }}</span>
                    <span>{{ $p->city->name ?? '' }}</span>
                  </div>
                  <div class="mt-2 text-sm font-semibold text-gray-800">
                    {{ number_format($p->price, 0, ',', ' ') }} ₽
                  </div>
                </div>

                <!-- Hover действия -->
                <div class="absolute inset-0 bg-white/85 opacity-0 group-hover:opacity-100 flex items-center justify-center gap-2 transition">
                  <a href="{{ route('seller.products.edit', $p) }}"
                     class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md hover:bg-gray-100">
                    Редактировать
                  </a>
                  <form method="POST" action="{{ route('seller.products.destroy', $p) }}">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-3 py-1.5 text-xs font-medium border border-red-300 text-red-600 rounded-md hover:bg-red-50">
                      Удалить
                    </button>
                  </form>
                </div>

                <div class="bg-gray-50 text-[11px] text-gray-500 px-3 py-1.5 flex justify-between border-t border-gray-100">
                  <span>{{ $p->created_at->format('d.m.Y') }}</span>
                  <span>👁 {{ rand(10, 250) }}</span>
                </div>
              </div>
            @endforeach
          </div>

          <div class="mt-10">{{ $products->links() }}</div>

        @else
          <div class="text-center text-gray-500 mt-20">
            <p class="text-lg mb-2">Нет товаров</p>
            <a href="{{ route('seller.products.create') }}" class="text-indigo-600 hover:underline text-sm">
              Добавить первый товар
            </a>
          </div>
        @endif
      </section>


    </main>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-app-l>
