<x-app-layout :title="$product->title">

    <x-slot name="specs">
        @include('components.product.specs', ['product' => $product])
    </x-slot>


    @php
        $isFav = auth()->check() && $product->isFavoritedBy(auth()->user());
        $adminChatId = request()->integer('admin_chat');
        $isAdminProductPreview = auth()->check() && auth()->user()->role === 'admin' && $adminChatId;
    @endphp
    
    <div class="w-full max-w-[1440px] mx-auto pt-0 sm:pt-20 pb-10 px-3 sm:px-4 md:px-6 lg:px-8">
        @if($isAdminProductPreview)
            <div class="mb-3 rounded-2xl border border-indigo-100 bg-indigo-50/90 p-3 shadow-sm sm:mb-4 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="text-xs font-bold uppercase tracking-wide text-indigo-600">Админ-просмотр товара</div>
                        <div class="mt-1 truncate text-sm font-semibold text-slate-900">
                            Карточка открыта из диалога ID {{ $adminChatId }}
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.chats.show', $adminChatId) }}"
                           class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700">
                            <i class="ri-arrow-left-line"></i>
                            Вернуться в чат
                        </a>
                        <a href="{{ route('admin.products.edit', $product) }}"
                           class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-indigo-200 bg-white px-3 text-xs font-bold text-indigo-700 transition hover:bg-indigo-50">
                            <i class="ri-edit-2-line"></i>
                            Редактировать
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Хлебные крошки --}}
        <x-product.breadcrumbs :product="$product" />


        {{-- Верхний блок: Галерея + Инфо + Цена --}}
        <div class="grid gap-6 lg:grid-cols-12 bg-white border rounded-3xl p-6 shadow-sm">
            <x-product.gallery :product="$product" />
            <x-product.info :product="$product" />
            <x-product.buy-box :product="$product" :isFav="$isFav" />
        </div>




  
        {{-- Карта местоположения (раскрывающаяся) --}}
        <div 
            x-data="{ openMap: false }" 
            class=" mt-6 bg-white border rounded-2xl shadow-sm">

            {{-- Заголовок + стрелка --}}
            <button 
                @click="openMap = !openMap" 
                class="w-full flex items-center justify-between p-4 text-lg font-medium">
                <span>Местоположение продавца</span>

                <svg 
                    :class="openMap ? 'rotate-90' : ''"
                    class="w-6 h-6 text-gray-600 transition-transform duration-300"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            {{-- Контейнер карты --}}
                <div class="mt-0 mr-2 ml-2 p-4" x-show="openMap" x-collapse>
                    <x-product.map :product="$product" />
                </div>


        </div>

        





        {{-- Вкладки: описание, размеры, характеристики, отзывы --}}
      <x-product.tabs :product="$product" :reviews="$reviews" :myReview="$myReview" />

        {{-- Похожие товары --}}
        <x-product.related :items="$related" />

    </div>

    @if($chatConversation)
        @include('chats.partials.widget', [
            'conversation' => $chatConversation,
            'messages' => $chatMessages,
            'hasOlderMessages' => $chatHasOlderMessages,
            'oldestMessageId' => $chatOldestMessageId,
            'latestMessageId' => $chatLatestMessageId,
            'latestReadOutgoingMessageId' => $chatLatestReadOutgoingMessageId,
            'contextProduct' => $product,
            'returnUrl' => route('product.show', [
                'identifier' => $product->slug,
                'chat' => $chatConversation->id,
            ], false),
            'closeUrl' => route('product.show', $product->slug, false),
        ])
    @endif

</x-app-layout>
