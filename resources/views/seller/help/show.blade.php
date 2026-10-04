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

  <section class="w-full space-y-6 bg-white px-3 py-4 pb-28 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">

    <!-- 🖼️ Обложка статьи -->
    <div class="relative mb-6 overflow-hidden rounded-2xl border border-neutral-200">
      <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $image }}" alt="Совет продавцу" class="h-56 w-full object-cover sm:h-80">
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
      <div class="absolute inset-x-0 bottom-0 p-5 text-white sm:p-7">
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $news['title'] }}</h1>
        <p class="mt-1 text-sm text-neutral-200">{{ $news['date'] }}</p>
      </div>
    </div>

    <!-- 📄 Основной контент статьи -->
    <article class="space-y-6 rounded-2xl border border-neutral-200 bg-white p-5 leading-relaxed text-neutral-800 sm:p-7 lg:p-8">

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
      <h2 class="text-xl font-semibold text-neutral-950">Как улучшить карточку товара</h2>
      <ul class="list-disc space-y-2 pl-6 text-neutral-700">
        <li>Используйте фотографии высокого качества и на белом фоне.</li>
        <li>Добавляйте 3–5 изображений товара с разных ракурсов.</li>
        <li>Указывайте реальные характеристики — без “воды”.</li>
        <li>Добавляйте ключевые слова в заголовок и описание.</li>
      </ul>

      {{-- Вставка цитаты --}}
      <blockquote class="rounded-r-xl border-l-4 border-brand-500 bg-brand-50 px-4 py-3 text-neutral-600 italic">
        “Хорошо оформленная карточка увеличивает вероятность покупки на 30–50%.”
      </blockquote>

      {{-- Раздел: работа с отзывами --}}
      <h2 class="text-xl font-semibold text-neutral-950">Работа с отзывами</h2>
      <p>
        Отвечайте на отзывы оперативно и вежливо. Даже если отзыв негативный — покажите, что вы готовы помочь.
        Это повышает доверие покупателей и рейтинг магазина.
      </p>

      {{-- Вставка блока-совета --}}
      <div class="rounded-xl border border-brand-100 bg-brand-50 p-4 text-sm text-brand-800">
        💡 <strong>Совет:</strong> если покупатель доволен заказом, предложите ему оставить отзыв — 
        это поднимет ваш товар в поиске WebVitrina.
      </div>

      {{-- Раздел: чек-лист --}}
      <h2 class="text-xl font-semibold text-neutral-950">Чек-лист для проверки карточки</h2>
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
    <div class="flex items-center justify-between gap-3 border-t border-neutral-200 pt-5 text-sm">
      {{-- Кнопка: назад к панели продавца --}}
      <a href="{{ route('seller.cabinet') }}"
         class="flex items-center gap-1 text-neutral-500 hover:text-brand-600">
        <i class="ri-arrow-left-line"></i> Назад к панели продавца
      </a>

      {{-- Кнопка: следующая статья (пример) --}}
      <a href="{{ route('seller.help', ['slug' => 'product-optimization']) }}"
         class="flex items-center gap-1 text-right text-neutral-500 hover:text-brand-600">
        Следующая статья <i class="ri-arrow-right-line"></i>
      </a>
    </div>

  </section>

</x-seller-layout>
