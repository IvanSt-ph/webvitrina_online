{{-- resources/views/seller/cabinet.blade.php --}}
<x-seller-layout title="Панель продавца" :hideHeader="true">
  <div class="pt-2 pb-10 space-y-10 pl-4 pr-6">

    @php
      $user = auth()->user();
      $shop = $user->shop;
      $rating = $user->reviews_avg_rating ?? 0;
    @endphp

    {{-- 🏪 БАННЕР МАГАЗИНА --}}
    <section id="banner-box"
             class="relative w-full rounded-2xl overflow-hidden mb-6
                    border border-indigo-100/50 shadow-md bg-gradient-to-br from-indigo-50/80 via-purple-50/80 to-pink-50/80">
      <div class="relative w-full pt-[33%] sm:pt-[21%]">
        
        @php
          if ($shop?->banner) {
              if (\Illuminate\Support\Str::startsWith($shop->banner, ['http://', 'https://'])) {
                  $bannerPath = $shop->banner;
              } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($shop->banner)) {
                  $bannerPath = asset('storage/'.$shop->banner);
              } else {
                  $bannerPath = asset('images/default-shop-banner.jpg');
              }
          } else {
              $bannerPath = asset('images/default-shop-banner.jpg');
          }
        @endphp

        <img src="{{ $bannerPath }}"
             alt="Баннер магазина"
             class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-in-out hover:scale-105">

        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/20 to-transparent"></div>

        {{-- 🔹 Инфо о магазине --}}
        <div class="absolute bottom-3 left-4 sm:left-6 text-white drop-shadow-lg flex items-center gap-3">

          {{-- 🧑‍💼 Аватар продавца --}}
          <a href="{{ route('profile.edit') }}" class="block">
            <div class="flex-shrink-0 relative w-10 h-10 sm:w-12 sm:h-12 rounded-full overflow-hidden bg-white/20 backdrop-blur-sm flex items-center justify-center text-base font-semibold aspect-square ring-2 ring-white/30">
              @if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                  <img src="{{ asset('storage/' . $user->avatar) }}" alt="Аватар продавца" class="absolute inset-0 w-full h-full object-cover">
              @else
                  <span class="text-white">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
              @endif
            </div>
          </a>

          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-lg sm:text-2xl font-semibold tracking-wide">
                {{ $shop->name ?? 'Ваш магазин' }}
              </h3>

              {{-- ⭐ Рейтинг --}}
              @if($rating > 0)
                <div class="flex items-center gap-1 ml-1">
                  @for ($i = 1; $i <= 5; $i++)
                    @if ($rating >= $i)
                      <i class="ri-star-fill text-yellow-400 text-lg sm:text-2xl"></i>
                    @elseif ($rating >= $i - 0.5)
                      <i class="ri-star-half-fill text-yellow-400 text-lg sm:text-2xl"></i>
                    @else
                      <i class="ri-star-line text-white/40 text-lg sm:text-2xl"></i>
                    @endif
                  @endfor
                  <span class="text-xs sm:text-sm font-semibold text-white ml-1">
                    {{ number_format($rating, 2) }}
                  </span>
                </div>
              @else
                <div class="flex items-center gap-1 opacity-70 text-white text-xs sm:text-sm ml-1">
                  <i class="ri-star-line text-white/40 text-lg sm:text-xl"></i>
                  <span>Нет оценок</span>
                </div>
              @endif
            </div>

            <p class="text-xs sm:text-sm opacity-90">{{ $shop->city ?? 'Город не указан' }}</p>
          </div>
        </div>

        {{-- ✏️ Кнопка "Редактировать" --}}
        <a href="{{ route('profile.edit') }}"
           class="absolute top-3 right-3 bg-white/80 hover:bg-white px-2 sm:px-3 py-2 rounded-lg text-sm text-gray-700 font-medium shadow-sm border border-gray-200/50 flex items-center gap-1 backdrop-blur-sm transition-all hover:shadow-md hover:-translate-y-0.5">
          <i class="ri-edit-2-line text-indigo-500 text-base"></i>
          <span class="hidden sm:inline">Редактировать</span>
        </a>
      </div>
    </section>

    {{-- 📋 Основная информация --}}
    <section class="bg-white rounded-2xl border border-gray-100/80 shadow-sm overflow-hidden">
      <div class="p-6 space-y-5">
        <p class="text-sm text-gray-600 leading-relaxed border-b border-gray-100/80 pb-4">
          {{ $shop->description ?? 'Добавьте краткое описание вашей компании и ассортимента.' }}
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-gray-600">
          <div>
            <p class="text-gray-500 flex items-center gap-1">
              <i class="ri-phone-line text-indigo-400 text-sm"></i>
              <strong class="text-gray-800">Телефон:</strong>
            </p>
            @php
              $rawPhone = preg_replace('/\D+/', '', $shop->phone ?? '');
              $formatted = null;
              if (str_starts_with($shop->phone ?? '', '+373')) {
                  $digits = substr($rawPhone, 3);
                  $formatted = '(+373) ' . substr($digits, 0, 2) . ' ' . substr($digits, 2, 3) . '-' . substr($digits, 5);
              } elseif (str_starts_with($shop->phone ?? '', '+380')) {
                  $digits = substr($rawPhone, 3);
                  $formatted = '(+380) ' . substr($digits, 0, 2) . ' ' . substr($digits, 2, 3) . '-' . substr($digits, 5, 2) . '-' . substr($digits, 7);
              } elseif (str_starts_with($shop->phone ?? '', '+7')) {
                  $digits = substr($rawPhone, 1);
                  $formatted = '(+7) ' . substr($digits, 1, 3) . ' ' . substr($digits, 4, 3) . '-' . substr($digits, 7, 2) . '-' . substr($digits, 9);
              } else {
                  $formatted = '(+373) 77 698-989';
              }
            @endphp
            <p class="text-gray-700 font-normal text-[13px] tracking-wider leading-relaxed select-text">
              {{ $formatted }}
            </p>
          </div>

          <div>
            <p class="text-gray-500 flex items-center gap-1">
              <i class="ri-mail-line text-indigo-400 text-sm"></i>
              <strong class="text-gray-800">Email:</strong>
            </p>
            <p>{{ $user->email }}</p>
          </div>

          <div>
            <p class="text-gray-500 flex items-center gap-1">
              <i class="ri-map-pin-line text-indigo-400 text-sm"></i>
              <strong class="text-gray-800">Адрес:</strong>
            </p>
            <p>{{ $shop->city ?? 'Не указан' }}</p>
          </div>
        </div>

        <div class="flex justify-between items-center pt-4 border-t border-gray-100/80 text-sm">
          <span class="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 font-medium flex items-center gap-1 border border-emerald-200/50">
            <i class="ri-check-line text-emerald-500"></i> Активен
          </span>
          @if($shop?->updated_at)
            <span class="text-gray-400 text-xs flex items-center gap-1">
              <i class="ri-time-line text-indigo-300"></i>
              Обновлено: {{ $shop->updated_at->format('d.m.Y H:i') }}
            </span>
          @endif
        </div>
      </div>
    </section>

    {{-- 📈 График и новости --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white border border-gray-100/80 rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-1">
          <h2 class="text-lg font-semibold flex items-center gap-2">
            <div class="w-5 h-5 rounded bg-indigo-100 flex items-center justify-center">
              <i class="ri-line-chart-line text-indigo-600 text-xs"></i>
            </div>
            Заказы за 14 дней
          </h2>
          <p class="text-xs text-gray-400 flex items-center gap-1">
            <i class="ri-time-line text-indigo-300"></i>
            {{ now()->format('d.m.Y H:i') }}
          </p>
        </div>
        <p class="text-xs text-gray-400 mb-4"><i>(График показан для примера)</i></p>
        <canvas id="salesChart" height="100"></canvas>
      </div>

      {{-- Новости --}}
      <div class="bg-white border border-gray-100/80 rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold flex items-center gap-2">
            <div class="w-5 h-5 rounded bg-indigo-100 flex items-center justify-center">
              <i class="ri-question-line text-indigo-600 text-xs"></i>
            </div>
            Новости и советы
          </h2>
          <a href="{{ route('seller.help.index') }}"
             class="{{ request()->routeIs('seller.help.*') ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' }} flex items-center gap-2 text-sm">
            Все статьи
            <i class="ri-arrow-right-s-line"></i>
          </a>
        </div>
        <div class="space-y-4">
          @foreach (array_slice(config('seller_news'), 0, 5) as $news)
            <div class="border-b border-gray-100/80 pb-3 last:border-0">
              <a href="{{ $news['url'] }}" class="block text-sm font-medium text-gray-800 hover:text-indigo-600 transition">
                {{ $news['title'] }}
              </a>
              <p class="text-xs text-gray-400 mt-0.5">{{ $news['date'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    {{-- 📊 Статистика продавца --}}
    @if (!empty($stats))
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-6">
        @foreach ($stats as $item)
          @continue(str_contains($item['label'], 'Рейтинг'))
          <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-gray-100/80 p-6 hover:bg-white hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <p class="text-sm text-gray-500 flex items-center gap-1">
              <i class="ri-information-line text-indigo-300 text-xs"></i>
              {{ $item['label'] }}
            </p>
            <h3 class="text-2xl font-semibold mt-2 {{ $item['color'] }}">{{ $item['value'] }}</h3>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
              <i class="ri-time-line text-indigo-300"></i>
              {{ now()->format('d.m.Y') }}
            </p>
          </div>
        @endforeach
      </section>
    @endif

    {{-- ⚙️ Быстрые действия --}}
    <section>
      <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
        <div class="w-5 h-5 rounded bg-indigo-100 flex items-center justify-center">
          <i class="ri-flashlight-line text-indigo-600 text-xs"></i>
        </div>
        Быстрые действия
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="{{ route('seller.products.index') }}"
           class="group bg-white border border-gray-100/80 hover:border-indigo-300 rounded-xl p-6 shadow-sm hover:shadow transition-all hover:-translate-y-0.5">
          <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-indigo-600">Мои товары</h3>
            <i class="ri-box-3-line text-gray-400 group-hover:text-indigo-600 text-xl"></i>
          </div>
          <p class="text-sm text-gray-500">Просмотр и управление всеми товарами</p>
        </a>

        <a href="{{ route('seller.products.create') }}"
           class="group bg-white border border-gray-100/80 hover:border-emerald-300 rounded-xl p-6 shadow-sm hover:shadow transition-all hover:-translate-y-0.5">
          <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-emerald-600">Добавить товар</h3>
            <i class="ri-add-circle-line text-gray-400 group-hover:text-emerald-600 text-xl"></i>
          </div>
          <p class="text-sm text-gray-500">Создайте новую карточку товара</p>
        </a>

        <a href="{{ route('profile.edit') }}"
           class="group bg-white border border-gray-100/80 hover:border-amber-300 rounded-xl p-6 shadow-sm hover:shadow transition-all hover:-translate-y-0.5">
          <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-amber-600">Информация о компании</h3>
            <i class="ri-building-4-line text-gray-400 group-hover:text-amber-600 text-xl"></i>
          </div>
          <p class="text-sm text-gray-500">Редактировать контакты и описание</p>
        </a>
      </div>
    </section>

  </div>

  {{-- 📊 Chart.js --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const ctx = document.getElementById('salesChart');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['1','2','3','4','5','6','7','8','9','10','11','12','13','14'],
        datasets: [{
          label: 'Заказано, ₽',
          data: [210000,280000,310000,250000,290000,330000,305000,270000,260000,280000,295000,310000,290000,300000],
          borderColor: '#4F46E5',
          backgroundColor: 'rgba(79,70,229,0.08)',
          fill: true,
          tension: 0.35,
          pointRadius: 0
        }]
      },
      options: {
        plugins: { legend: { display:false } },
        scales: {
          x: { grid:{ display:false }, ticks:{ color:'#9CA3AF' } },
          y: { grid:{ color:'#F3F4F6' }, ticks:{ color:'#9CA3AF' } }
        }
      }
    });
  </script>

  @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>