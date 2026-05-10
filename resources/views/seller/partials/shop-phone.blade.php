{{-- resources/views/seller/partials/shop-phone.blade.php --}}
@if (Auth::user()->shop)
    <section class="mt-6 sm:mt-8 overflow-hidden rounded-xl sm:rounded-2xl border border-gray-200/70 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50/50 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
                        <i class="ri-phone-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950">Телефон магазина</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Контактный номер и подтверждение для покупателей</p>
                    </div>
                </div>

                @if(Auth::user()->shop->is_phone_verified)
                    <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Подтверждён
                    </span>
                @elseif(Auth::user()->shop->phone)
                    <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm font-semibold">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Ожидает SMS
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-gray-600 text-sm font-semibold">
                        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                        Не указан
                    </span>
                @endif
            </div>
        </div>

        <div class="p-4 sm:p-6">
            <div class="grid lg:grid-cols-2 gap-4 sm:gap-5 items-stretch">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Номер и изменение</p>
                            <p class="text-xs text-gray-500 mt-0.5">Обновите контактный номер магазина</p>
                        </div>
                        <i class="ri-edit-line text-indigo-500 text-lg"></i>
                    </div>

                    @if(Auth::user()->shop->is_phone_verified)
                        @include('seller.partials.phone.verified')
                    @else
                        @include('seller.partials.phone.unverified')
                    @endif
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">SMS подтверждение</p>
                            <p class="text-xs text-gray-500 mt-0.5">Подтверждение защищает доверие к магазину</p>
                        </div>
                        <i class="ri-message-2-line text-amber-500 text-lg"></i>
                    </div>

                    @if(Auth::user()->shop->phone)
                        @if(Auth::user()->shop->is_phone_verified)
                            <div class="h-full rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 sm:p-5 flex flex-col justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-sm shrink-0">
                                        <i class="ri-shield-check-line text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-950">SMS подтверждение активно</p>
                                        <p class="text-sm text-gray-600 mt-1">Номер магазина уже подтверждён и готов для связи с покупателями.</p>
                                    </div>
                                </div>
                                <div class="mt-5 rounded-xl bg-white/70 border border-emerald-100 px-3 py-2 text-xs text-emerald-700 flex items-center gap-2">
                                    <i class="ri-check-double-line"></i>
                                    Дополнительных действий не требуется
                                </div>
                            </div>
                        @else
                            @include('seller.partials.phone.verification-flow')
                        @endif
                    @else
                        <div class="h-full rounded-2xl border border-gray-200 bg-gray-50 p-4 sm:p-5 flex flex-col justify-between">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl bg-white text-gray-500 border border-gray-200 flex items-center justify-center shadow-sm shrink-0">
                                    <i class="ri-information-line text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-950">Сначала укажите номер</p>
                                    <p class="text-sm text-gray-600 mt-1">После сохранения номера здесь появится отправка SMS-кода.</p>
                                </div>
                            </div>
                            <div class="mt-5 rounded-xl bg-white border border-gray-200 px-3 py-2 text-xs text-gray-500 flex items-center gap-2">
                                <i class="ri-arrow-left-line"></i>
                                Заполните поле слева
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 rounded-xl bg-slate-50 border border-slate-100 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-gray-500">
                <span class="flex items-center gap-2">
                    <i class="ri-shield-check-line text-slate-400"></i>
                    Номер используется для доверия покупателей и сервисных уведомлений
                </span>

                @if(Auth::user()->shop->phone_verified_at)
                    <span class="flex items-center gap-2 text-emerald-700">
                        <i class="ri-time-line"></i>
                        Подтверждён {{ Auth::user()->shop->phone_verified_at->diffForHumans() }}
                    </span>
                @endif
            </div>
        </div>
    </section>
@endif
