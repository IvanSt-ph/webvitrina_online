<x-buyer-layout title="Избранное">

  @php
      $unavailableItems = $unavailableItems ?? collect();
      $addedId = (int) session('cart_added_id');
      $currencySymbol = \App\Models\Product::currencySymbol(session('currency', 'PRB'));
      $availableFavoriteIds = $items->pluck('id')->map(fn ($id) => (string) $id)->values();
  @endphp

  <div
    x-data="{
      selectMode: false,
      selected: [],
      allIds: @js($availableFavoriteIds),
      toggleSelect(id) {
        id = String(id);
        this.selected = this.selected.includes(id)
          ? this.selected.filter(item => item !== id)
          : [...this.selected, id];
      },
      selectAll() {
        this.selected = this.selected.length === this.allIds.length ? [] : [...this.allIds];
      },
      closeSelection() {
        this.selectMode = false;
        this.selected = [];
      }
    }"
    class="favorites-mobile-safe min-h-screen w-full space-y-5 overflow-x-hidden bg-white px-4 py-5 pb-24 text-neutral-800 sm:space-y-6 sm:px-6 sm:py-7 sm:pb-24 lg:px-8 lg:pb-8">

    <header class="flex flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0">
        <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
          <i class="ri-heart-3-line"></i>
          Избранное
        </span>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Сохранённые товары</h1>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">Возвращайтесь к понравившимся товарам и добавляйте нужное в корзину.</p>
      </div>

      <div class="grid w-full grid-cols-2 gap-2 sm:w-auto">
        @if($items->isNotEmpty())
          <x-secondary-action type="button" @click="selectMode ? closeSelection() : selectMode = true">
            <span x-show="!selectMode" class="inline-flex items-center gap-2">
              <i class="ri-checkbox-multiple-line"></i>
              Выбрать
            </span>
            <span x-show="selectMode" class="inline-flex items-center gap-2">
              <i class="ri-close-line"></i>
              Отменить
            </span>
          </x-secondary-action>

          <x-secondary-action as="a" href="{{ route('cart.index') }}">
            <div class="relative">
              <i class="ri-shopping-cart-line text-sm sm:text-base" data-cart-icon></i>
              <span data-cart-count
                    class="absolute -top-1.5 -right-2 bg-amber-500 text-white text-[9px] font-bold min-w-[16px] h-4 px-1 rounded-full hidden items-center justify-center">
              </span>
            </div>
            <span>Корзина</span>
          </x-secondary-action>
        @else
          <x-action-button as="a" :href="route('home')" class="col-span-2 sm:w-44">
            <i class="ri-store-3-line"></i>
            Найти товары
          </x-action-button>
        @endif
      </div>
    </header>

    @if($items->isNotEmpty())
      <section x-show="selectMode" x-cloak class="rounded-2xl border border-brand-100 bg-brand-50/70 p-3 shadow-sm shadow-brand-100/50 sm:p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-semibold text-brand-950">
              <span x-text="selected.length"></span> выбрано
            </p>
            <p class="mt-1 text-xs leading-5 text-brand-700">
              Добавим выбранные товары в корзину. В избранном они останутся, чтобы вы могли вернуться к ним позже.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
            <button type="button" @click="selectAll" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-brand-200 bg-white px-4 text-sm font-semibold text-brand-700 transition hover:bg-brand-100">
              <i class="ri-checkbox-circle-line"></i>
              <span x-text="selected.length === allIds.length ? 'Снять всё' : 'Выбрать всё'"></span>
            </button>

            <form method="POST" action="{{ route('cart.addFavorites') }}" class="js-add-all-to-cart-form min-w-0" @submit="if (selected.length === 0) { $event.preventDefault(); window.showAppToast ? window.showAppToast('Выберите хотя бы один товар', 'error') : alert('Выберите хотя бы один товар'); }">
              @csrf
              <template x-for="id in selected" :key="id">
                <input type="hidden" name="favorite_ids[]" :value="id">
              </template>
              <x-action-button :full="true" x-bind:disabled="selected.length === 0">
                <i class="ri-shopping-cart-2-line"></i>
                В корзину
              </x-action-button>
            </form>
          </div>
        </div>
      </section>
    @endif

    @if($unavailableItems->isNotEmpty())
      <section class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/70">
        <div class="border-b border-amber-100 px-4 py-3 sm:px-5">
          <h2 class="font-semibold text-amber-900">Больше недоступны</h2>
          <p class="mt-1 text-sm text-amber-700">Эти товары сохранены в избранном, но сейчас их нельзя купить.</p>
        </div>
        <div class="divide-y divide-amber-100">
          @foreach($unavailableItems as $favorite)
            <div class="flex min-w-0 items-center gap-3 px-4 py-3 sm:px-5">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-amber-500">
                <i class="ri-heart-line text-xl"></i>
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-800">{{ $favorite->product?->title ?? 'Товар больше недоступен' }}</p>
                <p class="text-xs text-amber-700">Товар снят с продажи или удалён продавцом</p>
              </div>
              <form method="POST" action="{{ route('favorites.remove', $favorite) }}">
                @csrf
                @method('DELETE')
                <button class="rounded-xl border border-amber-200 bg-white px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100">Удалить</button>
              </form>
            </div>
          @endforeach
        </div>
      </section>
    @endif

    @if($items->isEmpty())

      <x-empty-state
        icon="ri-heart-3-line"
         title="{{ $unavailableItems->isNotEmpty() ? 'Нет доступных товаров' : 'Здесь пока пусто' }}"
         description="{{ $unavailableItems->isNotEmpty() ? 'Недоступные позиции сохранены выше, пока вы сами их не удалите.' : 'Сохраняйте понравившиеся товары, чтобы быстро вернуться к ним.' }}"
      >
        <x-action-button as="a" :href="route('home')">
          <i class="ri-arrow-left-s-line"></i>
          <span>В каталог</span>
        </x-action-button>
      </x-empty-state>

    @else

      {{-- Favorites list --}}
      <div class="min-w-0 space-y-2 sm:space-y-3">
        @foreach($items as $f)
          @php
            $p = $f->product;
          @endphp
          @continue(! $p)
          @php
            $shortProductTitle = Str::limit($p->title, 18);
            $currentPrice = $p->price_for_current_currency;
            $price = $currentPrice['amount'] ?? $p->price;
            $oldPriceData = $p->old_price_for_current_currency;
            $oldPrice = $oldPriceData['amount'] ?? null;
            $discountPercent = $p->discount_percent;
            $itemCurrencySymbol = $currentPrice['symbol'] ?? $currencySymbol;
          @endphp

          <div class="fav-card group relative min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white transition-all duration-200 hover:border-brand-200 hover:shadow-lg hover:shadow-brand-100/40"
               :class="selectMode && selected.includes('{{ $f->id }}') ? 'border-brand-300 bg-brand-50/60 shadow-md' : ''"
               data-fav-card data-id="{{ $p->id }}" data-favorite-id="{{ $f->id }}">

            {{-- Мобильная версия --}}
            <div class="block sm:hidden">
              <div class="relative p-3">
                <button type="button"
                        x-show="selectMode"
                        x-cloak
                        @click="toggleSelect('{{ $f->id }}')"
                        class="absolute left-2 top-2 z-10 flex h-8 w-8 items-center justify-center rounded-xl border bg-white shadow-md transition"
                        :class="selected.includes('{{ $f->id }}') ? 'border-brand-500 bg-brand-500 text-white' : 'border-neutral-200 text-neutral-400'">
                  <i :class="selected.includes('{{ $f->id }}') ? 'ri-check-line' : 'ri-checkbox-blank-line'"></i>
                </button>

                <div class="grid min-w-0 grid-cols-[76px_minmax(0,1fr)] gap-3">
                  <a href="{{ route('product.show', $p) }}"
                     class="relative h-[76px] w-[76px] flex-shrink-0 overflow-hidden rounded-xl border border-neutral-100 bg-neutral-50">
                    @if($p->image)
                      <img data-image-candidates="{{ json_encode($p->image_thumb_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $p->image_thumb_url }}"
                           class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                           alt="{{ $p->title }}">
                    @else
                      <div class="flex h-full w-full items-center justify-center text-xl text-neutral-300">
                        <i class="ri-image-line"></i>
                      </div>
                    @endif

                    @if($addedId === (int) $p->id)
                      <span class="absolute bottom-1 left-1 rounded-full bg-emerald-500 px-1.5 py-0.5 text-[9px] font-semibold text-white">В корзине</span>
                    @endif
                    @if($discountPercent)
                      <span class="absolute right-1 top-1 rounded-full bg-rose-500 px-1.5 py-0.5 text-[9px] font-bold text-white">-{{ $discountPercent }}%</span>
                    @endif
                  </a>

                  <div class="min-w-0">
                    <a href="{{ route('product.show', $p) }}"
                       class="line-clamp-2 break-words text-base font-medium leading-snug text-neutral-900 transition hover:text-brand-600"
                       style="overflow-wrap: anywhere;">
                      {{ $shortProductTitle }}
                    </a>
                    <div class="mt-2 flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
                      @if($oldPrice && $oldPrice > $price)
                        <span class="text-xs text-neutral-400 line-through">
                          {{ number_format($oldPrice, 0, ',', ' ') }} {{ $itemCurrencySymbol }}
                        </span>
                      @endif
                      <span class="text-lg font-bold text-neutral-900">
                        {{ number_format($price, 0, ',', ' ') }} <span class="text-xs font-normal text-neutral-500">{{ $itemCurrencySymbol }}</span>
                      </span>
                      <span class="text-xs text-neutral-400">за шт.</span>
                    </div>
                  </div>
                </div>

                <div class="mt-3 grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_40px] items-center gap-2 border-t border-neutral-100 pt-3">
                  <form method="POST" action="{{ route('cart.add', $p->id) }}" class="js-add-to-cart-form min-w-0">
                    @csrf
                    <x-action-button size="sm" :full="true">
                      <i class="ri-shopping-cart-line text-sm"></i>
                      <span>В корзину</span>
                    </x-action-button>
                  </form>

                  <form method="POST" action="{{ route('checkout.quick', $p->id) }}" class="min-w-0">
                    @csrf
                    <button class="flex h-10 w-full items-center justify-center gap-1.5 rounded-xl border border-neutral-200 bg-white px-2 text-xs font-semibold text-neutral-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                      <i class="ri-flashlight-line text-sm"></i>
                      <span>Купить</span>
                    </button>
                  </form>

                  <form method="POST" action="{{ route('favorites.toggle', $p) }}" class="js-fav-remove-form">
                    @csrf
                    <button type="submit"
                      class="flex h-10 w-10 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-500 transition hover:bg-rose-100">
                      <i class="ri-delete-bin-6-line text-sm"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>

            {{-- Десктопная версия: строка --}}
            <div class="hidden items-center gap-4 p-4 sm:flex lg:p-5">
              <button type="button"
                      x-show="selectMode"
                      x-cloak
                      @click="toggleSelect('{{ $f->id }}')"
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border bg-white shadow-sm transition"
                      :class="selected.includes('{{ $f->id }}') ? 'border-brand-500 bg-brand-500 text-white' : 'border-neutral-200 text-neutral-400'">
                <i :class="selected.includes('{{ $f->id }}') ? 'ri-check-line' : 'ri-checkbox-blank-line'"></i>
              </button>
              
              {{-- Product image --}}
              <a href="{{ route('product.show', $p) }}"
                 class="relative h-20 w-20 flex-shrink-0 overflow-hidden rounded-xl border border-neutral-100 bg-neutral-50 lg:h-24 lg:w-24">
                @if($p->image)
                  <img data-image-candidates="{{ json_encode($p->image_thumb_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $p->image_thumb_url }}"
                       class="w-full h-full object-cover transition-transform duration-400 group-hover:scale-105"
                       alt="{{ $p->title }}">
                @else
                  <div class="w-full h-full flex items-center justify-center text-2xl text-gray-300">
                    <i class="ri-image-line"></i>
                  </div>
                @endif

                @if($addedId === (int) $p->id)
                  <div class="absolute bottom-1 left-1 bg-emerald-500 text-white text-[8px] font-medium px-1.5 py-0.5 rounded-full">
                    ✓
                  </div>
                @endif

                @if($discountPercent)
                  <div class="absolute -top-1 -right-1 bg-rose-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full shadow-sm">
                    -{{ $discountPercent }}%
                  </div>
                @endif
              </a>

              {{-- Product info --}}
              <div class="flex-1 min-w-0">
                <a href="{{ route('product.show', $p) }}"
                   class="line-clamp-2 break-words text-base font-medium leading-snug text-neutral-900 transition hover:text-brand-600 lg:text-lg"
                   style="overflow-wrap: anywhere;">
                  {{ $p->title }}
                </a>
                
                @if($p->short_description)
                  <p class="mt-1 line-clamp-1 text-xs text-neutral-500">
                    {{ Str::limit($p->short_description, 60) }}
                  </p>
                @endif

                <div class="mt-1">
                  @if($oldPrice && $oldPrice > $price)
                    <span class="text-xs text-gray-400 line-through mr-1.5">
                      {{ number_format($oldPrice, 0, ',', ' ') }} {{ $itemCurrencySymbol }}
                    </span>
                  @endif
                  <span class="text-xl font-bold text-neutral-900">
                    {{ number_format($price, 0, ',', ' ') }}
                  </span>
                  <span class="text-xs text-gray-400">{{ $itemCurrencySymbol }}</span>
                  <span class="ml-1 text-xs text-gray-400">за шт.</span>
                </div>
              </div>

              {{-- Actions --}}
              <div class="flex flex-shrink-0 items-center gap-2">
                <form method="POST" action="{{ route('cart.add', $p->id) }}" class="js-add-to-cart-form">
                  @csrf
                  <x-action-button size="sm">
                    <i class="ri-shopping-cart-line text-sm"></i>
                    <span>В корзину</span>
                  </x-action-button>
                </form>

                <form method="POST" action="{{ route('checkout.quick', $p->id) }}">
                  @csrf
                  <button class="flex h-10 items-center justify-center gap-1.5 rounded-xl border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                    <i class="ri-flashlight-line text-sm"></i>
                    <span>Купить</span>
                  </button>
                </form>

                <form method="POST" action="{{ route('favorites.toggle', $p) }}" class="js-fav-remove-form">
                  @csrf
                  <button type="submit"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-500 transition hover:bg-rose-100">
                    <i class="ri-delete-bin-6-line text-base"></i>
                  </button>
                </form>
              </div>

            </div>
          </div>

        @endforeach
      </div>

    @endif
  </div>

  <script>
