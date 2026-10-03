{{-- resources/views/seller/partials/shop-phone.blade.php --}}
@if (Auth::user()->shop)
    <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm sm:mt-8">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="ri-phone-line text-xl"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-gray-950">Телефон магазина</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Контактный номер для покупателей</p>
                </div>
            </div>

            @if(Auth::user()->shop->is_phone_verified)
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Подтверждён
                </span>
            @elseif(Auth::user()->shop->phone)
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    Ожидает SMS
                </span>
            @else
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    Не указан
                </span>
            @endif
        </div>

        <div class="space-y-4 p-4 sm:p-5">
            @if(Auth::user()->shop->is_phone_verified)
                @include('seller.partials.phone.verified')
            @else
                @include('seller.partials.phone.unverified')
            @endif

            @if(Auth::user()->shop->phone)
                @if(Auth::user()->shop->is_phone_verified)
                    <div class="flex flex-col gap-1.5 border-t border-gray-100 pt-3 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                        <span class="flex items-center gap-2 text-emerald-700">
                            <i class="ri-shield-check-line text-base"></i>
                            SMS-подтверждение активно
                        </span>
                        @if(Auth::user()->shop->phone_verified_at)
                            <span>Проверен {{ Auth::user()->shop->phone_verified_at->diffForHumans() }}</span>
                        @endif
                    </div>
                @else
                    @include('seller.partials.phone.verification-flow')
                @endif
            @else
                <p class="flex items-center gap-2 border-t border-gray-100 pt-3 text-xs text-gray-500">
                    <i class="ri-information-line text-base text-gray-400"></i>
                    После сохранения номера станет доступно SMS-подтверждение.
                </p>
            @endif
        </div>
    </section>
@endif
