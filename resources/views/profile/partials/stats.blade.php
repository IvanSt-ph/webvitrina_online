{{-- resources/views/profile/partials/stats.blade.php --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Дата регистрации --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <i class="ri-calendar-line text-blue-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Дата регистрации</p>
                <p class="text-lg font-semibold text-gray-900">
                    {{ Auth::user()->created_at?->format('d.m.Y') ?? '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Магазин (если есть) --}}
    @if (Auth::user()->shop)
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-5 border border-purple-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="ri-store-2-line text-purple-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-600">Ваш магазин</p>
                    <p class="text-lg font-semibold text-gray-900 truncate">{{ Auth::user()->shop->name ?? 'Без названия' }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Создан {{ Auth::user()->shop->created_at?->diffForHumans() }}
                    </p>
                </div>
                <a href="{{ route('seller.show', Auth::user()->id) }}" 
                   class="inline-flex items-center gap-1 text-purple-600 hover:text-purple-800 group">
                    <span class="text-sm font-medium group-hover:underline">В магазин</span>
                    <i class="ri-arrow-right-s-line text-lg transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>
        </div>
    @endif
</div>