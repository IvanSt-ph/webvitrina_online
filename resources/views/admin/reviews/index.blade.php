@extends('admin.layout')
@section('title', 'Отзывы')

@section('content')
<div x-data="reviewPanel()" x-init="init()" class="space-y-8">

    <!-- Заголовок + счётчики + фильтры -->
    <div class="flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-800">Отзывы</h1>

            <div class="flex flex-wrap gap-2">
                <span class="px-2.5 py-1 rounded-lg text-xs sm:text-sm bg-gray-100 text-gray-700">Все: {{ $counters['all'] }}</span>
                <span class="px-2.5 py-1 rounded-lg text-xs sm:text-sm bg-yellow-100 text-yellow-800">На модерации: {{ $counters['pending'] }}</span>
                <span class="px-2.5 py-1 rounded-lg text-xs sm:text-sm bg-green-100 text-green-800">Одобрено: {{ $counters['approved'] }}</span>
                <span class="px-2.5 py-1 rounded-lg text-xs sm:text-sm bg-red-100 text-red-800">Отклонено: {{ $counters['rejected'] }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <select x-model="filters.status" @change="reload()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                <option value="all">Все</option>
                <option value="pending">На модерации</option>
                <option value="approved">Одобренные</option>
                <option value="rejected">Отклонённые</option>
            </select>

            <select x-model="filters.sort" @change="reload()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                <option value="desc">Сначала новые</option>
                <option value="asc">Сначала старые</option>
            </select>
        </div>
    </div>

    <!-- 📋 Список отзывов -->
    <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100 shadow-sm">
        @foreach($reviews as $review)
            <div id="review-{{ $review->id }}"
                 class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 hover:bg-gray-50 transition cursor-pointer"
                 @click="openModal({{ $review->toJson() }})">

                <!-- 👤 Пользователь и текст -->
                <div class="flex items-center gap-3 w-full sm:w-auto flex-1">
                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                        {{ mb_substr($review->user->name ?? '?', 0, 1) }}
                    </div>
                    <div class="flex flex-col">
                        <div class="font-medium text-gray-800">{{ $review->user->name }}</div>
                        <div class="text-xs text-gray-500">{{ $review->user->email }}</div>
                    </div>
                </div>

                <!-- ⭐ Рейтинг -->
                <div class="flex items-center text-yellow-400">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="{{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                    @endfor
                </div>

                <!-- 🏷️ Статус -->
                <span class="status-badge text-xs px-2 py-1 rounded-lg font-medium whitespace-nowrap
                    @if($review->status === 'approved') bg-green-100 text-green-700
                    @elseif($review->status === 'rejected') bg-red-100 text-red-700
                    @else bg-yellow-100 text-yellow-700 @endif">
                    {{ $review->status_label }}
                </span>

                <!-- 📆 Дата -->
                <div class="text-xs text-gray-500 whitespace-nowrap">
                    {{ $review->created_at->format('d.m.Y H:i') }}
                </div>

                <!-- ⚙️ Кнопки -->
                <div class="flex gap-2">
                    @if($review->status !== 'approved')
                        <button @click.stop="changeStatus({{ $review->id }}, 'approve')"
                                class="btn-approve px-2 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-xs">
                            Одобрить
                        </button>
                    @endif
                    @if($review->status !== 'rejected')
                        <button @click.stop="changeStatus({{ $review->id }}, 'reject')"
                                class="btn-reject px-2 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-xs">
                            Отклонить
                        </button>
                    @endif
                    <button @click.stop="confirmDelete({{ $review->id }})"
                            class="btn-delete px-2 py-1 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs">
                        Удалить
                    </button>
                </div>
            </div>
        @endforeach

        @if($reviews->isEmpty())
            <div class="text-center text-gray-500 py-8">Нет отзывов</div>
        @endif
    </div>

    <div class="pt-4">{{ $reviews->links() }}</div>

    <!-- 🪟 Модальное окно просмотра -->
<div 
    x-show="modal.open"
    x-transition.opacity.duration.250ms
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
>
    <div 
        @click.away="modal.open = false"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-8 relative overflow-y-auto max-h-[90vh]"
    >
        <!-- ✖ Кнопка закрытия -->
        <button 
            @click="modal.open = false"
            class="absolute top-3 right-4 text-gray-400 hover:text-gray-600 text-2xl"
        >×</button>

        <!-- 🧾 Заголовок -->
        <div class="mb-4">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
                <span class="inline-block w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                    <span x-text="modal.data.user?.name?.[0] ?? '?'"></span>
                </span>
                <span x-text="modal.data.user?.name"></span>
            </h2>
            <p class="text-sm text-gray-500" x-text="modal.data.user?.email"></p>
        </div>

        <!-- 💬 Текст -->
        <div class="bg-gray-50 p-4 rounded-xl border text-gray-800 text-[15px] leading-relaxed mb-4 whitespace-pre-line"
             x-text="modal.data.body ?? '—'"></div>

        <!-- 🖼️ Изображения -->
        <template x-if="modal.data.images && modal.data.images.length">
            <div class="flex flex-wrap gap-3 mb-4">
                <template x-for="img in modal.data.images" :key="img.id">
                    <img :src="'/storage/' + img.path"
                         class="w-24 h-24 object-cover rounded-lg border hover:scale-105 transition cursor-pointer"
                         @click.stop="openImage('/storage/' + img.path)">
                </template>
            </div>
        </template>

        <!-- 📦 Информация о товаре -->
        <div class="space-y-2 text-sm text-gray-600">
            <div>
                <span class="font-semibold text-gray-800">Товар:</span>
                <a :href="modal.data.product?.slug ? '/p/' + modal.data.product.slug : '#'"
                   target="_blank"
                   class="text-indigo-600 hover:underline"
                   x-text="modal.data.product?.title ?? '—'"></a>
            </div>

            <div>
                <span class="font-semibold text-gray-800">Рейтинг:</span>
                <span class="text-yellow-400" x-text="'★'.repeat(modal.data.rating || 0)"></span>
                <span x-text="modal.data.rating + ' из 5'"></span>
            </div>

            <div>
                <span class="font-semibold text-gray-800">Статус:</span>
                <span 
                    :class="{
                        'text-yellow-600': modal.data.status === 'pending',
                        'text-green-600': modal.data.status === 'approved',
                        'text-red-600': modal.data.status === 'rejected'
                    }"
                    x-text="statusLabel(modal.data.status)"
                ></span>
            </div>

            <div>
                <span class="font-semibold text-gray-800">Дата:</span>
                <span x-text="formatDate(modal.data.created_at)"></span>
            </div>
        </div>

        <!-- ⚙️ Кнопки действий -->
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
            <button 
                class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200"
                @click="modal.open = false"
            >Закрыть</button>

            <template x-if="modal.data.status !== 'approved'">
                <button 
                    class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700"
                    @click="changeStatus(modal.data.id, 'approve'); modal.open = false"
                >Одобрить</button>
            </template>

            <template x-if="modal.data.status !== 'rejected'">
                <button 
                    class="px-4 py-2 rounded-lg bg-yellow-500 text-white hover:bg-yellow-600"
                    @click="changeStatus(modal.data.id, 'reject'); modal.open = false"
                >Отклонить</button>
            </template>

            <button 
                class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700"
                @click="confirmDelete(modal.data.id); modal.open = false"
            >Удалить</button>
        </div>
    </div>
