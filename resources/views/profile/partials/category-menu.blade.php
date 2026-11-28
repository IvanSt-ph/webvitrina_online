<!-- Выдвижное боковое меню категорий -->
<div
    x-data="categoryMenu"
    data-tree-url="{{ route('category.menu') }}"
    x-show="open"
    class="fixed inset-0 z-50 flex"
    x-cloak
    @keydown.escape.window="open = false"
    @category-menu-close.window="open = false"
>
    <div
        class="fixed inset-0 bg-slate-950/45 backdrop-blur-sm transition-opacity duration-300"
        x-show="open"
        x-transition.opacity
        @click="open = false"
    ></div>

        <aside
        class="relative z-50 flex h-full w-full max-w-[390px] flex-col overflow-hidden bg-white shadow-2xl shadow-slate-950/20 transition-transform duration-300 sm:max-w-[420px]"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        role="dialog"
        aria-modal="true"
        aria-label="Категории"
    >
        <header class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Категории</h2>

                <button
                    type="button"
                    @click="open = false"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600"
                    aria-label="Закрыть меню категорий"
                >
                    <span aria-hidden="true" class="text-2xl">×</span>
                </button>
        </header>

        <div class="shrink-0 border-b border-slate-100 px-3 py-3">
            <a href="{{ route('category.index') }}"
               @click="open = false"
               class="flex h-11 items-center justify-between rounded-xl bg-indigo-50 px-3 text-sm font-medium text-slate-900 transition-colors hover:bg-indigo-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600">
                <span class="inline-flex items-center gap-3">
                    <i class="ri-layout-grid-line text-lg text-slate-500"></i>
                    Все категории
                </span>
                <i class="ri-arrow-right-line text-slate-400"></i>
            </a>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-3 custom-scrollbar">
            <div x-show="loading" role="status" class="p-5 text-center text-sm text-slate-500">
                Загрузка категорий…
            </div>
            <div x-show="failed" x-cloak role="alert" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="text-sm text-slate-500">Не удалось загрузить категории.</p>
                <button type="button" @click="loadTree()" class="mt-3 text-sm font-bold text-indigo-600">
                    Попробовать снова
                </button>
            </div>
            <div x-html="html" :aria-busy="loading"></div>
        </div>
    </aside>
</div>

@once
    @push('styles')
        <style>
            .category-menu-media-failed .category-menu-fallback { display: inline !important; }
        </style>
    @endpush
@endonce
