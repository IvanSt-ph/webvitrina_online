{{-- resources/views/profile/partials/header.blade.php --}}
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
    {{-- Заголовок слева --}}
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-sm">
            <i class="ri-user-line text-white text-lg"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-900">Профиль пользователя</h2>
            <p class="text-sm text-gray-500 mt-0.5">Управление личными данными и настройками</p>
        </div>
    </div>
    
    {{-- Компактная статистика --}}
    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
        {{-- Дата регистрации --}}
        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 rounded-lg text-sm border border-gray-100 w-full lg:w-auto">
            <i class="ri-calendar-line text-gray-500"></i>
            <span class="text-gray-500 text-xs">Дата регистрации:</span>
            <span class="text-gray-700 font-medium ml-auto lg:ml-0">{{ Auth::user()->created_at?->format('d.m.Y') ?? '—' }}</span>
        </div>
        
        {{-- Магазин (если есть) --}}
        @if (Auth::user()->shop)
            <div class="flex items-center gap-1.5 w-full lg:w-auto">
                <a href="{{ route('seller.show', Auth::user()->id) }}" 
                   class="flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 hover:bg-purple-100 rounded-lg text-sm border border-purple-100 transition-all group flex-1 lg:flex-auto">
                    <i class="ri-store-2-line text-purple-600"></i>
                    
                    <span class="text-purple-700 font-medium max-w-[100px] lg:max-w-[150px] truncate ml-auto lg:ml-0">{{ Auth::user()->shop->name ?? 'Без названия' }}</span>
                    <i class="ri-arrow-right-s-line text-purple-400 group-hover:translate-x-0.5 transition-transform ml-auto lg:ml-0"></i>
                </a>
                
                {{-- Иконка обновления магазина с тултипом --}}
                <div class="relative group">
                    <i class="ri-history-line text-gray-300 hover:text-gray-500 text-sm cursor-help transition-colors"></i>
                    <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity text-xs bg-gray-800 text-white px-2 py-1 rounded whitespace-nowrap pointer-events-none z-20">
                        Магазин обновлен {{ Auth::user()->shop->updated_at?->diffForHumans() ?? '—' }}
                    </span>
                </div>
            </div>
        @endif
    </div>
</div>