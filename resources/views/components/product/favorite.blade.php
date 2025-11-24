<div
    x-data="{ copied: false }"
    class="mt-4 pt-3 border-t border-indigo-100 flex items-center justify-between gap-3
           text-sm text-gray-600"
>

    {{-- Артикул --}}
    @if ($product->sku)
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 text-gray-500">
                <span class="text-gray-400">Арт.</span>
                <span class="font-medium text-gray-800" id="sku-value">
                    {{ $product->sku }}
                </span>
            </div>

            <button
                @click="navigator.clipboard.writeText('{{ $product->sku }}'); copied = true; setTimeout(() => copied = false, 1500)"
                class="text-gray-400 hover:text-indigo-600 transition"
                title="Скопировать артикул"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                     viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-2 8h4a2 2 0 002-2v-4a2 2 0 00-2-2h-4a2 2 0 00-2 2v4a2 2 0 002 2z" />
                </svg>
            </button>

            <span
                x-show="copied"
                x-transition
                class="text-green-600 text-xs font-medium"
            >
                Скопировано
            </span>
        </div>
    @endif

    {{-- ❤️ Форма избранного --}}
    <form method="POST" action="{{ route('favorites.toggle', $product) }}"
          x-data="{
            active: {{ $isFav ? 'true' : 'false' }},
            anim: '',
            toggleFavorite() {
                fetch('{{ route('favorites.toggle', $product) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(() => {
                    this.anim  = this.active ? 'implode' : 'explode';
                    this.active = !this.active;
                    setTimeout(() => this.anim = '', 600);
                });
            }
          }"
          @submit.prevent="toggleFavorite()"
          class="relative flex flex-col items-center gap-1 mt-2"
    >

        <button
            type="button"
            @click="toggleFavorite()"
            class="p-1.5 transition relative"
        >
            <svg
                class="w-6 h-6 transition-transform duration-300"
                :class="active ? 'scale-110' : 'scale-100'"
                :fill="active ? '#74bdfd' : 'transparent'"
                :stroke="active ? 'none' : '#74bdfd'"
                stroke-width="1.7"
                viewBox="0 0 24 24"
            >
                <path
                    d="M12 21.35l-1.45-1.32C5.4 15.36
                       2 12.28 2 8.5 2 5.42 4.42 3
                       7.5 3c1.74 0 3.41 0.81
                       4.5 2.09C13.09 3.81
                       14.76 3 16.5 3c3.08 0 5.5 2.42 5.5 5.5
                       0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                />
            </svg>

            {{-- Explode --}}
            <template x-if="anim === 'explode'">
                <div>
                    <template x-for="i in 8">
                        <div class="dot" :style="'--i:' + i"></div>
                    </template>
                </div>
            </template>

            {{-- Implode --}}
            <template x-if="anim === 'implode'">
                <div>
                    <template x-for="i in 8">
                        <div class="dot dot-in" :style="'--i:' + i"></div>
                    </template>
                </div>
            </template>
        </button>

        <span class="text-xs text-gray-500">В избранное</span>
    </form>

</div>

<style>
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
        0% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
        100% {
            transform: rotate(calc(var(--i) * 45deg))
                       translate(-50%, -40px)
                       scale(.3);
            opacity: 0;
        }
    }
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
                       translate(-50%, -40px)
                       scale(.3);
            opacity: 1;
        }
        100% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 0;
        }
    }
</style>