document.addEventListener('DOMContentLoaded', () => {

    function showToast(text, type = 'success') {
      if (window.showAppToast) {
        window.showAppToast(text, type);
        return;
      }

      const existing = document.querySelector('.toast');
      if (existing) existing.remove();
      
      const el = document.createElement('div');
      el.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success');
      el.innerHTML = `
        <div class="flex items-center gap-2">
          <i class="${type === 'error' ? 'ri-error-warning-line' : 'ri-checkbox-circle-line'} text-base"></i>
          <span></span>
        </div>
      `;
      el.querySelector('span').textContent = String(text ?? '');
      document.body.appendChild(el);
      
      setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateX(20px)';
        setTimeout(() => el.remove(), 300);
      }, 2500);
    }

    function showPlusOne(btn) {
      const plus = document.createElement('span');
      plus.className = 'plus-one';
      plus.innerText = '+1';
      btn.style.position = 'relative';
      btn.appendChild(plus);
      setTimeout(() => plus.remove(), 300);
    }

    function flyToCart(img, cartIcon) {
      if (!img || !cartIcon) return;
      
      const clone = img.cloneNode(true);
      const start = img.getBoundingClientRect();
      const end = cartIcon.getBoundingClientRect();

      clone.style.position = 'fixed';
      clone.style.left = start.left + 'px';
      clone.style.top = start.top + 'px';
      clone.style.width = start.width + 'px';
      clone.style.height = start.height + 'px';
      clone.style.borderRadius = '12px';
      clone.style.zIndex = 9999;
      clone.style.transition = 'all 0.6s cubic-bezier(0.34, 1.2, 0.64, 1)';
      clone.style.pointerEvents = 'none';

      document.body.appendChild(clone);

      requestAnimationFrame(() => {
        clone.style.left = end.left + 'px';
        clone.style.top = end.top + 'px';
        clone.style.width = '24px';
        clone.style.height = '24px';
        clone.style.opacity = '0.4';
        clone.style.transform = 'scale(0.3)';
      });

      setTimeout(() => clone.remove(), 600);
    }

    function updateCartCount() {
      fetch("/cart-count")
        .then(r => r.json())
        .then(data => {
          const badge = document.querySelector("[data-cart-count]");
          if (!badge) return;
          if (data.count > 0) {
            badge.classList.remove("hidden");
            badge.classList.add("inline-flex");
            badge.textContent = data.count;
          } else {
            badge.classList.add("hidden");
            badge.classList.remove("inline-flex");
          }
        })
        .catch(() => {});
    }

    updateCartCount();

    async function responseMessage(response, fallback) {
      try {
        const data = await response.json();

        if (data?.errors?.qty?.[0]) {
          return data.errors.qty[0];
        }

        if (data?.message) {
          return data.message;
        }
      } catch (error) {
        return fallback;
      }

      return fallback;
    }

    document.querySelectorAll('.js-add-to-cart-form').forEach(form => {
      form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = this.querySelector('button');
        const card = this.closest('[data-fav-card]');
        const img = card?.querySelector('img');
        const cartIcon = document.querySelector('[data-cart-icon]');

        const originalContent = btn.innerHTML;
        btn.classList.add('loading');
        btn.disabled = true;
        let added = false;

        try {
          const response = await fetch(this.action, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': this.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}',
            },
            body: new FormData(this),
          });

          if (!response.ok) {
            showToast(await responseMessage(response, 'Не удалось добавить товар в корзину'), 'error');
            return;
          }

          const data = await response.json();

          showPlusOne(btn);
          showToast(data?.message || 'Товар добавлен в корзину');
          card?.classList.add('card-added');
          setTimeout(() => card?.classList.remove('card-added'), 1000);
          if (img && cartIcon) flyToCart(img, cartIcon);
          updateCartCount();

          btn.innerHTML = '<i class="ri-check-line text-sm"></i><span>Готово</span>';
          added = true;

          if (data?.removed_from_favorites && card) {
            setTimeout(() => {
              card.classList.add('fav-removing');
              setTimeout(() => card.remove(), 220);
            }, 450);
          }
        } catch (error) {
          showToast('Не удалось добавить товар в корзину', 'error');
        } finally {
          const restore = () => {
            btn.innerHTML = originalContent;
            btn.classList.remove('loading');
            btn.disabled = false;
          };

          added ? setTimeout(restore, 900) : restore();
        }
      });
    });

    document.querySelectorAll('.js-add-all-to-cart-form').forEach(form => {
      form.addEventListener('submit', function() {
        const btn = this.querySelector('button');
        if (!btn) return;

        btn.disabled = true;
        btn.classList.add('loading');
      });
    });

    document.querySelectorAll('.js-fav-remove-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (this.dataset.submitting === 'true') return;

        this.dataset.submitting = 'true';
        this.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
        const card = this.closest('[data-fav-card]');
        card.classList.add('fav-removing');
        setTimeout(() => HTMLFormElement.prototype.submit.call(this), 200);
      });
    });

  });
  </script>

  <style>
    .favorites-mobile-safe,
    .favorites-mobile-safe * {
      box-sizing: border-box;
    }

    .favorites-mobile-safe {
      max-width: 100vw;
    }

    .fav-card {
      transition: all 0.25s cubic-bezier(0.2, 0, 0, 1);
    }
    .fav-card:hover {
      transform: translateY(-2px);
    }

    .card-added {
      background: #ecfdf5 !important;
      border-color: #10b981 !important;
    }

    .fav-removing {
      opacity: 0 !important;
      transform: translateX(-12px);
      transition: all 0.2s ease-out;
    }

    .toast {
      position: fixed;
      right: 16px;
      top: 80px;
      padding: 10px 18px;
      background: #1e293b;
      color: white;
      border-radius: 40px;
      font-size: 13px;
      font-weight: 500;
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
      animation: slideInRight 0.3s ease;
      z-index: 99999;
      backdrop-filter: blur(8px);
      background: rgba(30, 41, 59, 0.95);
    }
    .toast-success {
      border-left: 3px solid #10b981;
    }
    .toast-error {
      background: rgba(239, 68, 68, 0.95);
      border-left: 3px solid #fecaca;
    }
    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .plus-one {
      position: absolute;
      right: 4px;
      top: -4px;
      font-size: 9px;
      font-weight: 800;
      color: #10b981;
      text-shadow: 0 0 2px white;
      animation: floatUp 0.35s ease-out forwards;
      pointer-events: none;
      z-index: 10;
    }
    @keyframes floatUp {
      0% {
        opacity: 0;
        transform: translateY(4px) scale(0.6);
      }
      30% {
        opacity: 1;
        transform: translateY(-2px) scale(1.1);
      }
      100% {
        opacity: 0;
        transform: translateY(-14px) scale(0.9);
      }
    }

    button.loading {
      color: transparent !important;
      position: relative;
      pointer-events: none;
    }
    button.loading::after {
      content: "";
      position: absolute;
      width: 14px;
      height: 14px;
      top: 50%;
      left: 50%;
      margin-left: -7px;
      margin-top: -7px;
      border: 2px solid rgba(255,255,255,0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .line-clamp-1 {
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      word-break: break-word;
      overflow-wrap: anywhere;
    }
  </style>

</x-buyer-layout>
