{{-- resources/views/profile/partials/header.blade.php --}}
<div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
    {{-- Заголовок слева --}}
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
            <i class="ri-user-line text-lg"></i>
        </div>
        <div>
            <h2 class="text-xl font-semibold text-neutral-950">Профиль пользователя</h2>
            <p class="mt-0.5 text-sm text-neutral-500">Управление личными данными и настройками</p>
        </div>
    </div>
    
    {{-- Компактная статистика --}}
    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
        {{-- Дата регистрации --}}
        <div class="flex w-full items-center gap-1.5 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm lg:w-auto">
            <i class="ri-calendar-line text-neutral-500"></i>
            <span class="text-xs text-neutral-500">Дата регистрации:</span>
            <span class="ml-auto font-medium text-neutral-700 lg:ml-0">{{ Auth::user()->created_at?->format('d.m.Y') ?? '—' }}</span>
        </div>
        
        {{-- Магазин (если есть) --}}
        @if (Auth::user()->shop)
            <div class="flex items-center gap-1.5 w-full lg:w-auto">
                <a href="{{ route('seller.show', Auth::user()->shop->slug) }}" 
                   class="group flex flex-1 items-center gap-1.5 rounded-xl border border-brand-100 bg-brand-50 px-3 py-2 text-sm transition hover:bg-brand-100 lg:flex-auto">
                    <i class="ri-store-2-line text-brand-600"></i>
                    
                    <span class="ml-auto max-w-[100px] truncate font-medium text-brand-700 lg:ml-0 lg:max-w-[150px]">{{ Auth::user()->shop->name ?? 'Без названия' }}</span>
                    <i class="ri-arrow-right-s-line ml-auto text-brand-400 transition-transform group-hover:translate-x-0.5 lg:ml-0"></i>
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
