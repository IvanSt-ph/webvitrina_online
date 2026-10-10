{{-- resources/views/seller/cabinet.blade.php --}}
<x-seller-layout title="Панель продавца" :hideHeader="true">
  <div class="w-full space-y-5 px-3 py-4 pb-28 sm:space-y-6 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">

    @php
      $user = auth()->user();
      $shop = $user->shop;
      $rating = $user->reviews_avg_rating ?? 0;
    @endphp

    {{-- 🏪 ШАПКА МАГАЗИНА --}}
    <section id="banner-box"
             class="relative w-full overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-100">
      <div class="relative min-h-[210px] w-full sm:min-h-[240px] lg:min-h-[260px]">

        @php
          $bannerPath = $shop?->banner_url ?? asset('images/image-placeholder.svg');
        @endphp

        <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $bannerPath }}"
             alt="Баннер магазина"
             class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-in-out hover:scale-[1.02]">

        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-black/5"></div>

        {{-- 🔹 Инфо о магазине --}}
        <div class="absolute bottom-4 left-4 right-4 flex items-end gap-3 text-white sm:bottom-5 sm:left-5 sm:right-5 sm:gap-4">

          {{-- 🧑‍💼 Аватар продавца --}}
          <a href="{{ route('profile.edit') }}" class="block">
            <div class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white/20 text-base font-semibold ring-2 ring-white/40 backdrop-blur-sm sm:h-14 sm:w-14">
              @if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                  <img data-image-candidates="{{ json_encode($user->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}" src="{{ $user->avatar_url }}" alt="Аватар продавца" class="absolute inset-0 w-full h-full object-cover" loading="lazy" decoding="async">
              @else
                  <span class="text-white">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
              @endif
            </div>
          </a>

          <div class="min-w-0 flex-1">
            <div class="mb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/75">Панель продавца</div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="truncate text-xl font-semibold tracking-tight sm:text-2xl">
                {{ $shop->name ?? 'Ваш магазин' }}
              </h1>

              {{-- ⭐ Рейтинг --}}
              @if($rating > 0)
                <div class="flex items-center gap-1 ml-1">
                  @for ($i = 1; $i <= 5; $i++)
                    @if ($rating >= $i)
                      <i class="ri-star-fill text-amber-300 text-base sm:text-lg"></i>
                    @elseif ($rating >= $i - 0.5)
                      <i class="ri-star-half-fill text-amber-300 text-base sm:text-lg"></i>
                    @else
                      <i class="ri-star-line text-white/40 text-base sm:text-lg"></i>
                    @endif
                  @endfor
                  <span class="text-xs sm:text-sm font-semibold text-white ml-1">
                    {{ number_format($rating, 2) }}
                  </span>
                </div>
              @else
                <div class="flex items-center gap-1 opacity-70 text-white text-xs sm:text-sm ml-1">
                  <i class="ri-star-line text-white/40 text-base sm:text-lg"></i>
                  <span>Нет оценок</span>
                </div>
              @endif
            </div>

            <p class="mt-1 flex items-center gap-1.5 text-xs text-white/80 sm:text-sm"><i class="ri-map-pin-line"></i>{{ $shop->city ?? 'Город не указан' }}</p>
          </div>
        </div>

        @if($shop?->slug)
          <a href="{{ route('seller.show', ['identifier' => $shop->slug]) }}"
             class="absolute right-3 top-3 inline-flex h-10 items-center gap-2 rounded-xl border border-white/30 bg-neutral-950/65 px-3 text-xs font-semibold text-white shadow-lg backdrop-blur-md transition-colors hover:bg-neutral-950/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-900 sm:right-4 sm:top-4 sm:text-sm">
            <i class="ri-store-3-line text-white" aria-hidden="true"></i>
            <span>Посмотреть магазин</span>
          </a>
        @endif
      </div>
    </section>

    <section class="rounded-2xl border border-neutral-200 bg-white p-3 sm:p-4">
      <form method="GET" action="{{ route('seller.products.index') }}" class="grid gap-2 sm:grid-cols-[1fr_auto]">
        <label class="relative">
          <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"></i>
          <input name="q" type="search" placeholder="Найти товар в моём магазине" class="wv-field h-11 pl-10">
        </label>
        <button class="wv-btn-primary inline-flex h-11 items-center justify-center gap-2 px-5">
          <i class="ri-search-line"></i>
          Искать
        </button>
      </form>
    </section>

    @if(!empty($reputationProgress))
      <section class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
        <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-brand-100 bg-brand-50 text-brand-600">
                <i class="ri-shield-star-line text-lg"></i>
              </span>
              <div>
                <h2 class="text-lg font-semibold text-neutral-900">Уровень продавца</h2>
                <p class="mt-0.5 text-sm text-neutral-500">Продажи, рейтинг и подтверждения, которые видят покупатели.</p>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm">
            <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand-500">Сейчас</div>
            <div class="mt-1 font-bold text-brand-800">{{ $reputationProgress['current'] }}</div>
          </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          <div class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-3">
            <div class="text-xs font-medium text-neutral-500">Продажи</div>
            <div class="mt-1 text-xl font-bold text-neutral-900">{{ number_format($reputationProgress['sales'], 0, ',', ' ') }}</div>
          </div>
          <div class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-3">
            <div class="text-xs font-medium text-neutral-500">Рейтинг</div>
            <div class="mt-1 text-xl font-bold text-neutral-900">
              {{ $reputationProgress['rating'] > 0 ? number_format($reputationProgress['rating'], 2, ',', ' ') . ' / 5' : '—' }}
            </div>
          </div>
          <div class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-3">
            <div class="text-xs font-medium text-neutral-500">Следующий шаг</div>
            <div class="mt-1 text-sm font-bold text-neutral-900">
              @if($reputationProgress['is_top'])
                Максимальный уровень
              @else
                {{ $reputationProgress['next'] }}
              @endif
            </div>
          </div>
        </div>

        <div class="mt-4 rounded-xl border border-brand-100 bg-brand-50/70 p-3 text-sm text-brand-900">
          @if($reputationProgress['is_top'])
            <div class="flex items-center gap-2 font-semibold">
              <i class="ri-checkbox-circle-fill text-brand-600"></i>
              У вас максимальный публичный уровень продавца. Поддерживайте рейтинг и скорость обработки заказов.
            </div>
          @elseif(empty($reputationProgress['tasks']))
            <div class="flex items-center gap-2 font-semibold">
              <i class="ri-refresh-line text-brand-600"></i>
              Условия для следующего уровня уже выглядят выполненными. Уровень обновится после пересчёта репутации.
            </div>
          @else
            <div class="font-semibold">До уровня “{{ $reputationProgress['next'] }}” нужно:</div>
            <div class="mt-2 flex flex-wrap gap-2">
              @foreach($reputationProgress['tasks'] as $task)
                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-brand-700 ring-1 ring-brand-100">{{ $task }}</span>
              @endforeach
            </div>
          @endif
        </div>
      </section>
    @endif

    @if(!empty($actionCards))
      <section class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4 sm:p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-neutral-900">Требует внимания</h2>
            <p class="mt-1 text-sm text-neutral-500">Заказы, ответы, остатки и публикации на сегодня.</p>
          </div>
          @if($pendingPlanRequest)
            <a href="{{ route('seller.plans.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">
              <i class="ri-vip-crown-line"></i>
              Заявка на уровень магазина в обработке
            </a>
          @endif
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
          @foreach($actionCards as $card)
            @php
              $toneClass = match($card['tone']) {
                  'amber' => 'bg-amber-50 text-amber-700',
                  'rose' => 'bg-rose-50 text-rose-700',
                  'indigo' => 'bg-brand-50 text-brand-700',
                  'emerald' => 'bg-emerald-50 text-emerald-700',
                  default => 'bg-neutral-100 text-neutral-500',
              };
            @endphp
            <a href="{{ $card['href'] }}" class="group rounded-xl border border-white bg-white p-4 text-neutral-900 transition hover:border-brand-200 hover:shadow-md">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-neutral-700">{{ $card['label'] }}</p>
                  <div class="mt-2 text-3xl font-bold tracking-tight text-neutral-950">{{ number_format($card['value'], 0, ',', ' ') }}</div>
                  <p class="mt-1 text-xs text-neutral-500">{{ $card['text'] }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xl transition group-hover:scale-105 {{ $toneClass }}">
                  <i class="{{ $card['icon'] }}"></i>
                </div>
              </div>
            </a>
          @endforeach
        </div>

        @if($actionOrders->isNotEmpty())
          <div class="mt-4 overflow-hidden rounded-xl border border-white bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-4 py-3">
              <h3 class="font-semibold text-neutral-900">Заказы, где нужен ответ</h3>
              <a href="{{ route('seller.orders.index', ['action' => 'needs_action']) }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Все задачи</a>
            </div>
            <div class="divide-y divide-neutral-100">
              @foreach($actionOrders as $order)
                <a href="{{ route('seller.orders.show', $order) }}" class="grid gap-2 px-4 py-3 transition hover:bg-brand-50/40 sm:grid-cols-[1fr_auto] sm:items-center">
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="font-semibold text-neutral-900">#{{ $order->number }}</span>
                      @if($order->cancellation_requested_at)
                        <span class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">Запрос отмены</span>
                      @else
                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">Новый заказ</span>
                      @endif
                    </div>
                    <p class="mt-1 truncate text-sm text-neutral-500">{{ $order->buyer_name }} · {{ $order->items->first()?->historical_title ?? 'Название не сохранено' }}</p>
                  </div>
                  <div class="text-sm font-bold text-neutral-900">{{ $order->formatted_total_price }}</div>
                </a>
              @endforeach
            </div>
          </div>
        @endif
      </section>
    @endif

    @if(!empty($setupChecklist))
      @php
        $doneCount = collect($setupChecklist)->where('done', true)->count();
        $totalCount = count($setupChecklist);
      @endphp
      <section class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-neutral-900">Чеклист запуска магазина</h2>
            <p class="mt-1 text-sm text-neutral-500">Контакты, товары и уровень магазина — всё необходимое для старта.</p>
          </div>
          <span class="w-fit rounded-full bg-brand-50 px-3 py-1 text-sm font-bold text-brand-700 ring-1 ring-brand-100">{{ $doneCount }} / {{ $totalCount }}</span>
        </div>
        <div class="mt-4 grid gap-2 sm:grid-cols-2">
          @foreach($setupChecklist as $item)
            <a href="{{ $item['href'] }}" class="flex items-center justify-between gap-3 rounded-xl border px-3 py-2 transition {{ $item['done'] ? 'border-emerald-100 bg-emerald-50 text-emerald-800' : 'border-amber-100 bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
              <span class="flex min-w-0 items-center gap-2 text-sm font-semibold">
                <i class="{{ $item['done'] ? 'ri-checkbox-circle-line' : 'ri-arrow-right-circle-line' }}"></i>
                <span class="truncate">{{ $item['label'] }}</span>
              </span>
              <span class="text-xs font-bold">{{ $item['done'] ? 'Готово' : 'Сделать' }}</span>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    {{-- 📋 Основная информация --}}
    <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
      <div class="space-y-5 p-4 sm:p-5">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-neutral-900">Информация о магазине</h2>
            <p class="mt-1 text-xs text-neutral-500">Контакты, которые используются в работе с покупателями</p>
          </div>
          <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
            <i class="ri-check-line"></i> Активен
          </span>
        </div>

        <p class="border-b border-neutral-100 pb-4 text-sm leading-relaxed text-neutral-600">
          {{ $shop->description ?? 'Добавьте краткое описание вашей компании и ассортимента.' }}
        </p>

        <div class="grid grid-cols-1 gap-3 text-sm text-neutral-600 sm:grid-cols-3">
          <div class="rounded-xl bg-neutral-50 p-3">
            <p class="flex items-center gap-2 text-xs text-neutral-500">
              <i class="ri-phone-line text-brand-500"></i>
              Телефон
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
            <p class="mt-1 select-text break-words font-semibold text-neutral-900">
              {{ $formatted }}
            </p>
          </div>

          <div class="min-w-0 rounded-xl bg-neutral-50 p-3">
            <p class="flex items-center gap-2 text-xs text-neutral-500">
              <i class="ri-mail-line text-brand-500"></i>
              Email
            </p>
            <p class="mt-1 truncate font-semibold text-neutral-900">{{ $user->email }}</p>
          </div>

          <div class="rounded-xl bg-neutral-50 p-3">
            <p class="flex items-center gap-2 text-xs text-neutral-500">
              <i class="ri-map-pin-line text-brand-500"></i>
              Адрес
            </p>
            <p class="mt-1 font-semibold text-neutral-900">{{ $shop->city ?? 'Не указан' }}</p>
          </div>
        </div>

        <div class="flex justify-end border-t border-neutral-100 pt-4 text-sm">
          @if($shop?->updated_at)
            <span class="flex items-center gap-1 text-xs text-neutral-400">
              <i class="ri-time-line text-brand-300"></i>
              Обновлено: {{ $shop->updated_at->format('d.m.Y H:i') }}
            </span>
          @endif
        </div>
      </div>
    </section>

    {{-- 📈 График и новости --}}
    <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
      <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-1">
          <h2 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
              <i class="ri-line-chart-line"></i>
            </span>
            Заказы за 14 дней
          </h2>
          <p class="hidden items-center gap-1 text-xs text-neutral-400 sm:flex">
            <i class="ri-time-line text-brand-300"></i>
            {{ now()->format('d.m.Y H:i') }}
          </p>
        </div>
        <p class="mb-4 ml-11 text-xs text-neutral-400">Динамика за последние две недели</p>
        <canvas id="salesChart" height="100"></canvas>
      </div>

      {{-- Новости --}}
      <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
              <i class="ri-lightbulb-line"></i>
            </span>
            Новости и советы
          </h2>
          <a href="{{ route('seller.help.index') }}"
             class="{{ request()->routeIs('seller.help.*') ? 'text-brand-600 font-semibold' : 'text-neutral-500 hover:text-brand-600' }} flex items-center gap-1 text-sm font-semibold">
            Все статьи
            <i class="ri-arrow-right-s-line"></i>
          </a>
        </div>
        <div class="space-y-4">
          @foreach (array_slice(config('seller_news'), 0, 5) as $news)
            <div class="border-b border-neutral-100 pb-3 last:border-0">
              <a href="{{ $news['url'] }}" class="block text-sm font-medium text-neutral-800 transition hover:text-brand-600">
                {{ $news['title'] }}
              </a>
              <p class="mt-1 text-xs text-neutral-400">{{ $news['date'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    {{-- 📊 Статистика продавца --}}
    @if (!empty($stats))
      <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($stats as $item)
          @continue(str_contains($item['label'], 'Рейтинг'))
          <div class="rounded-2xl border border-neutral-200 bg-white p-4 transition hover:border-brand-200 hover:shadow-md sm:p-5">
            <p class="flex items-center gap-1 text-xs text-neutral-500 sm:text-sm">
              <i class="ri-information-line text-brand-300"></i>
              {{ $item['label'] }}
            </p>
            <h3 class="mt-2 text-xl font-bold sm:text-2xl {{ $item['color'] }}">{{ $item['value'] }}</h3>
            <p class="mt-1 flex items-center gap-1 text-[11px] text-neutral-400">
              <i class="ri-time-line text-brand-300"></i>
              {{ now()->format('d.m.Y') }}
            </p>
          </div>
        @endforeach
      </section>
    @endif

    {{-- ⚙️ Быстрые действия --}}
    <section class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
      <div class="mb-4">
        <h2 class="text-lg font-semibold text-neutral-900">Быстрые действия</h2>
        <p class="mt-1 text-xs text-neutral-500">Основные инструменты управления магазином</p>
      </div>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('seller.products.index') }}"
           class="group rounded-xl border border-neutral-200 bg-white p-4 transition hover:border-brand-200 hover:bg-brand-50/40">
          <div class="mb-3 flex items-center justify-between">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-box-3-line text-xl"></i></span>
            <i class="ri-arrow-right-up-line text-neutral-300 transition group-hover:text-brand-500"></i>
          </div>
          <h3 class="font-semibold text-neutral-900">Мои товары</h3>
          <p class="mt-1 text-xs text-neutral-500">Просмотр и управление товарами</p>
        </a>

        <a href="{{ route('seller.products.create') }}"
           class="group rounded-xl border border-neutral-200 bg-white p-4 transition hover:border-brand-200 hover:bg-brand-50/40">
          <div class="mb-3 flex items-center justify-between">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-add-circle-line text-xl"></i></span>
            <i class="ri-arrow-right-up-line text-neutral-300 transition group-hover:text-brand-500"></i>
          </div>
          <h3 class="font-semibold text-neutral-900">Добавить товар</h3>
          <p class="mt-1 text-xs text-neutral-500">Создать новую карточку товара</p>
        </a>

        <a href="{{ route('profile.edit') }}"
           class="group rounded-xl border border-neutral-200 bg-white p-4 transition hover:border-brand-200 hover:bg-brand-50/40">
          <div class="mb-3 flex items-center justify-between">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-building-4-line text-xl"></i></span>
            <i class="ri-arrow-right-up-line text-neutral-300 transition group-hover:text-brand-500"></i>
          </div>
          <h3 class="font-semibold text-neutral-900">Компания</h3>
          <p class="mt-1 text-xs text-neutral-500">Редактировать контакты и описание</p>
        </a>

        <a href="{{ route('support') }}"
           class="group rounded-xl border border-neutral-200 bg-white p-4 transition hover:border-brand-200 hover:bg-brand-50/40">
          <div class="mb-3 flex items-center justify-between">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-customer-service-2-line text-xl"></i></span>
            <i class="ri-arrow-right-up-line text-neutral-300 transition group-hover:text-brand-500"></i>
          </div>
          <h3 class="font-semibold text-neutral-900">Поддержка</h3>
          <p class="mt-1 text-xs text-neutral-500">Чат с администратором</p>
        </a>
      </div>
    </section>

  </div>

  {{-- 📊 Chart.js --}}
  <script>
document.addEventListener('DOMContentLoaded', () => {
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

});
</script>

  @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
