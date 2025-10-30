{{-- resources/views/seller/cabinet.blade.php --}}
<x-seller-layout title="Панель продавца" :hideHeader="true">
  <div class="pt-2 pb-10 space-y-10 pl-4 pr-6">

    {{-- 📈 Блок: график заказов и новости --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white border border-gray-100 rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-1">
          <h2 class="text-lg font-semibold">Заказы за 14 дней</h2>
          <p class="text-xs text-gray-400">Обновлено {{ now()->format('d.m.Y H:i') }}</p>
        </div>
        <p class="text-xs text-gray-400 mb-4"><i>(График показан для примера)</i></p>
        <canvas id="salesChart" height="100"></canvas>
      </div>

      {{-- Новости и советы --}}
      <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Новости и советы</h2>
          <a href="{{ route('seller.help.index') }}"
             class="{{ request()->routeIs('seller.help.*') ? 'text-indigo-600 font-semibold' : 'text-gray-600 hover:text-indigo-600' }} flex items-center gap-2">
            <i class="ri-question-line text-lg"></i> Все статьи
          </a>
        </div>
        <div class="space-y-4">
          @foreach (array_slice(config('seller_news'), 0, 5) as $news)
            <div class="border-b border-gray-100 pb-3">
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
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
      @foreach ($stats as $item)
        <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 hover:shadow transition">
          <p class="text-sm text-gray-500">{{ $item['label'] }}</p>

          {{-- ⭐ Блок рейтинга --}}
          @if (str_contains($item['label'], 'Рейтинг'))
            @php
              $rating = (float) ($item['raw'] ?? preg_replace('/[^\d.]/', '', $item['value']) ?? 0);
            @endphp

            @if ($rating <= 0)
              <div class="flex items-center gap-1 mt-3 text-gray-300">
                @for ($i = 1; $i <= 5; $i++)
                  <i class="ri-star-line text-gray-300 text-xl"></i>
                @endfor
                <span class="text-sm text-gray-400 ml-1 font-medium">(нет оценок)</span>
              </div>
            @else
              <div class="flex items-center gap-1 mt-3 group">
                @for ($i = 1; $i <= 5; $i++)
                  @if ($rating >= $i)
                    <i class="ri-star-fill text-yellow-400 text-xl star-anim"></i>
                  @elseif ($rating >= $i - 0.5)
                    <i class="ri-star-half-fill text-yellow-400 text-xl star-anim"></i>
                  @else
                    <i class="ri-star-line text-gray-300 text-xl star-anim"></i>
                  @endif
                @endfor
                <span class="text-sm text-gray-600 ml-1 font-medium">({{ number_format($rating, 2) }})</span>
              </div>
            @endif

          @else
            {{-- Обычные карточки --}}
            <h3 class="text-2xl font-semibold mt-2 {{ $item['color'] }}">{{ $item['value'] }}</h3>
          @endif

          <p class="text-xs text-gray-400 mt-1">Обновлено {{ now()->format('d.m.Y') }}</p>
        </div>
      @endforeach
    </section>
    @endif

    {{-- ⭐ Стили для анимации звёзд --}}
    <style>
      .star-anim {
        transition: transform 0.3s ease, text-shadow 0.3s ease;
      }
      .star-anim:hover {
        transform: scale(1.2);
        text-shadow: 0 0 8px rgba(250, 204, 21, 0.6);
      }
      .group:hover .star-anim {
        transform: scale(1.15);
        text-shadow: 0 0 6px rgba(250, 204, 21, 0.5);
      }
    </style>

    {{-- ⚙️ Быстрые действия --}}
    <section>
      <h2 class="text-lg font-semibold mb-4">Быстрые действия</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="{{ route('seller.products.index') }}"
           class="group bg-white border border-gray-100 hover:border-indigo-400 rounded-xl p-6 shadow-sm hover:shadow transition">
          <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-indigo-600">Мои товары</h3>
            <i class="ri-box-3-line text-gray-400 group-hover:text-indigo-600 text-xl"></i>
          </div>
          <p class="text-sm text-gray-500">Просмотр и управление всеми товарами</p>
        </a>

        <a href="{{ route('seller.products.create') }}"
           class="group bg-white border border-gray-100 hover:border-green-400 rounded-xl p-6 shadow-sm hover:shadow transition">
          <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-green-600">Добавить товар</h3>
            <i class="ri-add-circle-line text-gray-400 group-hover:text-green-600 text-xl"></i>
          </div>
          <p class="text-sm text-gray-500">Создайте новую карточку товара</p>
        </a>

        <a href="{{ route('profile.edit') }}"
           class="group bg-white border border-gray-100 hover:border-yellow-400 rounded-xl p-6 shadow-sm hover:shadow transition">
          <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-yellow-600">Информация о компании</h3>
            <i class="ri-building-4-line text-gray-400 group-hover:text-yellow-600 text-xl"></i>
          </div>
          <p class="text-sm text-gray-500">Редактировать контакты и описание</p>
        </a>
      </div>
    </section>

    {{-- 🧾 Блок профиля --}}
    <section>
      <h2 class="text-lg font-semibold mb-4">Ваш профиль</h2>
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">

        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xl font-semibold">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
          </div>
          <div>
            <h3 class="text-xl font-semibold text-gray-800">{{ auth()->user()->shop_name ?? 'Название компании' }}</h3>
            <p class="text-sm text-gray-500">
              {{ auth()->user()->city ?? 'Город не указан' }}, {{ auth()->user()->country ?? 'Страна' }}
            </p>
          </div>
          <span class="ml-auto px-3 py-1 text-xs font-medium rounded bg-green-100 text-green-700">Активен</span>
        </div>

        <p class="text-sm text-gray-600 leading-relaxed border-t pt-4">
          {{ auth()->user()->shop_description ?? 'Добавьте краткое описание вашей компании и ассортимента.' }}
        </p>

        <div class="text-sm text-gray-500 border-t pt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div><strong class="text-gray-700">Телефон:</strong> {{ auth()->user()->phone ?? '+373 XX XXX XXX' }}</div>
          <div><strong class="text-gray-700">Email:</strong> {{ auth()->user()->email }}</div>
          <div><strong class="text-gray-700">Адрес:</strong> {{ auth()->user()->address ?? 'Не указан' }}</div>
        </div>
      </div>
    </section>

  </div>

  {{-- 📊 Chart.js (демо-график) --}}
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

  {{-- 📱 Мобильная нижняя навигация --}}
  @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
