<x-dynamic-component :component="$chatLayout ?? 'buyer-layout'" title="Служба поддержки">
    <div class="support-mobile-safe mx-auto w-full max-w-8xl min-w-0 px-3 py-4 pb-24 sm:px-6 sm:py-8 lg:pb-8">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
            <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
                    <p class="text-sm font-semibold text-indigo-600">WebVitrina support</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Служба поддержки</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Откройте отдельный support-чат, если нужна помощь с заказом, продавцом, покупателем, оплатой или безопасностью.
                    </p>
                </div>

                <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-6">
                    <form method="POST" action="{{ route('support.start') }}" class="min-w-0 rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                        @csrf
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20">
                            <i class="ri-chat-3-line text-2xl"></i>
                        </div>
                        <h2 class="mt-4 text-lg font-bold text-slate-950">Онлайн-чат</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ $supportConversation ? 'У вас уже есть открытый support-чат. Можно продолжить переписку.' : 'Создайте приватный чат с поддержкой внутри сайта.' }}
                        </p>
                        <button class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="ri-customer-service-2-line"></i>
                            {{ $supportConversation ? 'Открыть чат' : 'Начать чат' }}
                        </button>
                    </form>

                    <a href="mailto:support@webvitrina.com"
                       class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-indigo-200 hover:bg-white">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-indigo-600 ring-1 ring-slate-200">
                            <i class="ri-mail-line text-2xl"></i>
                        </div>
                        <h2 class="mt-4 text-lg font-bold text-slate-950">Email</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Для документов и длинных обращений можно написать на почту.</p>
                        <div class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700">
                            support@webvitrina.com
                            <i class="ri-arrow-right-up-line"></i>
                        </div>
                    </a>
                </div>
            </section>

            <aside class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-base font-bold text-slate-950">Когда писать</h2>
                <div class="mt-4 space-y-3">
                    @foreach([
                        ['icon' => 'ri-shield-check-line', 'text' => 'Подозрение на мошенничество или просьбы уйти из WebVitrina.'],
                        ['icon' => 'ri-error-warning-line', 'text' => 'Спор по заказу, оплате, доставке или состоянию товара.'],
                        ['icon' => 'ri-chat-delete-line', 'text' => 'Оскорбления, спам или нарушение правил общения.'],
                        ['icon' => 'ri-question-answer-line', 'text' => 'Нужна помощь с профилем, корзиной, отзывами или настройками.'],
                    ] as $item)
                        <div class="flex gap-3 rounded-xl bg-slate-50 p-3">
                            <i class="{{ $item['icon'] }} mt-0.5 text-lg text-indigo-600"></i>
                            <p class="text-sm leading-6 text-slate-600">{{ $item['text'] }}</p>
                        </div>
                    @endforeach
                </div>

                @if($supportConversation)
                    <a href="{{ route('chats.show', $supportConversation) }}"
                       class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                        <i class="ri-arrow-right-line"></i>
                        Продолжить support-чат
                    </a>
                @endif
            </aside>
        </div>
    </div>
</x-dynamic-component>
