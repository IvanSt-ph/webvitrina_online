@php
    $chatLayout = auth()->user()->isSeller() ? 'seller-layout' : 'buyer-layout';
@endphp

<x-dynamic-component :component="$chatLayout" title="Чаты">
    <div class="mx-auto flex min-h-[calc(100dvh-8.75rem)] w-full max-w-8xl min-w-0 flex-col overflow-x-hidden px-3 pb-4 pt-[9.75rem] sm:min-h-0 sm:px-6 sm:py-8">
        <div class="fixed inset-x-0 top-0 z-30 mb-4 shrink-0 border-b border-slate-100 bg-neutral-50/95 px-3 pb-4 pt-4 backdrop-blur sm:static sm:mx-0 sm:mb-6 sm:border-0 sm:bg-transparent sm:px-0 sm:pb-0 sm:pt-0">
            @if(auth()->user()->isSeller())
                <a href="{{ route('seller.cabinet') }}"
                   class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600 lg:hidden">
                    <i class="ri-arrow-left-line"></i>
                    Назад в кабинет
                </a>
            @endif
            <p class="text-sm font-medium text-indigo-600">Сообщения</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Чаты</h1>
            <p class="mt-2 text-slate-500">Обсуждайте детали покупки напрямую с продавцами.</p>
        </div>

        <div class="grid min-h-0 min-w-0 flex-1 gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">
            <section class="min-h-0 min-w-0 overflow-hidden rounded-[2rem] border border-slate-200/80 bg-slate-50/70 p-3">
                @include('chats.partials.list', ['currentConversation' => null])
                @if(method_exists($conversations, 'links'))
                    <div class="mt-4">{{ $conversations->links() }}</div>
                @endif
            </section>

            <section class="relative hidden min-w-0 overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm lg:block">
                <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-indigo-100 blur-3xl"></div>
                <div class="relative flex min-h-[420px] flex-col items-center justify-center text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-[1.75rem] bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-xl shadow-indigo-500/20">
                        <i class="ri-chat-3-line text-4xl"></i>
                    </div>
                    <h2 class="mt-6 text-2xl font-semibold text-slate-900">Выберите диалог</h2>
                    <p class="mt-2 max-w-md text-slate-500">Или откройте страницу магазина и нажмите «Написать», чтобы начать новый разговор.</p>
                </div>
            </section>
        </div>
    </div>

    @if(auth()->user()->isSeller())
        @include('layouts.mobile-bottom-seller-nav')
    @endif
</x-dynamic-component>
