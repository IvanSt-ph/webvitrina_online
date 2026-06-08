<!-- Выдвижное боковое меню категорий -->
<div
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
        <header class="border-b border-slate-100 bg-gradient-to-br from-indigo-50 via-white to-slate-50 px-4 py-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-600">Каталог</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Категории</h2>
                    <p class="mt-1 text-sm leading-5 text-slate-500">Быстрый переход по разделам WebVitrina.</p>
                </div>

                <button
                    type="button"
                    @click="open = false"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                    aria-label="Закрыть меню категорий"
                >
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <a href="{{ route('category.index') }}"
               @click="open = false"
               class="mt-4 flex h-11 items-center justify-between rounded-2xl bg-slate-950 px-4 text-sm font-bold text-white shadow-lg shadow-slate-950/15 transition hover:-translate-y-0.5 hover:bg-indigo-600">
                <span class="inline-flex items-center gap-2">
                    <i class="ri-layout-grid-line text-lg"></i>
                    Все категории
                </span>
                <i class="ri-arrow-right-line"></i>
            </a>
        </header>

        <div class="flex-1 overflow-y-auto px-3 py-3 custom-scrollbar">
            @if(($categories ?? collect())->isNotEmpty())
                <ul class="space-y-1.5">
                    @foreach($categories as $cat)
                        <x-category-item :category="$cat" />
                    @endforeach
                </ul>
            @else
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-indigo-600 shadow-sm">
                        <i class="ri-folder-warning-line text-xl"></i>
                    </div>
                    <h3 class="mt-3 text-sm font-bold text-slate-950">Категории пока не добавлены</h3>
                    <p class="mt-1 text-sm leading-5 text-slate-500">Когда появятся разделы каталога, они будут здесь.</p>
                </div>
            @endif
        </div>
    </aside>
</div>
