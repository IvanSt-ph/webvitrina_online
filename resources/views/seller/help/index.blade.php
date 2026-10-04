<x-seller-layout title="Центр помощи продавца">
    <div class="min-h-screen w-full overflow-x-hidden bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
        <div class="w-full space-y-6">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-graduation-cap-line" aria-hidden="true"></i>
                        База знаний продавца
                    </div>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Центр помощи продавца</h1>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">Практические инструкции по товарам, продажам, отзывам и новым возможностям WebVitrina.</p>
                </div>

                <a href="{{ route('support') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 sm:w-auto">
                    <i class="ri-customer-service-2-line" aria-hidden="true"></i>
                    Задать вопрос поддержке
                </a>
            </header>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Статьи для продавцов">
                @foreach(config('seller_news') as $news)
                    @php
                        $slug = last(explode('/', $news['url']));
                        $imagePath = "images/help/{$slug}.webp";
                        $image = file_exists(public_path($imagePath))
                            ? asset($imagePath)
                            : asset('images/help-banner.webp');
                        $descriptions = [
                            'boost-sales' => 'Как повысить продажи и увеличить количество заказов без дополнительных затрат.',
                            'product-optimization' => 'Узнайте, как сделать карточки товаров привлекательнее и повысить конверсию.',
                            'updates-2025' => 'Последние обновления и новые инструменты, упрощающие работу продавцов.',
                            'updates-2026' => 'нововедения в 2026 году.',
                            'reviews-and-rating' => 'Как управлять отзывами и рейтингом, чтобы завоевать доверие покупателей.',
                        ];
                    @endphp

                    <a href="{{ $news['url'] }}" class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white transition hover:border-brand-200 hover:bg-neutral-50/50">
                        <div class="relative aspect-[16/8] overflow-hidden bg-neutral-100">
                            <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $image }}" alt="{{ $news['title'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                            <div class="absolute inset-0 bg-gradient-to-t from-neutral-950/60 via-transparent to-transparent"></div>
                            <span class="absolute bottom-3 left-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-neutral-700 backdrop-blur">{{ $news['date'] }}</span>
                        </div>
                        <div class="flex flex-1 flex-col p-4 sm:p-5">
                            <h2 class="text-base font-semibold leading-6 text-neutral-950 transition group-hover:text-brand-700">{{ $news['title'] }}</h2>
                            <p class="mt-2 flex-1 text-sm leading-6 text-neutral-500">{{ $descriptions[$slug] ?? 'Советы и рекомендации для продавцов WebVitrina.' }}</p>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-600">
                                Читать статью
                                <i class="ri-arrow-right-line transition group-hover:translate-x-0.5" aria-hidden="true"></i>
                            </span>
                        </div>
                    </a>
                @endforeach
            </section>
        </div>
    </div>
</x-seller-layout>
