{{-- Шаблон отдельной статьи из раздела "Помощь продавцу" --}}
<x-seller-layout :title="$news['title']">

  @php
    // Получаем "slug" статьи (последнюю часть URL из config)
    // Пример: /seller/help/boost-sales → boost-sales
    $slug = last(explode('/', $news['url']));

    // Проверяем, есть ли уникальная обложка для этой статьи
    // Если нет — используем стандартный баннер
    $imagePath = "images/help/{$slug}.webp";
    $image = file_exists(public_path($imagePath))
        ? asset($imagePath)
        : asset('images/help/help-banner.webp');
  @endphp

  <section class="pt-2 pb-10 space-y-10 px-4 sm:px-6 lg:px-8">

    <!-- 🖼️ Обложка статьи -->
    <div class="relative rounded-3xl overflow-hidden shadow-md border border-gray-100 mb-8">
      <img src="{{ $image }}" alt="Совет продавцу" class="w-full h-60 sm:h-80 object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
      <div class="absolute bottom-4 left-6 text-white">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ $news['title'] }}</h1>
        <p class="text-sm text-gray-200">{{ $news['date'] }}</p>
      </div>
    </div>

    <!-- 📄 Основной контент статьи -->
    <article class="bg-gradient-to-br from-white to-indigo-50/30 rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 leading-relaxed text-gray-800 space-y-6">

      {{-- Вступительный абзац --}}
      <p>
        <strong>WebVitrina</strong> помогает продавцам улучшать карточки товаров, анализировать спрос
        и получать больше продаж. В этой статье расскажем, как использовать возможности площадки эффективно.
      </p>

      <!-- 🎬 Вставка видео с YouTube -->
      <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-6">
        <iframe class="w-full h-full"
                src="https://www.youtube.com/embed/DXUAyRRkI6k"
                title="Советы продавцам WebVitrina"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
      </div>

      {{-- Раздел: советы по карточкам товаров --}}
      <h2 class="text-xl font-semibold text-indigo-600">Как улучшить карточку товара</h2>
      <ul class="list-disc pl-6 space-y-2 text-gray-700">
        <li>Используйте фотографии высокого качества и на белом фоне.</li>
        <li>Добавляйте 3–5 изображений товара с разных ракурсов.</li>
        <li>Указывайте реальные характеристики — без “воды”.</li>
        <li>Добавляйте ключевые слова в заголовок и описание.</li>
      </ul>

      {{-- Вставка цитаты --}}
      <blockquote class="border-l-4 border-indigo-500 pl-4 text-gray-600 italic">
        “Хорошо оформленная карточка увеличивает вероятность покупки на 30–50%.”
      </blockquote>

      {{-- Раздел: работа с отзывами --}}
      <h2 class="text-xl font-semibold text-indigo-600">Работа с отзывами</h2>
      <p>
        Отвечайте на отзывы оперативно и вежливо. Даже если отзыв негативный — покажите, что вы готовы помочь.
        Это повышает доверие покупателей и рейтинг магазина.
      </p>

      {{-- Вставка блока-совета --}}
      <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-800">
        💡 <strong>Совет:</strong> если покупатель доволен заказом, предложите ему оставить отзыв — 
        это поднимет ваш товар в поиске WebVitrina.
      </div>

      {{-- Раздел: чек-лист --}}
      <h2 class="text-xl font-semibold text-indigo-600">Чек-лист для проверки карточки</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div class="flex items-center gap-2">
          <i class="ri-checkbox-circle-line text-green-500"></i>
          Заголовок содержит ключевые слова
        </div>
        <div class="flex items-center gap-2">
          <i class="ri-checkbox-circle-line text-green-500"></i>
          Фото сделаны на нейтральном фоне
        </div>
        <div class="flex items-center gap-2">
          <i class="ri-checkbox-circle-line text-green-500"></i>
          Добавлено 3+ изображений
        </div>
        <div class="flex items-center gap-2">
          <i class="ri-checkbox-circle-line text-green-500"></i>
          Указаны все характеристики
        </div>
      </div>

    </article>

    <!-- 🔙 Навигация между статьями -->
    <div class="mt-8 flex items-center justify-between text-sm">
      {{-- Кнопка: назад к панели продавца --}}
      <a href="{{ route('seller.cabinet') }}"
         class="text-gray-500 hover:text-indigo-600 flex items-center gap-1">
        <i class="ri-arrow-left-line"></i> Назад к панели продавца
      </a>

      {{-- Кнопка: следующая статья (пример) --}}
      <a href="{{ route('seller.help', ['slug' => 'product-optimization']) }}"
         class="text-gray-500 hover:text-indigo-600 flex items-center gap-1">
        Следующая статья <i class="ri-arrow-right-line"></i>
      </a>
    </div>

  </section>

</x-seller-layout>
