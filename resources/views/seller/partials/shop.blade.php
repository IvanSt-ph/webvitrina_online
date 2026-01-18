<section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-6">

  {{-- 🔹 Заголовок --}}
  <div class="flex items-center justify-between flex-wrap gap-2">
    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
      <i class="ri-store-2-line text-indigo-500"></i>
      Информация о магазине
    </h2>
    <span class="text-xs text-gray-400">
      Последнее обновление:
      {{ Auth::user()->shop?->updated_at?->diffForHumans() ?? '—' }}
    </span>
  </div>

  {{-- 🔹 Форма обновления данных магазина без телефона --}}
  <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-6" id="shop-update-form">
    @csrf
    @method('PATCH')

    {{-- 🏪 Название и город --}}
    <div class="grid sm:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Название магазина</label>
        <input type="text" name="name"
               value="{{ old('name', Auth::user()->shop?->name) }}"
               placeholder="Например: ТехноМаркет 24"
               maxlength="255"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Город</label>
        <input type="text" name="city"
               value="{{ old('city', Auth::user()->shop?->city) }}"
               placeholder="Тирасполь"
               maxlength="255"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>
    </div>

    {{-- 📝 Описание магазина --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Описание магазина</label>
      <textarea name="description"
                rows="4"
                maxlength="1000"
                placeholder="Кратко опишите ассортимент, преимущества, доставку или особые условия..."
                x-data="{ count: {{ strlen(old('description', Auth::user()->shop?->description ?? '')) }} }"
                @input="count = $event.target.value.length"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition resize-none">{{ old('description', Auth::user()->shop?->description) }}</textarea>

      <div class="flex justify-between text-xs text-gray-400 mt-1">
        <span>Максимум 1000 символов</span>
        <span x-text="count + ' / 1000'"></span>
      </div>
    </div>

    {{-- 🔹 Социальные сети --}}
    <div class="grid sm:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
        <input type="url" name="facebook"
               value="{{ old('facebook', Auth::user()->shop?->facebook) }}"
               placeholder="https://facebook.com/yourpage"
               maxlength="255"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
        <input type="url" name="instagram"
               value="{{ old('instagram', Auth::user()->shop?->instagram) }}"
               placeholder="https://instagram.com/yourpage"
               maxlength="255"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Telegram</label>
        <input type="url" name="telegram"
               value="{{ old('telegram', Auth::user()->shop?->telegram) }}"
               placeholder="https://t.me/yourchannel"
               maxlength="255"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
        <input type="url" name="whatsapp"
               value="{{ old('whatsapp', Auth::user()->shop?->whatsapp) }}"
               placeholder="https://wa.me/79990000000"
               maxlength="255"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>
    </div>

    {{-- 💾 Кнопка сохранения --}}
    <div class="flex justify-end pt-4 border-t border-gray-100">
      <button type="submit"
              class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700
                     text-white rounded-lg text-sm font-medium shadow-sm flex items-center gap-2 transition">
        <i class="ri-save-3-line text-base"></i>
        Обновить данные магазина
      </button>
    </div>
  </form>

 

</section>

{{-- 🔹 Подключение intl-tel-input для автоформата номера --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.min.css"/>
