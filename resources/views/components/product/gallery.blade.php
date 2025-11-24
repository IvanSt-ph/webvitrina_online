<div class="lg:col-span-5 w-full max-w-xl mx-auto">
    <div
        x-data="{
            activeImage: '',
            images: [
                @if ($product->image)
                    '{{ asset('storage/'.$product->image) }}',
                @endif
                @foreach ($product->gallery ?? [] as $img)
                    '{{ asset('storage/'.$img) }}',
                @endforeach
            ],

            startIndex: 0,
            visibleCount: 6,

            get canScrollUp() {
                return this.startIndex > 0
            },
            get canScrollDown() {
                return this.startIndex + this.visibleCount < this.images.length
            },
            scrollUp() {
                if (this.canScrollUp) this.startIndex--
            },
            scrollDown() {
                if (this.canScrollDown) this.startIndex++
            },

            // Свайп
            startX: 0,
            handleTouchStart(e) {
                this.startX = e.touches[0].clientX
            },
            handleTouchEnd(e) {
                const diff = e.changedTouches[0].clientX - this.startX
                if (Math.abs(diff) < 50) return

                const idx = this.images.indexOf(this.activeImage)

                if (diff < 0 && idx < this.images.length - 1) {
                    this.activeImage = this.images[idx + 1]
                }
                if (diff > 0 && idx > 0) {
                    this.activeImage = this.images[idx - 1]
                }
            }
        }"
        x-init="activeImage = images[0]"
        class="flex flex-col md:flex-row gap-4 items-start select-none"
    >


        {{-- 📱 Мобильная галерея --}}
        <div
            class="md:hidden w-full bg-gray-50 border rounded-2xl
                   flex items-center justify-center aspect-square
                   max-h-[380px] sm:max-h-[430px] md:max-h-[480px]
                   overflow-hidden relative"
            x-on:touchstart="handleTouchStart($event)"
            x-on:touchend="handleTouchEnd($event)"
        >
            <img
                :src="activeImage"
                loading="eager"
                class="w-full h-full object-contain transition-transform duration-300 ease-in-out"
            />

            {{-- индикаторы --}}
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2">
                <template x-for="(img, i) in images" :key="i">
                    <div
                        class="w-2.5 h-2.5 rounded-full transition"
                        :class="activeImage === img ? 'bg-indigo-600' : 'bg-gray-300'"
                    ></div>
                </template>
            </div>
        </div>


        {{-- 💻 Десктопная галерея --}}
        <div class="hidden md:flex gap-4 items-start w-full">

            {{-- Миниатюры --}}
            <div class="relative flex md:flex-col items-center h-[520px] lg:h-[580px] xl:h-[620px]">

                {{-- ↑ кнопка --}}
                <template x-if="canScrollUp && images.length > visibleCount">
                    <button
                        @click="scrollUp()"
                        class="absolute -top-4 left-1/2 -translate-x-1/2
                               bg-white border border-gray-200 rounded-full w-8 h-8
                               flex items-center justify-center shadow-sm hover:bg-indigo-50 hover:scale-105 transition z-10"
                    >
                        <svg class="w-4 h-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                        </svg>
                    </button>
                </template>

                {{-- Список миниатюр --}}
                <div class="flex md:flex-col gap-2 overflow-hidden w-full md:w-auto h-full relative">
                    <div
                        class="flex md:flex-col gap-2 transition-transform duration-500 ease-in-out"
                        :style="{ transform: `translateY(-${startIndex * 108}px)` }"
                    >
                        <template x-for="(img, i) in images" :key="i">
                            <img
                                :src="img"
                                loading="lazy"
                                @mouseover="activeImage = img"
                                @click="activeImage = img"
                                class="w-20 h-[6.3rem] object-cover rounded-xl border cursor-pointer
                                       transition-all duration-300 opacity-0 fade-thumb
                                       hover:ring-2 hover:ring-indigo-600 hover:scale-[1.05]"
                                :class="{ 'ring-2 ring-indigo-600': activeImage === img }"
                                x-on:load="$el.classList.remove('opacity-0')"
                            >
                        </template>
                    </div>
                </div>

                {{-- ↓ кнопка --}}
                <template x-if="canScrollDown && images.length > visibleCount">
                    <button
                        @click="scrollDown()"
                        class="absolute -bottom-4 left-1/2 -translate-x-1/2
                               bg-white border border-gray-200 rounded-full w-8 h-8
                               flex items-center justify-center shadow-sm hover:bg-indigo-50 hover:scale-105 transition z-10"
                    >
                        <svg class="w-4 h-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </template>

            </div>


            {{-- Большое фото --}}
            <div
                class="flex-1 bg-gray-50 border rounded-2xl flex items-center justify-center
                       aspect-square h-[520px] lg:h-[580px] xl:h-[620px]
                       overflow-hidden w-full relative"
            >
                <img
                    :src="activeImage"
                    loading="eager"
                    class="object-contain w-full h-full transition-transform duration-300 hover:scale-105"
                />
            </div>

        </div>
    </div>
</div>

<style>
.fade-thumb {
    opacity: 0;
    animation: fadein .35s forwards;
}
@keyframes fadein {
    to { opacity: 1; }
}
</style>
