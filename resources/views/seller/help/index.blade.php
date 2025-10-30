<x-seller-layout title="Центр помощи продавца">

  <section class="pt-4 pb-16 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">

    <!-- Заголовок -->
    <div class="mb-10 text-center">
      <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3">Центр помощи продавца</h1>
      <p class="text-gray-500 text-sm sm:text-base">
        Практические советы, обновления и инструкции для эффективной работы на <strong>WebVitrina</strong>.
      </p>
    </div>

    <!-- Сетка статей -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach(config('seller_news') as $news)
        @php
          $slug = last(explode('/', $news['url']));
          $imagePath = "images/help/{$slug}.webp";
          $image = file_exists(public_path($imagePath))
              ? asset($imagePath)
              : asset('images/help-banner.webp');
        @endphp

        <div class="group relative bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden flex flex-col">
          
          <!-- Изображение -->
          <div class="relative h-40 overflow-hidden">
            <img src="{{ $image }}" alt="{{ $news['title'] }}" 
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent opacity-70 group-hover:opacity-90 transition-opacity"></div>
            <div class="absolute bottom-2 left-3 text-white text-xs">
              {{ $news['date'] }}
            </div>
          </div>

          <!-- Контент карточки -->
          <div class="p-5 flex flex-col flex-grow">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 mb-2 group-hover:text-indigo-600 transition">
              {{ $news['title'] }}
            </h2>
            <p class="text-sm text-gray-500 flex-grow">
              @switch($slug)
                @case('boost-sales')
                  Как повысить продажи и увеличить количество заказов без дополнительных затрат.
                  @break
                @case('product-optimization')
                  Узнайте, как сделать карточки товаров привлекательнее и повысить конверсию.
                  @break
                @case('updates-2025')
                  Последние обновления и новые инструменты, упрощающие работу продавцов.
                  @break
                @case('reviews-and-rating')
                  Как управлять отзывами и рейтингом, чтобы завоевать доверие покупателей.
                  @break
                @default
                  Советы и рекомендации для продавцов WebVitrina.
              @endswitch
            </p>

            <!-- Кнопка -->
            <a href="{{ $news['url'] }}"
               class="mt-4 inline-flex items-center justify-center text-sm font-medium text-indigo-600 hover:text-indigo-700">
              Читать статью <i class="ri-arrow-right-line ml-1 text-base"></i>
            </a>
          </div>
        </div>
      @endforeach
    </div>

  </section>

</x-seller-layout>
