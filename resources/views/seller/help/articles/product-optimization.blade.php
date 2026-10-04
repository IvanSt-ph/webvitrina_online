<x-seller-layout :title="$news['title']">

  @php
    $slug = last(explode('/', $news['url']));
    $image = asset("images/help/{$slug}.webp");
  @endphp

  <section class="w-full space-y-6 bg-white px-3 py-4 pb-28 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">

    {{-- 🖼️ Обложка --}}
    <div class="relative mb-6 overflow-hidden rounded-2xl border border-neutral-200">
      <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $image }}" alt="{{ $news['title'] }}" class="h-56 w-full object-cover sm:h-80">
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent"></div>
      <div class="absolute inset-x-0 bottom-0 p-5 text-white sm:p-7">
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $news['title'] }}</h1>
        <p class="mt-1 text-sm text-neutral-200">{{ $news['date'] }}</p>
      </div>
    </div>

    {{-- 📄 Контент --}}
    <article class="space-y-8 rounded-2xl border border-neutral-200 bg-white p-5 leading-relaxed text-neutral-800 sm:p-7 lg:p-8">

      <p class="text-lg">
        Оптимизация карточки товара — это не просто оформление, а мощный инструмент продаж.  
        Чем понятнее, красивее и полезнее карточка, тем выше шанс, что товар попадёт в корзину.  
        В этой статье — практические советы, как сделать карточку идеальной.
      </p>

      {{-- Раздел 1 --}}
      <x-help-section icon="ri-pencil-line" title="1. Название и ключевые слова">
        <p>
          Заголовок — первое, что видят и покупатель, и алгоритм поиска.  
          Используйте релевантные ключевые слова, но без перегрузки.
        </p>
        <ul class="list-disc pl-6 space-y-2 text-gray-700 mt-3">
          <li>Не просто “Часы мужские” — а “часы мужские водонепроницаемые с кожаным ремешком”.</li>
          <li>Ключевые слова лучше размещать в начале заголовка.</li>
          <li>Избегайте слов “АКЦИЯ”, “СКИДКА”, “ЛУЧШИЕ” — они снижают доверие.</li>
        </ul>

        <x-help-tip color="indigo" icon="ri-lightbulb-flash-line">
          Используйте 2–3 ключевые фразы и не повторяйте одно и то же слово — алгоритм сочтёт это спамом.
        </x-help-tip>

        <img src="{{ asset('images/image-placeholder.svg') }}" alt="Пример хорошего заголовка" class="rounded-2xl shadow-md mt-6">
      </x-help-section>

      {{-- Раздел 2 --}}
      <x-help-section icon="ri-image-2-line" title="2. Фото и визуал">
        <p>
          Фото — это лицо вашего товара.  
          Качественные изображения могут повысить конверсию на 30–40%.
        </p>
        <ul class="list-disc pl-6 text-gray-700 space-y-2 mt-3">
          <li>Главное фото — на белом фоне или красиво оформленная карточка.</li>
          <li>Добавляйте 4–7 снимков: общий вид, детали, упаковка, товар в руках.</li>
          <li>Следите за светом: избегайте пересвета и жёстких теней.</li>
          <li>Для одежды и обуви используйте фото на модели.</li>
        </ul>

        <x-help-tip color="green" icon="ri-camera-line">
          Регулярно обновляйте фотографии — это помогает системе считать товар активным и актуальным.
        </x-help-tip>

        <img src="{{ asset('images/image-placeholder.svg') }}" alt="Пример фото карточки" class="rounded-2xl shadow-md mt-6">
      </x-help-section>

      {{-- Раздел 3 --}}
      <x-help-section icon="ri-file-list-line" title="3. Описание товара">
        <p>
          Хорошее описание помогает покупателю представить товар в использовании.  
          Не просто перечисляйте характеристики — покажите, какую пользу он даёт.
        </p>
        <ul class="list-disc pl-6 text-gray-700 space-y-2 mt-3">
          <li>Пишите простым, понятным языком.</li>
          <li>Добавляйте подзаголовки — “Материалы”, “Уход”, “Особенности”.</li>
          <li>Расскажите, какую проблему решает товар (“не скользит”, “удобен в дороге”).</li>
        </ul>

        <x-help-note color="yellow" icon="ri-quill-pen-line">
          Хорошее описание продаёт так же эффективно, как и красивая фотография.
        </x-help-note>

        <img src="{{ asset('images/image-placeholder.svg') }}" alt="Описание товара пример" class="rounded-2xl shadow-md mt-6">
      </x-help-section>

      {{-- Раздел 4 --}}
      <x-help-section icon="ri-equalizer-line" title="4. Характеристики товара">
        <p>
          Этот блок влияет на фильтры и видимость товара.  
          Если покупатель ищет “хлопок, красный, L” — ваш товар должен попасть в выборку.
        </p>
        <ul class="list-disc pl-6 space-y-2 text-gray-700 mt-3">
          <li>Заполняйте все поля: размер, материал, вес, цвет, страна.</li>
          <li>Избегайте “прочее” — такие значения система игнорирует.</li>
          <li>Проверяйте единицы измерения: “см”, “г”, “л”.</li>
        </ul>

        <x-help-tip color="yellow" icon="ri-list-check-2">
          Чем больше заполнено характеристик, тем чаще товар показывается в поиске и фильтрах.
        </x-help-tip>
      </x-help-section>

      {{-- Видео --}}
      <x-help-section icon="ri-play-circle-line" title="Видео-пример оформления">
        <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-4">
          <iframe class="w-full h-full" src="https://www.youtube.com/embed/DXUAyRRkI6k"
                  title="Пример оформления карточки товара"
                  frameborder="0" allowfullscreen></iframe>
        </div>
      </x-help-section>

      {{-- Раздел 5 --}}
      <x-help-section icon="ri-checkbox-circle-line" title="5. Чек-лист проверки карточки">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
          <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Заголовок содержит ключевые слова</div>
          <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Фото высокого качества</div>
          <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Все характеристики заполнены</div>
          <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Добавлено 4–7 изображений</div>
          <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Описание структурировано</div>
          <div class="flex items-center gap-2"><i class="ri-checkbox-circle-line text-green-500"></i> Цена актуальна</div>
        </div>
      </x-help-section>

    </article>

    {{-- 🔙 Навигация --}}
    <div class="flex items-center justify-between gap-3 border-t border-neutral-200 pt-5 text-sm text-neutral-500">
      <a href="{{ route('seller.help', ['slug' => 'boost-sales']) }}" class="flex items-center gap-1 hover:text-brand-600">
        <i class="ri-arrow-left-line"></i> Предыдущая статья
      </a>
      <a href="{{ route('seller.help', ['slug' => 'updates-2025']) }}" class="flex items-center gap-1 text-right hover:text-brand-600">
        Следующая статья <i class="ri-arrow-right-line"></i>
      </a>
    </div>

  </section>

</x-seller-layout>
