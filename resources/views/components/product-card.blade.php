@props(['p', 'showDescription' => true])

@php
  $avg = round($p->reviews->avg('rating'), 1);
  $isFav = auth()->check() && $p->isFavoritedBy(auth()->user());
  $city = $p->city->name ?? null;
  $country = $p->city->country->name ?? $p->country->name ?? null;
  $category = $p->category->name ?? null;
@endphp

<div class="relative bg-white rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 
            transition-all duration-300 border border-gray-100 flex flex-col overflow-hidden group">

  <!-- Фото -->
  <div class="relative h-60 bg-gray-50 flex items-center justify-center overflow-hidden">
    @if($p->image)
      <img src="{{ asset('storage/'.$p->image) }}"
           alt="{{ $p->title }}"
           class="object-contain w-full h-full transition duration-500 ease-out 
                  group-hover:scale-105 group-hover:brightness-75 group-hover:blur-[1px]" />
    @else
      <span class="text-gray-400 text-sm">Нет фото</span>
    @endif

    <div class="absolute inset-0 flex items-center justify-center">
      <a href="{{ route('product.show', $p) }}"
         class="slide-up px-4 py-2 text-sm font-medium bg-white/90 text-gray-800 
                rounded-lg shadow hover:bg-white transition">
        Подробнее
      </a>
    </div>

    <div class="absolute bottom-0 left-0 right-0 bg-[#f1f8ff]/95 backdrop-blur-sm 
                text-[13px] text-gray-600 py-2 px-3 flex flex-col items-start 
                opacity-0 translate-y-full group-hover:translate-y-0 group-hover:opacity-100 
                transition-all duration-500 ease-out">
      @if($city || $country)
        <div class="flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
               viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M12 21c4.97-4.97 8-8.485 8-11.5A8 8 0 1 0 4 9.5
                     c0 3.015 3.03 6.53 8 11.5z" />
            <circle cx="12" cy="9.5" r="2.5" fill="currentColor"/>
          </svg>
          <span>{{ $city ?? '—' }}{{ $country ? ', '.$country : '' }}</span>
        </div>
      @endif
      @if($category)
        <div class="flex items-center gap-1 mt-0.5">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
               viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <span>{{ $category }}</span>
        </div>
      @endif
    </div>
  </div>

  <!-- Контент -->
  <div class="p-5 flex flex-col flex-1">
    <div class="text-lg font-semibold text-neutral-800 mb-1">
      {{ number_format($p->price, 0, ',', ' ') }} ₽
    </div>

    <h3 class="text-sm text-neutral-700 font-medium mb-2 line-clamp-2">
      {{ $p->title }}
    </h3>

    @if($showDescription)
      <p class="text-xs text-neutral-500 mb-3">
        {{ Str::limit($p->description, 60) }}
      </p>
    @endif

    <!-- Рейтинг -->
    <div class="flex items-center gap-1 mb-4">
      @for($i = 1; $i <= 5; $i++)
        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-4 h-4 {{ $i <= $avg ? 'text-yellow-400' : 'text-gray-300' }}"
             fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.729 
                   1.516 8.234L12 18.896l-7.452 4.373 
                   1.516-8.234L0 9.306l8.332-1.151z"/>
        </svg>
      @endfor
      <span class="text-xs text-gray-400 ml-1">{{ $avg > 0 ? $avg.'/5' : '—' }}</span>
    </div>

    <!-- Кнопки -->
    <div class="mt-auto flex items-center justify-between">

      <!-- 🛒 В корзину -->
      <form method="post" action="{{ route('cart.add', $p) }}">
        @csrf
        <button type="submit"
          class="px-4 py-2 text-sm font-medium bg-white border border-gray-300 
                 rounded-lg hover:bg-[#f1f8ff] text-gray-800 active:scale-[0.98] transition">
          В корзину
        </button>
      </form>

      <!-- ❤️ Избранное -->
      <button 
        type="button"
        x-data="{
          active: {{ $isFav ? 'true' : 'false' }},
          anim: '',
          toggleFavorite() {
            fetch('{{ route('favorites.toggle', $p) }}', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
              }
            })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
              this.anim = this.active ? 'implode' : 'explode';
              this.active = !this.active;
              setTimeout(() => this.anim = '', 600);

              // 🔔 уведомление
              if (data?.message) {
                const container = document.getElementById('toast-container');
                if (container) {
                  const toast = document.createElement('div');
                  toast.textContent = data.message;
                  toast.className =
                    'toast-item bg-white text-gray-800 text-sm font-medium px-4 py-2 rounded-xl shadow-lg border border-gray-200 opacity-0 translate-y-3 transition-all duration-500 ease-out pointer-events-auto';
                  container.prepend(toast);
                  setTimeout(() => toast.classList.remove('opacity-0', 'translate-y-3'), 50);
                  while (container.children.length > 7) container.lastChild.remove();
                  setTimeout(() => {
                    toast.classList.add('opacity-0', 'translate-y-3');
                    setTimeout(() => toast.remove(), 500);
                  }, 3000);
                }
              }
            })
            .catch(console.error)
          }
        }"
        @click="toggleFavorite()"
        class="relative p-2 rounded-full hover:bg-[#f1f8ff] transition"
        :class="active ? 'text-[#74bdfd] pulse' : 'text-gray-400'">

        <!-- Сердце -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
             class="w-6 h-6 transition-transform duration-300"
             :class="active ? 'scale-110' : 'scale-100'" fill="currentColor">
          <path d="M12 21.35l-1.45-1.32C5.4 15.36 
                   2 12.28 2 8.5 2 5.42 4.42 3 
                   7.5 3c1.74 0 3.41 0.81 
                   4.5 2.09C13.09 3.81 
                   14.76 3 16.5 3 19.58 3 
                   22 5.42 22 8.5c0 3.78-3.4 
                   6.86-8.55 11.54L12 21.35z"/>
        </svg>

        <!-- Частицы -->
        <template x-if="anim === 'explode'">
          <div>
            <template x-for="i in 8">
              <div class="dot" :style="'--i:' + i"></div>
            </template>
          </div>
        </template>
        <template x-if="anim === 'implode'">
          <div>
            <template x-for="i in 8">
              <div class="dot dot-in" :style="'--i:' + i"></div>
            </template>
          </div>
        </template>
      </button>

    </div>
  </div>
