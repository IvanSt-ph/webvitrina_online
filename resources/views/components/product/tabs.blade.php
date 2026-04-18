@props(['product', 'reviews', 'myReview'])

<div
    class="mt-12 bg-white border rounded-2xl shadow-sm p-6"
    x-data="{ tab: 'desc' }"
>

    {{-- Навигация вкладок --}}
    <div class="flex flex-wrap gap-6 border-b pb-2 text-sm">
        <button
            @click="tab='desc'"
            :class="tab==='desc'
                ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium'
                : 'text-gray-600'"
            class="pb-2 transition"
        >
            Описание
        </button>

        <button
            @click="tab='sizes'"
            :class="tab==='sizes'
                ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium'
                : 'text-gray-600'"
            class="pb-2 transition"
        >
            Размеры
        </button>

        <button
            @click="tab='props'"
            :class="tab==='props'
                ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium'
                : 'text-gray-600'"
            class="pb-2 transition"
        >
            Характеристики
        </button>

        <button
            @click="tab='reviews'"
            :class="tab==='reviews'
                ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium'
                : 'text-gray-600'"
            class="pb-2 transition"
        >
            Отзывы ({{ $product->reviews_count }})
        </button>
    </div>

    {{-- Контент вкладок --}}
    <div class="mt-6">

        {{-- Описание с раскрытием --}}
        <div x-show="tab==='desc'" x-transition.opacity.duration.400ms>
            <div x-data="{ expanded: false }" class="relative">
                <div 
                    x-bind:class="expanded ? '' : 'max-h-32 overflow-hidden'" 
                    class="text-gray-700 leading-relaxed"
                >
                    <div class="whitespace-pre-wrap break-words">
                        {{ $product->description }}
                    </div>
                </div>
                
                @php
                    $descLength = strlen(strip_tags($product->description));
                @endphp
                
                @if($descLength > 200)
                    <div 
                        x-show="!expanded" 
                        class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-t from-white to-transparent pointer-events-none"
                    ></div>
                    <button 
                        @click="expanded = !expanded" 
                        class="mt-2 text-indigo-600 hover:text-indigo-700 text-sm font-medium inline-flex items-center gap-1 transition-colors"
                    >
                        <span x-text="expanded ? 'Свернуть ↑' : 'Показать полностью ↓'"></span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Размеры --}}
        <div x-show="tab==='sizes'" x-transition.opacity.duration.400ms>
            <p class="text-gray-700">
                Таблица размеров (сюда можно вывести данные из БД).
            </p>
        </div>

        {{-- Характеристики --}}
        <div x-show="tab==='props'" x-transition.opacity.duration.400ms>
            <ul class="text-gray-700 list-disc pl-5 space-y-1">
                <li>Материал: {{ $product->material ?? '—' }}</li>
                <li>Сезон: {{ $product->season ?? 'Всесезон' }}</li>
                <li>Бренд: {{ $product->brand->name ?? '—' }}</li>
            </ul>
        </div>

        {{-- ================= ОТЗЫВЫ ================= --}}
        <div
            x-show="tab==='reviews'"
            x-cloak
            class="space-y-6"
            x-transition.opacity.duration.400ms
            x-data
            x-init="
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(el => {
                        if (el.isIntersecting) {
                            el.target.classList.add('animate-fade-in-up');
                            observer.unobserve(el.target);
                        }
                    });
                }, { threshold: 0.1 });
                
                $nextTick(() => {
                    document.querySelectorAll('.review-card').forEach(card => {
                        observer.observe(card);
                    });
                });
            "
        >

            {{-- Форма моего отзыва --}}
            @auth
                <div
                    x-data="{
                        editing: {{ $myReview ? 'false' : 'true' }},
                        rating:  {{ $myReview->rating ?? 0 }},
                        hoverRating: 0
                    }"
                    class="bg-gray-50 border rounded-2xl p-5 shadow-sm space-y-3"
                >

                    {{-- Если уже есть отзыв --}}
                    <template x-if="!editing">
                        <div class="flex justify-between items-center gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Ваш отзыв</h3>
                                <p class="text-gray-700 mt-1">
                                    {{ $myReview->body ?? 'Без текста' }}
                                </p>

                                @if ($myReview && $myReview->images->count())
                                    <div class="mt-3 flex gap-3 flex-wrap">
                                        @foreach ($myReview->images as $img)
                                            <a href="{{ asset('storage/'.$img->path) }}" target="_blank">
                                                <img
                                                    src="{{ asset('storage/'.$img->path) }}"
                                                    class="w-24 h-24 object-cover rounded-lg border
                                                           hover:scale-105 transition-transform duration-300"
                                                >
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <button
                                @click="editing = true"
                                class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700
                                       text-white rounded-lg transition"
                            >
                                ✏️ Изменить
                            </button>
                        </div>
                    </template>

                    {{-- Форма редактирования / создания --}}
                    <template x-if="editing">
                        <form
                            method="post"
                            action="{{ route('review.store', $product) }}"
                            enctype="multipart/form-data"
                            class="space-y-3"
                        >
                            @csrf

                            <h3 class="text-lg font-semibold text-gray-800">
                                {{ $myReview ? 'Изменить отзыв' : 'Оставить отзыв' }}
                            </h3>

                            {{-- Звёзды --}}
                            <div
                                class="flex items-center gap-2"
                                @mouseleave="hoverRating = 0"
                            >
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg
                                        @mouseover="hoverRating={{ $i }}"
                                        @click="rating={{ $i }}"
                                        :class="{
                                            'text-yellow-400 scale-110':
                                                {{ $i }} <= (hoverRating || rating),
                                            'text-gray-300':
                                                {{ $i }} > (hoverRating || rating)
                                        }"
                                        class="w-8 h-8 cursor-pointer transition-all duration-200 transform"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.945a1 1 0 00.95.69h4.148c.969 0 1.371 1.24.588 1.81l-3.357 2.44a1 1 0 00-.364 1.118l1.286 3.945c.3.921-.755 1.688-1.54 1.118l-3.357-2.44a1 1 0 00-1.175 0l-3.357 2.44c-.784.57-1.839-.197-1.54-1.118l1.286-3.945a1 1 0 00-.364-1.118L2.075 9.372c-.783-.57-.38-1.81.588-1.81h4.148a1 1 0 00.95-.69l1.286-3.945z"
                                        />
                                    </svg>
                                @endfor

                                <input type="hidden" name="rating" :value="rating">
                            </div>

                            {{-- Текст --}}
                            <textarea
                                name="body"
                                rows="3"
                                placeholder="Поделись впечатлениями о товаре..."
                                class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            >{{ $myReview->body ?? '' }}</textarea>

                            {{-- Фото --}}
                            <input
                                type="file"
                                name="images[]"
                                multiple
                                accept="image/*"
                                class="block w-full text-sm text-gray-600 border rounded-lg p-2
                                       cursor-pointer hover:border-indigo-500 transition"
                            >
                            <p class="text-xs text-gray-400 mt-1">
                                Можно добавить до 3 фото
                            </p>

                            <div class="flex justify-between items-center">
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700
                                           text-white rounded-lg shadow transition"
                                >
                                    💾 {{ $myReview ? 'Сохранить изменения' : 'Отправить' }}
                                </button>

                                @if ($myReview)
                                    <button
                                        type="button"
                                        @click="editing = false"
                                        class="text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        Отмена
                                    </button>
                                @endif
                            </div>
                        </form>
                    </template>
                </div>
            @endauth

            {{-- Список отзывов --}}
            <div class="space-y-4">
                @forelse ($reviews as $r)
                    <div
                        class="review-card opacity-0 translate-y-6
                               bg-white border rounded-2xl p-4 shadow-sm
                               hover:shadow-md transition"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-indigo-100
                                           flex items-center justify-center
                                           text-indigo-700 font-bold"
                                >
                                    {{ mb_substr($r->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800">
                                        {{ $r->user->name }}
                                    </div>
                                    <div class="flex text-yellow-400 text-sm">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <span class="{{ $i <= $r->rating ? 'text-yellow-400' : 'text-gray-300' }}">
                                                ★
                                            </span>
                                        @endfor
                                    </div>
                                </div>
                            </div>

                            <div class="text-xs text-gray-500">
                                {{ $r->created_at->diffForHumans() }}
                            </div>
                        </div>

                        <div class="text-gray-700 leading-relaxed border-t pt-2">
                            {{ $r->body }}
                        </div>

                        @if ($r->images->count())
                            <div class="mt-3 flex gap-3 flex-wrap">
                                @foreach ($r->images as $img)
                                    <a href="{{ asset('storage/'.$img->path) }}" target="_blank">
                                        <img
                                            src="{{ asset('storage/'.$img->path) }}"
                                            class="w-24 h-24 object-cover rounded-lg border
                                                   hover:scale-105 transition-transform duration-300"
                                        >
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-10">
                        <p class="text-lg">Пока нет отзывов 😌</p>
                        <p class="text-sm mt-1">
                            Стань первым, кто поделится мнением о товаре!
                        </p>
                    </div>
                @endforelse
                
                {{-- Пагинация --}}
                <div class="mt-6">
                    {{ $reviews->links() }}
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Анимации и стили --}}
<style>
    @keyframes fade-in-up {
        0% {
            opacity: 0;
            transform: translateY(12px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.6s ease forwards;
    }
    
    .whitespace-pre-wrap {
        white-space: pre-wrap;
    }
    
    .break-words {
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-word;
    }
    
    .max-h-32 {
        max-height: 8rem;
    }
    
    .overflow-hidden {
        overflow: hidden;
    }
</style>