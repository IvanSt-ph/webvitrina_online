{{-- resources/views/seller/partials/shop.blade.php --}}
<section class="space-y-6 rounded-none border-0 bg-transparent p-0 sm:rounded-2xl sm:border sm:border-neutral-200 sm:bg-white sm:p-6 lg:p-8">

    {{-- 🔹 Заголовок --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <i class="ri-store-2-line text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-neutral-950">Информация о магазине</h2>
                <p class="mt-0.5 text-sm text-neutral-500">Основные данные и контакты вашего магазина</p>
            </div>
        </div>
        <div class="flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm text-neutral-500">
            <i class="ri-history-line"></i>
            <span>Обновлено: {{ Auth::user()->shop?->updated_at?->diffForHumans() ?? '—' }}</span>
        </div>
    </div>

    {{-- ✅ Уведомление об успехе --}}
    @if (session('status') === 'shop-updated')
        <div x-data="{ show: true }" 
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50">
            <div class="relative p-4">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500"></div>
                <div class="flex items-center gap-3 pl-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center shadow-sm">
                        <i class="ri-check-line text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-emerald-800">Данные магазина обновлены! ✨</p>
                        <p class="text-xs text-emerald-600 mt-0.5">{{ session('message', 'Изменения сохранены успешно') }}</p>
                    </div>
                    <button @click="show = false" class="text-emerald-400 hover:text-emerald-600 transition-colors">
                        <i class="ri-close-line text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- 🔹 Основная форма --}}
    <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-6" id="shop-update-form">
        @csrf
        @method('PATCH')

        {{-- 🏪 Основные данные магазина --}}
        <div class="space-y-5 rounded-2xl bg-neutral-50 p-4 sm:p-6">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <i class="ri-information-line text-sm"></i>
                </div>
                Основная информация
            </h3>
            
            <div class="grid md:grid-cols-2 gap-6">
                {{-- Название магазина --}}
                <div class="space-y-2">
                    <label class="flex items-center gap-1 text-sm font-medium text-neutral-700">
                        <i class="ri-building-2-line text-brand-400 text-sm"></i>
                        Название магазина
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                        <div class="relative">
                            <input type="text" 
                                   name="name"
                                   value="{{ old('name', Auth::user()->shop?->name) }}"
                                   placeholder="Например: ТехноМаркет 24"
                                   maxlength="255"
                                   class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-10 pr-4
                                          focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                          transition-all duration-200 outline-none @error('name') border-rose-300 bg-rose-50/50 @enderror">
                            <i class="ri-building-2-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 transition-colors group-focus-within:text-brand-500"></i>
                        </div>
                    </div>
                    @error('name')
                        <p class="text-xs text-rose-600 mt-1 flex items-center gap-1">
                            <i class="ri-error-warning-line"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Город --}}
                <div class="space-y-2">
                    <label class="flex items-center gap-1 text-sm font-medium text-neutral-700">
                        <i class="ri-map-pin-line text-brand-400 text-sm"></i>
                        Город
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                        <div class="relative">
                            <input type="text" 
                                   name="city"
                                   value="{{ old('city', Auth::user()->shop?->city) }}"
                                   placeholder="Тирасполь"
                                   maxlength="255"
                                   class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-10 pr-4
                                          focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                          transition-all duration-200 outline-none @error('city') border-rose-300 bg-rose-50/50 @enderror">
                            <i class="ri-map-pin-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 transition-colors group-focus-within:text-brand-500"></i>
                        </div>
                    </div>
                    @error('city')
                        <p class="text-xs text-rose-600 mt-1 flex items-center gap-1">
                            <i class="ri-error-warning-line"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 📝 Описание магазина --}}
        <div class="space-y-4 rounded-2xl bg-neutral-50 p-4 sm:p-6">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <i class="ri-file-text-line text-sm"></i>
                </div>
                Описание магазина
            </h3>
            
            <div class="space-y-3" x-data="{ charCount: {{ strlen(old('description', Auth::user()->shop?->description ?? '')) }} }">
                <label class="block text-sm font-medium text-neutral-700">Расскажите о вашем магазине</label>
                <div class="relative group">
                    <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                    <div class="relative">
                        <textarea name="description"
                                  rows="4"
                                  maxlength="450"
                                  placeholder="Кратко опишите ассортимент, преимущества, доставку или особые условия..."
                                  @input="charCount = $event.target.value.length"
                                  class="w-full resize-none rounded-xl border border-neutral-200 bg-white py-3 pl-4 pr-16
                                         focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                         transition-all duration-200 outline-none">{{ old('description', Auth::user()->shop?->description) }}</textarea>
                        <div class="absolute bottom-3 right-3 rounded-lg border border-brand-200/50 bg-brand-50/80 px-2 py-1 text-xs text-brand-700">
                            <span x-text="charCount"></span>/450
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔗 Социальные сети и мессенджеры --}}
        <div class="space-y-5 rounded-2xl bg-neutral-50 p-4 sm:p-6">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <i class="ri-share-line text-sm"></i>
                </div>
                Социальные сети и мессенджеры
            </h3>
            
            <div class="grid sm:grid-cols-2 gap-6">
                {{-- Facebook --}}
                <div class="space-y-2">
                    <label class="flex items-center gap-1 text-sm font-medium text-neutral-700">
                        <i class="ri-facebook-circle-fill text-brand-500"></i>
                        Facebook
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                        <div class="relative">
                            <input type="url" 
                                   name="facebook"
                                   value="{{ old('facebook', Auth::user()->shop?->facebook) }}"
                                   placeholder="https://facebook.com/yourpage"
                                   maxlength="255"
                                   class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-10 pr-4
                                          focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                          transition-all duration-200 outline-none">
                            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 transition-colors group-focus-within:text-brand-500"></i>
                        </div>
                    </div>
                </div>

                {{-- Instagram --}}
                <div class="space-y-2">
                    <label class="flex items-center gap-1 text-sm font-medium text-neutral-700">
                        <i class="ri-instagram-line text-brand-500"></i>
                        Instagram
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                        <div class="relative">
                            <input type="url" 
                                   name="instagram"
                                   value="{{ old('instagram', Auth::user()->shop?->instagram) }}"
                                   placeholder="https://instagram.com/yourpage"
                                   maxlength="255"
                                   class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-10 pr-4
                                          focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                          transition-all duration-200 outline-none">
                            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 transition-colors group-focus-within:text-brand-500"></i>
                        </div>
                    </div>
                </div>

                {{-- Telegram --}}
                <div class="space-y-2">
                    <label class="flex items-center gap-1 text-sm font-medium text-neutral-700">
                        <i class="ri-telegram-fill text-brand-500"></i>
                        Telegram
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                        <div class="relative">
                            <input type="url" 
                                   name="telegram"
                                   value="{{ old('telegram', Auth::user()->shop?->telegram) }}"
                                   placeholder="https://t.me/yourchannel"
                                   maxlength="255"
                                   class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-10 pr-4
                                          focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                          transition-all duration-200 outline-none">
                            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 transition-colors group-focus-within:text-brand-500"></i>
                        </div>
                    </div>
                </div>

                {{-- WhatsApp --}}
                <div class="space-y-2">
                    <label class="flex items-center gap-1 text-sm font-medium text-neutral-700">
                        <i class="ri-whatsapp-line text-brand-500"></i>
                        WhatsApp
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                        <div class="relative">
                            <input type="url" 
                                   name="whatsapp"
                                   value="{{ old('whatsapp', Auth::user()->shop?->whatsapp) }}"
                                   placeholder="https://wa.me/79990000000"
                                   maxlength="255"
                                   class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-10 pr-4
                                          focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                          transition-all duration-200 outline-none">
                            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 transition-colors group-focus-within:text-brand-500"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Подсказка --}}
            <div class="rounded-xl border border-brand-100 bg-brand-50/70 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-brand-100">
                        <i class="ri-information-line text-brand-600 text-sm"></i>
                    </div>
                    <p class="text-xs leading-5 text-brand-800/80">
                        Укажите ссылки на ваши социальные сети. Это повысит доверие покупателей и упростит связь.
                    </p>
                </div>
            </div>
        </div>

        {{-- 💾 Кнопка сохранения --}}
        <div class="flex justify-end border-t border-neutral-100 pt-6">
            <x-action-button size="lg">
                <i class="ri-save-3-line text-lg"></i>
                Сохранить изменения
                <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
            </x-action-button>
        </div>
    </form>
</section>

{{-- Alpine is provided by the seller layout Vite bundle. --}}
