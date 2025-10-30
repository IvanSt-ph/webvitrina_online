<x-seller-layout :title="$news['title']">

  @php
    // Определяем slug и картинку для обложки
    $slug = last(explode('/', $news['url']));
    $image = asset("images/help/{$slug}.webp");
  @endphp

  <section class="pt-2 pb-10 space-y-10 px-4 sm:px-6 lg:px-8">

    <!-- 🖼️ Обложка -->
    <div class="relative rounded-3xl overflow-hidden shadow-md border border-gray-100 mb-10">
      <img src="{{ $image }}" alt="Оптимизация карточки товара" class="w-full h-64 sm:h-96 object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
      <div class="absolute bottom-5 left-8 text-white">
        <h1 class="text-3xl sm:text-4xl font-bold drop-shadow-lg">{{ $news['title'] }}</h1>
        <p class="text-sm text-gray-200 mt-1">{{ $news['date'] }}</p>
      </div>
    </div>

    <!-- 📄 Контент статьи -->
    <article class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 leading-relaxed text-gray-800 space-y-8">

      <p class="text-lg">
        Оптимизация карточки товара — это не просто оформление, а инструмент продаж.  
        Чем понятнее, красивее и полезнее ваша карточка, тем выше шансы, что покупатель добавит товар в корзину.  
        В этой статье разберём, как сделать карточку идеальной.
      </p>

      <!-- 🔹 1. Название и ключевые слова -->
      <h2 class="text-2xl font-semibold text-indigo-600">🔹 1. Название и ключевые слова</h2>
      <p>
        Заголовок — первое, что видит покупатель и алгоритм поиска.  
        Используйте ключевые слова, по которым вас ищут.
      </p>

      <ul class="list-disc pl-6 space-y-2 text-gray-700">
        <li>Не пишите просто “Часы мужские” — добавьте уточнение: “часы мужские водонепроницаемые с кожаным ремешком”.</li>
        <li>Ключевые слова ставьте в начало, а не в конец заголовка.</li>
        <li>Избегайте “кричащих” слов типа “ЛУЧШИЕ”, “АКЦИЯ”, “СКИДКА” — они снижают доверие.</li>
      </ul>

      <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-800">
        💡 <strong>Совет:</strong> используйте 2–3 ключевые фразы и не повторяйте одно и то же слово — это выглядит спамно.
      </div>

      <img src="{{ asset('images/help/product-title-example.jpg') }}" alt="Пример хорошего заголовка" class="rounded-2xl shadow-md my-6">

      <!-- 🔹 2. Фото и визуал -->
      <h2 class="text-2xl font-semibold text-indigo-600">🔹 2. Фото и визуал</h2>
      <p>
        Фото — это лицо товара.  
        Качественные, чистые изображения без отвлекающего фона могут увеличить конверсию на 30–40%.
      </p>

      <ul class="list-disc pl-6 text-gray-700 space-y-2">
        <li>Используйте белый фон для главного фото.</li>
        <li>Добавляйте 4–7 изображений: общий вид, детали, упаковку, товар в руках.</li>
        <li>Проверяйте свет — переэкспозиция или тени портят впечатление.</li>
        <li>Для одежды и обуви — используйте фото на модели.</li>
      </ul>

      <img src="{{ asset('images/help/product-photos-example.jpg') }}" alt="Пример фото карточки" class="rounded-2xl shadow-md my-6">

      <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-sm text-green-800">
        💡 <strong>Совет:</strong> регулярно обновляйте фото — это помогает алгоритму считать товар “активным”.
      </div>

      <!-- 🔹 3. Описание -->
      <h2 class="text-2xl font-semibold text-indigo-600">🔹 3. Описание товара</h2>
      <p>
        Хорошее описание помогает покупателю представить товар в жизни.  
        Не просто перечисляйте характеристики — объясняйте пользу.
      </p>

      <ul class="list-disc pl-6 text-gray-700 space-y-2">
        <li>Пишите простыми словами, избегая канцеляризмов.</li>
        <li>Добавляйте подзаголовки — “Материалы”, “Уход”, “Особенности”.</li>
        <li>Если товар решает проблему — скажите об этом (“не скользит”, “помещается в сумку”).</li>
      </ul>

      <img src="{{ asset('images/help/product-description-example.jpg') }}" alt="Описание товара пример" class="rounded-2xl shadow-md my-6">

      <blockquote class="border-l-4 border-indigo-500 pl-4 text-gray-600 italic">
        “Хорошее описание продаёт так же, как и красивая фотография.”
      </blockquote>

      <!-- 🔹 4. Характеристики -->
      <h2 class="text-2xl font-semibold text-indigo-600">🔹 4. Характеристики товара</h2>
      <p>
        Это блок, который влияет на фильтры и сортировку. Если покупатель выбирает “красный, хлопок, L” —
        ваш товар должен попасть в выборку.
      </p>

      <ul class="list-disc pl-6 space-y-2 text-gray-700">
        <li>Заполните все характеристики, особенно размер, материал, вес, цвет.</li>
        <li>Избегайте "прочее" — алгоритм не сможет распознать ваш товар.</li>
        <li>Проверяйте единицы измерения — "см", "г", "л".</li>
      </ul>

      <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 text-sm text-yellow-800">
        💡 <strong>Совет:</strong> чем больше характеристик заполнено, тем чаще товар появляется в фильтрах.
      </div>

      <!-- 🎬 Видео-пример -->
      <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-6">
        <iframe class="w-full h-full" src="https://www.youtube.com/embed/DXUAyRRkI6k"
                title="Пример оформления карточки товара"
                frameborder="0" allowfullscreen></iframe>
      </div>

      <!-- 🔹 5. Проверка карточки -->
      <h2 class="text-2xl font-semibold text-indigo-600">🔹 5. Проверьте себя — чек-лист</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Заголовок содержит ключевые слова</div>
        <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Фото в хорошем качестве</div>
        <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Все характеристики заполнены</div>
        <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Добавлено 4–7 изображений</div>
        <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Описание структурировано</div>
        <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Цена актуальна</div>
      </div>

    </article>

    <!-- 🔙 Навигация -->
    <div class="mt-10 flex items-center justify-between text-sm text-gray-500">
      <a href="{{ route('seller.help', ['slug' => 'boost-sales']) }}" class="hover:text-indigo-600 flex items-center gap-1">
        <i class="ri-arrow-left-line"></i> Предыдущая статья
      </a>
      <a href="{{ route('seller.help', ['slug' => 'updates-2025']) }}" class="hover:text-indigo-600 flex items-center gap-1">
        Следующая статья <i class="ri-arrow-right-line"></i>
      </a>
    </div>

  </section>

</x-seller-layout>
