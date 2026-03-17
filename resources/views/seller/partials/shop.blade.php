{{-- resources/views/seller/partials/shop.blade.php --}}
<section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8 space-y-8">

    {{-- 🔹 Заголовок с градиентом --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-sm">
                <i class="ri-store-2-line text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Информация о магазине</h2>
                <p class="text-sm text-gray-500 mt-0.5">Основные данные и контакты вашего магазина</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
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
             class="overflow-hidden rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/70 shadow-sm">
            <div class="relative p-4">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-emerald-400 to-teal-400"></div>
                <div class="flex items-center gap-3 pl-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-400 flex items-center justify-center shadow-sm">
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
    <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-8" id="shop-update-form">
        @csrf
        @method('PATCH')

        {{-- 🏪 Основные данные магазина --}}
        <div class="bg-gray-50 rounded-xl p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-sm">
                    <i class="ri-information-line text-white text-xs"></i>
                </div>
                Основная информация
            </h3>
            
            <div class="grid md:grid-cols-2 gap-6">
                {{-- Название магазина --}}
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                        <i class="ri-building-2-line text-indigo-400 text-sm"></i>
                        Название магазина
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-indigo-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                        <div class="relative">
                            <input type="text" 
                                   name="name"
                                   value="{{ old('name', Auth::user()->shop?->name) }}"
                                   placeholder="Например: ТехноМаркет 24"
                                   maxlength="255"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                          focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50 
                                          transition-all duration-200 outline-none @error('name') border-rose-300 bg-rose-50/50 @enderror">
                            <i class="ri-building-2-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
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
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                        <i class="ri-map-pin-line text-indigo-400 text-sm"></i>
                        Город
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-indigo-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                        <div class="relative">
                            <input type="text" 
                                   name="city"
                                   value="{{ old('city', Auth::user()->shop?->city) }}"
                                   placeholder="Тирасполь"
                                   maxlength="255"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                          focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50 
                                          transition-all duration-200 outline-none @error('city') border-rose-300 bg-rose-50/50 @enderror">
                            <i class="ri-map-pin-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
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
        <div class="bg-gray-50 rounded-xl p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-sm">
                    <i class="ri-file-text-line text-white text-xs"></i>
                </div>
                Описание магазина
            </h3>
            
            <div class="space-y-3" x-data="{ charCount: {{ strlen(old('description', Auth::user()->shop?->description ?? '')) }} }">
                <label class="block text-sm font-medium text-gray-700">Расскажите о вашем магазине</label>
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-indigo-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                    <div class="relative">
                        <textarea name="description"
                                  rows="4"
                                  maxlength="450"
                                  placeholder="Кратко опишите ассортимент, преимущества, доставку или особые условия..."
                                  @input="charCount = $event.target.value.length"
                                  class="w-full pl-4 pr-16 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                         focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50 
                                         transition-all duration-200 outline-none resize-none">{{ old('description', Auth::user()->shop?->description) }}</textarea>
                        <div class="absolute bottom-3 right-3 text-xs bg-indigo-50/80 text-indigo-700 px-2 py-1 rounded-lg border border-indigo-200/50">
                            <span x-text="charCount"></span>/450
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔗 Социальные сети и мессенджеры --}}
        <div class="bg-gray-50 rounded-xl p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-sm">
                    <i class="ri-share-line text-white text-xs"></i>
                </div>
                Социальные сети и мессенджеры
            </h3>
            
            <div class="grid sm:grid-cols-2 gap-6">
                {{-- Facebook --}}
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                        <i class="ri-facebook-circle-fill text-blue-600"></i>
                        Facebook
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-blue-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                        <div class="relative">
                            <input type="url" 
                                   name="facebook"
                                   value="{{ old('facebook', Auth::user()->shop?->facebook) }}"
                                   placeholder="https://facebook.com/yourpage"
                                   maxlength="255"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                          focus:border-blue-300 focus:ring-4 focus:ring-blue-100/50 
                                          transition-all duration-200 outline-none">
                            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                        </div>
                    </div>
                </div>

                {{-- Instagram --}}
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                        <i class="ri-instagram-line text-pink-600"></i>
                        Instagram
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-pink-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                        <div class="relative">
                            <input type="url" 
                                   name="instagram"
                                   value="{{ old('instagram', Auth::user()->shop?->instagram) }}"
                                   placeholder="https://instagram.com/yourpage"
                                   maxlength="255"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                          focus:border-pink-300 focus:ring-4 focus:ring-pink-100/50 
                                          transition-all duration-200 outline-none">
                            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-pink-500 transition-colors"></i>
                        </div>
                    </div>
                </div>

                {{-- Telegram --}}
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                        <i class="ri-telegram-fill text-blue-500"></i>
                        Telegram
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-blue-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                        <div class="relative">
                            <input type="url" 
                                   name="telegram"
                                   value="{{ old('telegram', Auth::user()->shop?->telegram) }}"
                                   placeholder="https://t.me/yourchannel"
                                   maxlength="255"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                          focus:border-blue-300 focus:ring-4 focus:ring-blue-100/50 
                                          transition-all duration-200 outline-none">
                            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                        </div>
                    </div>
                </div>

                {{-- WhatsApp --}}
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                        <i class="ri-whatsapp-line text-green-600"></i>
                        WhatsApp
                    </label>
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-green-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                        <div class="relative">
                            <input type="url" 
                                   name="whatsapp"
                                   value="{{ old('whatsapp', Auth::user()->shop?->whatsapp) }}"
                                   placeholder="https://wa.me/79990000000"
                                   maxlength="255"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                          focus:border-green-300 focus:ring-4 focus:ring-green-100/50 
                                          transition-all duration-200 outline-none">
                            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-green-500 transition-colors"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Подсказка --}}
            <div class="bg-indigo-50/50 rounded-lg p-4 border border-indigo-200/50">
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                        <i class="ri-information-line text-indigo-600 text-sm"></i>
                    </div>
                    <p class="text-xs text-indigo-800/70">
                        Укажите ссылки на ваши социальные сети. Это повысит доверие покупателей и упростит связь.
                    </p>
                </div>
            </div>
        </div>

        {{-- 💾 Кнопка сохранения --}}
        <div class="flex justify-end pt-6 border-t border-gray-100">
            <button type="submit"
                    class="relative overflow-hidden group px-6 py-3.5 bg-indigo-500/90 hover:bg-indigo-600 
                           text-white font-medium rounded-xl shadow-md hover:shadow-lg 
                           transition-all duration-300 transform hover:-translate-y-0.5
                           flex items-center gap-2 backdrop-blur-sm border border-indigo-400/30">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="ri-save-3-line text-lg"></i>
                    Сохранить изменения
                    <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
                </span>
                <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                             group-hover:translate-y-0 transition-transform duration-300"></span>
            </button>
        </div>
    </form>
</section>

{{-- 🔹 Подключение стилей --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>

{{-- 🔹 Alpine.js для анимаций --}}
<script src="//unpkg.com/alpinejs" defer></script>