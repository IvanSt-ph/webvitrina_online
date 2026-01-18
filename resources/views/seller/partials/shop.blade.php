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
         class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
          <i class="ri-check-line text-green-600"></i>
        </div>
        <div class="flex-1">
          <p class="font-medium text-green-800">Данные магазина обновлены!</p>
          <p class="text-sm text-green-600">{{ session('message', 'Изменения сохранены успешно') }}</p>
        </div>
        <button @click="show = false" class="text-green-400 hover:text-green-600">
          <i class="ri-close-line text-lg"></i>
        </button>
      </div>
    </div>
  @endif

  {{-- 🔹 Основная форма в карточках --}}
  <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-8" id="shop-update-form">
    @csrf
    @method('PATCH')

    {{-- 🏪 Основные данные магазина --}}
    <div class="bg-gray-50 rounded-xl p-6 space-y-6">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <i class="ri-information-line text-indigo-500"></i>
        Основная информация
      </h3>
      
      <div class="grid md:grid-cols-2 gap-6">
        {{-- Название магазина --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <i class="ri-building-2-line text-gray-400"></i>
            Название магазина
          </label>
          <div class="relative">
            <input type="text" 
                   name="name"
                   value="{{ old('name', Auth::user()->shop?->name) }}"
                   placeholder="Например: ТехноМаркет 24"
                   maxlength="255"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 
                          focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                          transition-all duration-200 @error('name') border-red-500 @enderror">
            <i class="ri-building-2-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          </div>
          @error('name')
            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
              <i class="ri-error-warning-line"></i> {{ $message }}
            </p>
          @enderror
        </div>

        {{-- Город --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <i class="ri-map-pin-line text-gray-400"></i>
            Город
          </label>
          <div class="relative">
            <input type="text" 
                   name="city"
                   value="{{ old('city', Auth::user()->shop?->city) }}"
                   placeholder="Тирасполь"
                   maxlength="255"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 
                          focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                          transition-all duration-200 @error('city') border-red-500 @enderror">
            <i class="ri-map-pin-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          </div>
          @error('city')
            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
              <i class="ri-error-warning-line"></i> {{ $message }}
            </p>
          @enderror
        </div>
      </div>
    </div>

    {{-- 📝 Описание магазина --}}
    <div class="bg-gray-50 rounded-xl p-6 space-y-4">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <i class="ri-file-text-line text-indigo-500"></i>
        Описание магазина
      </h3>
      
      <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700">Расскажите о вашем магазине</label>
        <div class="relative">
          <textarea name="description"
                    rows="4"
                    maxlength="1000"
                    placeholder="Кратко опишите ассортимент, преимущества, доставку или особые условия..."
                    x-data="{ count: {{ strlen(old('description', Auth::user()->shop?->description ?? '')) }} }"
                    @input="count = $event.target.value.length"
                    class="w-full pl-4 pr-4 py-3 rounded-xl border border-gray-300 
                           focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                           transition-all duration-200 resize-none">{{ old('description', Auth::user()->shop?->description) }}</textarea>
          <div class="absolute bottom-3 right-3 text-xs text-gray-400 bg-white px-2 py-1 rounded-lg">
            <span x-text="count"></span>/1000
          </div>
        </div>
        <p class="text-xs text-gray-500">Это описание увидят покупатели при посещении вашего магазина</p>
      </div>
    </div>

    {{-- 🔗 Социальные сети и мессенджеры --}}
    <div class="bg-gray-50 rounded-xl p-6 space-y-6">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <i class="ri-share-line text-indigo-500"></i>
        Социальные сети и мессенджеры
      </h3>
      
      <div class="grid sm:grid-cols-2 gap-6">
        {{-- Facebook --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <i class="ri-facebook-circle-fill text-blue-600"></i>
            Facebook
          </label>
          <div class="relative">
            <input type="url" 
                   name="facebook"
                   value="{{ old('facebook', Auth::user()->shop?->facebook) }}"
                   placeholder="https://facebook.com/yourpage"
                   maxlength="255"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 
                          focus:border-blue-500 focus:ring-2 focus:ring-blue-100 
                          transition-all duration-200">
            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          </div>
        </div>

        {{-- Instagram --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <i class="ri-instagram-line text-pink-600"></i>
            Instagram
          </label>
          <div class="relative">
            <input type="url" 
                   name="instagram"
                   value="{{ old('instagram', Auth::user()->shop?->instagram) }}"
                   placeholder="https://instagram.com/yourpage"
                   maxlength="255"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 
                          focus:border-pink-500 focus:ring-2 focus:ring-pink-100 
                          transition-all duration-200">
            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          </div>
        </div>

        {{-- Telegram --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <i class="ri-telegram-fill text-blue-500"></i>
            Telegram
          </label>
          <div class="relative">
            <input type="url" 
                   name="telegram"
                   value="{{ old('telegram', Auth::user()->shop?->telegram) }}"
                   placeholder="https://t.me/yourchannel"
                   maxlength="255"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 
                          focus:border-blue-400 focus:ring-2 focus:ring-blue-50 
                          transition-all duration-200">
            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          </div>
        </div>

        {{-- WhatsApp --}}
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <i class="ri-whatsapp-line text-green-600"></i>
            WhatsApp
          </label>
          <div class="relative">
            <input type="url" 
                   name="whatsapp"
                   value="{{ old('whatsapp', Auth::user()->shop?->whatsapp) }}"
                   placeholder="https://wa.me/79990000000"
                   maxlength="255"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 
                          focus:border-green-500 focus:ring-2 focus:ring-green-100 
                          transition-all duration-200">
            <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          </div>
        </div>
      </div>
      
      <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-sm text-blue-800">
        <div class="flex items-start gap-2">
          <i class="ri-information-line text-blue-600 mt-0.5"></i>
          <p>Укажите ссылки на ваши социальные сети. Это повысит доверие покупателей и упростит связь.</p>
        </div>
      </div>
    </div>

    {{-- 💾 Кнопка сохранения --}}
    <div class="flex justify-end pt-6 border-t border-gray-100">
      <button type="submit"
              class="relative overflow-hidden group px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 
                     hover:from-indigo-700 hover:to-purple-700 text-white font-medium rounded-xl 
                     shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5
                     flex items-center gap-2">
        <span class="relative z-10 flex items-center gap-2">
          <i class="ri-save-3-line text-lg"></i>
          Сохранить изменения
        </span>
        <span class="absolute inset-0 bg-gradient-to-r from-indigo-700 to-purple-700 translate-y-full 
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