</div>

<!-- ⚡️СТИЛИ -->
<style>
.slide-up {
  transform: translateY(10px);
  opacity: 0;
  transition: all 0.4s ease;
}
.group:hover .slide-up {
  transform: translateY(0);
  opacity: 1;
}

/* 💥 Взрыв частиц */
.dot {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 6px;
  height: 6px;
  background: #74bdfd;
  border-radius: 50%;
  opacity: 0;
  animation: burst 0.6s ease-out forwards;
}
@keyframes burst {
  0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
  100% {
    transform: rotate(calc(var(--i) * 45deg))
               translate(-50%, -40px) scale(0.3);
    opacity: 0;
  }
}

/* 💫 Обратная анимация */
.dot-in {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 6px;
  height: 6px;
  background: #74bdfd;
  border-radius: 50%;
  opacity: 0;
  animation: collapse 0.6s ease-in forwards;
}
@keyframes collapse {
  0% {
    transform: rotate(calc(var(--i) * 45deg))
               translate(-50%, -40px) scale(0.3);
    opacity: 1;
  }
  100% { transform: translate(-50%, -50%) scale(1); opacity: 0; }
}

/* 💓 Пульсация активного сердца */
.pulse {
  animation: pulseGlow 2s ease-in-out infinite;
}
@keyframes pulseGlow {
  0%, 100% { filter: drop-shadow(0 0 0px #74bdfd); transform: scale(1); }
  50% { filter: drop-shadow(0 0 6px #74bdfd); transform: scale(1.08); }
}
</style>