</div>


    <!-- Toast -->
    <div x-show="toast.show"
         x-transition.opacity.duration.200ms
         class="fixed bottom-6 right-6 z-50">
        <div :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
             class="text-white px-4 py-2 rounded-xl shadow-lg">
            <span x-text="toast.message"></span>
        </div>
    </div>



<!-- 🖼 Lightbox для фото -->
<template x-teleport="body">
    <div 
        x-show="lightbox.open"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm"
        @click.self="lightbox.open = false"
    >
<div 
    class="relative bg-white rounded-xl shadow-2xl border border-gray-200"
    style="width: 450px; height: 450px;"
    @click.stop
>
    <!-- ✖ Кнопка закрытия -->
    <button 
        @click="lightbox.open = false"
        class="absolute top-0 right-0 m-2 bg-white text-gray-700 hover:text-red-600 rounded-full shadow-md w-7 h-7 flex items-center justify-center text-lg font-bold"
        title="Закрыть"
    >
        ×
    </button>

    <!-- Flex-обертка для центрирования -->
    <div class="w-full h-full flex items-center justify-center bg-gray-50">
        <img 
            :src="lightbox.url" 
            alt="Фото отзыва"
            class="max-w-[450px] max-h-[450px] object-contain"
        >
    </div>
</div>



</template>




</div>

<script>
function reviewPanel() {
    return {
        filters: { status: @json($status), sort: @json($sort) },
        toast: { show: false, message: '', type: 'success' },
        modal: { open: false, data: {} },
        lightbox: { open: false, url: '' }, // 🆕 добавили lightbox

        init() {},

        reload() {
            const params = new URLSearchParams(this.filters).toString();
            window.location = `/admin/reviews?${params}`;
        },

        /** 🔍 Открыть модалку с актуальными данными */
        async openModal(data) {
            try {
                const res = await fetch(`/admin/reviews/${data.id}`);
                if (res.ok) {
                    this.modal.data = await res.json();
                } else {
                    this.modal.data = data;
                }
            } catch {
                this.modal.data = data;
            }
            this.modal.open = true;
        },

        /** 🌟 Toast */
        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => this.toast.show = false, 2500);
        },

        /** ✅ / 🚫 Статусы */
        async changeStatus(id, action) {
            const res = await fetch(`/admin/reviews/${id}/${action}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            if (res.ok) {
                document.getElementById('review-' + id)?.classList.add('opacity-50');
                this.showToast('Статус обновлён ✅');
            } else {
                this.showToast('Ошибка при обновлении', 'error');
            }
        },

        /** ❌ Удаление с подтверждением */
        confirmDelete(id) {
            if (confirm('Удалить отзыв безвозвратно?')) this.deleteReview(id);
        },

        async deleteReview(id) {
            const card = document.getElementById('review-' + id);
            try {
                const res = await fetch(`/admin/reviews/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error();
                card.classList.add('opacity-0', 'scale-95', 'transition', 'duration-200');
                setTimeout(() => card.remove(), 200);
                this.showToast('Отзыв удалён ✅');
            } catch (e) {
                this.showToast('Ошибка при удалении', 'error');
            }
        },

        /** 🏷 Метки статусов */
        statusLabel(status) {
            switch (status) {
                case 'approved': return '✅ Одобрен';
                case 'rejected': return '🚫 Отклонён';
                case 'pending':  return '⏳ На модерации';
                default: return '—';
            }
        },

        /** 📅 Формат даты */
        formatDate(dateString) {
            if (!dateString) return '—';
            const d = new Date(dateString);
            return d.toLocaleString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },

        /** 🖼 Lightbox */
        openImage(url) {
            this.lightbox.url = url;
            this.lightbox.open = true;
        },
    }
}
</script>

@endsection
