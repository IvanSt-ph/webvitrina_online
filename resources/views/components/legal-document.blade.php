@props([
    'title',
    'eyebrow' => 'Правовая информация',
    'summary',
    'sections',
])

<x-app-layout :title="$title">
    <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)] lg:items-start">
            <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-600">Содержание</p>
                <nav class="mt-4 space-y-1" aria-label="Содержание документа">
                    @foreach($sections as $section)
                        <a href="#{{ $section['id'] }}" class="block rounded-xl px-3 py-2 text-sm font-medium leading-5 text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700">
                            {{ $section['title'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <article class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 bg-gradient-to-br from-indigo-50 via-white to-violet-50 px-5 py-8 sm:px-8 sm:py-10">
                    <p class="text-sm font-bold text-indigo-700">{{ $eyebrow }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
                    <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600">{{ $summary }}</p>
                    {{-- TODO LEGAL: заполнить и юридически проверить перед production. --}}
                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                        Технический проект документа. Владелец: <strong>[НАЗВАНИЕ ВЛАДЕЛЬЦА]</strong>,
                        форма: <strong>[ЮРИДИЧЕСКАЯ ФОРМА]</strong>, регистрационный номер:
                        <strong>[РЕГИСТРАЦИОННЫЙ НОМЕР]</strong>. Дата вступления в силу:
                        <strong>[ДАТА ВСТУПЛЕНИЯ В СИЛУ]</strong>.
                    </div>
                </header>

                <div class="space-y-10 px-5 py-8 sm:px-8 sm:py-10">
                    @foreach($sections as $section)
                        <section id="{{ $section['id'] }}" class="scroll-mt-24">
                            <h2 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ $section['title'] }}</h2>

                            @foreach($section['paragraphs'] ?? [] as $paragraph)
                                <p class="mt-4 text-[15px] leading-7 text-slate-600 sm:text-base">{{ $paragraph }}</p>
                            @endforeach

                            @if(!empty($section['items']))
                                <ul class="mt-4 space-y-3">
                                    @foreach($section['items'] as $item)
                                        <li class="flex gap-3 text-[15px] leading-7 text-slate-600 sm:text-base">
                                            <i class="ri-checkbox-circle-line mt-1 shrink-0 text-indigo-500" aria-hidden="true"></i>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @isset($section['note'])
                                <div class="mt-5 rounded-2xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm leading-6 text-slate-700">
                                    {{ $section['note'] }}
                                </div>
                            @endisset
                        </section>
                    @endforeach
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
